<?php
/**
 * Provides support for restore VPL antivities in the moodle2 backup format
 *
 * @version		$Id: restore_vpl_activity_task.class.php,v 1.2 2012-06-20 14:55:49 juanca Exp $
 * @package		VPL
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();
require_once dirname(__FILE__).'/restore_vpl_stepslib.php';

class restore_vpl_activity_task extends restore_activity_task {
	private $structure_step;
	/**
	 * Define (add) particular settings this activity can have
	 */
	protected function define_my_settings() {
		// No particular settings for this activity
	}

	/**
	 * Define (add) particular steps this activity can have
	 */
	protected function define_my_steps() {
		$this->structure_step = new restore_vpl_activity_structure_step('vpl_structure', 'vpl.xml');
		$this->add_step($this->structure_step);
	}

	/**
	 * Define the contents in the activity that must be
	 * processed by the link decoder
	 */
	static public function define_decode_contents() {
		$contents = array();

		$contents[] = new restore_decode_content('vpl', array('intro'), 'vpl');

		return $contents;
	}

	/**
	 * Define the decoding rules for links belonging
	 * to the activity to be executed by the link decoder
	 */
	static public function define_decode_rules() {
		$rules = array();

		$rules[] = new restore_decode_rule('VPLVIEWBYID', '/mod/vpl/view.php?id=$1', 'course_module');
		$rules[] = new restore_decode_rule('VPLINDEX', '/mod/vpl/index.php?id=$1', 'course');

		return $rules;

	}

	/**
	 * Define the restore log rules that will be applied
	 * by the {@link restore_logs_processor} when restoring
	 * choice logs. It must return one array
	 * of {@link restore_log_rule} objects
	 */
	static public function define_restore_log_rules() {
		$rules = array();
/*
		$rules[] = new restore_log_rule('vpl', 'add', 'view.php?id={course_module}', '{vpl}');
		$rules[] = new restore_log_rule('vpl', 'update', 'view.php?id={course_module}', '{vpl}');
		$rules[] = new restore_log_rule('vpl', 'view', 'view.php?id={course_module}', '{vpl}');
		$rules[] = new restore_log_rule('vpl', 'choose', 'view.php?id={course_module}', '{vpl}');
		$rules[] = new restore_log_rule('vpl', 'choose again', 'view.php?id={course_module}', '{vpl}');
		$rules[] = new restore_log_rule('vpl', 'report', 'report.php?id={course_module}', '{vpl}');
*/
		return $rules;
	}

	/**
	 * Define the restore log rules that will be applied
	 * by the {@link restore_logs_processor} when restoring
	 * course logs. It must return one array
	 * of {@link restore_log_rule} objects
	 *
	 * Note this rules are applied when restoring course logs
	 * by the restore final task, but are defined here at
	 * activity level. All them are rules not linked to any module instance (cmid = 0)
	 */
	static public function define_restore_log_rules_for_course() {
		$rules = array();

		// Fix old wrong uses (missing extension)
/*		$rules[] = new restore_log_rule('choice', 'view all', 'index?id={course}', null,
				null, null, 'index.php?id={course}');
		$rules[] = new restore_log_rule('choice', 'view all', 'index.php?id={course}', null);
*/
		return $rules;
	}
	
	public function after_restore() {
		global $DB;
		$id=$this->get_activityid();
		$data = $DB->get_record('vpl',array('id'=>$id));
		if($data != false){
			$data->basedon = $this->structure_step->get_mappingid('vpl', $data->basedon);
			$DB->update_record('vpl', $data);
		} 
	}
}