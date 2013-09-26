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
require_once $CFG->libdir.'/formslib.php';
require_once $CFG->libdir.'/moodlelib.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../vpl_submission.class.php';
require_once dirname(__FILE__).'/../editor/editor_utility.php';
require_once dirname(__FILE__).'/form.class.php';
vpl_include_jsfile('editor.js');
vpl_include_jsfile('hide_footer.js');
$PAGE->requires->string_for_js('changesNotSaved',VPL);
$PAGE->requires->string_for_js('filesChangedNotSaved',VPL);
$PAGE->requires->string_for_js('fileNotChanged',VPL);
$PAGE->requires->string_for_js('filesNotChanged',VPL);
class mod_vpl_editor_form extends vpl_form {
	protected $vpl;
	protected $instance;
	function __construct($page,$vpl){
		$this->vpl = $vpl;
		$this->instance = $vpl->get_instance();
		parent::__construct($page,'mform1');
	}

	function normalize_filelist($requiredfilelist,$filelist){
		return array_values(array_unique(array_merge($requiredfilelist,$filelist)));
	}

	function definition(){
		global $USER,$PAGE,$DB;
		$PAGE->set_pagelayout('popup');
		$this->vpl->require_capability(VPL_SUBMIT_CAPABILITY);
		$id = required_param('id',PARAM_INT);
		$copy = optional_param('copy',0,PARAM_INT);
		$userid = optional_param('userid',null,PARAM_INT);
		$fullscreen = optional_param('fullscreen',0,PARAM_INT);
		$this->addHidden('id',$id);
		if($userid){
			if($copy==0){
				$this->addHidden('userid',$userid);
			}
			$lastsub = $this->vpl->last_user_submission($userid);
		}
		else{
			$userid = $USER->id;
			$lastsub = $this->vpl->last_user_submission($userid);
		}
		$instance = $this->vpl->get_instance();
		$this->addHTML('<div style="text-align: center">');
		$onClickStart = 'onclick="return VPL.action(\'';
		$onClickEnd = ');"';
		if($instance->example){ //Don't save
			$htmlAlertFilesChanged = 'onclick="return true;"';
		}else{
			if($fullscreen){
				if($copy){
					$user = $DB->get_record('user',array('id'=>$USER->id));
				}else{
					$user = $DB->get_record('user',array('id'=>$userid));
				}
				if(isset($user)){
					$this->addHTML($this->vpl->fullname($user).' ');
				}
			}
			$htmlAlertFilesChanged = 'onclick="return VPL.alertFilesChanged();"';
			$strSave = get_string('save',VPL);
			$this->addSubmitButton('save',$strSave,
					$onClickStart.s($strSave).'\',true'.$onClickEnd);
		}
		$manager = $this->vpl->has_capability(VPL_MANAGE_CAPABILITY);
		if($instance->run || $manager){
			$text = get_string('run',VPL);
			if(!$instance->run){
				$text = '('.$text.')';
			}
			$this->addSubmitButton('run',$text,
					$onClickStart.s($text).'\',false'.$onClickEnd);
		}
		if($instance->debug || $manager){
			$text = get_string('debug',VPL);
			if(!$instance->debug){
				$text = '('.$text.')';
			}
			$this->addSubmitButton('debug',$text,
					$onClickStart.s($text).'\',false'.$onClickEnd);
		}
		if($instance->evaluate || $manager){
			$text = get_string('evaluate',VPL);
			if(!$instance->evaluate){
				$text = '('.$text.')';
			}
			$this->addSubmitButton('evaluate',$text,
					$onClickStart.s($text).'\',false'.$onClickEnd);
		}
		$linkBase = vpl_rel_url('edit.php','id', $id);
		if($copy==0){
			$linkBase=vpl_url_add_param($linkBase,'userid',$userid);
		}
		if(optional_param('fullscreen',0,PARAM_INT)){
			$linkScreen = $linkBase;
			$linkSReset = vpl_url_add_param($linkBase,'fullscreen','1');
			$linkSReset = vpl_url_add_param($linkSReset,'resetfiles','1');
			$screenText = get_string('regularscreen',VPL);
		}else{
			$linkScreen = vpl_url_add_param($linkBase,'fullscreen','1');
			$linkSReset = vpl_url_add_param($linkBase,'resetfiles','1');
			$screenText = get_string('fullscreen',VPL);
		}
		$this->addHTML('&nbsp;<a href="'.$linkScreen.'">'.s($screenText).'</a>');
		$req_fgm = $this->vpl->get_required_fgm();
		if($req_fgm->is_populated() && !$instance->example){ //Reset files only if required files have information
			$onclick ='onclick="return confirm(\''.s(get_string('sureresetfiles',VPL)).'\')"';
			$this->addHTML('&nbsp;|&nbsp;<a href="'.$linkSReset.'" '.$onclick.'>'.s(get_string('resetfiles',VPL)).'</a>');
		}
		$this->addHTML('</div>');
		//Generate editor
		$param_tags='';
		$req_filelist =$req_fgm->getFileList();
		if($lastsub && optional_param('resetfiles',0,PARAM_INT)==0){
			$submission = new mod_vpl_submission($this->vpl, $lastsub);
			$fgp =  $submission->get_submitted_fgm();
			$filelist = $this->normalize_filelist($req_filelist,$fgp->getFileList());
			$param_tags .= $submission->get_CE_parms();
		}else{
			$fgp =  $req_fgm;
			$filelist = $fgp->getFileList();
		}
		$max = $this->instance->maxfiles;
		$nf = count($filelist);
		for( $i = 0; $i < $max; $i++){
			$field_filename = 'filename'.$i;
			$field_filedata = 'filedata'.$i;
			if($i< $nf){
				$filename=$filelist[$i];
				$filedata=$fgp->getFileData($filelist[$i]);
				//TODO urlencode only onces
				$this->addHidden($field_filename,$filename);
				$this->addHidden($field_filedata,urlencode($filedata));
				$param_tags .= vpl_param_tag($field_filename,$filename);
				$param_tags .= vpl_param_tag($field_filedata,$filedata);
			}else{
				$this->addHidden($field_filename,'');
				$this->addHidden($field_filedata,'');
			}
		}
		$fontsize=get_user_preferences('vpl_fontsize',12);
		//Sanitize fontsize value
		if($fontsize <10 || $fontsize > 42){
			$fontsize = 12;
		}
		$this->addHidden('fontsize',$fontsize);
		$param_tags .= vpl_param_tag('fontsize',$fontsize,true);
		$req_fgm = $this->vpl->get_required_fgm();
		$req_filelist =$req_fgm->getFileList();
		$min = count($req_filelist);
		$param_tags .= vpl_editor_i18n::get_params_tag();
		$param_tags .= vpl_param_tag('minfiles',$min);
		$param_tags .= vpl_param_tag('maxfiles',$max);
		if($instance->restrictededitor){
			$param_tags .= vpl_param_tag('restrictededit');
		}
		if($instance->example){
			$param_tags .= vpl_param_tag('readonly');
		}
		$this->addHTML(vpl_get_editor_tag($param_tags));
	}
	function display(){
		global $OUTPUT;
		echo $OUTPUT->box_start();
		parent::display();
		echo $OUTPUT->box_end();
	}
}


require_login();

$id = required_param('id',PARAM_INT);
$userid = optional_param('userid',FALSE,PARAM_INT);
$copy = optional_param('copy',0,PARAM_INT);
$vpl = new mod_vpl($id);
$page_parms = array('id' => $id);
if($userid){
	$page_parms['userid']= $userid;
}
if($copy != 0){
	$page_parms['copy']= 1;
}
$vpl->prepare_page('forms/edit.php', $page_parms);
if(!$vpl->is_visible()){
	notice(get_string('notavailable'));
}
if(!$vpl->is_submit_able()){
	print_error('notavailable');
}

if(!$userid || $userid == $USER->id){//Edit own submission
	$userid = $USER->id;
	$vpl->require_capability(VPL_SUBMIT_CAPABILITY);
	$vpl->network_check();
	$vpl->password_check();
}
else { //Edit other user submission
	$vpl->require_capability(VPL_MANAGE_CAPABILITY);
	$vpl->network_check();
	$vpl->password_check();
}
$url_log = vpl_rel_url('forms/edit.php','id',$id);
if($userid){
	$url_log = vpl_url_add_param($url_log,'userid',$userid);
}
if($copy != 0){
	$url_log = vpl_url_add_param($url_log,'copy',1);
}
$vpl->add_to_log('edit submission', $url_log);
$fullscreen=optional_param('fullscreen',0,PARAM_INT);
$iframe='<div id="div_er" style="display: none"></div>';
$mform = new mod_vpl_editor_form('edit_process.php',$vpl);
if($fullscreen){
	$vpl->print_header_simple();
}else{
	$vpl->print_header(get_string('edit',VPL));
	$vpl->print_view_tabs(basename(__FILE__));
}
session_write_close();
$mform->display();
echo $iframe;

$vpl->print_footer_simple();
?>
