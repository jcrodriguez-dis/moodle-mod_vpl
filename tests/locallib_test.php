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
 * Unit tests for mod/vpl/locallib.php.
 *
 * @package mod_vpl
 * @copyright  Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');

class mod_vpl_locallib_testcase extends advanced_testcase {
    public function test_vpl_delete_dir() {
        global $CFG;
        $text = 'Example text';
        $testdir = $CFG->dirroot .'/mod/vpl/test/tmp';
        // Dir empty.
        mkdir($testdir);
        $this->assertTrue( is_dir($testdir) );
        vpl_delete_dir($testdir);
        $this->assertFalse( is_dir($testdir) );
        // Dir complex.
        mkdir($testdir . '/a1/b1/c1');
        file_put_contents ($testdir . '/a1/b1/c1/t1' ,$text);
        mkdir($testdir . '/a1/b1/c2');
        file_put_contents ($testdir . '/a1/b1/t1' ,$text);
        file_put_contents ($testdir . '/a1/b1/t2' ,$text);
        mkdir($testdir . '/a1/b2/c1');
        file_put_contents ($testdir . '/a1/b2/t1' ,$text);
        mkdir($testdir . '/a1/b3');
        file_put_contents ($testdir . '/a1/t1' ,$text);
        $this->assertTrue( is_dir($testdir) );
        vpl_delete_dir($testdir);
        $this->assertFalse( is_dir($testdir) );
    }
}
