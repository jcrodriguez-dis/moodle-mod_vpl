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
 * Process submission form
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/submission_form.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once(dirname(__FILE__).'/../vpl_submission.class.php');

global $USER;

require_login();

$id = required_param( 'id', PARAM_INT );
$userid = optional_param( 'userid', false, PARAM_INT );
$vpl = new mod_vpl( $id );
if ($userid) {
    $vpl->prepare_page( 'forms/submission.php', array (
            'id' => $id,
            'userid' => $userid
    ) );
} else {
    $vpl->prepare_page( 'forms/submission.php', array (
            'id' => $id
    ) );
}
if (! $vpl->is_submit_able()) {
    vpl_redirect( '?id=' . $id, get_string( 'notavailable' ));
}
if (! $userid || $userid == $USER->id) { // Make own submission.
    $userid = $USER->id;
    if ($vpl->get_instance()->restrictededitor) {
        $vpl->require_capability( VPL_MANAGE_CAPABILITY );
    }
    $vpl->require_capability( VPL_SUBMIT_CAPABILITY );
    $vpl->restrictions_check();
} else { // Make other user submission.
    $vpl->require_capability( VPL_MANAGE_CAPABILITY );
}
$instance = $vpl->get_instance();
$vpl->print_header( get_string( 'submission', VPL ) );
$vpl->print_view_tabs( basename( __FILE__ ) );
$mform = new mod_vpl_submission_form( 'submission.php', $vpl );
if ($mform->is_cancelled()) {
    vpl_inmediate_redirect( vpl_mod_href( 'view.php', 'id', $id ) );
    die();
}
if ($fromform = $mform->get_data()) {
    $rawpostsize = strlen( file_get_contents( "php://input" ) );
    if ($_SERVER['CONTENT_LENGTH'] != $rawpostsize) {
        $error = "NOT SAVED (Http POST error: CONTENT_LENGTH expected " . $_SERVER['CONTENT_LENGTH'] . " found $rawpostsize)";
        vpl_redirect( vpl_mod_href( 'forms/submission.php', 'id', $id, 'userid', $userid ),
                    $error, 'error' );
        die();
    }
    \mod_vpl\util\phpconfig::increase_memory_limit();
    $files = array ();
    for ($i = 0; $i < $instance->maxfiles; $i ++) {
        $attribute = 'file' . $i;
        $name = $mform->get_new_filename( $attribute );
        $data = $mform->get_file_content( $attribute );
        if ($data !== false && $name !== false) {
            // Autodetect text file encode if not binary.
            if (! vpl_is_binary( $name )) {
                $encode = mb_detect_encoding( $data, 'UNICODE, UTF-16, UTF-8, ISO-8859-1', true );
                if ($encode > '') { // If code detected.
                    $data = iconv( $encode, 'UTF-8', $data );
                }
            }
            $files[$name] = $data;
        }
    }
    $errormessage = '';
    if ($subid = $vpl->add_submission( $userid, $files, $fromform->comments, $errormessage )) {
        \mod_vpl\event\submission_uploaded::log( array (
                'objectid' => $subid,
                'context' => $vpl->get_context(),
                'relateduserid' => ($USER->id != $userid ? $userid : null)
        ) );

        // If evaluate on submission.
        if ($instance->evaluate && $instance->evaluateonsubmission) {
            vpl_redirect( vpl_mod_href( 'forms/evaluation.php', 'id', $id, 'userid', $userid ),
                          get_string( 'saved', VPL ));
        }
        vpl_redirect( vpl_mod_href( 'forms/submissionview.php', 'id', $id, 'userid', $userid ),
                      get_string( 'saved', VPL ));
    } else {
        vpl_notice( get_string( 'notsaved', VPL ) );
        vpl_redirect( vpl_mod_href( 'forms/submission.php', 'id', $id, 'userid', $userid ),
                      $errormessage);
    }
}

// Display page.
$data = new stdClass();
$data->id = $id;
$data->userid = $userid;
$mform->set_data( $data );
$mform->display();
$vpl->print_footer();
