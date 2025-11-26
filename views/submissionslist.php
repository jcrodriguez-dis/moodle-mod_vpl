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
 * List student submissions of a VPL instances
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__) . '/../../../config.php');
global $CFG, $USER, $OUTPUT;

require_once($CFG->dirroot . '/mod/vpl/locallib.php');
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/mod/vpl/vpl_submission_CE.class.php');
require_once($CFG->dirroot . '/mod/vpl/views/sh_factory.class.php');
require_once($CFG->libdir . '/tablelib.php');

/**
 * Class to compare userinfo and submission objects.
 *
 * This class is used by usort to order the list of submissions.
 */
class vpl_submissionlist_order {
    /**
     * Field to compare.
     * @var string
     */
    protected static $field;
    /**
     * Value to return when ascending or descending order.
     * @var int
     */
    protected static $ascending;

    /**
     * Static class to compare userinfo objects.
     *
     * This is used by usort to order the list of submissions.
     * It is static because usort does not call static methods in old PHP versions.
     *
     * @var vpl_submissionlist_order
     */
    protected static $corder = null;

    /**
     * Compare two userinfo objects by user id.
     *
     * @param object $a first object
     * @param object $b second object
     * @return int -1, 0 or 1
     */
    public static function cpm_userid($a, $b) {
        if ($a->userinfo->id < $b->userinfo->id) {
            return self::$ascending;
        } else {
            return - self::$ascending;
        }
    }

    /**
     * Compare two userinfo objects.
     *
     * If userinfo is not set, compare by user id.
     * If userinfo is set, compare by field and then by user id.
     *
     * @param object $a first object
     * @param object $b second object
     * @return int -1, 0 or 1
     */
    public static function cpm_userinfo($a, $b) {
        $field = self::$field;
        $adata = $a->userinfo->$field;
        $bdata = $b->userinfo->$field;
        if ($adata == $bdata) {
            return self::cpm_userid($a, $b);
        }
        if ($adata < $bdata) {
            return self::$ascending;
        } else {
            return - self::$ascending;
        }
    }

    /**
     * Compare two submissions.
     *
     * If submission is not set, compare by user id.
     * If submission is set, compare by field and then by user id.
     *
     * @param object $a first object
     * @param object $b second object
     * @return int -1, 0 or 1
     */
    public static function cpm_submission($a, $b) {
        $field = self::$field;
        $submissiona = $a->submission;
        $submissionb = $b->submission;
        if ($submissiona == $submissionb) {
            return self::cpm_userid($a, $b);
        }
        if ($submissiona == null) {
            return self::$ascending;
        }
        if ($submissionb == null) {
            return - self::$ascending;
        }
        $adata = $submissiona->get_instance()->$field;
        $bdata = $submissionb->get_instance()->$field;
        if ($adata === null) {
            return self::$ascending;
        }
        if ($bdata === null) {
            return - self::$ascending;
        }
        if ($adata == $bdata) {
            return self::cpm_userid($a, $b);
        } else if ($adata < $bdata) {
            return self::$ascending;
        } else {
            return - self::$ascending;
        }
    }

    /**
     * Compare two variations.
     *
     * If variation is not set, compare by user id.
     * If variation is set, compare by variation and then by user id.
     *
     * @param object $a first object
     * @param object $b second object
     * @return int -1, 0 or 1
     */
    public static function cpm_variation($a, $b) {
        if (!isset($a->variation)) {
            return self::cpm_userid($a, $b);
        }
        $adata = $a->variation;
        $bdata = $b->variation;
        if ($adata == $bdata) {
            return self::cpm_userid($a, $b);
        }
        if ($adata < $bdata) {
            return self::$ascending;
        } else {
            return - self::$ascending;
        }
    }
    /**
     * Check and set data to sort return comparation function.
     * @param string $field field to sort
     * @param bool $ascending true for ascending order
     * @return array [comparator object, comparator function]
     */
    public static function set_order($field, $ascending = true) {
        if (self::$corder === null) {
            self::$corder = new vpl_submissionlist_order();
        }
        $userinfofields = ['firstname' => 0, 'lastname' => 0];
        $submissionfields = [
                'datesubmitted' => 0,
                'gradesortable' => 0,
                'grader' => 0,
                'dategraded' => 0,
                'nsubmissions' => 0,
        ];
        self::$field = $field;
        if ($ascending) {
            self::$ascending = - 1;
        } else {
            self::$ascending = 1;
        }
        // Funtion usort of old PHP versions don't call static class functions.
        if (isset($userinfofields[$field])) {
            return [self::$corder, 'cpm_userinfo'];
        } else if (isset($submissionfields[$field])) {
            return [self::$corder, 'cpm_submission'];
        } else if ($field == 'variation') {
            self::$field = 'lastname';
            return [self::$corder, 'cpm_variation'];
        } else {
            self::$field = 'firstname';
            return [self::$corder, 'cpm_userinfo'];
        }
    }
}

/**
 * Preprare javascript to evaluate users
 * @param int $id activity cm id
 * @param array $evaluateusers list of users to evaluate
 */
function vpl_prepare_evaluation($id, $evaluateusers) {
    $usersid = [];
    foreach ($evaluateusers as $user) {
        $usersid[] = ['id' => $user->id, 'subid' => $user->subid];
    }
    $options = [ 'baseurl' => "../forms/edit.json.php?id={$id}&userid="];
    vpl_editor_util::print_js_i18n();
    vpl_editor_util::generate_batch_evaluate_sript($options, $usersid);
}

/**
 * Get list menu for submissions list
 * @param bool $showgrades show feedback report
 * @param int $id activity cm id
 * @return action_menu
 */
function vpl_get_listmenu($showgrades, $id) {
    $menu = new action_menu();
    $url = new moodle_url('/mod/vpl/views/activityworkinggraph.php', ['id' => $id]);
    $menu->add(vpl_get_action_link('submissions', $url));
    if ($showgrades) {
        $url = new moodle_url('/mod/vpl/views/submissionslist.php', ['id' => $id]);
        $menu->add(vpl_get_action_link('submissionslist', $url));
    } else {
        $url = new moodle_url('/mod/vpl/views/submissionslist.php', ['id' => $id, 'showgrades' => 1]);
        $menu->add(vpl_get_action_link('gradercomments', $url));
    }
    $url = new moodle_url('/mod/vpl/views/downloadallsubmissions.php', ['id' => $id]);
    $menu->add(vpl_get_action_link('downloadsubmissions', $url));
    $url = new moodle_url('/mod/vpl/views/downloadallsubmissions.php', ['id' => $id, 'all' => 1]);
    $menu->add(vpl_get_action_link('downloadallsubmissions', $url));
    return $menu;
}

/**
 * Return action_menu_link for menu in list
 * @param string $str
 * @param moodle_url $link
 * @param string $comp value for get_string
 * @return action_menu_link_secondary
 */
function vpl_get_action_link($str, $link, $comp = 'mod_vpl') {
    $stri18n = get_string($str, $comp);
    $iconname = $str == 'gradenoun' ? 'grade' : $str;
    return new action_menu_link_secondary($link, new pix_icon($iconname, '', 'mod_vpl'), $stri18n);
}

/**
 * Show perpage button if needed
 * @param flexible_table $table
 * @param int $ntabledata number of rows
 * @param array $params url parameters
 */
function vpl_show_perpage_button($table, $ntabledata, $params) {
    if ($ntabledata > 0) {
        if ($table->get_default_per_page() < $table->get_page_size()) {
            $perpagesize = $table->get_default_per_page();
            $perpagestring = get_string('showperpage', '', $table->get_default_per_page());
        } else if ($table->get_page_size() < $ntabledata) {
            $perpagesize = TABLE_SHOW_ALL_PAGE_SIZE;
            $perpagestring = get_string('showall', '', $ntabledata);
        }
        if (isset($perpagesize) && isset($perpagestring)) {
            $perpageurl = new moodle_url('/mod/vpl/views/submissionslist.php', $params);
            $perpageurl->param('tperpage', $perpagesize);
            echo html_writer::link(
                $perpageurl,
                $perpagestring,
                ['class' => 'btn btn-secondary']
            );
            echo '<br>';
        }
    }
}

/**
 * Show graders table
 * @param int $id activity cm id
 * @param int $usernumber number of students
 * @param array $gradersdata {graderid => marks}
 */
function vpl_show_graders_table($id, $usernumber, $gradersdata) {
    global $CFG, $OUTPUT;
    if (count($gradersdata)) {
        $title = get_string('teachers');
        echo '<br>';
        echo html_writer::tag('b', $title);
        echo '<br>';
        if ($CFG->fullnamedisplay == 'lastname firstname') { // For better view (dlnsk).
            $namehead = get_string('lastname') . ' / ' . get_string('firstname');
        } else {
            $namehead = get_string('firstname') . ' / ' . get_string('lastname');
        }
        $tablegraders = new flexible_table("vpl-submissionslist-graders-{$id}");
        $tablegraders->set_attribute('title', $title);
        $tablegraders->define_headers(['#', null, $namehead, get_string(vpl_get_gradenoun_str())]);
        $tablegraders->define_columns(['#', 'userpic', 'fullname', 'grade']);
        $tablegraders->define_baseurl(new moodle_url('/mod/vpl/views/submissionslist.php', ['id' => $id]));
        $tablegraders->setup();
        $gradernumber = 0;
        foreach ($gradersdata as $graderid => $marks) {
            $gradernumber++;
            $grader = mod_vpl_submission::get_grader($graderid);
            $picture = '';
            if ($graderid > 0) { // No automatic grading.
                $picture = $OUTPUT->user_picture($grader, ['popup' => true]);
            }
            $graderdata = [
                    $gradernumber,
                    $picture,
                    fullname($grader),
                    sprintf('%d/%d  (%5.2f%%)', $marks, $usernumber, (float) 100.0 * $marks / $usernumber),
            ];
            $tablegraders->add_data($graderdata);
        }
        $tablegraders->finish_output();
    }
}

/**
 * Return list of students (in current group if apply) or groups in activity
 * @param object $vpl mod_vpl object of activity
 * @return array of objects
 */
function vpl_get_students($vpl) {
    if ($vpl->is_group_activity()) {
        $cm = $vpl->get_course_module();
        return groups_get_all_groups($vpl->get_course()->id, 0, $cm->groupingid);
    } else {
        $currentgroup = groups_get_activity_group($vpl->get_course_module(), true);
        if (! $currentgroup) {
            $currentgroup = 0;
        }
        return $vpl->get_students($currentgroup);
    }
}

/**
 * Filter students by initials
 * @param object $vpl mod_vpl
 * @param array $allstudents list of students
 * @return array of objects
 */
function vpl_filter_by_initials($vpl, $allstudents) {
    $tilast = optional_param('tilast', '', PARAM_TEXT);
    $tifirst = optional_param('tifirst', '', PARAM_TEXT);
    if ($vpl->is_group_activity()) {
        if ($tilast > '') {
            $newlist = [];
            foreach ($allstudents as $group) {
                if (strcasecmp(substr($group->name, 0, 1), $tilast) == 0) {
                    $newlist[$group->id] = $group;
                }
            }
            $allstudents = $newlist;
        }
    } else {
        if ($tilast > '' || $tifirst > '') {
            $newlist = [];
            foreach ($allstudents as $user) {
                if ($tilast > '' && strcasecmp(substr($user->lastname, 0, 1), $tilast) != 0) {
                    continue;
                }
                if ($tifirst > '' && strcasecmp(substr($user->firstname, 0, 1), $tifirst) != 0) {
                    continue;
                }
                $newlist[$user->id] = $user;
            }
            $allstudents = $newlist;
        }
    }
    return $allstudents;
}

require_login();

$id = required_param('id', PARAM_INT);
$groupid = optional_param('group', - 1, PARAM_INT);
$evaluate = optional_param('evaluate', 0, PARAM_INT);
$showgrades = optional_param('showgrades', 0, PARAM_INT);
$sort = vpl_get_set_session_var('subsort', 'lastname', 'sort');
$sortdir = vpl_get_set_session_var('subsortdir', 3, 'sortdir');
$tilast = optional_param('tilast', '', PARAM_TEXT);
$tifirst = optional_param('tifirst', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$tperpage = vpl_get_set_session_var('tperpage', 30);
$subselection = vpl_get_set_session_var('subselection', 'allsubmissions', 'selection');
$download = optional_param('download', '', PARAM_ALPHA);

$thiddenfields = explode(',', optional_param('thiddenfields', '', PARAM_RAW));
$thide = optional_param('thide', '', PARAM_TEXT);
if ($thide) {
    $thiddenfields[] = $thide;
    $thiddenfields = array_unique($thiddenfields);
}
$tshow = optional_param('tshow', '', PARAM_TEXT);
if ($tshow) {
    $thiddenfields = array_filter($thiddenfields, function ($value) use ($tshow) {
        return $value != $tshow;
    });
}

$params = [
    'id' => $id,
    'showgrades' => $showgrades,
    'group' => $groupid,
    'tilast' => $tilast,
    'tifirst' => $tifirst,
    'tperpage' => $tperpage,
    'thiddenfields' => implode(',', $thiddenfields),
];
$evaluateusers = [];
if ($evaluate > 0) {
    require_once($CFG->dirroot . '/mod/vpl/editor/editor_utility.php');
    vpl_editor_util::generate_requires_evaluation();
}
$vpl = new mod_vpl($id);
$cm = $vpl->get_course_module();
$vpl->require_capability(VPL_GRADE_CAPABILITY);
$vpl->prepare_page('views/submissionslist.php', $params);
$downloading = $download != '';
$noevaluating = $evaluate == 0;

\mod_vpl\event\vpl_all_submissions_viewed::log($vpl);

if (! $downloading) {
    $PAGE->requires->css(new moodle_url('/mod/vpl/css/sh.css'));
    $PAGE->requires->css(new moodle_url('/mod/vpl/css/index.css'));
    $PAGE->requires->css(new moodle_url('/mod/vpl/css/grade.css'));
    // Print header.
    $vpl->print_header(get_string('submissionslist', VPL));
    $vpl->print_view_tabs(basename(__FILE__));
} else {
    // Disable display debugging.
    @ini_set('display_errors', '0');
    $CFG->debugdisplay = 0;
}

// Find out current groups mode.
$groupmode = groups_get_activity_groupmode($cm);
if (! $groupmode) {
    $groupmode = groups_get_course_groupmode($vpl->get_course());
}

$allstudents = vpl_get_students($vpl);
$filteredstudents = vpl_filter_by_initials($vpl, $allstudents);

// Find if using variations.
$vplinstance = $vpl->get_instance();
$usevariations = $vplinstance->usevariations;
if ($usevariations) {
    $variations = $DB->get_records(VPL_VARIATIONS, ['vpl' => $vplinstance->id]);
    $usevariations = count($variations) > 0;
}
if ($usevariations) {
    $assignedvariations = $DB->get_records(VPL_ASSIGNED_VARIATIONS, ['vpl' => $vplinstance->id]);
    $uservariation = [];
    foreach ($assignedvariations as $assignedvariation) {
        $uservariation[$assignedvariation->userid] = $variations[$assignedvariation->variation];
    }
}

// Check if gradeable.
$gradeable = $vpl->get_grade() != 0;

// Get students.
// TODO Improve performance by selecting students using initials.

$submissions = $vpl->filter_submissions_by_students($vpl->all_last_user_submission(), $allstudents);
$submissionsnumber = $vpl->get_submissions_number();
mod_vpl_submission::load_gradebook_grades($vpl);

// Filter by evaluation and get all information.
// Counters for submissions and graded status.
$nsubmissions = 0;
$ngraded = 0;
$alldata = [];
foreach ($filteredstudents as $uginfo) {
    $submission = null;
    if (! isset($submissions[$uginfo->id])) {
        if ($subselection != 'all') {
            continue;
        }
    } else {
        $nsubmissions++;
        $subinstance = $submissions[$uginfo->id];
        $submission = new mod_vpl_submission_CE($vpl, $subinstance);
        $subid = $subinstance->id;
        $subinstance->gradesortable = null;
        if ($subinstance->dategraded > 0) {
            $ngraded++;
            if ($subselection == 'notgraded') {
                continue;
            }
            if ($subselection == 'gradedbyuser' && $subinstance->grader != $USER->id) {
                continue;
            }
            $subinstance->gradesortable = $subinstance->grade;
        } else {
            $subinstance->grade = null;
            if ($subselection == 'graded' || $subselection == 'gradedbyuser') {
                continue;
            }
            $result = $submission->getCE();
            if ($result['executed'] !== 0) {
                $prograde = $submission->proposedGrade($result['execution']);
                if ($prograde > '') {
                    $subinstance->gradesortable = $prograde;
                }
            }
            $result = []; // Dispose array.
        }
        // I know that subinstance isn't the correct place to put nsubmissions but is the easy.
        if (isset($submissionsnumber[$uginfo->id])) {
            $subinstance->nsubmissions = $submissionsnumber[$uginfo->id]->submissions;
        } else {
            $subinstance->nsubmissions = ' ';
        }
    }
    $data = new stdClass();
    $data->userinfo = $uginfo;
    $data->submission = $submission;
    if ($usevariations) {
        if (isset($uservariation[$uginfo->id])) {
            $data->variation = $uservariation[$uginfo->id]->identification;
        } else {
            $data->variation = '';
        }
    }
    // When group activity => add lastname to groupname for order porpouse.
    if ($vpl->is_group_activity()) {
        $data->userinfo->firstname = '';
        $data->userinfo->lastname = $uginfo->name;
    }
    $alldata[] = $data;
}

$groupsurl = vpl_mod_href('views/submissionslist.php', 'id', $id, 'sort', $sort, 'sortdir', $sortdir, 'selection', $subselection);
$baseurl = vpl_mod_href('views/submissionslist.php', 'id', $id, 'group', $groupid);

// Load strings.
$gradenoun = vpl_get_gradenoun_str();
$strsubtime = get_string('submittedon', VPL);
$strgrade = get_string($gradenoun);
$strgrader = get_string('grader', VPL);
$strgradedon = get_string('gradedon', VPL);
$strcomments = get_string('gradercomments', VPL);
$strsubmisions = get_string('submissions', VPL);
$headers = ['#', ''];
$fields = ['#', 'userpic'];
if ($vpl->is_group_activity()) {
    $headers[] = get_string('group');
    $fields[] = 'lastname';
} else {
    $headers[] = '';
    $fields[] = 'fullname';
}
if ($usevariations) {
    $headers[] = get_string('variations', VPL);
    $fields[] = 'variation';
}
if ($showgrades) {
    $headers[] = $strgrade;
    $headers[] = $strcomments;
    $fields[] = 'gradesortable';
    $fields[] = 'gradecomments';
} else {
    $headers[] = $strsubtime;
    $headers[] = $strsubmisions;
    $fields[] = 'datesubmitted';
    $fields[] = 'nsubmissions';
    if ($gradeable) {
        $headers[] = $strgrade;
        $headers[] = $strgrader;
        $headers[] = $strgradedon;
        $fields[] = 'gradesortable';
        $fields[] = 'grader';
        $fields[] = 'dategraded';
    }
}

// Unblock user session.
if (! $downloading) {
    session_write_close();
    $headers[] = $OUTPUT->render(vpl_get_listmenu($showgrades, $id));
    $fields[] = 'actions';
}

// Sort by sort field.
usort($alldata, vpl_submissionlist_order::set_order($sort, $sortdir != 4));

$options = [
    'height' => 550,
    'width' => 780,
    'directories' => 0,
    'location' => 0,
    'menubar' => 0,
    'personalbar' => 0,
    'status' => 0,
    'toolbar' => 0,
];
// Get table data.
$tabledata = [];
$showphoto = $tperpage < 100;
$usernumber = 0;
$gradersdata = []; // Number of revisions made by teacher.
$nextids = []; // Information to get next user in list.
$lastid = 0; // Last id for next.
foreach ($alldata as $data) {
    $actions = new action_menu();
    if ($vpl->is_group_activity()) {
        $gr = $data->userinfo;
        $users = $vpl->get_group_members($gr->id);
        if (count($users) == 0) {
            continue;
        }
        $user = reset($users);
        $user->firstname = '';
        $user->lastname = $gr->name;
    } else {
        $user = $data->userinfo;
    }
    $gradecomments = '';
    $linkparms = ['id' => $id, 'userid' => $user->id];
    if ($data->submission == null) {
        $text = get_string('nosubmission', VPL);
        $hrefview = vpl_mod_href('forms/submissionview.php', 'id', $id, 'userid', $user->id, 'inpopup', 1);
        $action = new popup_action('click', $hrefview, 'viewsub' . $user->id, $options);
        $subtime = $OUTPUT->action_link($hrefview, $text, $action);
        $link = new moodle_url('/mod/vpl/forms/submissionview.php', $linkparms);
        $actions->add(vpl_get_action_link('submissionview', $link));
        $prev = '';
        $grade = '';
        $grader = '';
        $gradedon = '';
    } else {
        $submission = $data->submission;
        $subinstance = $submission->get_instance();
        $hrefview = vpl_mod_href('forms/submissionview.php', 'id', $id, 'userid', $user->id, 'inpopup', 1);
        $hrefprev = vpl_mod_href('views/previoussubmissionslist.php', 'id', $id, 'userid', $user->id, 'inpopup', 1);
        $hrefgrade = vpl_mod_href('forms/gradesubmission.php', 'id', $id, 'userid', $user->id, 'inpopup', 1);
        $link = new moodle_url('/mod/vpl/forms/submissionview.php', $linkparms);
        $actions->add(vpl_get_action_link('submissionview', $link));
        $subtime = $OUTPUT->action_link($hrefview, userdate($subinstance->datesubmitted));
        if ($subinstance->nsubmissions > 0) {
            $prev = $OUTPUT->action_link($hrefprev, $subinstance->nsubmissions);
            $link = new moodle_url('/mod/vpl/views/previoussubmissionslist.php', $linkparms);
            $actions->add(vpl_get_action_link('previoussubmissionslist', $link));
        } else {
            $prev = '';
        }
        $subid = $subinstance->id;
        if ($evaluate == 4) { // Need evaluation all.
            $user->subid = $subid;
            $evaluateusers[] = $user;
        }
        if ($subinstance->dategraded > 0) {
            $text = $submission->get_grade_core();
            // Add proposed grade diff.
            $result = $submission->getCE();
            if ($result['executed'] !== 0) {
                $prograde = $submission->proposedGrade($result['execution']);
                if ($prograde > '' && $prograde != $subinstance->grade) {
                    $text .= ' (' . $prograde . ')';
                }
            }
            $result = []; // Dispose array.
            $text = '<div id="g' . $subid . '" class="gd' . $subid . '">' . $text . '</div>';
            if ($subinstance->grader == $USER->id || $vpl->has_capability(VPL_EDITOTHERSGRADES_CAPABILITY)) {
                $action = new popup_action('click', $hrefgrade, 'gradesub' . $user->id, $options);
                $grade = $OUTPUT->action_link($hrefgrade, $text, $action);
                $link = new moodle_url('/mod/vpl/forms/gradesubmission.php', $linkparms);
                $actions->add(vpl_get_action_link($gradenoun, $link, 'core'));
                // Add new next user.
                if ($lastid) {
                    $nextids[$lastid] = $user->id;
                }
                $lastid = $subid; // Save submission id as next index.
            } else {
                $grade = $text;
            }

            $graderid = $subinstance->grader;
            $graderuser = $submission->get_grader($graderid);
            // Count evaluator marks.
            if (isset($gradersdata[$graderid])) {
                $gradersdata[$graderid]++;
            } else {
                $gradersdata[$graderid] = 1;
            }
            $grader = fullname($graderuser);
            $gradedon = userdate($subinstance->dategraded);
            if ($showgrades) {
                $gradecomments .= $submission->get_detailed_grade();
                $gradecomments .= $submission->print_ce(true);
            }
        } else {
            $result = $submission->getCE();
            $text = '';
            if (($evaluate == 2 && $result['executed'] === 0) || $evaluate == 3) { // Need evaluation.
                $user->subid = $subid;
                $evaluateusers[] = $user;
            }
            if ($result['executed'] !== 0) {
                $prograde = $submission->proposedGrade($result['execution']);
                if ($prograde > '') {
                    $text = get_string('proposedgrade', VPL, $submission->get_grade_core($prograde));
                }
            }
            $result = []; // Dispose array.
            if ($text == '') {
                $text = get_string('nograde');
            }
            $action = new popup_action('click', $hrefgrade, 'gradesub' . $subinstance->userid, $options);
            $text = '<div id="g' . $subid . '" class="gd' . $subid . '">' . $text . '</div>';
            $grade = $OUTPUT->action_link($hrefgrade, $text, $action);
            $grader = '&nbsp;';
            $gradedon = '&nbsp;';
            $link = new moodle_url('/mod/vpl/forms/gradesubmission.php', $linkparms);
            $actions->add(vpl_get_action_link($gradenoun, $link, 'core'));
            // Add new next user.
            if ($lastid) {
                $nextids[$lastid] = $user->id;
            }
            $lastid = $subid; // Save submission id as next index.
            if ($showgrades) {
                $gradecomments = $submission->print_ce(true);
            }
        }
        // Add div id to submission info.
        $grader = '<div id="m' . $subid . '" class="gd' . $subid . '">' . $grader . '</div>';
        $gradedon = '<div id="o' . $subid . '" class="gd' . $subid . '">' . $gradedon . '</div>';
    }
    $url = vpl_mod_href('forms/edit.php', 'id', $id, 'userid', $user->id, 'privatecopy', 1);
    $options = [
            'height' => 550,
            'width' => 780,
            'directories' => 0,
            'location' => 0,
            'menubar' => 0,
            'personalbar' => 0,
            'status' => 0,
            'toolbar' => 0,
    ];
    $action = new popup_action('click', $url, 'privatecopyl' . $id, $options);

    if (isset($subid)) {
        $gradecomments = '<div id="c' . $subid . '" class="gd' . $subid . '">' . $gradecomments . '</div>';
    }

    $usernumber++;
    $usernumberlink = $OUTPUT->action_link($url, $usernumber, $action);
    $linkcopyparms = ['id' => $id, 'userid' => $user->id, 'privatecopy' => 1];
    $link = new moodle_url('/mod/vpl/forms/edit.php', $linkcopyparms);
    $actions->add(vpl_get_action_link('copy', $link));
    $photo = $showphoto ? $vpl->user_picture($user) : '';
    $row = [$usernumberlink, $photo, $vpl->fullname($user)];
    if ($usevariations) {
        $row[] = $data->variation;
    }
    if ($showgrades) {
        $row = array_merge($row, [$grade, $gradecomments]);
    } else {
        $row = array_merge($row, [$subtime, $prev]);
        if ($gradeable) {
            $row = array_merge($row, [$grade, $grader, $gradedon]);
        }
    }
    if (! $downloading) {
        $row[] = $OUTPUT->render($actions);
    }
    $tabledata[] = $row;
}

if (! $downloading) {
    $nstudents = count($allstudents);
    $vpl->print_submissions_status($nstudents, $nsubmissions, $ngraded);
    echo '<div class="d-flex flex-row flex-wrap justify-content-between">';
    // Print groups menu.
    if ($groupmode && ! $vpl->is_group_activity()) {
        groups_print_activity_menu($cm, $groupsurl);
    }
    // Print user selection by submission state.
    $urlbase = $CFG->wwwroot . "/mod/vpl/views/submissionslist.php?id={$id}&sort={$sort}&group={$groupid}&selection=";
    $urlindex = vpl_select_index($urlbase, [
            'all',
            'allsubmissions',
            'notgraded',
            'graded',
            'gradedbyuser',
    ]);
    $urls = array_merge([
            $urlbase . 'all' => get_string('all'),
    ], vpl_select_array($urlbase, [
            'allsubmissions',
            'notgraded',
            'graded',
            'gradedbyuser',
    ]));
    $urlsel = new url_select($urls, $urlindex[$subselection]);
    $urlsel->set_label(get_string('submissionselection', VPL));
    echo $OUTPUT->render($urlsel);
    $urlbase = $CFG->wwwroot . "/mod/vpl/views/submissionslist.php?id=$id&sort=$sort"
            . "&sortdir=$sortdir&selection=$subselection&evaluate=";
    $urls = [
            0 => null,
            2 => $urlbase . '2',
            3 => $urlbase . '3',
            4 => $urlbase . '4',
    ];
    // Print evaluation selection.
    $urlsel = new url_select([
            $urls[2] => get_string('notexecuted', VPL),
            $urls[3] => get_string('notgraded', VPL),
            $urls[4] => get_string('all'),
    ], $urls[$evaluate]);
    $urlsel->set_label(get_string('evaluate', VPL));
    echo $OUTPUT->render($urlsel);
    echo '<br>';
    echo '</div>';
}
$table = new flexible_table("vpl-submissionslist-{$id}");
if ($noevaluating) {
    $table->is_downloading($download, $vpl->get_name() . '_submissions', $vpl->get_name());
} else {
    vpl_prepare_evaluation($id, $evaluateusers);
}
$table->define_baseurl(new moodle_url('/mod/vpl/views/submissionslist.php', $params));
$table->define_columns($fields);
$table->define_headers($headers);
$table->set_hidden_columns($thiddenfields);
$table->set_attribute('class', 'generaltable generalbox reporttable');
if ($downloading) {
    $table->setup();
    foreach ($tabledata as $row) {
        $table->add_data($row);
    }
    $table->finish_output();
    die();
}
$table->set_control_variables([TABLE_VAR_SORT => 'sort', TABLE_VAR_DIR => 'sortdir']);
$table->sortable($noevaluating, $sort, $sortdir);
foreach (['#', 'userpic', 'actions'] as $field) {
    $table->no_sorting($field);
}
$ntabledata = count($tabledata);
if ($noevaluating) {
    $table->collapsible(true);
    $table->initialbars(count($allstudents) > 10);
    $table->show_download_buttons_at([TABLE_P_BOTTOM]);
    $table->pageable($table->get_page_size() < $ntabledata);
    $table->set_default_per_page(30);
    $table->pagesize($tperpage, $ntabledata);
    $pagesize = $table->get_page_size();
    $pagestart = $page * $pagesize;
} else {
    $pagestart = 0;
    $pagesize = $ntabledata;
}
$table->setup();
$pageend = min($pagestart + $pagesize, $ntabledata);
for ($i = $pagestart; $i < $pageend; $i++) {
    $table->add_data($tabledata[$i]);
}
$table->finish_output();
if ($noevaluating) {
    vpl_show_perpage_button($table, $ntabledata, $params);
    vpl_show_graders_table($id, $usernumber, $gradersdata);
    // For manual evaluation.
    // Generate next info as <div id="submissionid">nextuser</div>.
    if (count($nextids)) {
        // Hide info.
        echo '<div style="display:none;">';
        foreach ($nextids as $subid => $nextuser) {
            echo '<div id="n' . $subid . '">' . $nextuser . '</div>';
        }
        echo '</div>';
    }
}

$vpl->print_footer();
