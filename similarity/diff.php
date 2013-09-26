<?php
/**
 * @version		$Id: diff.php,v 1.9 2012-06-05 23:22:10 juanca Exp $
 * @package		VPL. Show two files diff
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/diff.class.php';

require_login();

$id = required_param('id', PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('similarity/diff.php', array('id' => $id));
$vpl->add_to_log('Diff', vpl_rel_url('similarity/diff.php','id',$id));
//Print header
$PAGE->requires->css(new moodle_url('/mod/vpl/css/sh.css'));
$vpl->print_header_simple(get_string('diff',VPL));
//$vpl->print_view_tabs(basename(__FILE__));
//Get left file
vpl_diff::vpl_get_similfile('1',$vpl,$HTMLheader1,$filename1,$data1);
//Get right file
vpl_diff::vpl_get_similfile('2',$vpl,$HTMLheader2,$filename2,$data2);
//Show files
vpl_diff::show($filename1,$data1,$HTMLheader1,$filename2,$data2,$HTMLheader2);
$vpl->print_footer();
?>
