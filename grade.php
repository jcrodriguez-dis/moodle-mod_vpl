<?php
/**
 * @version		$Id: grade.php,v 1.9 2013-06-10 10:48:54 juanca Exp $
 * @package		VPL. Redirect grade.php
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../config.php';
require_once dirname(__FILE__).'/lib.php';
require_once dirname(__FILE__).'/vpl.class.php';
require_login();
$id=required_param('id', PARAM_INT);
$vpl=new mod_vpl($id);
$vpl->prepare_page('grade.php', array('id' => $id));
$vpl->print_header();
if ($vpl->has_capability(VPL_GRADE_CAPABILITY)) {
	$userid = optional_param('userid',false,PARAM_INT);
	if($userid){
		vpl_inmediate_redirect(vpl_mod_href('forms/gradesubmission.php','id',$id,'userid',$userid));
	}else{
		vpl_inmediate_redirect(vpl_mod_href('views/submissionslist.php','id',$id));
	}
} else {
	vpl_inmediate_redirect(vpl_mod_href('view.php','id',$id));
}
?>