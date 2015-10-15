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
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../editor/editor_utility.php';
vpl_editor_util::generate_requires();

require_login();
$id = required_param('id',PARAM_INT);

$vpl = new mod_vpl($id);
$instance = $vpl->get_instance();
$vpl->prepare_page('forms/executionfiles.php', array('id' => $id));

$vpl->require_capability(VPL_MANAGE_CAPABILITY);
$fgp = $vpl->get_required_fgm();
$vpl->print_header(get_string('executionfiles',VPL));
$vpl->print_heading_with_help('executionfiles');
$vpl->print_configure_tabs(basename(__FILE__));
//TODO download in zip file

$options = Array();
$options['restrictededitor']=false;
$options['save']=true;
$options['run']=false;
$options['debug']=false;
$options['evaluate']=false;
$options['ajaxurl']="executionfiles.json.php?id={$id}&action=";
$options['download']="../views/downloadexecutionfiles.php?id={$id}";
$options['resetfiles']=false;
$options['minfiles']=0;
$options['maxfiles']=100;
//Get files
$fgp = $vpl->get_execution_fgm();
$options['minfiles']=$fgp->get_numstaticfiles();
$filelist =$fgp->getFileList();
$nf = count($filelist);
$files = Array();
for( $i = 0; $i < $nf; $i++){
	$filename=$filelist[$i];
	$filedata=$fgp->getFileData($filelist[$i]);
	$files[$filename]=$filedata;
}
session_write_close();
echo $OUTPUT->box_start();
vpl_editor_util::print_tag($options,$files);
echo $OUTPUT->box_end();
$vpl->print_footer_simple();
