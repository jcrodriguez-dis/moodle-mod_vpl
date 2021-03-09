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
 * Unit tests for class mod_vpl mod/vpl/vpl.class.php
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
require_once($CFG->dirroot . '/mod/vpl/vpl_submission_CE.class.php');

/**
 * Unit tests for mod_vpl class.
 * @group mod_vpl
 */
class mod_vpl_testcase extends mod_vpl_base_testcase {

    /**
     * Method to create test fixture
     */
    protected function setUp(): void {
        parent::setUp();
        $this->setupinstances();
    }

    /**
     * Method to test mod_vpl:delete_all
     */
    public function test_delete_all() {
        global $CFG, $DB;
        // Get vpls information.
        $submissions = array();
        $othervpls = array();
        foreach ($this->vpls as $vpl) {
            $vplid = $vpl->get_instance()->id;
            $submissions[$vplid] = $vpl->all_last_user_submission();
            $othervpls[$vplid] = $vpl;
        }
        foreach ($this->vpls as $vpl) {
            $vpl->delete_all();
            // Test full delete.
            $instance = $vpl->get_instance();
            $directory = $CFG->dataroot . '/vpl_data/' . $instance->id;
            $res = $DB->get_record(VPL, array('id' => $instance->id));
            $this->assertFalse( $res, $instance->name);
            $tables = [
                VPL_SUBMISSIONS,
                VPL_VARIATIONS,
                VPL_ASSIGNED_VARIATIONS,
                VPL_RUNNING_PROCESSES
            ];
            $parms = array('vpl' => $instance->id);
            foreach ($tables as $table) {
                $res = $DB->get_records($table, $parms);
                $this->assertCount( 0, $res, $instance->name);
            }
            $sparms = array ('modulename' => VPL, 'instance' => $instance->id );
            $event = $DB->get_record('event', $sparms );
            $this->assertFalse($event, $instance->name);
            $this->assertFalse(file_exists($directory) && is_dir($directory), $instance->name);
            // Test rest of the instances not affected.
            unset($othervpls[$instance->id]);
            foreach ($othervpls as $other) {
                $instance = $other->get_instance();
                $directory = $CFG->dataroot . '/vpl_data/' . $instance->id;
                $res = $DB->get_record(VPL, array('id' => $instance->id));
                $this->assertNotEmpty( $res, $instance->name);
                $subsexpected = $submissions[$instance->id];
                $subsresult = $other->all_last_user_submission();
                $this->assertEquals( $subsexpected, $subsresult, $instance->name);
                if (count($subsexpected) > 0) {
                    $this->assertTrue(file_exists($directory) && is_dir($directory), $instance->name);
                    foreach ($subsexpected as $sub) {
                        $userid = $sub->userid;
                        $subid = $sub->id;
                        $userdir = $directory . "/usersdata/$userid/$subid/submittedfiles";
                        $this->assertTrue(file_exists($userdir) && is_dir($userdir), $instance->name);
                    }
                }
            }
        }
    }

    /**
     * Internal method to test mod_vpl::get_students returns
     */
    public function internal_test_get_students($users, $students) {
        $studentsid = array();
        foreach ($students as $student) {
            $studentsid[$student->id] = $student;
        }
        $this->assertEquals(count($students), count($users));
        foreach ($users as $student) {
            $this->assertTrue(isset($studentsid[$student->id]));
            unset($studentsid[$student->id]);
        }
    }

    /**
     * Method to test mod_vpl::get_students
     */
    public function test_get_students() {
        $vpl = $this->vpldefault;
        $this->internal_test_get_students($vpl->get_students(), $this->students);
        $this->internal_test_get_students($vpl->get_students('', 'u.username'), $this->students);
        $this->internal_test_get_students($vpl->get_students('', ',u.username'), $this->students);
    }

    public function test_add_submission() {
        // Test regular submission.
        // Test equal submission.
        // Test team submission and last user submission.
        // Test team to individual submission.
        // Test overflow remove.
    }

    /**
     * Method to test mod_vpl::print_submission_restriction
     */
    public function test_print_submission_restriction() {
        // TODO Refactor code to test print submission.
    }

}
