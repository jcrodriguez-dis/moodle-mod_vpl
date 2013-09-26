<?php
/**
 * Execute post-uninstall custom actions for VPL
 * 
 * @version		$Id: uninstall.php,v 1.1 2012-06-05 23:22:05 juanca Exp $
 * @package		VPL
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();
require_once $CFG->dirroot.'/mod/vpl/vpl.class.php';
$ret = true;
$vpls = $DB->get_records('vpl',null,'','id');
foreach ($vpls as $vplinstance) {
	$vpl = new mod_vpl(null,$vplinstance->id);
	$ret = $ret && $vpl->delete_all();
}
if(!$ret){
	print_error('error deleting VPL');
}