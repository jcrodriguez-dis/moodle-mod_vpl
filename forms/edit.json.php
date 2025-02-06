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
 * Processes AJAX requests from IDE
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

define( 'AJAX_SCRIPT', true );

require(__DIR__ . '/../../../config.php');

global $PAGE, $OUTPUT, $USER;

$result = new stdClass();
$result->success = true;
$result->response = new stdClass();
$result->error = '';
try {
    require_once(dirname( __FILE__ ) . '/edit.class.php');
    if (! isloggedin()) {
        throw new Exception( get_string( 'loggedinnot' ) );
    }
    $id = required_param( 'id', PARAM_INT ); // Course module id.
    $action = required_param( 'action', PARAM_ALPHANUMEXT );
    $userid = optional_param( 'userid', false, PARAM_INT );
    $subid = optional_param( 'subid', false, PARAM_INT );
    $copy = optional_param('privatecopy', false, PARAM_INT);
    $vpl = new mod_vpl( $id );
    // TODO use or not sesskey."require_sesskey();".
    require_login( $vpl->get_course(), false );

    $PAGE->set_url( new moodle_url( '/mod/vpl/forms/edit.json.php', [
            'id' => $id,
            'action' => $action,
    ] ) );
    echo $OUTPUT->header(); // Send headers.
    $rawdata = file_get_contents( "php://input" );
    $rawdatasize = strlen( $rawdata );
    if ($_SERVER['CONTENT_LENGTH'] != $rawdatasize) {
        throw new Exception( "Ajax POST error: CONTENT_LENGTH expected " . $_SERVER['CONTENT_LENGTH'] . " found $rawdatasize)" );
    }
    \mod_vpl\util\phpconfig::increase_memory_limit();
    $actiondata = json_decode($rawdata, null, 512, JSON_INVALID_UTF8_SUBSTITUTE );
    if (! $vpl->is_submit_able()) {
        throw new Exception( get_string( 'notavailable' ) );
    }
    if (! $userid || $userid == $USER->id) { // Make load own submission.
        $userid = $USER->id;
        $vpl->require_capability( VPL_SUBMIT_CAPABILITY );
        $vpl->restrictions_check();
    } else { // Access other user submission.
        $vpl->require_capability( VPL_GRADE_CAPABILITY );
    }
    $instance = $vpl->get_instance();
    switch ($action) {
	
        //Added by Tamar
        case 'setlanguage':
            require_sesskey();
            $selectedLanguage = required_param('selectedLanguage', PARAM_ALPHANUMEXT);
            // You might want to validate $selectedLanguage against allowed values
            // For example:
            $allowedLanguages = ['python', 'cpp', 'java','c']; // Add other allowed languages
            if (!in_array($selectedLanguage, $allowedLanguages) && $selectedLanguage != '') {
                throw new Exception('Invalid language selected');
            }
    
            // Use $SESSION global object for Moodle session management
            global $SESSION;
            $SESSION->runscript = $selectedLanguage;
    
            $result->response->runscript = $SESSION->runscript;
            break;
        
        //Changed by Tamar
        case 'save':
            if ($userid != $USER->id) {
                $vpl->require_capability(VPL_MANAGE_CAPABILITY);
            }
            // Files being saved by the student
            $files = mod_vpl_edit::filesfromide($actiondata->files);
        
            // Retrieve files from the last submission
            $previousFiles = [];
            $lastSubmission = $vpl->last_user_submission($userid);
            if ($lastSubmission) {
                $submission = new mod_vpl_submission($vpl, $lastSubmission);
                $previousFiles = $submission->get_submitted_fgm()->getallfiles();
            } else {
                // No previous submission, so get required files
                $previousFiles = mod_vpl_edit::get_requested_files($vpl);
            }
        
            // Merge previous files with current files
            foreach ($files as $filename => $filedata) {
                $previousFiles[$filename] = $filedata;
            }
        
            // Now save all files together
            $result->response = mod_vpl_edit::save($vpl, $userid, $previousFiles, $actiondata->comments, $actiondata->version);
            break;
            
        case 'update':
            $files = mod_vpl_edit::filesfromide( $actiondata->files );
            $filestodelete = isset($actiondata->filestodelete) ? $actiondata->filestodelete : [];
            $result->response = mod_vpl_edit::update($vpl,
                                                     $userid,
                                                     $actiondata->processid,
                                                     $files,
                                                     $filestodelete);
            break;
        case 'resetfiles':
            $files = mod_vpl_edit::get_requested_files( $vpl );
            $result->response->files = mod_vpl_edit::filestoide( $files );
            break;
        case 'load':
            if ( isset($actiondata->submissionid) ) {
                $subid = $actiondata->submissionid;
            }
            if ( $subid && $vpl->has_capability( VPL_GRADE_CAPABILITY ) ) {
                $load = mod_vpl_edit::load( $vpl, $userid , $subid);
            } else {
                $load = mod_vpl_edit::load( $vpl, $userid );
            }
            if ($copy) {
                $load->version = -1;
            }

            //Added by Tamar
            // **Add this code to filter files based on selected language**
            global $SESSION;
            $selectedLanguage = isset($SESSION->runscript) ? $SESSION->runscript : '';
            if ($selectedLanguage != '') {
                // Define which file extensions correspond to each language
                $languageExtensions = [
                    'python' => ['.py'],
                    'java' => ['.java'],
                    'cpp' => ['.cpp', '.hpp'],
                    'c' => ['.c', '.h'],
                    // Add other languages and their extensions as needed
                ];
            
                // Get the list of extensions for the selected language
                $allowedExtensions = isset($languageExtensions[$selectedLanguage]) ? $languageExtensions[$selectedLanguage] : [];
            
                // Filter the files
                $filteredFiles = [];
                foreach ($load->files as $filename => $filedata) {
                    foreach ($allowedExtensions as $extension) {
                        if (substr($filename, -strlen($extension)) === $extension) {
                            $filteredFiles[$filename] = $filedata;
                            break; // Break the inner loop to avoid checking other extensions
                        }
                    }
                }
                $load->files = $filteredFiles;
            }
            
            $load->files = mod_vpl_edit::filestoide( $load->files );
            $result->response = $load;
            break;
        case 'run':
        case 'debug':
        case 'evaluate':
            if (! $instance->$action && ! $vpl->has_capability( VPL_GRADE_CAPABILITY )) {
                throw new Exception( get_string( 'notavailable' ) );
            }
            $result->response = mod_vpl_edit::execute( $vpl, $userid, $action, $actiondata );
            break;
        case 'retrieve':
            $result->response = mod_vpl_edit::retrieve_result( $vpl, $userid, $actiondata->processid );
            break;
        case 'cancel':
            $result->response->error = mod_vpl_edit::cancel( $vpl, $userid, $actiondata->processid );
            break;
        case 'getjails':
            $result->response->servers = vpl_jailserver_manager::get_https_server_list( $vpl->get_instance()->jailservers );
            break;
        case 'directrun':
            $files = mod_vpl_edit::filesfromide( $actiondata->files );
            $result->response = mod_vpl_edit::directrun( $vpl, $userid, $actiondata->command, $files);
            break;
        
            
        default:
            throw new Exception( 'ajax action error: ' + $action );
    }
    if ($result->response === null) {
        $result->success = false;
        $result->error = "Response is null for $action";
    } else {
        $duedate = $vpl->get_effective_setting('duedate', $userid);
        $timeleft = $duedate - time();
        $hour = 60 * 60;
        if ( $duedate > 0 && $timeleft > -$hour ) {
            $result->response->timeLeft = $timeleft;
        }
    }
} catch ( Exception $e ) {
    $result->success = false;
    $result->error = $e->getMessage();
}
echo json_encode( $result );
die();
