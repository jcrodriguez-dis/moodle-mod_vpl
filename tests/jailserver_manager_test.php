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

/**
 * Tests for shared jail server view helpers.
 *
 * @package mod_vpl
 * @copyright 2026 Juan Carlos Rodriguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group mod_vpl
 * @covers \vpl_jailserver_manager
 */
final class jailserver_manager_test extends \advanced_testcase {
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
