<?php
/**
 * @version		$Id: edit_process.php,v 1.8 2013-04-16 17:45:40 juanca Exp $
 * @package		VPL. process submission edit
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
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
	require_once dirname(__FILE__).'/../vpl_submission_CE.class.php';
	require_once dirname(__FILE__).'/../vpl_example_CE.class.php';
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
    switch ($action) {
	case 'save':
		$postfiles=(array)$data;
		$files = Array();
		foreach($postfiles as $name => $data){
			$files[]=array('name' => $name, 'data' => $data);
		}
		if($vpl->add_submission($userid,$files,'',$error_message)){
			$vpl->add_to_log('submit files',vpl_rel_url('forms/submissionview.php','id',$id,'userid',$userid));
		}else{
			throw new Exception(get_string('notsaved',VPL).': '.$error_message);
		}		
	break;
	case 'resetfiles':
	    $req_fgm = $vpl->get_required_fgm();
		$req_filelist =$req_fgm->getFileList();
		$nf = count($req_filelist);
		for( $i = 0; $i < $nf; $i++){
			$filename=$req_filelist[$i];
			$filedata=$req_fgm->getFileData($req_filelist[$i]);
			$files[$filename]=$filedata;
		}
		$outcome->response->files = $files;
	break;
	case 'run':
	case 'debug':
	case 'evaluate':
		$lastsub = $vpl->last_user_submission($userid);
		if(!$lastsub){
			throw new Exception(get_string('nosubmmision'));
		}
		$submission = new mod_vpl_submission_CE($vpl, $lastsub);
		$translate = array('run'=>0,'debug'=>1,'evaluate'=>2);
		$outcome->response=$submission->run($translate[$action]);
	break;
	case 'retrieve':
		$lastsub = $vpl->last_user_submission($userid);
		if(!$lastsub){
			throw new Exception(get_string('nosubmmision'));
		}
		$submission = new mod_vpl_submission_CE($vpl, $lastsub);
		$outcome->response=$submission->retrieveResult();
		break;
	case 'cancel':
		$lastsub = $vpl->last_user_submission($userid);
		if(!$lastsub){
			throw new Exception(get_string('nosubmmision'));
		}
		$submission = new mod_vpl_submission_CE($vpl, $lastsub);
		$outcome->response=$submission->cancelProcess();
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
