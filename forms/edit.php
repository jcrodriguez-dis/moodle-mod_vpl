<?php
/**
 * @version		$Id: edit.php,v 1.9 2013-06-07 15:59:14 juanca Exp $
 * @package		VPL. submission edit
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

global $CFG;
require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../vpl_submission.class.php';
require_once dirname(__FILE__).'/../editor/editor_utility.php';
header("Pragma: no-cache"); //Browser must reload page
vpl_editor_util::generate_requires();
require_login();
$id = required_param('id',PARAM_INT);
$userid = optional_param('userid',FALSE,PARAM_INT);
$copy = optional_param('privatecopy',false,PARAM_INT);
$vpl = new mod_vpl($id);
$page_parms = array('id' => $id);
if($userid && !$copy){
	$page_parms['userid']= $userid;
}
if($copy){
	$page_parms['privatecopy']= 1;
}
$vpl->prepare_page('forms/edit.php', $page_parms);
if(!$vpl->is_visible()){
	notice(get_string('notavailable'));
}
if(!$vpl->is_submit_able()){
	print_error('notavailable');
}
$url_log = vpl_rel_url('forms/edit.php','id',$id);
if($userid){
	$url_log = vpl_url_add_param($url_log,'userid',$userid);
}
if($copy != 0){
	$url_log = vpl_url_add_param($url_log,'privatecopy',1);
}
$vpl->add_to_log('edit submission', $url_log);
if(!$userid || $userid == $USER->id){//Edit own submission
	$userid = $USER->id;
	$vpl->require_capability(VPL_SUBMIT_CAPABILITY);
}
else { //Edit other user submission
	$vpl->require_capability(VPL_MANAGE_CAPABILITY);
}
$vpl->network_check();
$vpl->password_check();

$lastsub = $vpl->last_user_submission($userid);
$instance= $vpl->get_instance();
$manager = $vpl->has_capability(VPL_MANAGE_CAPABILITY);
$grader = $vpl->has_capability(VPL_GRADE_CAPABILITY);
$options = Array();
$options['id']=$id;
$options['restrictededitor']=$instance->restrictededitor && !$grader;
$options['save']=!$instance->example;
$options['run']=($instance->run || $manager);
$options['debug']=($instance->debug || $manager);
$options['evaluate']=($instance->evaluate || $manager);
$options['example']=$instance->example;
$linkuserid = $copy?$USER->id:$userid;
$options['ajaxurl']="edit.json.php?id={$id}&userid={$linkuserid}&action=";
$options['download']="../views/downloadsubmission.php?id={$id}&userid={$linkuserid}";
//Get files
$files = Array();
$req_fgm = $vpl->get_required_fgm();
$options['resetfiles']=($req_fgm->is_populated() && !$instance->example);
$options['maxfiles']=$instance->maxfiles;
$req_filelist =$req_fgm->getFileList();
$min = count($req_filelist);
$options['minfiles']=$min;
$nf = count($req_filelist);
for( $i = 0; $i < $nf; $i++){
	$filename=$req_filelist[$i];
	$filedata=$req_fgm->getFileData($req_filelist[$i]);
	$files[$filename]=$filedata;
}
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
}
session_write_close();
if($copy && $grader){
	$userid=$USER->id;
}
$vpl->print_header(get_string('edit',VPL));
$vpl->print_view_tabs(basename(__FILE__));
echo $OUTPUT->box_start();
vpl_editor_util::print_tag($options,$files,($lastsub && !$copy));
echo $OUTPUT->box_end();
if($lastsub){
    echo vpl_editor_util::send_CE($CE);
}
$vpl->print_footer();
