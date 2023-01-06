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
 * Unit tests for classes at /mod/vpl/similarity/similarity_sources.class.php
 *
 * @package mod_vpl
 * @copyright Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl;

use \vpl_similarity_preprocess;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/mod/vpl/vpl_submission_CE.class.php');
require_once($CFG->dirroot . '/mod/vpl/similarity/similarity_sources.class.php');


/**
 * Unit tests for vpl_similarity_preprocess class.
 * @group mod_vpl
 * @group mod_vpl_vplt
 * @group mod_vpl_similarity
 * @group mod_vpl_similarity_preprocess
 */
class similarity_test extends base_test {

    /**
     * Method to create test fixture
     */
    protected function setUp(): void {
        // No fixture.
    }

    /**
     * Method to test vpl_similarity_preprocess::get_zip_filepath
     * @covers \vpl_similarity_preprocess::get_zip_filepath
     */
    public function test_get_zip_filepath() {
        global $CFG;
        $base = $CFG->dataroot . '/temp/vpl_zip/';
        $expet = $base . '2_z1';
        $res = vpl_similarity_preprocess::get_zip_filepath(2, 'z1');
        $this->assertEquals($expet, $res);
        $expet = $base . '456_z1.algo';
        $res = vpl_similarity_preprocess::get_zip_filepath(456, 'z1.algo');
        $this->assertEquals($expet, $res);
        $expet = $base . '45633_z1.algo';
        $res = vpl_similarity_preprocess::get_zip_filepath(45633, '/valor/h.33/nada/z1.algo');
        $this->assertEquals($expet, $res);
    }

    /**
     * Method to test vpl_similarity_preprocess::create_zip_file
     * @covers \vpl_similarity_preprocess::create_zip_file
     */
    public function test_create_zip_file() {
        $path = vpl_similarity_preprocess::get_zip_filepath(434, '/asg/z1');
        vpl_similarity_preprocess::create_zip_file(434, '/asg/z1', 'contents');
        $this->assertTrue(is_readable($path), $path);
        $contents = file_get_contents($path);
        $this->assertEquals($contents, 'contents');
        $path = vpl_similarity_preprocess::get_zip_filepath(0, '1');
        vpl_similarity_preprocess::create_zip_file(0, '1', 'contenido');
        $this->assertTrue(is_readable($path), $path);
        $contents = file_get_contents($path);
        $this->assertEquals($contents, 'contenido');
    }
}
