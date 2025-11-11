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
 * Unit tests for \mod_vpl\similarity\preprocess class.
 *
 * @package mod_vpl
 * @copyright Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl;

use mod_vpl\similarity\preprocess;


/**
 * Unit tests for \mod_vpl\similarity\preprocess class.
 * @group mod_vpl
 * @group mod_vpl_vplt
 * @group mod_vpl_similarity
 * @group mod_\mod_vpl\similarity\preprocess
 */
final class similarity_test extends \advanced_testcase {
    /**
     * Method to test \mod_vpl\similarity\preprocess::get_zip_filepath
     * @covers \\mod_vpl\similarity\preprocess::get_zip_filepath
     */
    public function test_get_zip_filepath(): void {
        global $CFG;
        $base = $CFG->dataroot . '/temp/vpl_zip/';
        $expect = $base . '2_z1';
        $res = \mod_vpl\similarity\preprocess::get_zip_filepath(2, 'z1');
        $this->assertEquals($expect, $res);
        $expect = $base . '456_z1.algo';
        $res = \mod_vpl\similarity\preprocess::get_zip_filepath(456, 'z1.algo');
        $this->assertEquals($expect, $res);
        $expect = $base . '45633_z1.algo';
        $res = \mod_vpl\similarity\preprocess::get_zip_filepath(45633, '/valor/h.33/nada/z1.algo');
        $this->assertEquals($expect, $res);
    }

    /**
     * Method to test \mod_vpl\similarity\preprocess::create_zip_file
     * @covers \\mod_vpl\similarity\preprocess::create_zip_file
     */
    public function test_create_zip_file(): void {
        $path = \mod_vpl\similarity\preprocess::get_zip_filepath(434, '/asg/z1');
        \mod_vpl\similarity\preprocess::create_zip_file(434, '/asg/z1', 'contents');
        $this->assertTrue(is_readable($path), $path);
        $contents = file_get_contents($path);
        $this->assertEquals($contents, 'contents');
        $path = \mod_vpl\similarity\preprocess::get_zip_filepath(0, '1');
        \mod_vpl\similarity\preprocess::create_zip_file(0, '1', 'contenido');
        $this->assertTrue(is_readable($path), $path);
        $contents = file_get_contents($path);
        $this->assertEquals($contents, 'contenido');
    }
}
