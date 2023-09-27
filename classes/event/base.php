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
 * Base class for logging
 *
 * @package mod_vpl
 * @copyright 2014 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;

defined( 'MOODLE_INTERNAL' ) || die();
require_once(dirname( __FILE__ ) . '/../../locallib.php');

/**
 * The base abstract class for VPL events.
 */
abstract class base extends \core\event\base {
    /**
     * The legacy action.
     *
     * @var string
     */
    protected $legacyaction = '';
    /**
     * Get the base URL for a script.
     *
     * @param string $script The script name.
     * @return \moodle_url The URL object.
     */
    protected function get_url_base($script) {
        $parms = [
                'id' => $this->contextinstanceid,
        ];
        if (($this->relateduserid) && $this->relateduserid != $this->userid) {
            $parms['userid'] = $this->relateduserid;
        }
        return new \moodle_url( '/mod/vpl/' . $script, $parms );
    }
    /**
     * Get the event description.
     *
     * @return string The event description.
     */
    public function get_description() {
        return '';
    }
    /**
     * Get the event URL.
     *
     * @return \moodle_url The event URL.
     */
    public function get_url() {
        return null;
    }
    /**
     * Log an event.
     *
     * @param array $eventinfo The event information.
     */
    public static function log($eventinfo) {
        $event = self::create( $eventinfo );
        $event->trigger();
    }
}
