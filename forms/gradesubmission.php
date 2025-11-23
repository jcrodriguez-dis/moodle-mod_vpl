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
 * Grade a submission
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/grade_form.php');
require_once(dirname(__FILE__) . '/../vpl.class.php');
require_once(dirname(__FILE__) . '/../vpl_submission.class.php');

require_login();
global $CFG, $PAGE, $DB, $USER, $OUTPUT;
$PAGE->requires->css(new moodle_url('/mod/vpl/css/grade.css'));
$PAGE->requires->css(new moodle_url('/mod/vpl/css/sh.css'));
$PAGE->requires->strings_for_js(['loading', 'error'], 'moodle');

vpl_include_jsfile('hide_footer.js', false);

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$vpl = new mod_vpl($id);

$vpl->prepare_page('forms/gradesubmission.php', [
    'id' => $id,
    'userid' => $userid,
]);

// Go to submission view if activity is not gradable.
if ($vpl->get_grade() == 0) {
    $link = vpl_mod_href('forms/submissionview.php', 'id', $id, 'userid', $userid);
    vpl_inmediate_redirect($link);
}

$inpopup = optional_param('inpopup', 0, PARAM_INT);
$vpl->require_capability(VPL_GRADE_CAPABILITY);

if ($inpopup) {
    $vpl->print_header_simple();
} else {
    $vpl->print_header(get_string(vpl_get_gradenoun_str()));
    $vpl->print_view_tabs(basename(__FILE__));
}

// Read submission to grade.
$submissionid = optional_param('submissionid', false, PARAM_INT);
if ($submissionid) {
    $subinstance = $DB->get_record('vpl_submissions', [
            'id' => $submissionid,
    ]);
} else {
    $subinstance = $vpl->last_user_submission($userid);
}
// Check consistence.
$link = vpl_mod_href('view.php', 'id', $id, 'userid', $userid);
if (! $subinstance) {
    vpl_redirect($link, get_string('nosubmission', VPL), 'error');
}
$submissionid = $subinstance->id;

if ($vpl->is_inconsistent_user($subinstance->userid, $userid)) {
    vpl_redirect($link, 'vpl submission user inconsistence', 'error');
}
if ($vpl->get_instance()->id != $subinstance->vpl) {
    vpl_redirect($link, 'vpl submission vpl inconsistence', 'error');
}
$submission = new mod_vpl_submission($vpl, $subinstance);
if ($inpopup) {
    $link = vpl_mod_href('forms/gradesubmission.php', 'id', $id, 'userid', $userid, 'inpopup', $inpopup);
} else {
    $link = vpl_mod_href('forms/gradesubmission.php', 'id', $id, 'userid', $userid);
}

// No marked or marked by current user or automatic or has edit others grades capability.
if (
    $subinstance->dategraded == 0 ||
    $subinstance->grader == $USER->id ||
    $subinstance->grader == 0 ||
    $vpl->has_capability(VPL_EDITOTHERSGRADES_CAPABILITY)
) {
    if ($inpopup) {
        $href = htmlspecialchars_decode($link);
    } else {
        $href = 'gradesubmission.php';
    }
    $gradeform = new mod_vpl_grade_form($href, $vpl, $submission);
    if ($gradeform->is_cancelled()) { // Grading canceled.
        vpl_inmediate_redirect($link);
    } else if ($fromform = $gradeform->get_data()) { // Grade (new or update).
        if (isset($fromform->removegrade)) {
            if ($submission->remove_grade()) {
                \mod_vpl\event\submission_grade_deleted::log($submission);
                if ($inpopup) {
                    $gradedata = new stdClass();
                    $gradedata->grade = get_string('nograde');
                    $gradedata->grader = '';
                    $gradedata->gradedon = '';
                    $gradedata->comments = '';
                    $PAGE->requires->js_call_amd('mod_vpl/gradeform', 'updateSubmissionsList', [ $submissionid, $gradedata, null ]);
                }
                vpl_redirect($link, get_string('graderemoved', VPL), 5);
            } else {
                vpl_redirect($link, get_string('gradenotremoved', VPL), 5);
            }
            die();
        }
        $badgrade = ! isset($fromform->grade) && ! isset($fromform->savenext);
        if ($vpl->get_instance()->grade < 0) {
            $badgrade = $badgrade || $fromform->grade == -1;
        } else {
            $badgrade = $badgrade || trim($fromform->grade) == '';
            $floatn = unformat_float($fromform->grade);
            if ($floatn === false) {
                $badgrade = true;
            } else {
                $badgrade = $badgrade || $floatn > $vpl->get_instance()->grade;
            }
        }
        if ($badgrade) {
            vpl_redirect($link, get_string('badgrade', 'grades'), 'error');
        }
        if ($submission->is_graded()) {
            $logclass = \mod_vpl\event\submission_grade_updated::class;
        } else {
            $logclass = \mod_vpl\event\submission_graded::class;
        }
        $gradinginstance = $submission->get_grading_instance();
        if ($gradinginstance) {
            $gradinginstance->submit_and_get_grade($fromform->advancedgrading, $submissionid);
        }
        if ($submission->set_grade($fromform)) {
            $logclass::log($submission);
        } else {
            vpl_redirect($link, get_string('gradenotsaved', VPL), 'error');
        }

        if ($inpopup) {
            // Change grade info at parent window.
            $gradedata = new stdClass();
            $gradedata->grade = $submission->get_grade_core();
            $gradedata->grader = fullname($submission->get_grader($USER->id));
            $gradedata->gradedon = userdate($submission->get_instance()->dategraded);
            $gradedata->comments = nl2br($submission->get_detailed_grade() . $submission->print_ce(true));
            if (isset($fromform->savenext)) {
                $nexturl = $CFG->wwwroot . '/mod/vpl/forms/gradesubmission.php?id=' . $id . '&inpopup=1&userid=';
                echo $OUTPUT->notification(get_string('gradesaved_redirect', VPL), 'success');
            } else {
                $nexturl = null;
                echo $OUTPUT->notification(get_string('gradesaved', VPL), 'success');
                echo '<div class="continuebutton">
                        <button class="btn btn-primary" onclick="window.close();">' .
                            get_string('continue') .
                        '</button>
                    </div>';
            }
            $PAGE->requires->js_call_amd('mod_vpl/gradeform', 'updateSubmissionsList', [ $submissionid, $gradedata, $nexturl ]);
            $vpl->print_footer_simple();
        } else {
            vpl_redirect($link, get_string('gradesaved', VPL), 'success');
        }
        die();
    } else {
        // Show grade form.
        \mod_vpl\event\submission_grade_viewed::log($submission);
        $data = new stdClass();
        $data->id = $vpl->get_course_module()->id;
        $data->userid = $subinstance->userid;
        $data->submissionid = $submissionid;
        if ($submission->is_graded()) {
            $data->grade = format_float($subinstance->grade, 5, true, true);
            $data->comments = $submission->get_grade_comments();
        } else {
            $res = $submission->getCE();
            if ($res['executed']) {
                $graderaw = $submission->proposedGrade($res['execution']);
                if ($graderaw > '') {
                    $data->grade = format_float(floatval($graderaw), 5, true, true);
                } else {
                    $data->grade = '';
                }
                $data->comments = $submission->proposedComment($res['execution']);
            }
        }
        if (! empty($CFG->enableoutcomes)) {
            $gradinginfo = grade_get_grades($vpl->get_course()->id, 'mod', 'vpl', $vpl->get_instance()->id, $userid);
            if (! empty($gradinginfo->outcomes)) {
                foreach ($gradinginfo->outcomes as $oid => $outcome) {
                    $field = 'outcome_grade_' . $oid;
                    $data->$field = $outcome->grades[$userid]->grade;
                }
            }
        }
        $gradeform->set_data($data);
        $gradeform->display();
        echo '<div class="m-t-2">';
        $submission->print_submission();
        echo '</div>';
        $PAGE->requires->js_call_amd('mod_vpl/gradeform', 'highlightSubmission', [ $submissionid ]);
    }
} else {
    vpl_inmediate_redirect(vpl_mod_href('forms/submissionview.php', 'id', $id, 'userid', $userid));
}
$vpl->print_footer_simple();
