<?php
/**
 * @version		$Id: jailserver_manager.class.php,v 1.14 2012-06-05 23:22:13 juanca Exp $
 * @package		VPL. jailservers manager class definition
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/**
 * vpl_jailserver_manager is a utility class to manage
 * the jail servers. get_Server if the key function
 *
 */
class vpl_jailserver_manager{
	const recheck=300; //300 sec = 5 min ¿optional setable?
	const table='vpl_jailservers';

	/**
	 * Check if the server is tagged as down
	 * @param url $server
	 * @return boolean
	 */
	static private function is_checkable($server){
		global $DB;
		$info= $DB->get_record(self::table,array('server' => $server));
		if($info != null){
			if($info->lastfail+self::recheck > time()){
				return false;
			}
		}
		return true;
	}

	/**
	 * Tag the server as down
	 * @param URL $server
	 * @param string $strerror
	 * @return void
	 */
	static private function server_fail($server,$strerror){
		global $DB;
		if($strerror==null){
			$strerror='';
		}
		$info= $DB->get_record(self::table,array('server' => $server));
		if($info != null){
			$info->lastfail=time();
			$info->laststrerror=$strerror;
			$info->nfails++;
			$DB->update_record(self::table,$info);
		}else{
			$info = new stdClass();
			$info->server = $server;
			$info->lastfail=time();
			$info->laststrerror=$strerror;
			$info->nfails=1;
			$DB->insert_record(self::table,$info);
		}
	}
	
	/**
	 * Return the defined server list
	 * @param string $localserverlisttext='' List of local server in text
	 * @return array of servers
	 */
	static function get_server_list($localserverlisttext){
		global $CFG;
		$nl_local = vpl_detect_newline($localserverlisttext);
		$nl_global = vpl_detect_newline($CFG->vpl_jail_servers);
		$tempserverlist = array_merge(explode($nl_local,$localserverlisttext),
									explode($nl_global,$CFG->vpl_jail_servers));
		$serverlist = array();
		//Clean temp server list and search for 'end_of_jails'
		foreach ($tempserverlist as $server) {
			$server = trim($server);
			if($server>'' && $server[0]!='#'){
				if(strtolower($server) == 'end_of_jails'){
					break;
				}else{
					$serverlist[] = $server;
				}
			}
		}
		return $serverlist;
	}

	/**
	 * Return a valid server to be used, May tag some servers as faulty
	 * @param int $maxmemory requiered
	 * @param string $localserverlisttext='' List of local server in text
	 * @param string $feedback info about jail servers response
	 * @return URL
	 */
	static function get_server($maxmemory,$localserverlisttext='', &$feedback=null){
		if(!function_exists('xmlrpc_encode_request')){
			throw new Exception('PHP XMLRPC requiered');
		}
		$serverlist = vpl_jailserver_manager::get_server_list($localserverlisttext);
		shuffle($serverlist);
		$data = new stdClass();
		$data->maxmemory=$maxmemory;
		$requestReady = xmlrpc_encode_request('status',$data);
		$feedback = '';
		foreach (array(0=>false,1=>true) as $checkall){
			foreach ($serverlist as $server) {
				if($checkall || vpl_jailserver_manager::is_checkable($server)){
					$cause='';
					$fail = false;
					$http = new vpl_HTTP_request($requestReady);
					if($http->try_server($server,4)){
						$raw_response=$http->get_response();
						$response = xmlrpc_decode($raw_response);
						if(is_array($response)){
							if(xmlrpc_is_fault ($response)){
								$cause = 'xmlrpc is fault: '.s($response["faultString"]);
								$fail = true;
							}else{
								$status=$response['status'];
								if($status == 'ready'){
									return $server;
								}else if($status!='busy'){
									$cause = 'busy or without resources to acept the request';
								}else{
									$fail = true;
									$cause = 'status: '.s($status);
								}
							}
						}else{
							$cause = 'http error '.s($raw_response);
							$fail = true;
						}
					}else{
						$cause =  'request failed: '.s($http->get_error());
						$fail = true;
					}
					if($fail){
						vpl_jailserver_manager::server_fail($server,$cause);
					}
					$feedback .= $server.' '.$cause."\n";
				}
			}
		}
		return false;
	}

	/**
	 * Clear servers table and check for every one again
	 * @return array of server object with info about server status
	 */
	static function check_servers($localserverlisttext=''){
		global $CFG;
		global $DB;
		if(!function_exists('xmlrpc_encode_request')){
			throw new Exception('PHP XMLRPC requiered');
		}
		$data = new stdClass();
		$data->maxmemory=(int)$CFG->vpl_maxexememory;
		$requestReady = xmlrpc_encode_request('status',$data);
		$serverlist = array_unique(vpl_jailserver_manager::get_server_list($localserverlisttext));
		$feedback = array();
		foreach ($serverlist as $server) {
			$cause='';
			$fail = false;
			$http = new vpl_HTTP_request($requestReady);
			if($http->try_server($server,4)){
				$raw_response=$http->get_response();
				if($http->is_connected() && $raw_response==null){
					$http->close_handles();
					$cause =  'timeout';
					$fail = true;
				}else{
					$response = xmlrpc_decode($raw_response);
					if(is_array($response)){
						if(xmlrpc_is_fault ($response)){
							$cause = 'xmlrpc is fault: '.s($response["faultString"]);
							$fail = true;
						}else{
							$status=$response['status'];
							$cause = s($status);
						}
					}else{
						$cause = 'http error '.s($raw_response);
						$fail = true;
					}
				}
			}else{
				$cause =  'request failed: '.s($http->get_error());
				$fail = true;
			}
			$info= $DB->get_record(self::table,array('server' => $server));
			if($fail){
				vpl_jailserver_manager::server_fail($server,$cause);
			}
			if($info == null){
				$info = new stdClass();
				$info->server = $server;
				$info->lastfail=null;
				$info->laststrerror='';
				$info->nfails=0;
			}
			$info->current_status=$cause;
			$info->offline = $fail;
			$feedback[]=$info;
		}
		return $feedback;
	}
}

?>