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
 * Unit tests for class file_group_process mod/vpl/filegroup.class.php
 *
 * @package mod_vpl
 * @copyright Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/mod/vpl/filegroup.class.php');

/**
 * Unit tests for file_group_process class.
 * @group mod_vpl
 * @covers \mod_vpl\file_group_process
 */
class filegroup_test extends \advanced_testcase {
    protected $basedir = null;
    protected $gpempty = null;
    protected $gponefile = null;
    protected $gpfiles = null;
    protected $gpdirectory = null;
    protected $gponefilecontents = null;
    protected $gpfilescontents = null;
    protected $gpdirectorycontents = null;
    /**
     * Method to create the fixture
     */
    protected function setUp(): void {
        global $CFG;
        $this->basedir = $CFG->dataroot . '/vpl_data/gpt/';

        $this->gpempty = new \file_group_process($this->basedir . 'empty', 0, 0);
        $this->gponefile = new \file_group_process($this->basedir . 'onefile', 1, 1);
        $this->gpfiles = new \file_group_process($this->basedir . 'files');
        $this->gpdirectory = new \file_group_process($this->basedir . 'directory', 100, 4);

        $this->gponefilecontents = array('one file.txt' => "One file contents");
        $this->gponefile->addallfiles($this->gponefilecontents);

        $this->gpfilescontents = array(
                'first file.txt' => "First file contents",
                'Second file.txt' => "Second file contents",
                'Third file.txt' => "Third  file contents"
        );
        $this->gpfiles->addallfiles($this->gpfilescontents);

        $this->gpdirectorycontents = array(
                'a sub dir/first file.txt' => "First file contents",
                'a sub dir/Second file.txt' => "Second file contents",
                'b/c/d/Third file.txt' => "Third  file contents",
                'b/c/d/Fourth file.txt' => "Fourth  file contents",
                'Other file.txt' => "Other  file contents",
                'b/Other file.txt' => "Other  file contents",
                'b/c/Other file.txt' => "Other  file contents"
        );
        $this->gpdirectory->addallfiles($this->gpdirectorycontents);
    }

    /**
     * Method to delete the fixture
     */
    protected function tearDown(): void {
        vpl_delete_dir($this->basedir);
        parent::tearDown();
    }

    /**
     * Method to test file_group_process::addallfiles with other dir
     */
    public function test_addallfiles() {
        $otherempty = new \file_group_process($this->basedir . 'emptyother', 0, 0);
        $otherfiles = new \file_group_process($this->basedir . 'filesother');
        $otherempty->addallfiles(array(), $this->basedir . 'empty');
        $this->assertEquals(array(), $otherempty->getallfiles());
        $otherempty->addallfiles(array(), $this->basedir . 'files');
        $this->assertEquals(array(), $otherempty->getallfiles());
        $files = array(
            'first file.txt' => "First file contents",
            'Second file2.txt' => "Second file contents",
            'Third file.txt' => "",
            'Last file.txt' => "Algo",
        );
        $otherfiles->addallfiles($this->gpfilescontents, $this->basedir . 'files');
        $this->assertEquals($this->gpfilescontents, $otherfiles->getallfiles());
    }

    /**
     * Method to test file_group_process::get_maxnumfiles
     */
    public function test_get_maxnumfiles() {
        $this->assertEquals(0, $this->gpempty->get_maxnumfiles());
        $this->assertEquals(1, $this->gponefile->get_maxnumfiles());
        $this->assertEquals(10000, $this->gpfiles->get_maxnumfiles());
        $this->assertEquals(100, $this->gpdirectory->get_maxnumfiles());
    }

    /**
     * Method to test file_group_process::get_numstaticfiles
     */
    public function test_get_numstaticfiles() {
        $this->assertEquals(0, $this->gpempty->get_numstaticfiles());
        $this->assertEquals(1, $this->gponefile->get_numstaticfiles());
        $this->assertEquals(0, $this->gpfiles->get_numstaticfiles());
        $this->assertEquals(4, $this->gpdirectory->get_numstaticfiles());
    }

    /**
     * Method to test file_group_process::read_list
     */
    public function test_read_list() {
        $filelist = array();
        $this->assertEquals($filelist, \file_group_process::read_list($this->gpempty->getfilelistname()));
        $filelist = array('one file.txt');
        $this->assertEquals($filelist, \file_group_process::read_list($this->gponefile->getfilelistname()));
        $filelist = array('first file.txt', 'Second file.txt', 'Third file.txt');
        $this->assertEquals($filelist, \file_group_process::read_list($this->gpfiles->getfilelistname()));
        $filelist = array('a sub dir/first file.txt', 'a sub dir/Second file.txt',
                'b/c/d/Third file.txt', 'b/c/d/Fourth file.txt',
                'Other file.txt', 'b/Other file.txt', 'b/c/Other file.txt');
        $this->assertEquals($filelist, \file_group_process::read_list($this->gpdirectory->getfilelistname()));
    }

    /**
     * Method to test file_group_process::write_list
     */
    public function test_write_list() {
        $filelist = array('algo.txt');
        \file_group_process::write_list($this->gpempty->getfilelistname(), $filelist);
        $this->assertEquals($filelist, \file_group_process::read_list($this->gpempty->getfilelistname()));
        $filelist = array();
        \file_group_process::write_list($this->gponefile->getfilelistname(), $filelist);
        $this->assertEquals($filelist, \file_group_process::read_list($this->gponefile->getfilelistname()));
        $filelist = array('first file.txt', 'Second file.txt', 'Third file.txt', 'first file1.txt',
                          'Second file1.txt', 'Third file1.txt');
        \file_group_process::write_list($this->gpfiles->getfilelistname(), $filelist);
        $this->assertEquals($filelist, \file_group_process::read_list($this->gpfiles->getfilelistname()));
        $filelist = array('a sub dir/first file.txt', 'a sub dir/Second file.txt',
                'b/c/d/Third file.txt', 'b/c/d/Fourth file.txt',
                'Other file.txt', 'b/Other file.txt', 'b/c/Other file.txt');
        \file_group_process::write_list($this->gpdirectory->getfilelistname(), $filelist);
        $this->assertEquals($filelist, \file_group_process::read_list($this->gpdirectory->getfilelistname()));
        $other = array(
                'aaa/bb/ccc/first file.txt',
                'aaa/bb/Second file.txt',
                'aaaThird file.txt'
        );
        \file_group_process::write_list($this->gpdirectory->getfilelistname(), $other);
        $this->assertEquals($other, \file_group_process::read_list($this->gpdirectory->getfilelistname()));
    }

    /**
     * Method to test file_group_process::encodefilename
     */
    public function test_encodefilename() {
        $this->assertEquals('a.b.c', \file_group_process::encodefilename('a.b.c'));
        $this->assertEquals('a=b=c.d', \file_group_process::encodefilename('a/b/c.d'));
    }

    private function internal_test_one_addfile($fg, $fn, $data, $added) {
        $res = $fg->addfile($fn, $data);
        $this->assertEquals($added, $res);
        if ( $added ) {
            if ($data !== null) {
                $this->assertEquals($data, $fg->getfiledata($fn));
            } else {
                $this->assertFalse(file_exists($fn));
                $this->assertEquals('', $fg->getfiledata($fn));
            }
        }
    }

    /**
     * Method to test file_group_process::addfile.
     */
    public function test_addfile() {
        $this->internal_test_one_addfile($this->gpempty, 'a', '', false);
        $this->internal_test_one_addfile($this->gponefile, 'one file.txt', 'algo distinto', true);
        $this->internal_test_one_addfile($this->gponefile, 'otrofile.txt', 'algo distinto', false);
        $this->gponefile->deleteallfiles();
        $this->assertEquals(array(), $this->gponefile->getfilelist());
        $this->internal_test_one_addfile($this->gponefile, 'otrofile.txt', 'algo distinto', true);

        $this->internal_test_one_addfile($this->gpfiles, 'otrofile.txt', 'algo distinto', true);
        $this->internal_test_one_addfile($this->gpfiles, 'otrofile.txt', 'algo  lkjfsads lkf distinto', true);
        $this->internal_test_one_addfile($this->gpfiles, 'otrofile 1.txt', 'algo distinto', true);
        $this->internal_test_one_addfile($this->gpfiles, 'Second file.txt', 'algo  lkf distinto', true);
        $this->internal_test_one_addfile($this->gpfiles, 'Second file.txt', 'algo  distinto', true);
        $this->internal_test_one_addfile($this->gpfiles, 'Second file.txt', null, true);
        $this->gpfiles->deleteallfiles();
        $this->assertEquals(array(), $this->gpfiles->getfilelist());
        $this->internal_test_one_addfile($this->gpfiles, 'otrofile.txt', 'algo distinto', true);
        $this->internal_test_one_addfile($this->gpfiles, 'Second file.txt', 'algo  lkf distinto', true);
    }

    /**
     * Method to test file_group_process::getallfiles.
     */
    public function test_getallfiles() {
        $this->assertEquals(array(), $this->gpempty->getallfiles());
        $this->assertEquals($this->gponefilecontents, $this->gponefile->getallfiles());
        $this->assertEquals($this->gpfilescontents, $this->gpfiles->getallfiles());
        $this->assertEquals($this->gpdirectorycontents, $this->gpdirectory->getallfiles());
    }

    /**
     * Method to test file_group_process::deleteallfiles.
     */
    public function test_deleteallfiles() {
        $this->gpempty->deleteallfiles();
        $this->assertEquals(array(), $this->gpempty->getfilelist());
        $this->gponefile->deleteallfiles();
        $this->assertEquals(array(), $this->gponefile->getfilelist());
        $this->gpfiles->deleteallfiles();
        $this->assertEquals(array(), $this->gpfiles->getfilelist());
        $this->gpdirectory->deleteallfiles();
        $this->assertEquals(array(), $this->gpdirectory->getfilelist());
    }

    /**
     * Method to test file_group_process::getfilelist
     */
    public function test_getfilelist() {
        $filelist = array();
        $this->assertEquals($filelist, $this->gpempty->getfilelist());
        $filelist = array('one file.txt');
        $this->assertEquals($filelist, $this->gponefile->getfilelist());
        $filelist = array('first file.txt', 'Second file.txt', 'Third file.txt');
        $this->assertEquals($filelist, $this->gpfiles->getfilelist());
        $filelist = array('a sub dir/first file.txt', 'a sub dir/Second file.txt',
                'b/c/d/Third file.txt', 'b/c/d/Fourth file.txt',
                'Other file.txt', 'b/Other file.txt', 'b/c/Other file.txt');
        $this->assertEquals($filelist, $this->gpdirectory->getfilelist());
    }

    /**
     * Method to test file_group_process::getfilecomment
     */
    public function test_getfilecomment() {
        $expected = get_string('file') . ' 4';
        $this->assertEquals($expected, $this->gpempty->getfilecomment(3));
    }

    private function internal_test_one_getfiledata($fg, $fgdata) {
        $i = 0;
        foreach ($fgdata as $fn => $fd) {
            $this->assertEquals($fd, $fg->getfiledata($i));
            $this->assertEquals($fd, $fg->getfiledata($fn));
            $i ++;
        }
    }

    /**
     * Method to test file_group_process::getfiledata
     */
    public function test_getfiledata() {
        $this->internal_test_one_getfiledata($this->gponefile, $this->gponefilecontents);
        $this->internal_test_one_getfiledata($this->gpfiles, $this->gpfilescontents);
        $this->internal_test_one_getfiledata($this->gpdirectory, $this->gpdirectorycontents);
    }

    private function internal_test_one_is_populated($fg) {
        $fnl = $fg->getfilelist();
        foreach ($fnl as $fn) {
            $this->assertTrue($fg->is_populated());
            $this->assertTrue($fg->addfile($fn, ''));
        }
        $this->assertFalse($fg->is_populated());
        foreach ($fnl as $fn) {
            $fg->addfile($fn, 'algo');
            $this->assertTrue($fg->is_populated());
        }
    }

    /**
     * Method to test file_group_process::is_populated
     */
    public function test_is_populated() {
        $this->internal_test_one_is_populated($this->gpempty);
        $this->internal_test_one_is_populated($this->gponefile);
        $this->internal_test_one_is_populated($this->gpfiles);
        $this->internal_test_one_is_populated($this->gpdirectory);
    }

    /**
     * Method to test file_group_process::getversion
     */
    public function test_getversion() {
        $this->assertTrue($this->gpempty->getversion() === 0);
        $this->assertTrue($this->gponefile->getversion() > 0);
        $this->assertTrue($this->gpfiles->getversion() > 0);
        $this->assertTrue($this->gpdirectory->getversion() > 0);
        vpl_delete_dir($this->basedir . 'onefile', true);
        $this->assertTrue($this->gponefile->getversion() === 0);
    }

    /**
     * Test one file_group_process::generate_zip_file
     * @param file_group_process $fgp
     * @param array $expectedfiles
     */
    protected function internal_test_generate_zip_file(\file_group_process $fgp, array $expectedfiles) {
        $zipfilename = $fgp->generate_zip_file();
        $this->assertTrue($zipfilename !== false);
        $this->assertFileExists($zipfilename);
        $zip = new \ZipArchive();
        $result = $zip->open( $zipfilename );
        $this->assertTrue($result, "Error code: $result  status: {$zip->getStatusString()}");
        $zipfiles = array();
        for ($i = 0; $i < $zip->numFiles; $i ++) {
            $zipfiles[$zip->getNameIndex( $i )] = $zip->getFromIndex( $i );
        }
        $this->assertTrue($zip->close());
        $this->assertTrue(unlink($zipfilename));
        $this->assertEquals($expectedfiles, $zipfiles);
    }
    /**
     * Method to test file_group_process::generate_zip_file
     */
    public function test_generate_zip_file() {
        $this->internal_test_generate_zip_file($this->gpempty, []);
        $this->internal_test_generate_zip_file($this->gponefile, $this->gponefilecontents);
        $this->internal_test_generate_zip_file($this->gpfiles, $this->gpfilescontents);
        $this->internal_test_generate_zip_file($this->gpdirectory, $this->gpdirectorycontents);
    }
}
