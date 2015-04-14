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

require_once(dirname( __FILE__ ) . '/../../locallib.php');
defined( 'MOODLE_INTERNAL' ) || die();
class vpl_base extends base {
    protected function init() {
        $this->data ['crud'] = 'u';
        $this->data ['edulevel'] = self::LEVEL_TEACHING;
        $this->data ['objecttable'] = VPL;
    }
    public static function log($vpl) {
        if (is_array( $vpl )) {
            parent::log( $vpl );
        } else {
            $einfo = array (
                    'objectid' => $vpl->get_instance()->id,
                    'context' => $vpl->get_context()
            );
            parent::log( $einfo );
        }
    }
    public function get_url() {
        return $this->get_url_base( 'view.php' );
    }
    public function get_description_mod($mod) {
        $desc = 'The user with id ' . $this->userid . ' ' . $this->action;
        $desc .= ' ' . $mod . ' of VPL activity with id ' . $this->objectid;
        if (($this->relateduserid) && $this->relateduserid != $this->userid) {
            $desc .= ' for user with id ' . $this->relateduserid;
        }
        return $desc;
    }
}
