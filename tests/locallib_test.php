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
 * @copyright  Juan Carlos Rodrí­guez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos RodrÃ­guez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl;

use Exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');

/**
 * Unit tests for mod/vpl/locallib.php functions.
 *
 * @group mod_vpl
 * @group mod_vpl_locallib
 */
final class locallib_test extends \advanced_testcase {
    /**
     * Tests the function vpl_delete_dir.
     *
     * @covers \vpl_delete_dir
     */
    public function test_vpl_delete_dir(): void {
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
        file_put_contents($testdir . '/a1/b1/c1/t1', $text);
        mkdir($testdir . '/a1/b1/c2', 0777, true);
        file_put_contents($testdir . '/a1/b1/t1', $text);
        file_put_contents($testdir . '/a1/b1/t2', $text);
        mkdir($testdir . '/a1/b2/c1', 0777, true);
        file_put_contents($testdir . '/a1/b2/t1', $text);
        mkdir($testdir . '/a1/b3', 0777, true);
        file_put_contents($testdir . '/a1/t1', $text);
        $this->assertTrue(is_writable($testdir) && is_dir($testdir));
        vpl_delete_dir($testdir);
        $this->assertFalse(file_exists($testdir) && is_dir($testdir), $testdir);
    }

    /**
     * Helper method to test vpl_fopen.
     *
     * @param string $path The path to the file.
     * @param string $text The text to write to the file.
     * @return void
     */
    public function internal_test_vpl_fopen($path, $text = 'Example text') {
        global $CFG;
        $testdir = $CFG->dataroot . '/temp/vpl_test/tmp';
        $fpath = $testdir . $path;
        if (file_exists($fpath) && is_dir($fpath)) {
            $this->expectedNotice();
        };
        $fp = vpl_fopen($fpath);
        $this->assertNotNull($fp);
        fwrite($fp, $text);
        fclose($fp);
        $this->assertEquals($text, file_get_contents($fpath));
    }

    /**
     * Tests the function vpl_fopen.
     *
     * @covers \vpl_fopen
     */
    public function test_vpl_fopen(): void {
        global $CFG;
        $testdir = $CFG->dataroot . '/temp/vpl_test/tmp';
        $text = 'Any thing is ok! 뭐든 괜찮아!';
        $this->internal_test_vpl_fopen('/a1/b1/c1');
        $this->internal_test_vpl_fopen('/aaaaaaaaaaaaaaaa.bbb');
        $this->internal_test_vpl_fopen('/aaaaaaaaaaaaaaaa.bbb', 'Other text');
        $this->internal_test_vpl_fopen('/nf.bbb', $text);
        $fpath = $testdir . '/nf.bbb';
        chmod($fpath, 0000);
        try {
            if (@file_get_contents($fpath) == $text) {
                $chmodusefull = false;
            } else {
                $chmodusefull = true;
            }
        } catch (\Throwable $e) {
            $chmodusefull = false;
        }
        chmod($fpath, 0777);
        $bads = ['/a1/..', '/a1/.', '/', '/a1', '/a1/b1'];
        foreach ($bads as $bad) {
            try {
                $throwexception = false;
                $this->internal_test_vpl_fopen($bad);
            } catch (\Throwable $e) {
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
            } catch (\Throwable $e) {
                $throwexception = true;
            }
            chmod($testdir, 0777);
            $this->assertTrue($throwexception, 'Exception expected');
            $this->assertTrue(vpl_delete_dir($testdir));
        }
    }

    /**
     * Tests the function vpl_get_array_key.
     *
     * @covers \vpl_get_array_key
     */
    public function test_vpl_get_array_key(): void {
        $array = [0 => 'nothing', 1 => 'a', 2 => 'b', 5 => 'c', 1200 => 'd', 1500 => 'f'];
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
     * Tests the function vpl_fwrite.
     *
     * @covers \vpl_fwrite
     */
    public function test_vpl_fwrite(): void {
        global $CFG;
        $text = 'Example text';
        $otext = 'Other text';
        $testdir = $CFG->dataroot . '/temp/vpl_test/tmp';
        // Dir empty.
        vpl_delete_dir($testdir);
        mkdir($testdir, 0777, true);
        $fpath = $testdir . '/a1/b1/c1';
        vpl_fwrite($fpath, $text);
        $this->assertEquals($text, file_get_contents($fpath));
        vpl_fwrite($fpath, $otext);
        $this->assertEquals($otext, file_get_contents($fpath));
        // Tests if the File System honor chmod.
        chmod($fpath, 0000);
        try {
            if (@file_get_contents($fpath) == $otext) {
                $chmodusefull = false;
            } else {
                $chmodusefull = true;
            }
        } catch (\Throwable $e) {
            $chmodusefull = false;
        }
        chmod($fpath, 0777);
        $fpath = $testdir . '/aaaaaaaaaaaaaaaa.bbb';
        vpl_fwrite($fpath, $text);
        $this->assertEquals($text, file_get_contents($fpath));
        $fpath = $testdir . '/aaaaaaaaaaaaaaaa.bbb';
        vpl_fwrite($fpath, '');
        $this->assertEquals('', file_get_contents($fpath));
        $fpath = $testdir . '/nf.bbb';
        vpl_fwrite($fpath, '');
        $this->assertEquals('', file_get_contents($fpath));
        $bads = ['/a1/..', '/a1/.', '/', '/a1', '/a1/b1'];
        foreach ($bads as $bad) {
            $fpath = $testdir . $bad;
            try {
                $throwexception = false;
                vpl_fwrite($fpath, $text);
            } catch (\Throwable $e) {
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
            } catch (\Throwable $e) {
                $throwexception = true;
            }
            chmod($testdir, 0777);
            $this->assertTrue($throwexception, 'Exception expected');
            vpl_delete_dir($testdir);
        }
    }

    /**
     * Tests the function vpl_get_set_session_var.
     *
     * @covers \vpl_get_set_session_var
     */
    public function test_vpl_get_set_session_var(): void {
        global $SESSION;
        $nosession = false;
        $nopost = false;
        if (!isset($SESSION)) {
            $nosession = true;
        } else {
            $sessionsave = $SESSION;
        }
        if (!isset($_POST)) {
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
        if ($nopost) {
            unset($_POST);
        } else {
            $_POST = $postsave;
        }
        if ($nosession) {
            unset($SESSION);
        } else {
            $SESSION = $sessionsave;
        }
    }

    /**
     * Tests the function vpl_is_image.
     *
     * @covers \vpl_is_image
     */
    public function test_vpl_is_image(): void {
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
     * Tests the function vpl_is_audio.
     *
     * @covers \vpl_is_audio
     */
    public function test_vpl_is_audio(): void {
        $this->assertTrue(vpl_is_audio('filename.wav'));
        $this->assertTrue(vpl_is_audio('filename.mp3'));
        $this->assertTrue(vpl_is_audio('filename.aac'));
        $this->assertTrue(vpl_is_audio('filename.ogg'));
        $this->assertTrue(vpl_is_audio('filename.m4a'));
        $this->assertTrue(vpl_is_audio('filename.flac'));
        $this->assertTrue(vpl_is_audio('filename.wma'));
        $this->assertTrue(vpl_is_audio('filename.aiff'));
        $this->assertTrue(vpl_is_audio('filename.Mp3'));
        $this->assertTrue(vpl_is_audio('filename.WAV'));
        $this->assertTrue(vpl_is_audio('filename.FLAC'));
        $this->assertTrue(vpl_is_audio('audio.pcm'));
        $this->assertTrue(vpl_is_audio('audio.alac'));
        $this->assertTrue(vpl_is_audio('audio.ape'));
        $this->assertTrue(vpl_is_audio('audio.wv'));
        $this->assertTrue(vpl_is_audio('audio.amr'));
        $this->assertFalse(vpl_is_audio('filename.mp4'));
        $this->assertFalse(vpl_is_audio('filename.pdf'));
        $this->assertFalse(vpl_is_audio('filename.txt'));
        $this->assertFalse(vpl_is_audio('filename.mp3.old'));
        $this->assertFalse(vpl_is_audio('a.mp3/filename.pdf'));
        $this->assertFalse(vpl_is_audio('a.wav/filename_mp3'));
        $this->assertFalse(vpl_is_audio('a.ogg/mp3'));
    }

    /**
     * Tests the function vpl_is_video.
     *
     * @covers \vpl_is_video
     */
    public function test_vpl_is_video(): void {
        $this->assertTrue(vpl_is_video('filename.mp4'));
        $this->assertTrue(vpl_is_video('filename.webm'));
        $this->assertTrue(vpl_is_video('filename.ogv'));
        $this->assertTrue(vpl_is_video('filename.avi'));
        $this->assertTrue(vpl_is_video('filename.mov'));
        $this->assertTrue(vpl_is_video('filename.wmv'));
        $this->assertTrue(vpl_is_video('filename.flv'));
        $this->assertTrue(vpl_is_video('filename.mkv'));
        $this->assertTrue(vpl_is_video('filename.m4v'));
        $this->assertTrue(vpl_is_video('filename.mpeg'));
        $this->assertTrue(vpl_is_video('filename.mpg'));
        $this->assertTrue(vpl_is_video('filename.3gp'));
        $this->assertTrue(vpl_is_video('filename.Mp4'));
        $this->assertTrue(vpl_is_video('filename.WEBM'));
        $this->assertTrue(vpl_is_video('filename.AVI'));
        $this->assertTrue(vpl_is_video('video.OGV'));
        $this->assertFalse(vpl_is_video('filename.mp3'));
        $this->assertFalse(vpl_is_video('filename.pdf'));
        $this->assertFalse(vpl_is_video('filename.txt'));
        $this->assertFalse(vpl_is_video('filename.jpg'));
        $this->assertFalse(vpl_is_video('filename.mp4.old'));
        $this->assertFalse(vpl_is_video('a.mp4/filename.pdf'));
        $this->assertFalse(vpl_is_video('a.mov/filename_mp4'));
        $this->assertFalse(vpl_is_video('a.avi/webm'));
    }

    /**
     * Tests the function vpl_is_binary.
     *
     * @covers \vpl_is_binary
     */
    public function test_vpl_is_binary(): void {
        // Image files should be binary.
        $this->assertTrue(vpl_is_binary('filename.gif'));
        $this->assertTrue(vpl_is_binary('filename.jpg'));
        $this->assertTrue(vpl_is_binary('filename.png'));
        $this->assertTrue(vpl_is_binary('filename.ico'));
        // Audio files should be binary.
        $this->assertTrue(vpl_is_binary('filename.wav'));
        $this->assertTrue(vpl_is_binary('filename.mp3'));
        $this->assertTrue(vpl_is_binary('filename.ogg'));
        $this->assertTrue(vpl_is_binary('filename.flac'));
        // Video files should be binary.
        $this->assertTrue(vpl_is_binary('filename.mp4'));
        $this->assertTrue(vpl_is_binary('filename.avi'));
        $this->assertTrue(vpl_is_binary('filename.mkv'));
        $this->assertTrue(vpl_is_binary('filename.webm'));
        // Other known binary extensions.
        $this->assertTrue(vpl_is_binary('filename.exe'));
        $this->assertTrue(vpl_is_binary('filename.zip'));
        $this->assertTrue(vpl_is_binary('filename.tar'));
        $this->assertTrue(vpl_is_binary('filename.bin'));
        $this->assertTrue(vpl_is_binary('filename.dll'));
        // Text files should not be binary (by extension).
        $this->assertFalse(vpl_is_binary('filename.txt'));
        $this->assertFalse(vpl_is_binary('filename.c'));
        $this->assertFalse(vpl_is_binary('filename.cpp'));
        $this->assertFalse(vpl_is_binary('filename.java'));
        $this->assertFalse(vpl_is_binary('filename.py'));
        $this->assertFalse(vpl_is_binary('filename.js'));
        $this->assertFalse(vpl_is_binary('filename.html'));
        $this->assertFalse(vpl_is_binary('filename.css'));
        $this->assertFalse(vpl_is_binary('filename.md'));
        $this->assertFalse(vpl_is_binary('Makefile'));
        // Case insensitivity.
        $this->assertTrue(vpl_is_binary('filename.EXE'));
        $this->assertTrue(vpl_is_binary('filename.ZIP'));
        $this->assertTrue(vpl_is_binary('filename.Mp4'));
        // Test with content - text content should not be binary.
        $textcontent = 'This is a simple text file content with normal characters.';
        $this->assertFalse(vpl_is_binary('unknown.xyz', $textcontent));
        // Test with content - binary content should be detected.
        $binarycontent = "\x00\x01\x02\x03\x04\x05\x06\x89\xAB\xCD\xEF";
        $this->assertTrue(vpl_is_binary('unknown.xyz', $binarycontent));
        // Known binary extension should override text content.
        $this->assertTrue(vpl_is_binary('filename.exe', $textcontent));
        // Mixed content with some binary bytes.
        $mixedcontent = "Text content\x00\x01\x02with binary";
        $this->assertTrue(vpl_is_binary('unknown.xyz', $mixedcontent));
    }

    /**
     * Tests the function vpl_truncate_string.
     *
     * @covers \vpl_truncate_string
     */
    public function test_vpl_truncate_string(): void {
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
     * Tests the function vpl_bash_export.
     *
     * @covers \vpl_bash_export
     */
    public function test_vpl_bash_export(): void {
        $this->assertEquals("export VPL=3\n", vpl_bash_export('VPL', 3));
        $this->assertEquals("export ALGO='text'\n", vpl_bash_export('ALGO', 'text'));
        $this->assertEquals("export ALGO='te\" \$'\\''xt'\n", vpl_bash_export('ALGO', 'te" $\'xt'));
        $this->assertEquals("export ALGO='te'\\'''\\''xt'\\'''\n", vpl_bash_export('ALGO', "te''xt'"));
        $res = vpl_bash_export('a', [ "te''xt'", 'te" $\'xt']);
        $this->assertEquals("export a=( 'te'\\'''\\''xt'\\''' 'te\" \$'\\''xt' )\n", $res);
    }

    /**
     * Tests the function vpl_is_valid_file_name.
     *
     * @covers \vpl_is_valid_file_name
     */
    public function test_vpl_is_valid_file_name(): void {
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
     * Test vpl_filter_groups_by_initials
     *
     * @covers ::vpl_filter_groups_by_initials
     */
    public function test_vpl_filter_groups_by_initials() {
        $g1 = (object)['id' => 1, 'name' => 'Alpha'];
        $g2 = (object)['id' => 2, 'name' => 'Beta'];
        $g3 = (object)['id' => 3, 'name' => 'Gamma'];
        $g4 = (object)['id' => 4, 'name' => 'Álvaro'];
        $groups = [$g1, $g2, $g3, $g4];

        // Test filter by empty initial
        $res = vpl_filter_groups_by_initials($groups, '');
        $this->assertCount(4, $res);

        // Test filter by 'A'
        $res = vpl_filter_groups_by_initials($groups, 'A');
        $this->assertCount(2, $res); // Alpha, Álvaro

        // Test filter by 'Á'
        $res = vpl_filter_groups_by_initials($groups, 'Á');
        $this->assertCount(1, $res); // Álvaro only, Alpha does not match strict accent

        // Test filter by 'B'
        $res = vpl_filter_groups_by_initials($groups, 'B');
        $this->assertCount(1, $res);
        $this->assertEquals('Beta', reset($res)->name);

        // Test filter by 'Z' (no match)
        $res = vpl_filter_groups_by_initials($groups, 'Z');
        $this->assertCount(0, $res);
    }

    /**
     * Test vpl_filter_users_by_initials
     *
     * @covers ::vpl_filter_users_by_initials
     */
    public function test_vpl_filter_users_by_initials() {
        $u1 = (object)['id' => 1, 'firstname' => 'Adam', 'lastname' => 'Smith'];
        $u2 = (object)['id' => 2, 'firstname' => 'Bob', 'lastname' => 'Jones'];
        $u3 = (object)['id' => 3, 'firstname' => 'Charlie', 'lastname' => 'Brown'];
        $u4 = (object)['id' => 4, 'firstname' => 'Alice', 'lastname' => 'Smith'];
        $u5 = (object)['id' => 5, 'firstname' => 'Óscar', 'lastname' => 'Úbbeda'];
        $users = [$u1, $u2, $u3, $u4, $u5];

        // Test filter by empty initials
        $res = vpl_filter_users_by_initials($users, '', '');
        $this->assertCount(5, $res);

        // Test filter by last name 'S'
        $res = vpl_filter_users_by_initials($users, 'S', '');
        $this->assertCount(2, $res); // Adam Smith, Alice Smith
        foreach ($res as $u) {
            $this->assertEquals('Smith', $u->lastname);
        }

        // Test filter by first name 'A'
        $res = vpl_filter_users_by_initials($users, '', 'A');
        $this->assertCount(2, $res); // Adam Smith, Alice Smith

        // Test filter by both 'S' and 'A'
        $res = vpl_filter_users_by_initials($users, 'S', 'A');
        $this->assertCount(2, $res); // Adam Smith, Alice Smith

        // Test filter by 'J' lastname
        $res = vpl_filter_users_by_initials($users, 'J', '');
        $this->assertCount(1, $res);
        $this->assertEquals('Jones', reset($res)->lastname);

        // Test filter by 'B' firstname
        $res = vpl_filter_users_by_initials($users, '', 'B');
        $this->assertCount(1, $res);
        $this->assertEquals('Bob', reset($res)->firstname);

        // Test accented matching
        // 'O' matches 'Óscar'
        $res = vpl_filter_users_by_initials($users, '', 'O');
        $this->assertCount(1, $res);
        $this->assertEquals('Óscar', reset($res)->firstname);

        // 'Ó' matches 'Óscar'
        $res = vpl_filter_users_by_initials($users, '', 'Ó');
        $this->assertCount(1, $res);
        $this->assertEquals('Óscar', reset($res)->firstname);

        // 'U' matches 'Úbbeda'
        $res = vpl_filter_users_by_initials($users, 'U', '');
        $this->assertCount(1, $res);
        $this->assertEquals('Úbbeda', reset($res)->lastname);

        // 'Ú' matches 'Úbbeda'
        $res = vpl_filter_users_by_initials($users, 'Ú', '');
        $this->assertCount(1, $res);
        $this->assertEquals('Úbbeda', reset($res)->lastname);
        
        // Test no match
        $res = vpl_filter_users_by_initials($users, 'Z', 'Z');
        $this->assertCount(0, $res);
    }
}
