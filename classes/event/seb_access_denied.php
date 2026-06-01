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
 * Event class for denied SEB access attempts.
 *
 * @package mod_vpl
 * @copyright 2026 onwards Alejandro David Arzola Saavedra
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author  Alejandro David Arzola Saavedra <alejandro.arzola101@alu.ulpgc.es>
 */
namespace mod_vpl\event;

defined('MOODLE_INTERNAL') || die();

class seb_access_denied extends base {
    /**
     * Init method for setting event properties.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'vpl';
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventsebaccessdenied', 'mod_vpl');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $reason = (string)($this->other['reason'] ?? 'unknown');
        return "El usuario con id {$this->userid} no pudo acceder a una actividad VPL con SEB. Motivo: {$reason}.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/vpl/view.php', array('id' => $this->contextinstanceid));
    }

    /**
     * Custom data for legacy log.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return array($this->courseid, 'vpl', 'seb_access_denied', 'view.php?id=' . $this->contextinstanceid, $this->userid, $this->contextinstanceid);
    }
}