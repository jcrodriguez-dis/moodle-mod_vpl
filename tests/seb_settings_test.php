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

use mod_vpl\seb\session_manager;
use mod_vpl\seb\settings;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/tests/classes/base_fixture.php');

/**
 * Unit tests for the manual SEB settings storage.
 *
 * @group mod_vpl
 * @group mod_vpl_seb
 * @covers \mod_vpl\seb\settings
 */
final class seb_settings_test extends base_fixture {
    /**
     * Create a VPL instance using manual SEB configuration.
     *
     * @param array $params Extra module parameters.
     * @return \stdClass The created module instance.
     */
    protected function create_seb_vpl(array $params = []): \stdClass {
        return $this->getDataGenerator()->create_module('vpl', array_merge([
            'course' => $this->course->id,
            'name' => 'VPL with manual SEB',
            'sebrequired' => 2,
        ], $params));
    }

    /**
     * The default object must contain every form field and nothing else.
     */
    public function test_get_defaults_matches_form_fields(): void {
        $defaults = settings::get_defaults();

        $this->assertEqualsCanonicalizing(array_keys(settings::get_form_fields()), array_keys((array)$defaults));
        foreach (settings::get_form_fields() as $field => $definition) {
            $this->assertSame($definition['default'], $defaults->$field);
        }
    }

    /**
     * Without a stored record the defaults are returned, plus the VPL id.
     */
    public function test_get_values_from_vplid_returns_defaults_when_no_record(): void {
        global $DB;

        $instance = $this->create_seb_vpl();
        settings::delete_for_vpl($instance->id);

        $this->assertFalse($DB->record_exists('vpl_seb', ['vplid' => $instance->id]));

        $values = settings::get_values_from_vplid($instance->id);
        $this->assertEquals($instance->id, $values->vplid);
        foreach (settings::get_form_fields() as $field => $definition) {
            $this->assertSame($definition['default'], $values->$field);
        }
    }

    /**
     * Manual SEB settings are stored when at least one value is non-default.
     */
    public function test_settings_are_stored_for_manual_seb_configuration(): void {
        global $DB;

        $default = $this->create_seb_vpl(['name' => 'All defaults']);
        $this->assertFalse($DB->record_exists('vpl_seb', ['vplid' => $default->id]));

        $custom = $this->create_seb_vpl([
            'name' => 'Custom SEB',
            'showsebtaskbar' => 0,
            'sebteacherpassword' => 'teacherpass',
        ]);
        $record = $DB->get_record('vpl_seb', ['vplid' => $custom->id]);
        $this->assertNotFalse($record);
        $this->assertEquals(0, $record->showsebtaskbar);
        $this->assertSame('teacherpass', $record->sebteacherpassword);

        $values = settings::get_values_from_vplid($custom->id);
        $this->assertSame(0, $values->showsebtaskbar);
        $this->assertSame('teacherpass', $values->sebteacherpassword);
    }

    /**
     * Saving new values updates the record and keeps the creation time.
     */
    public function test_save_for_vpl_updates_existing_record(): void {
        global $DB, $USER;

        $this->setAdminUser();
        $instance = $this->create_seb_vpl(['showsebtaskbar' => 0]);
        $created = $DB->get_record('vpl_seb', ['vplid' => $instance->id]);

        $data = (object)[
            'sebrequired' => 2,
            'showsebtaskbar' => 0,
            'showtime' => 0,
            'sebteacherpassword' => '  spaced  ',
        ];
        settings::save_for_vpl($instance->id, $data, (int)$USER->id);

        $updated = $DB->get_record('vpl_seb', ['vplid' => $instance->id]);
        $this->assertEquals($created->id, $updated->id);
        $this->assertEquals($created->timecreated, $updated->timecreated);
        $this->assertEquals(0, $updated->showtime);
        $this->assertSame('spaced', $updated->sebteacherpassword);
        $this->assertEquals($USER->id, $updated->usermodified);
    }

    /**
     * Saving a partial settings object must preserve an omitted teacher password.
     */
    public function test_save_for_vpl_preserves_omitted_teacher_password(): void {
        global $DB, $USER;

        $this->setAdminUser();
        $instance = $this->create_seb_vpl(['sebteacherpassword' => 'teacherpass']);

        settings::save_for_vpl($instance->id, (object)[
            'sebrequired' => 2,
            'showtime' => 0,
        ], (int)$USER->id);

        $updated = $DB->get_record('vpl_seb', ['vplid' => $instance->id]);
        $this->assertSame('teacherpass', $updated->sebteacherpassword);
    }

    /**
     * Changing the settings invalidates every SEB session of the activity.
     */
    public function test_save_for_vpl_invalidates_sessions(): void {
        global $DB, $USER;

        $this->setAdminUser();
        $instance = $this->create_seb_vpl(['showsebtaskbar' => 0]);
        $values = settings::get_values_from_vplid($instance->id);
        session_manager::create_session($values, (int)$USER->id);
        $this->assertTrue($DB->record_exists('vpl_seb_session', ['vplid' => $instance->id]));

        settings::save_for_vpl($instance->id, (object)[
            'sebrequired' => 2,
            'showtime' => 0,
        ], (int)$USER->id);

        $this->assertFalse($DB->record_exists('vpl_seb_session', ['vplid' => $instance->id]));
    }

    /**
     * Saving unchanged settings preserves the existing SEB session.
     */
    public function test_save_for_vpl_preserves_sessions_when_settings_are_unchanged(): void {
        global $DB, $USER;

        $this->setAdminUser();
        $instance = $this->create_seb_vpl(['showsebtaskbar' => 0]);
        $values = settings::get_values_from_vplid($instance->id);
        session_manager::create_session($values, (int)$USER->id);

        settings::save_for_vpl($instance->id, (object)[
            'sebrequired' => 2,
            'showsebtaskbar' => 0,
        ], (int)$USER->id);

        $this->assertTrue($DB->record_exists('vpl_seb_session', ['vplid' => $instance->id]));
    }

    /**
     * Saving activity settings without manual SEB configuration removes SEB data.
     */
    public function test_save_for_vpl_deletes_settings_when_seb_is_not_required(): void {
        global $DB, $USER;

        $this->setAdminUser();
        $existing = $this->create_seb_vpl(['sebteacherpassword' => 'teacherpass']);
        $new = $this->create_seb_vpl(['name' => 'No manual SEB']);

        settings::save_for_vpl($existing->id, (object)['sebrequired' => 1], (int)$USER->id);
        settings::save_for_vpl($new->id, (object)['sebrequired' => 0], (int)$USER->id);

        $this->assertFalse($DB->record_exists('vpl_seb', ['vplid' => $existing->id]));
        $this->assertFalse($DB->record_exists('vpl_seb', ['vplid' => $new->id]));
    }

    /**
     * Ensure that removing the SEB settings clears the record and the sessions.
     */
    public function test_delete_for_vpl_removes_settings_and_sessions(): void {
        global $DB, $USER;

        $this->setAdminUser();
        $instance = $this->create_seb_vpl(['showsebtaskbar' => 0]);
        $values = settings::get_values_from_vplid($instance->id);
        session_manager::create_session($values, (int)$USER->id);

        settings::delete_for_vpl($instance->id);

        $this->assertFalse($DB->record_exists('vpl_seb', ['vplid' => $instance->id]));
        $this->assertFalse($DB->record_exists('vpl_seb_session', ['vplid' => $instance->id]));
    }

    /**
     * Deleting the VPL activity removes the SEB settings.
     */
    public function test_delete_instance_removes_settings(): void {
        global $DB;

        $instance = $this->create_seb_vpl(['showsebtaskbar' => 0]);
        $this->assertTrue($DB->record_exists('vpl_seb', ['vplid' => $instance->id]));

        vpl_delete_instance($instance->id);

        $this->assertFalse($DB->record_exists('vpl_seb', ['vplid' => $instance->id]));
    }

    /**
     * Boolean fields are stored as integers and text fields are trimmed.
     */
    public function test_normalize_value(): void {
        $this->assertSame(1, settings::normalize_value('showsebtaskbar', '1'));
        $this->assertSame(1, settings::normalize_value('showsebtaskbar', true));
        $this->assertSame(0, settings::normalize_value('showsebtaskbar', '0'));
        $this->assertSame(0, settings::normalize_value('showsebtaskbar', ''));
        $this->assertSame('secret', settings::normalize_value('quitpassword', '  secret  '));
        // A null value falls back to the field default.
        $this->assertSame(1, settings::normalize_value('showsebtaskbar', null));
        $this->assertSame('', settings::normalize_value('quitpassword', null));
    }

    /**
     * The course module id is resolved from the VPL instance id.
     */
    public function test_get_cmid(): void {
        $instance = $this->create_seb_vpl();

        $this->assertEquals($instance->cmid, settings::get_cmid($instance->id));
        $this->assertSame(0, settings::get_cmid(-1));
    }
}
