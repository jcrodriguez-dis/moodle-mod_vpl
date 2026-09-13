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
 * Variation definitions form
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl\variation;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class to define variation add and edit form
 */
class form extends \moodleform {
    /**
     * @var int $varid the id of the variation to edit, -1 for new variation
     */
    protected $varid;

    /**
     * @var string $action the action being performed (add, edit, delete)
     */
    protected $action;
    /**
     * Constructor
     * @param object $page the page where the form will be displayed
     * @param string $action the action being performed (add, edit, delete)
     * @param int $varid the id of the variation to edit, -1 for new variation
     * @param string $varname the name of the variation
     */
    public function __construct($page, $action, $varid = 0, $varname = '') {
        $this->action = $action;
        $this->varid = $varid;
        parent::__construct($page);
    }

    /**
     * Defines the form elements
     */
    protected function definition() {
        $mform = & $this->_form;
        if ($this->action === 'add') {
            $title = get_string('add');
        } else {
            $title = get_string('variation_n', VPL, "{$this->varid}");
        }
        $mform->addElement('header', 'variation', $title);
        $mform->addElement('hidden', 'varid', $this->varid);
        $mform->addElement('hidden', 'action', $this->action);
        $mform->setType('varid', PARAM_INT);
        $mform->setType('action', PARAM_TEXT);
        $identificationoptions = ['size' => 20];
        $identificationstr = get_string('varidentification', VPL);
        $mform->addElement('text', 'identification', $identificationstr, $identificationoptions);
        $mform->setDefault('identification', '');
        $mform->setType('identification', PARAM_RAW);
        $mform->disabledIf('identification', 'action', 'eq', 'delete');

        $descriptionoptions = ['rows' => $this->action === 'delete' ? 2 : 10];
        $descriptionstr = get_string('description', VPL);
        $mform->addElement('editor', 'description', $descriptionstr, $descriptionoptions);
        $mform->setType('description', PARAM_RAW);
        $mform->setDefault('description', '');
        $mform->disabledIf('description', 'action', 'eq', 'delete');

        $buttongroup = [];
        if ($this->action === 'delete') {
            $buttongroup[] = $mform->createElement('submit', 'delete', get_string('delete'));
        } else {
            $buttongroup[] = $mform->createElement('submit', 'save', get_string('save', VPL));
        }
        $buttongroup[] = $mform->createElement('submit', 'cancel', get_string('cancel'));
        $mform->addGroup($buttongroup);
    }
}
