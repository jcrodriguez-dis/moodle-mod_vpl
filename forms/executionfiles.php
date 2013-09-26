<?php
/**
 * @version		$Id: executionfiles.php,v 1.13 2013-06-10 08:08:21 juanca Exp $
 * @package		VPL. set executions files
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
$vpl->prepare_page('forms/executionfiles.php', array('id' => $id));
$vpl->require_capability(VPL_MANAGE_CAPABILITY);
$PAGE->requires->css(new moodle_url('/mod/vpl/css/sh.css'));
//Display page
$course = $vpl->get_course();
$fgp = $vpl->get_execution_fgm();
$vpl->print_header(get_string('execution',VPL));
$vpl->print_heading_with_help('executionfiles');
$vpl->print_configure_tabs(basename(__FILE__));
$mform = new mod_vpl_filegroup_form('executionfiles.php',$fgp,get_string('execution',VPL));
$mform->preheader_process($vpl->get_printable_name());
$mform->process();
$vpl->add_to_log('execution form', vpl_rel_url('forms/executionfiles.php','id',$id));
$mform->display();
$vpl->print_footer_simple();
?>