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
 * @version        $Id: edit_process.php,v 1.8 2013-04-16 17:45:40 juanca Exp $
 * @package mod_vpl. process submission edit
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
    require_once dirname(__FILE__).'/../locallib.php';
    require_once dirname(__FILE__).'/../vpl.class.php';
    if(!isloggedin()){
        throw new Exception(get_string('loggedinnot'));
    }

    $id      = required_param('id', PARAM_INT); // course id
    $action  = required_param('action', PARAM_ALPHANUMEXT);
    $vpl = new mod_vpl($id);
    //TODO use or not sesskey
    //require_sesskey();
    require_login($vpl->get_course(),false);
    $vpl->require_capability(VPL_MANAGE_CAPABILITY);
    $PAGE->set_url(new moodle_url('/mod/vpl/forms/requiredfiles.json.php', array('id'=>$id, 'action'=>$action)));
    echo $OUTPUT->header(); // Send headers.
    $data=json_decode(file_get_contents('php://input'));
    switch ($action) {
    case 'save':
        $postfiles=(array)$data;
        foreach($postfiles as $name => $data){
            $files[]=array('name' => $name, 'data' => $data);
        }
        $req_fgm = $vpl->get_required_fgm();
        $req_filelist =$req_fgm->getFileList();
        //TODO Make new file_group operation to do it better
        for($i=count($req_filelist)-1; $i>=0; $i--){
            $req_fgm->deleteFile($i);
        }
        foreach($postfiles as $name => $data){
            $req_fgm->addFile($name, $data);
        }
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
