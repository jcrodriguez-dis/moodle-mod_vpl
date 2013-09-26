<?php
/**
 * @version		$Id: executionlimits.php,v 1.8 2013-06-10 08:11:31 juanca Exp $
 * @package		VPL. Execution limits form
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */


require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once $CFG->libdir.'/formslib.php';

class mod_vpl_executionlimits_form extends moodleform{
	protected $vpl;
	function __construct($page,$vpl){
		$this->vpl = $vpl;
		parent::__construct($page);
	}
	function definition(){
		global $CFG;
		$mform = & $this->_form;
		$id = $this->vpl->get_course_module()->id;
		$instance = $this->vpl->get_instance();
        $mform->addElement('hidden','id',$id);
		$mform->setType('id', PARAM_INT);
        $mform->addElement('header', 'header_execution_limits', get_string('resourcelimits',VPL));
		$mform->addElement('select','maxexetime',get_string('maxexetime',VPL),
											vpl_get_select_time((int)$CFG->vpl_maxexetime));
		$mform->setType('maxexetime', PARAM_INT);
		if($instance->maxexetime)
			$mform->setDefault('maxexetime',$instance->maxexetime);
		$mform->addElement('select','maxexememory',get_string('maxexememory',VPL),
											vpl_get_select_sizes(64*1024*1024,(int)$CFG->vpl_maxexememory));
		$mform->setType('maxexememory', PARAM_INT);
		if($instance->maxexememory)
			$mform->setDefault('maxexememory',$instance->maxexememory);
		$mform->addElement('select','maxexefilesize',get_string('maxexefilesize',VPL),
											vpl_get_select_sizes(1024*256,(int)$CFG->vpl_maxexefilesize));
		$mform->setType('maxexefilesize', PARAM_INT);
		if($instance->maxexefilesize)
			$mform->setDefault('maxexefilesize',$instance->maxexefilesize);
		$mform->addElement('text','maxexeprocesses',get_string('maxexeprocesses',VPL));
		$mform->setType('maxexeprocesses', PARAM_INT);
		if($instance->maxexeprocesses)
			$mform->setDefault('maxexeprocesses',$instance->maxexeprocesses);
		$mform->addElement('submit','savelimitoptions',get_string('saveoptions',VPL));
	}
}

require_login();

$id = required_param('id',PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('forms/executionlimits.php', array('id' => $id));
vpl_include_jsfile('hideshow.js');
$vpl->require_capability(VPL_MANAGE_CAPABILITY);
//Display page
$vpl->print_header(get_string('execution',VPL));
$vpl->print_heading_with_help('resourcelimits');
$vpl->print_configure_tabs(basename(__FILE__));
$course = $vpl->get_course();
$fgp = $vpl->get_execution_fgm();
$mform = new mod_vpl_executionlimits_form('executionlimits.php',$vpl);
$link = vpl_mod_href('forms/executionlimits.php','id',$id);
$linkrel = vpl_rel_url('forms/executionlimits.php','id',$id);
if ($fromform=$mform->get_data()){
	if(isset($fromform->savelimitoptions)){
		$instance = $vpl->get_instance();
		$vpl->add_to_log('execution save limits', $linkrel);
		$instance->maxexetime = $fromform->maxexetime;
		$instance->maxexememory = $fromform->maxexememory;
		$instance->maxexefilesize = $fromform->maxexefilesize;
		$instance->maxexeprocesses = $fromform->maxexeprocesses;
		if($DB->update_record(VPL,$instance)){
			vpl_notice(get_string('optionssaved',VPL));
		}
		else{
			vpl_error(get_string('optionsnotsaved',VPL));
		}
	}
}
$vpl->add_to_log('execution limits form', $linkrel);
$mform->display();
$vpl->print_footer();
?>