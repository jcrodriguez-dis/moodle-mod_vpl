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

require_once(dirname( __FILE__ ) . '/../../locallib.php');
defined( 'MOODLE_INTERNAL' ) || die();
class submission_graded extends submission_base {
    protected function init() {
        parent::init();
        $this->data ['crud'] = 'c';
        $this->data ['edulevel'] = self::LEVEL_TEACHING;
        $this->legacyaction = 'grade';
    }
    protected function get_description_mod($mod) {
        $desc = 'The user with id ' . $this->userid . ' ' . $this->action;
        $desc .= ' ' . $mod . ' VPL submission with id ' . $this->objectid;
        $desc .= ' of user with id ' . $this->relateduserid;
        return $desc;
    }
    public function get_description() {
        return $this->get_description_mod( '' );
    }
}
