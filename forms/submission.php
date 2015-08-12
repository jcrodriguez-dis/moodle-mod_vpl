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
 * @version        $Id: submission.php,v 1.34 2013-06-10 08:15:42 juanca Exp $
 * @package mod_vpl. Process submission form
 * @copyright    2012 Juan Carlos Rodríguez-del-Pino
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author        Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/submission_form.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../vpl_submission.class.php';

require_login();

$id = required_param('id',PARAM_INT);
$userid = optional_param('userid',FALSE,PARAM_INT);
$vpl = new mod_vpl($id);
if($userid){
    $vpl->prepare_page('forms/submission.php', array('id' => $id, 'userid' => $userid));
}else{
    $vpl->prepare_page('forms/submission.php', array('id' => $id));
}
if(!$vpl->is_submit_able()){
    notice(get_string('notavailable'));
}
if(!$userid || $userid == $USER->id){//Make own submission
    $userid = $USER->id;
    if($vpl->get_instance()->restrictededitor){
        $vpl->require_capability(VPL_MANAGE_CAPABILITY);
    }
    $vpl->require_capability(VPL_SUBMIT_CAPABILITY);
    $vpl->network_check();
    $vpl->password_check();
}
else { //Make other user submission
    $vpl->require_capability(VPL_MANAGE_CAPABILITY);
}
$instance = $vpl->get_instance();
$vpl->print_header(get_string('submission',VPL));
$vpl->print_view_tabs(basename(__FILE__));
$mform = new mod_vpl_submission_form('submission.php',$vpl);
if ($mform->is_cancelled()){
    vpl_inmediate_redirect(vpl_mod_href('view.php','id',$id));
    die;
}
if ($fromform=$mform->get_data()){
    $raw_POST_size = strlen(file_get_contents("php://input"));
    if($_SERVER['CONTENT_LENGTH'] != $raw_POST_size){
        $error="NOT SAVED (Http POST error: CONTENT_LENGTH expected ".$_SERVER['CONTENT_LENGTH']." found $raw_POST_size)";
        notice($error,vpl_mod_href('forms/submission.php','id',$id,'userid',$userid),$vpl->get_course());
        die;
    }
    $rfn = $vpl->get_required_fgm();
    $minfiles = count($rfn->getFilelist());
    $files=array();
    for($i = 0 ; $i < $instance->maxfiles ; $i++ ){
        $attribute = 'file'.$i;
        $name = trim($mform->get_new_filename($attribute));
        $data = $mform->get_file_content($attribute);
        if($data !== false && $name !== false ){
        //autodetect data file encode
            $ext = strtolower (pathinfo ($name, PATHINFO_EXTENSION));
            if(in_array($ext, Array('jar','zip','jpg','gif'))){
                $data = chunk_split(base64_encode($data));
                $name .= '.b64';
            }else{
                if($data != ''){
                    $encode = mb_detect_encoding($data, 'UNICODE, UTF-16, UTF-8, ISO-8859-1',true);
                    if($encode > ''){ //If code detected
                        $data = iconv($encode,'UTF-8',$data);
                    }
                }
            }
            $files[] = array('name' => $name, 'data' => $data);
        }else{
            if($i < $minfiles){ //add empty file if required
                $files[] = array('name' => '', 'data' => '');
            }
        }
    }
    $error_message='';
    if($subid=$vpl->add_submission($userid,$files,$fromform->comments,$error_message)){
        \mod_vpl\event\submission_uploaded::log(array(
                'objectid' => $subid,
                'context' => $vpl->get_context(),
                'relateduserid' => ($USER->id != $userid?$userid:null)
        ));

        //if evaluate on submission
        if($instance->evaluate && $instance->evaluateonsubmission){
            notice(get_string('saved',VPL),
                vpl_mod_href('forms/evaluation.php','id',$id,'userid',$userid));
        }
        notice(get_string('saved',VPL),
            vpl_mod_href('forms/submissionview.php','id',$id,'userid',$userid));
    }else{
        echo $OUTPUT->box(get_string('notsaved',VPL));
        notice($error_message,vpl_mod_href('forms/submission.php','id',$id,'userid',$userid),$vpl->get_course());
    }
}
//Display page

$data = new stdClass();
$data->id = $id;
$data->userid = $userid;
$mform->set_data($data);
$mform->display();
$vpl->print_footer();
