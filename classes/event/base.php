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

require_once(dirname( __FILE__ ) . '/../../locallib.php');
defined( 'MOODLE_INTERNAL' ) || die();
abstract class base extends \core\event\base {
    protected $legacyaction = '';
    protected function get_url_base($script) {
        $parms = array (
                'id' => $this->contextinstanceid
        );
        if (($this->relateduserid) && $this->relateduserid != $this->userid) {
            $parms ['userid'] = $this->relateduserid;
        }
        return new \moodle_url( 'mod/vpl/' . $script, $parms );
    }
    public function get_description() {
        return '';
    }
    public function get_url() {
        return null;
    }
    public static function log($eventinfo) {
        $event = self::create( $eventinfo );
        $event->trigger();
    }
    public function get_legacy_logdata() {
        $urltext = '';
        $url = $this->get_url();
        if ($url != null) {
            $urltext = $url->out( false );
        }
        return array (
                $this->courseid,
                VPL,
                $this->legacyaction,
                $urltext,
                $this->get_description(),
                $this->contextinstanceid
        );
    }
}
