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
 * View a submission
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(__DIR__ . '/../../../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/grade_form.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once(dirname(__FILE__).'/../vpl_submission.class.php');
require_once(dirname(__FILE__).'/../views/sh_factory.class.php');

global $DB, $USER, $PAGE;

require_login();
$id = required_param( 'id', PARAM_INT );
$userid = optional_param( 'userid', false, PARAM_INT );

$vpl = new mod_vpl( $id );
if ($userid) {
    $vpl->prepare_page( 'forms/submissionview.php', array (
            'id' => $id,
            'userid' => $userid
    ) );
} else {
    $vpl->prepare_page( 'forms/submissionview.php', array (
            'id' => $id
    ) );
}
if (! $vpl->is_visible()) {
    \mod_vpl\event\vpl_security::log( $vpl );
    vpl_redirect( '?id=' . $id, get_string( 'notavailable' ) );
}

$submissionid = optional_param( 'submissionid', false, PARAM_INT );
// Read records.
if ($userid && $userid != $USER->id) {
    // Grader.
    $vpl->require_capability( VPL_GRADE_CAPABILITY );
    if ($submissionid) {
        $subinstance = $DB->get_record( 'vpl_submissions', array (
            'id' => $submissionid,
            'vpl' => $vpl->get_instance()->id
        ) );
    } else {
        $subinstance = $vpl->last_user_submission( $userid );
    }
} else {
    // View own submission.
    $vpl->require_capability( VPL_VIEW_CAPABILITY );
    $userid = $USER->id;
    if ($submissionid && $vpl->has_capability( VPL_GRADE_CAPABILITY )) {
        $subinstance = $DB->get_record( 'vpl_submissions', array (
            'id' => $submissionid,
            'vpl' => $vpl->get_instance()->id,
        ) );
    } else {
        $subinstance = $vpl->last_user_submission( $userid );
    }
}

if ($subinstance != null && $subinstance->vpl != $vpl->get_instance()->id) {
    throw new moodle_exception('invalidcourseid');
}
if ($USER->id == $userid) {
    $vpl->restrictions_check();
}

$PAGE->requires->css( new moodle_url( '/mod/vpl/css/sh.css' ) );

// Print header.
$vpl->print_header( get_string( 'submissionview', VPL ) );
$vpl->print_view_tabs( basename( __FILE__ ) );

// Check consistence.
if (! $subinstance) {
    vpl_redirect(vpl_mod_href( 'view.php', 'id', $id, 'userid', $userid ),
                 get_string( 'nosubmission', VPL ));
}

$submissionid = $subinstance->id;

if ($vpl->get_instance()->id != $subinstance->vpl) {
    throw new moodle_exception('error:inconsistency', 'mod_vpl', 'vpl_submission');
}

// Display submission.

$submission = new mod_vpl_submission( $vpl, $subinstance );

if ($vpl->get_visiblegrade() || $vpl->has_capability( VPL_GRADE_CAPABILITY )) {
    if ($submission->is_graded()) {
        echo '<h2>' . get_string( 'grade', 'core_grades' ) . '</h2>';
        $submission->print_grade( true );
        \mod_vpl\event\submission_grade_viewed::log($submission);
    }
}
$submission->print_submission();
$vpl->print_footer();
\mod_vpl\event\submission_viewed::log( $submission );
