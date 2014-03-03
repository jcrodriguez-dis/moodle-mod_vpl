<?php

/**
 * @version		$Id: access.php,v 1.8 2013-03-05 09:22:34 juanca Exp $
 * @package		VPL
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'mod/vpl:view' => array( //Allow to view complete vpl description
    	'riskbitmask' => 0,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
           'guest' => CAP_PREVENT,
           'student' => CAP_ALLOW,
           'teacher' => CAP_ALLOW,
           'editingteacher' => CAP_ALLOW,
           'coursecreator' => CAP_ALLOW,
        	'manager' => CAP_ALLOW
        )
    ),

    'mod/vpl:submit' => array( //Allow to submit a vpl assingment
    	'riskbitmask' => 0,
    	'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
           'guest' => CAP_PROHIBIT,
           'student' => CAP_ALLOW,
           'teacher' => CAP_PREVENT,
           'editingteacher' => CAP_ALLOW,
           'coursecreator' => CAP_ALLOW,
           'manager' => CAP_ALLOW
        	)
    ),

    'mod/vpl:grade' => array( //Allow to grade a vpl submission
    	'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
           'guest' => CAP_PROHIBIT,
           'student' => CAP_PREVENT,
           'teacher' => CAP_ALLOW,
           'editingteacher' => CAP_ALLOW,
           'coursecreator' => CAP_ALLOW,
           'manager' => CAP_ALLOW
        	)
        ),
    'mod/vpl:similarity' => array( //Allow to show submissions similarity
    	'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
           'guest' => CAP_PROHIBIT,
           'student' => CAP_PREVENT,
           'teacher' => CAP_ALLOW,
           'editingteacher' => CAP_ALLOW,
           'coursecreator' => CAP_ALLOW,
           'manager' => CAP_ALLOW
        	)
        ),
    'mod/vpl:addinstance' => array( //Allow to add new vpl instance
    	'riskbitmask' => RISK_SPAM|RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
           'guest' => CAP_PROHIBIT,
           'student' => CAP_PROHIBIT,
           'teacher' => CAP_PREVENT,
           'editingteacher' => CAP_ALLOW,
           'coursecreator' => CAP_ALLOW,
           'manager' => CAP_ALLOW
        	)
        ),
	'mod/vpl:manage' => array( //Allow to manage a vpl instance
		'riskbitmask' => RISK_SPAM|RISK_XSS|RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
           'guest' => CAP_PROHIBIT,
           'student' => CAP_PROHIBIT,
           'teacher' => CAP_PREVENT,
           'editingteacher' => CAP_ALLOW,
           'coursecreator' => CAP_ALLOW,
           'manager' => CAP_ALLOW
        	)
        ),
    'mod/vpl:setjails' => array( //Allow to set the jails for a vpl instance
    	'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
           'guest' => CAP_PROHIBIT,
           'student' => CAP_PROHIBIT,
           'teacher' => CAP_PROHIBIT,
           'editingteacher' => CAP_ALLOW,
           'coursecreator' => CAP_ALLOW,
           'manager' => CAP_ALLOW
        	)
        )
 );
?>