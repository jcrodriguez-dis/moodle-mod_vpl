<?php
/**
 * @version		$Id: filegroup_form.php,v 1.46 2013-06-10 08:11:31 juanca Exp $
 * @package		VPL. Form to edit a group of files
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once $CFG->libdir.'/formslib.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../filegroup.class.php';
require_once dirname(__FILE__).'/../editor/editor_utility.php';

class mod_vpl_filegroup_form extends moodleform {
	protected $fgp;
	protected $page;
	protected $title;
	protected $savenextonly;
	function __construct($page,$fgp, $title,$savenextonly=false){
		$this->fgp = $fgp;
		$this->page = $page;
		$this->title = $title;
		$this->savenextonly = $savenextonly;
		parent::__construct($page);
	}
	function definition(){
		global $PAGE;
		$mform    =& $this->_form;
		$filelist = $this->fgp->getFileList();
		//TODO Make ready for no javascript
		//Select form type
		$edit = optional_param('edit',-1,PARAM_INT);
		if($edit >=0 && $edit < count($filelist)){ //Editing file
			vpl_include_jsfile('editor.js');
			vpl_include_jsfile('hide_footer.js');
			$PAGE->requires->string_for_js('changesNotSaved',VPL);
			$PAGE->requires->string_for_js('filesChangedNotSaved',VPL);
			$PAGE->requires->string_for_js('fileNotChanged',VPL);
			$PAGE->requires->string_for_js('filesNotChanged',VPL);
			$mform->addElement('hidden','id');
			$mform->setType('id', PARAM_INT);
			$mform->setDefault('id',required_param('id',PARAM_INT));
			$mform->addElement('hidden','edit');
			$mform->setType('edit', PARAM_INT);
			$mform->setDefault('edit',required_param('edit',PARAM_INT));
			$htmlReadFile = 'onclick="return VPL.readFile();"';
			$htmlCheck = 'onclick="return VPL.alertFilesChanged();"';
			$buttongroup = array();
			if($this->savenextonly){
				$buttongroup[] = $mform->createElement('submit','savecontinue',get_string('save',VPL),$htmlReadFile);
			}else{
				$buttongroup[] = $mform->createElement('submit','save',get_string('save',VPL),$htmlReadFile);
				$buttongroup[] = $mform->createElement('submit','savecontinue',get_string('savecontinue',VPL),$htmlReadFile);
				$buttongroup[] = $mform->createElement('submit','cancel',get_string('cancel'),$htmlCheck);
			}
			$mform->addGroup($buttongroup);
			$fontsize=get_user_preferences('vpl_fontsize',12);
			$mform->addElement('hidden','fontsize');
			$mform->setType('fontsize', PARAM_INT);
			$mform->setDefault('fontsize',$fontsize);
			$mform->addElement('hidden','filename');
			$mform->setType('filename', PARAM_CLEANFILE);
			$mform->setDefault('filename',$filelist[$edit]);
			$mform->addElement('hidden','filedata');
			$mform->setType('filedata', PARAM_RAW);
			$mform->setDefault('filedata',$this->fgp->getFileData($edit));
		}
		else{ //File group editing
			$mform->addElement('header', 'header_filegroup', $this->title);
			$mform->addElement('hidden','id');
			$mform->setType('id', PARAM_INT);
			$mform->setDefault('id',required_param('id',PARAM_INT));
			$i = 0;
			foreach($filelist as $filename){
				$group = array();
				$ename = 'filename'.$i;
				$isstatic = $i< $this->fgp->get_numstaticfiles();
				$attributes = array();
				if($isstatic){
					$attributes['disabled'] = true;
				}
				$group[] = & $mform->createElement('text',$ename,'',$attributes);
				$group[] = & $mform->createElement('submit','edit'.$i,get_string('edit'));
				if(!$isstatic){
					$menssage = addslashes(get_string('delete_file_fq',VPL,$filename));
					$onclick ='onclick="return confirm(\''.$menssage.'\')"';
					$group[] = & $mform->createElement('submit','rename'.$i,get_string('rename'));
					$group[] = & $mform->createElement('submit','delete'.$i,get_string('delete'),$onclick);
				}
				$mform->addGroup($group,null, $this->fgp->getFileComment($i));
				$mform->setType($ename,PARAM_FILE);
				$mform->setDefault($ename,$filename);
				$i++;
			}
			if($this->fgp->get_maxnumfiles()> count($filelist)){
				$gnewfile = array();
				$gnewfile[] = & $mform->createElement('text','newfilename');
				$gnewfile[] = & $mform->createElement('submit','addfile',get_string('addfile',VPL));
				$mform->addGroup($gnewfile,null,get_string('addfile',VPL));
				$mform->setType('newfilename', PARAM_CLEANFILE);
			}
			//Error if fileupload in a addGroup element
			$mform->addElement('filepicker','file',get_string('uploadfile',VPL));
			$mform->addElement('submit','uploadfile',get_string('uploadfile',VPL));
			$mform->addElement('submit','download',get_string('download',VPL));
		}
	}
	function display(){
		parent::display();
		$edit = optional_param('edit',-1,PARAM_INT);
		$filelist = $this->fgp->getFileList();
		if($edit >= 0 && $edit < count($filelist)){ //Editing file
			//Generate editor
			$param_tags = vpl_param_tag('filename0',$filelist[$edit]);
			$param_tags .= vpl_param_tag('filedata0',$this->fgp->getFileData($edit));
			$param_tags .= vpl_editor_i18n::get_params_tag();
			$param_tags .= vpl_param_tag('minfiles','1');
			$param_tags .= vpl_param_tag('maxfiles','1');
			$fontsize=get_user_preferences('vpl_fontsize',12);
			$param_tags .= vpl_param_tag('fontsize',$fontsize,true);
			echo vpl_get_editor_tag($param_tags);
		}else{
			$this->fgp->print_files();
		}
	}
	public function preheader_process($text=''){
		$fromform=$this->get_data();
		if(isset($fromform->download)){
			if($text>''){
				$text =' '.$text;
			}
			$this->fgp->download_files($this->title.$text);
		}
	}
	public function process(){
		$link=$this->page.'?id='.required_param('id',PARAM_INT);
		$fromform=$this->get_data();
		$mform    =& $this->_form;
		$filelist = $this->fgp->getFileList();
		$l = count($filelist);
		for($i =0 ; $i < $l ; $i++){
			$filename = 'filename'.$i;
			$rename = 'rename'.$i;
			$delete = 'delete'.$i;
			$confirmdelete = 'confirmdelete'.$i;
			$edit = 'edit'.$i;
			if(isset($fromform->$rename)){
				$name = basename($fromform->$filename);
				if($this->fgp->renameFile($i,$name)){
					$names = new stdClass();
					$names->from = $filelist[$i];
					$names->to = $name;
					vpl_redirect($link,get_string('filerenamed',VPL,$names));
				}else{
					error(get_string('filenotrenamed',VPL,$filelist[$i]),$link);
				}
				return;
			}
			if(isset($fromform->$delete)){
				if($this->fgp->deleteFile($i)){
					vpl_redirect($link,get_string('filedeleted',VPL,$filelist[$i]));
				}else{
					error(get_string('filenotdeleted',VPL,$filelist[$i]),$link);
				}
				return;
			}
			if(isset($fromform->$edit)){
				vpl_inmediate_redirect($this->page.'?id='.required_param('id',PARAM_INT).'&edit='.$i);
				return;
			}
		}
		if(isset($fromform->addfile)){
			if(isset($fromform->newfilename) && $fromform->newfilename>''){
				$name = basename($fromform->newfilename);
				if($this->fgp->addFile($name)){
					vpl_redirect($link,get_string('fileadded',VPL,$name));
				}else{
					error(get_string('filenotadded',VPL),$link);
				}
				return;
			}
		}
		if(isset($fromform->uploadfile)){
			$name = $this->get_new_filename('file');
			$data = $this->get_file_content('file');
			if($data !== false && $name !== false ){
				$name = basename($name);
				if(!($name>'')){
					error(get_string('incorrect_file_name',VPL),$link);
				}
				//autodetect data file encode
				$encode = mb_detect_encoding($data, 'UNICODE, UTF-16, UTF-8, ISO-8859-1',true);
				if($encode > ''){ //If code detected
					$data = iconv($encode,'UTF-8',$data);
				}		
				if($this->fgp->addFile($name, $data)){
					if(array_search($name,$filelist)){
						$text=get_string('fileupdated',VPL,$name);
					}else{
						$text=get_string('fileadded',VPL,$name);
					}
					vpl_redirect($link,$text);
				}else{
					vpl_redirect($link,get_string('filenotadded',VPL));
				}
			}
			else{
				vpl_redirect($link,get_string('filenotadded',VPL));
			}
			return;
		}
		if(isset($fromform->save) || isset($fromform->savecontinue)){
			if($fontsize=optional_param('fontsize',FALSE,PARAM_INT)){
				set_user_preference('vpl_fontsize', $fontsize);
			}
			$num = required_param('edit',PARAM_INT);
			$filename = basename($fromform->filename);
			$data = urldecode($fromform->filedata);
			$this->fgp->addFile($filename,$data);
			if(isset($fromform->savecontinue)){
				vpl_redirect($link.'&amp;edit='.$num,get_string('savedfile',VPL,$filename));
			}else{
				vpl_redirect($link,get_string('savedfile',VPL,$filename));
			}
		}
		if(isset($fromform->cancel)){
			vpl_inmediate_redirect($link);
		}
		return;
	}
}


?>