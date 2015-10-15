<?php
/**
 * @version		$Id: activityworkinggraph.php,v 1.2 2012-06-05 23:22:09 juanca Exp $
 * @package		VPL. Grade submission
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';

require_login();
$id = required_param('id',PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('views/activityworkinggraph.php', array('id' => $id));

$course = $vpl->get_course();
$instance = $vpl->get_instance();
$vpl->require_capability(VPL_GRADE_CAPABILITY);
//Print header
$vpl->print_header_simple();
echo '<div class="clearer"> </div>';
echo '<div style="text-align: center">';
echo '<img src="'.vpl_rel_url('workinggraph.php','id',$id,'userid',-1).'" alt="Working periods" />';
echo '</div>';
$vpl->print_footer_simple();
?>