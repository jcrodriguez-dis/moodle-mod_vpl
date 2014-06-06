<?php
/**
 * @package		VPL. Show URL to web service with token
 * @copyright	2014 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../vpl.class.php';

require_login();

$id = required_param('id',PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('views/show_webservice.php', array('id' => $id));
$vpl->require_capability(VPL_VIEW_CAPABILITY);
$log_url=vpl_rel_url('views/show_webservice.php','id',$id);
if(!$vpl->is_visible()){
	$vpl->add_to_log('show_webservice', $log_url, "available");
	notice(get_string('notavailable'));
}
$vpl->print_header(get_string('createtokenforuser','core_webservice'));
$vpl->print_view_tabs('view.php');
echo '<h1>'.get_string('webservice','core_webservice').'</h1>';
echo '<h3>'.get_string('createtokenforuserdescription','core_webservice').'</h3>';
$service_url = vpl_get_webservice_urlbase($vpl);
$rows = (int) (strlen($service_url)/80+2);
echo $OUTPUT->box('<div style="white-space: pre-wrap">'.s($service_url).'</div>');
notice('',vpl_mod_href('view.php','id',$id));
$vpl->print_footer();
