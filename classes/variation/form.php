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
     * @var int $number Number of the variation in the page
     */
    protected $number;
    /**
     * Constructor
     * @param object $page the page where the form will be displayed
     * @param int $number the number of the variation in the page
     * @param int $varid the id of the variation to edit, -1 for new variation
     */
    public function __construct($page, $number = 0, $varid = 0) {
        $this->number = $number;
        $this->varid = $varid;
        parent::__construct($page);
    }

    /**
     * Defines the form elements
     */
    protected function definition() {
        $mform = & $this->_form;
        if ($this->number > 0) {
            $title = get_string('variation_n', VPL, "{$this->number}");
        } else {
            $title = get_string('add');
        }
        $mform->addElement('header', 'variation', $title);
        $mform->addElement('hidden', 'varid', $this->varid);
        $mform->setType('varid', PARAM_INT);

        $mform->addElement('text', 'identification', get_string('varidentification', VPL), [
                'size' => '20',
        ]);
        $mform->setDefault('identification', '');
        $mform->setType('identification', PARAM_RAW);
        $fieldname = 'description'; // Allows multile editors in page.
        $mform->addElement('editor', $fieldname, get_string('description', VPL));
        $mform->setType($fieldname, PARAM_RAW);
        $mform->setDefault($fieldname, '');

        $buttongroup = [];
        $buttongroup[] = $mform->createElement('submit', 'save', get_string('save', VPL));
        $buttongroup[] = $mform->createElement('submit', 'cancel', get_string('cancel'));
        if ($this->number > 0) {
            $menssage = addslashes(get_string('delete'));
            $onclick = 'onclick="return confirm(\'' . $menssage . '\')"';
            $buttongroup[] = $mform->createElement('submit', 'delete', get_string('delete'), $onclick);
        }
        $mform->addGroup($buttongroup);
    }
}
