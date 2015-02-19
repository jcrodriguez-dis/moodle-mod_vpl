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
 * @copyright    2012 Juan Carlos Rodríguez-del-Pino
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author        Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

define('AJAX_SCRIPT', true);
$outcome = new stdClass();
$outcome->success = true;
$outcome->response = new stdClass();
$outcome->error = '';
try{
    require_once dirname(__FILE__).'/../../../config.php';
    require_once dirname(__FILE__).'/edit.class.php';
    if(!isloggedin()){
        throw new Exception(get_string('loggedinnot'));
    }

    $id      = required_param('id', PARAM_INT); // course id
    $action  = required_param('action', PARAM_ALPHANUMEXT);
    $userid = optional_param('userid',FALSE,PARAM_INT);
    $vpl = new mod_vpl($id);
    //TODO use or not sesskey
    //require_sesskey();
    require_login($vpl->get_course(),false);

    $PAGE->set_url(new moodle_url('/mod/vpl/forms/editor.json.php', array('id'=>$id, 'action'=>$action)));
    echo $OUTPUT->header(); // Send headers.
    $raw_data=file_get_contents("php://input");
    $raw_data_size = strlen($raw_data);
    if($_SERVER['CONTENT_LENGTH'] != $raw_data_size){
        throw new Exception("Ajax POST error: CONTENT_LENGTH expected "
                .$_SERVER['CONTENT_LENGTH']
                ." found $raw_data_size)");
    }
    $data=json_decode($raw_data);
    if (!$vpl->is_submit_able()) {
       throw new Exception(get_string('notavailable'));
    }
    if (!$userid || $userid == $USER->id) { // Make own submission
        $userid = $USER->id;
        $vpl->require_capability ( VPL_SUBMIT_CAPABILITY );
        if (! $vpl->pass_network_check ()) {
            throw new Exception(get_string ( 'opnotallowfromclient', VPL ) . ' ' . getremoteaddr ());
        }
        if(!$vpl->pass_password_check()){
            throw new Exception(get_string('requiredpassword',VPL));
        }
    } else { // Make other user submission
        $vpl->require_capability ( VPL_MANAGE_CAPABILITY );
    }
    $instance = $vpl->get_instance();
    switch ($action) {
    case 'save':
        $postfiles=(array)$data;
        $files = Array();
        foreach($postfiles as $name => $data){
            $files[]=array('name' => $name, 'data' => $data);
        }
        mod_vpl_edit::save($vpl,$userid,$files);
    break;
    case 'resetfiles':
        $outcome->response->files = mod_vpl_edit::get_requested_files($vpl);
    break;
    case 'run':
        if(!$instance->run and !$vpl->has_capability ( VPL_GRADE_CAPABILITY ))
            throw new Exception(get_string('notavailable'));
        $outcome->response = mod_vpl_edit::execute($vpl,$userid,$action);
    break;
    case 'debug':
        if(!$instance->debug and !$vpl->has_capability ( VPL_GRADE_CAPABILITY ))
            throw new Exception(get_string('notavailable'));
        $outcome->response = mod_vpl_edit::execute($vpl,$userid,$action);
    break;
    case 'evaluate':
        if(!$instance->evaluate and !$vpl->has_capability ( VPL_GRADE_CAPABILITY ))
            throw new Exception(get_string('notavailable'));
        $outcome->response = mod_vpl_edit::execute($vpl,$userid,$action);
    break;
    case 'retrieve':
        $outcome->response=mod_vpl_edit::retrieve_result($vpl,$userid);
        break;
    case 'cancel':
        $outcome->response=mod_vpl_edit::cancel($vpl,$userid);
        break;
    case 'getjails':
        $outcome->response->servers=vpl_jailserver_manager::get_https_server_list($vpl->get_instance()->jailservers);
        break;
    default:
        throw new Exception('ajax action error');
  }
}catch(Exception $e){
    $outcome->success =false;
    $outcome->error = $e->getMessage();
}
echo json_encode($outcome);
die();
