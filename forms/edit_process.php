<?php
/**
 * @version		$Id: edit_process.php,v 1.8 2013-04-16 17:45:40 juanca Exp $
 * @package		VPL. process submission edit
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

global $CFG;

require_once dirname(__FILE__).'/../../../config.php';
require_once $CFG->libdir.'/formslib.php';
require_once $CFG->libdir.'/moodlelib.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../vpl_submission_CE.class.php';
require_once dirname(__FILE__).'/../vpl_example_CE.class.php';
echo '<html><head>';
$url=new moodle_url('/mod/vpl/jscript/transferResult.js');
echo '<script type="text/javascript" src="'.$url->out().'"></script>';
echo '</head><body>';
if(!isloggedin()){
	vpl_js_alert(get_string('loggedinnot'));
	print_error('loggedinnot');
}
$id = required_param('id',PARAM_INT);
$vpl = new mod_vpl($id);
$userid = optional_param('userid',FALSE,PARAM_INT);
if(!$userid || $userid == $USER->id){//Edit own submission
	$userid = $USER->id;
	$vpl->require_capability(VPL_SUBMIT_CAPABILITY,true);
}
else { //Edit other user submission
	$vpl->require_capability(VPL_MANAGE_CAPABILITY,true);
}
//Display page
if(!$vpl->is_submit_able()){
	vpl_js_alert(get_string('notavailable'));
	print_error('notavailable');
}
if( !$vpl->pass_password_check() || !$vpl->pass_network_check()){
	vpl_js_alert(get_string('passwordrequired'));
	print_error('passwordrequired');
}
if($fontsize=optional_param('fontsize',FALSE,PARAM_INT)){
	set_user_preference('vpl_fontsize', $fontsize);
}
try{
if(optional_param('save','',PARAM_TEXT)!=''){
	if($userid != $USER->id){
		$vpl->require_capability(VPL_MANAGE_CAPABILITY);
	}
	mod_vpl_submission_CE::send_start_process(get_string('saving',VPL));
	$vpl->add_to_log('save submission', vpl_rel_url('forms/edit_process.php','id',$id,'userid',$userid));
	$raw_POST_size = strlen(file_get_contents("php://input")); 
	if($_SERVER['CONTENT_LENGTH'] != $raw_POST_size){
		$error="NOT SAVED (Http POST error: CONTENT_LENGTH expected ".$_SERVER['CONTENT_LENGTH']." found $raw_POST_size)";
		mod_vpl_submission_CE::send_end_process($error);
		vpl_js_alert($error);
		echo '</body></html>';
		die;
	}
	$rfn = $vpl->get_required_fgm();
	$minfiles = count($rfn->getFilelist());
	$files = array();
	$max = $vpl->get_instance()->maxfiles;
	for( $i = 0; $i < $max; $i++){
		$field_filename = 'filename'.$i;
		$field_filedata = 'filedata'.$i;
		if(isset($_POST[$field_filename]) && isset($_POST[$field_filedata])){
			$filename =$_POST[$field_filename];
			if($filename == '') break;
			$files[] = array('name' => $filename,
						'data' =>urldecode(stripslashes($_POST[$field_filedata])));
		}
		else{
			if($i < $minfiles){ //add empty file if required
				$files[] = array('name' => '', 'data' => '');
			}
		}
		mod_vpl_submission_CE::send_alive();
	}
	$error='';
	if(!$vpl->add_submission($userid,$files,'',$error)){
		mod_vpl_submission_CE::send_end_process($error);
		vpl_js_alert($error);
	}
	mod_vpl_submission_CE::send_end_process();
}
else if(optional_param('run','',PARAM_TEXT)!=''){
	if($userid != $USER->id || !$vpl->get_instance()->run){
		$vpl->require_capability(VPL_GRADE_CAPABILITY);
	}
	$vpl->add_to_log('run', vpl_rel_url('forms/edit.php','id',$id,'userid',$userid));
	$subinstance = $vpl->last_user_submission($userid);
	if($subinstance !== false || $vpl->get_instance()->example){
		if($vpl->get_instance()->example){
			$submission=new mod_vpl_example_CE($vpl);
		}else{
			$submission=new mod_vpl_submission_CE($vpl,$subinstance);
		}
		$submission->send_start_process(get_string('running',VPL));
		try{
			$submission->run(0);
		}catch(Exception $e){
			vpl_js_alert($e->getMessage());
		}
		$submission->send_end_process();
	}else{
		vpl_js_alert(get_string('nosubmission',VPL));
		mod_vpl_submission_CE::send_end_process();
	}
}
else if(optional_param('debug','',PARAM_TEXT)!=''){
	if($userid != $USER->id || !$vpl->get_instance()->debug){
		$vpl->require_capability(VPL_GRADE_CAPABILITY);
	}
	$vpl->add_to_log('debug', vpl_rel_url('forms/edit.php','id',$id,'userid',$userid));
	$subinstance = $vpl->last_user_submission($userid);
	if($subinstance !== false || $vpl->get_instance()->example){
		if($vpl->get_instance()->example){
			$submission=new mod_vpl_example_CE($vpl);
		}else{
			$submission=new mod_vpl_submission_CE($vpl,$subinstance);
		}
		$submission->send_start_process(get_string('debugging',VPL));
		try{
			$submission->run(1);
		}catch(Exception $e){
			vpl_js_alert($e->getMessage());
		}
		$submission->send_end_process();
	}else{
		vpl_js_alert(get_string('nosubmission',VPL));
		mod_vpl_submission_CE::send_end_process();
	}
}
else if(optional_param('evaluate','',PARAM_TEXT)!=''){
	if($userid != $USER->id || !$vpl->get_instance()->evaluate){
		$vpl->require_capability(VPL_GRADE_CAPABILITY);
	}
	$vpl->add_to_log('evaluate', vpl_rel_url('forms/edit.php','id',$id,'userid',$userid));
	$subinstance = $vpl->last_user_submission($userid);
	if($subinstance !== false){
		$submission=new mod_vpl_submission_CE($vpl,$vpl->last_user_submission($userid));
		$submission->send_start_process(get_string('evaluating',VPL));
		try{
			$submission->evaluate();
		}catch(Exception $e){
			vpl_js_alert($e->getMessage());
		}
		$submission->send_end_process();
	}else{
		vpl_js_alert(get_string('nosubmission',VPL));
		mod_vpl_submission_CE::send_end_process();
	}
}
else{
	$vpl->add_to_log('error post', vpl_rel_url('forms/edit.php','id',$id,'userid',$userid));
	vpl_js_alert(get_string('error'). ': in post');
}
echo '</body></html>';
}catch(Exception $e){
	vpl_js_alert($e->getMessage()."\n".$e->getTraceAsString());
}
?>