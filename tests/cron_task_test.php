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


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');

use mod_vpl\tests\base_fixture;

/**
 * Unit tests for cron_task class.
 *
 *       (now - range / 2)       now       (now + range / 2)
 *  -------------|---------------|---------------|-------------
 *  out of range             on range             out of range
 * @group mod_vpl
 * @covers \mod_vpl\task\cron_task
 */
final class cron_task_test extends base_fixture {
    /**
     * Fixture object of class \mod_vpl\task\cron_task
     * @var \mod_vpl\task\cron_task
     */
    private $crontask;
    /**
     * Method to create lib test fixture
     */
    protected function setUp(): void {
        parent::setUp();
        $this->setupinstances();
        $this->crontask = new \mod_vpl\task\cron_task();
        $this->crontask->set_verbose(false);
    }

    /**
     * Method to test start date before range.
     */
    public function test_startdate_before_range(): void {
        global $DB;
        $this->setUser($this->editingteachers[0]);
        foreach ($this->vpls as $vpl) {
            $cm = $vpl->get_course_module();
            $instance = $vpl->get_instance();
            $instance->startdate = time() + $this->crontask->get_time_range() + 5;
            $instance->duedate = time() + $this->crontask->get_time_range() + 100;
            $DB->update_record(VPL, $instance);
            $this->assertTrue(set_coursemodule_visible($cm->id, false));
            rebuild_course_cache($cm->course, true);
        }
        $this->crontask->execute();
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            $this->assertTrue(instance_is_visible(VPL, $instance) == 0);
        }
    }

    /**
     * Method to test startdate on range.
     */
    public function test_startdate_on_range(): void {
        global $DB;
        foreach ($this->vpls as $vpl) {
            $cm = $vpl->get_course_module();
            $instance = $vpl->get_instance();
            $instance->startdate = time() + $this->crontask->get_time_range() / 4;
            $instance->duedate = 0;
            $this->assertTrue($DB->update_record(VPL, $instance));
            $this->assertTrue(set_coursemodule_visible($cm->id, false));
            rebuild_course_cache($cm->course, true);
        }
        $this->crontask->execute();
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            $this->assertEquals(instance_is_visible(VPL, $instance), 1);
        }
    }

    /**
     * Method to test a recently passed start date within range.
     */
    public function test_startdate(): void {
        global $DB;
        foreach ($this->vpls as $vpl) {
            $cm = $vpl->get_course_module();
            $instance = $vpl->get_instance();
            $instance->startdate = time() - 5;
            $instance->duedate = 0;
            $DB->update_record(VPL, $instance);
            $this->assertTrue(set_coursemodule_visible($cm->id, false));
            rebuild_course_cache($cm->course, true);
        }
        $this->crontask->execute();
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            $this->assertTrue(instance_is_visible(VPL, $instance) == 1);
        }
    }

    /**
     * Method to test startdate out of range.
     */
    public function test_startdate_out_of_range(): void {
        global $DB;
        foreach ($this->vpls as $vpl) {
            $cm = $vpl->get_course_module();
            $instance = $vpl->get_instance();
            $instance->startdate = time() - $this->crontask->get_time_range() - 5;
            $instance->duedate = 0;
            $DB->update_record(VPL, $instance);
            $this->assertTrue(set_coursemodule_visible($cm->id, false));
            rebuild_course_cache($cm->course, true);
        }
        $this->crontask->execute();
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            $this->assertTrue(instance_is_visible(VPL, $instance) == 0);
        }
    }

    /**
     * Method to test duedate out of range.
     */
    public function test_duedate_out_of_range(): void {
        global $DB;
        foreach ($this->vpls as $vpl) {
            $cm = $vpl->get_course_module();
            $instance = $vpl->get_instance();
            $instance->startdate = time() + $this->crontask->get_time_range() / 4;
            $instance->duedate = time() - 5;
            $DB->update_record(VPL, $instance);
            $this->assertTrue(set_coursemodule_visible($cm->id, false));
            rebuild_course_cache($cm->course, true);
        }
        $this->crontask->execute();
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            $this->assertTrue(instance_is_visible(VPL, $instance) == 0);
        }
    }
}
