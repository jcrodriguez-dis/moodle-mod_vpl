<?php
/**
 * @package		VPL. Class for logging of similary report view events
 * @copyright	2014 onward Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;
require_once dirname(__FILE__).'/../../locallib.php';
defined('MOODLE_INTERNAL') || die();

class vpl_similarity_report_viewed extends vpl {
	protected function init() {
		parent::init();
		$this->data['crud'] = 'r';
		$this->legacy_action='view similarity';
	}
	public function get_description() {
		return $this->get_description_mod('similarity report');
	}	
}
