<?php
/**
 * @package		VPL. edit/execute submission class
 * @copyright	2014 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../vpl_submission_CE.class.php';
require_once dirname(__FILE__).'/../vpl_example_CE.class.php';
class mod_vpl_edit{
	public static function files2object($array_files){
		$files = array();
		foreach($array_files as $name => $data){
			$file = array('name' =>$name,'data'=>$data);
			$files[] = $file;
		}
		return $files;
	}
	
	public static function save($vpl,$userid,$files){
		if($vpl->add_submission($userid,$files,'',$error_message)){
			$id = $vpl->get_course_module()->id;
			$vpl->add_to_log('submit files',vpl_rel_url('forms/submissionview.php','id',$id,'userid',$userid));
		}else{
			throw new Exception(get_string('notsaved',VPL).': '.$error_message);
		}
	}
	
	public static function get_requested_files($vpl){
		$req_fgm = $vpl->get_required_fgm();
		$req_filelist =$req_fgm->getFileList();
		$nf = count($req_filelist);
		$files = Array();
		for( $i = 0; $i < $nf; $i++){
			$filename=$req_filelist[$i];
			$filedata=$req_fgm->getFileData($req_filelist[$i]);
			$files[$filename]=$filedata;
		}
		return $files;
	}
	
	public static function get_submitted_files($vpl,$userid,& $CE){
		$CE = false;
		$lastsub = $vpl->last_user_submission($userid);
		if($lastsub){
			$submission = new mod_vpl_submission($vpl, $lastsub);
			$fgp =  $submission->get_submitted_fgm();
			$filelist = $fgp->getFileList();
			$nf=count($filelist);
			for( $i = 0; $i < $nf; $i++){
				$filename=$filelist[$i];
				$filedata=$fgp->getFileData($filelist[$i]);
				$files[$filename]=$filedata;
			}
			$CE=$submission->get_CE_for_editor();
		}else{
			$files = self::get_requested_files($vpl);
		}
		return $files;
	}
	
	public static function execute($vpl,$userid,$action){
		$example = $vpl->get_instance()->example;
		$lastsub = $vpl->last_user_submission($userid);
		if(!$lastsub && !$example){
			throw new Exception(get_string('nosubmission',VPL));
		}
		if($example){
			$submission = new mod_vpl_example_CE($vpl);
		}else{
			$submission = new mod_vpl_submission_CE($vpl, $lastsub);
		}
		$translate = array('run'=>0,'debug'=>1,'evaluate'=>2);
		return $submission->run($translate[$action]);
	}
	
	public static function retrieve_result($vpl,$userid){
		$lastsub = $vpl->last_user_submission($userid);
		if(!$lastsub){
			throw new Exception(get_string('nosubmission',VPL));
		}
		$submission = new mod_vpl_submission_CE($vpl, $lastsub);
		return $submission->retrieveResult();
	}
	
	public static function cancel($vpl,$userid){
		$example = $vpl->get_instance()->example;
		$lastsub = $vpl->last_user_submission($userid);
		if(!$lastsub && !$example){
			throw new Exception(get_string('nosubmission',VPL));
		}
		if($example){
			$submission = new mod_vpl_example_CE($vpl);
		}else{
			$submission = new mod_vpl_submission_CE($vpl, $lastsub);
		}
		return $submission->cancelProcess();
	}
}