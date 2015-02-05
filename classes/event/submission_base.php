<?php
/**
 * @package		VPL. Base class for logging submission related events
 * @copyright	2014 onwards Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;
require_once dirname(__FILE__).'/../../locallib.php';
defined('MOODLE_INTERNAL') || die();

class submission_base extends base {
	protected function init() {
		$this->data['crud'] = 'c';
		$this->data['edulevel'] = self::LEVEL_PARTICIPATING;
		$this->data['objecttable'] = VPL_SUBMISSIONS;
	}
	public function get_url(){
    	return $this->get_url_base('forms/submissionview.php');
	}
	protected function get_description_mod($mod) {
		$desc='The user with id '.$this->userid.' '.$this->action.' '.$mod.' VPL submission with id '.$this->objectid;
		if(isset($this->relateduserid) && $this->relateduserid>0 && $this->relateduserid != $this->userid){
			$desc .= ' of user with id '.$this->relateduserid;
		}
		return $desc;
	}
	public static function log($submission) {
		if(is_array($submission)){
			parent::log($submission);
		}else{
			global $USER;
			$subinstance = $submission->get_instance();
			$vpl = $submission->get_vpl();
			$einfo=array(
				'objectid' => $subinstance->id,
				'context' => $vpl->get_context(),
				'relateduserid' => ($USER->id != $subinstance->userid?$subinstance->userid:null)
			);
			parent::log($einfo);
		}
	}
	public function get_description() {
		return $this->get_description_mod('');
	}
}
