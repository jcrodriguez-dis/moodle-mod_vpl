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
 * Manage jail (execution) servers API
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../locallib.php');

/**
 * vpl_jailserver_manager is a utility class to manage
 * the jail servers.
 *
 */
class vpl_jailserver_manager {
    /**
     * Time in seconds to wait before rechecking a server.
     * This is the time to wait before a server is considered down.
     * If set to 0, it will not recheck the server.
     *
     * @var int
     */
    const RECHECK = 300; // Optional setable?

    /**
     * Name of the table jailservers in the database.
     *
     * @var string
     */
    const TABLE = 'vpl_jailservers';

    /**
     * Save last server version.
     *
     * @var string
     */
    private static $lastserverversion = '';

    /**
     * Get the last server version.
     *
     * @return string Last server version
     */
    public static function get_last_server_version() {
        return self::$lastserverversion;
    }

    /**
     * Parse headers from cURL response to get the server version.
     *
     * @param resource $ch cURL handle
     * @param string $header Header string from the response
     * @return int Length of the header string
     */
    public static function parse_headers($ch, $header) {
        $parsed = explode(' ', $header);
        if (
            count($parsed) == 3 &&
            $parsed[0] == 'Server:' &&
            $parsed[1] == 'vpl-jail-system'
        ) {
            self::$lastserverversion = trim($parsed[2]);
        }
        return strlen($header);
    }

    /**
     * Get a cURL handle for the jail server.
     *
     * @param string $server URL of the jail server
     * @param string $request Request to be sent
     * @param bool $fresh If true, force a fresh connection
     * @return \CurlHandle cURL handle
     * @throws Exception if cURL is not available
     */
    public static function get_curl($server, $request, $fresh = false) {
        if (! function_exists('curl_init')) {
            throw new Exception('PHP cURL required');
        }
        $plugincfg = get_config('mod_vpl');
        $ch = curl_init();
        $contenttype = $request[0] == '{' ? 'application/json' : 'text/xml';
        curl_setopt($ch, CURLOPT_URL, $server);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-type: {$contenttype};charset=UTF-8",
                'User-Agent: VPL ' . vpl_get_version(),
        ]);
        self::$lastserverversion = '';
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'vpl_jailserver_manager::parse_headers');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        if ($fresh) {
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        }
        if (isset($plugincfg->acceptcertificates) && $plugincfg->acceptcertificates) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        if (isset($plugincfg->proxy) && strlen($plugincfg->proxy) > 7) {
            curl_setopt($ch, CURLOPT_PROXY, $plugincfg->proxy);
        }
        return $ch;
    }

    /**
     * Last JSONRPC id used.
     *
     * @var string
     */
    private static $lastjsonrpcid = '';

    /**
     * Generate a new JSONRPC id.
     *
     * @return string
     */
    public static function generate_jsonrpcid() {
        global $USER;
        $idtime = hrtime();
        self::$lastjsonrpcid = $USER->id . '-' . $idtime[0] . '-' . $idtime[1];
    }

    /**
     * Get the JSONRPC id.
     *
     * @return string the JSONRPC id
     */
    public static function get_jsonrpcid() {
        return self::$lastjsonrpcid;
    }

    /**
     * Encode action and data as JSONRPC adding an automatic id.
     *
     * @param string $method
     * @param object $data
     * @return string
     */
    public static function jsonrpc_encode($method, $data) {
        $rpcobject = new stdclass();
        if (false) { // TODO remove when jail servers fixes correponding bug.
            $rpcobject->jsonrpc = "2.0";
        }
        $rpcobject->method = $method;
        $rpcobject->params = $data;
        self::generate_jsonrpcid();
        $rpcobject->id = self::get_jsonrpcid();
        return json_encode($rpcobject, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the response from a jail server.
     *
     * @param string $server URL of the jail server
     * @param string $request Request to be sent
     * @param string $error Error message if any
     * @param bool $fresh If true, force a fresh connection
     * @return array|false Response from the jail server or false on error
     */
    public static function get_response($server, $request, &$error = null, $fresh = false) {
        $ch = self::get_curl($server, $request, $fresh);
        $rawresponse = curl_exec($ch);
        if ($rawresponse === false) {
            $detailederror = str_replace($server, '[JAIL_SERVER]', curl_error($ch));
            $error = 'Request failed: ' . s($detailederror);
            @curl_close($ch); // TODO: Remove when PHP 8 is required, as it will be closed automatically.
        } else {
            $error = '';
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            @curl_close($ch); // TODO: Remove when PHP 8 is required, as it will be closed automatically.
            if ($httpcode != 200) {
                $error = "HTTP Status Code: {$httpcode}";
                if ($httpcode == 404) {
                    $error .= " - Bad URLPATH?";
                } else if ($httpcode >= 400 && $httpcode < 500) {
                    $error .= " - Client Error";
                } else if ($httpcode >= 500) {
                    $error .= " - Internal Jail Server Error";
                }
            } else if ($rawresponse[0] == '{') {
                $response = json_decode($rawresponse, null, 512, JSON_INVALID_UTF8_SUBSTITUTE);
                if (json_last_error() != JSON_ERROR_NONE) {
                    $error = 'JSONRPC response is fault: ' . json_last_error_msg();
                } else {
                    if ($response->id != self::get_jsonrpcid()) {
                        $error = 'JSONRPC response mismatch ID';
                    } else {
                        return (array) ($response->result);
                    }
                }
            } else {
                $xmlrpcdecode = 'xmlrpc_decode';
                if (! function_exists($xmlrpcdecode)) {
                    $error = 'Requires execution server version >= 3 or PHP with XML-RPC support';
                } else {
                    $response = $xmlrpcdecode($rawresponse, "UTF-8");
                    if (is_array($response)) {
                        $xmlrpcisfault = 'xmlrpc_is_fault';
                        if ($xmlrpcisfault($response)) {
                            $error = 'XML-RPC is fault: ' . $response["faultString"];
                        } else {
                            return $response;
                        }
                    } else {
                        $rawresponse = mb_substr($rawresponse, 0, 40);
                        $error = 'HTTP error ' . $rawresponse;
                    }
                }
            }
        }
        return false;
    }
    /**
     * Check if the server is tagged as down.
     *
     * @param string $server URL of the server
     * @return boolean true if the server is checkable, false if it is down
     */
    private static function is_checkable(string $server) {
        global $DB;
        $info = $DB->get_record(self::TABLE, [
                'serverhash' => self::get_hash($server),
                'server' => $server,
        ]);
        if ($info != null) {
            if ($info->lastfail + self::RECHECK > time()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check for issues in server URL
     *
     * @param string $server URL of the server
     * @return string[] Issues message for the server, empty array otherwise
     */
    public static function get_server_issues(string $server): array {
        $parse = parse_url($server);
        if ($parse === false || ! isset($parse['scheme']) || ! isset($parse['host'])) {
            return [get_string('jail_server_badurl', VPL, s($server))];
        }
        $scheme = $parse['scheme'];
        if ($scheme != 'http' && $scheme != 'https') {
            return [get_string('jail_server_badurl', VPL, s($server))];
        }
        $message = [];
        if ($scheme == 'http') {
            $message[] = get_string('jail_server_usinghttp', VPL, s($server));
        }
        if (!isset($parse['path']) || $parse['path'] == '') {
            $message[] = get_string('jail_server_isopen', VPL, s($server));
        }
        if (self::is_private_host($server)) {
            $message[] = get_string('jail_server_isprivate', VPL, s($server));
        }
        return $message;
    }

    /**
     * Tag the server as down.
     *
     * @param string $server URL of the server
     * @param string $strerror Error message to be stored
     * @return void
     */
    private static function server_fail(string $server, string $strerror) {
        global $DB;
        $buggyserver = $strerror == get_string('message::bad_jailserver', VPL);
        $info = $DB->get_record(self::TABLE, [
                'serverhash' => self::get_hash($server),
                'server' => $server,
        ]);
        if ($info != null) {
            $info->lastfail = time();
            $info->laststrerror = $strerror;
            $info->nfails++;
            $info->nbusy = $buggyserver ? -1000000 : $info->nbusy;
            vpl_truncate_jailservers($info);
            $DB->update_record(self::TABLE, $info);
        } else {
            $info = new stdClass();
            $info->server = $server;
            $info->lastfail = time();
            $info->laststrerror = $strerror;
            $info->nfails = 1;
            $info->serverhash = self::get_hash($server);
            $info->nbusy = $buggyserver ? -1000000 : 0;
            vpl_truncate_jailservers($info);
            $DB->insert_record(self::TABLE, $info);
        }
    }

    /**
     * Taken text of server definition returns the info about servers
     *
     * @param string $serverslisttext List of server definition in text
     * @return array with ['servers' => array, 'badservers' => array, 'lsservers' => array]
     */
    public static function get_servers_info(string $serverslisttext) {
        $lines = preg_split("/\r\n|\n|\r/", $serverslisttext);
        $allservers = [];
        $servers = [];
        $badservers = [];
        $lsservers = [];
        foreach ($lines as $line) {
            $line = trim($line);
            // Remove comments and empty lines.
            if ($line == '' || $line[0] == '#') {
                continue;
            }
            // Check for the end of servers mark.
            if (strtolower($line) == 'end_of_jails') {
                break;
            }
            // Check if is a 'LS' server definition.
            if (preg_match('/^ls\s+(.+)\s+([^\s]+)\s*$/i', $line, $matches) > 0) {
                $languages = $matches[1];
                $server = trim($matches[2]);
            } else {
                $languages = null;
                $server = $line;
            }
            $parse = parse_url($server);
            if ($parse === false || ! isset($parse['scheme']) || ! isset($parse['host'])) {
                $badservers[] = $server;
            } else if ($parse['scheme'] != 'http' && $parse['scheme'] != 'https') {
                $badservers[] = $server;
            } else {
                if ($languages != null) {
                    foreach (preg_split('/[ ,;]+/', $languages) as $language) {
                        $language = trim($language);
                        if ($language == '') {
                            continue;
                        }
                        if (isset($lsservers[$language])) {
                            $lsservers[$language][] = $server;
                        } else {
                            $lsservers[$language] = [$server];
                        }
                    }
                } else {
                    $servers[] = $server;
                }
                $allservers[] = $server;
            }
        }
        return [
                'servers' => $servers,
                'badservers' => $badservers,
                'lsservers' => $lsservers,
                'allservers' => $allservers,
        ];
    }

    /**
     * Return the text definition of servers form a vpl activity.
     * This is a recursive process and use also global jail servers definition.
     *
     * @param \mod_vpl $vpl Object of the current VPL activity
     * @return string Text definition of servers
     */
    public static function get_servers_text(\mod_vpl $vpl) {
        $visited = []; // To avoid recursive based on loops.
        $serverstext = $vpl->get_instance()->jailservers;
        while ($vpl->get_instance()->basedon && ! isset($visited[$vpl->get_instance()->id])) {
            $visited[$vpl->get_instance()->id] = true;
            $vpl = new \mod_vpl(null, $vpl->get_instance()->basedon);
            $serverstext .= "\n" . $vpl->get_instance()->jailservers;
        }
        $serverstext .= "\n" . get_config('mod_vpl')->jail_servers;
        return $serverstext;
    }

    /**
     * Get the list of available ls servers.
     *
     * @param \mod_vpl $vpl Object of the current VPL activity
     * @return string[]
     */
    public static function get_ls_list(\mod_vpl $vpl): array {
        $ls = [];
        $serverstext = self::get_servers_text($vpl);
        $serversinfo = self::get_servers_info($serverstext);
        return array_keys($serversinfo['lsservers']);
    }


    /**
     * Returns action request XMLRPC or JSONRPC.
     *
     * @param string $action
     * @param object $data
     * @return string
     */
    public static function get_action_request(string $action, object $data): string {
        global $CFG;
        $plugincfg = get_config('mod_vpl');
        if (empty($plugincfg->use_xmlrpc)) {
            $plugincfg->use_xmlrpc = false;
        }
        $xmlrpcencoderequest = 'xmlrpc_encode_request';
        if ($plugincfg->use_xmlrpc && function_exists($xmlrpcencoderequest)) {
            $outputoptions = [
                'escaping' => 'markup',
                'encoding' => 'UTF-8',
                'verbosity' => 'newlines_only',
            ];
            return $xmlrpcencoderequest($action, $data, $outputoptions);
        } else {
            return self::jsonrpc_encode($action, $data);
        }
    }

    /**
     * Returns available request XMLRPC or JSONRPC.
     * @param int $maxmemory Maximum memory in bytes for the request.
     * @return string
     */
    public static function get_available_request(int $maxmemory): string {
        $data = new stdClass();
        $data->maxmemory = $maxmemory;
        return self::get_action_request('available', $data);
    }

    /**
     * Return a valid server to be used, May tag some servers as faulty
     *
     * @param int $maxmemory Required
     * @param \mod_vpl $vpl Object of the current VPL activity
     * @param ?string $feedback Info about jail servers response
     * @param string $language Language for LS servers, default null (any language)
     * @return string URL of the server or empty string if no server is available
     */
    public static function get_server(
        \mod_vpl $vpl,
        int $maxmemory,
        ?string &$feedback = null,
        ?string $language = null
    ): string {
        global $DB;
        $serversinfo = self::get_servers_info(self::get_servers_text($vpl));
        if ($language != null) {
            if (isset($serversinfo['lsservers'][$language])) {
                $serverlist = $serversinfo['lsservers'][$language];
            } else {
                $serverlist = [];
            }
        } else {
             $serverlist = $serversinfo['servers'];
        }
        $serverlist = array_unique($serverlist);
        shuffle($serverlist);
        $requestready = self::get_available_request($maxmemory);
        $feedback = '';
        $error = '';
        $planb = [];
        foreach ($serverlist as $server) {
            if (self::is_checkable($server)) {
                $response = self::get_response($server, $requestready, $error);
                if ($response === false) {
                    self::server_fail($server, $error);
                    $feedback .= parse_url($server, PHP_URL_HOST) . ' ' . $error . "\n";
                } else if (! isset($response['status'])) {
                    self::server_fail($server, $error);
                    $feedback .= parse_url($server, PHP_URL_HOST) . " protocol error (No status)\n";
                } else if (self::get_last_server_version() > '' && self::get_last_server_version() < '4.0.3') {
                    self::server_fail($server, get_string('message::bad_jailserver', VPL));
                    $feedback .= parse_url($server, PHP_URL_HOST) . " not available.\n";
                } else {
                    if ($response['status'] == 'ready') {
                        $info = $DB->get_record(self::TABLE, [
                            'serverhash' => self::get_hash($server),
                            'server' => $server,
                        ]);
                        if ($info != null) {
                            $info->nrequests++;
                            $DB->update_record(self::TABLE, $info);
                        }
                        return $server;
                    } else {
                        $info = $DB->get_record(self::TABLE, [
                            'serverhash' => self::get_hash($server),
                            'server' => $server,
                        ]);
                        if ($info != null) {
                            $info->nbusy++;
                            $DB->update_record(self::TABLE, $info);
                        }
                    }
                }
            } else {
                $planb[] = $server;
            }
        }
        foreach ($planb as $server) {
            $response = self::get_response($server, $requestready, $error, true);
            if ($response === false) {
                self::server_fail($server, $error);
                $feedback .= parse_url($server, PHP_URL_HOST) . ' ' . $error . "\n";
            } else if (! isset($response['status'])) {
                self::server_fail($server, $error);
                $feedback .= parse_url($server, PHP_URL_HOST) . " protocol error (No status)\n";
            } else {
                if ($response['status'] == 'ready') {
                    $info = $DB->get_record(self::TABLE, [
                        'serverhash' => self::get_hash($server),
                        'server' => $server,
                    ]);
                    if ($info != null) {
                        $info->nrequests++;
                        $DB->update_record(self::TABLE, $info);
                    }
                    return $server;
                }
            }
        }
        return '';
    }

    /**
     * Check if a server is located in a private network
     * Return true ==> private IP
     *
     * @param string $url to server
     * @return bool
     */
    public static function is_private_host(string $url): bool {
        $url = filter_var($url, FILTER_VALIDATE_URL);
        if ($url === false) {
            return false;
        }
        $hostname = parse_url($url, PHP_URL_HOST);
        if ($hostname === null) {
            return false;
        }
        $name = $hostname . '.';
        $ip = gethostbyname($name);
        if ($ip != $name) {
            $private = '10., 127., 172.16-31, 192.168., 169.254., 224-239, 240.';
            return address_in_subnet($ip, $private);
            // IPv6 not implemented fc00::/7 fe80::/10 .
        }
        return false;
    }

    /**
     * Clear servers table and check for every one again
     *
     * @param \mod_vpl $vpl VPL activity object
     * @return array of server object with info about server status
     */
    public static function check_servers(\mod_vpl $vpl): array {
        global $DB;
        $requestready = self::get_available_request(1024 * 10);
        $serversinfo = self::get_servers_info(self::get_servers_text($vpl));
        $serverlist = array_unique($serversinfo['allservers']);
        $feedback = [];
        foreach ($serverlist as $server) {
            $status = '';
            $response = self::get_response($server, $requestready, $status);
            $params = [ 'serverhash' => self::get_hash($server), 'server' => $server ];
            $info = $DB->get_record(self::TABLE, $params);
            if ($info === false) {
                $info = new stdClass();
                $info->server = $server;
                $info->lastfail = null;
                $info->laststrerror = '';
                $info->nfails = 0;
                $info->serverhash = self::get_hash($server);
                $info->nbusy = 0;
            }
            $info->version = '';
            if ($response === false) {
                $info->offline = true;
                self::server_fail($server, $status);
            } else {
                if (self::get_last_server_version() > '' && self::get_last_server_version() < '4.0.3') {
                    $info->offline = true;
                    $status = get_string('message::bad_jailserver', VPL);
                } else {
                    $info->offline = false;
                    $info->version = self::get_last_server_version();
                    $status = $response['status'];
                }
            }
            $info->current_status = $status;
            $feedback[] = $info;
        }
        return $feedback;
    }

    /**
     * Return the https URL servers list
     *
     * @param string $localserverlisttext List of local server in text, default ''
     * @return array of URLs
     */
    public static function get_https_server_list(string $localserverlisttext = ''): array {
        $requestready = self::get_available_request(1024 * 10);
        $error = '';
        $serversinfo = self::get_servers_info($localserverlisttext);
        $serverlist = array_unique($serversinfo['allservers']);
        $list = [];
        foreach ($serverlist as $server) {
            $parsed = parse_url($server);
            if ($parsed === false || ! isset($parsed['scheme']) || ($parsed['scheme'] != 'https')) {
                continue;
            }
            if (self::is_checkable($server)) {
                $response = self::get_response($server, $requestready, $error);
                if ($response === false) {
                    self::server_fail($server, $error);
                } else if (! isset($response['status'])) {
                    self::server_fail($server, $error);
                } else {
                    if ($response['status'] == 'ready') {
                        $parsed = parse_url($server);
                        $list[] = 'https://' . $parsed['host'] . ':' . $response['secureport'] . '/OK';
                    }
                }
            }
        }
        return $list;
    }

    /**
     * Get server URL hash
     *
     * @param string $server $URL to generate hash
     * @return int
     */
    private static function get_hash(string $server): int {
        $md = substr(md5($server), -7);
        return hexdec($md);
    }
}
