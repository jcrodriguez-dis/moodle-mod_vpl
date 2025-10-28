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
 * Class for logging submission graded events
 *
 * @package mod_vpl
 * @copyright 2014 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../locallib.php');

/**
 * Event class for when a submission is graded.
 * This class is used to log the event when a submission is graded in the VPL module.
 */
class submission_graded extends submission_base {
    /**
     * Initializes the event.
     * This method is called when the event is created.
     * It sets the CRUD action, educational level, and legacy action for the event.
     */
    protected function init() {
        parent::init();
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->legacyaction = 'grade';
    }

    /**
     * Returns the description of the event.
     * This method is used to provide a human-readable description of the event.
     *
     * @param string $mod The type of modification (e.g., 'updated', 'deleted').
     * @return string Description of the event.
     */
    protected function get_description_mod($mod) {
        $desc = 'The user with id ' . $this->userid . ' ' . $this->action;
        $desc .= ' ' . $mod . ' VPL submission with id ' . $this->objectid;
        $desc .= ' of user with id ' . $this->relateduserid;
        return $desc;
    }

    /**
     * Returns the description of the event.
     * This method is used to provide a human-readable description of the event.
     *
     * @return string Description of the event.
     */
    public function get_description() {
        return $this->get_description_mod('');
    }
}
