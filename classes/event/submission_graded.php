<?php
/**
 * @package		VPL. Class for logging submission graded events
 * @copyright	2014 onward Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;
require_once dirname(__FILE__).'/../../locallib.php';
defined('MOODLE_INTERNAL') || die();

class submission_graded extends submission {
	protected function init() {
		parent::init();
		$this->data['crud'] = 'c';
		$this->data['edulevel'] = self::LEVEL_TEACHING;
		$this->legacy_action='grade';
	}
	protected function get_description_mod($mod) {
		$desc='The user with id '.$this->userid.' '.$this->action.' '.$mod.' VPL submission with id '.$this->objectid;
		$desc .= ' of user with id '.$this->relateduserid;
		return $desc;
	}
	public function get_description() {
		return $this->get_description_mod('');
	}
}
