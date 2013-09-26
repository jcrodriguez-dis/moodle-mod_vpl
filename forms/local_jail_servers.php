<?php
/**
 * @version		$Id: local_jail_servers.php,v 1.4 2013-06-10 08:12:32 juanca Exp $
 * @package		VPL. Setjails form
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once $CFG->libdir.'/formslib.php';

class mod_vpl_setjails_form extends moodleform {
	function __construct($page){
		parent::__construct($page);
	}
	function definition(){
		$mform    =& $this->_form;    
        $mform->addElement('header', 'headersetjails', get_string('local_jail_servers', VPL));
        $mform->addElement('hidden','id',required_param('id',PARAM_INT));
		$mform->setType('id', PARAM_INT);
        $mform->addElement('textarea', 'jailservers', get_string('jail_servers_description', VPL), array('cols'=>45, 'rows'=>10, 'wrap'=>'off'));
        $mform->setType('jailservers', PARAM_RAW);
        $this->add_action_buttons();
	}
}

require_login();

$id = required_param('id',PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('forms/local_jail_servers.php', array('id' => $id));
vpl_include_jsfile('hideshow.js');
$vpl->require_capability(VPL_SETJAILS_CAPABILITY);
$vpl->print_header(get_string('local_jail_servers',VPL));
$vpl->print_heading_with_help('local_jail_servers');
$vpl->print_configure_tabs(basename(__FILE__));
$mform = new mod_vpl_setjails_form('local_jail_servers.php');
//Display page
$course = $vpl->get_course();
$link = vpl_rel_url('local_jail_servers.php','id',$id);
if (!$mform->is_cancelled() && $fromform=$mform->get_data()){
	if(isset($fromform->jailservers)){
		$vpl->add_to_log('set jails',vpl_rel_url('forms/local_jail_servers.php','id',$id));
		$instance = $vpl->get_instance();
		$instance->jailservers=s($fromform->jailservers);
		if($DB->update_record(VPL,$instance)){
			vpl_notice(get_string('saved',VPL));
		}
		else{
			vpl_error(get_string('notsaved',VPL));
		}
	}
	else{
		vpl_error(get_string('notsaved',VPL));
	}
}
$data = new stdClass();
$data->id = $id;
$data->jailservers = $vpl->get_instance()->jailservers;
$mform->set_data($data);
$vpl->add_to_log('edit jails',$link);
$mform->display();
$vpl->print_footer();

?>