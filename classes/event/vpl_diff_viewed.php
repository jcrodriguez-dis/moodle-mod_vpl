<?php
/**
 * @package mod_vpl. Class for logging of submission view in diff events
 * @copyright	2014 onwards Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;
require_once dirname(__FILE__).'/../../locallib.php';
defined('MOODLE_INTERNAL') || die();

class vpl_diff_viewed extends submission_base {
	protected function init() {
		parent::init();
		$this->data['edulevel'] = self::LEVEL_TEACHING;
		$this->data['crud'] = 'r';
		$this->legacy_action='Diff';
	}
	public function get_description() {
		return $this->get_description_mod('diff');
	}	
}
