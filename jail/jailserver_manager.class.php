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
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/**
 * vpl_jailserver_manager is a utility class to manage
 * the jail servers. get_Server is the main feature
 *
 */
class vpl_jailserver_manager{
    const recheck=300; //300 sec = 5 min ¿optional setable?
    const table='vpl_jailservers';

    static public function get_curl($server,$request,$fresh=false){
        global $CFG;
        if(!function_exists('curl_init')){
            throw new Exception('PHP cURL requiered');
        }
        $plugincfg = get_config('mod_vpl');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $server);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/xml;charset=UTF-8', 'User-Agent: VPL 3.1.2'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        if( $fresh ){
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        }
        if( @$plugincfg->acceptcertificates ){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        if( isset($plugincfg->proxy) && strlen($plugincfg->proxy)>7 ){
            curl_setopt($ch, CURLOPT_PROXY, $plugincfg->proxy);
        }
        return $ch;
    }

    static public function get_response($server,$request, &$error=null,$fresh=false){
        $ch = vpl_jailserver_manager::get_curl($server,$request,$fresh);
        $raw_response=curl_exec($ch);
        if($raw_response === false){
            $error='request failed: '.s(curl_error($ch));
            curl_close($ch);
            return false;
        }else{
            curl_close($ch);
            $error='';
            $response = xmlrpc_decode($raw_response, "UTF-8");
            if(is_array($response)){
                if(xmlrpc_is_fault ($response)){
                    $error = 'xmlrpc is fault: '.s($response["faultString"]);
                }else{
                    return $response;
                }
            }else{
                $error = 'http error '.s(strip_tags($raw_response));
                $fail = true;
            }
            return false;
        }
    }
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
            vpl_truncate_JAILSERVERS($info);
            $DB->update_record(self::table,$info);
        }else{
            $info = new stdClass();
            $info->server = $server;
            $info->lastfail=time();
            $info->laststrerror=$strerror;
            $info->nfails=1;
            vpl_truncate_JAILSERVERS($info);
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
        $plugincfg = get_config('mod_vpl');
        $nl_local = vpl_detect_newline($localserverlisttext);
        $nl_global = vpl_detect_newline($plugincfg->jail_servers);
        $tempserverlist = array_merge(explode($nl_local,$localserverlisttext),
                                    explode($nl_global,$plugincfg->jail_servers));
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
        $requestReady = xmlrpc_encode_request('available',$data,array('encoding'=>'UTF-8'));
        $feedback = '';
        $plan_b = array();
        foreach ($serverlist as $server) {
            if(vpl_jailserver_manager::is_checkable($server)){
                $response=self::get_response($server,$requestReady,$error);
                if($response === false){
                    vpl_jailserver_manager::server_fail($server,$error);
                    $feedback .= parse_url($server,PHP_URL_HOST).' '.$error."\n";
                }elseif(!isset($response['status'])){
                    vpl_jailserver_manager::server_fail($server,$error);
                    $feedback .= parse_url($server,PHP_URL_HOST)." protocol error (No status)\n";
                }else{
                    if($response['status']=='ready')
                        return $server;
                }
            }else{
                $plan_b[]=$server;
            }
        }
        foreach ($plan_b as $server) {
            $response=self::get_response($server,$requestReady,$error,true);
            if($response === false){
                vpl_jailserver_manager::server_fail($server,$error);
                $feedback .= parse_url($server,PHP_URL_HOST).' '.$error."\n";
            }elseif(!isset($response['status'])){
                vpl_jailserver_manager::server_fail($server,$error);
                $feedback .= parse_url($server,PHP_URL_HOST)." protocol error (No status)\n";
            }else{
                if($response['status']=='ready')
                    return $server;
            }
        }
        return false;
    }

    /**
     * Check if a server is located in a private network
     * @return true == private
     */
    static function is_private_host($URL){
        $host_name=parse_url($URL,PHP_URL_HOST);
        if($host_name===false) return true;
        $private = '10., 127., 172.16.0.0/12, 192.168., 169.254.';
        $name = $host_name.'.';
        $IP = gethostbyname($name);
        if($IP != $name){
            return address_in_subnet($IP,$private);
            // IPv6 not implemented
            // fc00::/7
            // fe80::/10
        }
        return true;
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
        $data->maxmemory=(int)1024*10;
        $requestReady = xmlrpc_encode_request('available',$data,array('encoding'=>'UTF-8'));
        $serverlist = array_unique(vpl_jailserver_manager::get_server_list($localserverlisttext));
        $feedback = array();
        foreach ($serverlist as $server) {
            $response=self::get_response($server,$requestReady,$status);
            $info= $DB->get_record(self::table,array('server' => $server));
            if($response === false){
                vpl_jailserver_manager::server_fail($server,$status);
            }else{
                $status=s($response['status']);
            }
            if($info == null){
                $info = new stdClass();
                $info->server = $server;
                $info->lastfail=null;
                $info->laststrerror='';
                $info->nfails=0;
            }
            $info->current_status=$status;
            $info->offline = $response === false;
            if(self::is_private_host($server)){
                //TODO implement other way to warning
                $info->server = '[private] '.$info->server;
            }
            $feedback[]=$info;
        }
        return $feedback;
    }

    /**
     * Return the https URL servers list
     * @param string $localserverlisttext='' List of local server in text
     * @return array of URLs
     */
    static function get_https_server_list($localserverlisttext=''){
        global $CFG;
        global $DB;
        if(!function_exists('xmlrpc_encode_request')){
            throw new Exception('PHP XMLRPC requiered');
        }
        $data = new stdClass();
        $data->maxmemory=(int)1024*10;
        $requestReady = xmlrpc_encode_request('available',$data,array('encoding'=>'UTF-8'));
        $serverlist = array_unique(vpl_jailserver_manager::get_server_list($localserverlisttext));
        $list = array();
        foreach ($serverlist as $server) {
            if(vpl_jailserver_manager::is_checkable($server)){
                $response=self::get_response($server,$requestReady,$error);
                if($response === false){
                    vpl_jailserver_manager::server_fail($server,$error);
                }elseif(!isset($response['status'])){
                    vpl_jailserver_manager::server_fail($server,$error);
                }else{
                    if($response['status']=='ready'){
                        $parsed = parse_url($server);
                        $list[] = 'https://'.$parsed['host'].':'.$response['secureport'].'/OK';
                    }
                }
            }
        }
        return $list;
    }
}
