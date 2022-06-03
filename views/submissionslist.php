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

require_once(dirname( __FILE__ ) . '/../../../config.php');
global $CFG, $USER, $OUTPUT;
require_once($CFG->dirroot.'/mod/vpl/locallib.php');
require_once($CFG->dirroot.'/mod/vpl/vpl.class.php');
require_once($CFG->dirroot.'/mod/vpl/vpl_submission_CE.class.php');
require_once($CFG->dirroot.'/mod/vpl/views/sh_factory.class.php');

class vpl_submissionlist_order {
    protected static $field; // Field to compare.
    protected static $ascending; // Value to return when ascending or descending order.
    protected static $corder = null; // Funtion usort of old PHP versions don't call static class functions.
    // Compare two submission fields.
    public static function cpm_userid($a, $b) {
        if ($a->userinfo->id < $b->userinfo->id) {
            return self::$ascending;
        } else {
            return - self::$ascending;
        }
    }
    // Compare two userinfo fields.
    public static function cpm_userinfo($a, $b) {
        $field = self::$field;
        $adata = $a->userinfo->$field;
        $bdata = $b->userinfo->$field;
        if ($adata == $bdata) {
            return self::cpm_userid( $a, $b );
        }
        if (is_string( $adata ) && function_exists( 'core_collator::compare' )) {
            return (core_collator::compare( $adata, $bdata )) * (self::$ascending);
        }
        if ($adata < $bdata) {
            return self::$ascending;
        } else {
            return - self::$ascending;
        }
    }
    // Compare two submission fields.
    public static function cpm_submission($a, $b) {
        $field = self::$field;
        $submissiona = $a->submission;
        $submissionb = $b->submission;
        if ($submissiona == $submissionb) {
            return self::cpm_userid( $a, $b );
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
            return self::cpm_userid( $a, $b );
        } else if ($adata < $bdata) {
            return self::$ascending;
        } else {
            return - self::$ascending;
        }
    }

    /**
     * Check and set data to sort return comparation function $field field to compare $descending order
     *
     * @return array with function name
     */
    public static function set_order($field, $ascending = true) {
        if (self::$corder === null) {
            self::$corder = new vpl_submissionlist_order();
        }
        $userinfofields = array (
                'firstname' => 0,
                'lastname' => 0
        );
        $submissionfields = array (
                'datesubmitted' => 0,
                'gradesortable' => 0,
                'grader' => 0,
                'dategraded' => 0,
                'nsubmissions' => 0
        );
        self::$field = $field;
        if ($ascending) {
            self::$ascending = - 1;
        } else {
            self::$ascending = 1;
        }
        // Funtion usort of old PHP versions don't call static class functions.
        if (isset( $userinfofields[$field] )) {
            return array (
                    self::$corder,
                    'cpm_userinfo'
            );
        } else if (isset( $submissionfields[$field] )) {
            return array (
                    self::$corder,
                    'cpm_submission'
            );
        } else {
            self::$field = 'firstname';
            return array (
                    self::$corder,
                    'cpm_userinfo'
            );
        }
    }
}
function vpl_evaluate($vpl, $alldata, $userinfo, $nevaluation, $groupsurl) {
    global $OUTPUT;
    $nevaluation ++;
    try {
        echo '<h2>' . s( get_string( 'evaluating', VPL ) ) . '</h2>';
        $text = $nevaluation . '/' . count( $alldata );
        $text .= ' ' . $vpl->user_picture( $userinfo );
        $text .= ' ' . fullname( $userinfo );
        $text .= ' <a href="' . $groupsurl . '">' . get_string( 'cancel' ) . '</a>';
        echo $OUTPUT->box( $text );
        $id = $vpl->get_course_module()->id;
        $ajaxurl = "../forms/edit.json.php?id={$id}&userid={$userinfo->id}&action=";
        $url = vpl_url_add_param( $groupsurl, 'evaluate', optional_param( 'evaluate', 0, PARAM_INT ) );
        $url = vpl_url_add_param( $url, 'nevaluation', $nevaluation );
        $nexturl = str_replace( '&amp;', '&', urldecode( $url ) );
        vpl_editor_util::print_js_i18n();
        vpl_editor_util::generate_evaluate_script( $ajaxurl, $nexturl );
    } catch ( Exception $e ) {
        vpl_notice( $e->getMessage(), 'error' );
    }
    $vpl->print_footer();
    die();
}
function vpl_submissionlist_arrow($burl, $sort, $selsort, $seldir) {
    global $OUTPUT;
    $newdir = 'down';
    $url = vpl_url_add_param( $burl, 'sort', $sort );
    if ($sort == $selsort) {
        $sortdir = $seldir;
        if ($sortdir == 'up') {
            $newdir = 'down';
        } else if ($sortdir == 'down') {
            $newdir = 'up';
        }
    } else {
        $sortdir = 'move';
    }
    $url = vpl_url_add_param( $url, 'sortdir', $newdir );
    $showgrades = optional_param( 'showgrades', 0, PARAM_INT );
    if ( $showgrades > 0 ) {
        $url = vpl_url_add_param( $url, 'showgrades', 1 );
    }
    return ' <a href="' . $url . '">' . ($OUTPUT->pix_icon( 't/' . $sortdir, get_string( $sortdir ) )) . '</a>';
}
function vpl_get_listmenu($showgrades, $id) {
    $menu = new action_menu();
    $url = new moodle_url( '/mod/vpl/views/activityworkinggraph.php', array (
            'id' => $id) );
    $menu->add(vpl_get_action_link('submissions', $url));
    if ($showgrades) {
        $url = new moodle_url( '/mod/vpl/views/submissionslist.php', array (
                'id' => $id) );
        $menu->add(vpl_get_action_link('submissionslist', $url));
    } else {
        $url = new moodle_url( '/mod/vpl/views/submissionslist.php', array (
                'id' => $id, 'showgrades' => 1) );
        $menu->add(vpl_get_action_link('gradercomments', $url));
    }

    $url = new moodle_url( '/mod/vpl/views/downloadallsubmissions.php', array (
            'id' => $id) );
    $menu->add(vpl_get_action_link('downloadsubmissions', $url));
    $url = new moodle_url( '/mod/vpl/views/downloadallsubmissions.php', array (
            'id' => $id,
            'all' => 1) );
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
    return new action_menu_link_secondary($link, new pix_icon($str, '', 'mod_vpl'),  $stri18n);
}

require_login();

$id = required_param( 'id', PARAM_INT );
$group = optional_param( 'group', - 1, PARAM_INT );
$evaluate = optional_param( 'evaluate', 0, PARAM_INT );
$nevaluation = optional_param( 'nevaluation', 0, PARAM_INT );
$showgrades = optional_param( 'showgrades', 0, PARAM_INT );
$sort = vpl_get_set_session_var( 'subsort', 'lastname', 'sort' );
$sortdir = vpl_get_set_session_var( 'subsortdir', 'move', 'sortdir' );
$subselection = vpl_get_set_session_var( 'subselection', 'allsubmissions', 'selection' );
if ($evaluate > 0) {
    require_once($CFG->dirroot.'/mod/vpl/editor/editor_utility.php');
    vpl_editor_util::generate_requires_evaluation();
}

$vpl = new mod_vpl( $id );
$vpl->prepare_page( 'views/submissionslist.php', array (
        'id' => $id
) );

$cm = $vpl->get_course_module();
$vpl->require_capability( VPL_GRADE_CAPABILITY );
\mod_vpl\event\vpl_all_submissions_viewed::log( $vpl );

$PAGE->requires->css( new moodle_url( '/mod/vpl/css/sh.css' ) );

// Print header.
$vpl->print_header( get_string( 'submissionslist', VPL ) );
$vpl->print_view_tabs( basename( __FILE__ ) );
@ob_flush();
flush();

// Find out current groups mode.
$groupmode = groups_get_activity_groupmode( $cm );
if (! $groupmode) {
    $groupmode = groups_get_course_groupmode( $vpl->get_course() );
}

// Get graders.
$gradeable = $vpl->get_grade() != 0;

// Get students.
$currentgroup = groups_get_activity_group( $cm, true );
if (! $currentgroup) {
    $currentgroup = '';
}
if ($vpl->is_group_activity()) {
    $list = groups_get_all_groups($vpl->get_course()->id, 0, $cm->groupingid);
} else {
    $list = $vpl->get_students( $currentgroup );
}
$submissions = $vpl->all_last_user_submission();
$submissionsnumber = $vpl->get_submissions_number();

// Get all information.
$alldata = array ();
foreach ($list as $uginfo) {
    $submission = null;
    if (! isset( $submissions[$uginfo->id] )) {
        if ($subselection != 'all') {
            continue;
        }
    } else {
        $subinstance = $submissions[$uginfo->id];
        $submission = new mod_vpl_submission_CE( $vpl, $subinstance );
        $subid = $subinstance->id;
        $subinstance->gradesortable = null;
        if ($subinstance->dategraded > 0) {
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
                $prograde = $submission->proposedGrade( $result['execution'] );
                if ($prograde > '') {
                    $subinstance->gradesortable = $prograde;
                }
            }
        }
        // I know that subinstance isn't the correct place to put nsubmissions but is the easy.
        if (isset( $submissionsnumber[$uginfo->id] )) {
            $subinstance->nsubmissions = $submissionsnumber[$uginfo->id]->submissions;
        } else {
            $subinstance->nsubmissions = ' ';
        }
    }
    $data = new stdClass();
    $data->userinfo = $uginfo;
    $data->submission = $submission;
    // When group activity => add lastname to groupname for order porpouse.
    if ($vpl->is_group_activity()) {
        $data->userinfo->firstname = '';
        $data->userinfo->lastname = $uginfo->name;
    }
    $alldata[] = $data;
}
$groupsurl = vpl_mod_href( 'views/submissionslist.php', 'id', $id, 'sort', $sort, 'sortdir', $sortdir, 'selection', $subselection );
// Unblock user session.
session_write_close();

$baseurl = vpl_mod_href( 'views/submissionslist.php', 'id', $id, 'group', $group );

$firstname = get_string( 'firstname' ) . vpl_submissionlist_arrow( $baseurl, 'firstname', $sort, $sortdir );
$lastname = get_string( 'lastname' ) . vpl_submissionlist_arrow( $baseurl, 'lastname', $sort, $sortdir );
if ($CFG->fullnamedisplay == 'lastname firstname') { // For better view (dlnsk).
    $namesortselect = $lastname . ' / ' . $firstname;
} else {
    $namesortselect = $firstname . ' / ' . $lastname;
}
if ($vpl->is_group_activity()) {
    $namesortselect = get_string( 'group' ) . vpl_submissionlist_arrow( $baseurl, 'lastname', $sort, $sortdir );
}
$options = array (
        'height' => 550,
        'width' => 780,
        'directories' => 0,
        'location' => 0,
        'menubar' => 0,
        'personalbar' => 0,
        'status' => 0,
        'toolbar' => 0
);
// Load strings.
$strsubtime = get_string( 'submittedon', VPL ) . vpl_submissionlist_arrow( $baseurl, 'datesubmitted', $sort, $sortdir );
$strgrade = get_string( 'grade', 'core_grades' ) . vpl_submissionlist_arrow( $baseurl, 'gradesortable', $sort, $sortdir );
$strgrader = get_string( 'grader', VPL ) . vpl_submissionlist_arrow( $baseurl, 'grader', $sort, $sortdir );
$strgradedon = get_string( 'gradedon', VPL ) . vpl_submissionlist_arrow( $baseurl, 'dategraded', $sort, $sortdir );
$strcomments = get_string( 'gradercomments', VPL );
$hrefnsub = vpl_mod_href( 'views/activityworkinggraph.php', 'id', $id );
$action = new popup_action( 'click', $hrefnsub, 'activityworkinggraph' . $id, $options );
$linkworkinggraph = $OUTPUT->action_link( $hrefnsub, get_string( 'submissions', VPL ), $action );
$strsubmisions = $linkworkinggraph . vpl_submissionlist_arrow( $baseurl, 'nsubmissions', $sort, $sortdir );

$table = new html_table();
if ($showgrades) {
    $table->head = array (
            '',
            '',
            $namesortselect,
            $strgrade,
            $strcomments,
            $OUTPUT->render(vpl_get_listmenu($showgrades, $id))
    );
    $table->aling = array (
            'right',
            'left',
            'left',
            'right',
            'left'
    );
    $table->size = array (
            '3em'
    );
} else if ($gradeable) {
    $table->head = array (
            '',
            '',
            $namesortselect,
            $strsubtime,
            $strsubmisions,
            $strgrade,
            $strgrader,
            $strgradedon,
            $OUTPUT->render(vpl_get_listmenu($showgrades, $id))
    );
    $table->size = array (
            '3em',
            '',
            '',
            '',
            '3em'
    );
    $table->aling = array (
            'right',
            'left',
            'left',
            'right',
            'right',
            'right',
            'right',
            'left'
    );
} else {
    $table->head = array (
            '',
            '',
            $namesortselect,
            $strsubtime,
            $strsubmisions,
            $OUTPUT->render(vpl_get_listmenu($showgrades, $id))
    );
    $table->size = array (
            '3em',
            '',
            '',
            '',
            '3em'
    );
    $table->aling = array (
            'right',
            'left',
            'left',
            'right',
            'right'
    );
}

// Sort by sort field.
usort( $alldata, vpl_submissionlist_order::set_order( $sort, $sortdir != 'up' ) );
$showphoto = count( $alldata ) < 100;

$usernumber = 0;
$ngrades = array (); // Number of revisions made by teacher.
$nextids = array (); // Information to get next user in list.
$lastid = 0; // Last id for next.
foreach ($alldata as $data) {
    $actions = new action_menu();
    if ($vpl->is_group_activity()) {
        $gr = $data->userinfo;
        $users = $vpl->get_group_members($gr->id);
        if ( count($users) == 0 ) {
            continue;
        }
        $user = reset( $users );
        $user->firstname = '';
        $user->lastname = $gr->name;
    } else {
        $user = $data->userinfo;
    }
    $gradecomments = '';
    $linkparms = array('id' => $id, 'userid' => $user->id);
    if ($data->submission == null) {
        $text = get_string( 'nosubmission', VPL );
        $hrefview = vpl_mod_href( 'forms/submissionview.php', 'id', $id, 'userid', $user->id, 'inpopup', 1 );
        $action = new popup_action( 'click', $hrefview, 'viewsub' . $user->id, $options );
        $subtime = $OUTPUT->action_link( $hrefview, $text, $action );
        $link = new moodle_url('/mod/vpl/forms/submissionview.php', $linkparms);
        $actions->add(vpl_get_action_link('submissionview', $link));
        $prev = '';
        $grade = '';
        $grader = '';
        $gradedon = '';
    } else {
        $submission = $data->submission;
        $subinstance = $submission->get_instance();
        $hrefview = vpl_mod_href( 'forms/submissionview.php', 'id', $id, 'userid', $user->id, 'inpopup', 1 );
        $hrefprev = vpl_mod_href( 'views/previoussubmissionslist.php', 'id', $id, 'userid', $user->id, 'inpopup', 1 );
        $hrefgrade = vpl_mod_href( 'forms/gradesubmission.php', 'id', $id, 'userid', $user->id, 'inpopup', 1 );
        $link = new moodle_url('/mod/vpl/forms/submissionview.php', $linkparms);
        $actions->add(vpl_get_action_link('submissionview', $link));
        $subtime = $OUTPUT->action_link( $hrefview, userdate( $subinstance->datesubmitted ) );
        if ($subinstance->nsubmissions > 0) {
            $prev = $OUTPUT->action_link( $hrefprev, $subinstance->nsubmissions );
            $link = new moodle_url('/mod/vpl/views/previoussubmissionslist.php', $linkparms);
            $actions->add(vpl_get_action_link('previoussubmissionslist', $link));
        } else {
            $prev = '';
        }
        $subid = $subinstance->id;
        if ($evaluate == 4 && $nevaluation <= $usernumber) { // Need evaluation.
            vpl_evaluate( $vpl, $alldata, $user, $usernumber, $groupsurl );
        }
        if ($subinstance->dategraded > 0) {
            $text = $submission->get_grade_core();
            // Add proposed grade diff.
            $result = $submission->getCE();
            if ($result['executed'] !== 0) {
                $prograde = $submission->proposedGrade( $result['execution'] );
                if ($prograde > '' && $prograde != $subinstance->grade) {
                    $text .= ' (' . $prograde . ')';
                }
            }
            $text = '<div id="g' . $subid . '">' . $text . '</div>';
            if ($subinstance->grader == $USER->id) {
                $action = new popup_action( 'click', $hrefgrade, 'gradesub' . $user->id, $options );
                $grade = $OUTPUT->action_link( $hrefgrade, $text, $action );
                $link = new moodle_url('/mod/vpl/forms/gradesubmission.php', $linkparms);
                $actions->add( vpl_get_action_link('grade', $link, 'core_grades') );
                // Add new next user.
                if ($lastid) {
                    $nextids[$lastid] = $user->id;
                }
                $lastid = $subid; // Save submission id as next index.
            } else {
                $grade = $text;
            }

            $graderid = $subinstance->grader;
            $graderuser = $submission->get_grader( $graderid );
            // Count evaluator marks.
            if (isset( $ngrades[$graderid] )) {
                $ngrades[$graderid] ++;
            } else {
                $ngrades[$graderid] = 1;
            }
            $grader = fullname( $graderuser );
            $gradedon = userdate( $subinstance->dategraded );
            if ($showgrades) {
                $gradecomments .= $submission->get_detailed_grade();
                $gradecomments .= $submission->print_ce(true);
            }
        } else {
            $result = $submission->getCE();
            $text = '';
            if (($evaluate == 1 && $result['compilation'] === 0)
                || ($evaluate == 2 && $result['executed'] === 0 && $nevaluation <= $usernumber)
                || ($evaluate == 3 && $nevaluation <= $usernumber)) { // Need evaluation.
                    vpl_evaluate( $vpl, $alldata, $user, $usernumber, $groupsurl );
            }
            if ($result['executed'] !== 0) {
                $prograde = $submission->proposedGrade( $result['execution'] );
                if ($prograde > '') {
                    $text = get_string( 'proposedgrade', VPL, $submission->get_grade_core( $prograde ) );
                }
            }
            if ($text == '') {
                $text = get_string( 'nograde' );
            }
            $action = new popup_action( 'click', $hrefgrade, 'gradesub' . $subinstance->userid, $options );
            $text = '<div id="g' . $subid . '">' . $text . '</div>';
            $grade = $OUTPUT->action_link( $hrefgrade, $text, $action );
            $grader = '&nbsp;';
            $gradedon = '&nbsp;';
            $link = new moodle_url('/mod/vpl/forms/gradesubmission.php', $linkparms);
            $actions->add(vpl_get_action_link('grade', $link, 'core_grades'));
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
        $grader = '<div id="m' . $subid . '">' . $grader . '</div>';
        $gradedon = '<div id="o' . $subid . '">' . $gradedon . '</div>';
    }
    $url = vpl_mod_href( 'forms/edit.php', 'id', $id, 'userid', $user->id, 'privatecopy', 1 );
    $options = array (
            'height' => 550,
            'width' => 780,
            'directories' => 0,
            'location' => 0,
            'menubar' => 0,
            'personalbar' => 0,
            'status' => 0,
            'toolbar' => 0
    );
    $action = new popup_action( 'click', $url, 'privatecopyl' . $id, $options );
    $usernumber ++;
    $usernumberlink = $OUTPUT->action_link( $url, $usernumber, $action);
    $link = new moodle_url('/mod/vpl/forms/edit.php', array('id' => $id, 'userid' => $user->id, 'privatecopy' => 1));
    $actions->add(vpl_get_action_link('copy', $link));
    if ($showgrades) {
        $table->data[] = array (
                $usernumberlink,
                $showphoto ? $vpl->user_picture( $user ) : '',
                $vpl->fullname( $user, !$showphoto),
                $grade,
                $gradecomments,
                $OUTPUT->render($actions)
        );
    } else if ($gradeable) {
        $table->data[] = array (
                $usernumberlink,
                $showphoto ? $vpl->user_picture( $user) : '',
                $vpl->fullname( $user, !$showphoto),
                $subtime,
                $prev,
                $grade,
                $grader,
                $gradedon,
                $OUTPUT->render($actions)
        );
    } else {
        $table->data[] = array (
                $usernumberlink,
                $showphoto ? $vpl->user_picture( $user) : '',
                $vpl->fullname( $user, !$showphoto),
                $subtime,
                $prev,
                $OUTPUT->render($actions)
        );
    }
}
if (count( $ngrades )) {
    if ($CFG->fullnamedisplay == 'lastname firstname') { // For better view (dlnsk).
        $namehead = get_string( 'lastname' ) . ' / ' . get_string( 'firstname' );
    } else {
        $namehead = get_string( 'firstname' ) . ' / ' . get_string( 'lastname' );
    }
    $tablegraders = new html_table();
    $tablegraders->head = array (
            '#',
            $namehead,
            get_string( 'grade', 'core_grades' )
    );
    $tablegraders->align = array (
            'right',
            'left',
            'center'
    );
    $tablegraders->wrap = array (
            'nowrap',
            'nowrap',
            'nowrap'
    );
    $tablegraders->data = array ();
    $gradernumber = 0;
    foreach ($ngrades as $graderid => $marks) {
        $gradernumber ++;
        $grader = mod_vpl_submission::get_grader( $graderid );
        $picture = '';
        if ($graderid > 0) { // No automatic grading.
            $picture = $OUTPUT->user_picture( $grader, array (
                    'popup' => true
            ) );
        }
        $tablegraders->data[] = array (
                $gradernumber,
                $picture . ' ' . fullname( $grader ),
                sprintf( '%d/%d  (%5.2f%%)', $marks, $usernumber, ( float ) 100.0 * $marks / $usernumber )
        );
    }
}
// Menu for groups.
if ($groupmode) {
    groups_print_activity_menu( $cm, $groupsurl );
}
// Print user selection by submission state.
$urlbase = $CFG->wwwroot . "/mod/vpl/views/submissionslist.php?id=$id&sort=$sort&group=$group&selection=";
$urlindex = vpl_select_index( $urlbase, array (
        'all',
        'allsubmissions',
        'notgraded',
        'graded',
        'gradedbyuser'
) );
$urls = array_merge( array (
        $urlbase . 'all' => get_string( 'all' )
), vpl_select_array( $urlbase, array (
        'allsubmissions',
        'notgraded',
        'graded',
        'gradedbyuser'
) ) );
$urlsel = new url_select( $urls, $urlindex[$subselection] );
$urlsel->set_label( get_string( 'submissionselection', VPL ) );
echo $OUTPUT->render( $urlsel );
if ($subselection != 'notgraded') {
    $urlbase = $CFG->wwwroot . "/mod/vpl/views/submissionslist.php?id=$id&sort=$sort"
               ."&sortdir=$sortdir&selection=$subselection&evaluate=";
    $urls = array (
            0 => null,
            2 => $urlbase . '2',
            '3' => $urlbase . '3',
            4 => $urlbase . '4'
    );
    $urlsel = new url_select( array (
            $urls[2] => get_string( 'notexecuted', VPL ),
            $urls[3] => get_string( 'notgraded', VPL ),
            $urls[4] => get_string( 'all' )
    ), $urls[$evaluate] );
    $urlsel->set_label( get_string( 'evaluate', VPL ) );
    echo $OUTPUT->render( $urlsel );
}
echo '<br>';
@ob_flush();
flush();
echo html_writer::table( $table );
if (count( $ngrades ) > 0) {
    echo '<br>';
    echo html_writer::table( $tablegraders );
}

// Generate next info as <div id="submissionid">nextuser</div>.
if (count( $nextids )) {
    // Hide info.
    echo '<div style="display:none;">';
    foreach ($nextids as $subid => $nextuser) {
        echo '<div id="n' . $subid . '">' . $nextuser . '</div>';
    }
    echo '</div>';
}
$vpl->print_footer();
