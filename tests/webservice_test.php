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
    protected function setUp() {
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
                'functionname' =>$fn) );
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

    public function test_vpl_webservice_info() {
        foreach ($this->vpls as $vpl) {
            $instance = $vpl->get_instance();
            if ($instance->requirednet > '') {
                try {
                    $res = mod_vpl_webservice::info($vpl->get_course_module()->id, $instance->password);
                    $this->fail('Exception expected');
                }
                catch(Exception $e) {
                    $this->assertContains('machine', $e->getMessage());
                }
            } else {
                $res = mod_vpl_webservice::info($vpl->get_course_module()->id, $instance->password);
                $this->assertEquals($instance->name, $res['name']);
                $rqfiles = $vpl->get_required_fgm();
                $this->assertEquals($rqfiles->getallfiles(), $res['reqfiles']);
            }
        }
        global $DB;
        $DB->update_record(VPL, array('id' => $this->vpldefault->get_instance()->id, 'password' => 'key'));
        try {
            $res = mod_vpl_webservice::info($this->vpldefault->get_course_module()->id, 'bobería');
            $this->fail('Exception expected');
        }
        catch(Exception $e) {
            $this->assertContains('password', $e->getMessage());
        }
    }
    private function internal_test_vpl_webservice_open($id, $files = array(), $compilation ='', $evaluation = '', $grade ='', $password = '') {
        $res = mod_vpl_webservice::open($id, $password);
        $this->assertEquals($files, $res['files']);
        $this->assertEquals($compilation, $res['compilation']);
        $this->assertEquals($evaluation, $res['evaluation']);
        $this->assertEquals($grade, $res['grade']);
    }
    public function test_vpl_webservice_open() {
        $id = $this->vpldefault>-get_course_module()->id;
        foreach ( $this->users as $user) {
            $this->setUser($user);
            internal_test_vpl_webservice_open($id);
        }
        $id = $this->vplonefile>-get_course_module()->id;
        foreach ( $this->users as $user) {
            $this->setUser($user);
            if ($user == $this->students[0]) {
                internal_test_vpl_webservice_open($id, array('a.c' => "int main(){\nprintf(\"Hola\");\n}"));
            } else {
                internal_test_vpl_webservice_open($id);
            }
        }
    }
    public function test_vpl_webservice_save() {
    }
    public function test_vpl_webservice_evaluate() {
    }


/*
echo s( vpl_get_webservice_token( $vpl ) );
$serviceurl = vpl_get_webservice_urlbase( $vpl );

$res = vpl_call_service( $serviceurl, 'mod_vpl_info' );
vpl_call_print( $res );
echo '<h3>Get last submission</h3>';
$res = vpl_call_service( $serviceurl, 'mod_vpl_open' );
vpl_call_print( $res );
echo '<h3>Modify and save last submission</h3>';
if (isset( $res->files )) {
    $files = $res->files;
}
if (count( $files ) == 0) {
    $file = new stdClass();
    $file->name = 'test.c';
    $file->data = 'int main(){printf("hello");}';
    $files = array (
            $file
    );
} else {
    foreach ($files as $file) {
        $file->data = "Modification " . time() . "\n" . $file->data;
    }
}
$res->files = $files;
$body = '';
foreach ($files as $key => $file) {
    if ($key > 0) {
        $body .= '&';
    }
    $body .= "files[$key][name]=" . urlencode( $file->name ) . '&';
    $body .= "files[$key][data]=" . urlencode( $file->data );
}

$newres = vpl_call_service( $serviceurl, 'mod_vpl_save', $body );
vpl_call_print( $newres );
echo '<h3>Reread file to test saved files</h3>';
$newres = vpl_call_service( $serviceurl, 'mod_vpl_open' );
if (! isset( $res->files ) or ! isset( $newres->files ) or $res->files != $newres->files) {
    echo "Error";
} else {
    echo "OK";
}
vpl_call_print( $newres );

echo '<h3>Call evaluate (unreliable test)</h3>';
echo '<h4>It may be unavailable</h4>';
echo '<h4>The client don\'t use websocket then the jail server may timeout</h4>';
$res = vpl_call_service( $serviceurl, 'mod_vpl_evaluate' );
vpl_call_print( $res );
sleep( 5 );
echo '<h3>Call get result of last evaluation (unreliable test)</h3>';
$res = vpl_call_service( $serviceurl, 'mod_vpl_get_result' );
vpl_call_print( $res );
*/
}