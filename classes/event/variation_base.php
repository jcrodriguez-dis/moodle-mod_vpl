<?php
/**
 * @package mod_vpl. Class for logging of variation updated events
 * @copyright	2014 onwards Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;
require_once dirname(__FILE__).'/../../locallib.php';
defined('MOODLE_INTERNAL') || die();

class variation_base extends vpl_base {
	protected function init() {
		parent::init();
		$this->data['objecttable'] = VPL_VARIATIONS;
		$this->legacy_action='variations form';
	}
	public function get_description() {
		return $this->get_description_mod('variation');
	}
	public static function log($info) {
		if(is_array($info)){
			parent::log($info);
		}else{
			throw new \coding_exception('Parameter must be an array');
		}
	}
}
