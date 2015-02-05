<?php
/**
 * @package		VPL. Class for logging submission grade delete events
 * @copyright	2014 onwards Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;
require_once dirname(__FILE__).'/../../locallib.php';
defined('MOODLE_INTERNAL') || die();

class submission_grade_deleted extends submission_graded {
	protected function init() {
		parent::init();
		$this->data['crud'] = 'd';
	}

	public function get_description() {
		return $this->get_description_mod('grade');
	}
}
