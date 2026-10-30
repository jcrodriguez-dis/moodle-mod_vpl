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
require_once($CFG->dirroot . '/mod/vpl/forms/edit.class.php');

/**
 * Tests for submission execution requests.
 *
 * @package mod_vpl
 * @copyright 2026 Juan Carlos Rodriguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group mod_vpl
 * @group mod_vpl_edit
 * @covers \mod_vpl_edit
 */
final class edit_test extends \advanced_testcase {
    /**
     * Clear cached activity records between tests.
     */
    protected function tearDown(): void {
        \mod_vpl::reset_db_cache();
        parent::tearDown();
    }

    /**
     * Empty submissions cannot start evaluations, including when templates exist.
     *
     * @dataProvider empty_submission_provider
     * @param bool $teacher Whether the request is made by a teacher.
     * @param bool $requiredfiles Whether the activity has required template files.
     */
    public function test_evaluate_rejects_empty_submission(bool $teacher, bool $requiredfiles): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $instance = $generator->create_module('vpl', [
            'course' => $course->id,
            'evaluate' => 1,
        ]);
        $vpl = new \mod_vpl($instance->cmid);
        if ($requiredfiles) {
            $this->assertTrue($vpl->get_required_fgm()->addfile('main.c', 'int main() {}'));
        }
        $this->setUser($student);
        $files = [];
        $error = '';
        $submissionid = $vpl->add_submission($student->id, $files, '', $error);
        $this->assertNotFalse($submissionid, $error);
        if ($teacher) {
            $this->setAdminUser();
        }
        $sink = $this->redirectEvents();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(get_string('evaluatenofiles', 'vpl'));
        try {
            \mod_vpl_edit::execute($vpl, $student->id, 'evaluate');
        } finally {
            $this->assertCount(0, $sink->get_events());
            $this->assertEquals(0, $DB->get_field('vpl_submissions', 'nevaluations', ['id' => $submissionid]));
        }
    }

    /**
     * Empty submissions must be rejected for both students and teachers.
     *
     * @return array
     */
    public static function empty_submission_provider(): array {
        return [
            'student without templates' => [false, false],
            'student with templates' => [false, true],
            'teacher without templates' => [true, false],
            'teacher with templates' => [true, true],
        ];
    }

    /**
     * Templates do not make an empty saved submission evaluable in the editor.
     *
     * @dataProvider submitted_files_provider
     * @param array|null $files Submitted files, or null when no submission exists.
     */
    public function test_editor_reports_saved_files(?array $files): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $instance = $generator->create_module('vpl', ['course' => $course->id]);
        $vpl = new \mod_vpl($instance->cmid);
        $this->assertTrue($vpl->get_required_fgm()->addfile('main.c', 'int main() {}'));
        if ($files !== null) {
            $error = '';
            $this->assertNotFalse($vpl->add_submission($USER->id, $files, '', $error), $error);
        }
        $loaded = \mod_vpl_edit::load($vpl, $USER->id);
        $this->assertArrayHasKey('main.c', $loaded->files);
        $this->assertSame(!empty($files), $loaded->hassubmittedfiles);

        $files = ['main.c' => ''];
        $saved = \mod_vpl_edit::save($vpl, $USER->id, $files);
        $this->assertTrue($saved->hassubmittedfiles);
        $submission = new \mod_vpl_submission($vpl, $saved->version);
        $this->assertTrue($submission->has_submitted_files());
        ob_start();
        $submission->print_info();
        $info = ob_get_clean();
        $this->assertStringContainsString('forms/evaluation.php', $info);

        $files = [];
        $saved = \mod_vpl_edit::save($vpl, $USER->id, $files);
        $this->assertFalse($saved->hassubmittedfiles);
        $submission = new \mod_vpl_submission($vpl, $saved->version);
        $this->assertFalse($submission->has_submitted_files());
        ob_start();
        $submission->print_info();
        $info = ob_get_clean();
        $this->assertStringNotContainsString('forms/evaluation.php', $info);
    }

    /**
     * Saved states must distinguish templates from real submissions.
     *
     * @return array
     */
    public static function submitted_files_provider(): array {
        return [
            'no submission' => [null],
            'access-only submission' => [[]],
            'zero-byte submitted file' => [['main.c' => '']],
        ];
    }
}
