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
 * @copyright  Juan Carlos RodrÃ­guez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos RodrÃ­guez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl;

use \Exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');

/**
 * Unit tests for mod/vpl/locallib.php functions.
 * @group mod_vpl
 */
class locallib_test extends \advanced_testcase {
    /**
     * @covers \vpl_delete_dir
     */
    public function test_vpl_delete_dir() {
        global $CFG;
        $text = 'Example text';
        $testdir = $CFG->dataroot . '/temp/vpl_test/tmp';
        // Dir empty.
        mkdir($testdir, 0777, true);
        $this->assertTrue(is_writable($testdir) && is_dir($testdir));

        vpl_delete_dir($testdir);
        $this->assertFalse(file_exists($testdir) && is_dir($testdir));
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
        $this->assertTrue(is_writable($testdir) && is_dir($testdir));
        vpl_delete_dir($testdir);
        $this->assertFalse(file_exists($testdir) && is_dir($testdir),  $testdir);
    }

    public function internal_test_vpl_fopen($path, $text = 'Example text') {
        global $CFG;
        $testdir = $CFG->dataroot . '/temp/vpl_test/tmp';
        $fpath = $testdir . $path;
        $fp = vpl_fopen($fpath);
        $this->assertNotNull( $fp );
        fwrite($fp, $text);
        fclose($fp);
        $this->assertEquals( $text, file_get_contents($fpath) );
    }

    /**
     * @covers \vpl_fopen
     */
    public function test_vpl_fopen() {
        global $CFG;
        $testdir = $CFG->dataroot . '/temp/vpl_test/tmp';
        $text = 'Any thing is ok! 뭐든 괜찮아!';
        $this->internal_test_vpl_fopen( '/a1/b1/c1' );
        $this->internal_test_vpl_fopen( '/aaaaaaaaaaaaaaaa.bbb' );
        $this->internal_test_vpl_fopen( '/aaaaaaaaaaaaaaaa.bbb', 'Other text');
        $this->internal_test_vpl_fopen( '/nf.bbb', $text );
        $fpath = $testdir . '/nf.bbb';
        chmod($fpath, 0000);
        try {
            if (file_get_contents($fpath) == $text) {
                $chmodusefull = false;
            } else {
                $chmodusefull = true;
            }
        } catch (Exception $e) {
            $chmodusefull = false;
        }
        chmod($fpath, 0777);
        $bads = ['/a1/..', '/a1/.', '/', '/a1', '/a1/b1'];
        foreach ($bads as $bad) {
            try {
                $throwexception = false;
                $this->internal_test_vpl_fopen($bad);
            } catch (Exception $e) {
                $throwexception = true;
            }
            $this->assertTrue($throwexception, 'Exception expected');
        }
        // Checks patch for Windows filename limits.
        $bads = ['/a1/aux.java', '/a1/lpt9', '/com5.txt', '/prn', '/a1/con'];
        foreach ($bads as $bad) {
            $this->internal_test_vpl_fopen($bad, $text);
        }
        $this->assertTrue(vpl_delete_dir($testdir));
        // Windows does not check directories access control.
        if ($chmodusefull) {
            mkdir($testdir, 0777, true);
            chmod($testdir, 0000);
            $fpath = $testdir . '/a1/b1/c1';
            try {
                $throwexception = false;
                vpl_fwrite($fpath, $text);
            } catch (Exception $e) {
                $throwexception = true;
            }
            chmod($testdir, 0777);
            $this->assertTrue($throwexception, 'Exception expected');
            $this->assertTrue(vpl_delete_dir($testdir));
        }
    }

    /**
     * @covers \vpl_get_array_key
     */
    public function tes_vpl_get_array_key() {
        $array = array(0 => 'nothing', 1 => 'a', 2 => 'b', 5 => 'c', 1200 => 'd', 1500 => 'f');
        $this->assertEquals(1, vpl_get_array_key($array, 1));
        $this->assertEquals(2, vpl_get_array_key($array, 2));
        $this->assertEquals(5, vpl_get_array_key($array, 3));
        $this->assertEquals(5, vpl_get_array_key($array, 4));
        $this->assertEquals(5, vpl_get_array_key($array, 5));
        $this->assertEquals(1200, vpl_get_array_key($array, 6));
        $this->assertEquals(1200, vpl_get_array_key($array, 1100));
        $this->assertEquals(1200, vpl_get_array_key($array, 1200));
        $this->assertEquals(1500, vpl_get_array_key($array, 1201));
        $this->assertEquals(1500, vpl_get_array_key($array, 1800));
    }

    /**
     * @covers \vpl_fwrite
     */
    public function test_vpl_fwrite() {
        global $CFG;
        $text = 'Example text';
        $otext = 'Other text';
        $testdir = $CFG->dataroot . '/temp/vpl_test/tmp';
        // Dir empty.
        vpl_delete_dir($testdir);
        mkdir($testdir, 0777, true);
        $fpath = $testdir . '/a1/b1/c1';
        vpl_fwrite($fpath, $text);
        $this->assertEquals( $text, file_get_contents($fpath) );
        vpl_fwrite($fpath, $otext);
        $this->assertEquals( $otext, file_get_contents($fpath) );
        // Tests if the File System honor chmod.
        chmod($fpath, 0000);
        try {
            if (file_get_contents($fpath) == $otext) {
                $chmodusefull = false;
            } else {
                $chmodusefull = true;
            }
        } catch (Exception $e) {
            $chmodusefull = false;
        }
        chmod($fpath, 0777);
        $fpath = $testdir . '/aaaaaaaaaaaaaaaa.bbb';
        vpl_fwrite($fpath, $text);
        $this->assertEquals( $text, file_get_contents($fpath) );
        $fpath = $testdir . '/aaaaaaaaaaaaaaaa.bbb';
        vpl_fwrite($fpath, '');
        $this->assertEquals( '', file_get_contents($fpath) );
        $fpath = $testdir . '/nf.bbb';
        vpl_fwrite($fpath, '');
        $this->assertEquals( '', file_get_contents($fpath) );
        $bads = ['/a1/..', '/a1/.', '/', '/a1', '/a1/b1'];
        foreach ($bads as $bad) {
            $fpath = $testdir . $bad;
            try {
                $throwexception = false;
                vpl_fwrite($fpath, $text);
            } catch (Exception $e) {
                $throwexception = true;
            }
            $this->assertTrue($throwexception, 'Exception expected');
        }
        vpl_delete_dir($testdir);

        // If the File System honor chmod.
        if ($chmodusefull) {
            mkdir($testdir, 0777, true);
            chmod($testdir, 0000);
            $fpath = $testdir . '/a1/b1/c1';
            try {
                $throwexception = false;
                vpl_fwrite($fpath, $text);
            } catch (Exception $e) {
                $throwexception = true;
            }
            chmod($testdir, 0777);
            $this->assertTrue($throwexception, 'Exception expected');
            vpl_delete_dir($testdir);
        }
    }

    /**
     * @covers \vpl_get_set_session_var
     */
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
        $SESSION = new \stdClass();
        $SESSION->vpl_testvpl1 = 'testdata';
        $this->assertEquals('testdata', vpl_get_set_session_var('testvpl1', 'nada'));
        $this->assertEquals('nada', vpl_get_set_session_var('testvpl2', 'nada'));
        $_POST['testvpl3'] = 'algo';
        $this->assertEquals('algo', vpl_get_set_session_var('testvpl3', 'nada'));
        $SESSION->vpl_testvpl4 = 'testdata 4';
        $this->assertEquals('testdata 4', vpl_get_set_session_var('testvpl4', 'nada'));
        $_POST['testvpl5'] = 'algo 5';
        $this->assertEquals('algo 5', vpl_get_set_session_var('testvpl5', 'nada'));
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

    /**
     * @covers \vpl_is_image
     */
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

    /**
     * @covers \vpl_truncate_string
     */
    public function test_vpl_truncate_string() {
        $var = 'testvpl3';
        vpl_truncate_string($var, 3);
        $this->assertEquals('...', $var);
        $var = 'testvpl3';
        vpl_truncate_string($var, 4);
        $this->assertEquals('t...', $var);
        $var = 'testvpl3';
        vpl_truncate_string($var, 5);
        $this->assertEquals('te...', $var);
        $var = 'testvpl3';
        vpl_truncate_string($var, 8);
        $this->assertEquals('testvpl3', $var);
        $var = 'testvpl3';
        vpl_truncate_string($var, 7);
        $this->assertEquals('test...', $var);
        $var = 'testvpl3';
        vpl_truncate_string($var, 80);
        $this->assertEquals('testvpl3', $var);
    }

    /**
     * @covers \vpl_bash_export
     */
    public function test_vpl_bash_export() {
        $this->assertEquals("export VPL=3\n", vpl_bash_export('VPL', 3));
        $this->assertEquals("export ALGO='text'\n", vpl_bash_export('ALGO', 'text'));
        $this->assertEquals("export ALGO='te\" \$xt'\n", vpl_bash_export('ALGO', 'te" $xt'));
        $this->assertEquals("export ALGO='te'\"'\"''\"'\"'xt'\"'\"''\n", vpl_bash_export('ALGO', "te''xt'"));
    }

    /**
     * @covers \vpl_is_valid_file_name
     */
    public function test_vpl_is_valid_file_name() {
        $this->assertTrue(vpl_is_valid_file_name('filename.PNG.png'));
        $this->assertTrue(vpl_is_valid_file_name('filename kjhfs adkjhkafs fdj kfsdhahfskdh'));
        $this->assertTrue(vpl_is_valid_file_name('f'));
        $this->assertTrue(vpl_is_valid_file_name('fj'));
        $this->assertTrue(vpl_is_valid_file_name('.f'));
        $this->assertTrue(vpl_is_valid_file_name('f.'));
        $this->assertTrue(vpl_is_valid_file_name('.f.'));
        $this->assertTrue(vpl_is_valid_file_name('..f'));
        $this->assertFalse(vpl_is_valid_file_name('.'));
        $this->assertFalse(vpl_is_valid_file_name('..'));
        $this->assertFalse(vpl_is_valid_file_name(' '));
        $this->assertFalse(vpl_is_valid_file_name('             '));
        $this->assertFalse(vpl_is_valid_file_name('a/b'));
        $this->assertFalse(vpl_is_valid_file_name('a\b'));
        $this->assertFalse(vpl_is_valid_file_name('\.'));
    }

    /**
     * @covers \vpl_check_network
     */
    public function test_vpl_check_network() {
        // Tests exact IPs.
        $this->assertTrue(vpl_check_network('1.2.3.4', '1.2.3.4'));
        $this->assertFalse(vpl_check_network('199.193.245.44', '199.193.245.4'));
        $this->assertTrue(vpl_check_network('1.2.3.4, 199.193.245.44', '199.193.245.44'));
        $this->assertTrue(vpl_check_network('1.2.3.4, 199.193.245.44', '1.2.3.4'));
        $this->assertFalse(vpl_check_network('1.2.3.4, 199.193.245.44', '1.2.3.41'));
        $this->assertTrue(vpl_check_network('1.2.3.4, 199.193.245.44, 77.77.88.99', '77.77.88.99'));
        $this->assertTrue(vpl_check_network('1.2.3.4, 199.193.245.44, 77.77.88.99', '199.193.245.44'));
        // Tests subnets.
        $this->assertTrue(vpl_check_network('1.2.3', '1.2.3.4'));
        $this->assertFalse(vpl_check_network('199.193', '199.194.245.4'));
        $this->assertTrue(vpl_check_network('1.2, 199.193.245.', '199.193.245.44'));
        $this->assertTrue(vpl_check_network('1.2, 199.193.245.44', '1.2.3.4'));
        $this->assertFalse(vpl_check_network('1.2.3., 199.193.245.44', '1.2.33.4'));
        $this->assertTrue(vpl_check_network('1.2.3, 199.193.245.44, 77.', '77.77.88.99'));
        $this->assertTrue(vpl_check_network('1.2.3.4, 199., 77.77.88.99', '199.193.245.44'));
    }
}
