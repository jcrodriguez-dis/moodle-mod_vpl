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
 * Manage running tasks
 *
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Only ONE task per user
 *
 * @author juanca
 */
class vpl_running_processes {
    const TABLE = 'vpl_running_processes';
    static public function get($userid) {
        global $DB;
        return $DB->get_record( self::TABLE, array (
                'userid' => $userid
        ) );
    }
    static public function set($userid, $server, $vplid, $adminticket) {
        global $DB;
        $info = new stdClass();
        $info->userid = $userid;
        $info->server = $server;
        $info->vpl = $vplid;
        $info->start_time = time();
        $info->adminticket = $adminticket;
        vpl_truncate_running_processes( $info );
        return $DB->insert_record( self::TABLE, $info );
    }
    static public function delete($userid, $adminticket=false) {
        global $DB;
        $parms = array('userid' => $userid);
        if ($adminticket) {
            $parms['adminticket'] = $adminticket;
        }
        $DB->delete_records( self::TABLE, $parms );
    }
    static public function lanched_processes($courseid) {
        global $DB;
        // Clean old processes.
        // TODO: save the maximum time and delete based on it
        $old = time() - (60 * 60); // One hour.
        $DB->delete_records_select(self::TABLE, "start_time < ?", array($old));

        $sql = 'SELECT {vpl_running_processes}.* FROM {vpl_running_processes}';
        $sql .= ' INNER JOIN {vpl} ON {vpl_running_processes}.vpl = {vpl}.id';
        $sql .= ' WHERE {vpl}.course = ?;';
        $param = array ( $courseid );
        return $DB->get_records_sql( $sql, $param );
    }
}
