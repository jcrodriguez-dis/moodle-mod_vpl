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
 * Base fixture for unit tests
 * Code inspired on mod/assign/tests/base_test.php
 * @package mod_vpl
 * @copyright Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */

namespace mod_vpl\tests;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');

/**
 * Base fixture for unit tests.
 * Code based on mod/assign/tests/base_test.php
 */
class base_fixture extends \advanced_testcase {
    /** @var number of students to create */
    const DEFAULT_STUDENT_COUNT = 7;

    /** @var number of teachers to create */
    const DEFAULT_TEACHER_COUNT = 2;

    /** @var number of editing teachers to create */
    const DEFAULT_EDITING_TEACHER_COUNT = 2;

    /** @var number of groups to create */
    const GROUP_COUNT = 4;

    /** @var number of groups to create */
    const GROUPING_COUNT = 2;

    /** @var stdClass $course New course created to hold VPL instances */
    protected $course = null;

    /** @var array $teachers List of DEFAULT_TEACHER_COUNT teachers in the course*/
    protected $teachers = null;

    /** @var array $editingteachers List of DEFAULT_EDITING_TEACHER_COUNT editing teachers in the course */
    protected $editingteachers = null;

    /** @var array $students List of DEFAULT_STUDENT_COUNT students in the course*/
    protected $students = null;

    /** @var array $users List of users in the course*/
    protected $users = null;

    /** @var array $groups List of groups in the course */
    protected $groups = null;

    /** @var array $groupings List of groupings in the course */
    protected $groupings = null;

    /**
     * Setup function - we will create a course and add an VPL instances to it.
     */
    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest(true);

        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->teachers = [];
        for ($i = 0; $i < self::DEFAULT_TEACHER_COUNT; $i++) {
            $record = new stdClass();
            $record->username = 'teacher' . $i;
            array_push($this->teachers, $this->getDataGenerator()->create_user($record));
        }

        $this->editingteachers = [];
        for ($i = 0; $i < self::DEFAULT_EDITING_TEACHER_COUNT; $i++) {
            $record = new stdClass();
            $record->username = 'editingteacher' . $i;
            array_push($this->editingteachers, $this->getDataGenerator()->create_user($record));
        }

        $this->students = [];
        for ($i = 0; $i < self::DEFAULT_STUDENT_COUNT; $i++) {
            $record = new stdClass();
            $record->username = 'student' . $i;
            array_push($this->students, $this->getDataGenerator()->create_user($record));
        }

        $this->users = array_merge($this->students, $this->teachers, $this->editingteachers);

        $this->groups = [];
        for ($i = 0; $i < self::GROUP_COUNT; $i++) {
            array_push($this->groups, $this->getDataGenerator()->create_group(['courseid' => $this->course->id]));
        }

        $this->groupings = [];
        for ($i = 0; $i < self::GROUPING_COUNT; $i++) {
            array_push($this->groupings, $this->getDataGenerator()->create_grouping(['courseid' => $this->course->id]));
        }
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        foreach ($this->teachers as $i => $teacher) {
            $this->getDataGenerator()->enrol_user(
                $teacher->id,
                $this->course->id,
                $teacherrole->id
            );
        }
        foreach ($this->teachers as $i => $teacher) {
            groups_add_member($this->groups[2], $teacher);
        }

        $editingteacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        foreach ($this->editingteachers as $i => $editingteacher) {
            $this->getDataGenerator()->enrol_user(
                $editingteacher->id,
                $this->course->id,
                $editingteacherrole->id
            );
        }
        foreach ($this->editingteachers as $i => $editingteacher) {
            groups_add_member($this->groups[3], $editingteacher);
        }

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        foreach ($this->students as $student) {
            $this->getDataGenerator()->enrol_user(
                $student->id,
                $this->course->id,
                $studentrole->id
            );
        }
        $usernum = 0;
        foreach ($this->students as $student) {
            $groupselected = $usernum % 2;
            groups_add_member($this->groups[$groupselected]->id, $student->id);
            $student->groupassigned = $groupselected;
            $usernum++;
        }
        $groupnum = 0;
        foreach ($this->groups as $group) {
            if ($groupnum < 2) {
                $parm = ['groupingid' => $this->groupings[0]->id, 'groupid' => $group->id];
                $this->getDataGenerator()->create_grouping_group($parm);
            }
            if ($groupnum > 0) {
                $parm = ['groupingid' => $this->groupings[1]->id, 'groupid' => $group->id];
                $this->getDataGenerator()->create_grouping_group($parm);
            }
            $groupnum++;
        }
    }

    /**
     * Tear down function - we will remove the course and VPL instance in it.
     */
    protected function tearDown(): void {
        \mod_vpl::reset_db_cache();
        parent::tearDown();
    }

    /**
     * @var \mod_vpl\mod_vpl $vpldefault Instance of VPL default tests.
     *
     * This will be used to access the default instance in the tests.
     */
    protected $vpldefault = null;

    /**
     * @var \mod_vpl\mod_vpl $vplnotavailable Instance of VPL not available for students tests.
     *
     * This will be used to access the not available for students instance in the tests.
     */
    protected $vplnotavailable = null;

    /**
     * @var \mod_vpl\mod_vpl $vplonefile Instance of VPL with one file tests.
     *
     * This will be used to access the one file instance in the tests.
     */
    protected $vplonefile = null;

    /**
     * @var \mod_vpl\mod_vpl $vplmultifile Instance of VPL for multifile tests.
     *
     * This will be used to access the multifile instance in the tests.
     */
    protected $vplmultifile = null;

    /**
     * @var \mod_vpl\mod_vpl $vplvariations Instance of VPL for variations tests.
     *
     * This will be used to access the variations instance in the tests.
     */

    protected $vplvariations = null;

    /**
     * @var \mod_vpl\mod_vpl $vploverrides Instance of VPL for overrides tests.
     *
     * This will be used to access the overrides instance in the tests.
     */
    protected $vploverrides = null;

    /**
     * @var \mod_vpl\mod_vpl $vplteamwork Instance of VPL for teamwork tests.
     *
     * This will be used to access the teamwork instance in the tests.
     */
    protected $vplteamwork = null;

    /**
     * @var array $vpls List of VPL instances created in the setup.
     *
     * This will be used to access the instances in the tests.
     */
    protected $vpls = null;

    /**
     * Setup function - we will create VPL instances.
     *
     * This function will create VPL instances that will be used in the tests.
     */
    protected function setupinstances() {
        // Add VPL instances.
        $this->setup_default_instance();
        $this->setup_notavailable_instance();
        $this->setup_onefile_instance();
        $this->setup_multifile_instance();
        $this->setup_variations_instance();
        $this->setup_overrides_instance();
        $this->setup_vplteamwork_instance();
        $this->vpls = [
                $this->vpldefault,
                $this->vplnotavailable,
                $this->vplonefile,
                $this->vplmultifile,
                $this->vplvariations,
                $this->vploverrides,
                $this->vplteamwork,
        ];
        return;
    }

    /**
     * Setup function - we will create a default instance.
     *
     * This instance will be used as the default one for tests.
     */
    protected function setup_default_instance() {
        $this->setUser($this->editingteachers[0]);
        $parms = ['name' => 'default', 'evaluate' => 1];
        $this->vpldefault = $this->create_instance($parms);
    }

    /**
     * Setup function - we will create a not available instance.
     *
     * This instance will be not available for students.
     */
    protected function setup_notavailable_instance() {
        $this->setUser($this->editingteachers[0]);
        $parms = [
                'name' => 'not available',
                'duedate' => 0,
                'maxfiles' => 3,
                'maxfilesize' => 100,
                'requirednet' => '',
                'password' => 'hola',
                'grade' => 15,
                'visiblegrade' => false,
                'usevariations' => false,
                'example' => false,
                'worktype' => 0,
        ];

        $this->vplnotavailable = $this->create_instance($parms);
    }

    /**
     * Setup function - we will create a onefile instance.
     *
     * This instance will have one file and one submission.
     */
    protected function setup_onefile_instance() {
        $this->setUser($this->editingteachers[0]);
        $parms = [
                'name' => 'One file',
                'shortdescription' => 'Short description',
                'duedate' => time() + 3600,
                'maxfiles' => 1,
                'maxfilesize' => 1000,
                'grade' => 10,
                'worktype' => 0,
        ];
        $this->vplonefile = $this->create_instance($parms);
        $rqfiles = $this->vplonefile->get_required_fgm();
        $rqfiles->addallfiles(['a.c' => "int main(){\n}"]);
        // Add a submission.
        $this->setUser($this->students[0]);
        $userid = $this->students[0]->id;
        $files = ['a.c' => "int main(){\nprintf(\"Hola\");\n}"];
        $error = '';
        $submissionid = $this->vplonefile->add_submission($userid, $files, '', $error);
        if ($submissionid == 0 || $error != '') {
            $this->fail($error);
        }
    }

    /**
     * Setup function - we will create a multifile instance.
     *
     * This instance will have multiple files and submissions.
     */
    protected function setup_multifile_instance() {
        $this->setUser($this->editingteachers[0]);
        $parms = [
                'name' => 'Multiple files',
                'duedate' => time() + 3600,
                'maxfiles' => 10,
                'maxfilesize' => 1000,
                'grade' => 10,
                'worktype' => 0,
                'basedon' => $this->vplonefile->get_instance()->id,
        ];
        $this->vplmultifile = $this->create_instance($parms);
        // Add a submission.
        $this->setUser($this->students[0]);
        $userid = $this->students[0]->id;
        $files = [
                'a.c' => "int main(){\nprintf(\"Hola1\");\n}",
                'b.c' => "inf f(int n){\n if (n<1) return 1;\n else return n+f(n-1);\n}\n",
                'b.h' => "#define MV 4\n",
        ];
        $error = '';
        $submissionid = $this->vplmultifile->add_submission($userid, $files, '', $error);
        if ($submissionid == 0 || $error != '') {
            $this->fail($error);
        }
        // Add other submission.
        $this->setUser($this->students[1]);
        $userid = $this->students[1]->id;
        $files = [
                'a.c' => "int main(){\nprintf(\"Hola2\");\n}",
                'b.c' => "inf f(int n){\n if (n<1) return 1;\n else return n+f(n-1);\n}\n",
                'b.h' => "#define MV 5\n",
        ];
        $submissionid = $this->vplmultifile->add_submission($userid, $files, '', $error);
        if ($submissionid == false || $error != '') {
            $this->fail($error);
        }
    }

    /**
     * Setup function - we will create a variations instance.
     *
     * This instance will have variations and submissions.
     */
    protected function setup_variations_instance() {
        global $DB;
        $this->setUser($this->editingteachers[0]);
        $parms = [
                'name' => 'Variations',
                'duedate' => time() + 3600,
                'maxfiles' => 10,
                'maxfilesize' => 1000,
                'grade' => 10,
                'worktype' => 0,
                'usevariations' => 1,
                'variationtitle' => 'Variations Title',
        ];
        $this->vplvariations = $this->create_instance($parms);
        $instance = $this->vplvariations->get_instance();
        for ($i = 1; $i < 6; $i++) {
            $parms = [
                'vpl' => $instance->id,
                'identification' => '' . $i,
                'description' => 'variation ' . $i,
            ];
            $DB->insert_record(VPL_VARIATIONS, $parms);
        }
        // Add a submission.
        $this->setUser($this->students[0]);
        $userid = $this->students[0]->id;
        $files = [
                'a.c' => "int main(){\nprintf(\"Hola3\");\n}",
                'b.c' => "inf f(int n){\n if (n<1) return 1;\n else return n+f(n-1);\n}\n",
                'b.h' => "#define MV 6\n",
        ];
        $error = '';
        $submissionid = $this->vplvariations->add_submission($userid, $files, '', $error);
        if ($submissionid == 0 || $error != '') {
            $this->fail($error);
        }
        // Add other submission.
        $this->setUser($this->students[1]);
        $userid = $this->students[1]->id;
        $files = [
                'a.c' => "int main(){\nprintf(\"Hola4\");\n}",
                'b.c' => "inf f(int n){\n if (n<1) return 1;\n else return n+f(n-1);\n}\n",
                'b.h' => "#define MV 7\n",
        ];
        $error = '';
        $submissionid = $this->vplvariations->add_submission($userid, $files, '', $error);
        if ($submissionid == false || $error != '') {
            $this->fail($error);
        }
    }

    /**
     * Setup function - we will create an overrides instance.
     *
     * This instance will have overrides for students and teachers.
     */
    protected function setup_overrides_instance() {
        global $DB;
        $this->setUser($this->editingteachers[0]);
        $now = time();
        $baseduedate = $now + DAYSECS;
        $parms = [
                'name' => 'Overrides',
                'startdate' => 0,
                'duedate' => $baseduedate,
                'freeevaluations' => 0,
                'reductionbyevaluation' => 0,
                'maxfiles' => 10,
                'maxfilesize' => 1000,
                'grade' => 10,
                'worktype' => 0,
        ];
        $this->vploverrides = $this->create_instance($parms);

        // Create overrides such that:
        // - Student 0 has default settings,
        // - Student 1 has everything (due date is postponed by 1 day) overriden (by user),
        // - Student 2 has everything (due date is postponed by 1 day) overriden (by user),
        // - Student 3 has due date (due date is postponed by 2 days) overriden (by user),
        // - Teacher 0 has due date (due date is postponed by 2 days) overriden (by group),
        // - Editing teacher 0 has due date (due date is postponed by 2 days) overriden (by group),
        // - Teacher 1 has due date (due date is disabled) overriden (by group and by user, latter should prevail).

        $override = new stdClass();
        $override->vpl = $this->vploverrides->get_instance()->id;
        $override->startdate = $now - 3600;
        $override->duedate = $baseduedate + DAYSECS;
        $override->freeevaluations = 10;
        $override->reductionbyevaluation = 1;
        $override->id = $DB->insert_record(VPL_OVERRIDES, $override);
        $assignedoverride = new stdClass();
        $assignedoverride->vpl = $override->vpl;
        $assignedoverride->override = $override->id;
        $userids = [$this->students[1]->id, $this->students[2]->id];
        foreach ($userids as $userid) {
            $assignedoverride->userid = $userid;
            $assignedoverride->groupid = null;
            $DB->insert_record(VPL_ASSIGNED_OVERRIDES, $assignedoverride);
        }
        $override->userids = implode(',', $userids);
        $override->groupids = null;
        $this->vploverrides->update_override_calendar_events($override);

        $override = new stdClass();
        $override->vpl = $this->vploverrides->get_instance()->id;
        $override->startdate = null;
        $override->duedate = $baseduedate + 2 * DAYSECS;
        $override->freeevaluations = null;
        $override->reductionbyevaluation = null;
        $override->id = $DB->insert_record(VPL_OVERRIDES, $override);
        $assignedoverride = new stdClass();
        $assignedoverride->vpl = $override->vpl;
        $assignedoverride->override = $override->id;
        $assignedoverride->userid = $this->students[3]->id;
        $assignedoverride->groupid = null;
        $DB->insert_record(VPL_ASSIGNED_OVERRIDES, $assignedoverride);
        $groupids = [$this->groups[2]->id, $this->groups[3]->id];
        foreach ($groupids as $groupid) {
            $assignedoverride->userid = null;
            $assignedoverride->groupid = $groupid;
            $DB->insert_record(VPL_ASSIGNED_OVERRIDES, $assignedoverride);
        }
        $override->userids = $this->students[3]->id;
        $override->groupids = implode(',', $groupids);
        $this->vploverrides->update_override_calendar_events($override);

        $override = new stdClass();
        $override->vpl = $this->vploverrides->get_instance()->id;
        $override->groupids = null;
        $override->startdate = null;
        $override->duedate = 0;
        $override->freeevaluations = null;
        $override->reductionbyevaluation = null;
        $override->id = $DB->insert_record(VPL_OVERRIDES, $override);
        $assignedoverride = new stdClass();
        $assignedoverride->vpl = $override->vpl;
        $assignedoverride->override = $override->id;
        $assignedoverride->userid = $this->teachers[1]->id;
        $assignedoverride->groupid = null;
        $DB->insert_record(VPL_ASSIGNED_OVERRIDES, $assignedoverride);
        $override->userids = $this->teachers[1]->id;
        $override->groupids = null;
        $this->vploverrides->update_override_calendar_events($override);
    }

    /**
     * Setup function - we will create a VPL instance with teamwork.
     */
    protected function setup_vplteamwork_instance() {
        global $DB;
        $this->setUser($this->editingteachers[0]);
        $parms = [
                'name' => 'Team work',
                'duedate' => time() + 3600,
                'maxfiles' => 10,
                'maxfilesize' => 1000,
                'grade' => 10,
                'worktype' => 1,
                'basedon' => $this->vplonefile->get_instance()->id,
        ];
        $this->vplteamwork = $this->create_instance($parms);
        $cm = $this->vplteamwork->get_course_module();
        $param = ['id' => $cm->id, "groupingid" => $this->groupings[0]->id];
        $DB->update_record("course_modules", $param);
        $this->vplteamwork->get_course_module()->groupingid = $this->groupings[0]->id;
        unset($this->vplteamwork->group_activity);
        // Add a submission.
        $this->setUser($this->students[0]);
        $userid = $this->students[0]->id;
        $files = [
                'a.c' => "int main(){\nprintf(\"Hola5\");\n}",
                'b.c' => "inf f(int n){\n if (n<1) return 1;\n else return n+f(n-1);\n}\n",
                'b.h' => "#define MV 8\n",
        ];
        $error = '';
        $submissionid = $this->vplteamwork->add_submission($userid, $files, '', $error);
        if ($submissionid == 0 || $error != '') {
            $this->fail($error);
        }
        // Add other submission.
        $this->setUser($this->students[1]);
        $userid = $this->students[1]->id;
        $files = [
                'a.c' => "int main(){\nprintf(\"Hola6\");\n}",
                'b.c' => "inf f(int n){\n if (n<1) return 1;\n else return n+f(n-1);\n}\n",
                'b.h' => "#define MV 9\n",
        ];
        $error = '';
        $submissionid = $this->vplteamwork->add_submission($userid, $files, '', $error);
        if ($submissionid == 0 || $error != '') {
            $this->fail($error);
        }
    }

    /**
     * Creates a testable instance of VPL.
     *
     * @param array $params Array of parameters to pass to the generator
     * @return testable_vpl Testable wrapper around the mod_vpl class.
     */
    protected function create_instance($params = []) {
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_vpl');
        if (!isset($params['course'])) {
            $params['course'] = $this->course->id;
        }
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance(VPL, $instance->id);
        return new testable_vpl($cm->id);
    }

    /**
     * Test that we can create an instance of mod_vpl.
     */
    public function test_create_instance(): void {
        if (isset($this->course)) { // No fixture => don't check.
            $this->assertNotEmpty($this->create_instance());
        }
    }

    /**
     * Call protected method of passed object
     *
     * @param object $obj with protected methods
     * @param string $name name of the method
     * @param array $args list of parameters
     * @return mixed
     */
    public static function call_method($obj, $name, array $args) {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }
}
