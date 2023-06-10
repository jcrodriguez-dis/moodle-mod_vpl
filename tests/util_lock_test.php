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
 * Unit tests for class \mod_vpl\util\lock
 *
 * @package mod_vpl
 * @copyright Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../locallib.php');

/**
 * Unit tests for \mod_vpl\util\lock class.
 * @group mod_vpl
 * @covers \mod_vpl\util\lock
 */
class util_lock_test extends \advanced_testcase {

    private $basedir;

    /**
     * Setup function creates test directory
     */
    protected function setUp(): void {
        global $CFG;
        $this->basedir = $CFG->dataroot . '/vpl_data/locktest';
        $this->assertTrue(mkdir($this->basedir, 0777, true));
    }

    /**
     * Teardown function removes test directory
     */
    protected function tearDown(): void {
        $this->assertTrue(vpl_delete_dir($this->basedir));
        parent::tearDown();
    }
    /**
     * Method to test creating locks
     */
    public function test_creating_lock() {
        $dir1 = $this->basedir . '/a/b';
        $lock1 = new \mod_vpl\util\lock($dir1);
        $this->assertTrue(file_exists($dir1 . \mod_vpl\util\lock::filename()));
        $dir2 = $this->basedir . '/a/c/d';
        $lock2 = new \mod_vpl\util\lock($dir2);
        $this->assertTrue(file_exists($dir2 . \mod_vpl\util\lock::filename()));

        $lock1->__destruct();
        $this->assertFalse(file_exists($dir1 . \mod_vpl\util\lock::filename()));
        $lock2->__destruct();
        $this->assertFalse(file_exists($dir2 . \mod_vpl\util\lock::filename()));
    }

    /**
     * Method to test overwriting locks
     */
    public function test_overwriting_lock() {
        $dir1 = $this->basedir . '/a/b';
        $lock1 = new \mod_vpl\util\lock($dir1);
        $this->assertTrue(file_exists($dir1 . \mod_vpl\util\lock::filename()));
        $now = time();
        $lock2 = new \mod_vpl\util\lock($dir1, 0);
        $this->assertTrue($now + 3 > time());
        $this->assertTrue(file_exists($dir1 . \mod_vpl\util\lock::filename()));
        $lock2->__destruct();
        $this->assertFalse(file_exists($dir1 . \mod_vpl\util\lock::filename()));
        $lock1->__destruct();
    }
}
