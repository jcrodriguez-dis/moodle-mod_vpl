<?php
/**
 * @version		$Id: downloadsubmission.php,v 1.15 2012-06-05 23:22:09 juanca Exp $
 * @package		VPL. Download submission in zip file
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../similarity/watermark.php';
require_once dirname(__FILE__).'/../../../config.php';
global $CFG, $USER;
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../vpl_submission.class.php';
try{
    require_login();
    $id = required_param('id',PARAM_INT);
    $vpl = new mod_vpl($id);
    $userid = optional_param('userid',FALSE,PARAM_INT);
    $submissionid =  optional_param('submissionid',FALSE,PARAM_INT);
    if(!$vpl->has_capability(VPL_GRADE_CAPABILITY)){
        $userid = FALSE;
        $submissionid = FALSE;
    }
	//Read record
	if($userid && $userid != $USER->id){
		//Grader
		$vpl->require_capability(VPL_GRADE_CAPABILITY);
		$grader =TRUE;
		if($submissionid){
			$subinstance = $DB->get_record('vpl_submissions',array('id' => $submissionid));
		}else{
			$subinstance = $vpl->last_user_submission($userid);
		}
	}
	else{
		//Download own submission
		$vpl->require_capability(VPL_VIEW_CAPABILITY);
		$userid = $USER->id;
		$grader = FALSE;
		if($submissionid && $vpl->has_capability(VPL_GRADE_CAPABILITY)){
			$subinstance = $DB->get_record('vpl_submissions',array('id' => $submissionid));
		}else{
			$subinstance = $vpl->last_user_submission($userid);
		}
		$vpl->password_check();
	}
	
	//Check consistence
	if(!$subinstance){
		throw new Exception(get_string('nosubmission',VPL));
	}
	if($subinstance->vpl != $vpl->get_instance()->id){
		throw new Exception(get_string('invalidcourseid'));
	}
	$submissionid = $subinstance->id;
	
	if($vpl->is_inconsistent_user($subinstance->userid,$userid)){
		throw new Exception('vpl submission user inconsistence');
	}
	if($vpl->get_instance()->id != $subinstance->vpl){
		throw new Exception('vpl submission vpl inconsistence');
	}
	$submission = new mod_vpl_submission($vpl,$subinstance);
	$fgm = $submission->get_submitted_fgm();
	$fgm->download_files($vpl->get_printable_name());
}catch (Exception $e){
	$vpl->prepare_page('views/downloadsubmission.php', array('id' => $id));
	$vpl->print_header(get_string('download',VPL));
	echo $OUTPUT->box($e->getMessage());
	$vpl->print_footer();
}
