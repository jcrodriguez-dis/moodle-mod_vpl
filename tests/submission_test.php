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

use \mod_vpl_submission;
use \mod_vpl_submission_CE;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/mod/vpl/vpl_submission_CE.class.php');

/**
 * Unit tests for submission class.
 * @group mod_vpl
 */
class submission_test extends base_test {

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
    public function test_remove_grade_reduction() {
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
    public function test_adaptbinaryfiles() {
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
}
