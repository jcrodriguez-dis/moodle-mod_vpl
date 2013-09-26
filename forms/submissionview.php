<?php
/**
 * @version		$Id: submissionview.php,v 1.11 2013-06-10 08:18:06 juanca Exp $
 * @package		VPL. View a submission
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
global $CFG, $USER;
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/grade_form.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../vpl_submission.class.php';
try{
require_login();

$id = required_param('id',PARAM_INT);
$userid = optional_param('userid',FALSE,PARAM_INT);
$vpl = new mod_vpl($id);
if($userid){
	$vpl->prepare_page('forms/submissionview.php', array('id' => $id, 'userid' => $userid));
}else{
	$vpl->prepare_page('forms/submissionview.php', array('id' => $id));
}
if(!$vpl->is_visible()){
	$log_url = vpl_rel_url('forms/submissionview.php','id',$id);
	$vpl->add_to_log('view submission', $log_url, "not show");
	notice(get_string('notavailable'));
}
$course = $vpl->get_course();
$instance = $vpl->get_instance();

$submissionid =  optional_param('submissionid',FALSE,PARAM_INT);
//Read records
if($userid && $userid != $USER->id){
	//Grader
	$vpl->require_capability(VPL_GRADE_CAPABILITY);
	$grader =TRUE;
	if($submissionid){
		$subinstance = $DB->get_record('vpl_submissions', array('id' => $submissionid));
	}else{
		$subinstance = $vpl->last_user_submission($userid);
	}
}
else{
	//view own submission
	$vpl->require_capability(VPL_VIEW_CAPABILITY);
	$userid = $USER->id;
	$grader = FALSE;
	if($submissionid && $vpl->has_capability(VPL_GRADE_CAPABILITY)){
		$subinstance = $DB->get_record('vpl_submissions',array('id' => $submissionid));
	}else{
		$subinstance = $vpl->last_user_submission($userid);
	}
}
if($subinstance!=null && $subinstance->vpl != $vpl->get_instance()->id){
	print_error('invalidcourseid');
}
if($USER->id == $userid){
	$vpl->network_check();
	$vpl->password_check();
}
//Print header
$PAGE->requires->css(new moodle_url('/mod/vpl/css/sh.css'));
$vpl->print_header(get_string('submissionview',VPL));
$vpl->print_view_tabs(basename(__FILE__));
//Display submission

//Check consistence
if(!$subinstance){
	notice(get_string('nosubmission',VPL),vpl_mod_href('view.php','id',$id,'userid',$userid));
}
$submissionid = $subinstance->id;

if($vpl->is_inconsistent_user($subinstance->userid,$userid)){
	print_error('vpl submission user inconsistence');
}
if($vpl->get_instance()->id != $subinstance->vpl){
	print_error('vpl submission vpl inconsistence');
}
$submission = new mod_vpl_submission($vpl,$subinstance);

if($vpl->get_visiblegrade() || $vpl->has_capability(VPL_GRADE_CAPABILITY)){
	if($submission->is_graded()){
		echo '<h2>'.get_string('grade').'</h2>';
		$submission->print_grade(true);
	}
}
$vpl->print_variation( $subinstance->userid);
$submission->print_submission();
$vpl->print_footer();
}catch(Exception $e){
	print_r($e);
}
?>