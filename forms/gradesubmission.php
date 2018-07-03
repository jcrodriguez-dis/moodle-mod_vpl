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
 * Grade submission
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/grade_form.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once(dirname(__FILE__).'/../vpl_submission.class.php');
require_once(dirname(__FILE__).'/../views/sh_factory.class.php');

function vpl_grade_header($vpl, $inpopup) {
    if ($inpopup) {
        $vpl->print_header_simple();
    } else {
        $vpl->print_header( get_string( 'grade' ) );
        $vpl->print_view_tabs( basename( __FILE__ ) );
    }
}
require_login();
$PAGE->requires->css( new moodle_url( '/mod/vpl/css/grade.css' ) );
vpl_include_jsfile( 'grade.js', false );
vpl_include_jsfile( 'hide_footer.js', false );
vpl_include_jsfile( 'updatesublist.js', false );
vpl_sh_factory::include_js();

$id = required_param( 'id', PARAM_INT );
$userid = required_param( 'userid', PARAM_INT );
$vpl = new mod_vpl( $id );
$vpl->prepare_page( 'forms/gradesubmission.php', array (
        'id' => $id,
        'userid' => $userid
) );

$jscript = '';
$inpopup = optional_param( 'inpopup', 0, PARAM_INT );
$vpl->require_capability( VPL_GRADE_CAPABILITY );
// Read records.
$submissionid = optional_param( 'submissionid', false, PARAM_INT );
if ($submissionid) {
    $subinstance = $DB->get_record( 'vpl_submissions', array (
            'id' => $submissionid
    ) );
} else {
    $subinstance = $vpl->last_user_submission( $userid );
}
// Check consistence.
$link = vpl_mod_href( 'view.php', 'id', $id, 'userid', $userid );
if (! $subinstance) {
    vpl_grade_header( $vpl, $inpopup );
    vpl_redirect( $link, get_string( 'nosubmission', VPL ), 'error');
}
$submissionid = $subinstance->id;

if ($vpl->is_inconsistent_user( $subinstance->userid, $userid )) {
    vpl_grade_header( $vpl, $inpopup );
    vpl_redirect( $link, 'vpl submission user inconsistence', 'error' );
}
if ($vpl->get_instance()->id != $subinstance->vpl) {
    vpl_grade_header( $vpl, $inpopup );
    vpl_redirect( $link, 'vpl submission vpl inconsistence', 'error' );
}
$submission = new mod_vpl_submission( $vpl, $subinstance );
if ($inpopup) {
    $link = vpl_mod_href( 'forms/gradesubmission.php', 'id', $id, 'userid', $userid, 'inpopup', $inpopup );
} else {
    $link = vpl_mod_href( 'forms/gradesubmission.php', 'id', $id, 'userid', $userid );
}
$linkrel = vpl_rel_url( 'forms/gradesubmission.php', 'id', $id, 'userid', $userid );
// No marked or marked by current user or automatic.
if ($subinstance->dategraded == 0 || $subinstance->grader == $USER->id || $subinstance->grader == 0) {
    if ($inpopup) {
        $href = $link;
    } else {
        $href = 'gradesubmission.php';
    }
    $gradeform = new mod_vpl_grade_form( $href, $submission);
    if ($gradeform->is_cancelled()) { // Grading canceled.
        vpl_inmediate_redirect( $link );
    } else if ($fromform = $gradeform->get_data()) { // Grade (new or update).
        if (isset( $fromform->evaluate )) {
            $url = vpl_mod_href( 'forms/evaluation.php', 'id', $fromform->id, 'userid'
                                 , $fromform->userid, 'grading', 1, 'inpopup', $inpopup );
            vpl_inmediate_redirect( $url );
        }
        if (isset( $fromform->removegrade )) {
            vpl_grade_header( $vpl, $inpopup );
            if ($submission->remove_grade()) {
                \mod_vpl\event\submission_grade_deleted::log( $submission );
                if ($inpopup) {
                    // FIXME don't work.
                    // Change grade info at parent window.
                    $jscript .= 'VPL.updatesublist(' . $submission->get_instance()->id . ',';
                    $jscript .= "' ',' ',' ');";
                    echo vpl_include_js( $jscript );
                }
                vpl_redirect( $link, get_string( 'graderemoved', VPL ), 5 );
            } else {
                vpl_redirect( $link, get_string( 'gradenotremoved', VPL ), 5 );
            }
            die();
        }
        vpl_grade_header( $vpl, $inpopup );
        if (! isset( $fromform->grade ) && ! isset( $fromform->savenext )) {
            vpl_redirect( $link, get_string( 'badinput' ), 'error' );
        }

        if ($submission->is_graded()) {
            $action = 'update grade';
        } else {
            $action = 'grade';
        }
        if (! $submission->set_grade( $fromform )) {
            vpl_redirect( $link, get_string( 'gradenotsaved', VPL ), 'error' );
        }
        if ($action == 'grade') {
            \mod_vpl\event\submission_graded::log( $submission );
        } else {
            \mod_vpl\event\submission_grade_updated::log( $submission );
        }

        if ($inpopup) {
            // Change grade info at parent window.
            $text = $submission->get_grade_core();
            $grader = fullname( $submission->get_grader( $USER->id ) );
            $gradedon = userdate( $submission->get_instance()->dategraded );
            $jscript .= 'VPL.updatesublist(' . $submission->get_instance()->id . ',';
            $jscript .= '\'' . addslashes( $text ) . '\',';
            $jscript .= '\'' . addslashes( $grader ) . '\',';
            $jscript .= '\'' . addslashes( $gradedon ) . "');\n";
            if (isset( $fromform->savenext )) {
                $url = $CFG->wwwroot . '/mod/vpl/forms/gradesubmission.php?id=' . $id . '&inpopup=1&userid=';
                $jscript .= 'VPL.go_next(\'' . $submission->get_instance()->id . '\',\'' . addslashes( $url ) . '\');';
            } else {
                $jscript .= 'window.close();';
            }
        } else {
            vpl_redirect( $link, get_string( 'graded', VPL ), 'success' );
        }
        $vpl->print_footer();
        echo vpl_include_js( $jscript );
        die();
    } else {
        // Show grade form.
        vpl_grade_header( $vpl, $inpopup );

        $data = new stdClass();
        $data->id = $vpl->get_course_module()->id;
        $data->userid = $subinstance->userid;
        $data->submissionid = $submissionid;
        if ($submission->is_graded()) {
            $data->grade = format_float($subinstance->grade, 5, true, true);
            $data->comments = $submission->get_grade_comments();
        } else {
            $res = $submission->getCE();
            if ($res ['executed']) {
                $graderaw = $submission->proposedGrade($res['execution']);
                if ( $graderaw > '' ) {
                    $data->grade = format_float(floatval($graderaw), 5, true, true);
                } else {
                    $data->grade = '';
                }
                $data->comments = $submission->proposedComment( $res ['execution'] );
            }
        }
        if (! empty( $CFG->enableoutcomes )) {
            $gradinginfo = grade_get_grades( $vpl->get_course()->id, 'mod', 'vpl'
                                            , $vpl->get_instance()->id, $userid );
            if (! empty( $gradinginfo->outcomes )) {
                foreach ($gradinginfo->outcomes as $oid => $outcome) {
                    $field = 'outcome_grade_' . $oid;
                    $data->$field = $outcome->grades [$userid]->grade;
                }
            }
        }

        $gradeform->set_data( $data );
        echo '<div id="vpl_grade_view">';
        echo '<div id="vpl_grade_form">';
        $gradeform->display();
        echo '</div>';
        echo '<div id="vpl_grade_comments">';
        $comments = $vpl->get_grading_help();
        if ($comments > '') {
            echo $OUTPUT->box_start();
            echo '<b>' . get_string( 'listofcomments', VPL ) . '</b><hr />';
            echo $comments;
            echo $OUTPUT->box_end();
        }
        echo '</div>';
        echo '</div>';
        echo '<div id="vpl_submission_view">';
        echo '<hr />';
        $vpl->print_variation( $subinstance->userid );
        $submission->print_submission();
        echo '</div>';
        $jscript .= 'VPL.hlrow(' . $submissionid . ');';
        $jscript .= 'window.onunload= function(){VPL.unhlrow(' . $submissionid . ');};';
        if ($inpopup) {
            $jscript .= 'VPL.removeHeaderFooter();';
        }
    }
} else {
    vpl_inmediate_redirect( vpl_mod_href( 'forms/submissionview.php', 'id', $id, 'userid', $userid ) );
}
$vpl->print_footer_simple();
vpl_sh_factory::syntaxhighlight();
echo vpl_include_js( $jscript );
