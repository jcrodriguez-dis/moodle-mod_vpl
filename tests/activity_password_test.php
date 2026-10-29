<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace mod_vpl;

use mod_vpl\forms\activity_password;
use mod_vpl\seb\access_validator;
use mod_vpl\seb\config_payload;
use mod_vpl\seb\session_manager;
use mod_vpl\seb\settings;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');

/**
 * Tests for the activity password form.
 *
 * @package mod_vpl
 * @copyright 2026 Juan Carlos Rodriguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group mod_vpl
 * @group mod_vpl_activity_password
 * @covers \mod_vpl\forms\activity_password
 */
final class activity_password_test extends \advanced_testcase {
    /**
     * Clear cached activity records between tests.
     */
    protected function tearDown(): void {
        unset($_SERVER['HTTP_X_SAFEEXAMBROWSER_CONFIGKEYHASH']);
        \mod_vpl::reset_db_cache();
        parent::tearDown();
    }

    /**
     * Password submission must stay within the activity's SEB URL allowlist.
     *
     * @dataProvider password_page_provider
     * @param string $script Activity page requesting the password.
     * @param bool $filtering Whether additional URL filtering is enabled.
     */
    public function test_password_submission_is_allowed_by_seb(string $script, bool $filtering): void {
        global $PAGE, $FULLME;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $instance = $generator->create_module('vpl', [
            'course' => $course->id,
            'password' => 'exam-password',
            'sebrequired' => 2,
            'activateurlfiltering' => $filtering,
        ]);
        $this->setUser($student);
        $vpl = new \mod_vpl($instance->cmid);
        $vpl->prepare_page($script, ['id' => $instance->cmid, 'userid' => $student->id]);
        $FULLME = $PAGE->url->out(false);
        $form = new activity_password($vpl);
        $html = $form->render();
        $this->assertSame(1, preg_match('/<form\b[^>]*\baction="([^"]*)"/', $html, $matches));
        $action = html_entity_decode($matches[1], ENT_QUOTES);
        $this->assertSame($FULLME, $action);

        $sebsettings = settings::get_values_from_vplid($instance->id);
        $rules = config_payload::get_url_filter_rules($sebsettings);
        $allowed = false;
        foreach ($rules as $rule) {
            if ($rule['active'] && $rule['action'] === 1 && $rule['regex']) {
                $allowed = $allowed || preg_match('~' . $rule['expression'] . '~', $action) === 1;
            }
        }
        $this->assertTrue($allowed, 'SEB must allow the rendered password form action.');
        $session = session_manager::create_session($sebsettings, (int)$student->id);
        session_manager::prepare_phase2($sebsettings, $session);
        $FULLME = $action;
        $_SERVER['HTTP_USER_AGENT'] = 'SEB';
        $_SERVER['HTTP_X_SAFEEXAMBROWSER_CONFIGKEYHASH'] = hash('sha256', $FULLME . $session->configkey2);
        $this->assertSame(2, access_validator::get_session_and_phase($sebsettings)->phase);
        $this->assertFalse($vpl->passed_password_check());
        $this->assertFalse($vpl->pass_password_check('wrong-password'));
        $this->assertFalse($vpl->passed_password_check());

        activity_password::mock_submit([
            'id' => $instance->cmid,
            'userid' => $student->id,
            'password' => 'exam-password',
        ]);
        $submittedform = new activity_password($vpl);
        $data = $submittedform->get_data();
        $this->assertNotNull($data);
        $this->assertSame('exam-password', $data->password);
        $vpl->restrictions_check();
        $this->assertTrue($vpl->passed_password_check());
    }

    /**
     * All activity pages allowed by manual SEB can request the password.
     *
     * @return array
     */
    public static function password_page_provider(): array {
        return [
            'description' => ['view.php', false],
            'editor' => ['forms/edit.php', false],
            'submission' => ['forms/submissionview.php', false],
            'description with filtering' => ['view.php', true],
            'editor with filtering' => ['forms/edit.php', true],
            'submission with filtering' => ['forms/submissionview.php', true],
        ];
    }
}
