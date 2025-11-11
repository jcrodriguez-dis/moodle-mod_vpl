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
 * Base class for logging check all vpls of a course
 *
 * @package mod_vpl
 * @copyright 2017 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;

/**
 * Event class for checking all VPL activities in a course.
 * This class is used to log the event when a user checks all VPL activities in a course.
 */
class vpl_checkvpls extends base {
    /**
     * Returns the legacy action for this event.
     * This method is used to define the legacy action that corresponds to this event.
     * In this case, it is set to 'check all vpls'.
     *
     * @return string The legacy action for this event.
     */
    public static function get_objectid_mapping() {
        return ['db' => 'course', 'restore' => 'course'];
    }

    /**
     * Returns the mapping for other data.
     * This method is used to define how other data associated with the event should be mapped.
     * In this case, there is no other data to map, so it returns false.
     *
     * @return bool|false Returns false as there is no other data to map.
     */
    public static function get_other_mapping() {
        // Nothing to map.
        return false;
    }

    /**
     * Initializes the event.
     * This method is called when the event is created.
     * It sets the action, data, and other properties of the event.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'course';
    }

    /**
     * Returns the URL associated with this event.
     * This method is used to provide a URL that can be used to view the event in Moodle.
     *
     * @return \moodle_url The URL associated with this event.
     */
    public function get_url() {
        $param = [ 'id' => $this->data['objectid'] ];
        return new \moodle_url('mod/vpl/views/checkvpls.php', $param);
    }

    /**
     * Returns the event description.
     * This method is used to provide a human-readable description of the event.
     *
     * @return string Description of the event.
     */
    public function get_description() {
        $desc = 'The user with id ' . $this->userid . ' ' . $this->action;
        $desc .= ' ' . 'checking all VPL activities of course id ' . $this->objectid;
        return $desc;
    }
}
