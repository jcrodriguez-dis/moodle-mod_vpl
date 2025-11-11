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
 * Base class for logging of vpl instance related events
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
 * Base event class for VPL events.
 * This class is used to log events related to VPL instances, such as viewing or modifying them.
 */
class vpl_base extends base {
    /**
     * Returns the object ID mapping for this event.
     * This method is used to define how the object ID associated with the event should be mapped.
     * In this case, it maps the VPL instance ID to the database and restore.
     *
     * @return array Returns an array with 'db' and 'restore' keys mapping to VPL.
     */
    public static function get_objectid_mapping() {
        return ['db' => VPL, 'restore' => VPL];
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
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = VPL;
    }

    /**
     * Logs the event.
     * This method is used to log the event when a VPL instance is viewed or modified.
     *
     * @param \mod_vpl|array $vpl The VPL instance to log.
     */
    public static function log($vpl) {
        if (is_array($vpl)) {
            parent::log($vpl);
        } else {
            $einfo = [
                    'objectid' => $vpl->get_instance()->id,
                    'context' => $vpl->get_context(),
            ];
            parent::log($einfo);
        }
    }

    /**
     * Returns the URL associated with this event.
     * This method is used to provide a URL that can be used to view the event in Moodle.
     *
     * @return \moodle_url The URL associated with this event.
     */
    public function get_url() {
        return $this->get_url_base('view.php');
    }

    /**
     * Returns the description of the event.
     * This method is used to provide a human-readable description of the event.
     *
     * @param string $mod The type of modification (e.g., 'updated', 'deleted').
     * @return string Description of the event.
     */
    public function get_description_mod($mod) {
        $desc = 'The user with id ' . $this->userid . ' ' . $this->action;
        $desc .= ' ' . $mod . ' of VPL activity with id ' . $this->objectid;
        if (($this->relateduserid) && $this->relateduserid != $this->userid) {
            $desc .= ' for user with id ' . $this->relateduserid;
        }
        return $desc;
    }
}
