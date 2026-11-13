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

use mod_vpl\tests\testable_submission_form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/mod/vpl/tests/classes/testable_submission_form.php');

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
        $form = new testable_submission_form('submission.php', $vpl, $USER->id);
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

    /**
     * Archive limits apply before expansion and after text or binary conversion.
     *
     * @dataProvider archive_provider
     * @covers \mod_vpl\util\submission_archive
     * @param array $entries ZIP entry names and contents.
     * @param array $required Required filenames.
     * @param int $maxfiles File count limit.
     * @param int $maxfilesize Per-file byte limit.
     * @param int $maxbytes Aggregate byte limit.
     * @param array $previous Previous submission files.
     * @param array|null $expected Expected files, or null for rejected archives.
     * @param int $expectedignored Expected number of excess additional files.
     * @param string|null $error Expected error code.
     */
    public function test_archive_import(
        array $entries,
        array $required,
        int $maxfiles,
        int $maxfilesize,
        int $maxbytes,
        array $previous,
        ?array $expected,
        int $expectedignored,
        ?string $error
    ): void {
        $filename = tempnam(sys_get_temp_dir(), 'vpl_archive_');
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($filename, \ZipArchive::OVERWRITE));
        foreach ($entries as $name => $content) {
            $this->assertTrue($zip->addFromString($name, $content));
        }
        $this->assertTrue($zip->close());
        if ($error !== null) {
            $this->expectException(\moodle_exception::class);
        }
        $ignored = 0;
        try {
            $files = \mod_vpl\util\submission_archive::read(
                $filename,
                $required,
                $maxfiles,
                $maxfilesize,
                $maxbytes,
                $previous,
                $ignored
            );
            $this->assertSame($expected, $files);
            $this->assertSame($expectedignored, $ignored);
        } catch (\moodle_exception $exception) {
            $this->assertSame($error, $exception->errorcode);
            throw $exception;
        } finally {
            $this->assertFileDoesNotExist($filename);
            if (is_file($filename)) {
                unlink($filename);
            }
        }
    }

    /**
     * Small archives exercise exact thresholds without generating ZIP bombs.
     *
     * @return array
     */
    public static function archive_provider(): array {
        return [
            'required files last and ignored oversized extra' => [
                ['extra.txt' => 'ok', 'ignored.txt' => str_repeat('x', 1024), 'main.c' => 'code'],
                ['main.c'], 2, 4, 6, [], ['main.c' => 'code', 'extra.txt' => 'ok'], 1, null,
            ],
            'oversized selected entry' => [
                ['main.c' => '12345'], ['main.c'], 1, 4, 10, [], null, 0, 'maxfilesizeexceeded',
            ],
            'aggregate expansion limit' => [
                ['a.c' => '1234', 'b.c' => '1234'], [], 2, 4, 7, [], null, 0, 'maxpostsizeexceeded',
            ],
            'binary required file' => [
                ['image.png' => "\0\1\2"], ['image.png.b64'], 1, 4, 4, [],
                ['image.png.b64' => 'AAEC'], 0, null,
            ],
            'base64 output exceeds per-file limit' => [
                ['image.png' => "\0\1\2\3"], ['image.png.b64'], 1, 4, 10, [],
                null, 0, 'maxfilesizeexceeded',
            ],
            'base64 output exceeds aggregate limit' => [
                ['image.png' => "\0\1\2"], ['image.png.b64'], 1, 4, 3, [],
                null, 0, 'maxpostsizeexceeded',
            ],
            'replacement preserves previous files and ignores new extras' => [
                ['extra.c' => 'x', 'main.c' => 'new'], ['main.c'], 2, 4, 6,
                ['main.c' => 'old', 'keep.c' => 'yes'], ['main.c' => 'new', 'keep.c' => 'yes'], 1, null,
            ],
            'conversion to UTF-8' => [
                ['main.c' => "caf\xe9"], ['main.c'], 1, 5, 5, [],
                ['main.c' => "caf\xc3\xa9"], 0, null,
            ],
            'converted text exceeds per-file limit' => [
                ['main.c' => "caf\xe9"], ['main.c'], 1, 4, 10, [],
                null, 0, 'maxfilesizeexceeded',
            ],
            'invalid path' => [
                ['../main.c' => 'x'], [], 1, 4, 10, [], null, 0, 'error:invalidfile',
            ],
        ];
    }

    /**
     * Actual stream bytes are bounded even when ZIP metadata underreports them.
     *
     * @dataProvider underreported_archive_provider
     * @covers \mod_vpl\util\submission_archive
     * @param int $maxfilesize Per-file byte limit.
     * @param int $maxbytes Aggregate byte limit.
     * @param string $error Expected error code.
     */
    public function test_underreported_archive_size(int $maxfilesize, int $maxbytes, string $error): void {
        $filename = tempnam(sys_get_temp_dir(), 'vpl_archive_');
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($filename, \ZipArchive::OVERWRITE));
        $this->assertTrue($zip->addFromString('main.c', '123456789'));
        $this->assertTrue($zip->close());
        $content = file_get_contents($filename);
        $local = strpos($content, "PK\x03\x04");
        $central = strpos($content, "PK\x01\x02");
        $this->assertNotFalse($local);
        $this->assertNotFalse($central);
        $content = substr_replace($content, pack('V', 1), $local + 22, 4);
        $content = substr_replace($content, pack('V', 1), $central + 24, 4);
        $this->assertNotFalse(file_put_contents($filename, $content));
        $ignored = 0;
        $this->expectException(\moodle_exception::class);
        try {
            \mod_vpl\util\submission_archive::read($filename, ['main.c'], 1, $maxfilesize, $maxbytes, [], $ignored);
        } catch (\moodle_exception $exception) {
            $this->assertSame($error, $exception->errorcode);
            throw $exception;
        } finally {
            $this->assertFileDoesNotExist($filename);
            if (is_file($filename)) {
                unlink($filename);
            }
        }
    }

    /**
     * The per-file and aggregate guards must independently constrain stream reads.
     *
     * @return array
     */
    public static function underreported_archive_provider(): array {
        return [
            'per-file stream bound' => [4, 10, 'maxfilesizeexceeded'],
            'aggregate stream bound' => [10, 4, 'maxpostsizeexceeded'],
        ];
    }
}
