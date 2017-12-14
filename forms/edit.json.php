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
$outcome = new stdClass();
$outcome->success = true;
$outcome->response = new stdClass();
$outcome->error = '';
try {
    require_once(dirname( __FILE__ ) . '/edit.class.php');
    if (! isloggedin()) {
        throw new Exception( get_string( 'loggedinnot' ) );
    }

    $id = required_param( 'id', PARAM_INT ); // Course id.
    $action = required_param( 'action', PARAM_ALPHANUMEXT );
    $userid = optional_param( 'userid', false, PARAM_INT );
    $subid = optional_param( 'subid', false, PARAM_INT );
    $vpl = new mod_vpl( $id );
    // TODO use or not sesskey."require_sesskey();".
    require_login( $vpl->get_course(), false );

    $PAGE->set_url( new moodle_url( '/mod/vpl/forms/edit.json.php', array (
            'id' => $id,
            'action' => $action
    ) ) );
    echo $OUTPUT->header(); // Send headers.
    $rawdata = file_get_contents( "php://input" );
    $rawdatasize = strlen( $rawdata );
    if ($_SERVER ['CONTENT_LENGTH'] != $rawdatasize) {
        throw new Exception( "Ajax POST error: CONTENT_LENGTH expected " . $_SERVER ['CONTENT_LENGTH'] . " found $rawdatasize)" );
    }
    $actiondata = json_decode( $rawdata );
    if (! $vpl->is_submit_able()) {
        throw new Exception( get_string( 'notavailable' ) );
    }
    if (! $userid || $userid == $USER->id) { // Make load own submission.
        $userid = $USER->id;
        $vpl->require_capability( VPL_SUBMIT_CAPABILITY );
        $vpl->restrictions_check();
    } else { // Make other user submission.
        $vpl->require_capability( VPL_MANAGE_CAPABILITY );
    }
    $instance = $vpl->get_instance();
    switch ($action) {
        case 'save' :
            $files = mod_vpl_edit::filesfromide( $actiondata->files );
            if (! isset($actiondata->comments) ) {
                $actiondata->comments = '';
            }
            mod_vpl_edit::save( $vpl, $userid, $files, $actiondata->comments );
            break;
        case 'resetfiles' :
            $files = mod_vpl_edit::get_requested_files( $vpl );
            $outcome->response->files = mod_vpl_edit::filestoide( $files );
            break;
        case 'load' :
            if ( $subid && $vpl->has_capability( VPL_MANAGE_CAPABILITY ) ) {
                $load = mod_vpl_edit::load( $vpl, $userid , $subid);
            } else {
                $load = mod_vpl_edit::load( $vpl, $userid );
            }
            $load->files = mod_vpl_edit::filestoide( $load->files );
            $outcome->response = $load;
            break;
        case 'run' :
        case 'debug' :
        case 'evaluate' :
            if (! $instance->$action and ! $vpl->has_capability( VPL_GRADE_CAPABILITY )) {
                throw new Exception( get_string( 'notavailable' ) );
            }
            $outcome->response = mod_vpl_edit::execute( $vpl, $userid, $action, $actiondata );
            break;
        case 'retrieve' :
            $outcome->response = mod_vpl_edit::retrieve_result( $vpl, $userid );
            break;
        case 'cancel' :
            $outcome->response = mod_vpl_edit::cancel( $vpl, $userid );
            break;
        case 'getjails' :
            $outcome->response->servers = vpl_jailserver_manager::get_https_server_list( $vpl->get_instance()->jailservers );
            break;
        default :
            throw new Exception( 'ajax action error: ' + $action );
    }
    $timeleft = $instance->duedate - time();
    $hour = 60 * 60;
    if ( $instance->duedate > 0 && $timeleft > -$hour ) {
        $outcome->response->timeLeft = $timeleft;
    }
} catch ( Exception $e ) {
    $outcome->success = false;
    $outcome->error = $e->getMessage();
}
echo json_encode( $outcome );
die();
