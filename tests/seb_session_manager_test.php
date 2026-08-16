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

namespace mod_vpl\tests;

defined('MOODLE_INTERNAL') || die();

use mod_vpl\seb\session_manager;
use mod_vpl\event\seb_wrong_key;

/**
 * Unit tests for session_manager::validate_token.
 *
 * @group mod_vpl
 */
final class seb_session_manager_test extends \advanced_testcase {
    public function test_validate_token_returns_session_when_valid() {
        
        global $DB, $USER;
        $this->resetAfterTest();
        
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('vpl', [
            'course' => $course->id,
            'name' => 'VPL SEB',
            'requiresafeexambrowser' => 1,
        ]);

        $record = $DB->get_record('vpl_seb', ['vplid' => $instance->id]);
        $starturl = new \moodle_url('/mod/vpl/view.php', ['id' => $instance->cmid]);
        $session = session_manager::get_or_create($record, $USER->id, $starturl);
        $result = session_manager::validate_token($session->token1public, $record->vplid, $USER->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($session->id, $result->id);

    }

    public function test_validate_token_returns_null_and_triggers_event_when_invalid() {
        
        global $DB, $USER;
        $this->resetAfterTest();
        
        $course = $this->getDataGenerator()->create_course();
        
        $instance = $this->getDataGenerator()->create_module('vpl', [
            'course' => $course->id,
            'name' => 'VPL SEB',
            'requiresafeexambrowser' => 1,
        ]);

        $record = $DB->get_record('vpl_seb', ['vplid' => $instance->id]);
        $invalidtoken = 'invalidtoken123';
        $sink = $this->redirectEvents();
        $result = session_manager::validate_token($invalidtoken, $record->vplid, $USER->id);
        $events = $sink->get_events();
        
        $this->assertNull($result);
        $found = false;
        
        foreach ($events as $event) {
            if ($event instanceof seb_wrong_key) {
                $found = true;
                $this->assertEquals($record->vplid, $event->objectid);
                $this->assertEquals($USER->id, $event->userid);
                $this->assertEquals($invalidtoken, $event->other['token']);
            }
        }
        $this->assertTrue($found, 'seb_wrong_key event was not triggered');
    }
}
