<?php
/**
 * @version		$Id: restore_vpl_stepslib.php,v 1.1 2012-06-05 23:22:15 juanca Exp $
 * @package		VPL
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

class restore_vpl_activity_structure_step extends restore_activity_structure_step {

	protected function define_structure() {

		$paths = array();
		$userinfo = $this->get_setting_value('userinfo');

		$paths[] = new restore_path_element('vpl', '/activity/vpl');
		$paths[] = new restore_path_element('required_file', '/activity/vpl/required_files/required_file');
		$paths[] = new restore_path_element('execution_file', '/activity/vpl/execution_files/execution_file');
		$paths[] = new restore_path_element('variation', '/activity/vpl/variations/variation');
		if ($userinfo) {
			$paths[] = new restore_path_element('assigned_variation', '/activity/vpl/assigned_variations/assigned_variation');
			$paths[] = new restore_path_element('submission', '/activity/vpl/submissions/submission');
			$paths[] = new restore_path_element('submission_file', '/activity/vpl/submissions/submission/submission_files/submission_file');
		}

		// Return the paths wrapped into standard activity structure
		return $this->prepare_activity_structure($paths);
	}
	
	protected function process_vpl($data) {
		global $DB;
		$data = (object)$data;
		$oldid = $data->id;
		$data->course = $this->get_courseid();
		$data->startdate = $this->apply_date_offset($data->startdate);
		$data->duedate = $this->apply_date_offset($data->duedate);
		if ($data->grade < 0) {
			$data->grade = -($this->get_mappingid('scale', -($data->grade)));
		}
		// insert the choice record
		$newitemid = $DB->insert_record('vpl', $data);
		// immediately after inserting "activity" record, call this
		$this->apply_activity_instance($newitemid);
	}

	private function process_groupfile($data,$path){
		global $CFG;
		$data = (object)$data;
		$fp = vpl_fopen($path.$data->name);
		fwrite($fp,$data->content);
		fclose($fp);
	}

	protected function process_required_file($data) {
		global $CFG;
		$vplid=$this->get_new_parentid('vpl');
		$path= $CFG->dataroot.'/vpl_data/'.$vplid.'/';
		$this->process_groupfile($data,$path);
	}

	protected function process_execution_file($data) {
		global $CFG;
		$vplid=$this->get_new_parentid('vpl');
		$path= $CFG->dataroot.'/vpl_data/'.$vplid.'/';
		$this->process_groupfile($data,$path);
	}

	protected function process_variation($data) {
        global $DB;
 
        $data = (object)$data;
        $oldid = $data->id;
 
        $data->vpl = $this->get_new_parentid('vpl'); 
        $newitemid = $DB->insert_record('vpl_variations', $data);
	}
	
	protected function process_assigned_variation($data) {
		global $DB;
		$data = (object)$data;
		$oldid = $data->id;
		$data->vpl = $this->get_new_parentid('vpl');
		$data->variation = $this->get_new_parentid('vpl_variation');
		$data->userid = $this->get_mappingid('user', $data->userid);
		$newitemid = $DB->insert_record('vpl_assigned_variations', $data);
	}

	protected function process_submission($data) {
		global $DB;	
		$data = (object)$data;
		$oldid = $data->id;
		$data->vpl = $this->get_new_parentid('vpl');
		$data->userid = $this->get_mappingid('user', $data->userid);
		$data->grader = $this->get_mappingid('user', $data->grader);
		$newitemid = $DB->insert_record('vpl_submissions', $data);
		$this->set_mapping('submission', $oldid, $newitemid);
	}
	protected function process_submission_file($data) {
		global $DB;
		global $CFG;
		static $sub=false;
		$data = (object)$data;
		$vplid=$this->get_new_parentid('vpl');
		$subid=$this->get_new_parentid('submission');
		if($sub === false || $sub->id != $subid){
			$sub = $DB->get_record('vpl_submissions', array('id' => $subid),'id,userid,vpl');
		}
		if($sub === false){
			throw new Exception('Submission record not found '.$subid);
		}
		if($vplid != $sub->vpl){
			throw new Exception('Submission record vplid inconsistence');
		}
		$path= $CFG->dataroot.'/vpl_data/'.$vplid.'/usersdata/'.$sub->userid.'/'.$subid.'/';
		$this->process_groupfile($data,$path);
	}
	
	protected function after_execute() {
		// Add choice related files, no need to match by itemname (just internally handled context)
	}
}