<?php
/**
 * @version		$Id: requiredfiles.php,v 1.2 2013-06-10 08:15:42 juanca Exp $
 * @package		VPL
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/filegroup_form.php';
require_once dirname(__FILE__).'/../vpl.class.php';

require_login();
$id = required_param('id',PARAM_INT);

$vpl = new mod_vpl($id);
$vpl->prepare_page('forms/requiredfiles.php', array('id' => $id));

$vpl->require_capability(VPL_MANAGE_CAPABILITY);
$fgp = $vpl->get_required_fgm();
$PAGE->requires->css(new moodle_url('/mod/vpl/css/sh.css'));
$vpl->print_header(get_string('requestedfiles',VPL));
$vpl->print_heading_with_help('requestedfiles');
$vpl->print_configure_tabs(basename(__FILE__));
$mform = new mod_vpl_filegroup_form('requiredfiles.php',$fgp,get_string('requestedfiles',VPL));
$mform->preheader_process($vpl->get_printable_name());
$mform->process();
//Display page

$mform->display();
$vpl->print_footer_simple();
?>