<?php
/**
 * @version		$Id: executionkeepfiles.php,v 1.7 2013-06-10 08:10:14 juanca Exp $
 * @package		VPL. form to set files to keep while execution
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once $CFG->libdir.'/formslib.php';

class mod_vpl_executionkeepfiles_form extends moodleform{
	protected $fgp;
	function __construct($page,$fgp){
		$this->fgp = $fgp;
		parent::__construct($page);
	}
	function definition(){
		$mform = & $this->_form;
        $mform->addElement('hidden','id',required_param('id',PARAM_INT));
		$mform->setType('id', PARAM_INT);
        $mform->addElement('header', 'header_keepfiles', get_string('keepfiles',VPL));
		$list = $this->fgp->getFileList();
		$keeplist = $this->fgp->getFileKeepList();
		$num=0;
		foreach($list as $filename){
			$mform->addElement('checkbox','keepfile'.$num,$filename);
			$mform->setDefault('keepfile'.$num, in_array($filename,$keeplist));
			$num++;
		}
		$mform->addElement('submit','savekeepfiles',get_string('saveoptions',VPL));
	}
}

require_login();

$id = required_param('id',PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('forms/executionkeepfiles.php', array('id' => $id));
vpl_include_jsfile('hideshow.js');
$vpl->require_capability(VPL_MANAGE_CAPABILITY);
//Display page
$vpl->print_header(get_string('execution',VPL));
$vpl->print_heading_with_help('keepfiles');
$vpl->print_configure_tabs(basename(__FILE__));
$course = $vpl->get_course();
$fgp = $vpl->get_execution_fgm();
$link = vpl_mod_href('forms/executionkeepfiles.php','id',$id);
$linkrel = vpl_rel_url('forms/executionkeepfiles.php','id',$id);
$mform = new mod_vpl_executionkeepfiles_form('executionkeepfiles.php',$fgp);
if ($fromform=$mform->get_data()){
	if(isset($fromform->savekeepfiles)){
		$list = $fgp->getFileList();
		$nlist = count($list);
		$keeplist= array();
		for($i=0; $i<$nlist; $i++){
			$name='keepfile'.$i;
			if(isset($fromform->$name)){
				$keeplist[]=$list[$i];
			}
		}
		$fgp->setFileKeepList($keeplist);
		$vpl->add_to_log('execution save keeplist', $linkrel);
		vpl_notice(get_string('optionssaved',VPL));
	}
}
$vpl->add_to_log('execution keep file form', $linkrel);
$mform->display();
$vpl->print_footer();
?>