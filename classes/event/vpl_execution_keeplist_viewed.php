<?php
/**
 * @package		VPL. Class for logging of execution keep list view events
 * @copyright	2014 onward Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;
require_once dirname(__FILE__).'/../../locallib.php';
defined('MOODLE_INTERNAL') || die();

class vpl_execution_keeplist_viewed extends vpl {
	protected function init() {
		parent::init();
		$this->data['crud'] = 'r';
		$this->legacy_action='execution keep file form';
	}
	public function get_description() {
		return $this->get_description_mod('list of files to keep in execution');
	}	
}
