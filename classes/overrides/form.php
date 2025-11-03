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
 * Form to select users and groups for an override.
 */
class form extends \moodleform {
    /**
     * @var ?array $users Array of users to select from, or null if not applicable.
     */
    protected $users;

    /**
     * @var ?array $groups Array of groups to select from, or null if not applicable.
     */
    protected $groups;

    /**
     * Constructor
     *
     * @param ?array $users Array of users to select from, or null if not applicable.
     * @param ?array $groups Array of groups to select from, or null if not applicable.
     */
    public function __construct($users, $groups) {
        $this->users = $users;
        $this->groups = $groups;
        parent::__construct();
        $this->_form->updateAttributes(['id' => 'vpl_override_users_form']);
    }

    /**
     * Defines the form elements for selecting users and groups.
     *
     * This method adds autocomplete fields for users and groups to the form.
     */
    protected function definition() {
        global $CFG;
        $mform = &$this->_form;
        foreach (['users', 'groups'] as $field) {
            if ($this->$field !== null) {
                $mform->addElement('html', '<div>');
                $mform->addElement('autocomplete', $field, get_string($field), $this->$field, ['multiple' => true]);
                $mform->addElement('html', '</div>');
            }
        }
    }
}
