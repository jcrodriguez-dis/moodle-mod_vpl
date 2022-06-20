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

/**
 * Only ONE task per user
 *
 * @author juanca
 */
class vpl_running_processes {
    const TABLE = 'vpl_running_processes';
    public static function get($userid, $vplid = false, $adminticket = false) {
        global $DB;
        $params = array ( 'userid' => $userid);
        if ( $vplid !== false ) {
            $params['vpl'] = $vplid;
        }
        if ( $adminticket !== false ) {
            $params['adminticket'] = $adminticket;
        }
        return $DB->get_record( self::TABLE, $params );
    }

    /**
     * Returns process info by id.
     * @param int $vplid VPL id.
     * @param int $userid User id.
     * @param int $id Process record id.
     * @return object
     */
    public static function get_by_id(int $vplid, int $userid, int $id) {
        global $DB;
        $params = ['id' => $id, 'vpl' => $vplid, 'userid' => $userid];
        return $DB->get_record( self::TABLE, $params );
    }

    /**
     * Adds a proccess information to the vpl_running_processes DB table.
     *
     * @param int $userid User id
     * @param string $server URL to execution server
     * @param int $vplid VPL activity id
     * @param string $adminticket to control the process
     * @return int Process id in the DB table
     */
    public static function set($userid, $server, $vplid, $adminticket) {
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

    public static function delete($userid, $vplid, $adminticket=false) {
        global $DB;
        $parms = array('userid' => $userid, 'vpl' => $vplid);
        if ($adminticket) {
            $parms['adminticket'] = $adminticket;
        }
        $DB->delete_records( self::TABLE, $parms );
    }
    public static function lanched_processes($courseid) {
        global $DB;
        // Clean old processes.
        // TODO: save the maximum time and delete based on it.
        $old = time() - (60 * 60); // One hour.
        $DB->delete_records_select(self::TABLE, "start_time < ?", array($old));

        $sql = 'SELECT {vpl_running_processes}.* FROM {vpl_running_processes}';
        $sql .= ' INNER JOIN {vpl} ON {vpl_running_processes}.vpl = {vpl}.id';
        $sql .= ' WHERE {vpl}.course = ?;';
        $param = array ( $courseid );
        return $DB->get_records_sql( $sql, $param );
    }
}
