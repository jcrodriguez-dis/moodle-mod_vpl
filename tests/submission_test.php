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
 * Unit tests for class mod_vpl_submission mod/vpl/vpl_submission.class.php
 *
 * @package mod_vpl
 * @copyright Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl;

use mod_vpl_submission;
use mod_vpl_submission_CE;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/mod/vpl/vpl_submission_CE.class.php');

use mod_vpl\tests\base_fixture;

/**
 * Unit tests for submission class.
 * @group mod_vpl
 * @group mod_vpl_submission
 */
final class submission_test extends base_fixture {
    /**
     * Method to create test fixture
     */
    protected function setup(): void {
        parent::setup();
        $this->setupinstances();
    }

    /**
     * Method to test mod_vpl_submission::remove_grade_reduction in title
     * @covers \mod_vpl_submission::remove_grade_reduction
     */
    public function test_remove_grade_reduction(): void {
        $this->assertEquals('Example no match', mod_vpl_submission::remove_grade_reduction('Example no match'));
        $this->assertEquals('Other no match', mod_vpl_submission::remove_grade_reduction('Other no match'));
        $this->assertEquals('-', mod_vpl_submission::remove_grade_reduction('-'));
        $this->assertEquals('- Title with no grade  ', mod_vpl_submission::remove_grade_reduction('- Title with no grade  '));
        $this->assertEquals('- Title with grade ', mod_vpl_submission::remove_grade_reduction('- Title with grade (-4)'));
        $this->assertEquals('- Title with grade ', mod_vpl_submission::remove_grade_reduction('- Title with grade ( -4 )'));
        $this->assertEquals('- Title with grade', mod_vpl_submission::remove_grade_reduction('- Title with grade( - 4 )'));
        $this->assertEquals('- Title with grade', mod_vpl_submission::remove_grade_reduction('- Title with grade( - 4.0 )'));
        $this->assertEquals('- Title with grade', mod_vpl_submission::remove_grade_reduction('- Title with grade(-.0010)'));
    }
    /**
     * Method to test mod_vpl_submission_CE::adaptbinaryfiles
     * @covers \mod_vpl_submission_CE::adaptbinaryfiles
     */
    public function test_adaptbinaryfiles(): void {
        $data = new \stdClass();
        $data->filestodelete = [];
        $files = [];
        mod_vpl_submission_CE::adaptbinaryfiles($data, $files);
        $this->assertCount(0, $files);
        $this->assertCount(0, $data->filestodelete);
        $this->assertCount(0, $data->files);
        $this->assertCount(0, $data->fileencoding);

        $data = new \stdClass();
        $data->filestodelete = [];
        $files = ['a.c' => 'a', 'b.c' => 'b'];
        mod_vpl_submission_CE::adaptbinaryfiles($data, $files);
        $this->assertCount(2, $files);
        $this->assertEquals('', $files['a.c']);
        $this->assertEquals('', $files['b.c']);
        $this->assertCount(0, $data->filestodelete);
        $this->assertCount(2, $data->files);
        $this->assertEquals('a', $data->files['a.c']);
        $this->assertEquals('b', $data->files['b.c']);
        $this->assertCount(2, $data->files);
        $this->assertEquals('a', $data->files['a.c']);
        $this->assertEquals('b', $data->files['b.c']);
        $this->assertCount(2, $data->fileencoding);
        $this->assertEquals(0, $data->fileencoding['a.c']);
        $this->assertEquals(0, $data->fileencoding['b.c']);

        $data = new \stdClass();
        $data->filestodelete = ['algo' => 1];
        $files = ['a.c' => 'a', 'a.jpg' => 'b'];
        mod_vpl_submission_CE::adaptbinaryfiles($data, $files);
        $this->assertCount(2, $files);
        $this->assertEquals('', $files['a.c']);
        $this->assertEquals('', $files['a.jpg']);
        $this->assertCount(2, $data->filestodelete);
        $this->assertEquals(1, $data->filestodelete['algo']);
        $this->assertEquals(1, $data->filestodelete['a.jpg.b64']);
        $this->assertCount(2, $data->files);
        $this->assertEquals('a', $data->files['a.c']);
        $this->assertEquals(base64_encode('b'), $data->files['a.jpg.b64']);
        $this->assertCount(2, $data->fileencoding);
        $this->assertEquals(0, $data->fileencoding['a.c']);
        $this->assertEquals(1, $data->fileencoding['a.jpg.b64']);
    }

    /**
     * Method to test programming language detection.
     * @covers \mod_vpl_submission_CE::get_pln
     */
    public function test_get_pln(): void {
        $this->assertEquals('c', mod_vpl_submission_CE::get_pln(['main.c']));
        $this->assertEquals('make', mod_vpl_submission_CE::get_pln(['Makefile']));
        $this->assertEquals('default', mod_vpl_submission_CE::get_pln(['README']));
    }

    /**
     * Method to test script selection.
     * @covers \mod_vpl_submission_CE::get_script
     */
    public function test_get_script(): void {
        $data = new \stdClass();
        $script = mod_vpl_submission_CE::get_script('run', 'php', $data);
        $this->assertStringContainsString('$PHP -f', $script);

        $script = mod_vpl_submission_CE::get_script('run', 'unknown', $data);
        $this->assertNotEmpty($script);

        $this->expectException(\coding_exception::class);
        mod_vpl_submission_CE::get_script('invalid', 'php', $data);
    }

    /**
     * Method to test script collection for each execution type.
     * @covers \mod_vpl_submission_CE::get_scripts
     */
    public function test_get_scripts(): void {
        $data = (object) [
            'pln' => 'php',
            'type' => mod_vpl_submission_CE::TRUN,
        ];
        $scripts = mod_vpl_submission_CE::get_scripts($this->vpldefault, $data);
        $this->assertArrayHasKey('vpl_run.sh', $scripts);
        $this->assertArrayNotHasKey('vpl_debug.sh', $scripts);

        $data->type = mod_vpl_submission_CE::TDEBUG;
        $scripts = mod_vpl_submission_CE::get_scripts($this->vpldefault, $data);
        $this->assertArrayHasKey('vpl_debug.sh', $scripts);

        $data->type = mod_vpl_submission_CE::TEVALUATE;
        $scripts = mod_vpl_submission_CE::get_scripts($this->vpldefault, $data);
        $this->assertArrayHasKey('vpl_evaluate.sh', $scripts);
        $this->assertArrayHasKey('vpl_evaluate.cpp', $scripts);

        $data->type = mod_vpl_submission_CE::TTESTEVALUATE;
        $scripts = mod_vpl_submission_CE::get_scripts($this->vpldefault, $data);
        $this->assertArrayHasKey('vpl_test_evaluate.sh', $scripts);

        $this->setUser($this->editingteachers[0]);
        $data->pln = 'all';
        $data->type = mod_vpl_submission_CE::TRUN;
        $scripts = mod_vpl_submission_CE::get_scripts($this->vpldefault, $data);
        $this->assertArrayHasKey('php_run.sh', $scripts);
    }

    /**
     * Method to test execution base data collection.
     * @covers \mod_vpl_submission_CE::prepare_execution_base
     */
    public function test_prepare_execution_base(): void {
        $data = mod_vpl_submission_CE::prepare_execution_base(
            $this->vplonefile,
            mod_vpl_submission_CE::TRUN
        );

        $this->assertEquals($this->vplonefile->get_instance()->id, $data->activityid);
        $this->assertEquals(mod_vpl_submission_CE::TRUN, $data->type);
        $this->assertArrayHasKey('vpl_run.sh', $data->files);
        $this->assertArrayNotHasKey('vpl_debug.sh', $data->files);
    }

    /**
     * Method to test submission data preparation.
     * @covers \mod_vpl_submission_CE::prepare_execution_submission
     */
    public function test_prepare_execution_submission(): void {
        $submissionrecord = $this->vplonefile->last_user_submission($this->students[0]->id);
        $submission = new mod_vpl_submission_CE($this->vplonefile, $submissionrecord);
        $data = (object) [
            'files' => [],
            'maxmemory' => 1024,
        ];

        $data = $submission->prepare_execution_submission($data);

        $this->assertEquals('c', $data->pln);
        $this->assertEquals(['a.c'], $data->submittedlist);
        $this->assertEquals("int main(){\nprintf(\"Hola\");\n}", $data->files['a.c']);
        $this->assertEquals($this->students[0]->id, $data->userid);
    }

    /**
     * Method to test evaluation test preparation.
     * @covers \mod_vpl_submission_CE::prepare_execution_evaluation_tests
     */
    public function test_prepare_execution_evaluation_tests(): void {
        $this->vplonefile->get_execution_fgm()->addallfiles([
            'vpl_evaluation_tests/case/a.c' => 'int main() {}',
        ]);
        $data = (object) [
            'activityid' => $this->vplonefile->get_instance()->id,
            'files' => [],
        ];

        $data = mod_vpl_submission_CE::prepare_execution_evaluation_tests($data);

        $this->assertEquals('c', $data->pln);
        $this->assertSame([], $data->submittedlist);
        $this->assertArrayHasKey(
            'vpl_evaluation_tests/case/.localenvironment.sh',
            $data->files
        );
        $this->assertStringContainsString('VPL_SUBFILE0', $data->files[
            'vpl_evaluation_tests/case/.localenvironment.sh'
        ]);
    }

    /**
     * Method to test run mode selection.
     * @covers \mod_vpl_submission_CE::get_run_mode
     */
    public function test_get_run_mode(): void {
        $data = (object) [
            'files' => [],
        ];
        foreach (['2', '3', '4', '5'] as $mode) {
            $data->run_mode = $mode;
            $this->assertSame($mode, mod_vpl_submission_CE::get_run_mode($data));
        }

        $data->run_mode = '6';
        $data->files = [
            'teacher.sh' => mod_vpl_submission_CE::RUN_GUI_MODE_MARK,
            'main.py' => mod_vpl_submission_CE::RUN_TEXT_MODE_MARK,
        ];
        $data->submittedlist = ['main.py'];
        $this->assertSame('3', mod_vpl_submission_CE::get_run_mode($data));

        $data->files = [
            'first.py' => mod_vpl_submission_CE::RUN_TEXT_MODE_MARK,
            'second.py' => mod_vpl_submission_CE::RUN_TEXTINGUI_MODE_MARK,
        ];
        $data->submittedlist = ['second.py', 'first.py', 'missing.py'];
        $this->assertSame('5', mod_vpl_submission_CE::get_run_mode($data));

        $data->pln = 'all';
        $data->run_mode = '5';
        $this->assertSame('0', mod_vpl_submission_CE::get_run_mode($data));

        $data->pln = null;
        $data->run_mode = '6';
        $data->files = [
            'late.py' => str_repeat('x', 2 * 1024) . mod_vpl_submission_CE::RUN_GUI_MODE_MARK,
        ];
        $this->assertSame('0', mod_vpl_submission_CE::get_run_mode($data));
    }

    /**
     * Method to test environment variable generation.
     * @covers \mod_vpl_submission_CE::get_environment_variables
     */
    public function test_get_environment_variables(): void {
        $data = (object) [
            'type' => mod_vpl_submission_CE::TRUN,
            'userid' => $this->students[0]->id,
            'groupid' => 0,
            'run_mode' => '5',
            'files' => [],
        ];
        $variables = mod_vpl_submission_CE::get_environment_variables($this->vplonefile, $data);

        $this->assertEquals('5', $variables['VPL_RUN_MODE']);
        $this->assertEquals($this->students[0]->id, $variables['MOODLE_USER_ID']);
        $this->assertEquals($this->students[0]->email, $variables['MOODLE_USER_EMAIL']);
        $this->assertArrayHasKey('VPL_COMPILATIONFAILED', $variables);
    }

    /**
     * Method to test environment and script file preparation.
     * @covers \mod_vpl_submission_CE::prepare_execution_info
     */
    public function test_prepare_execution_info(): void {
        $data = (object) [
            'activityid' => $this->vplonefile->get_instance()->id,
            'type' => mod_vpl_submission_CE::TRUN,
            'pln' => 'php',
            'files' => [
                'vpl_run.sh' => 'echo custom',
            ],
            'filestodelete' => [],
            'submittedlist' => ['main.php'],
            'run_mode' => '1',
            'evaluator' => '',
        ];

        $data = mod_vpl_submission_CE::prepare_execution_info($data);

        $this->assertStringStartsWith("#!/bin/bash\n", $data->files['vpl_run.sh']);
        $this->assertArrayHasKey('vpl_environment.sh', $data->files);
        $this->assertArrayHasKey('common_script.sh', $data->files);
        $this->assertStringContainsString('VPL_SUBFILE0', $data->files['vpl_environment.sh']);
    }

    /**
     * Method to test submitted file environment exports.
     * @covers \mod_vpl_submission_CE::get_bash_export_for_subfiles
     */
    public function test_get_bash_export_for_subfiles(): void {
        $content = mod_vpl_submission_CE::get_bash_export_for_subfiles(['main.c', 'helper.h']);

        $this->assertStringContainsString("export VPL_SUBFILE0='main.c'\n", $content);
        $this->assertStringContainsString("export VPL_SUBFILE1='helper.h'\n", $content);
        $this->assertStringContainsString("export VPL_SUBFILES=\"main.c\nhelper.h\n\"\n", $content);
    }

    /**
     * Method to test complete execution data preparation.
     * @covers \mod_vpl_submission_CE::prepare_execution
     */
    public function test_prepare_execution(): void {
        $submissionrecord = $this->vplonefile->last_user_submission($this->students[0]->id);
        $submission = new mod_vpl_submission_CE($this->vplonefile, $submissionrecord);
        $data = $submission->prepare_execution(mod_vpl_submission_CE::TRUN);

        $this->assertEquals('c', $data->pln);
        $this->assertArrayHasKey('vpl_environment.sh', $data->files);
        $this->assertArrayHasKey('common_script.sh', $data->files);
    }

    /**
     * Method to test action logging.
     * @covers \mod_vpl_submission_CE::log_action
     */
    public function test_log_action(): void {
        global $CFG;

        \vpl_jailserver_manager::generate_jsonrpcid();
        $id = \vpl_jailserver_manager::get_jsonrpcid();
        $filename = $CFG->dataroot . "/temp/vpl_test_{$id}_log.json";
        mod_vpl_submission_CE::log_action('test', 'request', ['result' => 'ok']);

        $this->assertFileExists($filename);
        $this->assertStringContainsString('request', file_get_contents($filename));
        $this->assertStringContainsString('"result":"ok"', file_get_contents($filename));
        unlink($filename);
    }

    /**
     * Method to test no-process guards.
     * @covers \mod_vpl_submission_CE::jailreaction
     * @covers \mod_vpl_submission_CE::update
     * @covers \mod_vpl_submission_CE::cancelprocess
     */
    public function test_no_process_guards(): void {
        $submissionrecord = $this->vplonefile->last_user_submission($this->students[0]->id);
        $submission = new mod_vpl_submission_CE($this->vplonefile, $submissionrecord);

        $this->assertNull($submission->jailreaction('running'));
        $this->assertFalse(mod_vpl_submission_CE::update(
            $this->vplonefile,
            $this->students[0]->id,
            999999,
            ['main.c' => 'code']
        ));
        $submission->cancelprocess();
        $this->assertTrue(true);
    }

    /**
     * Method to test mod_vpl_submission::find_proposedgrade in evaluation
     * @covers \mod_vpl_submission::find_proposedgrade
     */
    public function test_find_proposedgrade(): void {
        $text = '';
        $expected = '';
        $this->assertEquals($expected, mod_vpl_submission::find_proposedgrade($text));

        $text = 'Grade :=>> value ';
        $expected = 'value';
        $this->assertEquals($expected, mod_vpl_submission::find_proposedgrade($text));

        $text = "noGrade :=>> bad\nGrade :=>> value\nGrade :=>> correct \n Grade :=>> incorrect";
        $expected = 'correct';
        $this->assertEquals($expected, mod_vpl_submission::find_proposedgrade($text));

        $text = "noGrade :=>> bad\r\nGrade :=>> value\r\nGrade :=>> correct \r\n Grade :=>> incorrect";
        $expected = 'correct';
        $this->assertEquals($expected, mod_vpl_submission::find_proposedgrade($text));

        $text = "noGrade :=>> bad\nGrade :=>> 4.86\ngrade :=>> correct \nGrade  :=>> incorrect";
        $expected = '4.86';
        $this->assertEquals($expected, mod_vpl_submission::find_proposedgrade($text));

        $text = "noGrade :=>> bad\r\nGrade :=>> 4.86\ngrade :=>> correct \nGrade  :=>> incorrect";
        $expected = '4.86';
        $this->assertEquals($expected, mod_vpl_submission::find_proposedgrade($text));
    }

    /**
     * Method to test mod_vpl_submission::find_proposedcomment in evaluation
     * @covers \mod_vpl_submission::find_proposedcomment
     */
    public function test_find_proposedcomment(): void {
        $text = '';
        $expected = '';
        $this->assertEquals($expected, mod_vpl_submission::find_proposedcomment($text));

        $text = 'Comment :=>>Comment in a line';
        $expected = "Comment in a line\n";
        $this->assertEquals($expected, mod_vpl_submission::find_proposedcomment($text));

        $text = 'Comment :=>>--- Comment in a line other staff   ';
        $expected = "--- Comment in a line other staff   \n";
        $this->assertEquals($expected, mod_vpl_submission::find_proposedcomment($text));

        $text = "\nComment :=>>--- Comment in a line other staff   \n No usefull thing\n\nGrade :=>> correct ";
        $expected = "--- Comment in a line other staff   \n";
        $this->assertEquals($expected, mod_vpl_submission::find_proposedcomment($text));

        $text = "\r\nComment :=>>--- Comment in a line other staff   \r\n No usefull thing\r\n\r\nGrade :=>> correct ";
        $expected = "--- Comment in a line other staff   \n";
        $this->assertEquals($expected, mod_vpl_submission::find_proposedcomment($text));

        $text = "<|--\ncomment1\n--|>";
        $expected = "comment1\n";
        $this->assertEquals($expected, mod_vpl_submission::find_proposedcomment($text));

        $text = "<|--\ncomment1\n--|>\n<|--\ncomment2\n--|>";
        $expected = "comment1\ncomment2\n";
        $this->assertEquals($expected, mod_vpl_submission::find_proposedcomment($text));

        $text = "lkj fsd\n<|--\ncomment1\n--|>\n k \n<|--\ncomment2\n--|>\n\ndh f";
        $expected = "comment1\ncomment2\n";
        $this->assertEquals($expected, mod_vpl_submission::find_proposedcomment($text));

        $text = "lkj fsd\n<|--\n comment1 \n--|>\nComment :=>>Comment in a line\n k \n<|--\n comment3 \n--|>\n\ndh f";
        $expected = " comment1 \nComment in a line\n comment3 \n";
        $this->assertEquals($expected, mod_vpl_submission::find_proposedcomment($text));

        $text = "l f\r\n<|--\n comment1 \n--|>\r\nComment :=>>Comment in a line\n k
                 \r\n<|--\r\n comment3 \r\n--|>\r\n\r\nd f\r";
        $expected = " comment1 \nComment in a line\n comment3 \n";
        $this->assertEquals($expected, mod_vpl_submission::find_proposedcomment($text));
    }
}
