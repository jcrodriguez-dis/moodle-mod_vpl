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
 * Download submission in zip file
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */


require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../similarity/watermark.class.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once(dirname(__FILE__).'/../vpl_submission.class.php');

global $CFG, $USER;
try {
    require_login();
    $id = required_param( 'id', PARAM_INT );
    $vpl = new mod_vpl( $id );
    $userid = optional_param( 'userid', false, PARAM_INT );
    $submissionid = optional_param( 'submissionid', false, PARAM_INT );
    if (! $vpl->has_capability( VPL_GRADE_CAPABILITY )) {
        $userid = false;
        $submissionid = false;
    }
    // Read record.
    if ($userid && $userid != $USER->id) {
        // Grader.
        $vpl->require_capability( VPL_GRADE_CAPABILITY );
        $grader = true;
        if ($submissionid) {
            $subinstance = $DB->get_record( 'vpl_submissions', array (
                    'id' => $submissionid
            ) );
        } else {
            $subinstance = $vpl->last_user_submission( $userid );
        }
    } else {
        // Download own submission.
        $vpl->require_capability( VPL_VIEW_CAPABILITY );
        $userid = $USER->id;
        $grader = false;
        if ($submissionid && $vpl->has_capability( VPL_GRADE_CAPABILITY )) {
            $subinstance = $DB->get_record( 'vpl_submissions', array (
                    'id' => $submissionid
            ) );
        } else {
            $subinstance = $vpl->last_user_submission( $userid );
        }
        $vpl->restrictions_check();
    }

    // Check consistence.
    if (! $subinstance) {
        throw new Exception( get_string( 'nosubmission', VPL ) );
    }
    if ($subinstance->vpl != $vpl->get_instance()->id) {
        throw new Exception( get_string( 'invalidcourseid' ) );
    }
    $submissionid = $subinstance->id;

    if ($vpl->is_inconsistent_user( $subinstance->userid, $userid )) {
        throw new Exception( 'vpl submission user inconsistence' );
    }
    if ($vpl->get_instance()->id != $subinstance->vpl) {
        throw new Exception( 'vpl submission vpl inconsistence' );
    }
    $submission = new mod_vpl_submission( $vpl, $subinstance );
    $plugincfg = get_config('mod_vpl');
    $watermark = isset( $plugincfg->use_watermarks ) && $plugincfg->use_watermarks;
    $fgm = $submission->get_submitted_fgm();
    $fgm->download_files( $vpl->get_printable_name() , $watermark);
} catch ( Exception $e ) {
    $vpl->prepare_page( 'views/downloadsubmission.php', array (
            'id' => $id
    ) );
    $vpl->print_header( get_string( 'download', VPL ) );
    print_error($e->getMessage());
    $vpl->print_footer();
}
