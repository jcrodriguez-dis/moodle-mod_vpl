<?php
/**
 * @version		$Id: checkjailservers.php,v 1.3 2012-06-05 23:22:09 juanca Exp $
 * @package		VPL. Check jail servers
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../jail/HTTP_request.class.php';
require_once dirname(__FILE__).'/../jail/jailserver_manager.class.php';

require_login();

$id = required_param('id',PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('views/checkjailservers.php', array('id' => $id));

$vpl->require_capability(VPL_MANAGE_CAPABILITY);
//Display page
$PAGE->requires->css(new moodle_url('/mod/vpl/css/checkjailservers.css'));
$course = $vpl->get_course();
$vpl->print_header(get_string('check_jail_servers',VPL));
$vpl->print_heading_with_help('check_jail_servers');
$vpl->print_configure_tabs(basename(__FILE__));
$servers = vpl_jailserver_manager::check_servers($vpl->get_instance()->jailservers);
$table = new html_table();
$table->head  = array ('#', 
						get_string('server',VPL),
						get_string('currentstatus',VPL),
						get_string('lasterror',VPL),
						get_string('lasterrordate',VPL),
						get_string('totalnumberoferrors',VPL));
$table->align = array ('right','left', 'left', 'left', 'left', 'right');
$table->data =array();
$num=0;
$clean_url = !$vpl->has_capability(VPL_SETJAILS_CAPABILITY);
foreach($servers as $server){
	$serverurl=$server->server;
	if($clean_url){
		$serverurl=str_replace('http://','',$serverurl);
		$pos=strrpos($serverurl,':');
		if($pos !== false){
			$serverurl = substr($serverurl,0,$pos);
		}
		$pos=strpos($serverurl,'/',2);
		if($pos !== false){
			$serverurl = substr($serverurl,0,$pos);
		}
	}
	$num++;
	if($server->offline){
		$status='<div class="vpl_server_failed">'.$server->current_status.'</div>';
	}else{
		$status=$server->current_status;
	}
	$table->data[] = array(	$num,
							$serverurl,
							$status,
							$server->laststrerror,
							$server->lastfail>0?userdate($server->lastfail):'',
							$server->nfails);
}
echo html_writer::table($table);
$vpl->print_footer();

?>