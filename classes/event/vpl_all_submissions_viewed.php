<?php
/**
 * @package		VPL. Class for logging of all submissions view events
 * @copyright	2014 onward Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;
require_once dirname(__FILE__).'/../../locallib.php';
defined('MOODLE_INTERNAL') || die();

class vpl_all_submissions_viewed extends vpl {
	protected function init() {
		parent::init();
		$this->data['crud'] = 'r';
		$this->legacy_action='view all submissions';
	}
	public function get_description() {
		return $this->get_description_mod('all submissions');
	}	
}
