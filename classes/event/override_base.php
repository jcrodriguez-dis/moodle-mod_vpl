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
 * Class for logging of override updated events
 *
 * @package mod_vpl
 * @copyright 2014 onwards Juan Carlos Rodríguez-del-Pino, 2021 Astor Bizard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_vpl\event;

defined( 'MOODLE_INTERNAL' ) || die();
require_once(dirname( __FILE__ ) . '/../../locallib.php');

/**
 * The base class for VPL overrides events.
 */
class override_base extends base {
    /**
     * Get the object ID mapping.
     *
     * @return array The object ID mapping.
     */
    public static function get_objectid_mapping() {
        return ['db' => VPL_OVERRIDES, 'restore' => VPL_OVERRIDES];
    }
    /**
     * Get the other mapping.
     *
     * @return bool False, indicating there is nothing to map.
     */
    public static function get_other_mapping() {
        // Nothing to map.
        return false;
    }
    /**
     * Initialize the override event.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = VPL_OVERRIDES;
    }
    /**
     * Log an override event.
     *
     * @param mixed $vpl The VPL instance or an array of information.
     * @param int|null $overrideid The override ID.
     */
    public static function log($vpl, $overrideid = null) {
        if (is_array($vpl)) {
            $info = $vpl;
        } else {
            $vplinstance = $vpl->get_instance();
            $info = [
                    'objectid' => $overrideid,
                    'context' => $vpl->get_context(),
                    'courseid' => $vplinstance->course,
                    'other' => ['vplid' => $vplinstance->id],
            ];
        }
        parent::log( $info );
    }
    /**
     * Get the URL for the override.
     *
     * @return \moodle_url The URL object.
     */
    public function get_url() {
        return $this->get_url_base( 'view.php' );
    }
    /**
     * Get the description of the override event for a specific action.
     *
     * @param string $mod The action.
     * @return string The description of the override.
     */
    public function get_description_mod($mod) {
        $desc = 'The user with id ' . $this->userid . ' ' . $mod;
        $desc .= ' override with id ' . $this->objectid . ' of VPL activity with id ' . $this->other['vplid'];
        if ($this->relateduserid) {
            $desc .= ' for user with id ' . $this->relateduserid;
        }
        return $desc;
    }
}
