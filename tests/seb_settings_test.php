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
 * SEB: Unit tests for class mod_vpl\seb\settings.
 *
 * @package mod_vpl
 * @copyright 2026 Alejandro David Arzola Saavedra
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Alejandro David Arzola Saavedra <alejandro.arzola101@alu.ulpgc.es>
 */


namespace mod_vpl\tests;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');

/**
 * Unit tests for the manual SEB helper.
 *
 * @group mod_vpl
 */
final class seb_settings_test extends base_fixture {
    /**
     * Ensure that the SEB helper stores and loads the dedicated record.
     */
    public function test_persisted_seb_settings_are_loaded(): void {
        global $DB;

        $instance = $this->getDataGenerator()->create_module('vpl', [
            'course' => $this->course->id,
            'name' => 'VPL with manual SEB',
            'requiresafeexambrowser' => 1,
            'allowedbrowserexamkeys' => 'alpha beta',
            'showsebtaskbar' => 1,
            'showreloadbutton' => 1,
        ]);

        $record = $DB->get_record('vpl_seb', ['vplid' => $instance->id]);
        $this->assertNotFalse($record);
        $this->assertEquals(1, $record->requiresafeexambrowser);
        $this->assertEquals('alpha beta', trim((string)$record->allowedbrowserexamkeys));
        $this->assertTrue(\mod_vpl\seb\settings::uses_seb((object) ['id' => $instance->id]));
        $this->assertNotEmpty(\mod_vpl\seb\settings::get_config_hash($record));
    }

    /**
     * Ensure that removing the SEB settings clears the dedicated record.
     */
    public function test_delete_for_vpl_removes_settings(): void {
        global $DB;

        $instance = $this->getDataGenerator()->create_module('vpl', [
            'course' => $this->course->id,
            'name' => 'VPL without SEB',
        ]);

        $this->assertFalse($DB->record_exists('vpl_seb', ['vplid' => $instance->id]));
        \mod_vpl\seb\settings::delete_for_vpl($instance->id);
        $this->assertFalse($DB->record_exists('vpl_seb', ['vplid' => $instance->id]));
    }

    /**
     * Ensure that the downloadable plist includes the expected SEB keys.
     */
    public function test_download_config_xml_contains_expected_entries(): void {
        $instance = $this->getDataGenerator()->create_module('vpl', [
            'course' => $this->course->id,
            'name' => 'VPL with download config',
            'requiresafeexambrowser' => 1,
            'showsebtaskbar' => 1,
            'showreloadbutton' => 1,
            'showtime' => 1,
            'showkeyboardlayout' => 1,
            'allowuserquitseb' => 1,
            'quitpassword' => 'secret',
            'activateurlfiltering' => 1,
            'expressionsallowed' => "example.com\nsub.example.com",
        ]);

        $record = \mod_vpl\seb\settings::get_effective_record((object) ['id' => $instance->id]);
        $config = \mod_vpl\seb\settings::build_download_config_xml(
            $record,
            new \moodle_url('/mod/vpl/view.php', ['id' => $instance->cmid])
        );

        $this->assertStringContainsString('<key>startURL</key><string>', $config);
        $this->assertStringContainsString('<key>allowQuit</key><true/>', $config);
        $this->assertStringContainsString('<key>hashedQuitPassword</key><string>' . hash('sha256', 'secret') . '</string>', $config);
        $this->assertStringContainsString('<key>URLFilterRules</key><array>', $config);
        $this->assertStringContainsString('<key>expression</key><string>example.com</string>', $config);
    }

    /**
     * Ensure that access validation depends on the SEB config hash, not the user agent string.
     */
    public function test_validate_access_accepts_valid_config_hash_without_user_agent(): void {
        $instance = $this->getDataGenerator()->create_module('vpl', [
            'course' => $this->course->id,
            'name' => 'VPL with SEB access',
            'requiresafeexambrowser' => 1,
        ]);

        $record = \mod_vpl\seb\settings::get_effective_record((object) ['id' => $instance->id]);
        $confighash = \mod_vpl\seb\settings::get_config_hash($record);
        $fullme = 'https://example.com/moodle/mod/vpl/view.php?id=' . $instance->cmid;
        $requestconfighash = hash('sha256', $fullme . $confighash);

        $this->assertTrue(\mod_vpl\seb\settings::validate_access((object) ['id' => $instance->id], $fullme, null, $requestconfighash));
        $this->assertFalse(\mod_vpl\seb\settings::validate_access((object) ['id' => $instance->id], $fullme, null, 'broken-config-hash'));
    }

    /**
     * Ensure generated plist output has deterministic sorted root keys.
     */
    public function test_download_config_xml_has_sorted_root_keys(): void {
        $instance = $this->getDataGenerator()->create_module('vpl', [
            'course' => $this->course->id,
            'name' => 'VPL with sorted plist',
            'requiresafeexambrowser' => 1,
            'allowuserquitseb' => 1,
            'quitpassword' => 'secret',
        ]);

        $record = \mod_vpl\seb\settings::get_effective_record((object) ['id' => $instance->id]);
        $config = \mod_vpl\seb\settings::build_download_config_xml(
            $record,
            new \moodle_url('/mod/vpl/view.php', ['id' => $instance->cmid])
        );

        $this->assertNotFalse(strpos($config, '<key>allowPreferencesWindow</key>'));
        $this->assertNotFalse(strpos($config, '<key>allowQuit</key>'));
        $this->assertNotFalse(strpos($config, '<key>allowSpellCheck</key>'));

        $this->assertLessThan(
            strpos($config, '<key>allowQuit</key>'),
            strpos($config, '<key>allowPreferencesWindow</key>')
        );
        $this->assertLessThan(
            strpos($config, '<key>allowSpellCheck</key>'),
            strpos($config, '<key>allowQuit</key>')
        );
    }

    /**
     * Ensure browser exam key is strictly required when keys are configured.
     */
    public function test_validate_access_requires_browser_exam_hash_when_keys_configured(): void {
        $instance = $this->getDataGenerator()->create_module('vpl', [
            'course' => $this->course->id,
            'name' => 'VPL with browser exam keys',
            'requiresafeexambrowser' => 1,
            'allowedbrowserexamkeys' => 'testkey',
        ]);

        $record = \mod_vpl\seb\settings::get_effective_record((object) ['id' => $instance->id]);
        $fullme = 'https://example.com/moodle/mod/vpl/view.php?id=' . $instance->cmid;
        $requestconfighash = hash('sha256', $fullme . \mod_vpl\seb\settings::get_config_hash($record));
        $requestbrowserhash = hash('sha256', $fullme . 'testkey');

        $this->assertFalse(\mod_vpl\seb\settings::validate_access(
            (object) ['id' => $instance->id],
            $fullme,
            null,
            $requestconfighash
        ));

        $this->assertTrue(\mod_vpl\seb\settings::validate_access(
            (object) ['id' => $instance->id],
            $fullme,
            $requestbrowserhash,
            $requestconfighash
        ));
    }
}