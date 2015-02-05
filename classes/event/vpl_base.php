<?php
/**
 * @package		VPL. Base class for logging of vpl instance related events
 * @copyright	2014 onwards Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;
require_once dirname(__FILE__).'/../../locallib.php';
defined('MOODLE_INTERNAL') || die();

class vpl_base extends base {
	protected function init() {
		$this->data['crud'] = 'u';
		$this->data['edulevel'] = self::LEVEL_TEACHING;
		$this->data['objecttable'] = VPL;
	}
	public static function log($vpl) {
		if(is_array($vpl)){
			parent::log($vpl);
		}else{
			$einfo =array(
					'objectid' => $vpl->get_instance()->id,
					'context' => $vpl->get_context()
			);
			parent::log($einfo);
		}
	}
	public function get_url(){
	    return $this->get_url_base('view.php');
	}
	public function get_description_mod($mod) {
		$desc = 'The user with id '.$this->userid.' '.$this->action.' '
				.$mod.' of VPL activity with id '.$this->objectid;
		if(($this->relateduserid) && $this->relateduserid != $this->userid){
			$desc .= ' for user with id '.$this->relateduserid;
		}		
		return $desc;
	}
}
