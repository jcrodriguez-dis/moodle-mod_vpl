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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/mod/vpl/forms/submission_form.php');

/**
 * Tests for upload controls with empty and partially populated submissions.
 *
 * @package mod_vpl
 * @copyright 2026 Juan Carlos Rodriguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group mod_vpl
 * @covers \mod_vpl_submission_form
 */
final class submission_form_test extends \advanced_testcase {
    /**
     * Clear cached activity records between tests.
     */
    protected function tearDown(): void {
        \mod_vpl::reset_db_cache();
        parent::tearDown();
    }

    /**
     * Only files actually submitted may be kept or replaced.
     *
     * @dataProvider previous_files_provider
     * @param array|null $files Previous files, or null for no submission.
     */
    public function test_upload_controls_depend_on_previous_files(?array $files): void {
        global $PAGE, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('vpl', [
            'course' => $course->id,
            'maxfiles' => 3,
        ]);
        $vpl = new \mod_vpl($instance->cmid);
        $required = $vpl->get_required_fgm();
        $this->assertTrue($required->addfile('main.c', ''));
        $this->assertTrue($required->addfile('helper.c', ''));
        if ($files !== null) {
            $error = '';
            $this->assertNotFalse($vpl->add_submission($USER->id, $files, '', $error), $error);
        }
        $PAGE->set_url('/mod/vpl/forms/submission.php', ['id' => $instance->cmid]);
        $form = new class('submission.php', $vpl, $USER->id) extends \mod_vpl_submission_form {
            public function getinternalform() {
                return parent::getinternalform();
            }
        };
        $form->set_data((object) ['id' => $instance->cmid, 'userid' => $USER->id]);
        $internal = $form->getinternalform();
        $previousfiles = $files ?? [];
        $this->assertSame(count($previousfiles) > 0, $internal->elementExists('archiveaction'));
        foreach (['main.c', 'helper.c'] as $index => $filename) {
            $field = 'file' . $index;
            $this->assertTrue($internal->elementExists($field));
            $this->assertSame(array_key_exists($filename, $previousfiles), $internal->elementExists($field . 'action'));
        }
        if (array_key_exists('extra.c', $previousfiles)) {
            $this->assertTrue($internal->elementExists('file2action'));
        }
    }

    /**
     * Previous submission states encountered by the upload form.
     *
     * @return array
     */
    public static function previous_files_provider(): array {
        return [
            'no submission' => [null],
            'access-only submission' => [[]],
            'partial submission' => [['main.c' => '']],
            'existing submission' => [['main.c' => '', 'helper.c' => '', 'extra.c' => '']],
        ];
    }
}
