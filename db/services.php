<?php
/**
 * @package		VPL. web service definition
 * @copyright	2014 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
//Definition of functions of the web service 
$functions=array(
	'mod_vpl_info'=>array(
		'classname'=>'mod_vpl_webservice',
		'methodname'=>'info',
		'classpath'=>'mod/vpl/externallib.php',
		'description'=>'Get the information/description about a VPL activity',
		'requiredcapability' => 'mod/vpl:view',
		'type'=>'read'
	),
	'mod_vpl_save'=>array(
		'classname'=>'mod_vpl_webservice',
		'methodname'=>'save',
		'classpath'=>'mod/vpl/externallib.php',
		'description'=>'Save/submit the student\'s files of a VPL activity',
		'requiredcapability' => 'mod/vpl:submit',
		'type'=>'write'
	),
	'mod_vpl_open'=>array(
			'classname'=>'mod_vpl_webservice',
			'methodname'=>'open',
			'classpath'=>'mod/vpl/externallib.php',
			'description'=>'Open/Download the student\'s files of the last submission of a VPL activity',
			'requiredcapability' => 'mod/vpl:view',
			'type'=>'read'
	),
	'mod_vpl_evaluate'=>array(
			'classname'=>'mod_vpl_webservice',
			'methodname'=>'evaluate',
			'classpath'=>'mod/vpl/externallib.php',
			'description'=>'Evaluate the student\'s submission',
			'requiredcapability' => 'mod/vpl:submit',
			'type'=>'write'
	),
	'mod_vpl_get_result'=>array(
			'classname'=>'mod_vpl_webservice',
			'methodname'=>'get_result',
			'classpath'=>'mod/vpl/externallib.php',
			'description'=>'Get result of the evalaution',
			'requiredcapability' => 'mod/vpl:view',
			'type'=>'write'
	),	
);
//Define web service
$services=array(
		'VPL web service'=>array(
				'functions'=>array('mod_vpl_info' ,'mod_vpl_save','mod_vpl_open','mod_vpl_evaluate','mod_vpl_get_result'),
				'shortname' => 'mod_vpl_edit',
				'restrictedusers'=>0,
				'enabled'=>0,
		)
);
