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

namespace mod_vpl;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/mod/vpl/jail/jailserver_manager.class.php');
require_once($CFG->dirroot . '/mod/vpl/tests/classes/testable_jailserver_manager.php');

/**
 * Tests for jail server transport and shared view helpers.
 *
 * @package mod_vpl
 * @copyright 2026 Juan Carlos Rodriguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group mod_vpl
 * @group mod_vpl_jailserver_manager
 * @covers \vpl_jailserver_manager
 */
final class jailserver_manager_test extends \advanced_testcase {
    /**
     * Release the injected transport after each test.
     */
    protected function tearDown(): void {
        tests\testable_jailserver_manager::$client = null;
        parent::tearDown();
    }

    /**
     * Only VPL Server headers update the detected version.
     *
     * @covers \vpl_jailserver_manager::parse_headers
     */
    public function test_parse_headers(): void {
        $previousversion = \vpl_jailserver_manager::get_last_server_version();
        try {
            $this->assertTrue(\vpl_jailserver_manager::parse_headers("Server: vpl-jail-system 3.0.1\r\n"));
            $this->assertSame('3.0.1', \vpl_jailserver_manager::get_last_server_version());

            $ignoredheaders = [
                "HTTP/1.1 200 OK\r\n",
                'Content-Type: text/plain',
                'Server: nginx 1.24',
                'Server: vpl-jail-system',
            ];
            foreach ($ignoredheaders as $header) {
                $this->assertFalse(\vpl_jailserver_manager::parse_headers($header), $header);
                $this->assertSame('3.0.1', \vpl_jailserver_manager::get_last_server_version());
            }

            $this->assertTrue(\vpl_jailserver_manager::parse_headers("sErVeR: VPL-JAIL-SYSTEM   4.0.0  \r\n"));
            $this->assertSame('4.0.0', \vpl_jailserver_manager::get_last_server_version());
        } finally {
            $property = new \ReflectionProperty(\vpl_jailserver_manager::class, 'lastserverversion');
            $property->setAccessible(true);
            $property->setValue(null, $previousversion);
        }
    }

    /**
     * Server issues use localized HTML messages and retain all applicable warnings.
     *
     * @covers \vpl_jailserver_manager::get_server_issues
     */
    public function test_get_server_issues(): void {
        $cases = [
            ['https://8.8.8.8/secret', []],
            ['ftp://8.8.8.8/secret', ['jail_server_badurl']],
            ['http://8.8.8.8/secret', ['jail_server_usinghttp']],
            ['https://8.8.8.8', ['jail_server_isopen']],
            ['https://127.0.0.1/secret', ['jail_server_isprivate']],
            ['https://[fd00::1]/secret', ['jail_server_isprivate']],
            ['https://[2606:4700:4700::1111]/secret', []],
            ['http://127.0.0.1', ['jail_server_usinghttp', 'jail_server_isopen', 'jail_server_isprivate']],
        ];
        foreach ($cases as [$server, $issues]) {
            $servername = \vpl_jailserver_manager::get_server_short_name_html($server);
            $expected = [];
            foreach ($issues as $issue) {
                $expected[] = get_string($issue, VPL, $servername);
            }
            $this->assertSame($expected, \vpl_jailserver_manager::get_server_issues($server), $server);
        }
    }

    /**
     * Literal IPv4 and IPv6 addresses are classified without DNS resolution.
     *
     * @covers \vpl_jailserver_manager::is_private_host
     * @dataProvider private_host_provider
     * @param string $url Server URL.
     * @param bool $expected Whether the address is private or local.
     */
    public function test_is_private_host(string $url, bool $expected): void {
        $this->assertSame($expected, \vpl_jailserver_manager::is_private_host($url));
    }

    /**
     * Address families and IPv6 subnet boundaries.
     *
     * @return array
     */
    public static function private_host_provider(): array {
        return [
            'invalid URL' => ['not a URL', false],
            'IPv4 loopback' => ['https://127.0.0.1/secret', true],
            'IPv4 private' => ['https://192.168.1.1/secret', true],
            'IPv4 public' => ['https://8.8.8.8/secret', false],
            'IPv6 loopback' => ['https://[::1]:8080/secret', true],
            'IPv6 expanded loopback' => ['https://[0:0:0:0:0:0:0:1]/secret', true],
            'IPv6 unique-local lower bound' => ['https://[fc00::]/secret', true],
            'IPv6 unique-local upper bound' => ['https://[fdff:ffff:ffff:ffff:ffff:ffff:ffff:ffff]/secret', true],
            'IPv6 below unique-local' => ['https://[fbff::1]/secret', false],
            'IPv6 above unique-local' => ['https://[fe00::1]/secret', false],
            'IPv6 below link-local' => ['https://[fe7f::1]/secret', false],
            'IPv6 link-local lower bound' => ['https://[fe80::]/secret', true],
            'IPv6 link-local upper bound' => ['https://[febf:ffff:ffff:ffff:ffff:ffff:ffff:ffff]/secret', true],
            'IPv6 above link-local' => ['https://[fec0::1]/secret', false],
            'IPv6 multicast' => ['https://[ff02::1]/secret', true],
            'IPv6 public' => ['https://[2606:4700:4700::1111]/secret', false],
        ];
    }

    /**
     * Wrapper defaults must not change VPL's TLS, redirect or proxy policies.
     *
     * @dataProvider curl_options_provider
     * @param string $request Request body.
     * @param string $contenttype Expected content type.
     * @param bool $fresh Whether a fresh connection is required.
     * @param bool $acceptcertificates Whether certificate verification is disabled.
     */
    public function test_get_curl_options(
        string $request,
        string $contenttype,
        bool $fresh,
        bool $acceptcertificates
    ): void {
        global $CFG;
        $this->resetAfterTest();
        unset_config('proxy', 'mod_vpl');
        set_config('acceptcertificates', $acceptcertificates, 'mod_vpl');
        $CFG->proxyhost = 'global-proxy.invalid';
        $CFG->proxyport = 8080;
        $CFG->proxytype = 'SOCKS5';
        $CFG->proxyuser = 'test-user';
        $CFG->proxypassword = 'test-password';
        $client = \vpl_jailserver_manager::get_curl('https://jail.example/secret', $request, $fresh);
        $this->assertInstanceOf(\curl::class, $client);
        $this->assertNull($client->proxy);
        $this->assertSame([
            'Content-type: ' . $contenttype . ';charset=UTF-8',
            'User-Agent: VPL ' . vpl_get_version(),
        ], $client->header);
        $property = new \ReflectionProperty(\curl::class, 'options');
        $property->setAccessible(true);
        $options = $property->getValue($client);
        $this->assertSame(5, $options['CURLOPT_CONNECTTIMEOUT']);
        $this->assertSame($fresh, $options['CURLOPT_FRESH_CONNECT']);
        $this->assertFalse($options['CURLOPT_FOLLOWLOCATION']);
        $this->assertSame(!$acceptcertificates, $options['CURLOPT_SSL_VERIFYPEER']);
        $this->assertSame(2, $options['CURLOPT_SSL_VERIFYHOST']);
        $this->assertSame('VPL ' . vpl_get_version(), $options['CURLOPT_USERAGENT']);
        $this->assertSame('test-user:test-password', $options['CURLOPT_PROXYUSERPWD']);
        $this->assertSame(CURLAUTH_BASIC | CURLAUTH_NTLM, $options['CURLOPT_PROXYAUTH']);
        $this->assertSame(CURLPROXY_SOCKS5, $options['CURLOPT_PROXYTYPE']);
        $this->assertArrayNotHasKey('CURLOPT_HEADERFUNCTION', $options);
    }

    /**
     * Request formats and certificate/connection settings.
     *
     * @return array
     */
    public static function curl_options_provider(): array {
        return [
            'JSON with verified certificate' => ['{"method":"available"}', 'application/json', false, false],
            'XML with accepted certificate' => ['<methodCall/>', 'text/xml', true, true],
            'JSON with fresh connection' => ['{"method":"available"}', 'application/json', true, false],
            'XML with verified certificate' => ['<methodCall/>', 'text/xml', false, false],
        ];
    }

    /**
     * An absent or empty VPL setting leaves Moodle's normal proxy policy intact.
     *
     * @dataProvider proxy_fallback_provider
     * @param string|null $vplproxy VPL setting, or null when absent.
     * @param bool $moodleproxy Whether Moodle has a global proxy.
     */
    public function test_proxy_fallback(?string $vplproxy, bool $moodleproxy): void {
        global $CFG;
        $this->resetAfterTest();
        if ($vplproxy === null) {
            unset_config('proxy', 'mod_vpl');
        } else {
            set_config('proxy', $vplproxy, 'mod_vpl');
        }
        $CFG->proxyhost = $moodleproxy ? 'global-proxy.invalid' : '';
        $CFG->proxyport = 8080;
        $client = \vpl_jailserver_manager::get_curl('https://jail.example/secret', '{}');
        $this->assertSame(\curl::class, get_class($client));
        if ($moodleproxy) {
            $this->assertNull($client->proxy);
            $property = new \ReflectionProperty(\curl::class, 'proxy_host');
            $property->setAccessible(true);
            $this->assertSame('global-proxy.invalid:8080', $property->getValue($client));
        } else {
            $this->assertFalse($client->proxy);
        }
    }

    /**
     * Proxy fallback with and without a site-wide proxy.
     *
     * @return array
     */
    public static function proxy_fallback_provider(): array {
        return [
            'unset VPL proxy with Moodle proxy' => [null, true],
            'empty VPL proxy with Moodle proxy' => ['', true],
            'unset VPL proxy without Moodle proxy' => [null, false],
            'empty VPL proxy without Moodle proxy' => ['', false],
        ];
    }

    /**
     * The factory disables Moodle's proxy and clears its credentials for the override.
     */
    public function test_vpl_proxy_override(): void {
        global $CFG;
        $this->resetAfterTest();
        $proxy = 'http://vpl-proxy.invalid:8080';
        $server = 'https://jail.example/secret';
        $request = '{"method":"available"}';
        set_config('proxy', $proxy, 'mod_vpl');
        $CFG->proxyhost = 'global-proxy.invalid';
        $CFG->proxyport = 8080;
        $CFG->proxytype = 'SOCKS5';
        $CFG->proxyuser = 'global-user';
        $CFG->proxypassword = 'global-password';
        $client = \vpl_jailserver_manager::get_curl($server, $request);
        $this->assertSame(\curl::class, get_class($client));
        $this->assertFalse($client->proxy);
        $property = new \ReflectionProperty(\curl::class, 'options');
        $property->setAccessible(true);
        $options = $property->getValue($client);
        $this->assertSame('', $options['CURLOPT_PROXYUSERPWD']);
        $this->assertSame(CURLAUTH_BASIC, $options['CURLOPT_PROXYAUTH']);
        $this->assertSame(CURLPROXY_HTTP, $options['CURLOPT_PROXYTYPE']);
        $this->assertFalse($options['CURLOPT_HTTPPROXYTUNNEL']);
    }

    /**
     * JSON responses use Moodle's built-in transport mock and keep the RPC ID checks.
     */
    public function test_jsonrpc_response_with_moodle_curl(): void {
        $this->resetAfterTest();
        $request = \vpl_jailserver_manager::jsonrpc_encode('available', (object) []);
        \curl::mock_response(json_encode([
            'id' => \vpl_jailserver_manager::get_jsonrpcid(),
            'result' => ['status' => 'ready'],
        ]));
        $error = 'previous error';
        $this->assertSame(
            ['status' => 'ready'],
            \vpl_jailserver_manager::get_response('https://jail.example/secret', $request, $error)
        );
        $this->assertSame('', $error);
    }

    /**
     * Preserve raw POST data, VPL proxy selection and the server version header.
     */
    public function test_response_proxy_and_headers(): void {
        $this->resetAfterTest();
        $server = 'https://jail.example/secret';
        $proxy = 'http://vpl-proxy.invalid:8080';
        set_config('proxy', $proxy, 'mod_vpl');
        $request = \vpl_jailserver_manager::jsonrpc_encode('available', (object) []);
        $client = $this->createMock(\curl::class);
        $client->expects($this->once())->method('post')->with(
            $server,
            $request,
            ['CURLOPT_PROXY' => $proxy]
        )->willReturn(json_encode([
            'id' => \vpl_jailserver_manager::get_jsonrpcid(),
            'result' => ['status' => 'ready'],
        ]));
        $client->method('get_errno')->willReturn(0);
        $client->error = '';
        $client->method('get_info')->willReturn(['http_code' => 200]);
        $client->method('get_raw_response')->willReturn([
            "HTTP/1.1 200 OK\r\n",
            "sErVeR: VPL-JAIL-SYSTEM 3.0.1\r\n",
            "\r\n",
        ]);
        tests\testable_jailserver_manager::$client = $client;
        $error = '';
        $this->assertSame(
            ['status' => 'ready'],
            tests\testable_jailserver_manager::get_response($server, $request, $error, true)
        );
        $this->assertSame('', $error);
        $this->assertSame('3.0.1', \vpl_jailserver_manager::get_last_server_version());
        \curl::mock_response(json_encode([
            'id' => \vpl_jailserver_manager::get_jsonrpcid(),
            'result' => [],
        ]));
        $this->assertSame([], \vpl_jailserver_manager::get_response($server, $request, $error));
        $this->assertSame('', \vpl_jailserver_manager::get_last_server_version());
    }

    /**
     * Transport failures, HTTP errors and invalid RPC responses retain explicit errors.
     *
     * @dataProvider response_errors_provider
     * @param string|false $body Response body.
     * @param int $status HTTP status.
     * @param int $errno Transport error code.
     * @param string $transporterror Transport error message.
     * @param string $expectederror Expected VPL error message.
     */
    public function test_response_errors($body, int $status, int $errno, string $transporterror, string $expectederror): void {
        $this->resetAfterTest();
        unset_config('proxy', 'mod_vpl');
        $server = 'https://jail.example/secret';
        $request = '{"method":"available"}';
        $client = $this->createMock(\curl::class);
        $client->expects($this->once())->method('post')->with($server, $request, [])->willReturn($body);
        $client->method('get_errno')->willReturn($errno);
        $client->error = $transporterror;
        $client->method('get_info')->willReturn(['http_code' => $status]);
        $client->method('get_raw_response')->willReturn([]);
        tests\testable_jailserver_manager::$client = $client;
        $error = '';
        $this->assertFalse(tests\testable_jailserver_manager::get_response($server, $request, $error));
        $this->assertSame($expectederror, $error);
    }

    /**
     * Failure cases including URL masking and blocked requests without a cURL errno.
     *
     * @return array
     */
    public static function response_errors_provider(): array {
        return [
            'transport error' => [
                false, 0, 7, 'Cannot connect to https://jail.example/secret',
                'Request failed: Cannot connect to [JAIL_SERVER]',
            ],
            'blocked URL' => ['URL blocked', 0, 0, 'URL blocked', 'Request failed: URL blocked'],
            'redirect not followed' => ['', 302, 0, '', 'HTTP Status Code: 302'],
            'bad URL path' => ['', 404, 0, '', 'HTTP Status Code: 404 - Bad URLPATH?'],
            'client error' => ['', 403, 0, '', 'HTTP Status Code: 403 - Client Error'],
            'server error' => ['', 503, 0, '', 'HTTP Status Code: 503 - Internal Jail Server Error'],
            'empty body' => ['', 200, 0, '', 'Empty response from jail server'],
            'malformed JSON' => ['{bad', 200, 0, '', 'JSONRPC response is fault: Syntax error'],
            'mismatched ID' => ['{"id":"wrong","result":{}}', 200, 0, '', 'JSONRPC response mismatch ID'],
        ];
    }

    /**
     * URL-security restrictions are enforced before sending a jail request.
     */
    public function test_blocked_jail_url(): void {
        global $USER;
        $this->resetAfterTest();
        set_config('curlsecurityblockedhosts', '127.0.0.1');
        $url = 'http://127.0.0.1/secret';
        $reason = get_string('curlsecurityurlblocked', 'admin');
        $error = '';
        $this->assertFalse(\vpl_jailserver_manager::get_response($url, '{"method":"available"}', $error));
        $this->assertSame('Request failed: ' . $reason, $error);
        $this->assertDebuggingCalled("Blocked $url: $reason [user {$USER->id}]", DEBUG_NONE);
    }

    /**
     * XML-RPC retains legacy decoding or its missing-extension error.
     */
    public function test_xmlrpc_response(): void {
        $this->resetAfterTest();
        $encode = 'xmlrpc_encode';
        $decode = 'xmlrpc_decode';
        $supported = function_exists($encode) && function_exists($decode);
        \curl::mock_response($supported ? $encode(['status' => 'ready']) : '<methodResponse/>');
        $error = '';
        $response = \vpl_jailserver_manager::get_response('https://jail.example/secret', '<methodCall/>', $error);
        if ($supported) {
            $this->assertSame(['status' => 'ready'], $response);
            $this->assertSame('', $error);
        } else {
            $this->assertFalse($response);
            $this->assertSame('Requires execution server version >= 3 or PHP with XML-RPC support', $error);
        }
    }

    /**
     * Moving the URL helpers preserves their output.
     */
    public function test_server_url_helpers(): void {
        $cases = [
            ['https://jail.example.com/secret', 'https://jail.example.com/******', 'jail.example'],
            ['https://www.example.com/', 'https://www.example.com/', 'www.example'],
            ['https://worker.example.com', 'https://worker.example.com', 'worker'],
            ['http://192.168.1.10:8080/secret', 'http://192.168.1.10:8080/******', '192.168.1.10'],
            ['https://[2001:db8::1]/secret', 'https://[2001:db8::1]/******', '[2001:db8::1]'],
            ['https://host.example/secret?x=1&y=2#status', 'https://host.example/******?x=1&y=2#status', 'host'],
        ];
        foreach ($cases as [$url, $masked, $name]) {
            $this->assertSame($masked, \vpl_jailserver_manager::remove_path($url));
            $this->assertSame(
                '<span title="' . s($masked) . '">' . s($name) . '</span>',
                \vpl_jailserver_manager::get_server_short_name_html($url)
            );
        }
    }

    /**
     * A language server column is needed only when a server has languages.
     */
    public function test_there_are_ls(): void {
        $this->assertFalse(\vpl_jailserver_manager::there_are_ls([]));
        $this->assertFalse(\vpl_jailserver_manager::there_are_ls([(object)[], (object)['ls' => []]]));
        $this->assertTrue(\vpl_jailserver_manager::there_are_ls([(object)['ls' => ['python']]]));
    }

    /**
     * Activity server lists are collected without changing their formatting.
     */
    public function test_get_currentjailservers(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('vpl', [
            'course' => $course->id,
            'jailservers' => 'https://jail.example.com/secret',
        ]);
        $vpl = new \mod_vpl($instance->cmid);
        $already = [];
        $this->assertSame(
            "https://jail.example.com/secret\n",
            \vpl_jailserver_manager::get_currentjailservers($vpl, $already)
        );
        $this->assertArrayHasKey($instance->id, $already);
        $this->expectException(\moodle_exception::class);
        \vpl_jailserver_manager::get_currentjailservers($vpl, $already);
    }
}
