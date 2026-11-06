<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Override definitions form
 *
 * @package mod_vpl
 * @copyright 2021 Astor Bizard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_vpl\overrides;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form to define override options.
 *
 * This form allows users to set override options such as start date, due date,
 * password, reduction by evaluation, and free evaluations.
 */
class options_form extends \moodleform {
    /**
     * @var int $id The VPL activity ID.
     */
    protected $id;

    /**
     * @var int $overrideid The override ID to edit, or 0 for a new override.
     */
    protected $overrideid;

    /**
     * @var string $action The action to perform ('save' or 'delete').
     */
    protected $action;

    /**
     * Constructor
     *
     * @param int $id The VPL activity ID.
     * @param int $overrideid The override ID to edit, or 0 for a new override.
     * @param string $action The action to perform ('new', 'edit' or 'delete').
     */
    public function __construct($id, $overrideid, $action) {
        $this->id = $id;
        $this->overrideid = $overrideid;
        $this->action = $action;
        parent::__construct();
        $this->_form->updateAttributes(['id' => 'vpl_override_options_form']);
    }

    /**
     * Defines the form elements for override options.
     */
    protected function definition() {
        global $OUTPUT;
        $mform = &$this->_form;
        $mform->addElement('hidden', 'id', $this->id);
        $mform->setType('id', PARAM_RAW);
        $mform->addElement('hidden', 'overrideid', $this->overrideid);
        $mform->setType('overrideid', PARAM_RAW);
        $mform->addElement('hidden', 'userids');
        $mform->setType('userids', PARAM_RAW);
        $mform->addElement('hidden', 'groupids');
        $mform->setType('groupids', PARAM_RAW);
        $mform->addElement('hidden', 'action', $this->action);
        $mform->setType('action', PARAM_TEXT);
        if ($this->action === 'delete') {
            $message = get_string('confirmoverridedeletion', 'mod_vpl');
            $confirm = $OUTPUT->notification($message, 'warning');
            $mform->addElement('html', '<div class="override-confirmation">' . $confirm . '</div>');
        }
        foreach (['startdate', 'duedate'] as $datefield) {
            $overridefield = 'override_' . $datefield;
            $mform->addElement('html', '<div class="override-option">');
            $mform->addElement('checkbox', $overridefield, get_string($datefield, VPL), get_string('override', VPL));
            $mform->addHelpButton($overridefield, 'override', VPL);
            $mform->disabledIf($overridefield, 'action', 'eq', 'delete');
            $mform->addElement('date_time_selector', $datefield, null, ['optional' => true]);
            $mform->disabledIf($datefield, $overridefield);
            $mform->disabledIf($datefield, 'action', 'eq', 'delete');
            $mform->addElement('html', '</div>');
        }

        $passwordfield = 'password';
        $overridefield = 'override_' . $passwordfield;
        $mform->addElement('html', '<div class="override-option">');
        $mform->addElement('checkbox', $overridefield, get_string($passwordfield), get_string('override', VPL));
        $mform->disabledIf($overridefield, 'action', 'eq', 'delete');
        $mform->addHelpButton($overridefield, 'override', VPL);
        $mform->addElement('passwordunmask', $passwordfield, null, ['optional' => true]);
        $mform->setType($passwordfield, PARAM_TEXT);
        $mform->setDefault($passwordfield, '');
        $mform->disabledIf($passwordfield, $overridefield);
        $mform->disabledIf($passwordfield, 'action', 'eq', 'delete');
        $mform->addElement('html', '</div>');

        foreach (['reductionbyevaluation', 'freeevaluations'] as $textfield) {
            $overridefield = 'override_' . $textfield;
            $mform->addElement('html', '<div class="override-option">');
            $mform->addElement('checkbox', $overridefield, get_string($textfield, VPL), get_string('override', VPL));
            $mform->addHelpButton($overridefield, 'override', VPL);
            $mform->disabledIf($overridefield, 'action', 'eq', 'delete');
            $mform->addElement('text', $textfield, null);
            $mform->setType($textfield, PARAM_TEXT);
            $mform->setDefault($textfield, 0);
            $mform->disabledIf($textfield, $overridefield);
            $mform->disabledIf($textfield, 'action', 'eq', 'delete');
            $mform->addElement('html', '</div>');
        }

        $this->add_action_buttons();
    }

    /**
     * Validate a field against a regular expression pattern.
     *
     * @param string $field The field name to validate.
     * @param string $pattern The regular expression pattern to match.
     * @param string $message The error message to display if validation fails.
     * @param array $data The data array containing the field value.
     * @param array $errors The errors array to store validation errors.
     */
    public static function validate($field, $pattern, $message, &$data, &$errors) {
        $data[$field] = trim($data[$field]);
        $res = preg_match($pattern, $data[$field]);
        if ($res == 0 || $res == false) {
            $errors[$field] = $message;
        }
    }

    /**
     * Validate the form data.
     *
     * @param array $data The submitted data.
     * @param array $files The submitted files.
     * @return array An array of errors, empty if no errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        self::validate('freeevaluations', '/^[0-9]*$/', '[0..]', $data, $errors);
        self::validate('reductionbyevaluation', '/^[0-9]*(\.[0-9]+)?%?$/', '#[.#][%]', $data, $errors);
        return $errors;
    }
}
