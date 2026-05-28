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

defined('MOODLE_INTERNAL') || die();

use mod_vpl\util\activity_modes;

require_once(dirname(__FILE__) . '/../../course/moodleform_mod.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/vpl.class.php');

/**
 * VPL instance form
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
class mod_vpl_mod_form extends moodleform_mod {
    /**
     * Get array for select activitymode
     * @return array
     */
    protected function get_modes(): array {
        $order = [
            activity_modes::NORMAL,
            activity_modes::NOSTUDENTS,
            activity_modes::STUDENTSREADONLY,
            activity_modes::BASEDON,
            activity_modes::VPLQUESTION,
            activity_modes::EXAMPLE,
        ];
        $ret = [];
        foreach ($order as $activitymode) {
            $in18str = activity_modes::get_i18n_key($activitymode);
            $ret[$activitymode] = get_string($in18str, VPL);
        }
        return $ret;
    }

    /**
     * Define the form elements.
     */
    protected function definition() {
        global $CFG;
        $plugincfg = get_config('mod_vpl');
        $mform = & $this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), [
                'size' => '50',
        ]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->applyFilter('name', 'trim');
        $mform->addElement('textarea', 'shortdescription', get_string('shortdescription', VPL), [
                'cols' => 70,
                'rows' => 1,
        ]);
        $mform->setType('shortdescription', PARAM_RAW);
        $this->standard_intro_elements(get_string('fulldescription', VPL));
        $mform->addElement('header', 'submissionperiod', get_string('submissionperiod', VPL));
        $secondsday = 24 * 60 * 60;
        $now = time();
        $inittime = round($now / $secondsday) * $secondsday + 5 * 60;
        $endtime = $inittime + (8 * $secondsday) - 5 * 60;
        $mform->addElement('date_time_selector', 'startdate', get_string('startdate', VPL), [
                'optional' => true,
        ]);
        $mform->setDefault('startdate', 0);
        $mform->addElement('date_time_selector', 'duedate', get_string('duedate', VPL), [
                'optional' => true,
        ]);
        $mform->setDefault('duedate', $endtime);

        $mform->addElement('header', 'submissionrestrictions', get_string('submissionrestrictions', VPL));
        $mform->addElement('text', 'maxfiles', get_string('maxfiles', VPL));
        $mform->setType('maxfiles', PARAM_INT);
        $mform->setDefault('maxfiles', 1);
        $mform->addElement('select', 'worktype', get_string('worktype', VPL), [
                0 => get_string('individualwork', VPL),
                1 => get_string('groupwork', VPL),
        ]);
        $mform->addElement('select', 'activity_mode', get_string('activity_mode', VPL), $this->get_modes());
        $mform->setDefault('activity_mode', activity_modes::NORMAL);
        $mform->addHelpButton('activity_mode', 'activity_mode', VPL);
        foreach (activity_modes::CONTROL_VIEW as $activitymode) {
            $mform->hideIf('visible', 'activity_mode', 'eq', $activitymode);
            $mform->hideIf('visiblegrade', 'activity_mode', 'eq', $activitymode);
        }
        foreach (activity_modes::NO_GRADE as $activitymode) {
            $mform->hideIf('grade', 'activity_mode', 'eq', $activitymode);
        }
        $mform->addElement('selectyesno', 'restrictededitor', get_string('restrictededitor', VPL));
        $mform->setDefault('restrictededitor', false);
        $mform->setAdvanced('restrictededitor');
        $max = \mod_vpl\util\phpconfig::get_post_max_size();
        if ($plugincfg->maxfilesize > 0 && $plugincfg->maxfilesize < $max) {
            $max = $plugincfg->maxfilesize;
        }
        $mform->addElement('select', 'maxfilesize', get_string('maxfilesize', VPL), vpl_get_select_sizes(16 * 1024, $max));
        $mform->setType('maxfilesize', PARAM_INT);
        $mform->setDefault('maxfilesize', 1);
        $mform->setAdvanced('maxfilesize');
        $mform->addElement('passwordunmask', 'password', get_string('password'));
        $mform->setType('password', PARAM_TEXT);
        $mform->setAdvanced('password');
        $mform->addElement('text', 'requirednet', get_string('requirednet', VPL), [
                'size' => '60',
        ]);
        $mform->setType('requirednet', PARAM_TEXT);
        $mform->setDefault('requirednet', '');
        $mform->addHelpButton('requirednet', 'requirednet', VPL);
        $mform->setAdvanced('requirednet');
        $mform->addElement('header', 'seb', get_string('seb', VPL));
        $mform->setExpanded('seb', false);
        foreach (\mod_vpl\seb\settings::get_form_fields() as $field => $definition) {
                $label = get_string($definition['label'], VPL);
                if ($definition['type'] === 'select') {
                        $mform->addElement('select', $field, $label, $definition['options']);
                } elseif ($definition['type'] === 'textarea') {
                        $mform->addElement('textarea', $field, $label, [
                                        'cols' => 66,
                                        'rows' => 2,
                        ]);
                } else {
                        $mform->addElement($definition['type'], $field, $label);
                }
                $mform->setType($field, $definition['param']);
                $mform->setDefault($field, $definition['default']);
                if (in_array($field, ['requiresafeexambrowser', 'allowedbrowserexamkeys'], true)) {
                        $mform->addHelpButton($field, $definition['label'], VPL);
                }
                if ($field !== 'requiresafeexambrowser') {
                        $mform->hideIf($field, 'requiresafeexambrowser', 'eq', 0);
                }
        }
        $mform->addElement('hidden', 'sebrequired', 0);
        $mform->setType('sebrequired', PARAM_INT);
        $mform->addElement('hidden', 'sebkeys', '');
        $mform->setType('sebkeys', PARAM_TEXT);
        // Grade.
        $this->standard_grading_coursemodule_elements();
        $mform->addElement('text', 'reductionbyevaluation', get_string('reductionbyevaluation', VPL));
        $mform->setType('reductionbyevaluation', PARAM_TEXT);
        $mform->setDefault('reductionbyevaluation', 0);
        $mform->addHelpButton('reductionbyevaluation', 'reductionbyevaluation', VPL);
        $mform->addElement('text', 'freeevaluations', get_string('freeevaluations', VPL));
        $mform->setType('freeevaluations', PARAM_INT);
        $mform->setDefault('freeevaluations', 0);
        $mform->addHelpButton('freeevaluations', 'freeevaluations', VPL);
        $mform->addElement('selectyesno', 'visiblegrade', get_string('visiblegrade', VPL));
        $mform->setDefault('visiblegrade', 1);
        // Standard course elements.
        $this->standard_coursemodule_elements();
        // End form.
        $this->add_action_buttons();
    }

    /**
     * Add SEB table values to the edit form defaults.
     *
     * @param array $defaultvalues Default form values.
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        if (empty($this->current->instance)) {
            return;
        }

        $sebvalues = \mod_vpl\seb\settings::get_form_values_from_instance((object)[
                'id' => $this->current->instance,
                'sebrequired' => $defaultvalues['sebrequired'] ?? 0,
                'sebkeys' => $defaultvalues['sebkeys'] ?? '',
        ]);

        foreach (\mod_vpl\seb\settings::get_form_fields() as $field => $definition) {
            if (isset($sebvalues->$field)) {
                $defaultvalues[$field] = $sebvalues->$field;
            }
        }
    }

    /**
     * Validate a field against a regular expression pattern.
     *
     * This method checks if the given field's value matches the specified pattern.
     *
     * @param string $field The name of the field to validate.
     * @param string $pattern The regular expression pattern to match against.
     * @param string $message The error message to set if validation fails.
     * @param array $data The form data array.
     * @param array $errors The errors array to populate with validation errors.
     */
    public function validate($field, $pattern, $message, &$data, &$errors) {
        $data[$field] = trim($data[$field]);
        $res = preg_match($pattern, $data[$field]);
        if ($res == 0 || $res == false) {
            $errors[$field] = $message;
        }
    }

    /**
     * Form validation.
     *
     * This method validates the form data and checks for specific patterns in the input fields.
     *
     * @param array $data The submitted form data.
     * @param array $files The uploaded files (not used in this form).
     * @return array An array of errors, if any.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $this->validate('freeevaluations', '/^[0-9]*$/', '[0..]', $data, $errors);
        $this->validate('maxfiles', '/^[0-9]*$/', '[0..]', $data, $errors);
        $this->validate('reductionbyevaluation', '/^[0-9]*(\.[0-9]+)?%?$/', '#[.#][%]', $data, $errors);
        if (!empty($data['requiresafeexambrowser']) && !empty($data['enablesebsession']) &&
                !empty($data['preventsebsimultaneoussessions']) &&
                trim((string)($data['sebteacherpassword'] ?? '')) === '') {
            $errors['sebteacherpassword'] = get_string('required');
        }
        return $errors;
    }
}
