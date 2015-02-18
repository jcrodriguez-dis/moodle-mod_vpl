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
 * Base class for logging submission related events
 *
 * @package mod_vpl
 * @copyright 2014 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;

require_once(dirname( __FILE__ ) . '/../../locallib.php');
defined( 'MOODLE_INTERNAL' ) || die();
class submission_base extends base {
    protected function init() {
        $this->data ['crud'] = 'c';
        $this->data ['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data ['objecttable'] = VPL_SUBMISSIONS;
    }
    public function get_url() {
        return $this->get_url_base( 'forms/submissionview.php' );
    }
    protected function get_description_mod($mod) {
        $desc = 'The user with id ' . $this->userid . ' ' . $this->action;
        $desc .= ' ' . $mod . ' VPL submission with id ' . $this->objectid;
        if (isset( $this->relateduserid ) && $this->relateduserid > 0 && $this->relateduserid != $this->userid) {
            $desc .= ' of user with id ' . $this->relateduserid;
        }
        return $desc;
    }
    public static function log($submission) {
        if (is_array( $submission )) {
            parent::log( $submission );
        } else {
            global $USER;
            $subinstance = $submission->get_instance();
            $vpl = $submission->get_vpl();
            $einfo = array (
                    'objectid' => $subinstance->id,
                    'context' => $vpl->get_context(),
                    'relateduserid' => ($USER->id != $subinstance->userid ? $subinstance->userid : null)
            );
            parent::log( $einfo );
        }
    }
    public function get_description() {
        return $this->get_description_mod( '' );
    }
}
