<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Override definitions form
 *
 * @package mod_vpl
 * @copyright 2021 Astor Bizard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../locallib.php');
require_once(__DIR__ . '/../vpl.class.php');

use mod_vpl\overrides\form;
use mod_vpl\overrides\options_form;

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Return HTML fragment for buttons of a given override row.
 * @param int $id VPL cmid.
 * @param int $overrideid Override id for the current row.
 * @param int $editing The id of the override being edited.
 * @return string HTML fragment for buttons.
 */
function vpl_get_overrideactions($id, $overrideid, $editing) {
    global $OUTPUT, $PAGE;
    if ($editing == $overrideid) {
        vpl_include_jsfile('override.js');
        $save = '<a href="#" onclick="VPL.submitOverrideForms();">' .
                    $OUTPUT->pix_icon('save', get_string('save'), 'mod_vpl') .
                '</a>';
        $cancel = '<a href="#" onclick="VPL.cancelOverrideForms();">' .
                    $OUTPUT->pix_icon('cancel', get_string('cancel'), 'mod_vpl') .
                '</a>';
        return $save . $cancel;
    } else if ($editing === null) {
        $edit = '<a href="?id=' . $id . '&edit=' . $overrideid . '#scroll_point">' .
                    $OUTPUT->pix_icon('editthis', get_string('edit'), 'mod_vpl') .
                '</a>';
        $copy = '<a href="?id=' . $id . '&edit=0&copy=' . $overrideid . '#scroll_point">' .
                    $OUTPUT->pix_icon('copy', get_string('copy'), 'mod_vpl') .
                '</a>';
        $deletebuttonid = 'delete_override_' . $overrideid;
        $delete = '<a id="' . $deletebuttonid . '" href="?id=' . $id . '&delete=' . $overrideid . '">' .
                      $OUTPUT->pix_icon('delete', get_string('delete'), 'mod_vpl') .
                  '</a>';
        $PAGE->requires->event_handler(
            '#' . $deletebuttonid,
            'click',
            'M.util.show_confirm_dialog',
            ['message' => get_string('confirmoverridedeletion', VPL)]
        );
        return $edit . $copy . $delete;
    } else {
        return '';
    }
}

require_login();

global $PAGE, $OUTPUT, $DB;

$id = required_param('id', PARAM_INT);
$edit = optional_param('edit', null, PARAM_INT);
$delete = optional_param('delete', null, PARAM_INT);
$update = optional_param('update', null, PARAM_INT);
$copyid = optional_param('copy', null, PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->require_capability(VPL_MANAGE_CAPABILITY);
$vpl->prepare_page('forms/overrides.php', [ 'id' => $id ]);

$vplid = $vpl->get_instance()->id;
$overrides = vpl_get_overrides($vplid);

if (!empty($edit) && !isset($overrides[$edit])) {
    $edit = null;
}

$fields = ['startdate', 'duedate', 'reductionbyevaluation', 'freeevaluations', 'password'];

// Prepare forms if we are editing or submitting an override.
if ($edit !== null || $update !== null) {
    // Compute users that are not already affected to another override.
    if (!$vpl->is_group_activity()) {
        $alreadyassignedusers = [];
        foreach ($overrides as $override) {
            if ($override->id != $edit && $override->id != $update && !empty($override->userids)) {
                $alreadyassignedusers = array_merge($alreadyassignedusers, explode(',', $override->userids));
            }
        }
        $availableusers = array_map(
            'fullname',
            array_filter(get_enrolled_users(context_module::instance($id)), function ($user) use ($alreadyassignedusers) {
                    return !in_array($user->id, $alreadyassignedusers);
            })
        );
    } else {
        $availableusers = null;
    }
    // Compute groups that are not already affected to another override.
    $groups = groups_get_all_groups($vpl->get_course()->id, 0, $vpl->get_course_module()->groupingid);
    if (!empty($groups)) {
        $alreadyassignedgroups = [];
        foreach ($overrides as $override) {
            if ($override->id != $edit && $override->id != $update && !empty($override->groupids)) {
                $alreadyassignedgroups = array_merge($alreadyassignedgroups, explode(',', $override->groupids));
            }
        }
        $availablegroups = array_filter($groups, function ($group) use ($alreadyassignedgroups) {
            return !in_array($group->id, $alreadyassignedgroups);
        });
        $availablegroups = array_map(function ($group) {
            return $group->name;
        }, $availablegroups);
    } else {
        $availablegroups = null;
    }

    // Prepare forms.
    $usersform = new form($availableusers, $availablegroups);
    $overrideid = $edit;
    if ($overrideid === null) {
        $overrideid = $update;
    }
    $optionsform = new options_form($id, $overrideid);
}

if ($delete !== null) {
    $overrideid = $delete;
    if (isset($overrides[$overrideid])) {
        $override = $overrides[$overrideid];
        // Delete associated calendar events.
        $vpl->update_override_calendar_events($override, null, true);
        // Delete the override.
        $DB->delete_records(VPL_OVERRIDES, ['id' => $overrideid]);
        $DB->delete_records(VPL_ASSIGNED_OVERRIDES, ['override' => $overrideid]);
        \mod_vpl\event\override_deleted::log($vpl, $overrideid);
    }
    // Properly reload the page.
    redirect(new moodle_url('/mod/vpl/forms/overrides.php', [ 'id' => $id ]));
}

if ($update !== null) {
    // Update or create an override.
    $override = $optionsform->get_data();
    if ($override !== null) {
        vpl_truncate_vpl($override); // Trim and cut password if too large.
        unset($override->id); // The id field of the form is not the override id - do not use it.
        foreach ($fields as $field) {
            if (!isset($override->{'override_' . $field})) {
                $override->$field = null;
            }
        }
        if (empty($override->userids)) {
            $override->userids = null;
        }
        if (empty($override->groupids)) {
            $override->groupids = null;
        }
        $override->vpl = $vplid;
        $old = [
                'userids' => [],
                'groupids' => [],
        ];
        if ($update == 0) {
            // Create the override.
            $newid = $DB->insert_record(VPL_OVERRIDES, $override);
            $override->id = $newid;
            $oldoverride = null;
            \mod_vpl\event\override_created::log($vpl, $newid);
        } else {
            // Update the override.
            $override->id = $update;
            if (isset($overrides[$override->id])) {
                $oldoverride = $overrides[$override->id];
                if (!empty($oldoverride->userids)) {
                    $old['userids'] = explode(',', $oldoverride->userids);
                }
                if (!empty($oldoverride->groupids)) {
                    $old['groupids'] = explode(',', $oldoverride->groupids);
                }
            } else {
                $oldoverride = null;
            }
            $DB->update_record(VPL_OVERRIDES, $override);
            \mod_vpl\event\override_updated::log($vpl, $update);
        }

        $record = [
                'vpl' => $override->vpl,
                'override' => $override->id,
        ];
        // Process users and groups for the updated override, to update assigned overrides table.
        foreach (['userid', 'groupid'] as $key) {
            $record['userid'] = null;
            $record['groupid'] = null;
            $ids = $key . 's';
            sort($old[$ids]);
            if (!empty($override->$ids)) {
                $newids = explode(',', $override->$ids);
            } else {
                $newids = [];
            }
            sort($newids);
            $i = 0;
            $n = count($old[$ids]);
            $j = 0;
            $m = count($newids);
            // Walk simultaneously through both arrays.
            while ($i < $n || $j < $m) {
                if ($i == $n || ($j < $m && $old[$ids][$i] > $newids[$j])) {
                    // Insert new user/group.
                    $record[$key] = $newids[$j];
                    $DB->insert_record(VPL_ASSIGNED_OVERRIDES, $record);
                    $j++;
                } else if ($j == $m || ($newids[$j] > $old[$ids][$i])) {
                    // Remove old user/group.
                    $DB->delete_records(VPL_ASSIGNED_OVERRIDES, [
                            'vpl' => $override->vpl,
                            'override' => $override->id,
                            $key => $old[$ids][$i],
                    ]);
                    $i++;
                } else {
                    // This user/group was and is still there, skip.
                    $i++;
                    $j++;
                }
            }
        }
        // Create or update associated calendar events.
        $vpl->update_override_calendar_events($override, $oldoverride);
    }
    // Do not redirect if validation fails.
    if ($optionsform->is_validated() || $optionsform->is_cancelled()) {
        // Properly reload the page.
        redirect(new moodle_url('/mod/vpl/forms/overrides.php', [ 'id' => $id ]));
    }
}

$PAGE->force_settings_menu();
$PAGE->requires->css(new moodle_url('/mod/vpl/css/overrides.css'));
$vpl->print_header(get_string('overrides', VPL));
$vpl->print_heading_with_help('overrides');
echo $OUTPUT->box_start();

$table = new html_table();

$table->head = [
        '#',
        get_string('override_users', VPL) . $OUTPUT->help_icon('override_users', VPL),
        get_string('override_options', VPL),
        get_string('actions'),
];
$table->align = [
        'right',
        'left',
        'left',
];
$table->size = [
        '2em',
        '35%',
        '',
        '10%',
];

$table->data = [];

$scrollpoint = "<i id='scroll_point'></i>";
// Populate table with existing overrides.
$i = 1;
foreach ($overrides as $override) {
    if ($edit == $override->id) {
        // This is the override being edited: fill and display the forms.
        $usersform->set_data([
                'users' => explode(',', $override->userids),
                'groups' => explode(',', $override->groupids),
        ]);
        $users = $scrollpoint . $usersform->render();
        $formdata = [];
        foreach ($fields as $field) {
            if ($override->$field === null) {
                $formdata[$field] = $vpl->get_instance()->$field;
                $formdata['override_' . $field] = false;
            } else {
                $formdata[$field] = $override->$field;
                $formdata['override_' . $field] = true;
            }
        }
        $optionsform->set_data($formdata);
        $overridedata = $optionsform->render();
    } else {
        // Set up a proper display for affected users and groups.
        $users = [];
        if (!empty($override->userids)) {
            $users = array_map('fullname', $DB->get_records_list('user', 'id', explode(',', $override->userids), 'id'));
        }
        if (!empty($override->groupids)) {
            $users = array_merge($users, array_map(function ($group) {
                return '<i class="fa fa-fw fa-group"></i>&nbsp;' . $group->name;
            }, $DB->get_records_list('groups', 'id', explode(',', $override->groupids), 'id')));
        }
        $users = implode(', ', $users);
        if ($users == '') {
            $users = get_string('none');
        }

        // Display active override options.
        $overridedata = '';
        foreach (['startdate', 'duedate'] as $datefield) {
            if ($override->$datefield !== null) {
                $overridedata .= get_string($datefield, VPL) . ': ';
                if ($override->$datefield > 0) {
                    $overridedata .= userdate($override->$datefield);
                } else {
                    $overridedata .= get_string('disabled', VPL);
                }
                $overridedata .= '<br>';
            }
        }
        if ($override->password !== null) {
            $overridedata .= get_string('password') . ' ';
            $infohs = new mod_vpl\util\hide_show();
            $overridedata .= $infohs->generate();
            $overridedata .= $infohs->content_in_tag('span', s($override->password)) . '<br>';
        }
        foreach (['reductionbyevaluation', 'freeevaluations'] as $field) {
            if ($override->$field !== null) {
                $overridedata .= get_string($field, VPL) . ': ' . $override->$field . '<br>';
            }
        }
        if ($overridedata == '') {
            $overridedata = get_string('none');
        }
    }
    $table->data[] = [$i++, $users, $overridedata, vpl_get_overrideactions($id, $override->id, $edit)];
}

if ($edit === 0) {
    // A new override is being created, put an additional row at the end.
    $users = $scrollpoint . $usersform->render();
    $formdata = [];
    foreach ($fields as $field) {
        $formdata[$field] = $vpl->get_instance()->$field;
        $formdata['override_' . $field] = false;
    }
    if (isset($overrides[$copyid])) {
        $override = $overrides[$copyid];
        foreach ($fields as $field) {
            if ($override->$field !== null) {
                $formdata[$field] = $override->$field;
                $formdata['override_' . $field] = true;
            }
        }
    }
    $optionsform->set_data($formdata);
    $overridedata = $optionsform->render();
    $table->data[] = ['', $users, $overridedata, vpl_get_overrideactions($id, 0, 0)];
}

echo html_writer::table($table);

if ($edit === null) {
    // No override is being edited, add a button to create one.
    echo '<a href="?id=' . $id . '&edit=0#scroll_point" class="btn btn-secondary">' . get_string('addoverride', VPL) . '</a>';
}

echo $OUTPUT->box_end();
$vpl->print_footer();
