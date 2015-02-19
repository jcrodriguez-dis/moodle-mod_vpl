<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// VPL for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with VPL for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @version        $Id: executionoptions.php,v 1.10 2013-06-10 08:11:31 juanca Exp $
 * @package mod_vpl. Execution options form
 * @copyright    2012 Juan Carlos Rodríguez-del-Pino
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author        Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once $CFG->libdir.'/formslib.php';

class mod_vpl_executionoptions_form extends moodleform{
    protected $vpl;
    function __construct($page,$vpl){
        $this->vpl = $vpl;
        parent::__construct($page);
    }
    function definition(){
        $mform = & $this->_form;
        $id = $this->vpl->get_course_module()->id;
        $mform->addElement('hidden','id',$id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('header', 'header_execution_options', get_string('executionoptions',VPL));
        $strbasedon = get_string('basedon', VPL);
        $basedonlist = array();
        $basedonlist[0]='';
        $courseid = $this->vpl->get_course()->id;
        $listcm = get_coursemodules_in_course(VPL,$courseid);
        $instance=$this->vpl->get_instance();
        $vplid = $instance->id;
        foreach ($listcm as $aux){
            if($aux->instance != $vplid){
                $vpl = new mod_vpl($aux->id);
                $basedonlist[$aux->instance] = $vpl->get_printable_name();
            }
        }
        asort($basedonlist);
        $basedonlist[0]=get_string('select');
        $mform->addElement('select', 'basedon', $strbasedon, $basedonlist);
        $mform->setDefault('basedon',$instance->basedon);
        $mform->addElement('selectyesno','run',get_string('run',VPL));
        $mform->setDefault('run',$instance->run);
        $mform->addElement('selectyesno','debug',get_string('debug',VPL));
        $mform->setDefault('debug',$instance->debug);
        $mform->addElement('selectyesno','evaluate',get_string('evaluate',VPL));
        $mform->setDefault('evaluate',$instance->evaluate);
        $mform->addElement('selectyesno','evaluateonsubmission',get_string('evaluateonsubmission',VPL));
        $mform->setDefault('evaluateonsubmission',$instance->evaluateonsubmission);
        $mform->disabledIf('evaluateonsubmission', 'evaluate', 'eq', 0);
        $mform->addElement('selectyesno','automaticgrading',get_string('automaticgrading',VPL));
        $mform->setDefault('automaticgrading',$instance->automaticgrading);
        $mform->disabledIf('automaticgrading', 'evaluate', 'eq', 0);

        $mform->addElement('submit','saveoptions',get_string('saveoptions',VPL));
    }
}

require_login();

$id = required_param('id',PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('forms/executionoptions.php', array('id' => $id));
vpl_include_jsfile('hideshow.js');
$vpl->require_capability(VPL_MANAGE_CAPABILITY);
//Display page
$vpl->print_header(get_string('execution',VPL));
$vpl->print_heading_with_help('executionoptions');
$vpl->print_configure_tabs(basename(__FILE__));
$course = $vpl->get_course();
$fgp = $vpl->get_execution_fgm();

$mform = new mod_vpl_executionoptions_form('executionoptions.php',$vpl);
if ($fromform=$mform->get_data()){
    if(isset($fromform->saveoptions)){
        $instance = $vpl->get_instance();
        \mod_vpl\event\vpl_execution_options_updated::log($vpl);
        $instance->basedon = $fromform->basedon;
        $instance->run = $fromform->run;
        $instance->debug = $fromform->debug;
        $instance->evaluate = $fromform->evaluate;
        $instance->evaluateonsubmission = $fromform->evaluate && $fromform->evaluateonsubmission;
        $instance->automaticgrading = $fromform->evaluate && $fromform->automaticgrading;
        if($DB->update_record(VPL,$instance)){
            vpl_notice(get_string('optionssaved',VPL));
        }
        else{
            vpl_error(get_string('optionsnotsaved',VPL));
        }
    }
}
\mod_vpl\event\vpl_execution_options_viewed::log($vpl);
$mform->display();
$vpl->print_footer();
