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
 * VPL instance form
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
defined('MOODLE_INTERNAL') || die();
require_once $CFG->dirroot.'/course/moodleform_mod.php';
require_once dirname(__FILE__).'/lib.php';
require_once dirname(__FILE__).'/vpl.class.php';

class mod_vpl_mod_form extends moodleform_mod {
    function definition(){
        global $CFG;
        $plugincfg = get_config('mod_vpl');
        $mform = & $this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        // name
        $modname= 'vpl';
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'50'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->applyFilter('name','trim');
        // shortdescription
        $mform->addElement('textarea', 'shortdescription', get_string('shortdescription',VPL), array('cols'=>70, 'rows'=>1));
        $mform->setType('shortdescription', PARAM_RAW);
        if($CFG->version < 2015041700.00){ //Moodle version < 2.9Beta
            $this->add_intro_editor(false,get_string('fulldescription',VPL)); //deprecated from 2.9beta
        }else{
            $this->standard_intro_elements(get_string('fulldescription',VPL));
        }
        $mform->addElement('header', 'submissionperiod', get_string('submissionperiod', VPL));
        $secondsday=24*60*60;
        $now = time();
        $inittime = round($now / $secondsday) * $secondsday+5*60;
        $endtime = $inittime + (8*$secondsday) - 5*60;
        // startdate
        $mform->addElement('date_time_selector', 'startdate', get_string('startdate', VPL), array('optional'=>true));
        $mform->setDefault('startdate', 0);
        $mform->setAdvanced('startdate');
        // duedate
        $mform->addElement('date_time_selector', 'duedate', get_string('duedate', VPL), array('optional'=>true));
        $mform->setDefault('duedate', $endtime);
        // maxfiles
        $mform->addElement('header', 'submissionrestrictions', get_string('submissionrestrictions', VPL));
        $mform->addElement('text', 'maxfiles', get_string('maxfiles',VPL),array('size'=>'2'));
        $mform->setType('maxfiles', PARAM_INT);
        $mform->setDefault('maxfiles', 1);
        $mform->addElement('select', 'worktype', get_string('worktype',VPL),
                            array(0 => get_string('individualwork',VPL),1 => get_string('groupwork',VPL)));
        $mform->addElement('selectyesno', 'restrictededitor', get_string('restrictededitor',VPL));
        $mform->setDefault('restrictededitor', false);
        $mform->setAdvanced('restrictededitor');
        $mform->addElement('selectyesno', 'example', get_string('isexample',VPL));
        $mform->setDefault('example', false);
        $mform->setAdvanced('example');
        $max = vpl_get_max_post_size();
        if($plugincfg->maxfilesize > 0 && $plugincfg->maxfilesize < $max){
            $max = $plugincfg->maxfilesize;
        }
        $mform->addElement('select', 'maxfilesize', get_string('maxfilesize',VPL),
                            vpl_get_select_sizes(16*1024,$max));
        $mform->setType('maxfilesize', PARAM_INT);
        $mform->setDefault('maxfilesize', 0);
        $mform->setAdvanced('maxfilesize');
        $mform->addElement('passwordunmask', 'password', get_string('password'));
        $mform->setType('password', PARAM_TEXT);
        $mform->setAdvanced('password');
        $mform->addElement('text', 'requirednet', get_string('requirednet',VPL),array('size'=>'60'));
        $mform->setType('requirednet', PARAM_TEXT);
        $mform->setDefault('requirednet', '');
        $mform->setAdvanced('requirednet');
        // grade
        $this->standard_grading_coursemodule_elements();
        $mform->addElement('selectyesno', 'visiblegrade', get_string('visiblegrade',VPL));
        $mform->setDefault('visiblegrade', 1);
        //Standard course elements
        $this->standard_coursemodule_elements();
        // end form
        $this->add_action_buttons();
    }
    function display(){
        $id = optional_param('update',FALSE,PARAM_INT);
        if($id){
            $vpl = new mod_vpl($id);
            $vpl->print_configure_tabs('edit');
            if($vpl->get_grade_info() !== false){
                $vpl->get_instance()->visiblegrade = ($vpl->get_grade_info()->hidden)?0:1;
            }else{
                $vpl->get_instance()->visiblegrade = false;
            }
            $this->set_data($vpl->get_instance());
        }
        parent::display();
    }
}

