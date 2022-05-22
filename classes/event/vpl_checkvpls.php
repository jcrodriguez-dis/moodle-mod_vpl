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

class vpl_checkvpls extends base {
    public static function get_objectid_mapping() {
        return array('db' => 'course', 'restore' => 'course');
    }
    public static function get_other_mapping() {
        // Nothing to map.
        return false;
    }
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'course';
    }

    public function get_url() {
        $param = array ( 'id' => $this->data['objectid'] );
        return new \moodle_url( 'mod/vpl/views/checkvpls.php',  $param);
    }
    public function get_description() {
        $desc = 'The user with id ' . $this->userid . ' ' . $this->action;
        $desc .= ' ' . 'checking all VPL activities of course id ' . $this->objectid;
        return $desc;
    }
}
