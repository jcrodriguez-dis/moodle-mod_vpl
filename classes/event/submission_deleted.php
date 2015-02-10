<?php
/**
 * @package		VPL. Class for logging submission edit events
 * @copyright	2014 onward Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;
require_once dirname(__FILE__).'/../../locallib.php';
defined('MOODLE_INTERNAL') || die();

class submission_deleted extends submission_base {
	protected function init() {
		parent::init();
		$this->data['crud'] = 'd';
		$this->legacy_action='delete submission';
	}
}
