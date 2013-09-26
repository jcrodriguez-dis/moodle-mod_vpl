<?php
/**
 * Provides support for backup VPL antivities in the moodle2 backup format
 *
 * @version		$Id: backup_vpl_stepslib.php,v 1.3 2013-07-09 13:42:25 juanca Exp $
 * @package		VPL
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();
require_once dirname(__FILE__).'/../../vpl.class.php';

class backup_nested_filegroup extends backup_nested_element{
	private function get_files($base,$dirname){
		$files = array();
		$filelst =$dirname.'.lst';
		$extrafiles=array($filelst,$filelst.'.keep',
						'compilation.txt','execution.txt','grade_comments.txt');
		foreach($extrafiles as $file){
			if(file_exists($base.$file)){
				$data = new stdClass();
				$data->name =$file;
				$data->content = file_get_contents($base.$file);
				$files[] = $data;
			}
		}
		$dirpath = $base.$dirname;
		if(file_exists($dirpath)){
			$dirlst = opendir($dirpath);
			while (false !== ($filename=readdir($dirlst))) {
				if ($filename=="." || $filename=="..") {
					continue;
				}
				$data = new stdClass();
				$data->name =$dirname.'/'.$filename;
				$data->content = file_get_contents($dirpath.'/'.$filename);
				$files[] = $data;
			}
			closedir($dirlst);
		}
		return new backup_array_iterator($files);
	}
	
	protected function get_iterator($processor) {
		global $CFG;
		
		$files = array();
		switch($this->get_name()){
			case 'required_file':
				$vplid=$this->find_first_parent_by_name('id')->get_value();
				$path= $CFG->dataroot.'/vpl_data/'.$vplid.'/';
				return $this->get_files($path,'required_files');
			case 'execution_file':
				$vplid=$this->find_first_parent_by_name('id')->get_value();
				$path= $CFG->dataroot.'/vpl_data/'.$vplid.'/';
				return $this->get_files($path,'execution_files');
			break;
			case 'submission_file':
				$vplid=$this->find_first_parent_by_name('vpl')->get_value();
				$subid=$this->find_first_parent_by_name('id')->get_value();
				$userid=$this->find_first_parent_by_name('userid')->get_value();
				$path= $CFG->dataroot.'/vpl_data/'.$vplid.'/usersdata/'.$userid.'/'.$subid.'/';
				return $this->get_files($path,'submittedfiles');
			break;
			default:
				throw new Exception('Type of element error for backup_nested_group');
		}		
	}	
	public function __construct($name, $attributes = null, $final_elements = null) {
		parent::__construct($name, $attributes, $final_elements);
	}
}
class backup_vpl_activity_structure_step extends backup_activity_structure_step {

	protected function define_structure() {

		// To know if we are including userinfo
		$userinfo = $this->get_setting_value('userinfo');

		// Define each element separated
				
		$vpl = new backup_nested_element('vpl', array('id'), array(
				'name','shortdescription','intro','introformat',
				'startdate','duedate','maxfiles','maxfilesize',
				'requirednet','password','grade','visiblegrade',
				'usevariations','variationtitle','basedon','run','debug',
				'evaluate','evaluateonsubmission','automaticgrading',
				'maxexetime','restrictededitor','example','maxexememory',
				'maxexefilesize','maxexeprocesses','jailservers','emailteachers',
				'worktype'
				));
		$required_files=new backup_nested_element('required_files');
		$required_file= new backup_nested_filegroup('required_file',array('id'), array(
				'name','content'));
		$execution_files=new backup_nested_element('execution_files');
		$execution_file= new backup_nested_filegroup('execution_file',array('id'), array(
				'name','content'));
		$variations=new backup_nested_element('variations');
		$variation=new backup_nested_element('variation',array('id'), array(
				'vpl','identification','description'));
		$asigned_variations=new backup_nested_element('asigned_variations');
		$asigned_variation=new backup_nested_element('asigned_variation',array('id'), array(
				'userid','vpl','variation'));
		$submissions = new backup_nested_element('submissions');
		$submission = new backup_nested_element('submission', array('id'), array(
				'vpl','userid','datesubmitted','comments',
				'grader','dategraded','grade','mailed','highlight'
				));
		$submission_files=new backup_nested_element('submission_files');
		$submission_file= new backup_nested_filegroup('submission_file',array('id'), array(
				'name','content'));
		// Build the tree
		$vpl->add_child($required_files);
		$vpl->add_child($execution_files);
		$vpl->add_child($variations);
		$vpl->add_child($submissions);
		$required_files->add_child($required_file);
		$execution_files->add_child($execution_file);
		$variations->add_child($variation);
		$variation->add_child($asigned_variations);
		$asigned_variations->add_child($asigned_variation);
		$submissions->add_child($submission);
		$submission->add_child($submission_files);
		$submission_files->add_child($submission_file);
		// Define sources
		$vpl->set_source_table('vpl', array('id' => backup::VAR_ACTIVITYID));
		$variation->set_source_table('vpl_variations', array('vpl' => backup::VAR_ACTIVITYID));
		if ($userinfo){
			$asigned_variation->set_source_table('vpl_assigned_variations',
					array('vpl' => backup::VAR_ACTIVITYID,'variation'  => backup::VAR_ACTIVITYID));
			//$submission->set_source_table('vpl_submissions', array('vpl' => backup::VAR_ACTIVITYID));
			//Uncomment previous line and comment next to backup all student's submissions nothe last one
			$query = 'SELECT s.* FROM {vpl_submissions} AS s,';
			$query .= '(SELECT max(id) as maxid, userid, vpl FROM {vpl_submissions}';
			$query .= ' WHERE {vpl_submissions}.vpl = ? GROUP BY {vpl_submissions}.userid) AS ls';
			$query .= ' WHERE s.vpl = ? and ls.maxid = s.id';
			$submission->set_source_sql($query, array(backup::VAR_ACTIVITYID,backup::VAR_ACTIVITYID));
		}

		// Define id annotations
		$vpl->annotate_ids('scale', 'grade');
		$vpl->annotate_ids('vpl', 'basedon');
		$asigned_variation->annotate_ids('user', 'userid');
		$submission->annotate_ids('user', 'userid');
		$submission->annotate_ids('user', 'grader');
		// Define file annotations
		$vpl->annotate_files('mod_vpl', 'intro', null);
		return $this->prepare_activity_structure($vpl);
	}
}