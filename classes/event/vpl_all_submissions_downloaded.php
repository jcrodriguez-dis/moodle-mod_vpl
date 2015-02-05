<?php
/**
 * @package		VPL. Class for logging download of all submissions of an activity events
 * @copyright	2014 onward Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;
require_once dirname(__FILE__).'/../../locallib.php';
defined('MOODLE_INTERNAL') || die();

class vpl_all_submissions_downloaded extends vpl_base {
	protected function init() {
		parent::init();
		$this->data['crud'] = 'r';
		$this->legacy_action='Download all submissions';
	}
	public function get_description() {
		return $this->get_description_mod('all submissions');
	}	
}
