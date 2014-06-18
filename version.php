<?php // $Id: version.php,v 1.22 2013-07-11 16:47:04 juanca Exp $

defined('MOODLE_INTERNAL') || die();
//TODO Change module to plugin NOT changed for incompatibility with previous versions
$module->version = 2014052912;	//Current module version 3.1
$module->cron    = 300; 		//cron check this module every 5 minutes
$module->requires = 2012062500;
$module->maturity = MATURITY_STABLE;
$module->release = '3.1';
$module->component = 'mod_vpl';
