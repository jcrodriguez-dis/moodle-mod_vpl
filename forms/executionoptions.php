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
global $CFG;
require_once($CFG->libdir.'/formslib.php');

class mod_vpl_executionoptions_form extends moodleform {
    protected $vpl;
    public function __construct($page, $vpl) {
        $this->vpl = $vpl;
        parent::__construct( $page );
    }
    protected function get_scriptdescription($filename) {
        $data = file_get_contents($filename);
        if ($data === false ) {
            return '';
        }
        $matches = [];
        $result = preg_match('/@vpl_script_description (.*)$/im', $data, $matches);
        if ( $result ) {
            return ': ' . $matches[1];
        }
        return '';
    }
    protected function get_dirlist($dir, $endwith) {
        $avoid = ['default' => 1];
        $el = strlen($endwith);
        $dirlist = scandir($dir);
        $list = [];
        foreach ($dirlist as $file) {
            if ( substr($file, - $el) == $endwith) {
                $name = substr($file, 0, - $el);
                if ( ! isset( $avoid[$name] ) ) {
                    $list[$name] = strtoupper($name) . $this->get_scriptdescription($dir . '/' . $file);
                }
            }
        }
        return $list;
    }

    protected function get_runlist() {
        return $this->get_dirlist(vpl_get_scripts_dir(), '_run.sh');
    }

    protected function get_debuglist() {
        return $this->get_dirlist(vpl_get_scripts_dir(), '_debug.sh');
    }

    protected function get_run_modelist() {
        $runlist = [];
        $runlist[''] = get_string('default');
        $runlist['1'] = get_string('run_mode:default', VPL);
        $runlist['2'] = get_string('run_mode:text', VPL);
        $runlist['3'] = get_string('run_mode:gui', VPL);
        $runlist['4'] = get_string('run_mode:webapp', VPL);
        $runlist['5'] = get_string('run_mode:textingui', VPL);
        $inherit = $this->vpl->get_closest_set_field_in_base_chain('run_mode', '');
        if ($inherit && isset($runlist[$inherit])) {
            $runlist[''] = get_string('inheritvalue', VPL, $runlist[$inherit]);
        }
        return $runlist;
    }
    protected function get_evaluation_modelist() {
        $evalutionlist = [];
        $evalutionlist[''] = get_string('default');
        $evalutionlist['1'] = get_string('evaluation_mode:default', VPL);
        $evalutionlist['2'] = get_string('evaluation_mode:textingui', VPL);
        $inherit = $this->vpl->get_closest_set_field_in_base_chain('evaluation_mode', '');
        if ($inherit && isset($evalutionlist[$inherit])) {
            $evalutionlist[''] = get_string('inheritvalue', VPL, $evalutionlist[$inherit]);
        }
        return $evalutionlist;
    }
    protected function get_evaluatorlist() {
        $evaluators = \mod_vpl\plugininfo\vplevaluator::get_enabled_plugins();
        $evaluatorslist = ['' => get_string('default')];
        foreach ($evaluators as $evaluator) {
            $evaluatorslist[$evaluator] = get_string('pluginname', "vplevaluator_{$evaluator}");
        }
        $inherit = $this->vpl->get_closest_set_field_in_base_chain('evaluator', '');
        if ($inherit && isset($evaluatorslist[$inherit])) {
            $evaluatorslist[''] = get_string('inheritvalue', VPL, $evaluatorslist[$inherit]);
        }
        return $evaluatorslist;
    }

    protected function definition() {
        $mform = & $this->_form;
        $id = $this->vpl->get_course_module()->id;
        $mform->addElement( 'hidden', 'id', $id );
        $mform->setType( 'id', PARAM_INT );
        $mform->addElement( 'header', 'header_execution_options', get_string( 'executionoptions', VPL ) );
        $strbasedon = get_string( 'basedon', VPL );
        $basedonlist = [];
        $basedonlist[0] = '';
        $courseid = $this->vpl->get_course()->id;
        $listcm = get_coursemodules_in_course( VPL, $courseid );
        $instance = $this->vpl->get_instance();
        $vplid = $instance->id;
        foreach ($listcm as $aux) {
            if ($aux->instance != $vplid) {
                $vpl = new mod_vpl( $aux->id );
                $basedonlist[$aux->instance] = $vpl->get_printable_name();
            }
        }
        asort( $basedonlist );
        $basedonlist[0] = get_string( 'select' );
        $mform->addElement( 'select', 'basedon', $strbasedon, $basedonlist );
        $mform->setDefault( 'basedon', $instance->basedon );
        $mform->addHelpButton( 'basedon', 'basedon', VPL );

        $inheritedrun = strtoupper($this->vpl->get_closest_set_field_in_base_chain('runscript', ''));
        $inheriteddebug = strtoupper($this->vpl->get_closest_set_field_in_base_chain('debugscript', ''));
        $strrundefault = $inheritedrun ? get_string('inheritvalue', VPL, $inheritedrun) : get_string('autodetect', VPL);
        $strrunscript = get_string('runscript', VPL);
        $runlist = array_merge(['' => $strrundefault], $this->get_runlist());
        $mform->addElement( 'select', 'runscript', $strrunscript, $runlist );
        $mform->setDefault( 'runscript', $instance->runscript );
        $mform->addHelpButton('runscript', 'runscript', VPL);

        $strdebugdefault = $inheriteddebug ? get_string('inheritvalue', VPL, $inheriteddebug) : get_string('autodetect', VPL);
        $strdebugscript = get_string('debugscript', VPL);
        $debuglist = array_merge(['' => $strdebugdefault], $this->get_debuglist());
        $mform->addElement( 'select', 'debugscript', $strdebugscript, $debuglist );
        $mform->setDefault( 'debugscript', $instance->debugscript );
        $mform->addHelpButton('debugscript', 'debugscript', VPL);

        $strevaluator = get_string('evaluator', VPL);
        $mform->addElement( 'select', 'evaluator', $strevaluator, $this->get_evaluatorlist());
        $mform->setDefault( 'evaluator', $instance->evaluator );
        $mform->addHelpButton('evaluator', 'evaluator', VPL);

        $strrunmode = get_string( 'run_mode', VPL );
        $runmodelist = $this->get_run_modelist();
        $mform->addElement( 'select', 'run_mode', $strrunmode, $runmodelist );
        $mform->setDefault( 'run_mode', $instance->run_mode );
        $mform->addHelpButton('run_mode', 'run_mode', VPL);

        $strevaluatemode = get_string( 'evaluation_mode', VPL );
        $evaluatemodelist = $this->get_evaluation_modelist();
        $mform->addElement( 'select', 'evaluation_mode', $strevaluatemode, $evaluatemodelist );
        $mform->setDefault( 'evaluation_mode', $instance->evaluation_mode );
        $mform->addHelpButton('evaluation_mode', 'evaluation_mode', VPL);

        $mform->addElement( 'selectyesno', 'run', get_string( 'run', VPL ) );
        $mform->setDefault( 'run', $instance->run );
        $mform->addElement( 'selectyesno', 'debug', get_string( 'debug', VPL ) );
        $mform->setDefault( 'debug', $instance->debug );
        $mform->addElement( 'selectyesno', 'evaluate', get_string( 'evaluate', VPL ) );
        $mform->setDefault( 'evaluate', $instance->evaluate );
        $mform->addElement( 'selectyesno', 'evaluateonsubmission', get_string( 'evaluateonsubmission', VPL ) );
        $mform->setDefault( 'evaluateonsubmission', $instance->evaluateonsubmission );
        $mform->disabledIf( 'evaluateonsubmission', 'evaluate', 'eq', 0 );
        $mform->addHelpButton( 'evaluateonsubmission', 'evaluateonsubmission', VPL );
        $mform->addElement( 'selectyesno', 'automaticgrading', get_string( 'automaticgrading', VPL ) );
        $mform->setDefault( 'automaticgrading', $instance->automaticgrading );
        $mform->disabledIf( 'automaticgrading', 'evaluate', 'eq', 0 );
        $mform->addHelpButton( 'automaticgrading', 'automaticgrading', VPL );

        $mform->addElement( 'submit', 'saveoptions', get_string( 'saveoptions', VPL ) );
    }
}

require_login();

$id = required_param( 'id', PARAM_INT );
$vpl = new mod_vpl( $id );
$vpl->prepare_page( 'forms/executionoptions.php', [ 'id' => $id ] );
vpl_include_jsfile( 'hideshow.js' );
$vpl->require_capability( VPL_MANAGE_CAPABILITY );
// Display page.
$vpl->print_header( get_string( 'execution', VPL ) );
$vpl->print_heading_with_help( 'executionoptions' );

$mform = new mod_vpl_executionoptions_form( 'executionoptions.php', $vpl );
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
        $instance->evaluator = $fromform->evaluator;
        $instance->run_mode = $fromform->run_mode;
        $instance->evaluation_mode = $fromform->evaluation_mode;
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
