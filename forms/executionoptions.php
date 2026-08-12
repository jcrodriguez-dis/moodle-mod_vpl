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

use mod_vpl\plugininfo\vplevaluator;
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../locallib.php');
require_once(__DIR__ . '/../vpl.class.php');
require_once(__DIR__ . '/../vpl_submission_CE.class.php');

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Class to define the form for setting execution options in VPL
 *
 * This form allows users to configure execution options such as run scripts,
 * debug scripts, evaluators, run modes, and evaluation modes for a VPL instance.
 */
class mod_vpl_executionoptions_form extends \moodleform {
    /**
     * @var \mod_vpl The VPL instance for which the execution options are being set.
     */
    public $vpl;

    /**
     * Constructor for the execution options form.
     *
     * This constructor initializes the form with the VPL instance and prepares
     * the page for displaying the form.
     *
     * @param \moodle_page $page The page object.
     * @param \mod_vpl $vpl The VPL instance.
     */
    public function __construct($page, $vpl) {
        $this->vpl = $vpl;
        parent::__construct($page);
    }

    /**
     * Returns the script description from the file.
     *
     * This method reads the content of the specified script file and extracts
     * the description using a regular expression. It returns an empty string
     * if the file cannot be read or if no description is found.
     *
     * @param string $filename The path to the script file.
     * @return string The script description or an empty string if not found.
     */
    protected function get_scriptdescription($filename) {
        $data = file_get_contents($filename);
        if ($data === false) {
            return '';
        }
        $matches = [];
        $result = preg_match('/@vpl_script_description (.*)$/im', $data, $matches);
        if ($result) {
            return ': ' . s($matches[1]);
        }
        return '';
    }

    /**
     * Returns a list of scripts available in the specified directory.
     *
     * This method scans the given directory for files ending with the specified
     * suffix and returns an associative array of script names with their descriptions.
     *
     * @param string $dir The directory to scan for scripts.
     * @param string $endwith The suffix that the script files should end with.
     * @return array An associative array of script names with their descriptions.
     */
    protected function get_dirlist($dir, $endwith) {
        $avoid = ['default' => 1];
        $el = strlen($endwith);
        $dirlist = scandir($dir);
        if ($dirlist === false) {
            return [];
        }
        $list = [];
        foreach ($dirlist as $file) {
            if (substr($file, - $el) == $endwith) {
                $name = substr($file, 0, - $el);
                if (! isset($avoid[$name])) {
                    $list[$name] = strtoupper($name) . $this->get_scriptdescription($dir . '/' . $file);
                }
            }
        }
        return $list;
    }

    /**
     * Returns a list of run scripts available for the VPL instance.
     *
     * This method retrieves the available run scripts and formats them
     * for selection in the form. It also handles inheritance from the closest
     * set field in the base chain.
     *
     * @return array An associative array of run scripts with their names.
     */
    protected function get_runlist() {
        return $this->get_dirlist(vpl_get_scripts_dir(), '_run.sh');
    }

    /**
     * Returns a list of debug scripts available for the VPL instance.
     *
     * This method retrieves the available debug scripts and formats them
     * for selection in the form. It also handles inheritance from the closest
     * set field in the base chain.
     *
     * @return array An associative array of debug scripts with their names.
     */
    protected function get_debuglist() {
        return $this->get_dirlist(vpl_get_scripts_dir(), '_debug.sh');
    }

    /**
     * Returns a list of run modes available for the VPL instance.
     *
     * This method retrieves the available run modes and formats them
     * for selection in the form. It also handles inheritance from the closest
     * set field in the base chain.
     *
     * @return array An associative array of run modes with their names.
     */
    protected function get_run_modelist() {
        $runlist = [];
        $runlist[''] = get_string('default');
        $runlist['1'] = get_string('run_mode:default', COMPVPL);
        $runlist['2'] = get_string('run_mode:text', COMPVPL);
        $runlist['3'] = get_string('run_mode:gui', COMPVPL);
        $runlist['4'] = get_string('run_mode:webapp', COMPVPL);
        $runlist['5'] = get_string('run_mode:textingui', COMPVPL);
        $inherit = $this->vpl->get_closest_set_field_in_base_chain('run_mode', '');
        if ($inherit && isset($runlist[$inherit])) {
            $runlist[''] = get_string('inheritvalue', COMPVPL, $runlist[$inherit]);
        }
        return $runlist;
    }

    /**
     * Returns a list of evaluation modes available for the VPL instance.
     *
     * @return array An associative array of evaluation modes with their names.
     */
    protected function get_evaluation_modelist() {
        $evaluationlist = [];
        $evaluationlist[''] = get_string('default');
        $evaluationlist['1'] = get_string('evaluation_mode:default', COMPVPL);
        $evaluationlist['2'] = get_string('evaluation_mode:textingui', COMPVPL);
        $inherit = $this->vpl->get_closest_set_field_in_base_chain('evaluation_mode', '');
        if ($inherit && isset($evaluationlist[$inherit])) {
            $evaluationlist[''] = get_string('inheritvalue', COMPVPL, $evaluationlist[$inherit]);
        }
        return $evaluationlist;
    }

    /**
     * Returns a list of evaluators available for the VPL instance and their help links.
     *
     * @param \mod_vpl $vpl The VPL instance.
     * @return array An associative array of evaluators with their help links.
     */
    protected static function get_evaluator_help_list($vpl) {
        $evaluators = vplevaluator::get_enabled_plugins();
        $evaluatorshelplist = ['' => ''];
        foreach ($evaluators as $evaluatorname) {
            $link = vplevaluator::get_printable_evaluator_help_link($vpl, $evaluatorname, true);
            $evaluatorshelplist[$evaluatorname] = $link;
        }
        return $evaluatorshelplist;
    }

    /**
     * Generate JavaScript code to show evaluator help based on selection.
     * This method creates a JavaScript function that updates the help section
     * when the evaluator selection changes.
     * @param \mod_vpl $vpl The VPL instance.
     * @return string JavaScript code as a string.
     */
    public static function get_evaluatorhelp_js($vpl) {
        $evaluatorshelplist = self::get_evaluator_help_list($vpl);
        return "(function() {
            var htmlMap = " . json_encode($evaluatorshelplist) . ";
            function updateEvaluatorHelp() {
                var evaluatorSelect = document.getElementById('id_evaluator');
                var evaluatorHelpLink = document.getElementById('id_evaluatorhelplink');
                if (!evaluatorSelect || !evaluatorHelpLink) {
                    return;
                }
                try {
                    var val = evaluatorSelect.value;
                    var html = htmlMap[val] || '';
                    evaluatorHelpLink.innerHTML = html;
                    if (html === '') {
                        evaluatorHelpLink.style.display = 'none';
                    } else {
                        evaluatorHelpLink.style.display = '';
                    }
                } catch (e) {
                    console.error('Error updating evaluator help:', e);
                }
            }
            function initEvaluatorHelp() {
                var evaluatorSelect = document.getElementById('id_evaluator');
                if (evaluatorSelect) {
                    evaluatorSelect.addEventListener('change', updateEvaluatorHelp);
                    updateEvaluatorHelp(); // Trigger initial update
                }
            }
            // Wait for page load before setting up event listeners
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initEvaluatorHelp);
            } else {
                initEvaluatorHelp();
            }
        })();";
    }

    /**
     * Returns a list of evaluators available for the VPL instance.
     *
     * This method retrieves the list of enabled evaluators and formats them
     * for selection in the form. It also handles inheritance from the closest
     * set field in the base chain.
     *
     * @param bool $customized Indicates if the evaluation script is customized.
     * @return array An associative array of evaluators with their names.
     */
    protected function get_evaluatorlist($customized) {
        $evaluators = vplevaluator::get_enabled_plugins();
        $evaluatorslist = ['' => $customized ? get_string('customizedscript', COMPVPL) : get_string('default')];
        foreach ($evaluators as $evaluator) {
            $evaluatorslist[$evaluator] = get_string('pluginname', "vplevaluator_{$evaluator}");
        }
        $inherit = $this->vpl->get_closest_set_field_in_base_chain('evaluator', '');
        if ($inherit && isset($evaluatorslist[$inherit])) {
            $evaluatorslist[''] = get_string('inheritvalue', COMPVPL, $evaluatorslist[$inherit]);
        }
        return $evaluatorslist;
    }

    /**
     * Returns a list of activities of the course to be used as based on selection.
     *
     * This method retrieves the list of activities in the course.
     *
     * @return array An associative array of activities with their names.
     */
    protected function get_basedonlist() {
        $courseid = $this->vpl->get_course()->id;
        $listcm = get_coursemodules_in_course(VPL, $courseid);
        $usedbasedonlist = [];
        $allactivities = [];
        foreach ($listcm as $aux) {
            $vpl = new \mod_vpl($aux->id);
            $instance = $vpl->get_instance();
            $allactivities[$instance->id] = $vpl;
            $usedbasedonlist[$instance->basedon] = 1;
        }
        // Remove current activity.
        unset($allactivities[$this->vpl->get_instance()->id]);
        $modebasedonlist = [];
        $basedonlist = [];
        $otherslist = [];
        foreach ($allactivities as $id => $vpl) {
            $name = $vpl->get_printable_name();
            if ($vpl->is_mode(\mod_vpl\util\activity_modes::BASEDON)) {
                $modebasedonlist[$id] = '↖🛡️ ' . $name;
            } else if (isset($usedbasedonlist[$id])) {
                $basedonlist[$id] = '↖ ' . $name;
            } else {
                $otherslist[$id] = $name;
            }
        }
        asort($modebasedonlist);
        asort($basedonlist);
        asort($otherslist);
        return [0 => get_string('select')] + $modebasedonlist + $basedonlist + $otherslist;
    }

    /**
     * Defines the form elements for execution options.
     *
     * This method sets up the form fields for configuring execution options
     * such as based on another VPL instance, run script, debug script, evaluator,
     * run mode, evaluation mode, and various execution flags.
     */
    protected function definition() {
        $mform = & $this->_form;
        $id = $this->vpl->get_course_module()->id;
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('header', 'header_execution_options', get_string('executionoptions', COMPVPL));
        $strbasedon = get_string('basedon', COMPVPL);
        $instance = $this->vpl->get_instance();
        $mform->addElement('select', 'basedon', $strbasedon, $this->get_basedonlist());
        $mform->setDefault('basedon', $instance->basedon);
        $mform->addHelpButton('basedon', 'basedon', COMPVPL);
        $customized = $this->vpl->get_customized_scripts();

        $strautodetect = get_string('autodetect', COMPVPL);
        $strrunscript = get_string('runscript', COMPVPL);
        $inheritedrun = strtoupper($this->vpl->get_closest_set_field_in_base_chain('runscript', ''));
        $strrundefault = $inheritedrun ? get_string('inheritvalue', COMPVPL, $inheritedrun) : $strautodetect;
        if ($customized['run']) {
            $runlist = [$instance->runscript => get_string('customizedscript', COMPVPL)];
        } else {
            $runlist = array_merge(['' => $strrundefault], $this->get_runlist());
        }
        $mform->addElement('select', 'runscript', $strrunscript, $runlist);
        $mform->setDefault('runscript', $instance->runscript);
        $mform->addHelpButton('runscript', 'runscript', COMPVPL);

        $inheriteddebug = strtoupper($this->vpl->get_closest_set_field_in_base_chain('debugscript', ''));
        $strdebugdefault = $inheriteddebug ? get_string('inheritvalue', COMPVPL, $inheriteddebug) : $strautodetect;
        $strdebugscript = get_string('debugscript', COMPVPL);
        if ($customized['debug']) {
            $debuglist = [$instance->debugscript => get_string('customizedscript', COMPVPL)];
        } else {
            $debuglist = array_merge(['' => $strdebugdefault], $this->get_debuglist());
        }
        $mform->addElement('select', 'debugscript', $strdebugscript, $debuglist);
        $mform->setDefault('debugscript', $instance->debugscript);
        $mform->addHelpButton('debugscript', 'debugscript', COMPVPL);

        // Show evaluator selection and help link.
        $strevaluator = get_string('evaluator', COMPVPL);
        $mform->addElement('select', 'evaluator', $strevaluator, $this->get_evaluatorlist($customized['evaluate']));
        $mform->setDefault('evaluator', $instance->evaluator);
        $mform->addHelpButton('evaluator', 'evaluator', COMPVPL);
        $mform->addElement('static', 'evaluatorhelplink', '', '<span id="id_evaluatorhelplink"></span>');
        $mform->hideIf('evaluatorhelplink', 'evaluator', 'eq', '');

        // Store previous evaluator.
        $mform->addElement('hidden', 'previousevaluator', $instance->evaluator);
        $mform->setType('previousevaluator', PARAM_ALPHANUMEXT);
        // Show evaluator settings.
        if ($instance->evaluator > '') {
            try {
                $evaluator = vplevaluator::get_evaluator($instance->evaluator, $this->vpl);
                if ($evaluator->has_settings()) {
                    $evaluator->add_form_settings($this->vpl, $this);
                }
            } catch (\Exception $e) {
                $message = get_string('error:savingevaluatorsettings', COMPVPL, $instance->evaluator);
                vpl_notice(s($message), 'error');
            }
        }

        $strrunmode = get_string('run_mode', COMPVPL);
        $runmodelist = $this->get_run_modelist();
        $mform->addElement('select', 'run_mode', $strrunmode, $runmodelist);
        $mform->setDefault('run_mode', $instance->run_mode);
        $mform->addHelpButton('run_mode', 'run_mode', COMPVPL);

        $strevaluatemode = get_string('evaluation_mode', COMPVPL);
        $evaluatemodelist = $this->get_evaluation_modelist();
        $mform->addElement('select', 'evaluation_mode', $strevaluatemode, $evaluatemodelist);
        $mform->setDefault('evaluation_mode', $instance->evaluation_mode);
        $mform->addHelpButton('evaluation_mode', 'evaluation_mode', COMPVPL);

        $mform->addElement('selectyesno', 'run', get_string('run', COMPVPL));
        $mform->setDefault('run', $instance->run);
        $mform->addHelpButton('run', 'run', COMPVPL);
        $mform->addElement('selectyesno', 'debug', get_string('debug', COMPVPL));
        $mform->setDefault('debug', $instance->debug);
        $mform->addHelpButton('debug', 'debug', COMPVPL);
        $mform->addElement('selectyesno', 'evaluate', get_string('evaluate', COMPVPL));
        $mform->setDefault('evaluate', $instance->evaluate);
        $mform->addHelpButton('evaluate', 'evaluate', COMPVPL);
        $mform->addElement('selectyesno', 'evaluateonsubmission', get_string('evaluateonsubmission', COMPVPL));
        $mform->setDefault('evaluateonsubmission', $instance->evaluateonsubmission);
        $mform->disabledIf('evaluateonsubmission', 'evaluate', 'eq', 0);
        $mform->addHelpButton('evaluateonsubmission', 'evaluateonsubmission', COMPVPL);
        $mform->addElement('selectyesno', 'automaticgrading', get_string('automaticgrading', COMPVPL));
        $mform->setDefault('automaticgrading', $instance->automaticgrading);
        $mform->disabledIf('automaticgrading', 'evaluate', 'eq', 0);
        $mform->addHelpButton('automaticgrading', 'automaticgrading', COMPVPL);

        $mform->addElement('submit', 'saveoptions', get_string('saveoptions', COMPVPL));
    }
}

$id = required_param('id', PARAM_INT);
mod_vpl::require_login($id);
$vpl = new \mod_vpl($id);
$vpl->prepare_page('forms/executionoptions.php', [ 'id' => $id ]);
$vpl->require_capability(VPL_MANAGE_CAPABILITY);

vpl_include_jsfile('hideshow.js');
echo vpl_include_js(\mod_vpl_executionoptions_form::get_evaluatorhelp_js($vpl));
// Display page.
$vpl->print_header(get_string('execution', COMPVPL));
$vpl->print_heading_with_help('executionoptions');

$mform = new \mod_vpl_executionoptions_form('executionoptions.php', $vpl);
if ($fromform = $mform->get_data()) {
    if (isset($fromform->saveoptions)) {
        $instance = $vpl->get_instance();
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

        if ($vpl->update()) {
            // Save evaluator settings.
            if ($instance->evaluator > '' && $instance->evaluator == $fromform->previousevaluator) {
                try {
                    $evaluator = vplevaluator::get_evaluator($instance->evaluator, $vpl);
                    if ($evaluator->has_settings()) {
                        $evaluator->save_form_settings($vpl, $fromform);
                    }
                } catch (\Exception $e) {
                    $message = get_string('error:savingevaluatorsettings', COMPVPL, $instance->evaluator);
                    vpl_notice(s($message), 'error');
                }
            }
            \mod_vpl\event\vpl_execution_options_updated::log($vpl);
            vpl_notice(get_string('optionssaved', COMPVPL));
        } else {
            vpl_notice(get_string('optionsnotsaved', COMPVPL), 'error');
            $vpl->print_footer();
            die();
        }
    }
}
\mod_vpl\event\vpl_execution_options_viewed::log($vpl);
$mform->display();
$vpl->print_footer();
