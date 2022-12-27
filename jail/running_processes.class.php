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
require_once( __DIR__ . '/jailserver_manager.class.php');

/**
 * Class that manage the Table of running processes.
 *
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

class vpl_running_processes {
    const TABLE = 'vpl_running_processes';
    /**
     * Returns record of a running process (type run, debug or evaluate).
     *
     * @param int $userid User id of the process.
     * @param ?int $vplid VPL activity id (optional).
     * @param ?string $adminticket Admin ticket of the process (optional).
     * @return (object|false) Record with the process information.
     */
    public static function get_run(int $userid, ?int $vplid = null, ?string $adminticket = null) {
        global $DB;
        $select = 'userid = :userid AND type <> 3';
        $params = ['userid' => $userid];
        if ( $vplid !== null ) {
            $params['vpl'] = $vplid;
            $select .= ' AND vpl = :vpl';
        }
        if ( $adminticket !== null ) {
            $params['adminticket'] = $adminticket;
            $select .= ' AND adminticket = :adminticket';
        }
        return $DB->get_record_select(self::TABLE, $select, $params);
    }

    /**
     * For a user and (optional) a VPL activity returns directruns.
     * @param int $userid
     * @param (int|null) $vplid
     * @return array processes records
     */
    public static function get_directrun(int $userid, ?int $vplid = null) {
        global $DB;
        $params = [ 'userid' => $userid, 'type' => 3 ];
        if ($vplid !== null) {
            $params['vpl'] = $vplid;
        }
        return $DB->get_records( self::TABLE, $params );
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
     * @param Object $data {userid, server, vplid, adminticket}
     * @return int Process id in the DB table
     */
    public static function set(object $data) {
        global $DB;
        $data->start_time = time();
        vpl_truncate_running_processes( $data );
        return $DB->insert_record( self::TABLE, $data );
    }

    public static function delete(int $userid, int $vplid, ?string $adminticket = null) {
        global $DB;
        $parms = ['userid' => $userid, 'vpl' => $vplid];
        if ($adminticket !== null) {
            $parms['adminticket'] = $adminticket;
        }
        $DB->delete_records( self::TABLE, $parms );
    }
    /**
     * Returns records of processes registered in a course
     * @param int $courseid ID of the course
     * @return array[] Array of DB records.
     */
    public static function lanched_processes(int $courseid) {
        global $DB;
        $sql = 'SELECT {vpl_running_processes}.* FROM {vpl_running_processes}';
        $sql .= ' INNER JOIN {vpl} ON {vpl_running_processes}.vpl = {vpl}.id';
        $sql .= ' WHERE {vpl}.course = ?;';
        $param = [ $courseid ];
        return $DB->get_records_sql( $sql, $param );
    }

    /**
     * Cleans table removing old processes
     */
    public static function remove_old_processes(int $timeout) {
        global $DB;
        $timelimit = time() - $timeout;
        $sql = 'SELECT * FROM {vpl_running_processes} WHERE start_time < ?';
        $param = [ $timelimit ];
        $oldprocesses = $DB->get_records_sql( $sql, $param, 0, 20);
        foreach ($oldprocesses as $processinfo) {
            $server = $processinfo->server;
            $data = new stdClass();
            $data->adminticket = $processinfo->adminticket;
            $request = vpl_jailserver_manager::get_action_request( 'stop', $data);
            $error = '';
            vpl_jailserver_manager::get_response( $server, $request, $error );
            $DB->delete_records(self::TABLE, ['id' => $processinfo->id]);
        }
    }
}
