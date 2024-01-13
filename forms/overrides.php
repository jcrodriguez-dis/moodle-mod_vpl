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
                    $OUTPUT->pix_icon( 'save', get_string('save'), 'mod_vpl' ) .
                '</a>';
        $cancel = '<a href="#" onclick="VPL.cancelOverrideForms();">' .
                    $OUTPUT->pix_icon( 'cancel', get_string('cancel'), 'mod_vpl' ) .
                '</a>';
        return $save . $cancel;
    } else if ($editing === null) {
        $edit = '<a href="?id=' . $id . '&edit=' . $overrideid . '">' .
                    $OUTPUT->pix_icon( 'editthis', get_string('edit'), 'mod_vpl' ) .
                '</a>';
        $deletebuttonid = 'delete_override_' . $overrideid;
        $delete = '<a id="' . $deletebuttonid . '" href="?id=' . $id . '&delete=' . $overrideid . '">' .
                      $OUTPUT->pix_icon( 'delete', get_string('delete'), 'mod_vpl' ) .
                  '</a>';
        $PAGE->requires->event_handler('#' . $deletebuttonid, 'click', 'M.util.show_confirm_dialog',
                ['message' => get_string('confirmoverridedeletion', VPL)]);
        return $edit . $delete;
    } else {
        return '';
    }
}

class vpl_override_users_form extends moodleform {
    protected $users;
    protected $groups;
    public function __construct($users, $groups) {
        $this->users = $users;
        $this->groups = $groups;
        parent::__construct();
        $this->_form->updateAttributes(['id' => 'vpl_override_users_form']);
    }
    protected function definition() {
        global $CFG;
        $mform = &$this->_form;
        foreach (['users', 'groups'] as $field) {
            if ($this->$field !== null) {
                $mform->addElement('html', '<div>');
                $mform->addElement('autocomplete', $field, get_string($field), $this->$field, ['multiple' => true]);
                $mform->addElement('html', '</div>');
            }
        }
    }
}

class vpl_override_options_form extends moodleform {
    protected $id;
    protected $overrideid;
    public function __construct($id, $overrideid) {
        $this->id = $id;
        $this->overrideid = $overrideid;
        parent::__construct();
        $this->_form->updateAttributes(['id' => 'vpl_override_options_form']);
    }
    protected function definition() {
        $mform = &$this->_form;
        $mform->addElement('hidden', 'id', $this->id);
        $mform->setType('id', PARAM_RAW);
        $mform->addElement('hidden', 'update', $this->overrideid);
        $mform->setType('update', PARAM_RAW);
        $mform->addElement('hidden', 'edit', $this->overrideid);
        $mform->setType('edit', PARAM_RAW);
        $mform->addElement('hidden', 'userids');
        $mform->setType('userids', PARAM_RAW);
        $mform->addElement('hidden', 'groupids');
        $mform->setType('groupids', PARAM_RAW);

        foreach (['startdate', 'duedate'] as $datefield) {
            $mform->addElement('html', '<div class="override-option">');
            $mform->addElement('checkbox', 'override_' . $datefield, get_string( $datefield, VPL ), get_string( 'override', VPL ));
            $mform->addHelpButton('override_' . $datefield, 'override', VPL);
            $mform->addElement('date_time_selector', $datefield, null, ['optional' => true]);
            $mform->disabledIf($datefield, 'override_' . $datefield);
            $mform->addElement('html', '</div>');
        }

        $passwordfield = 'password';
        $mform->addElement('html', '<div class="override-option">');
        $mform->addElement('checkbox', 'override_' . $passwordfield, get_string($passwordfield), get_string( 'override', VPL ));
        $mform->addHelpButton('override_' . $passwordfield, 'override', VPL);
        $mform->addElement( 'passwordunmask', $passwordfield, null, ['optional' => true]);
        $mform->setType($passwordfield, PARAM_TEXT);
        $mform->setDefault($passwordfield, '');
        $mform->disabledIf($passwordfield, 'override_' . $passwordfield);
        $mform->addElement('html', '</div>');

        foreach (['reductionbyevaluation', 'freeevaluations'] as $textfield) {
            $mform->addElement('html', '<div class="override-option">');
            $mform->addElement('checkbox', 'override_' . $textfield, get_string( $textfield, VPL ), get_string( 'override', VPL ));
            $mform->addHelpButton('override_' . $textfield, 'override', VPL);
            $mform->addElement('text', $textfield, null);
            $mform->setType($textfield, PARAM_TEXT);
            $mform->setDefault($textfield, 0);
            $mform->disabledIf($textfield, 'override_' . $textfield);
            $mform->addElement('html', '</div>');
        }

        $this->add_action_buttons();
    }

    public static function validate($field, $pattern, $message, & $data, & $errors) {
        $data[$field] = trim( $data[$field] );
        $res = preg_match($pattern, $data[$field]);
        if ( $res == 0 || $res == false) {
            $errors[$field] = $message;
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        self::validate('freeevaluations', '/^[0-9]*$/', '[0..]', $data, $errors);
        self::validate('reductionbyevaluation', '/^[0-9]*(\.[0-9]+)?%?$/', '#[.#][%]', $data, $errors);
        return $errors;
    }
}

require_login();

global $PAGE, $OUTPUT, $DB;

$id = required_param( 'id', PARAM_INT );
$edit = optional_param('edit', null, PARAM_INT);
$delete = optional_param('delete', null, PARAM_INT);
$update = optional_param('update', null, PARAM_INT);
$vpl = new mod_vpl( $id );
$vpl->require_capability( VPL_MANAGE_CAPABILITY );
$vpl->prepare_page( 'forms/overrides.php', [ 'id' => $id ] );

$vplid = $vpl->get_instance()->id;

$sql = 'SELECT ao.id as aid, o.*, ao.userid as userids, ao.groupid as groupids
            FROM {vpl_overrides} o
            LEFT JOIN {vpl_assigned_overrides} ao ON ao.override = o.id
            WHERE o.vpl = :vplid
            ORDER BY o.id ASC';
$overridesseparated = $DB->get_records_sql($sql, ['vplid' => $vplid]);
$overrides = vpl_agregate_overrides($overridesseparated);

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
        $availableusers = array_map('fullname',
                array_filter(get_enrolled_users(context_module::instance($id)), function($user) use ($alreadyassignedusers) {
                    return !in_array($user->id, $alreadyassignedusers);
                }
        ));
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
        $availablegroups = array_filter($groups, function($group) use ($alreadyassignedgroups) {
            return !in_array($group->id, $alreadyassignedgroups);
        });
        $availablegroups = array_map(function($group) {
            return $group->name;
        }, $availablegroups);
    } else {
        $availablegroups = null;
    }

    // Prepare forms.
    $usersform = new vpl_override_users_form($availableusers, $availablegroups);
    $overrideid = $edit;
    if ($overrideid === null) {
        $overrideid = $update;
    }
    $optionsform = new vpl_override_options_form($id, $overrideid);
}

if ($delete !== null) {
    $overrideid = $delete;
    if (isset($overrides[$overrideid])) {
        $override = $overrides[$overrideid];
        // Delete associated calendar events.
        $vpl->update_override_calendar_events($override, null, true);
        // Delete the override.
        $DB->delete_records( VPL_OVERRIDES, ['id' => $overrideid] );
        $DB->delete_records( VPL_ASSIGNED_OVERRIDES, ['override' => $overrideid] );
        \mod_vpl\event\override_deleted::log($vpl, $overrideid);
    }
    // Properly reload the page.
    redirect(new moodle_url('/mod/vpl/forms/overrides.php', [ 'id' => $id ]));
}

if ($update !== null) {
    // Update or create an override.
    $override = $optionsform->get_data();
    vpl_truncate_vpl($override); // Trim and cut password if too large.
    unset($override->id); // The id field of the form is not the override id - do not use it.
    if ($override !== null) {
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
            $newid = $DB->insert_record( VPL_OVERRIDES, $override );
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
            $DB->update_record( VPL_OVERRIDES, $override );
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
                    $DB->insert_record( VPL_ASSIGNED_OVERRIDES, $record);
                    $j++;
                } else if ($j == $m || ($newids[$j] > $old[$ids][$i])) {
                    // Remove old user/group.
                    $DB->delete_records( VPL_ASSIGNED_OVERRIDES, [
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
$PAGE->requires->css( new moodle_url( '/mod/vpl/css/overrides.css' ) );
$vpl->print_header( get_string( 'overrides', VPL ) );
$vpl->print_heading_with_help( 'overrides' );
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

// Populate table with existing overrides.
$i = 1;
foreach ($overrides as $override) {
    if ($edit == $override->id) {
        // This is the override being edited: fill and display the forms.
        $usersform->set_data([
                'users' => explode(',', $override->userids),
                'groups' => explode(',', $override->groupids),
        ]);
        $users = $usersform->render();
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
            $users = array_merge($users, array_map(function($group) {
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
            $overridedata .= $infohs->content_in_span(s($override->password)) . '<br>';
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
    $users = $usersform->render();
    $formdata = [];
    foreach ($fields as $field) {
        $formdata[$field] = $vpl->get_instance()->$field;
        $formdata['override_' . $field] = false;
    }
    $optionsform->set_data($formdata);
    $overridedata = $optionsform->render();
    $table->data[] = ['', $users, $overridedata, vpl_get_overrideactions($id, 0, 0)];
}

echo html_writer::table($table);

if ($edit === null) {
    // No override is being edited, add a button to create one.
    echo '<a href="?id=' . $id . '&edit=0" class="btn btn-secondary">' . get_string('addoverride', VPL) . '</a>';
}

echo $OUTPUT->box_end();
$vpl->print_footer();
