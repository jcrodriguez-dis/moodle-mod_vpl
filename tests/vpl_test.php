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

namespace mod_vpl;

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
class vpl_test extends base_test {

    /**
     * Method to create test fixture
     */
    protected function setUp(): void {
        parent::setUp();
        $this->setupinstances();
    }

    /**
     * Method to test mod_vpl::delete_all
     * @covers \mod_vpl::delete_all
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
                VPL_RUNNING_PROCESSES,
                VPL_OVERRIDES,
                VPL_ASSIGNED_OVERRIDES
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
     * @covers \mod_vpl::get_students
     */
    public function test_get_students() {
        $vpl = $this->vpldefault;
        $this->internal_test_get_students($vpl->get_students(), $this->students);
        $this->internal_test_get_students($vpl->get_students('', 'u.username'), $this->students);
        $this->internal_test_get_students($vpl->get_students('', ',u.username'), $this->students);
    }

    /**
     * Method to test mod_vpl::add_submission
     * @covers \mod_vpl::add_submission
     */
    public function test_add_submission() {
        // Test regular submission.
        // Test equal submission.
        // Test team submission and last user submission.
        // Test team to individual submission.
        // Test overflow remove.
    }

    /**
     * Method to test mod_vpl::print_submission_restriction
     * @covers \mod_vpl::print_submission_restriction
     */
    public function test_print_submission_restriction() {
        // TODO Refactor code to test print submission.
    }

    /**
     * Method to test mod_vpl::get_effective_setting
     * @covers \mod_vpl::get_effective_setting
     */
    public function test_get_effective_setting() {
        $vpl = $this->vploverrides;
        $instance = $vpl->get_instance();
        $baseduedate = $instance->duedate;

        // Check that student 0 has default settings.
        $user = $this->students[0];
        foreach (array('startdate', 'duedate', 'reductionbyevaluation', 'freeevaluations') as $field) {
            $this->assertEquals(
                    $instance->$field,
                    $vpl->get_effective_setting($field, $user->id),
                    $instance->name . ': ' . $user->username . ' ' . $field
            );
        }

        // Check that student 1 and student 2 have everything (due date is postponed by 1 day) overriden.
        foreach (array($this->students[1], $this->students[2]) as $user) {
            foreach (array('startdate', 'reductionbyevaluation', 'freeevaluations') as $field) {
                $this->assertNotEquals(
                        $instance->$field,
                        $vpl->get_effective_setting($field, $user->id),
                        $instance->name . ': ' . $user->username . ' ' . $field
                );
            }
            $this->assertEquals(
                    $baseduedate + DAYSECS,
                    $vpl->get_effective_setting('duedate', $user->id),
                    $instance->name . ': ' . $user->username . ' duedate'
            );
        }

        // Check that student 3, teacher 0 and editing teacher 0 has due date (due date is postponed by 2 days) overriden.
        foreach (array($this->students[3], $this->teachers[0], $this->editingteachers[0]) as $user) {
            foreach (array('startdate', 'reductionbyevaluation', 'freeevaluations') as $field) {
                $this->assertEquals(
                        $instance->$field,
                        $vpl->get_effective_setting($field, $user->id),
                        $instance->name . ': ' . $user->username . ' ' . $field
                );
            }
            $this->assertEquals(
                    $baseduedate + 2 * DAYSECS,
                    $vpl->get_effective_setting('duedate', $user->id),
                    $instance->name . ': ' . $user->username . ' duedate'
            );
        }

        // Check that teacher 1 has due date (due date is disabled) overriden.
        $user = $this->teachers[1];
        foreach (array('startdate', 'reductionbyevaluation', 'freeevaluations') as $field) {
            $this->assertEquals(
                    $instance->$field,
                    $vpl->get_effective_setting($field, $user->id),
                    $instance->name . ': ' . $user->username . ' ' . $field
            );
        }
        $this->assertEquals(
                0,
                $vpl->get_effective_setting('duedate', $user->id),
                $instance->name . ': ' . $user->username . ' duedate'
        );

        // Check for any other vpl that settings are not overriden.
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            if ($instance->name == $this->vploverrides->get_instance()->name) {
                continue;
            }
            foreach ($this->users as $user) {
                foreach (array('startdate', 'duedate', 'reductionbyevaluation', 'freeevaluations') as $field) {
                    $this->assertEquals(
                            $instance->$field,
                            $vpl->get_effective_setting($field, $user->id),
                            $instance->name. ': ' . $user->username . ' ' . $field
                    );
                }
            }
        }
    }

    /**
     * Method to test mod_vpl::update_override_calendar_events
     * @covers \mod_vpl::update_override_calendar_events
     */
    public function test_update_override_calendar_events() {
        global $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');
        $vpl = $this->vploverrides;
        $instance = $vpl->get_instance();
        $baseduedate = $instance->duedate;
        $start = $baseduedate - DAYSECS;
        $end = $baseduedate + 3 * DAYSECS;

        // Check that student 0 has default duedate event.
        $user = $this->students[0];
        $userevents = array_filter(calendar_get_events($start, $end, $user->id, false, $instance->course),
                function($event) use ($instance) {
                    return $event->modulename == VPL && $event->instance == $instance->id;
                }
        );
        $this->assertCount(
                1,
                $userevents,
                $instance->name . ': events for ' . $user->username
        );
        $this->assertEquals(
                $baseduedate,
                reset($userevents)->timestart,
                $instance->name . ': event for ' . $user->username
        );

        // Check that student 1 and student 2 have due date postponed by 1 day event.
        foreach (array($this->students[1], $this->students[2]) as $user) {
            $userevents = array_filter(calendar_get_events($start, $end, $user->id, false, $instance->course),
                    function($event) use ($instance) {
                        return $event->modulename == VPL && $event->instance == $instance->id
                        && $event->priority !== null && $event->priority == CALENDAR_EVENT_USER_OVERRIDE_PRIORITY;
                    }
            );
            $this->assertCount(
                    1,
                    $userevents,
                    $instance->name . ': events for ' . $user->username
            );
            $this->assertEquals(
                    $baseduedate + DAYSECS,
                    reset($userevents)->timestart,
                    $instance->name . ': event for ' . $user->username
            );
        }

        // Check that student 3 has due date postponed by 2 days (user) event.
        $user = $this->students[3];
        $userevents = array_filter(calendar_get_events($start, $end, $user->id, false, $instance->course),
                function($event) use ($instance) {
                    return $event->modulename == VPL && $event->instance == $instance->id
                    && $event->priority !== null && $event->priority == CALENDAR_EVENT_USER_OVERRIDE_PRIORITY;
                }
        );
        $this->assertCount(
                1,
                $userevents,
                $instance->name . ': events for ' . $user->username
        );
        $this->assertEquals(
                $baseduedate + 2 * DAYSECS,
                reset($userevents)->timestart,
                $instance->name . ': event for ' . $user->username
        );

        // Check that teacher 0 and editing teacher 0 have due date postponed by 2 days (group) event.
        foreach (array($this->groups[2], $this->groups[3]) as $group) {
            $groupevents = array_filter(calendar_get_events($start, $end, false, $group->id, $instance->course),
                    function($event) use ($instance) {
                        return $event->modulename == VPL && $event->instance == $instance->id
                        && $event->priority !== null && $event->priority > CALENDAR_EVENT_USER_OVERRIDE_PRIORITY;
                    }
            );
            $this->assertCount(
                    1,
                    $groupevents,
                    $instance->name . ': events for ' . $group->name
            );
            $this->assertEquals(
                    $baseduedate + 2 * DAYSECS,
                    reset($groupevents)->timestart,
                    $instance->name . ': event for ' . $group->name
            );
        }

        // Check that teacher 1 has due date event disabled.
        $user = $this->teachers[1];
        $userevents = array_filter(calendar_get_events(0, $end, $user->id, false, $instance->course),
                function($event) use ($instance) {
                    return $event->modulename == VPL && $event->instance == $instance->id
                    && $event->priority !== null && $event->priority == CALENDAR_EVENT_USER_OVERRIDE_PRIORITY;
                }
        );
        $this->assertCount(
                1,
                $userevents,
                $instance->name . ': events for ' . $user->username
        );
        $this->assertEquals(
                0,
                reset($userevents)->timestart,
                $instance->name . ': event for ' . $user->username
        );
    }

}
