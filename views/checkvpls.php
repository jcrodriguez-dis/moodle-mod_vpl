<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// VPL for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with VPL for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Check all VPL instances in a course
 *
 * @package mod_vpl
 * @copyright 2017 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodriguez-del-Pino
 **/

require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/../vpl_submission.class.php');

global $DB, $PAGE, $OUTPUT, $USER;

$id = required_param('id', PARAM_INT); // Course id.

// Check course existence.
if (! $course = $DB->get_record("course", [ 'id' => $id ])) {
    throw new moodle_exception('invalidcourseid');
}
require_course_login($course);

$PAGE->set_url('/mod/vpl/views/checkvpls.php', [ 'id' => $id ]);

$admin = is_siteadmin();
$sitewide = $admin && optional_param('sitewide', 0, PARAM_INT);
$strvpls = get_string('modulenameplural', VPL);
$pagetitle = get_string('checkgroups', VPL);
$coursename = format_string($course->fullname);
if (!$sitewide) {
    $pagetitle .= ' (' . $coursename . ')';
}
$PAGE->navbar->add($strvpls);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($coursename);
echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

$context = context_course::instance($course->id);
$einfo = [
        'context' => $context,
        'objectid' => $course->id,
        'userid' => $USER->id,
];
\mod_vpl\event\vpl_checkvpls::log($einfo);
$ovpls = [];
if ($admin) {
    $url = $PAGE->url;
    $filterurls = [
            $url->out(true, [ 'sitewide' => 0 ]),
            $url->out(true, [ 'sitewide' => 1 ]),
    ];
    echo $OUTPUT->url_select([
            $filterurls[0] => get_string('checkforcourse', VPL, $coursename),
            $filterurls[1] => get_string('checksitewide', VPL),
    ], $filterurls[$sitewide], null);
    echo '<br>';
}

if ($sitewide) {
    $ovpls = $DB->get_records(VPL, [], 'id');
} else if (has_capability(VPL_MANAGE_CAPABILITY, $context)) {
    $ovpls = get_all_instances_in_course(VPL, $course);
}

// Get and select vpls to show.
foreach ($ovpls as $ovpl) {
    $vpl = new mod_vpl(false, $ovpl->id);
    $instance = $vpl->get_instance();
    if ($vpl->is_group_activity()) {
        // Check groups concistence.
        $groupingid = $vpl->get_course_module()->groupingid;
        $groups = groups_get_all_groups($vpl->get_course()->id, 0, $groupingid);
        $students = $vpl->get_students();
        foreach ($students as $student) {
            $student->groups = [];
        }
        foreach ($groups as $group) {
            $users = $vpl->get_group_members($group->id);
            foreach ($users as $user) {
                if (isset($students[$user->id])) {
                    $students[$user->id]->groups[] = $group->id;
                }
            }
        }
        $multigroup = '';
        $nogroup = '';
        foreach ($students as $student) {
            if (count($student->groups) == 0) {
                if ($nogroup == '') {
                    $nogroup = get_string('nogroupsassigned', 'group') . ':<ul>';
                }
                $nogroup .= '<li>' . fullname($student) . '</li>';
            }
            if (count($student->groups) > 1) {
                $multigroup .= fullname($student)  . ' ' . get_string('groups', 'group') . ':<ul>';
                foreach ($student->groups as $groupid) {
                    $multigroup .= '<li>' . s($groups[$groupid]->name) . '</li>';
                }
                $multigroup .= '</ul>';
            }
        }
        if ($nogroup > '') {
            $nogroup .= '</ul>';
        }

        $title = '';
        if ($sitewide) {
            $title = $vpl->get_course()->shortname . ' > ';
        }
        $title .= $vpl->get_printable_name();
        $icon = 'check';
        $level = 'success';
        $text = '';
        if ($nogroup > '') {
            $icon = 'warning';
            $level = 'warning';
            $text = $OUTPUT->notification($nogroup, 'warning') . $text;
        }
        if ($multigroup > '') {
            $icon = 'remove';
            $level = 'error';
            $text = $OUTPUT->notification($multigroup, 'error') . $text;
        } else {
            $gradersid = array_keys($vpl->get_graders());
            $ing = $DB->get_in_or_equal($gradersid);
            $select = '( not userid ' . $ing[0] . ' ) and vpl = ? and groupid = 0';
            $params = $ing[1];
            $params[] = $instance->id;
            if ($DB->count_records_select(VPL_SUBMISSIONS, $select, $params) > 0) {
                $text .= $OUTPUT->notification('Fixing submissions with no group or pre V3.3 data', 'success');
                $vpl->update_group_v32();
            }
        }
        $cmid = $vpl->get_course_module()->id;
        echo '<h5>';
        echo '<a href="/mod/vpl/view.php?id=' . $cmid . '" class="text-' . $level . '">' . s($title) . '</a>';
        echo ' <i class="icon fa fa-' . $icon . ' text-' . $level . ' fa-fw "></i>';
        echo '</h5>';
        if ($text > '') {
            echo $OUTPUT->box($text);
        }
    }
}

echo $OUTPUT->footer();
