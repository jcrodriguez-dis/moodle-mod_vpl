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
 * Unit tests VPL web service
 *
 * @package mod_vpl
 * @copyright Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl;

use \mod_vpl_webservice;
use \Exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/mod/vpl/vpl_submission_CE.class.php');

/**
 * Unit tests for VPL webservice.
 * @group mod_vpl
 * @group mod_vpl_webservice
 * @covers \mod_vpl_webservice
 * @runTestsInSeparateProcesses
 */
class webservice_test extends base_test {
    private function vpl_call_service($url, $fun, $request = '') {
        if (! function_exists( 'curl_init' )) {
            return 'PHP cURL requiered';
        }
        $plugincfg = get_config('mod_vpl');
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url . $fun );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/urlencode;charset=UTF-8'));
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $request );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
        if ( @$plugincfg->acceptcertificates ) {
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        }
        $rawresponse = curl_exec( $ch );
        echo "Raw " . $url. " response " . $rawresponse . "\n";
        if ($rawresponse === false) {
            $error = 'request failed: ' . s( curl_error( $ch ) );
            curl_close( $ch );
            return $error;
        } else {
            curl_close( $ch );
            return json_decode($rawresponse, null, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }

    /**
     * Method to create test fixture
     */
    protected function setUp(): void {
        global $CFG, $DB;
        parent::setUp();
        $this->setupinstances();
        $CFG->enablewebservices = true;
        $DB->insert_record( 'external_services', array (
                'name' => 'mod_vpl',
                'restrictedusers' => 0,
                'timecreated' => time(),
                'timemodified' => time(),
                'shortname' => 'mod_vpl_edit',
                'enabled' => 1,
                'downloadfiles' => 0
        ) );
        $esid = $DB->get_record('external_services', array('name' => 'mod_vpl'))->id;
        $functionnames = array('mod_vpl_evaluate',
                               'mod_vpl_get_result',
                               'mod_vpl_info',
                               'mod_vpl_open',
                               'mod_vpl_save'
        );
        foreach ($functionnames as $fn) {
            $DB->insert_record( 'external_services_functions', array (
                'externalserviceid' => $esid,
                'functionname' => $fn) );
        }
        $this->setUser($this->students[1]);
    }

    /**
     * Restore the use_xmlrpc plugin configuration setting
     */
    protected function tearDown(): void {
        set_config('use_xmlrpc', false, 'mod_vpl');
        parent::tearDown();
    }

    /**
     * Description of test_vpl_webservice_token
     * @runInSeparateProcess
     */
    public function test_vpl_webservice_token() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vpl/externallib.php');
        if ( ! vpl_get_webservice_available()) {
            $this->markTestSkipped('VPL web service not tested: Web service not available.');
        }
        $this->assertTrue(vpl_get_webservice_token( $this->vpldefault ) > "" );
        $this->assertTrue(vpl_get_webservice_token( $this->vplnotavailable ) > "" );
        $this->assertTrue(vpl_get_webservice_token( $this->vplonefile ) > "");
        $this->assertTrue(vpl_get_webservice_token( $this->vplmultifile ) > "" );
        $this->assertTrue(vpl_get_webservice_token( $this->vplvariations ) > "" );
        $this->assertTrue(vpl_get_webservice_token( $this->vplteamwork ) > "" );
    }

    private function internal_test_files($files, $filesarray) {
        $this->assertEquals(count($files), count($filesarray));
        foreach ($filesarray as $file) {
            $this->assertTrue(isset($files[$file['name']]));
            if (isset($file['encoding']) && $file['encoding'] == 1) {
                $this->assertEquals(base64_decode($file['data']), $files[$file['name']]);
            } else {
                $this->assertEquals($file['data'], $files[$file['name']]);
            }
        }
    }

    /**
     * Description of test_vpl_webservice_info
     * @runInSeparateProcess
     */
    public function test_vpl_webservice_info() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vpl/externallib.php');
        if ( ! vpl_get_webservice_available()) {
            $this->markTestSkipped('VPL web service not tested: Web service not available.');
        }
        foreach ($this->users as $user) {
            $this->setUser($user);
            foreach ($this->vpls as $vpl) {
                $instance = $vpl->get_instance();
                $res = mod_vpl_webservice::info($vpl->get_course_module()->id, $instance->password);
                $this->assertEquals($instance->name, $res['name']);
                $rqfiles = $vpl->get_required_fgm();
                $this->internal_test_files($rqfiles->getallfiles(), $res['reqfiles']);
            }
        }
        foreach ($this->students as $user) {
            $this->setUser($user);
            try {
                $res = mod_vpl_webservice::info($this->vplnotavailable->get_course_module()->id, 'bobería');
                $this->fail('Exception expected calling mod_vpl_webservice::info');
            } catch (Exception $e) {
                $this->assertFalse(strpos($e->getMessage(), 'password') === false);
            }
        }
        foreach ($this->teachers as $user) {
            $this->setUser($user);
            $res = mod_vpl_webservice::info($this->vplnotavailable->get_course_module()->id, 'bobería');
        }
        $this->assertIsObject(mod_vpl_webservice::info_parameters());
        $this->assertIsObject(mod_vpl_webservice::info_returns());
    }

    /**
     * Description of test_vpl_webservice_info_exceptions
     * @runInSeparateProcess
     */
    public function test_vpl_webservice_info_exceptions() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vpl/externallib.php');
        if ( ! vpl_get_webservice_available()) {
            $this->markTestSkipped('VPL web service not tested: Web service not available.');
        }
        $ok = false;
        $this->setUser($this->editingteachers[0]);

        $parms = ['name' => 'forbidden net', 'requirednet' => '1.1.1.1'];
        $forbbidennet = $this->create_instance($parms);

        $parms = ['name' => 'With password', 'password' => 'password'];
        $withpassword = $this->create_instance($parms);

        $parms = ['name' => 'Not visible'];
        $notvisible = $this->create_instance($parms);

        $this->setUser($this->students[0]);
        // The initial_checks if request come from IP in required IP/Network.
        try {
            mod_vpl_webservice::info($forbbidennet->get_course_module()->id, '');
            $this->fail('Exception expected netrequired');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The initial_checks if request come from IP in required IP/Network.
        try {
            mod_vpl_webservice::info($withpassword->get_course_module()->id, 'bad password');
            $this->fail('Exception expected if bad password');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The info if not is_visible().
        try {
            set_coursemodule_visible($notvisible->get_course_module()->id, false );
            get_fast_modinfo($notvisible->get_course_module()->course, 0, true);
            mod_vpl_webservice::info($notvisible->get_course_module()->id, '');
            $this->fail('Exception expected if not visible');
        } catch (Exception $e) {
            $ok = $e;
        }
        $this->assertIsObject($ok);
        $this->setUser($this->editingteachers[0]);
        mod_vpl_webservice::info($notvisible->get_course_module()->id, '');
    }

    private function internal_test_vpl_webservice_open($id, $files = array(),
            $compilation ='', $evaluation = '',
            $grade ='', $password = '', $userid = -1) {
        $res = mod_vpl_webservice::open($id, $password, $userid);
        $this->internal_test_files($files, $res['files']);
        $this->assertEquals($compilation, $res['compilation']);
        $this->assertEquals($evaluation, $res['evaluation']);
        $this->assertEquals($grade, $res['grade']);
    }

    /**
     * Description of test_vpl_webservice_open
     * @runInSeparateProcess
     */
    public function test_vpl_webservice_open() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vpl/externallib.php');
        if ( ! vpl_get_webservice_available()) {
            $this->markTestSkipped('VPL web service not tested: Web service not available.');
        }
        $id = $this->vpldefault->get_course_module()->id;
        foreach ($this->users as $user) {
            $this->setUser($user);
            $this->internal_test_vpl_webservice_open($id);
        }
        $id = $this->vplonefile->get_course_module()->id;
        foreach ($this->users as $user) {
            $this->setUser($user);
            if ($user == $this->students[0]) {
                $files = array('a.c' => "int main(){\nprintf(\"Hola\");\n}");
            } else {
                $files = array('a.c' => "int main(){\n}");
            }
            $this->internal_test_vpl_webservice_open($id, $files);
        }
        $id = $this->vplmultifile->get_course_module()->id;
        foreach ($this->users as $user) {
            $this->setUser($user);
            if ($user == $this->students[0]) {
                $files = array(
                    'a.c' => "int main(){\nprintf(\"Hola1\");\n}",
                    'b.c' => "inf f(int n){\n if (n<1) return 1;\n else return n+f(n-1);\n}\n",
                    'b.h' => "#define MV 4\n",
                );
                $this->internal_test_vpl_webservice_open($id, $files);
            } else if ($user == $this->students[1]) {
                $files = array(
                    'a.c' => "int main(){\nprintf(\"Hola2\");\n}",
                    'b.c' => "inf f(int n){\n if (n<1) return 1;\n else return n+f(n-1);\n}\n",
                    'b.h' => "#define MV 5\n",
                );
                $this->internal_test_vpl_webservice_open($id, $files);
            } else {
                $this->internal_test_vpl_webservice_open($id);
            }
        }
        $id = $this->vplteamwork->get_course_module()->id;
        $guser0 = $this->students[0]->groupasigned;
        $guser1 = $this->students[1]->groupasigned;
        foreach ($this->students as $user) {
            $this->setUser($user);
            if ( $guser0 == $user->groupasigned ) {
                $files = array(
                    'a.c' => "int main(){\nprintf(\"Hola5\");\n}",
                    'b.c' => "inf f(int n){\n if (n<1) return 1;\n else return n+f(n-1);\n}\n",
                    'b.h' => "#define MV 8\n",
                );
                $this->internal_test_vpl_webservice_open($id, $files);
            } else if ( $guser1 == $user->groupasigned) {
                $files = array(
                    'a.c' => "int main(){\nprintf(\"Hola6\");\n}",
                    'b.c' => "inf f(int n){\n if (n<1) return 1;\n else return n+f(n-1);\n}\n",
                    'b.h' => "#define MV 9\n",
                );
                $this->internal_test_vpl_webservice_open($id, $files);
            } else {
                $this->internal_test_vpl_webservice_open($id);
            }
        }
        // Checks new parameter userid.
        $allteachers = array_merge($this->teachers, $this->editingteachers);
        foreach ($allteachers as $teacher) {
            $this->setUser($teacher);
            $id = $this->vplonefile->get_course_module()->id;
            foreach ($this->users as $user) {
                if ($user == $this->students[0]) {
                    $files = array('a.c' => "int main(){\nprintf(\"Hola\");\n}");
                } else {
                    $files = array('a.c' => "int main(){\n}");
                }
                $this->internal_test_vpl_webservice_open($id, $files, '', '', '', '', $user->id);
            }
            $id = $this->vplmultifile->get_course_module()->id;
            foreach ($this->users as $user) {
                if ($user == $this->students[0]) {
                    $files = array(
                        'a.c' => "int main(){\nprintf(\"Hola1\");\n}",
                        'b.c' => "inf f(int n){\n if (n<1) return 1;\n else return n+f(n-1);\n}\n",
                        'b.h' => "#define MV 4\n",
                    );
                    $this->internal_test_vpl_webservice_open($id, $files, '', '', '', '', $user->id);
                } else if ($user == $this->students[1]) {
                    $files = array(
                        'a.c' => "int main(){\nprintf(\"Hola2\");\n}",
                        'b.c' => "inf f(int n){\n if (n<1) return 1;\n else return n+f(n-1);\n}\n",
                        'b.h' => "#define MV 5\n",
                    );
                    $this->internal_test_vpl_webservice_open($id, $files, '', '', '', '', $user->id);
                } else {
                    $this->internal_test_vpl_webservice_open($id, [], '', '', '', '', $user->id);
                }
            }
            $id = $this->vplteamwork->get_course_module()->id;
            $guser0 = $this->students[0]->groupasigned;
            $guser1 = $this->students[1]->groupasigned;
            foreach ($this->students as $user) {
                if ( $guser0 == $user->groupasigned ) {
                    $files = array(
                        'a.c' => "int main(){\nprintf(\"Hola5\");\n}",
                        'b.c' => "inf f(int n){\n if (n<1) return 1;\n else return n+f(n-1);\n}\n",
                        'b.h' => "#define MV 8\n",
                    );
                    $this->internal_test_vpl_webservice_open($id, $files, '', '', '', '', $user->id);
                } else if ( $guser1 == $user->groupasigned) {
                    $files = array(
                        'a.c' => "int main(){\nprintf(\"Hola6\");\n}",
                        'b.c' => "inf f(int n){\n if (n<1) return 1;\n else return n+f(n-1);\n}\n",
                        'b.h' => "#define MV 9\n",
                    );
                    $this->internal_test_vpl_webservice_open($id, $files, '', '', '', '', $user->id);
                } else {
                    $this->internal_test_vpl_webservice_open($id, [], '', '', '', '', $user->id);
                }
            }
        }
        $this->assertIsObject(mod_vpl_webservice::open_parameters());
        $this->assertIsObject(mod_vpl_webservice::open_returns());
    }

    /**
     * Description of test_vpl_webservice_open_exceptions
     * @runInSeparateProcess
     */
    public function test_vpl_webservice_open_exceptions() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vpl/externallib.php');
        if ( ! vpl_get_webservice_available()) {
            $this->markTestSkipped('VPL web service not tested: Web service not available.');
        }
        $ok = false;
        $this->setUser($this->editingteachers[0]);

        $parms = ['name' => 'forbidden net', 'requirednet' => '1.1.1.1'];
        $forbbidennet = $this->create_instance($parms);

        $parms = ['name' => 'With password', 'password' => 'password'];
        $withpassword = $this->create_instance($parms);

        $parms = ['name' => 'Not visible'];
        $notvisible = $this->create_instance($parms, ['visible' => 0]);

        $this->setUser($this->students[0]);
        // The initial_checks if request come from IP in required IP/Network.
        try {
            mod_vpl_webservice::open($forbbidennet->get_course_module()->id, '', -1);
            $this->fail('Exception expected netrequired');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The initial_checks if request come from IP in required IP/Network.
        try {
            mod_vpl_webservice::open($withpassword->get_course_module()->id, 'bad password', -1);
            $this->fail('Exception expected if bad password');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The info if not is_visible().
        try {
            set_coursemodule_visible($notvisible->get_course_module()->id, false );
            get_fast_modinfo($notvisible->get_course_module()->course, 0, true);
            mod_vpl_webservice::open($notvisible->get_course_module()->id, '', -1);
            $this->fail('Exception expected if not visible');
        } catch (Exception $e) {
            $ok = $e;
        }
        $this->assertIsObject($ok);
        $this->setUser($this->editingteachers[0]);
        mod_vpl_webservice::open($notvisible->get_course_module()->id, '', -1);
    }

    private function internal_test_vpl_webservice_save($id, $files = array(), $password = '', $userid = -1) {
        $filesarray = array();
        foreach ($files as $name => $data) {
            $file = [];
            $file['name'] = $name;
            if ( vpl_is_binary($name)) {
                $file['encoding'] = 1;
                $file['data'] = base64_encode($data);
            } else {
                $file['encoding'] = 0;
                $file['data'] = $data;
            }
            $filesarray[] = $file;
        }
        mod_vpl_webservice::save($id, $filesarray, $password, $userid);
        $this->internal_test_vpl_webservice_open($id, $files, '', '', '', '', $userid);
    }

    /**
     * Description of test_vpl_webservice_save
     * @runInSeparateProcess
     */
    public function test_vpl_webservice_save() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vpl/externallib.php');
        if ( ! vpl_get_webservice_available()) {
            $this->markTestSkipped('VPL web service not tested: Web service not available.');
        }
        $id = $this->vpldefault->get_course_module()->id;
        $files = array('a.c' => '#include <content.h>\n');
        $password = $this->vpldefault->get_instance()->password;
        foreach (array_merge($this->students, $this->teachers) as $user) {
            $this->setUser($user);
            if ( $this->vpldefault->is_submit_able() ) {
                try {
                    $this->internal_test_vpl_webservice_save($id, $files, $password);
                } catch (Exception $e) {
                    throw new \Exception("Saving submission " . $e);
                }
            }
        }
        $files = ['b.c' => '#include <content.h>\n'];
        $password = $this->vpldefault->get_instance()->password;
        $teacher = $this->editingteachers[0];
        $this->setUser($teacher);
        foreach (array_merge($this->students, $this->teachers) as $user) {
            try {
                $files['b.c'] = $files['b.c'] . $user->id;
                $this->internal_test_vpl_webservice_save($id, $files, $password, $user->id);
            } catch (Exception $e) {
                throw new \Exception("Saving submission " . $e);
            }
        }
        $files = ['b.c' => '#include <content.h>\n'];
        foreach (array_merge($this->students, $this->teachers) as $user) {
            $this->setUser($user);
            try {
                $files['b.c'] = $files['b.c'] . $user->id;
                $this->internal_test_vpl_webservice_open($id, $files, $password);
            } catch (Exception $e) {
                throw new \Exception("Saving submission " . $e);
            }
        }
        $this->assertIsObject(mod_vpl_webservice::save_parameters());
        $this->assertNull(mod_vpl_webservice::save_returns());
    }

    /**
     * Description of test_vpl_webservice_save_binary
     * @runInSeparateProcess
     */
    public function test_vpl_webservice_save_binary() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vpl/externallib.php');
        if ( ! vpl_get_webservice_available()) {
            $this->markTestSkipped('VPL web service not tested: Web service not available.');
        }
        $id = $this->vpldefault->get_course_module()->id;
        $filename = 'logo.png';
        $fullfilename = $CFG->dirroot . '/mod/vpl/tests/behat/datafiles/' . $filename;
        $this->assertFileExists($fullfilename);
        $data = file_get_contents($fullfilename);
        $this->assertTrue(strlen($data) > 1000);
        $files = [ $filename => $data];
        $password = $this->vpldefault->get_instance()->password;
        foreach (array_merge($this->students, $this->teachers) as $user) {
            $this->setUser($user);
            if ( $this->vpldefault->is_submit_able() ) {
                try {
                    $this->internal_test_vpl_webservice_save($id, $files, $password);
                } catch (Exception $e) {
                    throw new \Exception("Saving submission " . $e);
                }
            }
        }
        $this->assertIsObject(mod_vpl_webservice::save_parameters());
        $this->assertNull(mod_vpl_webservice::save_returns());
    }

    /**
     * Description of test_vpl_webservice_save_exceptions
     * @runInSeparateProcess
     */
    public function test_vpl_webservice_save_exceptions() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vpl/externallib.php');
        if ( ! vpl_get_webservice_available()) {
            $this->markTestSkipped('VPL web service not tested: Web service not available.');
        }
        $ok = false;
        $this->setUser($this->editingteachers[0]);

        $parms = ['name' => 'forbidden net', 'requirednet' => '1.1.1.1'];
        $forbbidennet = $this->create_instance($parms);

        $parms = ['name' => 'With password', 'password' => 'password'];
        $withpassword = $this->create_instance($parms);

        $parms = ['name' => 'Not visible'];
        $notvisible = $this->create_instance($parms, ['visible' => 0]);

        $parms = ['name' => 'Closed', 'duedate' => 1000];
        $closed = $this->create_instance($parms);

        $parms = ['name' => 'Example', 'example' => 1];
        $example = $this->create_instance($parms);

        $parms = ['name' => 'Nocopy', 'restrictededitor' => 1];
        $nocopy = $this->create_instance($parms);
        $files = [ ['name' => 'a.c', 'data' => '// Comment'] ];
        $this->setUser($this->students[0]);
        // The initial_checks if request come from IP in required IP/Network.
        try {
            mod_vpl_webservice::save($forbbidennet->get_course_module()->id, $files, '');
            $this->fail('Exception expected netrequired');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The initial_checks if request come from IP in required IP/Network.
        try {
            mod_vpl_webservice::save($withpassword->get_course_module()->id, $files, 'bad password');
            $this->fail('Exception expected if bad password');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The info if not is_visible().
        try {
            set_coursemodule_visible($notvisible->get_course_module()->id, false );
            get_fast_modinfo($notvisible->get_course_module()->course, 0, true);;
            mod_vpl_webservice::save($notvisible->get_course_module()->id, $files, '');
            $this->fail('Exception expected if not visible');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The info if closed.
        try {
            mod_vpl_webservice::save($nocopy->get_course_module()->id, $files, '');
            $this->fail('Exception expected if closed');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The info if example.
        try {
            mod_vpl_webservice::save($example->get_course_module()->id, $files, '');
            $this->fail('Exception expected if example');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The info if not external copy.
        try {
            mod_vpl_webservice::save($nocopy->get_course_module()->id, $files, '');
            $this->fail('Exception expected if not external copy');
        } catch (Exception $e) {
            $ok = $e;
        }
        $this->assertIsObject($ok);
        $this->setUser($this->editingteachers[0]);
        mod_vpl_webservice::save($notvisible->get_course_module()->id, $files, '');
        mod_vpl_webservice::save($nocopy->get_course_module()->id, $files, '');
        mod_vpl_webservice::save($closed->get_course_module()->id, $files, '');
    }

    /**
     * Description of test_vpl_webservice_evaluate
     * @runInSeparateProcess
     */
    public function test_vpl_webservice_evaluate() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vpl/externallib.php');
        if ( ! vpl_get_webservice_available()) {
            $this->markTestSkipped('VPL web service not tested: Web service not available.');
        }
        $id = $this->vpldefault->get_course_module()->id;
        $password = $this->vpldefault->get_instance()->password;
        $executionfiles = $this->vpldefault->get_execution_fgm();
        $added = $executionfiles->addfile('vpl_evaluate.cases', "case = t1\ninput=\noutput= Hello\n");
        $this->assertTrue($added);
        $files = array('a.c' => "#include <stdio.h>\nint main(){printf(\"Hello\\n\");}\n");
        foreach ([false, true] as $xmlrpc) {
            set_config('use_xmlrpc', $xmlrpc, 'mod_vpl');
            foreach ($this->students as $user) {
                $this->setUser($user);
                if ( $this->vpldefault->is_submit_able() ) {
                    try {
                        $this->internal_test_vpl_webservice_save($id, $files, $password);
                        mod_vpl_webservice::evaluate($id, $password);
                    } catch (Exception $e) {
                        throw new \Exception("Evaluation " . $e);
                    }
                }
            }
        }
        $this->assertIsObject(mod_vpl_webservice::evaluate_parameters());
        $this->assertIsObject(mod_vpl_webservice::evaluate_returns());
    }

    public function change_activity($instance, $teacher, $student, $changes) {
        foreach ($changes as $atribute => $value) {
            $instance->$atribute = $value;
        }
        $this->setUser($teacher);
        vpl_update_instance($instance);
        $this->setUser($student);
    }

    /**
     * Description of test_vpl_webservice_evaluate_exceptions
     * @runInSeparateProcess
     */
    public function test_vpl_webservice_evaluate_exceptions() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vpl/externallib.php');
        if ( ! vpl_get_webservice_available()) {
            $this->markTestSkipped('VPL web service not tested: Web service not available.');
        }
        $ok = false;
        $this->setUser($this->editingteachers[0]);

        $parms = ['name' => 'Test activity'];
        $activity = $this->create_instance($parms);

        $this->setUser($this->students[0]);
        $cid = $activity->get_course_module()->id;
        $instance = $activity->get_instance();
        $instance->instance = $instance->id; // Adds attibute required for vpl_update_instance
        // No submission.
        try {
            mod_vpl_webservice::evaluate($activity->get_course_module()->id, '');
            $this->fail('Exception expected no submission');
        } catch (Exception $e) {
            $ok = $e;
        }

        $files = [['name' => 'a.c', 'data' => "//\n"]];
        mod_vpl_webservice::save($cid, $files, '');

        // The initial_checks if request come from IP in required IP/Network.
        $changes = ['requirednet' => '1.1.1.1', 'evaluate' => 1];
        $this->change_activity($instance, $this->editingteachers[0], $this->students[0], $changes);
        try {
            mod_vpl_webservice::evaluate($cid, '');
            $this->fail('Exception expected netrequired');
        } catch (Exception $e) {
            $ok = $e;
        }

        $changes = ['requirednet' => '', 'password' => 'password'];
        $this->change_activity($instance, $this->editingteachers[0], $this->students[0], $changes);
        // The initial_checks if request come from IP in required IP/Network.
        try {
            mod_vpl_webservice::evaluate($cid, 'bad password');
            $this->fail('Exception expected if bad password');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The info if not is_visible().
        $parms = ['name' => 'Not visible', 'evaluate' => 1];
        $notvisible = $this->create_instance($parms);
        set_coursemodule_visible($notvisible->get_course_module()->id, false );
        get_fast_modinfo($notvisible->get_course_module()->course, 0, true);
        try {
            mod_vpl_webservice::evaluate($notvisible->get_course_module()->id, '');
            $this->fail('Exception expected if not visible');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The info if not evaluable.
        $changes = ['evaluate' => 0, 'password' => ''];
        $this->change_activity($instance, $this->editingteachers[0], $this->students[0], $changes);
        try {
            mod_vpl_webservice::evaluate($cid, '');
            $this->fail('Exception expected if not evaluable');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The info if example.
        $changes = ['evaluate' => 1, 'example' => 1];
        $this->change_activity($instance, $this->editingteachers[0], $this->students[0], $changes);
        try {
            mod_vpl_webservice::evaluate($cid, '');
            $this->fail('Exception expected if example');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The info if closed.
        $changes = ['duedate' => 1, 'example' => 0];
        $this->change_activity($instance, $this->editingteachers[0], $this->students[0], $changes);
        try {
            mod_vpl_webservice::evaluate($cid, '');
            $this->fail('Exception expected if closed');
        } catch (Exception $e) {
            $ok = $e;
        }
        $this->assertIsObject($ok);
        $this->setUser($this->editingteachers[0]);
        mod_vpl_webservice::save($notvisible->get_course_module()->id, $files, '');
        mod_vpl_webservice::evaluate($notvisible->get_course_module()->id, '');
        mod_vpl_webservice::save($activity->get_course_module()->id, $files, '');
        $changes = ['duedate' => 0, 'evaluate' => 0];
        $this->change_activity($instance, $this->editingteachers[0], $this->editingteachers[0], $changes);
        mod_vpl_webservice::evaluate($activity->get_course_module()->id, '');
        $changes = ['duedate' => 1, 'evaluate' => 1];
        $this->change_activity($instance, $this->editingteachers[0], $this->editingteachers[0], $changes);
        mod_vpl_webservice::save($activity->get_course_module()->id, $files, '');
        mod_vpl_webservice::evaluate($activity->get_course_module()->id, '');
    }

    /**
     * Description of test_vpl_webservice_get_result
     * @runInSeparateProcess
     */
    public function test_vpl_webservice_get_result() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vpl/externallib.php');
        if ( ! vpl_get_webservice_available()) {
            $this->markTestSkipped('VPL web service not tested: Web service not available.');
        }
        $id = $this->vpldefault->get_course_module()->id;
        $password = $this->vpldefault->get_instance()->password;
        $files = array('a.c' => "#include <stdio.h>\nint main(){printf(\"Hello\\n\");}\n");
        $executionfiles = $this->vpldefault->get_execution_fgm();
        $added = $executionfiles->addfile('vpl_evaluate.cases', "case = t1\ninput=\noutput= Hello\n");
        $this->assertTrue($added);
        foreach ($this->students as $user) {
            $this->setUser($user);
            if ( $this->vpldefault->is_submit_able() ) {
                try {
                    $this->internal_test_vpl_webservice_save($id, $files, $password);
                    mod_vpl_webservice::evaluate($id, $password);
                    sleep(2);
                    mod_vpl_webservice::get_result($id, $password);
                } catch (Exception $e) {
                    throw new \Exception("Evaluation " . $e);
                }
            }
        }
        $this->assertIsObject(mod_vpl_webservice::get_result_parameters());
        $this->assertIsObject(mod_vpl_webservice::get_result_returns());
    }

    /**
     * Description of test_vpl_webservice_get_result_exeptions
     * @runInSeparateProcess
     */
    public function test_vpl_webservice_get_result_exeptions() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vpl/externallib.php');
        if ( ! vpl_get_webservice_available()) {
            $this->markTestSkipped('VPL web service not tested: Web service not available.');
        }
        $ok = false;
        $this->setUser($this->editingteachers[0]);

        $parms = ['name' => 'Test activity'];
        $activity = $this->create_instance($parms);

        $this->setUser($this->students[0]);
        $cid = $activity->get_course_module()->id;
        $instance = $activity->get_instance();
        $instance->instance = $instance->id; // Adds attibute required for vpl_update_instance
        // No submission.
        try {
            mod_vpl_webservice::get_result($activity->get_course_module()->id, '');
            $this->fail('Exception expected no submission');
        } catch (Exception $e) {
            $ok = $e;
        }

        $files = [['name' => 'a.c', 'data' => "//\n"]];
        mod_vpl_webservice::save($cid, $files, '');

        // The initial_checks if request come from IP in required IP/Network.
        $changes = ['requirednet' => '1.1.1.1', 'evaluate' => 1];
        $this->change_activity($instance, $this->editingteachers[0], $this->students[0], $changes);
        try {
            mod_vpl_webservice::get_result($cid, '');
            $this->fail('Exception expected netrequired');
        } catch (Exception $e) {
            $ok = $e;
        }

        $changes = ['requirednet' => '', 'password' => 'password'];
        $this->change_activity($instance, $this->editingteachers[0], $this->students[0], $changes);
        // The initial_checks if request come from IP in required IP/Network.
        try {
            mod_vpl_webservice::get_result($cid, 'bad password');
            $this->fail('Exception expected if bad password');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The info if not is_visible().
        $parms = ['name' => 'Not visible', 'evaluate' => 1];
        $notvisible = $this->create_instance($parms);
        set_coursemodule_visible($notvisible->get_course_module()->id, false );
        get_fast_modinfo($notvisible->get_course_module()->course, 0, true);
        try {
            mod_vpl_webservice::get_result($notvisible->get_course_module()->id, '');
            $this->fail('Exception expected if not visible');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The info if not evaluable.
        $changes = ['evaluate' => 0, 'password' => ''];
        $this->change_activity($instance, $this->editingteachers[0], $this->students[0], $changes);
        try {
            mod_vpl_webservice::get_result($cid, '');
            $this->fail('Exception expected if not evaluable');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The info if example.
        $changes = ['evaluate' => 1, 'example' => 1];
        $this->change_activity($instance, $this->editingteachers[0], $this->students[0], $changes);
        try {
            mod_vpl_webservice::get_result($cid, '');
            $this->fail('Exception expected if example');
        } catch (Exception $e) {
            $ok = $e;
        }
        // The info if closed.
        $changes = ['duedate' => 1, 'example' => 0];
        $this->change_activity($instance, $this->editingteachers[0], $this->students[0], $changes);
        try {
            mod_vpl_webservice::get_result($cid, '');
            $this->fail('Exception expected if closed');
        } catch (Exception $e) {
            $ok = $e;
        }
        $this->assertIsObject($ok);
        $this->setUser($this->editingteachers[0]);
        mod_vpl_webservice::save($notvisible->get_course_module()->id, $files, '');
        mod_vpl_webservice::evaluate($notvisible->get_course_module()->id, '');
        mod_vpl_webservice::get_result($notvisible->get_course_module()->id, '');
        mod_vpl_webservice::save($activity->get_course_module()->id, $files, '');
        $changes = ['duedate' => 0, 'evaluate' => 0];
        $this->change_activity($instance, $this->editingteachers[0], $this->editingteachers[0], $changes);
        mod_vpl_webservice::evaluate($activity->get_course_module()->id, '');
        mod_vpl_webservice::get_result($activity->get_course_module()->id, '');
        $changes = ['duedate' => 1, 'evaluate' => 1];
        $this->change_activity($instance, $this->editingteachers[0], $this->editingteachers[0], $changes);
        mod_vpl_webservice::save($activity->get_course_module()->id, $files, '');
        mod_vpl_webservice::evaluate($activity->get_course_module()->id, '');
        mod_vpl_webservice::get_result($activity->get_course_module()->id, '');
    }
}
