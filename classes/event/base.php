<?php
/**
 * @package mod_vpl. Base class for log events
 * @copyright	2014 onwards Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl\event;
require_once dirname(__FILE__).'/../../locallib.php';
defined('MOODLE_INTERNAL') || die();

abstract class base extends \core\event\base {
	protected $legacy_action='';
	protected function get_url_base($script){
		$parms= array('id' => $this->contextinstanceid);
		if(($this->relateduserid) && $this->relateduserid != $this->userid){
			$parms['userid'] = $this->relateduserid;
		}
		return new \moodle_url('mod/vpl/'.$script,$parms);
	}
	public function get_description() {
		return '';
	}

	public function get_url(){
		return null;
	}
	public static function log($event_info){
		$event = self::create($event_info);
		$event->trigger();
	}
	public function get_legacy_logdata() {
		$urltext='';
		$url=$this->get_url();
		if($url != null){
			$urltext=$url->out(false);
		}
		return array(
					$this->courseid,
					VPL,
					$this->legacy_action,
				    $urltext,
					$this->get_description(),
					$this->contextinstanceid
			);
	}
}
