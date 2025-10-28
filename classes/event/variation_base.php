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
 * Class for logging of variation updated events
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
 * Base event class for variation-related events.
 * This class is used to log events related to variations in the VPL module.
 */
class variation_base extends base {
    /**
     * Returns the object ID mapping for the event.
     * This method is used to define how the object ID associated with the event should be mapped.
     * In this case, it maps the object ID to the VPL variations table.
     *
     * @return array Returns an array with 'db' and 'restore' keys pointing to VPL_VARIATIONS.
     */
    public static function get_objectid_mapping() {
        return ['db' => VPL_VARIATIONS, 'restore' => VPL_VARIATIONS];
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
     * It sets the CRUD action, educational level, and object table for the event.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = VPL_VARIATIONS;
    }

    /**
     * Logs the variation event.
     * This method is used to log the event when a variation is updated, deleted, or viewed.
     *
     * @param \mod_vpl $vpl The VPL instance.
     * @param int $varid The ID of the variation.
     * @param int|null $userid The ID of the user related to the event (optional).
     */
    public static function logvpl($vpl, $varid, $userid = null) {
        global $USER;
        $vplinstance = $vpl->get_instance();
        $info = [
            'objectid' => $varid,
            'context' => $vpl->get_context(),
            'courseid' => $vplinstance->course,
            'userid' => $USER->id,
            'relateduserid' => $userid,
            'other' => ['vplid' => $vplinstance->id],
        ];
        parent::log($info);
    }

    /**
     * Returns the URL for viewing the variation.
     * This method is used to generate the URL for viewing the variation in the VPL module.
     *
     * @return string The URL for viewing the variation.
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
        $desc = 'The user with id ' . $this->userid . ' ' . $mod;
        $desc .= ' variation with id ' . $this->objectid . ' of VPL activity with id ' . $this->other['vplid'];
        if ($this->relateduserid) {
            $desc .= ' for user with id ' . $this->relateduserid;
        }
        return $desc;
    }
}
