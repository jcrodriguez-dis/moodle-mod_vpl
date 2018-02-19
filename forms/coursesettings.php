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
 * Execution options form
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once($CFG->libdir.'/formslib.php');

class mod_vpl_coursesettings_form extends moodleform {
    protected $vpl;
    public function __construct($page, $vpl) {
        $this->vpl = $vpl;
        parent::__construct( $page );
    }
    protected function definition() {
        $mform = & $this->_form;
        $id = $this->vpl->get_course_module()->id;
        $mform->addElement( 'hidden', 'id', $id );
        $mform->setType( 'id', PARAM_INT );
        $mform->addElement( 'selectyesno', 'useracetheme', get_string( 'useracetheme', VPL ) );
        $mform->setDefault( 'useracetheme', false );
        $mform->addHelpButton('useracetheme', 'useracetheme', VPL);
        $mform->addElement( 'selectyesno', 'studentaccessprevious', get_string( 'studentaccessprevious', VPL ) );
        $mform->setDefault( 'studentaccessprevious', false );
        $mform->addHelpButton('studentaccessprevious', 'studentaccessprevious', VPL);
        $mform->addElement( 'selectyesno', 'gradebookshortfeedback', get_string( 'gradebookshortfeedback', VPL ) );
        $mform->setDefault( 'gradebookshortfeedback', false );
        $mform->addHelpButton('gradebookshortfeedback', 'gradebookshortfeedback', VPL);
        $mform->addElement( 'selectyesno', 'hidesubmissionform', get_string( 'hidesubmissionform', VPL ) );
        $mform->setDefault( 'hidesubmissionform', false );
        $mform->addHelpButton('hidesubmissionform', 'hidesubmissionform', VPL);
        $mform->addElement( 'selectyesno', 'notimelimit', get_string( 'notimelimit', VPL ) );
        $mform->setDefault( 'notimelimit', false );
        $mform->addHelpButton('notimelimit', 'notimelimit', VPL);
        $mform->addElement( 'submit', 'saveoptions', get_string( 'saveoptions', VPL ) );
    }
}

require_login();

$id = required_param( 'id', PARAM_INT );
$vpl = new mod_vpl( $id );
$vpl->prepare_page( 'forms/coursesttings.php', array ( 'id' => $id ) );
$vpl->require_capability( VPL_MANAGE_CAPABILITY );
$course = $vpl->get_course();
$PAGE->navbar->add( get_string( 'coursesttings', VPL ) );
// Display page.
echo $OUTPUT->header();
echo $OUTPUT->heading( get_string( 'coursesttings', VPL ) );

$course = $vpl->get_course();
$mform = new mod_vpl_coursesettings_form( 'coursesttings.php', $vpl );
if ($fromform = $mform->get_data()) {
    if (isset( $fromform->saveoptions )) {
        $instance = $vpl->get_instance();
        \mod_vpl\event\vpl_execution_options_updated::log( $vpl );
        $instance->basedon = $fromform->basedon;
        $instance->runscript = $fromform->runscript;
        $instance->debugscript = $fromform->debugscript;
        $instance->run = $fromform->run;
        $instance->debug = $fromform->debug;
        $instance->evaluate = $fromform->evaluate;
        $instance->evaluateonsubmission = $fromform->evaluate && $fromform->evaluateonsubmission;
        $instance->automaticgrading = $fromform->evaluate && $fromform->automaticgrading;
        if ( $vpl->update() ) {
            vpl_notice( get_string( 'optionssaved', VPL ) );
        } else {
            vpl_notice( get_string( 'optionsnotsaved', VPL ), 'error' );
        }
    }
}
\mod_vpl\event\vpl_execution_options_viewed::log( $vpl );
$mform->display();
$vpl->print_footer();
