<?php
/**
 * @version		$Id: submission_form.php,v 1.15 2013-06-10 08:14:31 juanca Exp $
 * @package		VPL. Submission form definition
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once $CFG->libdir.'/formslib.php';
require_once dirname(__FILE__).'/../locallib.php';
class mod_vpl_submission_form extends moodleform {
	protected $vpl;
	function getInternalForm(){
		return $this->_form;
	}
	function __construct($page,$vpl){
		$this->vpl =$vpl;
		parent::__construct($page);
	}
	function definition(){
		global $CFG;
		$mform =& $this->_form;
		$mform->addElement('header', 'headersubmission', get_string('submission', VPL));
		//Identification info
		$mform->addElement('hidden','id');
		$mform->setType('id', PARAM_INT);
		$mform->addElement('hidden','userid',0);
		$mform->setType('userid', PARAM_INT);
		//Comments
		$mform->addElement('textarea', 'comments', get_string('comments',VPL),array('cols'=>'40', 'rows'=>2));
		$mform->setType('comments', PARAM_TEXT);

		//Files upload
		$instance = $this->vpl->get_instance();
		$files = $this->vpl->get_required_files();
		$nfiles = count($files);
		for($i = 0 ; $i < $instance->maxfiles ; $i++ ){
			$field ='file'.$i;
			if($i < $nfiles){
			   $mform->addElement('filepicker', $field, $files[$i]);
			}
			else{
			   $mform->addElement('filepicker', $field, get_string('anyfile',VPL));
			}
		}
		$this->add_action_buttons(TRUE,get_string('submit'));
	}
}
?>
