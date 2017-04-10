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
        $testdir = $CFG->dirroot . '/mod/vpl/test/tmp';
        // Dir empty.
        mkdir($testdir, 0777, true);
        $this->assertTrue( is_dir($testdir) );
        vpl_delete_dir($testdir);
        $this->assertFalse( is_dir($testdir) );
        // Dir complex.
        mkdir($testdir . '/a1/b1/c1', 0777, true);
        file_put_contents ($testdir . '/a1/b1/c1/t1', $text);
        mkdir($testdir . '/a1/b1/c2', 0777, true);
        file_put_contents ($testdir . '/a1/b1/t1', $text);
        file_put_contents ($testdir . '/a1/b1/t2', $text);
        mkdir($testdir . '/a1/b2/c1', 0777, true);
        file_put_contents ($testdir . '/a1/b2/t1', $text);
        mkdir($testdir . '/a1/b3', 0777, true);
        file_put_contents ($testdir . '/a1/t1', $text);
        $this->assertTrue( is_dir($testdir) );
        vpl_delete_dir($testdir);
        $this->assertFalse( is_dir($testdir) );
    }
    public function test_vpl_get_set_session_var() {
        global $SESSION;
        $nosession = false;
        $nopost = false;
        if ( !isset ($SESSION) ) {
            $nosession = true;
        } else {
            $sessionsave = $SESSION;
        }
        if ( !isset ($_POST) ) {
            $nopost = true;
        } else {
            $postsave = $_POST;
        }
        $SESSION = new stdClass();
        $SESSION->vpl_testvpl1 = 'testdata';
        $this->assertEquals('testdata', vpl_get_set_session_var('testvpl1', 'nada'));
        $this->assertEquals('nada', vpl_get_set_session_var('testvpl2', 'nada'));
        $_POST['testvpl3'] = 'algo';
        $this->assertEquals('algo', vpl_get_set_session_var('testvpl3', 'nada'));
        if ( $nopost) {
            unset($_POST);
        } else {
            $_POST = $postsave;
        }
        if ( $nosession ) {
            unset($SESSION);
        } else {
            $SESSION = $sessionsave;
        }
    }
    public function test_vpl_is_image() {
        $this->assertTrue(vpl_is_image('filename.gif'));
        $this->assertTrue(vpl_is_image('filename.jpg'));
        $this->assertTrue(vpl_is_image('filename.jpeg'));
        $this->assertTrue(vpl_is_image('filename.png'));
        $this->assertTrue(vpl_is_image('filename.ico'));
        $this->assertTrue(vpl_is_image('filename.Jpg'));
        $this->assertTrue(vpl_is_image('filename.JPEG'));
        $this->assertTrue(vpl_is_image('filename.PNG'));
        $this->assertFalse(vpl_is_image('filename.db'));
        $this->assertFalse(vpl_is_image('filename.pdf'));
        $this->assertFalse(vpl_is_image('filename.ico.old'));
        $this->assertFalse(vpl_is_image('a.ico/filename.pdf'));
        $this->assertFalse(vpl_is_image('a.ico/filename_jpg'));
        $this->assertFalse(vpl_is_image('a.ico/jpg'));
    }

    public function test_vpl_truncate_string() {
        $this->assertEquals('...', vpl_truncate_string('testvpl3', 3));
        $this->assertEquals('t...', vpl_truncate_string('testvpl3', 4));
        $this->assertEquals('te...', vpl_truncate_string('testvpl3', 5));
        $this->assertEquals('testvpl3', vpl_truncate_string('testvpl3', 8));
        $this->assertEquals('test...', vpl_truncate_string('testvpl3', 7));
        $this->assertEquals('testvpl3', vpl_truncate_string('testvpl3', 80));
    }

    public function test_vpl_bash_export() {
        $this->assertEquals('export VPL=3', vpl_bash_export('VPL', 3));
        $this->assertEquals('export ALGO=\'text\'', vpl_bash_export('ALGO', 'text'));
        $this->assertEquals('export ALGO=\'te" $xt\'', vpl_bash_export('ALGO', 'te" $xt'));
        $this->assertEquals('export ALGO=\'te\\\'\\\'xt\\\'\'', vpl_bash_export('ALGO', "te''xt'"));
    }

    public function test_vpl_is_valid_file_name() {
        $this->assertTrue(vpl_is_valid_file_name('filename.PNG.png'));
        $this->assertTrue(vpl_is_valid_file_name('filename kjhfs adkjhkafsñ fdj kfsdhahfskdh'));
        $this->assertTrue(vpl_is_valid_file_name('f'));
        $this->assertTrue(vpl_is_valid_file_name('fj'));
        $this->assertFalse(vpl_is_valid_file_name('.'));
        $this->assertFalse(vpl_is_valid_file_name('..'));
        $this->assertFalse(vpl_is_valid_file_name(' '));
        $this->assertFalse(vpl_is_valid_file_name('             '));
        $this->assertFalse(vpl_is_valid_file_name('\.'));
    }
}
