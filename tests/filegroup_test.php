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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/mod/vpl/filegroup.class.php');

/**
 * class mod_filegroup_class_testcase
 *
 * Tests mod/vpl/filegroup.class.php functions.
 */
class mod_vpl_file_group_process_class_testcase extends mod_vpl_base_testcase {

    /**
     * Method to remove fixture, it is not needed
     */
    protected function setUp() {
        if ( ! method_exists ( $this , 'assertDirectoryNotExists' )) {
            $this->assertDirectoryNotExists = function($directory, $message = '') {
                $this->assertFalse(file_exists($directory) && is_dir($directory),  $message);
            };
        }
        if ( ! method_exists ( $this , 'assertDirectoryExists' )) {
            $this->assertDirectoryExists = function($directory, $message = '') {
                $this->assertTrue(file_exists($directory) && is_dir($directory),  $message);
            };
        }
        if ( ! method_exists ( $this , 'assertDirectoryIsWritable' )) {
            $this->assertDirectoryIsWritable = function($directory, $message = '') {
                $this->assertTrue(is_writable($directory) && is_dir($directory),  $message);
            };
        }
    }

    /**
     * Method to test file_group_process::write_list
     */
    public function test_write_list() {
    }
}
