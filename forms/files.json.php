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
 * Process ajax files edit
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
    require_once(dirname( __FILE__ ) . '/../locallib.php');
    require_once(dirname( __FILE__ ) . '/../vpl.class.php');
    require_once(dirname( __FILE__ ) . '/edit.class.php');

    if (! isloggedin()) {
        throw new Exception( get_string( 'loggedinnot' ) );
    }

    $id = required_param( 'id', PARAM_INT ); // Course id.
    $type = required_param( 'type', PARAM_ALPHANUMEXT );
    $action = required_param( 'action', PARAM_ALPHANUMEXT );
    $vpl = new mod_vpl( $id );
    // TODO use or not sesskey "require_sesskey();".
    require_login( $vpl->get_course(), false );
    $vpl->require_capability( VPL_MANAGE_CAPABILITY );
    $PAGE->set_url( new moodle_url( '/mod/vpl/forms/files.json.php', array (
            'id' => $id,
            'type' => $type,
            'action' => $action
    ) ) );
    echo $OUTPUT->header(); // Send headers.
    $actiondata = json_decode( file_get_contents( 'php://input' ) );
    if ($type=='testcases'){
        $action .='cases';
    }
    switch ($action) {
        case 'save' :
            $postfiles = mod_vpl_edit::filesfromide($actiondata->files);
            $fgm = $vpl->get_fgm($type);
            $fgm->deleteallfiles();
            $fgm->addallfiles($postfiles);
            break;
        case 'load' :
            $fgm = $vpl->get_fgm($type);
            $outcome->response->files = mod_vpl_edit::filestoide( $fgm->getallfiles() );
            break;
        case 'resetfiles' :
            $fgm = $vpl->get_fgm('required');
            $outcome->response->files = mod_vpl_edit::filestoide( $fgm->getallfiles() );
            break;
        case 'run' :
        case 'debug' :
        case 'evaluate' :
            $outcome->response = mod_vpl_edit::execute( $vpl, $USER->id, $action, $actiondata );
            break;
        case 'retrieve' :
            $outcome->response = mod_vpl_edit::retrieve_result( $vpl, $USER->id );
            break;
        case 'savecases' :
            $filename = 'vpl_evaluate.cases';
            $postfiles = mod_vpl_edit::filesfromide($actiondata->files);
            if (count( $postfiles ) != 1 || ! isset( $postfiles [$filename] )) {
                throw new Exception( get_string( 'incorrect_file_name', VPL ) );
            }
            $fgm = $vpl->get_fgm('execution');
            $fgm->addFile( $filename, $postfiles [$filename] );
            $vpl->update();
            break;
        case 'loadcases' :
            $filename = 'vpl_evaluate.cases';
            $fgm = $vpl->get_fgm('execution');
            $files = array();
            $files[$filename] = $fgm->getfiledata($filename);
            $outcome->response->files = mod_vpl_edit::filestoide( $files );
            break;
        default :
            throw new Exception( 'ajax action error: ' + $action );
    }
} catch ( Exception $e ) {
    $outcome->success = false;
    $outcome->error = $e->getMessage();
}
echo json_encode( $outcome );
die();
