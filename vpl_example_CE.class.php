<?php
/**
 * @version		$Id: vpl_example_CE.class.php,v 1.2 2012-06-05 23:22:14 juanca Exp $
 * @package		VPL. example Compilation Execution class definition
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
defined('MOODLE_INTERNAL') || die();
require_once dirname(__FILE__).'/vpl_submission_CE.class.php';
class mod_vpl_example_CE extends mod_vpl_submission_CE{
	/**
	 * Constructor
	 * @param $vpl. vpl object instance
	 **/
	function __construct($vpl) {
		global $USER;
		$fake = new stdClass();
		$fake->userid = $USER->id;
		$fake->vpl = $vpl->get_instance()->id;
		parent::__construct($vpl, $fake);
	}

	/**
	 * @return object file group manager for example files
	 **/
	function get_submitted_fgm(){
		if(!$this->submitted_fgm){
			$this->submitted_fgm = $this->vpl->get_required_fgm();
		}
		return $this->submitted_fgm;
	}
	
	/**
	 * Save Compilation Execution result. Removed
	 * @param $result array response from server
	 * @return uvoid
	 */
	function saveCE($result){
		//Paranoic removed
	}
}
?>