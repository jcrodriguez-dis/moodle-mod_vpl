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
 * Unit tests for class vpl_running_processes mod/vpl/jail/vpl_running_processes.class.php
 *
 * @package mod_vpl
 * @copyright Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');
require_once($CFG->dirroot . '/mod/vpl/jail/running_processes.class.php');

/**
 * Unit tests for vpl_running_processes class.
 * @group mod_vpl
 * @covers \vpl_running_processes
 */
class running_processes_test extends base_test {
    const TABLE = 'vpl_running_processes';
    protected object $run;
    protected object $debug;
    protected object $evaluate;
    protected object $directrun;
    protected object $otheruserrun;
    protected object $othervplrun;
    /**
     * Method to create the fixture
     */
    protected function setUp(): void {
        parent::setUp();
        $this->setup_default_instance();
        $this->setup_onefile_instance();
        $this->run = new \stdClass();
        $this->run->userid = $this->students[0]->id;
        $this->run->vpl = $this->vpldefault->get_instance()->id;
        $this->run->type = 0;
        $this->run->server = 'https://this.is.the.server/algo';
        $this->run->adminticket = 'fkdshkj';
        $this->debug = clone $this->run;
        $this->debug->type = 1;
        $this->debug->adminticket = 'fkdshdlkfskj';
        $this->evaluate = clone $this->run;
        $this->evaluate->type = 2;
        $this->evaluate->adminticket = 'fkdshdlkfskj34';
        $this->directrun = clone $this->run;
        $this->directrun->type = 3;
        $this->directrun->adminticket = 'fkdshdlkfskj7';
        $this->otheruserrun = clone $this->run;
        $this->otheruserrun->userid = $this->students[1]->id;
        $this->otheruserrun->adminticket = 'otheruserrun7';
        $this->othervplrun = clone $this->run;
        $this->othervplrun->vpl = $this->vplonefile->get_instance()->id;;
        $this->othervplrun->adminticket = 'othervplrun7';
        $this->run->id = \vpl_running_processes::set($this->run);
        $this->debug->id = \vpl_running_processes::set($this->debug);
        $this->evaluate->id = \vpl_running_processes::set($this->evaluate);
        $this->directrun->id = \vpl_running_processes::set($this->directrun);
        $this->otheruserrun->id = \vpl_running_processes::set($this->otheruserrun);
        $this->othervplrun->id = \vpl_running_processes::set($this->othervplrun);
    }

    /**
     * Method to delete the fixture
     */
    protected function tearDown(): void {
        global $DB;
        $DB->delete_records(self::TABLE);
        parent::tearDown();
    }

    protected function check_record($expected, $actual) {
        $fields = ['id', 'userid', 'vpl', 'type', 'server', 'adminticket'];
        foreach ($fields as $field) {
            $this->assertEquals($expected->$field, $actual->$field, "Field $field mismatch");
        }
        $this->assertTrue(time() - $actual->start_time <= 10 && time() >= $actual->start_time);
    }

    public function test_get_run() {
        $userid = $this->students[0]->id;
        $otheruserid = $this->students[1]->id;
        $vplid = $this->vpldefault->get_instance()->id;
        $othervplid = $this->vplonefile->get_instance()->id;
        $actual = \vpl_running_processes::get_run($userid, $othervplid);
        $this->check_record($this->othervplrun, $actual);
        $actual = \vpl_running_processes::get_run($otheruserid);
        $this->check_record($this->otheruserrun, $actual);
        $actual = \vpl_running_processes::get_run($otheruserid, $vplid);
        $this->check_record($this->otheruserrun, $actual);
        $actual = \vpl_running_processes::get_run($userid, $vplid, $this->otheruserrun->adminticket);
        $this->assertFalse($actual);
        $actual = \vpl_running_processes::get_run($userid, $vplid, $this->run->adminticket);
        $this->check_record($this->run, $actual);
        $actual = \vpl_running_processes::get_run($userid, $vplid, $this->debug->adminticket);
        $this->check_record($this->debug, $actual);
        $actual = \vpl_running_processes::get_run($userid, $vplid, $this->evaluate->adminticket);
        $this->check_record($this->evaluate, $actual);
        $actual = \vpl_running_processes::get_run($otheruserid, $vplid, $this->otheruserrun->adminticket);
        $this->check_record($this->otheruserrun, $actual);
        $actual = \vpl_running_processes::get_run($userid, $vplid, $this->directrun->adminticket);
        $this->assertFalse($actual);
    }

    public function test_get_directrun() {
        $userid = $this->students[0]->id;
        $otheruserid = $this->students[1]->id;
        $vplid = $this->vpldefault->get_instance()->id;
        $othervplid = $this->vplonefile->get_instance()->id;
        $actual = \vpl_running_processes::get_directrun($userid, $othervplid);
        $this->assertCount(0, $actual);
        $actual = \vpl_running_processes::get_directrun($otheruserid);
        $this->assertCount(0, $actual);
        $actual = \vpl_running_processes::get_directrun($otheruserid, $vplid);
        $this->assertCount(0, $actual);
        $actual = \vpl_running_processes::get_directrun($userid);
        $this->assertCount(1, $actual);
        $this->check_record($this->directrun, $actual[$this->directrun->id]);
        $actual = \vpl_running_processes::get_directrun($userid, $vplid);
        $this->assertCount(1, $actual);
        $this->check_record($this->directrun, $actual[$this->directrun->id]);
    }

    protected function internal_test_get_by_id($records) {
        foreach ($records as $record) {
            $actual = \vpl_running_processes::get_by_id($record->vpl, $record->userid, $record->id);
            $this->check_record($record, $actual);
        }
    }

    public function test_get_by_id() {
        $records = [
            $this->run,
            $this->debug,
            $this->evaluate,
            $this->directrun,
            $this->otheruserrun,
            $this->othervplrun,
        ];
        $this->internal_test_get_by_id($records);
    }
    public function test_delete() {
        $record = $this->debug;
        \vpl_running_processes::delete($record->userid, $record->vpl, $record->adminticket);
        $actual = \vpl_running_processes::get_by_id($record->vpl, $record->userid, $record->id);
        $this->assertFalse($actual);
        $records = [
            $this->run,
            $this->evaluate,
            $this->directrun,
            $this->otheruserrun,
            $this->othervplrun,
        ];
        $this->internal_test_get_by_id($records);

        $record = $this->otheruserrun;
        \vpl_running_processes::delete($record->userid, $record->vpl);
        $actual = \vpl_running_processes::get_by_id($record->vpl, $record->userid, $record->id);
        $this->assertFalse($actual);
        $records = [
            $this->run,
            $this->evaluate,
            $this->directrun,
            $this->othervplrun,
        ];
        $this->internal_test_get_by_id($records);

        $record = $this->run;
        \vpl_running_processes::delete($record->userid, $record->vpl);
        $records = [ $this->run, $this->debug, $this->evaluate, $this->directrun];
        foreach ($records as $record) {
            $actual = \vpl_running_processes::get_by_id($record->vpl, $record->userid, $record->id);
            $this->assertFalse($actual);
        }
        $records = [
            $this->othervplrun,
        ];
        $this->internal_test_get_by_id($records);
    }

    public function test_lanched_processes() {
        $actual = \vpl_running_processes::lanched_processes($this->course->id);
        $this->assertCount(6, $actual);
        $actual = \vpl_running_processes::lanched_processes($this->course->id + 10000);
        $this->assertCount(0, $actual);
    }
}
