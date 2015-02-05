<?php
/**
 * @package		VPL. Class for logging of execution options view events
 * @copyright	2014 onwards Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;
require_once dirname(__FILE__).'/../../locallib.php';
defined('MOODLE_INTERNAL') || die();

class vpl_execution_options_viewed extends vpl_base {
	protected function init() {
		parent::init();
		$this->data['crud'] = 'r';
		$this->legacy_action='execution options form';
	}
	public function get_description() {
		return $this->get_description_mod('execution options');
	}	
}
