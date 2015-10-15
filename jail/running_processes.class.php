<?php
/**
 * @copyright	2013 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/**
 * Only ONE task per user
 * @author juanca
 *
 */
 class vpl_running_processes{
	const table='vpl_running_processes';

	static public function get($userid){
		global $DB;
		return $DB->get_record(self::table,array('userid' => $userid));
	}

	static public function set($userid,$server,$vplid,$adminticket){
		global $DB;
		$info = new stdClass();
		$info->userid = $userid;
		$info->server = $server;
		$info->vpl = $vplid;
		$info->start_time=time();
		$info->adminticket = $adminticket;
		vpl_truncate_RUNNING_PROCESSES($info);
		return $DB->insert_record(self::table,$info);
	}

	static public function delete($userid){
		global $DB;
		$DB->delete_records(self::table, array('userid' => $userid));
	}
}
