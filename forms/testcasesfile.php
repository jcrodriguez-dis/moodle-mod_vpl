<?php
/**
 * @version		$Id: testcasesfile.php,v 1.3 2013-06-10 08:15:42 juanca Exp $
 * @package		VPL. Edit test cases' file
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/filegroup_form.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../views/sh_factory.class.php';
require_once $CFG->libdir.'/formslib.php';

require_login();

$id = required_param('id',PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('forms/testcasesfile.php', array('id' => $id,'edit'=>3));
$vpl->require_capability(VPL_MANAGE_CAPABILITY);
$vpl->print_header(get_string('testcases',VPL));
$vpl->print_heading_with_help('testcases');
$vpl->print_configure_tabs(basename(__FILE__));
$fgp = $vpl->get_execution_fgm();
$mform = new mod_vpl_filegroup_form('testcasesfile.php',$fgp,get_string('execution',VPL),true);
$mform->preheader_process($vpl->get_printable_name());
//Display page
$course = $vpl->get_course();
$vpl->add_to_log('form of test cases', vpl_rel_url('forms/testcasesfile.php','id',$id,'edit',3));
$mform->process();

$mform->display();
$vpl->print_footer_simple();
?>