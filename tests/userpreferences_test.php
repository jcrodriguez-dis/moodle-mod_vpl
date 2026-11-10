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
 * Unit tests for mod_vpl\util\userpreferences.
 *
 * @package mod_vpl
 * @copyright  Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl;

use mod_vpl\tests\base_fixture;
use mod_vpl\util\userpreferences;

/**
 * Unit tests for mod_vpl\util\userpreferences.
 *
 * @group mod_vpl
 * @group mod_vpl_userpreferences
 * @covers \mod_vpl\util\userpreferences
 */
final class userpreferences_test extends base_fixture {
    /**
     * Method to create test fixture
     */
    protected function setUp(): void {
        parent::setUp();
        $this->setupinstances();
    }

    /**
     * Tests the update and get functions of userpreferences.
     *
     * @covers \mod_vpl\util\userpreferences::update
     * @covers \mod_vpl\util\userpreferences::get
     */
    public function test_update_and_get(): void {
        // Check that preferences are empty for a new user.
        $this->setUser($this->users[0]);
        $prefs = userpreferences::get();
        $this->assertEmpty((array)$prefs);
        // Update preferences and check that they are saved and retrieved correctly.
        $newprefs = (object)[
            'editorTheme' => 'dark',
            'editorFontSize' => 14,
            'editorKeyBinding' => 'vim',
            'editorShowInvisibles' => true,
            'editorLiveAutocompletion' => true,
            'terminalTheme' => 'light',
            'terminalFontSize' => 12,
        ];
        // The terminalFontSize default is (12), so it will be stripped.
        $expected = clone $newprefs;
        unset($expected->terminalFontSize);
        $prefs = userpreferences::update($newprefs);
        $this->assertEquals($expected, $prefs);
        $prefs = userpreferences::get();
        $this->assertEquals($expected, $prefs);
        // Update some preferences and check that they are updated correctly.
        $newprefs->terminalFontSize = 16;
        $expected->terminalFontSize = 16;
        $prefs = userpreferences::update($newprefs);
        $this->assertEquals($expected, $prefs);
        $prefs = userpreferences::get();
        $this->assertEquals($expected, $prefs);
        // Reset preferences and check that they are empty again.
        $prefs = userpreferences::update((object)['reset' => true]);
        $this->assertEmpty((array)$prefs);
        $prefs = userpreferences::get();
        $this->assertEmpty((array)$prefs);
        // Check that invalid preferences are ignored and do not cause errors.
        $invalidprefs = (object)[
            'editorTheme' => 'dark',
            'editorFontSize' => 'not a number => 0',
            'editorKeyBinding' => 'vim',
            'not a valid field' => 'should be ignored',
            'editorShowInvisibles' => 'not a boolean => true',
            'editorLiveAutocompletion' => 'not a boolean => true',
            'terminalTheme' => 'light',
            'terminalFontSize' => 'not a number => 0',
            'unknownfield' => 'should be ignored',
        ];
        $prefs = userpreferences::update($invalidprefs);
        $expectedprefs = (object)[
            'editorTheme' => 'dark',
            'editorFontSize' => 0,
            'editorKeyBinding' => 'vim',
            'editorShowInvisibles' => true,
            'editorLiveAutocompletion' => true,
            'terminalTheme' => 'light',
            'terminalFontSize' => 0,
        ];
        $this->assertEquals($expectedprefs, $prefs);
        $prefs = userpreferences::get();
        $this->assertEquals($expectedprefs, $prefs);
        // Check that preferences are user-specific.
        $this->setUser($this->users[1]);
        $prefs = userpreferences::get();
        $this->assertEmpty((array)$prefs);
        $prefs = userpreferences::get($this->users[0]->id);
        $this->assertEquals($expectedprefs, $prefs);
        // Check that preferences can be updated for a specific user.
        $newprefs = (object)[
            'editorTheme' => 'light',
            'editorFontSize' => 18,
            'editorKeyBinding' => 'emacs',
            'editorShowInvisibles' => false,
            'editorLiveAutocompletion' => true,
            'terminalTheme' => 'dark',
            'terminalFontSize' => 14,
        ];
        // The editorShowInvisibles default is (false), so it will be stripped.
        $expectedupdated = (object)[
            'editorTheme' => 'light',
            'editorFontSize' => 18,
            'editorKeyBinding' => 'emacs',
            'editorLiveAutocompletion' => true,
            'terminalTheme' => 'dark',
            'terminalFontSize' => 14,
        ];
        $prefs = userpreferences::update($newprefs, $this->users[1]->id);
        $this->assertEquals($expectedupdated, $prefs);
        $prefs = userpreferences::get($this->users[1]->id);
        $this->assertEquals($expectedupdated, $prefs);
        $prefs = userpreferences::get($this->users[0]->id);
        $this->assertEquals($expectedprefs, $prefs);
    }
}
