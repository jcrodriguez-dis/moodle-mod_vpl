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
use \mod_vpl_submission;
use \mod_vpl_submission_CE;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use core_privacy\local\request\userlist;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/mod/vpl/vpl_submission_CE.class.php');

/**
 * Unit tests for \mod_vpl\privacy\provider class.
 *
 * @group mod_vpl
 * @group mod_vpl_privacy_provider
 * @covers \mod_vpl\privacy\provider
 */
class privacy_provider_test extends base_test {
    /**
     * Fixture object of class \mod_vpl\privacy\provider
     */
    private $provider;
    /**
     * Fixture object of class mod_vpl_submission_CE
     */
    private $submission1, $submission2;
    /**
     * Method to create lib test fixture
     */
    protected function setUp(): void {
        parent::setUp();
        $this->setupinstances();
        $this->provider = new testable_provider();
        $this->setUser($this->students[2]);
        $res = $this->vplvariations->get_variation($this->students[2]->id);
        if ($res === false) {
            $this->fail();
        }

        $this->setUser($this->editingteachers[0]);
        $files = array('a.c' => "int main(){\nprintf(\"editingteachers\");\n}");
        $error = '';
        $submissionid = $this->vplonefile->add_submission($this->editingteachers[0]->id, $files, '', $error);
        if ($submissionid == 0 || $error != '' ) {
            $this->fail($error);
        }

        $gradeinfo = new stdClass();
        $gradeinfo->grade = 5;
        $gradeinfo->comments = '- Well done!';
        $sub = new mod_vpl_submission($this->vplonefile, $submissionid);
        if (! $sub->set_grade($gradeinfo)) {
            $this->fail($error);
        }
        $subrecord = $this->vplteamwork->last_user_submission($this->students[1]->id);
        if ($subrecord === false) {
            $this->fail($error);
        }
        $sub = new mod_vpl_submission($this->vplteamwork, $subrecord);
        if (! $sub->set_grade($gradeinfo)) {
            $this->fail($error);
        }
    }

    /**
     * Clears the writer singlenton afer each test.
     */
    protected function tearDown(): void {
        writer::reset();
        parent::tearDown();
    }

    protected function check_vpls_contexts(array $vpls, contextlist $contexts, $message) {
        $this->assertEquals(count($vpls), count($contexts));
        foreach ($vpls as $vpl) {
            $found = false;
            foreach ($contexts as $context) {
                if ($vpl->get_course_module()->id == $context->instanceid) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, $message);
        }
    }

    /**
     * Method to test get_contexts_for_userid.
     */
    public function test_get_contexts_for_userid() {
        $users = [$this->students[0], $this->students[1], $this->students[2], $this->editingteachers[0], $this->students[5]];
        $usersvpls = [
            [$this->vplonefile, $this->vplmultifile, $this->vplvariations, $this->vplteamwork],
            [$this->vplmultifile, $this->vplvariations, $this->vplteamwork, $this->vploverrides],
            [$this->vplvariations, $this->vploverrides],
            [$this->vplonefile, $this->vplteamwork],
            [],
        ];
        for ($i = 0; $i < count($users); $i++) {
            $userid = $users[$i]->id;
            $vpls = $usersvpls[$i];
            $contexts = $this->provider->get_contexts_for_userid($userid);
            $this->assertEquals(count($vpls), $contexts->count(), "User {$users[$i]->username}");
            $this->check_vpls_contexts($vpls, $contexts, "User {$users[$i]->username}");
        }
    }

    protected function export_set_up() {
        $this->setUser($this->students[4]);
        $files = array('a.c' => "int main(){\nprintf(\"student4\");\n}");
        $error = '';
        $submissionid1 = $this->vplonefile->add_submission($this->students[4]->id, $files, 'algo', $error);
        if ($submissionid1 == 0 || $error != '' ) {
            $this->fail($error);
        }
        $this->submission1 = new mod_vpl_submission_CE($this->vplonefile, $submissionid1);

        $files = array('a.c' => "int main(){\nprintf(\"student4 second\");\n}");
        $error = '';
        $submissionid2 = $this->vplonefile->add_submission($this->students[4]->id, $files, '', $error);
        if ($submissionid2 == 0 || $error != '' ) {
            $this->fail($error);
        }
        $this->submission2 = new mod_vpl_submission_CE($this->vplonefile, $submissionid2);

        $this->setUser($this->teachers[1]);
        $gradeinfo = new stdClass();
        $gradeinfo->grade = 7.5;
        $gradeinfo->comments = '- Regular done!';
        $this->submission2->set_grade($gradeinfo);
    }

    /**
     * Method to test export user data for student.
     */
    public function test_export_user_data_for_student() {
        $this->export_set_up();
        $contexts = $this->provider->get_contexts_for_userid($this->students[4]->id);
        $context = $this->vplonefile->get_context();
        $this->assertEquals($context, $contexts->current());
        $approved = new \core_privacy\local\request\approved_contextlist($this->students[4], 'mod_vpl', array($context->id));
        $this->provider->export_user_data($approved);
        $writer = \core_privacy\local\request\writer::with_context($context);

        $data = $writer->get_data([]);
        $this->assertInstanceOf('stdClass', $data);
        $this->assertEquals($this->vplonefile->get_instance()->id, $data->id);
        $this->assertEquals('One file', $data->name);
        $this->assertEquals('Short description', $data->shortdescription);
        $gradestr = get_string('grademax', 'core_grades') . ': ' . format_float(10, 5, true, true);
        $this->assertEquals($gradestr, $data->grade);

        $sub1instance = $this->submission1->get_instance();
        $data = $writer->get_data([get_string('privacy:submissionpath', 'vpl', 1)]);
        $this->assertInstanceOf('stdClass', $data);
        $this->assertEquals(transform::datetime($sub1instance->datesubmitted), $data->datesubmitted);
        $this->assertEquals('algo', $data->comments);
        $this->assertEquals(0, $data->nevaluations);

        $sub2instance = $this->submission2->get_instance();
        $data = $writer->get_data([get_string('privacy:submissionpath', 'vpl', 2)]);
        $this->assertInstanceOf('stdClass', $data);
        $this->assertEquals(transform::datetime($sub2instance->datesubmitted), $data->datesubmitted);
        $this->assertEquals('', $data->comments);
        $this->assertEquals(0, $data->nevaluations);
        $this->assertEquals(transform::datetime($sub2instance->dategraded), $data->dategraded);
        $this->assertEquals($sub2instance->grade, $data->grade);
        $this->assertEquals('- Regular done!', $data->gradercomments);

        $data = $writer->get_data([get_string('privacy:submissionpath', 'vpl', 3)]);
        $this->assertEquals([], $data);
    }

    /**
     * Method to test export user data with variation.
     */
    public function test_export_user_data_with_variation() {
        $contexts = $this->provider->get_contexts_for_userid($this->students[2]->id);
        $context = $this->vplvariations->get_context();
        $this->assertEquals($context, $contexts->current());
        $approved = new \core_privacy\local\request\approved_contextlist($this->students[2], 'mod_vpl', array($context->id));
        $this->provider->export_user_data($approved);
        $writer = \core_privacy\local\request\writer::with_context($context);

        $data = $writer->get_data([]);
        $this->assertInstanceOf('stdClass', $data);
        $this->assertEquals($this->vplvariations->get_instance()->id, $data->id);
        $this->assertEquals('Variations', $data->name);
        $this->assertEquals('', $data->shortdescription);
        $gradestr = get_string('grademax', 'core_grades') . ': ' . format_float(10, 5, true, true);
        $this->assertEquals($gradestr, $data->grade);

        $data = $writer->get_data([get_string('privacy:variationpath', 'vpl')]);
        $this->setUser($this->students[2]);
        $userid = $this->students[2]->id;
        $res = $this->vplvariations->get_variation($userid);
        $this->assertEquals($res->vpl, $data->vpl);
        $this->assertEquals($userid, $data->userid);
        $this->assertEquals($res->description, $data->variation);
    }

    /**
     * Method to test export user data for grader.
     */
    public function test_export_user_data_for_grader() {
        $this->export_set_up();
        $contexts = $this->provider->get_contexts_for_userid($this->teachers[1]->id);
        $context = $this->vplonefile->get_context();
        $this->assertEquals($context, $contexts->current());
        $approved = new \core_privacy\local\request\approved_contextlist($this->teachers[1], 'mod_vpl', array($context->id));
        $this->provider->export_user_data($approved);
        $writer = \core_privacy\local\request\writer::with_context($context);

        $this->assertTrue($writer->has_any_data());
        $data = $writer->get_data([]);
        $this->assertInstanceOf('stdClass', $data);
        $this->assertEquals($this->vplonefile->get_instance()->id, $data->id);
        $this->assertEquals('One file', $data->name);
        $data = $writer->get_data([get_string('privacy:submissionpath', 'vpl', 1)]);
        $this->assertInstanceOf('stdClass', $data);
        $this->assertEquals('', $data->comments);
        $this->assertEquals(0, $data->nevaluations);
        $this->assertEquals(7.5, $data->grade);
        $this->assertEquals('- Regular done!', $data->gradercomments);

        $data = $writer->get_data([get_string('privacy:submissionpath', 'vpl', 2)]);
        $this->assertEquals([], $data);
    }

    /**
     * Method to test export user data with running processes.
     */
    public function test_export_user_data_with_running_processes() {
        global $DB;
        $instance = $this->vplonefile->get_instance();
        $vplid = $instance->id;
        $userid = $this->students[0]->id;
        for ($i = 1; $i < 4; $i++) {
            $parms = array(
                'userid' => $userid,
                'vpl' => $vplid,
                'server' => 'https://www.server' . $i . '.com',
                'type' => 0,
                'start_time' => time(),
                'adminticket' => 'secret',
            );
            $DB->insert_record( VPL_RUNNING_PROCESSES, $parms);
        }
        $context = $this->vplonefile->get_context();
        $approved = new \core_privacy\local\request\approved_contextlist($this->students[0], 'mod_vpl', array($context->id));
        $this->provider->export_user_data($approved);
        $writer = \core_privacy\local\request\writer::with_context($context);
        for ($i = 1; $i < 4; $i++) {
            $data = $writer->get_data([get_string('privacy:runningprocesspath', 'vpl', $i) ]);
            $this->assertInstanceOf('stdClass', $data);
            $this->assertEquals($vplid, $data->vpl);
            $this->assertEquals($userid, $data->userid);
            $this->assertEquals('www.server' . $i . '.com', $data->server);
        }
    }

    /**
     * Method to test export_user_preferences.
     */
    public function test_export_user_preferences() {
        // Student 0.
        set_user_preference('vpl_editor_fontsize', 14, $this->students[0]);
        set_user_preference('vpl_acetheme', 'Eclipse', $this->students[0]);
        set_user_preference('vpl_terminaltheme', 0, $this->students[0]);
        // Student 1.
        set_user_preference('vpl_editor_fontsize', 10, $this->students[1]);
        set_user_preference('vpl_acetheme', 'VPL', $this->students[1]);
        set_user_preference('vpl_terminaltheme', 2, $this->students[1]);
        // Student 2.
        set_user_preference('vpl_acetheme', 'Netbeans', $this->students[2]);
        // Student 3.
        set_user_preference('vpl_editor_fontsize', 8, $this->students[3]);
        // Teacher 0.
        set_user_preference('vpl_editor_fontsize', 10, $this->editingteachers[1]);
        set_user_preference('vpl_acetheme', 'VPL', $this->editingteachers[1]);
        set_user_preference('vpl_terminaltheme', 2, $this->editingteachers[1]);

        $expected = array('vpl_editor_fontsize' => 14, 'vpl_acetheme' => 'Eclipse', 'vpl_terminaltheme' => 0);
        $this->assertEquals($expected, $this->provider->get_user_preferences($this->students[0]->id));
        $expected = array('vpl_editor_fontsize' => 10, 'vpl_acetheme' => 'VPL', 'vpl_terminaltheme' => 2);
        $this->assertEquals($expected, $this->provider->get_user_preferences($this->students[1]->id));
        $this->assertEquals($expected, $this->provider->get_user_preferences($this->editingteachers[1]->id));

        $expected = array('vpl_acetheme' => 'Netbeans');
        $this->assertEquals($expected, $this->provider->get_user_preferences($this->students[2]->id));

        $expected = array('vpl_editor_fontsize' => 8);
        $this->assertEquals($expected, $this->provider->get_user_preferences($this->students[3]->id));

        $expected = array();
        $this->assertEquals($expected, $this->provider->get_user_preferences($this->students[4]->id));
    }
    /**
     * Method to test provider::delete_data_for_all_users_in_context.
     */
    public function test_delete_data_for_all_users_in_context() {
        $removelist = [$this->vplonefile, $this->vplmultifile, $this->vplvariations, $this->vplteamwork];
        $users = [$this->students[0], $this->students[1], $this->students[2], $this->editingteachers[0], $this->students[5]];
        $usersvpls = [
            [$this->vplonefile, $this->vplmultifile, $this->vplvariations, $this->vplteamwork],
            [$this->vplmultifile, $this->vplvariations, $this->vplteamwork, $this->vploverrides],
            [$this->vplvariations, $this->vploverrides],
            [$this->vplonefile, $this->vplteamwork],
            []
        ];
        foreach ($removelist as $remove) {
            $this->provider->delete_data_for_all_users_in_context($remove->get_context());
            for ($i = 0; $i < count($users); $i++) {
                if (($key = array_search($remove, $usersvpls[$i])) !== false) {
                    array_splice($usersvpls[$i], $key, 1);
                }
            }
            for ($i = 0; $i < count($users); $i++) {
                $userid = $users[$i]->id;
                $vpls = $usersvpls[$i];
                $contexts = $this->provider->get_contexts_for_userid($userid);
                $this->assertCount(count($vpls), $contexts, "User {$users[$i]->username}");
                $this->check_vpls_contexts($vpls, $contexts, "User {$users[$i]->username}");
            }
        }
    }

    /**
     * Method to test provider::delete_data_for_user.
     */
    public function test_delete_data_for_user() {
        // The editingteacher0 graded the submission of student 1. editingteacher0 must goes first to simplify tests.
        $users = [$this->editingteachers[0], $this->students[0], $this->students[1], $this->students[2],  $this->students[5]];
        $usersvpls = [
            [$this->vplonefile, $this->vplteamwork],
            [$this->vplonefile, $this->vplmultifile, $this->vplvariations, $this->vplteamwork],
            [$this->vplmultifile, $this->vplvariations, $this->vplteamwork, $this->vploverrides],
            [$this->vplvariations, $this->vploverrides],
            []
        ];
        for ($i = 0; $i < count($users); $i++) {
            $userid = $users[$i]->id;
            $vpls = $usersvpls[$i];
            $contexts = $this->provider->get_contexts_for_userid($userid);
            $this->assertEquals(count($vpls), $contexts->count(), "User {$users[$i]->username}");
            $this->check_vpls_contexts($vpls, $contexts, "User {$users[$i]->username}");
        }
        // Remove first context of each user.
        for ($n = 0; $n < count($users); $n++) {
            $contextids = [];
            $user = $users[$n];
            if (count($usersvpls[$n]) > 0) {
                $contextids = [$usersvpls[$n][0]->get_context()->id];
                array_splice($usersvpls[$n], 0, 1);
                $approved = new \core_privacy\local\request\approved_contextlist($user, 'mod_vpl', $contextids);
                $this->assertEquals(1, $approved->count(), "User {$user->username}");
                $userid = $user->id;
                $ncontextsbefore = $this->provider->get_contexts_for_userid($userid)->count();
                $this->provider->delete_data_for_user($approved);
                $ncontextsafter = $this->provider->get_contexts_for_userid($userid)->count();
                $this->assertEquals($ncontextsbefore - 1, $ncontextsafter, "User {$user->username}");
            }

            for ($i = 0; $i < count($users); $i++) {
                $userid = $users[$i]->id;
                $vpls = $usersvpls[$i];
                $contexts = $this->provider->get_contexts_for_userid($userid);
                $this->assertEquals(count($vpls), $contexts->count(), "User {$users[$i]->username}");
                $this->check_vpls_contexts($vpls, $contexts, "User {$users[$i]->username}");
            }
        }
        // Remove all context of each user.
        for ($n = 0; $n < count($users); $n++) {
            $contextids = [];
            $info = "";
            foreach ($usersvpls[$n] as $vpl) {
                $contextids[] = $vpl->get_context()->id;
                $info .= $vpl->get_instance()->name . " | ";
            }
            $approved = new \core_privacy\local\request\approved_contextlist($users[$n], 'mod_vpl', $contextids);
            $this->assertEquals(count($usersvpls[$n]), $approved->count(), "User {$users[$n]->username} {$info}");
            $this->provider->delete_data_for_user($approved);
            $usersvpls[$n] = [];
            for ($i = 0; $i < count($users); $i++) {
                $userid = $users[$i]->id;
                $vpls = $usersvpls[$i];
                $contexts = $this->provider->get_contexts_for_userid($userid);
                $this->assertEquals(count($vpls), $contexts->count(), "User {$users[$i]->username}");
                $this->check_vpls_contexts($vpls, $contexts, "User {$users[$i]->username}");
            }
        }
    }

    /**
     * Method to test provider::delete_data_for_users one user.
     */
    public function test_delete_data_for_users_one_user() {
        // The editing teacher 0 graded the submission of student 1. Teacher 0 must goes first to simplify tests.
        $users = [$this->editingteachers[0], $this->students[0], $this->students[1], $this->students[2], $this->students[5]];
        $usersvpls = [
            [$this->vplonefile, $this->vplteamwork],
            [$this->vplonefile, $this->vplmultifile, $this->vplvariations, $this->vplteamwork],
            [$this->vplmultifile, $this->vplvariations, $this->vplteamwork, $this->vploverrides],
            [$this->vplvariations, $this->vploverrides],
            []
        ];

        // Remove first context of each user.
        for ($n = 0; $n < count($users); $n++) {
            if (count($usersvpls[$n]) == 0) {
                continue;
            }
            $context = $usersvpls[$n][0]->get_context();
            $approved = new \core_privacy\local\request\approved_userlist($context, 'mod_vpl', array($users[$n]->id));
            array_splice($usersvpls[$n], 0, 1);

            $this->provider->delete_data_for_users($approved);

            for ($i = 0; $i < count($users); $i++) {
                $userid = $users[$i]->id;
                $vpls = $usersvpls[$i];
                $contexts = $this->provider->get_contexts_for_userid($userid);
                $this->assertCount(count($vpls), $contexts, "User {$users[$i]->username}");
                $this->check_vpls_contexts($vpls, $contexts, "User {$users[$i]->username}");
            }
        }
    }

    /**
     * Method to test provider::delete_data_for_users many users.
     */
    public function test_delete_data_for_users() {
        $allvpls = [$this->vplnotavailable, $this->vplonefile, $this->vplmultifile, $this->vplvariations, $this->vplteamwork];
        $users = [$this->students[0], $this->students[1], $this->students[2], $this->editingteachers[0], $this->students[5]];
        $usersvpls = [
            [$this->vplonefile, $this->vplmultifile, $this->vplvariations, $this->vplteamwork],
            [$this->vplmultifile, $this->vplvariations, $this->vplteamwork, $this->vploverrides],
            [$this->vplvariations, $this->vploverrides],
            [$this->vplonefile, $this->vplteamwork],
            []
        ];
        $userlist = [];
        foreach ($users as $user) {
            $userlist[] = $user->id;
        }
        foreach ($allvpls as $vpl) {
            $context = $vpl->get_context();
            $approved = new \core_privacy\local\request\approved_userlist($context, 'mod_vpl', $userlist);

            $this->provider->delete_data_for_users($approved);

            for ($i = 0; $i < count($users); $i++) {
                $userid = $users[$i]->id;
                $vpls = &$usersvpls[$i];
                if (($key = array_search($vpl, $vpls)) !== false) {
                    array_splice($vpls, $key, 1);
                }
                $contexts = $this->provider->get_contexts_for_userid($userid);
                $this->assertCount(count($vpls), $contexts, "User {$users[$i]->username}");
                $this->check_vpls_contexts($vpls, $contexts, "User {$users[$i]->username}");
            }
        }
    }

    /**
     * Method to test provider::get_users_in_context.
     */
    public function test_get_users_in_context() {
        $vpls = [$this->vplnotavailable, $this->vplonefile, $this->vplmultifile, $this->vplvariations, $this->vplteamwork];
        $users = [$this->students[0], $this->students[1], $this->students[2], $this->editingteachers[0], $this->students[5]];
        $usersvpls = [
            [$this->vplonefile, $this->vplmultifile, $this->vplvariations, $this->vplteamwork],
            [$this->vplmultifile, $this->vplvariations, $this->vplteamwork, $this->vploverrides],
            [$this->vplvariations, $this->vploverrides],
            [$this->vplonefile, $this->vplteamwork],
            []
        ];
        foreach ($vpls as $vpl) {
            $context = $vpl->get_context();
            $userlist = new userlist($context, 'mod_vpl');
            $this->provider->get_users_in_context($userlist);
            $expecteduserids = [];
            for ($i = 0; $i < count($users); $i++) {
                if (array_search($vpl, $usersvpls[$i]) !== false) {
                    $expecteduserids[] = $users[$i]->id;
                }
            }
            $a1 = $expecteduserids;
            $a2 = $userlist->get_userids();
            sort($a1);
            sort($a2);
            $this->assertEquals($a1, $a2);
        }

    }
}

/**
 * Class to use instead of \mod_vpl\privacy\provider.
 * This derived class of \mod_vpl\privacy\provider expose protected methods
 * as public to test it.
 */
class testable_provider extends \mod_vpl\privacy\provider {
    private static $nothing = false;
    public static function get_user_preferences(int $userid): array {
        self::$nothing = true; // Removes codecheck warning.
        return parent::get_user_preferences($userid);
    }
}
