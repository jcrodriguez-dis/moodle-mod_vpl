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
 * Backup and restore round-trip tests.
 *
 * @package mod_vpl
 * @copyright 2026 Juan Carlos Rodriguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_vpl;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/mod/vpl/backup/moodle2/backup_vpl_stepslib.php');
require_once($CFG->dirroot . '/mod/vpl/backup/moodle2/restore_vpl_stepslib.php');
require_once($CFG->dirroot . '/mod/vpl/tests/classes/testable_restore_vpl_activity_structure_step.php');

/**
 * Exercise the real backup and restore pipeline and file restore handlers.
 *
 * @group mod_vpl
 * @group mod_vpl_backup_restore
 * @covers \backup_vpl_activity_structure_step
 * @covers \restore_vpl_activity_structure_step
 * @covers \mod_vpl\backup\backup_vpl_filegroup
 */
final class backup_restore_test extends \advanced_testcase {
    /** @var int|null Course whose VPL files must be removed after the test. */
    protected $courseid = null;

    /**
     * Remove only the VPL directories belonging to the test course.
     */
    protected function tearDown(): void {
        global $CFG, $DB;
        try {
            foreach (['backup_ids_temp', 'backup_files_temp'] as $name) {
                if ($DB->get_manager()->table_exists($name)) {
                    $DB->get_manager()->drop_table(new \xmldb_table($name));
                }
            }
            \restore_dbops::reset_backup_ids_cached();
            if ($this->courseid !== null) {
                $instances = $DB->get_records('vpl', ['course' => $this->courseid], '', 'id');
                foreach ($instances as $instance) {
                    vpl_delete_dir($CFG->dataroot . '/vpl_data/' . $instance->id);
                }
            }
        } finally {
            \mod_vpl::reset_db_cache();
            parent::tearDown();
        }
    }

    /**
     * Settings, file lists and file contents survive backup and restore.
     *
     * @dataProvider file_groups_provider
     * @param array $required Required filenames and contents.
     * @param array $execution Execution filenames and contents.
     * @param array $keep Execution files to retain when running.
     */
    public function test_backup_restore_file_groups(array $required, array $execution, array $keep): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->courseid = $course->id;
        $activity = $this->getDataGenerator()->create_module('vpl', [
            'course' => $course->id,
            'name' => 'Backup and restore fixture',
            'shortdescription' => 'File round-trip',
            'intro' => '<p>Restore these files without changing their contents.</p>',
            'introformat' => FORMAT_HTML,
            'maxfiles' => 10,
            'grade' => 100,
        ]);
        $original = new \mod_vpl($activity->cmid);
        foreach ($required as $name => $content) {
            $this->assertTrue($original->get_required_fgm()->addfile($name, $content));
        }
        foreach ($execution as $name => $content) {
            $this->assertTrue($original->get_execution_fgm()->addfile($name, $content));
        }
        $original->get_execution_fgm()->setfilekeeplist($keep);
        $expectedrequired = $original->get_required_fgm()->getallfiles();
        $expectedexecution = $original->get_execution_fgm()->getallfiles();

        $newcmid = $this->backup_and_restore_activity($activity->cmid, $course->id);
        $newcm = get_fast_modinfo($course)->get_cm($newcmid);
        $this->assertInstanceOf(\cm_info::class, $newcm);
        $this->assertNotEquals($activity->cmid, $newcm->id);
        $this->assertNotEquals($activity->id, $newcm->instance);
        \mod_vpl::reset_db_cache();
        $restored = new \mod_vpl($newcm->id);

        foreach (['name', 'shortdescription', 'intro', 'introformat', 'maxfiles', 'grade'] as $field) {
            $this->assertEquals($original->get_instance()->$field, $restored->get_instance()->$field, $field);
        }
        $this->assertSame($expectedrequired, $restored->get_required_fgm()->getallfiles());
        $this->assertSame($expectedexecution, $restored->get_execution_fgm()->getallfiles());
        $this->assertSame($keep, $restored->get_execution_fgm()->getfilekeeplist());
        foreach (array_keys($required) as $name) {
            $this->assertFileExists($restored->get_required_files_directory() . util\file_group::encodefilename($name));
        }
        foreach (array_keys($execution) as $name) {
            $this->assertFileExists($restored->get_execution_files_directory() . util\file_group::encodefilename($name));
        }
        $this->assertSame($expectedrequired, $original->get_required_fgm()->getallfiles());
        $this->assertSame($expectedexecution, $original->get_execution_fgm()->getallfiles());
        $this->assertSame($keep, $original->get_execution_fgm()->getfilekeeplist());
    }

    /**
     * Back up and restore one activity without using the deprecated duplication API.
     *
     * @param int $cmid Source course module ID.
     * @param int $courseid Destination course ID.
     * @return int Restored course module ID.
     */
    private function backup_and_restore_activity(int $cmid, int $courseid): int {
        global $CFG, $USER;
        $CFG->backup_file_logger_level = \backup::LOG_NONE;
        $backup = new \backup_controller(
            \backup::TYPE_1ACTIVITY,
            $cmid,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id
        );
        $backupid = $backup->get_backupid();
        try {
            $backup->execute_plan();
        } finally {
            $backup->destroy();
        }

        $restore = new \restore_controller(
            $backupid,
            $courseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id,
            \backup::TARGET_CURRENT_ADDING
        );
        try {
            $this->assertTrue($restore->execute_precheck());
            $restore->execute_plan();
            $newcmid = null;
            foreach ($restore->get_plan()->get_tasks() as $task) {
                if ($task instanceof \restore_activity_task) {
                    $newcmid = $task->get_moduleid();
                    break;
                }
            }
            $this->assertNotNull($newcmid);
            return (int) $newcmid;
        } finally {
            $restore->destroy();
        }
    }

    /**
     * Both file handlers decode legacy binary files and reject unsafe relative names.
     *
     * @dataProvider restored_files_provider
     * @param string $name Backup filename relative to the file group.
     * @param int $encoding Whether the content is base64 encoded.
     * @param string $content Backup content.
     * @param string|null $expectedname Restored filename, or null when invalid.
     * @param string|null $expectedcontent Restored content, or null when invalid.
     */
    public function test_restore_file(
        string $name,
        int $encoding,
        string $content,
        ?string $expectedname,
        ?string $expectedcontent
    ): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->courseid = $course->id;
        $activity = $this->getDataGenerator()->create_module('vpl', ['course' => $course->id]);
        $step = $this->getMockBuilder(tests\testable_restore_vpl_activity_structure_step::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_new_parentid'])
            ->getMock();
        $step->expects($this->exactly(2))->method('get_new_parentid')->with('vpl')->willReturn($activity->id);
        $base = $CFG->dataroot . '/vpl_data/' . $activity->id . '/';
        $groups = ['required_files' => 'restore_required_file', 'execution_files' => 'restore_execution_file'];
        foreach ($groups as $group => $method) {
            $filename = $group . '/' . $name;
            $step->$method(['name' => $filename, 'encoding' => $encoding, 'content' => $content]);
            if ($expectedname === null) {
                if ($encoding === 1 && substr($filename, -4) === '.b64') {
                    $filename = substr($filename, 0, -4);
                }
                $this->assertDebuggingCalled(
                    'Invalid file path in backup: ' . $activity->id . '/' . $filename,
                    DEBUG_NORMAL
                );
                $this->assertDirectoryDoesNotExist($base . $group);
                $this->assertFileDoesNotExist($base . 'escape.txt');
            } else {
                $this->assertFileExists($base . $group . '/' . $expectedname);
                $this->assertSame($expectedcontent, file_get_contents($base . $group . '/' . $expectedname));
                $this->assertDebuggingNotCalled();
            }
        }
    }

    /**
     * Normal, encoded and malicious names encountered in backup XML.
     *
     * @return array
     */
    public static function restored_files_provider(): array {
        return [
            'plain text' => ['main.c', 0, "source\n", 'main.c', "source\n"],
            'encoded nested name' => ['src=helper.c', 0, 'helper', 'src=helper.c', 'helper'],
            'empty file' => ['empty.txt', 0, '', 'empty.txt', ''],
            'legacy binary suffix' => ['data.bin.b64', 1, base64_encode("\x00\xff"), 'data.bin', "\x00\xff"],
            'original binary suffix preserved' => ['original.b64.b64', 1, base64_encode("\x00"), 'original.b64', "\x00"],
            'binary without legacy suffix' => ['data.bin', 1, base64_encode("\x01"), 'data.bin', "\x01"],
            'parent traversal' => ['../escape.txt', 0, 'unsafe', null, null],
            'encoded traversal' => ['..=escape.txt', 0, 'unsafe', null, null],
            'encoded current directory' => ['.=escape.txt', 0, 'unsafe', null, null],
            'encoded empty component' => ['sub==escape.txt', 0, 'unsafe', null, null],
            'encoded leading option' => ['sub=-rf', 0, 'unsafe', null, null],
            'encoded trailing separator' => ['sub=', 0, 'unsafe', null, null],
            'empty component' => ['/escape.txt', 0, 'unsafe', null, null],
            'backslash' => ['sub\\escape.txt', 0, 'unsafe', null, null],
            'wildcard' => ['file*', 0, 'unsafe', null, null],
            'null byte' => ["file\x00name", 0, 'unsafe', null, null],
            'invalid name after suffix removal' => ['...b64', 1, base64_encode('unsafe'), null, null],
        ];
    }

    /**
     * File groups exercising text, encoded relative names and base64 binary data.
     *
     * @return array
     */
    public static function file_groups_provider(): array {
        return [
            'empty file groups' => [[], [], []],
            'text, nested, empty and binary files' => [
                [
                    'main.c' => "int main(void) { return 0; }\n",
                    'src/helper.c' => "/* <tag> & XML-sensitive text */\n",
                    'empty.txt' => '',
                    'data.bin' => "\x00\x01\xff\r\n",
                    'original.b64' => "\x00\x02\xfe",
                ],
                [
                    'vpl_run.sh' => "#!/bin/sh\nprintf '%s\\n' 'Hello'\n",
                    'tools/helper.sh' => "#!/bin/sh\nexit 0\n",
                    'empty.txt' => '',
                    'fixture.bin' => "\x00\x03\xfd\r\n",
                ],
                ['tools/helper.sh', 'fixture.bin'],
            ],
        ];
    }
}
