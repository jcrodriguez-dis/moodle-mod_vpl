<?php
/**
 * @version		$Id: password_form.php,v 1.8 2013-06-10 08:13:15 juanca Exp $
 * @package		VPL. Get password to access form
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

global $CFG;
require_once $CFG->libdir.'/formslib.php';
class mod_vpl_password_form extends moodleform {
	function definition(){
		global $SESSION;
		$mform    =& $this->_form;    
        $mform->addElement('header', 'headerpassword', get_string('requiredpassword', VPL));
        $mform->addElement('hidden','id',required_param('id',PARAM_INT));
        $mform->setType('id', PARAM_INT);
        $parms = array('userid','submissionid','popup','fullscreen','privatecopy');
        foreach($parms as $parm){
        	$value=optional_param($parm,-1,PARAM_INT);
        	if($value>=0){
        		$mform->addElement('hidden',$parm,$value);
        		$mform->setType($parm, PARAM_INT);
        	}
        }
        $mform->addElement('password', 'password', get_string('password') );
        $mform->setType('password', PARAM_TEXT);
        $mform->setDefault('password', '');
        if(isset($SESSION->vpl_attempt_number)){
        	$mform->addElement('static', 'attempt_number','', get_string('attemptnumber',VPL,$SESSION->vpl_attempt_number) );
        }
        $this->add_action_buttons();
	}
}
?>