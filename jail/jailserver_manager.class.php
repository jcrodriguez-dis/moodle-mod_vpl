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
global $CFG;
require_once(__DIR__ . '/../locallib.php');
require_once($CFG->libdir . '/filelib.php');

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
     * Get activity jail servers, including inherited servers.
     *
     * @param mod_vpl $vpl Current VPL activity.
     * @param array $already VPL activities already processed.
     * @return string List of jail servers, one per line.
     */
    public static function get_currentjailservers(mod_vpl $vpl, array &$already = []): string {
        $serverlist = '';
        $vplinstance = $vpl->get_instance();
        if (isset($already[$vplinstance->id])) {
            throw new moodle_exception('error:recursivedefinition', 'mod_vpl');
        }
        $already[$vplinstance->id] = true;
        if ($vplinstance->basedon) {
            $basedon = new mod_vpl(null, $vplinstance->basedon);
            $serverlist = self::get_currentjailservers($basedon, $already);
        }
        if ($vplinstance->jailservers > '') {
            $serverlist = $vplinstance->jailservers . "\n" . $serverlist;
        }
        return $serverlist;
    }

    /**
     * Mask the jail security path.
     *
     * @param string $url URL to clean.
     * @return string URL with its security path masked.
     */
    public static function remove_path(string $url): string {
        $parsed = parse_url($url);
        if ($parsed === false) {
            return $url;
        }
        $path = str_replace('/', '', $parsed['path'] ?? '');
        if ($path === '') {
            return $url;
        } else {
            return str_replace($path, '******', $url);
        }
    }

    /**
     * Get a short server name with the masked full URL as its tooltip.
     *
     * @param string $url URL of the server.
     * @return string Short name HTML of the server.
     */
    public static function get_server_short_name_html(string $url): string {
        $fullname = s(self::remove_path($url));
        $host = parse_url($url, PHP_URL_HOST);
        if (filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) !== false) {
            $shortname = $host;
        } else {
            $names = explode('.', $host);
            $shortname = $names[0];
            if (
                count($names) >= 2 && ($shortname === '' ||
                strcasecmp($shortname, 'www') === 0 ||
                strcasecmp($shortname, 'jail') === 0)
            ) {
                $shortname .= "." . $names[1];
            }
        }
        return '<span title="' . $fullname . '">' . s($shortname) . '</span>';
    }

    /**
     * Check whether server information contains any language servers.
     *
     * @param array $serversinfo Server information objects.
     * @return bool True if any server has language servers.
     */
    public static function there_are_ls(array $serversinfo): bool {
        foreach ($serversinfo as $info) {
            if (!empty($info->ls)) {
                return true;
            }
        }
        return false;
    }

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
     * @param string $header Header string from the response
     * @return bool True if the Server header was successfully parsed, false otherwise
     */
    public static function parse_headers($header) {
        $parsedheader = explode(':', trim($header), 2);
        if (count($parsedheader) == 2 && strcasecmp($parsedheader[0], 'Server') == 0) {
            $parsedname = explode(' ', trim($parsedheader[1]), 2);
            if (count($parsedname) == 2 && strcasecmp($parsedname[0], 'vpl-jail-system') === 0) {
                self::$lastserverversion = trim($parsedname[1]);
                return true;
            }
        }
        return false;
    }

    /**
     * Get a configured Moodle cURL client, using Moodle's proxy unless VPL overrides it.
     *
     * @param string $server URL of the jail server
     * @param string $request Request to be sent
     * @param bool $fresh If true, force a fresh connection
     * @return \curl Moodle cURL client
     */
    public static function get_curl($server, $request, $fresh = false) {
        $plugincfg = get_config('mod_vpl');
        $hasvplproxy = !empty($plugincfg->proxy) && strlen($plugincfg->proxy) > 7;
        $ch = $hasvplproxy ? new \curl(['proxy' => false]) : new \curl();
        $contenttype = substr($request, 0, 1) === '{' ? 'application/json' : 'text/xml';
        $ch->setHeader([
                "Content-type: {$contenttype};charset=UTF-8",
                'User-Agent: VPL ' . vpl_get_version(),
        ]);
        self::$lastserverversion = '';
        $ch->setopt([
            'CURLOPT_URL' => $server,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_USERAGENT' => 'VPL ' . vpl_get_version(),
            'CURLOPT_CONNECTTIMEOUT' => 5,
            'CURLOPT_FRESH_CONNECT' => $fresh,
            'CURLOPT_FOLLOWLOCATION' => false,
            'CURLOPT_SSL_VERIFYPEER' => empty($plugincfg->acceptcertificates),
            'CURLOPT_SSL_VERIFYHOST' => 2,
        ]);
        if ($hasvplproxy) {
            $ch->setopt([
                // Moodle's global proxy credentials and type must not leak to the VPL proxy.
                'CURLOPT_PROXYUSERPWD' => '',
                'CURLOPT_PROXYAUTH' => CURLAUTH_BASIC,
                'CURLOPT_PROXYTYPE' => CURLPROXY_HTTP,
                'CURLOPT_HTTPPROXYTUNNEL' => false,
            ]);
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
        $ch = static::get_curl($server, $request, $fresh);
        $error = '';
        $proxy = get_config('mod_vpl', 'proxy');
        // Moodle removes stored proxy options when its built-in proxy handling is disabled.
        $options = !empty($proxy) && strlen($proxy) > 7 ? ['CURLOPT_PROXY' => $proxy] : [];
        $rawresponse = $ch->post($server, $request, $options);
        foreach ($ch->get_raw_response() as $header) {
            if (self::parse_headers($header)) {
                break;
            }
        }
        if ($rawresponse === false || $ch->get_errno() !== 0 || !empty($ch->error)) {
            $detailederror = str_replace($server, '[JAIL_SERVER]', $ch->error);
            $error = 'Request failed: ' . $detailederror;
        } else {
            $info = $ch->get_info();
            $httpcode = $info['http_code'];
            if ($httpcode != 200) {
                $error = "HTTP Status Code: {$httpcode}";
                if ($httpcode == 404) {
                    $error .= " - Bad URLPATH?";
                } else if ($httpcode >= 400 && $httpcode < 500) {
                    $error .= " - Client Error";
                } else if ($httpcode >= 500) {
                    $error .= " - Internal Jail Server Error";
                }
            } else if ($rawresponse === '') {
                $error = 'Empty response from jail server';
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
     * Check for issues in jail server
     *
     * @param string $server URL of the server
     * @return string[] Issues messages in HTML for the server, empty array otherwise
     */
    public static function get_server_issues(string $server): array {
        $parse = parse_url($server);
        $servername = self::get_server_short_name_html($server);
        if ($parse === false || ! isset($parse['scheme']) || ! isset($parse['host'])) {
            return [get_string('jail_server_badurl', VPL, $servername)];
        }
        $scheme = $parse['scheme'];
        if ($scheme != 'http' && $scheme != 'https') {
            return [get_string('jail_server_badurl', VPL, $servername)];
        }
        $message = [];
        if ($scheme == 'http') {
            $message[] = get_string('jail_server_usinghttp', VPL, $servername);
        }
        if (!isset($parse['path']) || $parse['path'] == '') {
            $message[] = get_string('jail_server_isopen', VPL, $servername);
        }
        if (self::is_private_host($server)) {
            $message[] = get_string('jail_server_isprivate', VPL, $servername);
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
                $badservers[$server] = true;
            } else if ($parse['scheme'] != 'http' && $parse['scheme'] != 'https') {
                $badservers[$server] = true;
            } else {
                if ($languages != null) {
                    foreach (preg_split('/[ ,;]+/', $languages) as $language) {
                        $language = strtolower(trim($language));
                        if ($language == '') {
                            continue;
                        }
                        if (!isset($lsservers[$language])) {
                            $lsservers[$language] = [];
                        }
                        $lsservers[$language][$server] = true;
                    }
                } else {
                    $servers[$server] = true;
                }
                $allservers[$server] = true;
            }
        }
        foreach ($lsservers as $language => $servers) {
            $lsservers[$language] = array_keys($servers);
        }
        return [
                'servers' => array_keys($servers),
                'badservers' => array_keys($badservers),
                'lsservers' => $lsservers,
                'allservers' => array_keys($allservers),
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
        $data->servername = parse_url($CFG->wwwroot, PHP_URL_HOST);
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
     * Return a valid server to be used, may tag some servers as faulty
     *
     * @param \mod_vpl $vpl Object of the current VPL activity
     * @param int $maxmemory Required
     * @param string|null $feedback Info about jail servers response
     * @param string|null $language Language for LS servers, default null (any language)
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
                } else if (version_compare(self::get_last_server_version(), '4.0.3', '<')) {
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
     * Check if a server is located in a private or local network.
     * IPv6 literals include loopback, unique-local, link-local and multicast addresses.
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
        $hostname = trim($hostname, '[]');
        if (filter_var($hostname, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return address_in_subnet($hostname, '::1/128, fc00::/7, fe80::/10, ff00::/8');
        }
        $name = $hostname . '.';
        $ip = filter_var($hostname, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
            ? $hostname
            : gethostbyname($name);
        if ($ip != $name) {
            $private = '10., 127., 172.16-31, 192.168., 169.254., 224-239, 240.';
            return address_in_subnet($ip, $private);
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
        $lsservers = $serversinfo['lsservers'];
        $lsbyserver = [];
        foreach ($lsservers as $language => $servers) {
            foreach ($servers as $server) {
                if (!isset($lsbyserver[$server])) {
                    $lsbyserver[$server] = [];
                }
                $lsbyserver[$server][$language] = true;
            }
        }
        $feedback = [];
        foreach ($serverlist as $server) {
            $status = '';
            $response = self::get_response($server, $requestready, $status);
            $params = ['serverhash' => self::get_hash($server), 'server' => $server];
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
                if (version_compare(self::get_last_server_version(), '4.0.3', '<')) {
                    $info->offline = true;
                    $status = get_string('message::bad_jailserver', VPL);
                } else {
                    $info->offline = false;
                    $info->version = self::get_last_server_version();
                    $status = $response['status'];
                }
            }
            $info->current_status = $status;
            $info->ls = isset($lsbyserver[$server]) ? array_keys($lsbyserver[$server]) : [];
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
