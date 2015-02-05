<?php
/**
 * @package		VPL. Class for logging of execution limits update events
 * @copyright	2014 onwards Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;
require_once dirname(__FILE__).'/../../locallib.php';
defined('MOODLE_INTERNAL') || die();

class vpl_execution_limits_updated extends vpl_base {
	protected function init() {
		parent::init();
		$this->legacy_action='execution save limits';
	}
	public function get_description() {
		return $this->get_description_mod('execution resource limits');
	}	
}
