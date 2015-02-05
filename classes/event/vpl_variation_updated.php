<?php
/**
 * @package		VPL. Class for logging of variation related events
 * @copyright	2014 onward Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;
require_once dirname(__FILE__).'/../../locallib.php';
defined('MOODLE_INTERNAL') || die();

class vpl_variation_updated extends vpl {
	protected function init() {
		parent::init();
		$this->legacy_action='variations form';
	}
	public function get_description() {
		return $this->get_description_mod('variation config');
	}	
}
