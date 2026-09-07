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
 * Unit tests for the SEB session manager.
 *
 * @package mod_vpl
 * @copyright 2026 Alejandro David Arzola Saavedra
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Alejandro David Arzola Saavedra <alejandro.arzola101@alu.ulpgc.es>
 */

namespace mod_vpl\tests;

use mod_vpl\event\seb_wrong_key;
use mod_vpl\seb\access_validator;
use mod_vpl\seb\session_manager;
use mod_vpl\seb\settings;
use mod_vpl\seb\ui;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/tests/classes/base_fixture.php');

/**
 * Unit tests for the SEB session lifecycle and phase detection.
 *
 * @group mod_vpl
 * @group mod_vpl_seb
 * @covers \mod_vpl\seb\session_manager
 * @covers \mod_vpl\seb\access_validator
 * @covers \mod_vpl\seb\ui
 */
final class seb_session_manager_test extends base_fixture {
    /** Header used by SEB to send the hash of the current configuration key. */
    const CONFIG_KEY_HEADER = 'HTTP_X_SAFEEXAMBROWSER_CONFIGKEYHASH';

    /**
     * Remove the simulated SEB header between tests.
     */
    protected function tearDown(): void {
        unset($_SERVER[self::CONFIG_KEY_HEADER]);
        parent::tearDown();
    }

    /**
     * Create a VPL instance using manual SEB configuration.
     *
     * @param array $params Extra module parameters.
     * @return \stdClass The created module instance.
     */
    protected function create_seb_vpl(array $params = []): \stdClass {
        return $this->getDataGenerator()->create_module('vpl', array_merge([
            'course' => $this->course->id,
            'name' => 'VPL SEB',
            'sebrequired' => 2,
        ], $params));
    }

    /**
     * A session is created once and reused afterwards.
     */
    public function test_get_or_create_session_creates_the_session_once(): void {
        global $DB;

        $userid = (int)$this->students[0]->id;
        $instance = $this->create_seb_vpl();
        $settings = settings::get_values_from_vplid($instance->id);

        $this->assertNull(session_manager::get_session($settings, $userid));

        $first = session_manager::get_or_create_session($settings, $userid);
        $this->assertNotEmpty($first->id);
        $this->assertEquals($userid, $first->userid);
        $this->assertSame(64, strlen($first->token1public));
        $this->assertSame(64, strlen($first->token1private));
        $this->assertNotEquals($first->token1public, $first->token1private);
        $this->assertSame(64, strlen($first->configkey1));
        $this->assertFalse(session_manager::exists_phase2($first));

        $second = session_manager::get_or_create_session($settings, $userid);
        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, $DB->count_records('vpl_seb_session', ['vplid' => $instance->id]));
    }

    /**
     * When per user sessions are disabled every user shares the same session.
     */
    public function test_session_is_shared_when_user_sessions_are_disabled(): void {
        $instance = $this->create_seb_vpl(['enablesebsession' => 0]);
        $settings = settings::get_values_from_vplid($instance->id);
        $this->assertSame(0, $settings->enablesebsession);

        $session = session_manager::create_session($settings, (int)$this->students[0]->id);
        $this->assertEquals(0, $session->userid);

        $other = session_manager::get_session($settings, (int)$this->students[1]->id);
        $this->assertEquals($session->id, $other->id);
    }

    /**
     * The public token returns the matching session.
     */
    public function test_get_session_by_public_token_returns_the_session(): void {
        $instance = $this->create_seb_vpl();
        $settings = settings::get_values_from_vplid($instance->id);
        $session = session_manager::create_session($settings, (int)$this->students[0]->id);

        $result = session_manager::get_session_by_public_token($session->token1public, $instance->id);
        $this->assertNotEmpty($result);
        $this->assertEquals($session->id, $result->id);

        // The token is only valid for its own activity.
        $other = $this->create_seb_vpl(['name' => 'Another VPL SEB']);
        $this->assertEmpty(session_manager::get_session_by_public_token($session->token1public, $other->id));
    }

    /**
     * An unknown public token logs a seb_wrong_key event.
     */
    public function test_get_session_by_public_token_logs_event_when_unknown(): void {
        $this->setUser($this->students[0]);
        $instance = $this->create_seb_vpl();

        $sink = $this->redirectEvents();
        $result = session_manager::get_session_by_public_token('unknowntoken', $instance->id);
        $events = $sink->get_events();
        $sink->close();

        $this->assertEmpty($result);
        $found = false;
        foreach ($events as $event) {
            if ($event instanceof seb_wrong_key) {
                $found = true;
                $this->assertEquals($instance->id, $event->objectid);
                $this->assertEquals($this->students[0]->id, $event->userid);
                $this->assertEquals('No session found for token', $event->other['reason']);
            }
        }
        $this->assertTrue($found, 'seb_wrong_key event was not triggered');
    }

    /**
     * Preparing phase 2 stores the second token, its config key and the Moodle session key.
     */
    public function test_prepare_phase2_completes_the_session(): void {
        global $DB;

        $this->setUser($this->students[0]);
        $instance = $this->create_seb_vpl();
        $settings = settings::get_values_from_vplid($instance->id);
        $session = session_manager::create_session($settings, (int)$this->students[0]->id);

        session_manager::prepare_phase2($settings, $session);

        $this->assertTrue(session_manager::exists_phase2($session));
        $this->assertNotEquals($session->configkey1, $session->configkey2);
        $this->assertNotEquals($session->token1private, $session->token2private);

        $stored = $DB->get_record('vpl_seb_session', ['id' => $session->id]);
        $this->assertEquals($session->configkey2, $stored->configkey2);
        $this->assertEquals($session->token2private, $stored->token2private);
        $this->assertEquals(sesskey(), $stored->sesskey);
    }

    /**
     * A session is only bound to a Moodle session when simultaneous sessions are prevented.
     */
    public function test_is_unallowed_moodle_session(): void {
        $this->setUser($this->students[0]);
        $instance = $this->create_seb_vpl();
        $settings = settings::get_values_from_vplid($instance->id);
        $session = session_manager::create_session($settings, (int)$this->students[0]->id);

        // A brand new session is not bound to any Moodle session yet.
        $this->assertFalse(session_manager::is_unallowed_moodle_session($settings, $session));

        session_manager::prepare_phase2($settings, $session);
        $this->assertFalse(session_manager::is_unallowed_moodle_session($settings, $session));

        $session->sesskey = 'anothersesskey';
        $this->assertTrue(session_manager::is_unallowed_moodle_session($settings, $session));

        $settings->preventsebsimultaneoussessions = 0;
        $this->assertFalse(session_manager::is_unallowed_moodle_session($settings, $session));
    }

    /**
     * The SEB form warns before changing settings with existing sessions.
     */
    public function test_seb_form_warns_when_sessions_exist(): void {
        $instance = $this->create_seb_vpl();

        $mform = new \MoodleQuickForm('sebformwithoutsession', 'post', '');
        ui::add_fields_to_form($mform, $instance->id);
        $this->assertFalse($mform->elementExists('seb_session_warning'));

        $settings = settings::get_values_from_vplid($instance->id);
        session_manager::create_session($settings, (int)$this->students[0]->id);

        $mform = new \MoodleQuickForm('sebformwithsession', 'post', '');
        ui::add_fields_to_form($mform, $instance->id);
        $this->assertTrue($mform->elementExists('seb_session_warning'));
    }

    /**
     * The teacher view offers an explicit SEB check without creating a session while rendering.
     */
    public function test_teacher_view_offers_explicit_seb_check(): void {
        global $DB;

        $this->setUser($this->teachers[0]);
        $instance = $this->create_seb_vpl();
        $vpl = new \mod_vpl($instance->cmid);
        $vpl->prepare_page('view.php', ['id' => $instance->cmid]);

        $summary = $vpl->str_submission_restriction();

        $this->assertStringContainsString('sebcheck=1', $summary);
        $this->assertStringNotContainsString('download_seb_conf.php', $summary);
        $this->assertFalse($DB->record_exists('vpl_seb_session', ['vplid' => $instance->id]));
    }

    /**
     * The teacher password is required and compared as a whole.
     */
    public function test_validate_teacher_password(): void {
        $instance = $this->create_seb_vpl(['sebteacherpassword' => 'teacherpass']);
        $settings = settings::get_values_from_vplid($instance->id);

        $this->assertTrue(session_manager::validate_teacher_password($settings, 'teacherpass'));
        $this->assertFalse(session_manager::validate_teacher_password($settings, 'teacherpas'));
        $this->assertFalse(session_manager::validate_teacher_password($settings, ''));

        // An activity without teacher password never accepts one.
        $settings->sebteacherpassword = '';
        $this->assertFalse(session_manager::validate_teacher_password($settings, ''));
    }

    /**
     * The phase 1 configuration is permissive and allows reconfiguration.
     */
    public function test_phase1_config_xml(): void {
        global $CFG;

        $instance = $this->create_seb_vpl();
        $settings = settings::get_values_from_vplid($instance->id);
        $session = session_manager::create_session($settings, (int)$this->students[0]->id);

        $xml = session_manager::get_phase1_config_xml($settings, $session);

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<key>downloadAndOpenSebConfig</key><true/>', $xml);
        $this->assertStringContainsString('<key>examSessionReconfigureAllow</key><true/>', $xml);
        $starturl = $CFG->wwwroot . '/mod/vpl/forms/edit.php?id=' . $instance->cmid;
        $this->assertStringContainsString(
            '<key>examSessionReconfigureConfigURL</key><string>' . $starturl . '</string>',
            $xml
        );
        $this->assertStringContainsString('<key>startURL</key><string>' . $starturl . '</string>', $xml);
        $expected = '<key>hashedAdminPassword</key><string>' . hash('sha256', $session->token1private) . '</string>';
        $this->assertStringContainsString($expected, $xml);
    }

    /**
     * The phase 2 configuration reflects the activity settings.
     */
    public function test_phase2_config_xml_reflects_settings(): void {
        $instance = $this->create_seb_vpl([
            'showsebtaskbar' => 0,
            'quitpassword' => 'secret',
            'activateurlfiltering' => 1,
            'expressionsallowed' => "example.com\nsub.example.com",
        ]);
        $settings = settings::get_values_from_vplid($instance->id);
        $session = session_manager::create_session($settings, (int)$this->students[0]->id);
        session_manager::prepare_phase2($settings, $session);

        $xml = session_manager::get_phase2_config_xml($settings, $session);

        $this->assertStringContainsString('<key>showTaskBar</key><false/>', $xml);
        $this->assertStringContainsString('<key>allowQuit</key><true/>', $xml);
        $expected = '<key>hashedQuitPassword</key><string>' . hash('sha256', 'secret') . '</string>';
        $this->assertStringContainsString($expected, $xml);
        $this->assertStringContainsString('<key>URLFilterEnable</key><true/>', $xml);
        $this->assertStringContainsString('<key>expression</key><string>example.com</string>', $xml);
        $this->assertStringContainsString('<key>expression</key><string>sub.example.com</string>', $xml);
        $expected = '<key>hashedAdminPassword</key><string>' . hash('sha256', $session->token2private) . '</string>';
        $this->assertStringContainsString($expected, $xml);
        // Phase 2 must not let the browser load another configuration.
        $this->assertStringContainsString('<key>examSessionReconfigureAllow</key><false/>', $xml);
    }

    /**
     * Root keys of the generated plist are sorted, so the config key is reproducible.
     */
    public function test_config_xml_root_keys_are_sorted(): void {
        $instance = $this->create_seb_vpl();
        $settings = settings::get_values_from_vplid($instance->id);
        $session = session_manager::create_session($settings, (int)$this->students[0]->id);
        session_manager::prepare_phase2($settings, $session);

        $xml = session_manager::get_phase2_config_xml($settings, $session);

        $preferences = strpos($xml, '<key>allowPreferencesWindow</key>');
        $quit = strpos($xml, '<key>allowQuit</key>');
        $spellcheck = strpos($xml, '<key>allowSpellCheck</key>');

        $this->assertNotFalse($preferences);
        $this->assertNotFalse($quit);
        $this->assertNotFalse($spellcheck);
        $this->assertLessThan($quit, $preferences);
        $this->assertLessThan($spellcheck, $quit);
    }

    /**
     * The phase is derived from the configuration key sent by the browser.
     */
    public function test_get_session_and_phase_detects_each_phase(): void {
        global $FULLME;

        $this->setUser($this->students[0]);
        $instance = $this->create_seb_vpl();
        $settings = settings::get_values_from_vplid($instance->id);
        $FULLME = 'https://example.com/mod/vpl/view.php?id=' . $instance->cmid;

        // A regular browser sends no configuration key.
        unset($_SERVER[self::CONFIG_KEY_HEADER]);
        $session = access_validator::get_session_and_phase($settings);
        $this->assertSame(0, $session->phase);
        $this->assertNotEmpty($session->id);

        // SEB running the permissive configuration.
        $_SERVER[self::CONFIG_KEY_HEADER] = hash('sha256', $FULLME . $session->configkey1);
        $session = access_validator::get_session_and_phase($settings);
        $this->assertSame(1, $session->phase);

        // SEB running the restrictive configuration.
        session_manager::prepare_phase2($settings, $session);
        $_SERVER[self::CONFIG_KEY_HEADER] = hash('sha256', $FULLME . $session->configkey2);
        $session = access_validator::get_session_and_phase($settings);
        $this->assertSame(2, $session->phase);

        // An unrelated configuration key is rejected.
        $_SERVER[self::CONFIG_KEY_HEADER] = hash('sha256', $FULLME . 'unknownkey');
        $session = access_validator::get_session_and_phase($settings);
        $this->assertSame(0, $session->phase);
    }

    /**
     * A stale phase 2 key is logged as a denied access.
     */
    public function test_get_session_and_phase_logs_stale_phase2_key(): void {
        global $FULLME;

        $this->setUser($this->students[0]);
        $instance = $this->create_seb_vpl();
        $settings = settings::get_values_from_vplid($instance->id);
        $FULLME = 'https://example.com/mod/vpl/view.php?id=' . $instance->cmid;

        $session = session_manager::create_session($settings, (int)$this->students[0]->id);
        session_manager::prepare_phase2($settings, $session);
        $_SERVER[self::CONFIG_KEY_HEADER] = hash('sha256', $FULLME . 'stalekey');

        $sink = $this->redirectEvents();
        $session = access_validator::get_session_and_phase($settings);
        $events = $sink->get_events();
        $sink->close();

        $this->assertSame(0, $session->phase);
        $found = false;
        foreach ($events as $event) {
            if ($event instanceof \mod_vpl\event\seb_access_denied) {
                $found = true;
                $this->assertEquals($instance->id, $event->objectid);
            }
        }
        $this->assertTrue($found, 'seb_access_denied event was not triggered');
    }

    /**
     * Every phase value returned by the access validator is covered.
     */
    public function test_get_session_and_phase_returns_all_phase_values(): void {
        global $FULLME;

        $this->setUser($this->students[0]);

        // No SEB configuration key keeps a new session in phase 0.
        $instance = $this->create_seb_vpl();
        $settings = settings::get_values_from_vplid($instance->id);
        $FULLME = 'https://example.com/mod/vpl/view.php?id=' . $instance->cmid;
        unset($_SERVER[self::CONFIG_KEY_HEADER]);
        $session = access_validator::get_session_and_phase($settings);
        $this->assertSame(0, $session->phase);

        // A phase 1 key is accepted after the Moodle session is bound in phase 2 preparation.
        session_manager::prepare_phase2($settings, $session);
        $_SERVER[self::CONFIG_KEY_HEADER] = hash('sha256', $FULLME . $session->configkey1);
        $session = access_validator::get_session_and_phase($settings);
        $this->assertSame(1, $session->phase);

        // A matching phase 2 key grants access.
        $_SERVER[self::CONFIG_KEY_HEADER] = hash('sha256', $FULLME . $session->configkey2);
        $session = access_validator::get_session_and_phase($settings);
        $this->assertSame(2, $session->phase);

        // A phase 1 key without an allowed Moodle session is denied.
        $instance = $this->create_seb_vpl(['name' => 'VPL SEB phase 1 denied']);
        $settings = settings::get_values_from_vplid($instance->id);
        $FULLME = 'https://example.com/mod/vpl/view.php?id=' . $instance->cmid;
        $session = session_manager::create_session($settings, (int)$this->students[0]->id);
        session_manager::prepare_phase2($settings, $session);
        $session->sesskey = 'different-session';
        session_manager::update_session($session);
        $_SERVER[self::CONFIG_KEY_HEADER] = hash('sha256', $FULLME . $session->configkey1);
        $session = access_validator::get_session_and_phase($settings);
        $this->assertSame(-1, $session->phase);

        // A phase 2 key with a lost Moodle session is denied.
        $instance = $this->create_seb_vpl(['name' => 'VPL SEB phase 2 denied']);
        $settings = settings::get_values_from_vplid($instance->id);
        $FULLME = 'https://example.com/mod/vpl/view.php?id=' . $instance->cmid;
        $session = session_manager::create_session($settings, (int)$this->students[0]->id);
        session_manager::prepare_phase2($settings, $session);
        $session->sesskey = 'different-session';
        session_manager::update_session($session);
        $_SERVER[self::CONFIG_KEY_HEADER] = hash('sha256', $FULLME . $session->configkey2);
        $session = access_validator::get_session_and_phase($settings);
        $this->assertSame(-3, $session->phase);
    }

    /**
     * Sessions are removed with the activity.
     */
    public function test_delete_for_vpl_removes_the_sessions(): void {
        global $DB;

        $instance = $this->create_seb_vpl();
        $settings = settings::get_values_from_vplid($instance->id);
        session_manager::create_session($settings, (int)$this->students[0]->id);
        $this->assertTrue($DB->record_exists('vpl_seb_session', ['vplid' => $instance->id]));

        session_manager::delete_for_vpl($instance->id);

        $this->assertFalse($DB->record_exists('vpl_seb_session', ['vplid' => $instance->id]));
    }
}
