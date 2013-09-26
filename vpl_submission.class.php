<?php
/**
 * @version		$Id: vpl_submission.class.php,v 1.116 2013-06-10 11:13:57 juanca Exp $
 * @package		VPL. Submission class definition
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/**
 * Module instance files
 * path= vpl_data//vpl_instance#
 * Submission info
 * path/usersdata/userid#/submissionid#/submittedfiles.lst
 * path/usersdata/userid#/submissionid#/submittedfiles/
 * path/usersdata/userid#/submissionid#/grade_comments.txt
 * path/usersdata/userid#/submissionid#/teachertest.txt
 * path/usersdata/userid#/submissionid#/studenttest.txt
 */
defined('MOODLE_INTERNAL') || die();
require_once dirname(__FILE__).'/vpl.class.php';
require_once dirname(__FILE__).'/views/sh_factory.class.php';
require_once dirname(__FILE__).'/views/show_hide_div.class.php';

//Non static due to usort error
function vpl_compare_filenamebylengh($f1, $f2) {
	return strlen($f2)-strlen($f1);
}

class mod_vpl_submission {
	protected $vpl;
	protected $instance;

	/**
	 * Internal var object to submitted file group manager
	 * @var object of file group manager
	 */
	protected $submitted_fgm;

	/**
	 * Constructor
	 * @param $vpl object vpl instance
	 * @param $mix submission instance object or id
	 **/
	function __construct(mod_vpl $vpl, $mix=false) {
		global $DB;
		$this->vpl = $vpl;
		if(is_object($mix)){
			$this->instance = $mix;
		}elseif($mix === false){
			throw new Execption('vpl_submission id error');
		}
		else{
			$this->instance = $DB->get_record('vpl_submissions', array('id' => $mix));
			if(!$this->instance){
				throw new Execption('vpl_submission id error');
			}
		}
		$this->submitted_fgm = null;
	}

	/**
	 * get submission instance
	 * @return object submission instance
	 **/
	function get_instance(){
		return $this->instance;
	}

	/**
	 * get path to data submission directory
	 * @return string submission data directory
	 **/
	function get_data_directory(){
		return $this->vpl->get_users_data_directory().'/'.
				$this->instance->userid.'/'.$this->instance->id.'/';
	}

	/**
	 * get path to files submission directory
	 * @return string files submission directory
	 **/
	function get_submission_directory(){
		return $this->get_data_directory().'submittedfiles/';
	}

	/**
	 * get absolute path to name of file with list of submitted files
	 * @return string file name
	 **/
	function get_submissionfilelistname(){
		return $this->get_data_directory().'submittedfiles.lst';
	}

	/**
	 * @return object file group manager for submitted files
	 **/
	function get_submitted_fgm(){
		if(!$this->submitted_fgm){
			$this->submitted_fgm = new file_group_process($this->get_submissionfilelistname(), $this->get_submission_directory());
		}
		return $this->submitted_fgm;
	}

	/**
	 * get absolute path to the grade comments file name
	 * @return string file name
	 **/
	function get_gradecommentsfilename(){
		return $this->get_data_directory().'grade_comments.txt';
	}

	/**
	 * get submitted files
	 * @return array of array 'name' and 'data'
	 **/
	function get_submitted_files(){
		//TODO refactor to file_group_process
		$ret = array();
		$filelist = vpl_read_list_from_file($this->get_submissionfilelistname());
		$basedir = $this->get_submission_directory();
		foreach ($filelist as $filename) {
			if($filename > ''){
				$data = file_get_contents($basedir.$filename);
				$ret[] = array('name' => $filename, 'data' => $data);
			}
		}
		return $ret;
	}

	function set_submitted_file($files){
		//TODO refactor to file_group_process
		$filelist=array();
		$basepath = $this->get_submission_directory();
		foreach($files as $file){
			$filename = basename($file['name']);
			if($filename>''){
				$filelist[] = $filename;
				$fp = vpl_fopen($basepath.$filename);
				fwrite($fp,$file['data']);
				fclose($fp);
			}
		}
		vpl_write_list_to_file($this->get_submissionfilelistname(),$filelist);
	}

	function is_equal_to(&$files, $comment = ''){
		$subfiles = $this->get_submitted_files();
		if(count($files) != count($subfiles)) {
			return false;
		}
		for($i=0; $i < count($files); $i++){
			if($files[$i]['name'] != $subfiles[$i]['name']){
				return false;
			}
			if($files[$i]['data'] != $subfiles[$i]['data']){
				return false;
			}
		}
		if($this->instance->comments != $comment){
			return false;
		}
		return true;
	}
	
	/**
	 * Delete submitted files and own record
	 * @return void
	 **/
	function delete(){
		global $DB;
		vpl_delete_dir($this->get_data_directory());
		$DB->delete_records('vpl_submissions', array('id' => $this->instance->id));
	}
	
	/**
	 * Return if submission has been graded
	 * @return bool
	 */
	function is_graded(){
		return $this->instance->dategraded>0;
	}

	/**
	 * Remove grade
	 *@return true if removed and false if not
	 *
	 */
	function remove_grade(){
		global $USER;
		global $CFG;
		global $DB;
		ignore_user_abort (true);
		if (!function_exists('grade_update')) {
			require_once($CFG->libdir.'/gradelib.php');
		}
		if($this->vpl->is_group_activity()){
			$usersid=array();
			foreach($this->vpl->get_usergroup_members($this->instance->userid) as $user){
				$usersid[]=$user->id;
			}
		}else{
			$usersid=array($this->instance->userid);
		}
		$grades= array();
		$gradeinfo= array();
		$gradeinfo['userid'] =$this->instance->userid;
		$gradeinfo['rawgrade'] = null;
		$gradeinfo['feedback'] = '';
		foreach($usersid as $userid){
			$gradeinfo['userid'] =$userid;
			$grades[$userid] = $gradeinfo;
		}
		$vplinstance=$this->vpl->get_instance();
		if($this->vpl->get_grade()==0 || $vplinstance->example !=0){
			$itemdetails = array('deleted'=>1);
		}else{
			$itemdetails = NULL;
		}
		if(grade_update('mod/vpl',$this->vpl->get_course()->id,'mod',VPL,
			$this->vpl->get_instance()->id,0,$grades,$itemdetails) != GRADE_UPDATE_OK){
			return false;
		}
		if(!empty($CFG->enableoutcomes)){
			foreach($usersid as $userid){
				$grading_info = grade_get_grades($this->vpl->get_course()->id, 'mod', 'vpl',
				$this->vpl->get_instance()->id, $userid);
				if (!empty($grading_info->outcomes)) {
					$outcomes = array();
					foreach($grading_info->outcomes as $oid=>$dummy) {
						$outcomes[$oid]=null;
					}
					grade_update_outcomes('mod/vpl',$this->vpl->get_course()->id,'mod',VPL,
						$this->vpl->get_instance()->id,$userid,$outcomes);
				}
			}
		}
		$this->instance->grader=0;
		$this->instance->dategraded=0;
		$this->instance->grade=null;
		$fn = $this->get_gradecommentsfilename();
		if(!$DB->update_record('vpl_submissions',$this->instance)){
			print_error('DB error updating submission grade info');
		}else{
			if(file_exists($fn)){
				unlink($fn);
			}			
		}
		return true;
	}
	
	/**
	 * Set/update grade
	 * @param $info object with grade and comments fields
	 * @param $automatic if automatic grading (default false)
	 * @return void
	 */
	function set_grade($info, $automatic=false){
		global $USER;
		global $CFG;
		global $DB;
		ignore_user_abort (true);
		$scaleid = $this->vpl->get_grade();
		if($scaleid == 0 &&
		   empty($CFG->enableoutcomes)){ //No scale no outcomes
		   	return;
		}
		if (!function_exists('grade_update')) {
			require_once($CFG->libdir.'/gradelib.php');
		}
		if($automatic){ //Who grade
			$this->instance->grader=0;
		}else{
			$this->instance->grader=$USER->id;
		}
		if($this->vpl->is_group_activity()){
			$usersid=array();
			foreach($this->vpl->get_usergroup_members($this->instance->userid) as $user){
				$usersid[]=$user->id;
			}
		}else{
			$usersid=array($this->instance->userid);
		}
		$this->instance->dategraded=time();
		if($scaleid!=0){
			//Sanitize grade
			if($scaleid>0){
			    $info->grade = (float) $info->grade;
			}
			else{
				$info->grade = (int) $info->grade;
			}
			$this->instance->grade=$info->grade;
			//Save assessment comments
			$comments=$info->comments;
			$fn = $this->get_gradecommentsfilename();
			if($comments){
				$fp = vpl_fopen($fn);
				fwrite($fp,$comments);
				fclose($fp);
			} elseif(file_exists($fn)){
				unlink($fn);
			}
			//update gradebook
			$grades= array();
			$gradeinfo= array();
			//If no grade then don't set rawgrade and feedback
			if(!($info->grade == -1 && $scaleid <0 )){
				$gradeinfo['rawgrade'] = $info->grade;	
				$gradeinfo['feedback'] = $this->result_to_HTML($comments,false);
				$gradeinfo['feedbackformat'] = FORMAT_HTML;
			}
			if($this->instance->grader>0){ //Don't add grader if automatic
				$gradeinfo['usermodified'] = $this->instance->grader;
			}else{//This avoid to use an unexisting userid (0) in the gradebook
				$gradeinfo['usermodified'] = $USER->id;
			}
			$gradeinfo['datesubmitted'] = $this->instance->datesubmitted;
			$gradeinfo['dategraded'] = $this->instance->dategraded;
			foreach($usersid as $userid){
				$gradeinfo['userid']=$userid;
				$grades[$userid] = $gradeinfo;
			}
			if(grade_update('mod/vpl',$this->vpl->get_course()->id,'mod',VPL,
				$this->vpl->get_instance()->id,0,$grades) != GRADE_UPDATE_OK){
				return false;
			}
		}
		if(!empty($CFG->enableoutcomes)){
			foreach($usersid as $userid){
				$grading_info = grade_get_grades($this->vpl->get_course()->id, 'mod', 'vpl',
				$this->vpl->get_instance()->id, $userid);
				if (!empty($grading_info->outcomes)) {
					$outcomes = array();
					foreach($grading_info->outcomes as $oid=>$dummy) {
						$field='outcome_grade_'.$oid;
						if(isset($info->$field)){
							$outcomes[$oid]=$info->$field;
						}else{
							$outcomes[$oid]=null;
						}
					}
					grade_update_outcomes('mod/vpl',$this->vpl->get_course()->id,'mod',VPL,
						$this->vpl->get_instance()->id,$userid,$outcomes);
				}
			}
		}
		if(!$DB->update_record('vpl_submissions',$this->instance)){
			print_error('DB error updating submission grade info');
		}
		return true;
	}

	/**
	 * Get grade comments
	 *
	 * @return string
	 */
	function get_grade_comments(){
		$fn = $this->get_gradecommentsfilename();
		if(file_exists($fn)){
			return file_get_contents($fn);
		}
		else{
			return '';
		}
	}

	/**
	 * is visible this submission instance
	 * @return bool
	 */
	function is_visible(){
		global $USER;
		$cm = $this->vpl->get_course_module();
		$instance = $this->instance;
		$ret = $this->vpl->is_visible();
		//submission owner?
		$ret = $ret && ($USER->id == $instance->userid);
		if($ret){
			//is last submission?
			$lastsub = $this->vpl->last_user_submission($instance->userid);
			$ret = $ret && ($instance->id == $lastsub->id);
		}
		$ret = $ret || $this->vpl->has_capability(VPL_GRADE_CAPABILITY);
		return $ret;
	}

	/**
	 * is possible to grade/update this submission instance
	 * @return bool
	 */
	function is_grade_able(){
		global $USER;
		if($this->vpl->get_grade()==0){//Is grade_able the instance
			return false;
		}
		$instance = $this->instance;
		$ret = $this->vpl->has_capability(VPL_GRADE_CAPABILITY);;
		//new grade or update if grader
		$ret = $ret && ($instance->dategraded || $USER->id == $instance->grader);
		$ret = $ret || $this->vpl->has_capability(VPL_MANAGE_CAPABILITY);
		if($ret){
			//is last submission?
			$lastsub = $this->vpl->last_user_submission($instance->userid);
			$ret = $ret && ($instance->id == $lastsub->id);
		}
		return $ret;
	}
	
	/**
	 * @var array of users(graders) objects
	 */
	static $graders=array();
	
	/**
	 * Return user from DB with cache (automatic grader info for $id===0)
	 * @param $id
	 * @return FALSE/user object
	 */
	static function get_grader($id = null){
		global $DB;
		if($id ===null){
			$id= $this->instance->grader;
		}
		if($id === null){
			$id=0;
		}
		if(isset(self::$graders[$id])){
    		$graderuser = self::$graders[$id];
    	}else{
    		if($id <= 0){ //Automatic grading
    			$graderuser = new StdClass();
    			$graderuser->firstname = '';
    			$graderuser->lastname = get_string('automaticgrading',VPL);
    		}else{
    			$graderuser = $DB->get_record('user', array('id' => $id));
    		}
    		self::$graders[$id] = $graderuser;
    	}
    	return $graderuser;
	}

	/**
	 * Print core grade
	 * @parm optional grade to show
	 * @return string
	 */
	function print_grade_core($grade=null){
		$ret = '';
		$inst=$this->instance;
	    if($inst->dategraded>0 || $grade != null){
			$vplinstance = $this->vpl->get_instance();
			$scaleid = $this->vpl->get_grade();
			$options = array();
			if($scaleid == 0){
				$ret = get_string('nograde');
			}
			if ($scaleid > 0) {
				if($grade == null){
					//remove trailing zeros if needed
					$grade=vpl_rtzeros($inst->grade);
				}
				$ret = $grade.' / '.$scaleid;
			} elseif($scaleid < 0) {
				$scaleid = -$scaleid;
				if($grade === null){
					$grade=trim($inst->grade);
				}
				$grade = (int)$grade;
				if ($scale = $this->vpl->get_scale()) {
					$options=array();
					$options[-1]= get_string('nograde');
					$options = $options + make_menu_from_list($scale->scale);
					if(isset($options[$grade])){
						$ret = $options[$grade];
					}
				}
			}
	    }
    	return $ret;
	}
	
	/**
	 * Print sudmission grade
	 * @param $detailed true show detailed grade (default false)
	 * @param $return true return string/ false print grade (default false)
	 * @return mix string/void
	 */
	function print_grade($detailed=false,$return=false){
		global $CFG,$OUTPUT;
		$ret = '';
		$inst=$this->instance;
	    if($inst->dategraded>0){
	    	$grader = $this->get_grader($inst->grader);
	    	$a = new stdClass();
	    	$a->date=userdate($inst->dategraded);
	    	$a->gradername=fullname($grader);
	    	$ret .= get_string('gradedonby',VPL,$a).'<br />';
	    	if($this->vpl->get_grade()!=0){
		    	$ret .= '<b>'.get_string('grade').'</b> '.$this->print_grade_core().'<br />';
		    	if($detailed){
		    		$feedback = $this->get_grade_comments();
		    		if($feedback){
		    			$ret .= '<b>'.get_string('gradercomments',VPL).'</b><br />';
		    			$ret .= $this->result_to_HTML($feedback,true);
		    		}
		    	}
	    	}
	    	if(!empty($CFG->enableoutcomes)){
	    		//Bypass unknow gradelib not load
	    		if (!function_exists('grade_get_grades')) {
					require_once($CFG->libdir.'/gradelib.php');
				}
				$grading_info = grade_get_grades($this->vpl->get_course()->id, 'mod', 'vpl',
				$this->vpl->get_instance()->id, $this->instance->userid);
				if (!empty($grading_info->outcomes)) {
					$ret .= '<b>'.get_string('outcomes','grades').'</b><br />';
					foreach($grading_info->outcomes as $oid=>$outcome) {
						$ret .= s($outcome->name);
						$ret .= ' '.s($outcome->grades[$inst->userid]->str_grade).'<br />';
					}
				}
			}
	    	
	    }
	    if($return){
	    	return $ret;
	    }else{
	    	if($ret){
	    		echo $OUTPUT->box($ret);
	    	}
	    }
	}
	
	/**
	 * Print sudmission info
	 *
	 */
	function print_info($autolink=false){
		//TODO improve show submission info
		global $OUTPUT;
		$id=$this->vpl->get_course_module()->id;
		$userid=$this->instance->userid;
		$submissionid=$this->instance->id;
		if($autolink){
			$url=vpl_mod_href('forms/submissionview.php','id',$id,
						'userid',$userid,'submissionid',$submissionid);
			echo '<a href="'.$url.'">';		
		}
		$subdate=userdate($this->instance->datesubmitted);
	    p(get_string('submittedonp',VPL,$subdate));
		if($autolink){
			echo '</a>';	
		}
		$url=vpl_mod_href('views/downloadsubmission.php','id',$id,
						'userid',$userid,'submissionid',$submissionid);
		echo ' (<a href="'.$url.'">'.get_string('download',VPL);		
		echo '</a>)';
		//Show evaluation link
		$ce =$this->getCE();
		if($this->vpl->get_instance()->evaluate
			&& $ce['compilation'] !== 0
			&& !$this->is_graded()){
			$url=vpl_mod_href('forms/evaluation.php','id',$id,
							'userid',$userid);
			echo ' (<a href="'.$url.'">'.get_string('evaluate',VPL);		
			echo '</a>)';
		}
		echo '<br />';
		$commmets = $this->instance->comments;
		if($commmets>''){
			echo '<br />';
			echo get_string('comments',VPL);
			echo $OUTPUT->box($commmets);
		}
	}

	/**
	 * Print compilation and execution
	 * @return void
	 */
	function print_CE(){
		global $OUTPUT;
		$ce = $this->getCE();
		if($ce['compilation'] === 0){
			return;
		}
		$this->get_CE_HTML($ce,$compilation,$execution,$grade,true,true);
		if(strlen($compilation)+strlen($execution)+strlen($grade) >0){
			$div = new vpl_hide_show_div(!$this->is_graded() || !$this->vpl->get_visiblegrade());
			echo '<h3>'.get_string('automaticevaluation',VPL).$div->generate(true).'</h3>';
			$div->begin_div();
			echo $OUTPUT->box_start();
			if(strlen($grade) >0){
				echo '<b>'.$grade.'</b><br />';
			}
			if(strlen($execution)>0){
				echo $execution;
			}
			if(strlen($compilation) >0){
				echo $compilation;
			}
			echo $OUTPUT->box_end();
			$div->end_div();
		}
	}

	/**
	 * Print sudmission
	 */
	function print_submission(){
		$this->print_info();
		//Not automatic graded show proposed evaluation
		if(!$this->is_graded() ||
		   !$this->vpl->get_visiblegrade() ||
		    $this->vpl->has_capability(VPL_GRADE_CAPABILITY)){
			$this->print_CE();
		}
		$this->get_submitted_fgm()->print_files();
	}

	const gradeTag='Grade :=>>';
	const commentTag='Comment :=>>';
	const beginCommentTag='<|--';
	const endCommentTag='--|>';

	function proposedGrade($text) {
		$ret = '';
		$nl = vpl_detect_newline($text);
		$lines = explode($nl,$text);
		foreach($lines as $line){
			if(strpos($line,self::gradeTag) === 0){
				$ret = trim(substr($line,strlen(self::gradeTag)));
			}
		}
		return $ret;
	}

	function proposedComment($text){
		$inComment=false;
		$ret = '';
		$nl = vpl_detect_newline($text);
		$lines = explode($nl,$text);
		foreach($lines as $line){
			$line = rtrim($line); //Remove \r, spaces & tabs
			$tline = trim($line);
			if($inComment) {
				if($tline == self::endCommentTag){
					$inComment=false;
				}else{
					$ret .= $line."\n";
				}
			}else{
				if(strpos($line,self::commentTag) === 0){
					$ret .= substr($line,strlen(self::commentTag))."\n";
				} elseif($tline == self::beginCommentTag){
					$inComment=true;
				}
			}
		}
		return $ret;
	}
	
	/**
	 * Add link to file line format filename:linenumber:
	 * @param text to be converted
	 * @return string text with links
	 */
	function add_filelink($text){
		//Format filename:linenumber
		$ret='';
		$list = $this->get_submitted_fgm()->getFileList();
		usort($list,'vpl_compare_filenamebylengh');
		$nl = vpl_detect_newline($text);
		$lines = explode($nl,$text);
		//Prepare reg expressions
		$regexps = array();
		foreach ($list as $filename){
			$escapefilename=preg_quote($filename);
			$regexps[] = '/(.*?)('.$escapefilename.')\:( *)([0-9]+)(.*)/';
		}
		//process lines
		foreach($lines as $line){
			foreach ($regexps as $regexp){
				if(preg_match($regexp,$line,$r)){
					$line = $r[1].'<a href="#'.$r[2].'.'.$r[4].'">'.$r[2].':'.$r[3].$r[4].'</a>'.$r[5];
					break;
				}
			}
			$ret .= $line."\n";
		}
		return $ret;
	}
	/**
	 * Convert compilation/execution result to HTML
	 * @param text to be converted
	 * @return string HTML
	 */
	private function get_last_comment($title,&$comment,$dropdown){
		$html = '';
		if($title>''){ //previous comment
			if($comment=='' || !$dropdown){
				$html .= '<b>';
				$html .= s($title);
				$html .= '</b><br />';
				$html .= $comment;
			}else{
				$div=new vpl_hide_show_div(false);
				$html .= $div->generate(true);
				$html .= '<b>';
				$html .= s($title);
				$html .= '</b><br />';
				$html .= $div->begin_div(true).$comment.$div->end_div(true);
			}
		}else if($comment>''){ //No title comment
			$html .= $comment;
		}
		$comment =''; //Reset comment
		return $html;
	}
	/**
	 * Convert compilation/execution result to HTML
	 * @param text to be converted
	 * @return string HTML
	 */
	function result_to_HTML($text,$dropdown=true){
		if($text == '' || $text==null){
			return '';
		}
		$html=''; //Total html output
		$title = ''; //Title of comment
		$comment = ''; //Comment
		$nl = vpl_detect_newline($text);
		$lines = explode($nl,$text);
		$caseToShow=''; //Pre to show
		foreach($lines as $line){
			$clean=trim($line);
			//End of case?
			if(strlen($caseToShow)>0 && !(strlen($clean)>0 && $clean[0]=='>')){
				$comment .='<pre><i>';
				$comment .= s($caseToShow);
				$comment .= '</i></pre>';
				$caseToShow ='';
			}
			//Is title line
			if(strlen($line)>2 && $line[0]=='-') { //Title
				$html .= $this->get_last_comment($title, $comment,$dropdown);
				$line = substr($line,1);
				if($line[strlen($line)-1]==')') { //Has grade?
					$posOpen = strrpos($line,'(');
					if($posOpen !== false) {
						$grade = substr($line,$posOpen+1,strlen($line)-$posOpen-2);
						$grade = trim($grade);
						if($grade < 0){
							$title = substr($line,0,$posOpen);
							//TODO implement grader information
							continue;
						}
					}
				}
				$title = $line;
			}elseif(strlen($clean)>0 && $clean[0]=='>') { //Case
				$pos=strpos($line,'>');
				$rest=substr($line,$pos+1);
				//TODO CODE WORK AAROUND FOR PROBLEM IN 1.9 RESOLVED in 2.0
				if($rest != '0'){ //s output 0 is string empty or "0.0"
					$caseToShow .= $rest."\n";
				}else{
					$caseToShow .= $rest."\n";
				}
			} elseif(strlen($clean)>8 && 
			         (substr($clean,0,5)=="http:" || substr($clean,0,6)=="https:")) {
				//Is url
				//output spaces
				$nspaces=strpos($line,'h');
				for($i=0; $i<$nspaces; $i++){
					$comment .= '&nbsp;';
				}
				$spacepos = strpos($clean,' ');
				if($spacepos){
					$comment .= '<a href="';
					$comment .= urlencode(substr($clean,0,$spacepos));
					$comment .= '">';
					$comment .= s(substr($clean,$spacepos+1,strlen($clean)-$spacepos-1));
					$comment .= '</a>';
				}else{
					$comment .= '<a href="';
					$comment .= urlencode($clean);
					$comment .= '">';
					$comment .= s($clean);
					$comment .= '</a>';
				}
				$comment .= '<br />';
			}else{ //Regular text
				$comment .= $this->add_filelink(s($line)).'<br />';
			}
		}
		if(strlen($caseToShow)>0){
			$html .='<pre><i>';
			$html .= s($caseToShow);
			$html .= '</i></pre>';
		}
		$html .= $this->get_last_comment($title, $comment,$dropdown);
		return $html;
	}
	/**
	 * Add a new text to the list
	 */
	function  filter_feedback_add(&$list,$text,$grade=0){
		$text=trim($text);
		if(!isset($list[$text])){
			$list[$text]=new StdClass();
			$list[$text]->count=0;
			$list[$text]->grades=array();
		}
		$list[$text]->count++;
		$list[$text]->grades[$grade]=true;
	}
	/**
	 * Filter Convert compilation/execution result to HTML
	 * @param text to be filter
	 * @return array of mensajes
	 */
	function  filter_feedback(&$list){
		$text=$this->get_grade_comments();
		$nl = vpl_detect_newline($text);
		$lines = explode($nl,$text);
		foreach($lines as $line){
			$line = rtrim($line);
			//Is title line
			if(strlen($line)>2 && $line[0]=='-') { //Title
				$line = substr($line,1);
				if($line[strlen($line)-1]==')') { //Has grade?
					$posOpen = strrpos($line,'(');
					if($posOpen !== false) {
						$grade = substr($line,$posOpen+1,strlen($line)-$posOpen-2);
						$grade = trim($grade);
						if($grade < 0){
							$this->filter_feedback_add($list,substr($line,0,$posOpen),$grade);
							continue;
						}
					}
				}
				$this->filter_feedback_add($list,$line,0);
			}
		}
	}


	const compilationFN='compilation.txt';
	const executionFN='execution.txt';
	/**
	 * Save Compilation Execution result to files
	 * @param $result array response from server
	 * @return uvoid
	 */
	function saveCE($result){
		ignore_user_abort (true);
		$compfn = $this->get_data_directory().'/'.self::compilationFN;
		if(file_exists($compfn)){
			unlink($compfn);
		}
		$execfn = $this->get_data_directory().'/'.self::executionFN;
		if(file_exists($execfn)){
			unlink($execfn);
		}
		file_put_contents($compfn,$result['compilation']);
		if($result['executed']>0){
			file_put_contents($execfn,$result['execution']);
		}
	}

	/**
	 * Get Compilation Execution information from files
	 * @return array with server response fields
	 */
	function getCE(){
		$ret = array();
		$compfn = $this->get_data_directory().'/'.self::compilationFN;
		if(file_exists($compfn)){
			$ret['compilation']=file_get_contents($compfn);
		}else{
			$ret['compilation']=0;
		}
		$execfn = $this->get_data_directory().'/'.self::executionFN;
		if(file_exists($execfn)){
			$ret['executed']=1;
			$ret['execution']=file_get_contents($execfn);
		}else{
			$ret['executed']=0;
		}
		return $ret;
	}

	/**
	 * Get compilation, execution and proposed grade from array 
	 * @param $response array response from server
	 * @param $compilation 
	 * @param $execution
	 * @param $grade
	 * @return void
	 */
	function get_CE_HTML($response,&$compilation,&$execution,&$grade, $dropdown, $returnrawexecution=false){
		$compilation='';
		$execution='';
		$grade='';
		if($response['compilation']){
			$compilation=$this->result_to_HTML($response['compilation'],$dropdown);
			if(strlen($compilation)){
				$compilation ='<b>'.get_string('compilation',VPL).'</b><br />'.$compilation;
			}
		}
		if($response['executed']>0){
			$raw_execution = $response['execution'];
			$proposed_comments = $this->proposedComment($raw_execution);
			$proposed_grade = $this->proposedGrade($raw_execution);
			$execution=$this->result_to_HTML($proposed_comments,$dropdown);
			if(strlen($execution)){
				$execution = '<b>'.get_string('comments',VPL)."</b><br />\n".$execution;				
			}
			if(strlen($proposed_grade)){
				$sgrade = $this->print_grade_core($proposed_grade);
				$grade =get_string('proposedgrade',VPL,$sgrade);				
			}
			//Show raw ejecution if no grade or comments
			if(strlen($raw_execution)>0 &&
			 	(strlen($execution)+strlen($proposed_grade)==0) ){
					$execution .="<br />\n";
					$execution .='<b>'.get_string('execution',VPL)."</b><br />\n";
					$execution .= '<pre>'.s($raw_execution).'</pre>';
			} //Show raw ejecution if manager and $returnrawexecution
			elseif($returnrawexecution && strlen($raw_execution)>0 &&
			 	($this->vpl->has_capability(VPL_MANAGE_CAPABILITY))){
			 		$div = new vpl_hide_show_div();
					$execution .="<br />\n";
					$execution .='<b>'.get_string('execution',VPL).$div->generate(true)."</b><br />\n";
					$execution .=$div->begin_div(true);
					$execution .= '<pre>'.s($raw_execution).'</pre>';
					$execution .=$div->end_div(true);
			 }
		}
	}

	/**
	 * Send Compilation execution result to applet with transferResult JS function
	 * @param $response array with result from server
	 * @param $window DOM object where the applet is
	 * @param $defer true/false defer JScript execution 
	 * @return void
	 */
	function send_CE_to_editor($response,$window='window.parent'){
		$this->get_CE_HTML($response,$compilation,$execution,$grade,false);
		echo '<div style="display:none">';
		if(strlen($compilation)){
			echo '<div id="compilation">'.URLencode($compilation).'</div>';
			echo "\n";
		}
		if(strlen($execution)){
			echo '<div id="execution">'.URLencode($execution).'</div>';
			echo "\n";
		}
		if(strlen($grade)){
			echo '<div id="grade">'.URLencode($grade).'</div>';
			echo "\n";
		}
		echo '</div>';
		echo "\n";
		echo vpl_include_js('VPL.transferResult('.$window.');');
		echo "\n";
		@ob_flush();
		flush();
	}
	
	function get_detail(){
		$ret='';
		$subf = $this->get_submitted_fgm();
		$filelist = $subf->getFileList();
		foreach($filelist as $filename){
			$data = $subf->getFileData($filename);
			if($ret>''){
				$ret.=', ';
			}
			//TODO too slow calculus
			$nl = vpl_detect_newline($data);
			$ret .= $filename.' '.strlen($data).'b '.count(explode($nl,$data)).'l';
		}
		return $ret;
	}
	
	function get_CE_parms(){
		$response = $this->getCE();
		$this->get_CE_HTML($response,$compilation,$execution,$grade,false);
		$params = '';
		if(strlen($compilation)){
			$params .= vpl_param_tag('compilation',$compilation);
		}
		if(strlen($execution)){
			$params .= vpl_param_tag('evaluation',$execution);
		}
		if(strlen($grade)){
			$params .= vpl_param_tag('grade',$grade);
		}
		return $params;
	}
}
?>