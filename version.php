<?php // $Id: version.php,v 1.22 2013-07-11 16:47:04 juanca Exp $

defined('MOODLE_INTERNAL') || die();
//TODO Change module to plugin NOT changed for incompatibility with previous versions
$plugin->version = 2014052912;	//Current module version 3.1
$plugin->cron    = 300; 		//cron check this module every 5 minutes
$plugin->requires = 2013101800;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '3.1';
$plugin->component = 'mod_vpl';
