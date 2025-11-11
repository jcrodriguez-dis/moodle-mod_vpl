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
 * Unit tests for class file_group mod/vpl/filegroup.class.php
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
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');

/**
 * Unit tests for \mod\util\file_group class.
 *
 * @group mod_vpl
 * @covers \mod_vpl\util\file_group
 */
final class file_group_test extends \advanced_testcase {
    /**
     * Base directory for file group process tests.
     * @var string
     */
    protected $basedir = null;

    /**
     * Fixture for file group with no files.
     * @var \mod_vpl\util\file_group
     */
    protected $gpempty = null;

    /**
     * Fixture for file group with no files.
     * @var \mod_vpl\util\file_group
     */
    protected $gponefile = null;

    /**
     * Fixture for with more than one file.
     * @var \mod_vpl\util\file_group
     */
    protected $gpfiles = null;

    /**
     * Fixture for file group with directories.
     * @var \mod_vpl\util\file_group
     */
    protected $gpdirectory = null;

    /**
     * Fixture for one file contents.
     * @var array
     */
    protected $gponefilecontents = null;

    /**
     * Fixture more than one file contents.
     * @var array
     */
    protected $gpfilescontents = null;

    /**
     * Fixture for directory contents.
     * @var array
     */
    protected $gpdirectorycontents = null;

    /**
     * Method to create the fixture
     */
    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        // Create the base directory for file group process tests.
        $this->basedir = $CFG->dataroot . '/vpl_data/gpt/';

        $this->gpempty = new \mod_vpl\util\file_group($this->basedir . 'empty', 0, 0);
        $this->gponefile = new \mod_vpl\util\file_group($this->basedir . 'onefile', 1, 1);
        $this->gpfiles = new \mod_vpl\util\file_group($this->basedir . 'files');
        $this->gpdirectory = new \mod_vpl\util\file_group($this->basedir . 'directory', 100, 4);

        $this->gponefilecontents = ['one file.txt' => "One file contents"];
        $this->gponefile->addallfiles($this->gponefilecontents);

        $this->gpfilescontents = [
                'first file.txt' => "First file contents",
                'Second file.txt' => "Second file contents",
                'Third file.txt' => "Third  file contents",
        ];
        $this->gpfiles->addallfiles($this->gpfilescontents);

        $this->gpdirectorycontents = [
                'a sub dir/first file.txt' => "First file contents",
                'a sub dir/Second file.txt' => "Second file contents",
                'b/c/d/Third file.txt' => "Third  file contents",
                'b/c/d/Fourth file.txt' => "Fourth  file contents",
                'Other file.txt' => "Other  file contents",
                'b/Other file.txt' => "Other  file contents",
                'b/c/Other file.txt' => "Other  file contents",
        ];
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
     * Method to test \mod\util\file_group::addallfiles with other dir
     */
    public function test_addallfiles(): void {
        $otherempty = new \mod_vpl\util\file_group($this->basedir . 'emptyother', 0, 0);
        $otherfiles = new \mod_vpl\util\file_group($this->basedir . 'filesother');
        $otherempty->addallfiles([], $this->basedir . 'empty');
        $this->assertEquals([], $otherempty->getallfiles());
        $otherempty->addallfiles([], $this->basedir . 'files');
        $this->assertEquals([], $otherempty->getallfiles());
        $files = [
            'first file.txt' => "First file contents",
            'Second file2.txt' => "Second file contents",
            'Third file.txt' => "",
            'Last file.txt' => "Algo",
        ];
        $otherfiles->addallfiles($this->gpfilescontents, $this->basedir . 'files');
        $this->assertEquals($this->gpfilescontents, $otherfiles->getallfiles());
    }

    /**
     * Method to test \mod\util\file_group::get_maxnumfiles
     */
    public function test_get_maxnumfiles(): void {
        $this->assertEquals(0, $this->gpempty->get_maxnumfiles());
        $this->assertEquals(1, $this->gponefile->get_maxnumfiles());
        $this->assertEquals(10000, $this->gpfiles->get_maxnumfiles());
        $this->assertEquals(100, $this->gpdirectory->get_maxnumfiles());
    }

    /**
     * Method to test \mod\util\file_group::get_numstaticfiles
     */
    public function test_get_numstaticfiles(): void {
        $this->assertEquals(0, $this->gpempty->get_numstaticfiles());
        $this->assertEquals(1, $this->gponefile->get_numstaticfiles());
        $this->assertEquals(0, $this->gpfiles->get_numstaticfiles());
        $this->assertEquals(4, $this->gpdirectory->get_numstaticfiles());
    }

    /**
     * Method to test \mod\util\file_group::read_list
     */
    public function test_read_list(): void {
        $filelist = [];
        $this->assertEquals($filelist, \mod_vpl\util\file_group::read_list($this->gpempty->getfilelistname()));
        $filelist = ['one file.txt'];
        $this->assertEquals($filelist, \mod_vpl\util\file_group::read_list($this->gponefile->getfilelistname()));
        $filelist = ['first file.txt', 'Second file.txt', 'Third file.txt'];
        $this->assertEquals($filelist, \mod_vpl\util\file_group::read_list($this->gpfiles->getfilelistname()));
        $filelist = ['a sub dir/first file.txt', 'a sub dir/Second file.txt',
                'b/c/d/Third file.txt', 'b/c/d/Fourth file.txt',
                'Other file.txt', 'b/Other file.txt', 'b/c/Other file.txt', ];
        $this->assertEquals($filelist, \mod_vpl\util\file_group::read_list($this->gpdirectory->getfilelistname()));
    }

    /**
     * Method to test \mod\util\file_group::write_list
     */
    public function test_write_list(): void {
        $filelist = ['algo.txt'];
        \mod_vpl\util\file_group::write_list($this->gpempty->getfilelistname(), $filelist);
        $this->assertEquals($filelist, \mod_vpl\util\file_group::read_list($this->gpempty->getfilelistname()));
        $filelist = [];
        \mod_vpl\util\file_group::write_list($this->gponefile->getfilelistname(), $filelist);
        $this->assertEquals($filelist, \mod_vpl\util\file_group::read_list($this->gponefile->getfilelistname()));
        $filelist = ['first file.txt', 'Second file.txt', 'Third file.txt', 'first file1.txt',
                          'Second file1.txt', 'Third file1.txt', ];
        \mod_vpl\util\file_group::write_list($this->gpfiles->getfilelistname(), $filelist);
        $this->assertEquals($filelist, \mod_vpl\util\file_group::read_list($this->gpfiles->getfilelistname()));
        $filelist = ['a sub dir/first file.txt', 'a sub dir/Second file.txt',
                'b/c/d/Third file.txt', 'b/c/d/Fourth file.txt',
                'Other file.txt', 'b/Other file.txt', 'b/c/Other file.txt', ];
        \mod_vpl\util\file_group::write_list($this->gpdirectory->getfilelistname(), $filelist);
        $this->assertEquals($filelist, \mod_vpl\util\file_group::read_list($this->gpdirectory->getfilelistname()));
        $other = [
                'aaa/bb/ccc/first file.txt',
                'aaa/bb/Second file.txt',
                'aaaThird file.txt',
        ];
        \mod_vpl\util\file_group::write_list($this->gpdirectory->getfilelistname(), $other);
        $this->assertEquals($other, \mod_vpl\util\file_group::read_list($this->gpdirectory->getfilelistname()));
    }

    /**
     * Method to test \mod\util\file_group::encodefilename
     */
    public function test_encodefilename(): void {
        $this->assertEquals('a.b.c', \mod_vpl\util\file_group::encodefilename('a.b.c'));
        $this->assertEquals('a=b=c.d', \mod_vpl\util\file_group::encodefilename('a/b/c.d'));
    }

    /**
     * Helper method to test \mod\util\file_group::addfile
     * This method is used to test if the file group can add files correctly.
     * It checks if the file group can add a file with data and if it returns the expected result.
     *
     * @param \mod_vpl\util\file_group $fg The file group process instance to test.
     * @param string $fn The filename to add.
     * @param string|null $data The data to add to the file, or null for an empty file.
     * @param bool $added Expected result of the add operation.
     */
    private function internal_test_one_addfile($fg, $fn, $data, $added) {
        $res = $fg->addfile($fn, $data);
        $this->assertEquals($added, $res);
        if ($added) {
            if ($data !== null) {
                $this->assertEquals($data, $fg->getfiledata($fn));
            } else {
                $this->assertFalse(file_exists($fn));
                $this->assertEquals('', $fg->getfiledata($fn));
            }
        }
    }

    /**
     * Method to test \mod\util\file_group::addfile.
     */
    public function test_addfile(): void {
        $this->internal_test_one_addfile($this->gpempty, 'a', '', false);
        $this->internal_test_one_addfile($this->gponefile, 'one file.txt', 'algo distinto', true);
        $this->internal_test_one_addfile($this->gponefile, 'otrofile.txt', 'algo distinto', false);
        $this->gponefile->deleteallfiles();
        $this->assertEquals([], $this->gponefile->getfilelist());
        $this->internal_test_one_addfile($this->gponefile, 'otrofile.txt', 'algo distinto', true);

        $this->internal_test_one_addfile($this->gpfiles, 'otrofile.txt', 'algo distinto', true);
        $this->internal_test_one_addfile($this->gpfiles, 'otrofile.txt', 'algo  lkjfsads lkf distinto', true);
        $this->internal_test_one_addfile($this->gpfiles, 'otrofile 1.txt', 'algo distinto', true);
        $this->internal_test_one_addfile($this->gpfiles, 'Second file.txt', 'algo  lkf distinto', true);
        $this->internal_test_one_addfile($this->gpfiles, 'Second file.txt', 'algo  distinto', true);
        $this->internal_test_one_addfile($this->gpfiles, 'Second file.txt', null, true);
        $this->gpfiles->deleteallfiles();
        $this->assertEquals([], $this->gpfiles->getfilelist());
        $this->internal_test_one_addfile($this->gpfiles, 'otrofile.txt', 'algo distinto', true);
        $this->internal_test_one_addfile($this->gpfiles, 'Second file.txt', 'algo  lkf distinto', true);
    }

    /**
     * Method to test \mod\util\file_group::getallfiles.
     */
    public function test_getallfiles(): void {
        $this->assertEquals([], $this->gpempty->getallfiles());
        $this->assertEquals($this->gponefilecontents, $this->gponefile->getallfiles());
        $this->assertEquals($this->gpfilescontents, $this->gpfiles->getallfiles());
        $this->assertEquals($this->gpdirectorycontents, $this->gpdirectory->getallfiles());
    }

    /**
     * Method to test \mod\util\file_group::deleteallfiles.
     */
    public function test_deleteallfiles(): void {
        $this->gpempty->deleteallfiles();
        $this->assertEquals([], $this->gpempty->getfilelist());
        $this->gponefile->deleteallfiles();
        $this->assertEquals([], $this->gponefile->getfilelist());
        $this->gpfiles->deleteallfiles();
        $this->assertEquals([], $this->gpfiles->getfilelist());
        $this->gpdirectory->deleteallfiles();
        $this->assertEquals([], $this->gpdirectory->getfilelist());
    }

    /**
     * Method to test \mod\util\file_group::getfilelist
     */
    public function test_getfilelist(): void {
        $filelist = [];
        $this->assertEquals($filelist, $this->gpempty->getfilelist());
        $filelist = ['one file.txt'];
        $this->assertEquals($filelist, $this->gponefile->getfilelist());
        $filelist = ['first file.txt', 'Second file.txt', 'Third file.txt'];
        $this->assertEquals($filelist, $this->gpfiles->getfilelist());
        $filelist = ['a sub dir/first file.txt', 'a sub dir/Second file.txt',
                'b/c/d/Third file.txt', 'b/c/d/Fourth file.txt',
                'Other file.txt', 'b/Other file.txt', 'b/c/Other file.txt', ];
        $this->assertEquals($filelist, $this->gpdirectory->getfilelist());
    }

    /**
     * Method to test \mod\util\file_group::getfilecomment
     */
    public function test_getfilecomment(): void {
        $expected = get_string('file') . ' 4';
        $this->assertEquals($expected, $this->gpempty->getfilecomment(3));
    }

    /**
     * Helper method to test \mod\util\file_group::getfiledata
     * This method is used to test if the file group can retrieve file data correctly.
     * It checks if the file group can get file data by index and by filename.
     *
     * @param \mod_vpl\util\file_group $fg The file group process instance to test.
     * @param array $fgdata The expected file data indexed by filename.
     */
    private function internal_test_one_getfiledata($fg, $fgdata): void {
        $i = 0;
        foreach ($fgdata as $fn => $fd) {
            $this->assertEquals($fd, $fg->getfiledata($i));
            $this->assertEquals($fd, $fg->getfiledata($fn));
            $i++;
        }
    }

    /**
     * Method to test \mod\util\file_group::getfiledata
     */
    public function test_getfiledata(): void {
        $this->internal_test_one_getfiledata($this->gponefile, $this->gponefilecontents);
        $this->internal_test_one_getfiledata($this->gpfiles, $this->gpfilescontents);
        $this->internal_test_one_getfiledata($this->gpdirectory, $this->gpdirectorycontents);
    }

    /**
     * Helper method to test \mod\util\file_group::is_populated
     * This method is used to test if the file group is populated with files.
     * It checks if the file group has files and if they are added correctly.
     *
     * @param \mod_vpl\util\file_group $fg The file group process instance to test.
     */
    private function internal_test_one_is_populated($fg): void {
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
     * Method to test \mod\util\file_group::is_populated
     */
    public function test_is_populated(): void {
        $this->internal_test_one_is_populated($this->gpempty);
        $this->internal_test_one_is_populated($this->gponefile);
        $this->internal_test_one_is_populated($this->gpfiles);
        $this->internal_test_one_is_populated($this->gpdirectory);
    }

    /**
     * Method to test \mod\util\file_group::getversion
     */
    public function test_getversion(): void {
        $this->assertTrue($this->gpempty->getversion() === 0);
        $this->assertTrue($this->gponefile->getversion() > 0);
        $this->assertTrue($this->gpfiles->getversion() > 0);
        $this->assertTrue($this->gpdirectory->getversion() > 0);
        vpl_delete_dir($this->basedir . 'onefile', true);
        $this->assertTrue($this->gponefile->getversion() === 0);
    }

    /**
     * Test one \mod\util\file_group::generate_zip_file
     * @param \mod\util\file_group $fgp
     * @param array $expectedfiles
     */
    protected function internal_test_generate_zip_file(\mod_vpl\util\file_group $fgp, array $expectedfiles) {
        $zipfilename = $fgp->generate_zip_file();
        $this->assertTrue($zipfilename !== false);
        $this->assertFileExists($zipfilename);
        $zip = new \ZipArchive();
        $result = $zip->open($zipfilename);
        $this->assertTrue($result, "Error code: $result  status: {$zip->getStatusString()}");
        $zipfiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $zipfiles[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
        }
        $this->assertTrue($zip->close());
        $this->assertTrue(unlink($zipfilename));
        $this->assertEquals($expectedfiles, $zipfiles);
    }
    /**
     * Method to test \mod\util\file_group::generate_zip_file
     */
    public function test_generate_zip_file(): void {
        $this->internal_test_generate_zip_file($this->gpempty, []);
        $this->internal_test_generate_zip_file($this->gponefile, $this->gponefilecontents);
        $this->internal_test_generate_zip_file($this->gpfiles, $this->gpfilescontents);
        $this->internal_test_generate_zip_file($this->gpdirectory, $this->gpdirectorycontents);
    }
}
