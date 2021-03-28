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
 * @copyright  Juan Carlos Rodríguez-del-Pino
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
require_once($CFG->dirroot . '/mod/vpl/externallib.php');

/**
 * Unit tests for webservice.
 * @group mod_vpl
 */
class mod_vpl_webservice_testcase extends mod_vpl_base_testcase {
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
            return json_decode( $rawresponse );
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
        if ( ! vpl_get_webservice_available()) {
            $this->markTestSkipped('VPL web service not tested: Web service not available.');
        }
    }

    public function test_vpl_webservice_token() {
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
            $this->assertEquals($file['data'], $files[$file['name']]);
        }
    }
    public function test_vpl_webservice_info() {
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
        foreach ($this->users as $user) {
            $this->setUser($user);
            try {
                $res = mod_vpl_webservice::info($this->vplnotavailable->get_course_module()->id, 'bobería');
                $this->fail('Exception expected');
            } catch (Exception $e) {
                $this->assertFalse(strpos($e->getMessage(), 'password') === false);
            }
        }
    }

    private function internal_test_vpl_webservice_open($id, $files = array(),
            $compilation ='', $evaluation = '',
            $grade ='', $password = '') {
        $res = mod_vpl_webservice::open($id, $password);
        $this->internal_test_files($files, $res['files']);
        $this->assertEquals($compilation, $res['compilation']);
        $this->assertEquals($evaluation, $res['evaluation']);
        $this->assertEquals($grade, $res['grade']);
    }

    public function test_vpl_webservice_open() {
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
    }

    private function internal_test_vpl_webservice_save($id, $files = array(), $password = '') {
        $filesarray = array();
        foreach ($files as $name => $data) {
            $filesarray[] = array('name' => $name, 'data' => $data);
        }
        mod_vpl_webservice::save($id, $filesarray, $password);
        $this->internal_test_vpl_webservice_open($id, $files);
    }

    public function test_vpl_webservice_save() {
        $id = $this->vpldefault->get_course_module()->id;
        $files = array('a.c' => '#include <content.h>\n');
        $password = $this->vpldefault->get_instance()->password;
        foreach (array_merge($this->students, $this->teachers) as $user) {
            $this->setUser($user);
            if ( $this->vpldefault->is_submit_able() ) {
                try {
                    $this->internal_test_vpl_webservice_save($id, $files, $password);
                } catch (Exception $e) {
                    throw new Exception("Saving submission default vpl users " . $e);
                }
            }
        }
    }
}