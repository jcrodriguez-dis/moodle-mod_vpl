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
 * Unit tests for mod/vpl/lib.php.
 *
 * @package mod_vpl
 * @copyright  Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl;

use \stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/mod/vpl/vpl_submission_CE.class.php');

/**
 * Unit tests for mod/vpl/lib.php functions.
 * @group mod_vpl
 */
class lib_test extends base_test {

    /**
     * Method to create lib test fixture
     */
    protected function setUp(): void {
        parent::setUp();
        $this->setupinstances();
    }

    /**
     * Method to test vpl_grade_item_update() function
     * @covers \vpl_grade_item_update
     */
    public function test_vpl_grade_item_update() {
        $this->setUser($this->editingteachers[0]);
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            foreach ([false, 0, -1, 8, 12.5] as $testgrade) {
                if ($testgrade !== false) {
                    $instance->grade = $testgrade;
                    $this->assertTrue(vpl_grade_item_update($instance) == 0, $instance->name);
                }
                $grades = grade_get_grades($vpl->get_course()->id, 'mod', 'vpl', $instance->id);
                if ( count($grades->items) > 0 ) {
                    $gradeinfo = null;
                    foreach ($grades->items as $gi) {
                        $gradeinfo = $gi;
                    }
                    if ($instance->grade > 0) {
                        $this->assertTrue($gradeinfo->grademax == $instance->grade, $instance->name);
                    } else if ($instance->grade < 0) {
                        $this->assertTrue($gradeinfo->scaleid == -$instance->grade, $instance->name);
                    } else {
                        $this->fail($instance->name);
                    }
                } else {
                    $this->assertTrue($instance->grade == 0, $instance->name);
                }
            }
        }
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            $submissions = $vpl->all_last_user_submission();
            if (count($submissions) > 0) {
                foreach ([8, 12.5] as $testgrademax) {
                    foreach ([4, 5, 12.5, 14] as $testgrade) {
                        $instance->grade = $testgrademax;
                        $grades = array();
                        $ids = array();
                        foreach ($submissions as $sub) {
                            $grade = new stdClass();
                            $grade->userid = $sub->userid;
                            $grade->rawgrade = $testgrade;
                            $grade->usermodified = $this->editingteachers[0]->id;
                            $grade->dategraded = time();
                            $grade->datesubmitted = $sub->datesubmitted;
                            $grades[$grade->userid] = $grade;
                            $ids[] = $grade->userid;
                        }
                        $this->assertTrue(vpl_grade_item_update($instance) == 0, $instance->name);
                        $this->assertTrue(vpl_grade_item_update($instance, $grades) == 0, $instance->name);
                        $getgrades = grade_get_grades($vpl->get_course()->id, 'mod', 'vpl', $instance->id, $ids);
                        $this->assertTrue(count($getgrades->items) == 1);
                        foreach ($getgrades->items as $gi) {
                            $gradeinfo = $gi;
                        }
                        $this->assertTrue(count($gradeinfo->grades) == count($grades));
                        foreach ($gradeinfo->grades as $userid => $grade) {
                            $ge = $grades[$userid];
                            $gr = $grade;
                            $this->assertTrue($gr->usermodified == $ge->usermodified);
                            $this->assertTrue($gr->grade == min($ge->rawgrade, $testgrademax));
                            $this->assertTrue($gr->dategraded == $ge->dategraded);
                            $this->assertTrue($gr->datesubmitted == $ge->datesubmitted);
                        }
                    }
                }
            }
        }
    }

    /**
     * Method to test vpl_update_grades() function
     * @covers \vpl_update_grades
     */
    public function test_vpl_update_grades() {
        global $DB;
        $this->setUser($this->editingteachers[0]);
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            $submissions = $vpl->all_last_user_submission();
            if (count($submissions) > 0) {
                foreach ([8, 12.5] as $testgrademax) {
                    foreach ([4, 5, 12.5, 14] as $testgrade) {
                        $instance->grade = $testgrademax;
                        $grades = array();
                        $ids = array();
                        // Update submissions with grade information.
                        foreach ($submissions as $sub) {
                            $sub->grade = $testgrade;
                            $sub->grader = $this->editingteachers[0]->id;
                            $sub->dategraded = time();
                            $DB->update_record(VPL_SUBMISSIONS, $sub);
                            $grade = new stdClass();
                            $grade->userid = $sub->userid;
                            $grade->rawgrade = $testgrade;
                            $grade->usermodified = $this->editingteachers[0]->id;
                            $grade->dategraded = $sub->dategraded;
                            $grade->datesubmitted = $sub->datesubmitted;
                            $grades[$grade->userid] = $grade;
                            $ids[] = $grade->userid;
                        }
                        // Test vpl_update_grades.
                        vpl_update_grades($instance);
                        $getgrades = grade_get_grades($vpl->get_course()->id, 'mod', 'vpl', $instance->id, $ids);
                        $this->assertTrue(count($getgrades->items) == 1);
                        foreach ($getgrades->items as $gi) {
                            $gradeinfo = $gi;
                        }
                        $this->assertTrue(count($gradeinfo->grades) == count($grades));
                        foreach ($gradeinfo->grades as $userid => $grade) {
                            $ge = $grades[$userid];
                            $gr = $grade;
                            $this->assertTrue($gr->usermodified == $ge->usermodified);
                            $this->assertTrue($gr->grade == min($ge->rawgrade, $testgrademax));
                            $this->assertTrue($gr->dategraded == $ge->dategraded);
                            $this->assertTrue($gr->datesubmitted == $ge->datesubmitted);
                        }
                    }
                }
            }
        }
    }

    /**
     * Method to test vpl_delete_grade_item() function
     * @covers \vpl_delete_grade_item
     */
    public function test_vpl_delete_grade_item() {
        $this->setUser($this->editingteachers[0]);
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            $grades = grade_get_grades($vpl->get_course()->id, 'mod', 'vpl', $instance->id);
            if ( count($grades->items) > 0 ) {
                vpl_delete_grade_item($instance);
                $grades = grade_get_grades($vpl->get_course()->id, 'mod', 'vpl', $instance->id);
                $gradeinfo = null;
                foreach ($grades->items as $gi) {
                    $gradeinfo = $gi;
                }
                $this->assertFalse(isset($gradeinfo), $instance->name);
            }
        }
    }

    /**
     * Method to test vpl calendar events
     * @covers \vpl_update_instance
     */
    public function test_vpl_events() {
        global $DB;
        $this->setUser($this->editingteachers[0]);
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            $instance->instance = $instance->id;
            $sparms = array ('modulename' => VPL, 'instance' => $instance->id, 'priority' => null );
            $event = $DB->get_record( 'event', $sparms );
            $this->assertTrue(($event != false && $instance->duedate == $event->timestart) ||
                    ($event == false && $instance->duedate == 0),
                    $instance->name);
            $instance->duedate = time() + 1000;
            vpl_update_instance($instance);
            $sparms = array ('modulename' => VPL, 'instance' => $instance->id, 'priority' => null );
            $event = $DB->get_record( 'event', $sparms );
            $this->assertTrue(($event != false && $instance->duedate == $event->timestart) ||
                    ($event == false && $instance->duedate == 0),
                    $instance->name);
            $instance->duedate = 0;
            vpl_update_instance($instance);
            $event = $DB->get_record( 'event', $sparms );
            $this->assertFalse($event, $instance->name);
        }
    }

    /**
     * Method to test vpl_update_instance() function
     * @covers \vpl_update_instance
     */
    public function test_vpl_update_instance() {
        // Events change tested at test_vpl_events.
        $grades = [-1, 0, 7];
        $this->setUser($this->editingteachers[0]);
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            $instance->instance = $instance->id;
            foreach ($grades as $grade) {
                $instance->grade = $grade;
                vpl_update_instance($instance);
                $getgrades = grade_get_grades($vpl->get_course()->id, 'mod', 'vpl', $instance->id);
                if ( count($getgrades->items) > 0 ) {
                    $gradeinfo = null;
                    foreach ($getgrades->items as $gi) {
                        $gradeinfo = $gi;
                    }
                    if ($instance->grade > 0) {
                        $this->assertTrue($gradeinfo->grademax == $instance->grade, $instance->name);
                    } else if ($instance->grade < 0) {
                        $this->assertTrue($gradeinfo->scaleid == -$instance->grade, $instance->name);
                    } else {
                        $this->fail($instance->name);
                    }
                } else {
                    $this->assertTrue($instance->grade == 0, $instance->name);
                }
            }
        }
    }

    /**
     * Method to test vpl_delete_instance() function
     * @covers \vpl_delete_instance
     */
    public function test_vpl_delete_instance() {
        global $DB, $CFG;
        $this->setUser($this->editingteachers[0]);
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();

            $directory = $CFG->dataroot . '/vpl_data/' . $instance->id;
            $submissions = $vpl->all_last_user_submission();
            if (count($submissions) > 0) {
                $this->assertTrue(file_exists($directory) && is_dir($directory), $instance->name);
            }

            vpl_delete_instance($instance->id);

            $res = $DB->count_records(VPL, array('id' => $instance->id));
            $this->assertEquals( 0, $res, $instance->name);

            $tables = [
                    VPL_SUBMISSIONS,
                    VPL_VARIATIONS,
                    VPL_ASSIGNED_VARIATIONS,
                    VPL_RUNNING_PROCESSES,
                    VPL_OVERRIDES,
                    VPL_ASSIGNED_OVERRIDES
            ];
            $parms = array('vpl' => $instance->id);
            foreach ($tables as $table) {
                $res = $DB->count_records($table, $parms);
                $this->assertEquals( 0, $res, $instance->name);
            }

            $res = $DB->count_records(VPL, array('basedon' => $instance->id ));
            $this->assertEquals( 0, $res, $instance->name);

            $sparms = array ('modulename' => VPL, 'instance' => $instance->id );
            $res = $DB->count_records('event', $sparms );
            $this->assertEquals($res, 0, $instance->name);

            $this->assertFalse(file_exists($directory) && is_dir($directory), $instance->name);
        }
    }

    /**
     * Method to test vpl_supports() function
     * @covers \vpl_supports
     */
    public function test_vpl_supports() {
        $supp = [
                FEATURE_GROUPS,
                FEATURE_GROUPINGS,
                FEATURE_MOD_INTRO,
                FEATURE_GRADE_HAS_GRADE,
                FEATURE_GRADE_OUTCOMES,
                FEATURE_BACKUP_MOODLE2,
                FEATURE_SHOW_DESCRIPTION,
        ];
        foreach ($supp as $feature) {
            $this->assertTrue(vpl_supports($feature));
        }
        $nosupp = [
                FEATURE_COMPLETION_TRACKS_VIEWS,
                FEATURE_COMPLETION_HAS_RULES,
                FEATURE_ADVANCED_GRADING
        ];
        foreach ($nosupp as $feature) {
            $this->assertFalse(vpl_supports($feature));
        }
        $this->assertNull(vpl_supports('FEATURE_VPL_UNKNOW'));
    }

    /**
     * Method to test vpl_reset_gradebook() function
     * @covers \vpl_reset_gradebook
     */
    public function test_vpl_reset_gradebook() {
        global $DB;
        $this->setUser($this->editingteachers[0]);
        foreach ([4, 5, 12.5, 14] as $testgrade) {
            foreach ($this->vpls as $vpl) {
                $instance = $vpl->get_instance();
                $submissions = $vpl->all_last_user_submission();
                if (count($submissions) > 0) {
                    // Update submissions with grade information.
                    foreach ($submissions as $sub) {
                        $sub->grade = $testgrade;
                        $sub->grader = $this->editingteachers[0]->id;
                        $sub->dategraded = time();
                        $DB->update_record(VPL_SUBMISSIONS, $sub);
                        $grade = new stdClass();
                        $grade->userid = $sub->userid;
                        $grade->rawgrade = $testgrade;
                        $grade->usermodified = $this->editingteachers[0]->id;
                        $grade->dategraded = $sub->dategraded;
                        $grade->datesubmitted = $sub->datesubmitted;
                        $grades[$grade->userid] = $grade;
                    }
                    grade_update( 'mod/vpl', $instance->course, 'mod', VPL, $instance->id
                            , 0, null, $grades );
                }
            }
            // Test vpl_reset_gradebook.
            vpl_reset_gradebook($vpl->get_course()->id);
            foreach ($this->vpls as $vpl) {
                $instance = $vpl->get_instance();
                $submissions = $vpl->all_last_user_submission();
                if (count($submissions) > 0) {
                    $ids = array();
                    foreach ($submissions as $sub) {
                        $ids[] = $sub->userid;
                    }
                    $getgrades = grade_get_grades($vpl->get_course()->id, 'mod', 'vpl', $instance->id, $ids);
                    $grades = $getgrades->items[0]->grades;
                    $this->assertTrue(count($grades) == count($ids));
                    foreach ($ids as $userid) {
                        $grade = $grades[$userid];
                        $this->assertTrue($grade->grade == '');
                    }
                }
            }
        }
    }

    /**
     * Method to test vpl_reset_instance_userdata() function
     * @covers \vpl_reset_instance_userdata
     */
    public function test_vpl_reset_instance_userdata() {
        global $DB, $CFG;
        $this->setUser($this->editingteachers[0]);
        // Reset user data from instances.
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            vpl_reset_instance_userdata($instance->id);
            $parms = array( 'vpl' => $instance->id);
            $count = $DB->count_records(VPL_SUBMISSIONS, $parms);
            $this->assertEquals(0, $count, $instance->name);
            $parms = array( 'vpl' => $instance->id);
            $count = $DB->count_records(VPL_ASSIGNED_VARIATIONS, $parms);
            $this->assertEquals(0, $count, $instance->name);
            $parms = array( 'vpl' => $instance->id);
            $count = $DB->count_records(VPL_ASSIGNED_OVERRIDES, $parms);
            $this->assertEquals(0, $count, $instance->name);
            $directory = $CFG->dataroot . '/vpl_data/'. $instance->id . '/usersdata';
            $this->assertFalse(file_exists($directory) && is_dir($directory), $instance->name);
        }
    }

    /**
     * Method to test vpl_reset_userdata() function
     * @covers \vpl_reset_userdata
     */
    public function test_vpl_reset_userdata() {
        $nsubs = array();
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            $nsubs[$instance->id] = count($vpl->all_user_submission('s.id'));
        }
        // Reset nothing.
        $data = new stdClass();
        $data->reset_vpl_submissions = false;
        $status = vpl_reset_userdata($data);
        $this->assertCount(0, $status);
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            $count = count($vpl->all_user_submission('s.id'));
            $this->assertEquals($nsubs[$instance->id], $count, $instance->name);
        }
        $data->reset_vpl_submissions = true;
        $data->courseid = $this->vpldefault->get_course()->id;
        $status = vpl_reset_userdata($data);
        $this->assertCount(count($this->vpls), $status);
        foreach ($status as $st) {
            $this->assertFalse($st['error']);
        }
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            $count = count($vpl->all_user_submission('s.id'));
            $this->assertEquals(0, $count, $instance->name);
        }
    }

}
