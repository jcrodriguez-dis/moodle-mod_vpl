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

use mod_vpl\tests\testable_edit;
use mod_vpl\tests\testable_jailserver_manager;
use mod_vpl\util\activity_modes;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/forms/edit.class.php');
require_once($CFG->dirroot . '/mod/vpl/tests/classes/testable_jailserver_manager.php');
require_once($CFG->dirroot . '/mod/vpl/tests/classes/testable_edit.php');

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
        testable_jailserver_manager::$client = null;
        testable_edit::$calls = [];
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

    /**
     * Invalid direct-run commands are rejected before any server request.
     *
     * @dataProvider invalid_directrun_command_provider
     * @param mixed $command Client-supplied command.
     */
    public function test_directrun_rejects_invalid_commands($command): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('vpl', ['course' => $course->id]);
        set_config('jail_servers', 'ls python http://unused.invalid', 'mod_vpl');
        $vpl = new \mod_vpl($instance->cmid);
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('invalidlanguage', 'vpl'));
        \mod_vpl_edit::directrun($vpl, $USER->id, (object) ['command' => $command, 'files' => []]);
    }

    /**
     * Reject arbitrary commands, shell metacharacters and unconfigured languages.
     *
     * @return array
     */
    public static function invalid_directrun_command_provider(): array {
        return [
            'shell command' => ['/bin/sh'],
            'shell suffix' => ['$python; echo injected'],
            'embedded newline' => ["\$python\n"],
            'path traversal' => ['$../../sh'],
            'empty language' => ['$'],
            'unconfigured language' => ['$unknown'],
            'empty command' => [''],
            'non-string command' => [[]],
            'missing prefix' => ['python'],
            'repeated prefix' => ['$$python'],
            'null command' => [null],
        ];
    }

    /**
     * The browser's language-server command starts a job with administrator limits.
     */
    public function test_directrun_accepts_client_command(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('vpl', ['course' => $course->id]);
        $server = 'https://jail.example/secret';
        set_config('jail_servers', 'ls python ' . $server, 'mod_vpl');
        set_config('use_xmlrpc', 0, 'mod_vpl');
        $settingsvalues = [
            'maxexetime' => 17,
            'maxexefilesize' => 4096,
            'maxexememory' => 1048576,
            'maxexeprocesses' => 8,
        ];
        foreach ($settingsvalues as $setting => $value) {
            set_config($setting, $value, 'mod_vpl');
        }
        $requests = [];
        $client = $this->createMock(\curl::class);
        $client->expects($this->exactly(2))->method('post')->willReturnCallback(
            function ($url, $body, $options) use ($server, &$requests) {
                $this->assertSame($server, $url);
                $request = json_decode($body);
                $requests[] = $request;
                $result = $request->method === 'available' ? ['status' => 'ready'] : [
                    'executionticket' => 'execution-test',
                    'adminticket' => 'admin-test',
                    'homepath' => '/home/prisoner',
                    'port' => 8080,
                    'secureport' => 8443,
                ];
                return json_encode(['id' => $request->id, 'result' => $result]);
            }
        );
        $client->method('get_errno')->willReturn(0);
        $client->method('get_info')->willReturn(['http_code' => 200]);
        $client->method('get_raw_response')->willReturn(["HTTP/1.1 200 OK\r\n", "\r\n"]);
        $client->error = '';
        testable_jailserver_manager::$client = $client;
        $vpl = new \mod_vpl($instance->cmid);
        $before = time();
        $response = testable_edit::directrun($vpl, $USER->id, (object) [
            'command' => '$python',
            'files' => ['main.py' => 'print(1)'],
        ]);
        $this->assertSame(['available', 'directrun'], array_column($requests, 'method'));
        $data = $requests[1]->params;
        $this->assertSame([17, 4096, 1048576, 8], [
            $data->maxtime, $data->maxfilesize, $data->maxmemory, $data->maxprocesses,
        ]);
        $this->assertSame('print(1)', $data->files->{'main.py'});
        $this->assertStringContainsString('/usr/local/bin/vpl/python_ls', $data->files->{'.vpl_directrun.sh'});
        $this->assertSame('execution-test/execute', $response->executionPath);
        $process = $DB->get_record('vpl_running_processes', ['id' => $response->processid], '*', MUST_EXIST);
        $this->assertEquals($USER->id, $process->userid);
        $this->assertEquals($instance->id, $process->vpl);
        $this->assertEquals(\mod_vpl_submission_CE::TDIRECTRUN, $process->type);
        $this->assertSame('admin-test', $process->adminticket);
        $this->assertGreaterThanOrEqual($before + 17, (int) $process->time_limit);
        $this->assertLessThanOrEqual(time() + 17, (int) $process->time_limit);
    }

    /**
     * Existing-job actions remain available when a student cannot submit.
     *
     * @dataProvider existing_process_action_provider
     * @param string $state Deadline, example or hidden activity.
     * @param string $action Retrieve or cancel.
     */
    public function test_existing_process_actions(string $state, string $action): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $instance = $generator->create_module('vpl', [
            'course' => $course->id,
            'startdate' => time() - 3600,
            'duedate' => $state === 'deadline' ? time() - 60 : 0,
            'activity_mode' => $state === 'example' ? activity_modes::EXAMPLE
                : ($state === 'hidden' ? activity_modes::NOSTUDENTS : activity_modes::NORMAL),
        ]);
        $this->setUser($student);
        $vpl = new \mod_vpl($instance->cmid);
        $this->assertFalse($vpl->is_submit_able());
        $this->assertSame($state !== 'hidden', $vpl->is_visible());
        if ($state === 'hidden') {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage(get_string('notavailable'));
        }
        try {
            $response = testable_edit::process_action($vpl, $student->id, $action, (object) ['processid' => 17]);
            $this->assertEquals([[$action, $instance->id, $student->id, 17]], testable_edit::$calls);
            if ($action === 'retrieve') {
                $this->assertSame('completed', $response->execution);
                $this->assertSame('7', $response->grade);
            } else {
                $this->assertSame('', $response->error);
            }
        } finally {
            if ($state === 'hidden') {
                $this->assertSame([], testable_edit::$calls);
            }
        }
    }

    /**
     * Cover each existing-job operation and retain activity visibility restrictions.
     *
     * @return array
     */
    public static function existing_process_action_provider(): array {
        return [
            'retrieve after deadline' => ['deadline', 'retrieve'],
            'cancel after deadline' => ['deadline', 'cancel'],
            'retrieve in example mode' => ['example', 'retrieve'],
            'cancel in example mode' => ['example', 'cancel'],
            'retrieve in hidden mode' => ['hidden', 'retrieve'],
            'cancel in hidden mode' => ['hidden', 'cancel'],
        ];
    }
}
