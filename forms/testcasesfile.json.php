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
 * Process submission edit
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

define( 'AJAX_SCRIPT', true );

require(__DIR__ . '/../../../config.php');

global $OUTPUT, $PAGE;
$result = new stdClass();
$result->success = true;
$result->response = new stdClass();
$result->error = '';
try {
    require_once(dirname( __FILE__ ) . '/../locallib.php');
    require_once(dirname( __FILE__ ) . '/../vpl.class.php');
    require_once(dirname( __FILE__ ) . '/edit.class.php');
    if (! isloggedin()) {
        throw new Exception( get_string( 'loggedinnot' ) );
    }
    $id = required_param( 'id', PARAM_INT ); // Course id.
    $action = required_param( 'action', PARAM_ALPHANUMEXT );
    $vpl = new mod_vpl( $id );
    // TODO use or not sesskey "require_sesskey();".
    require_login( $vpl->get_course(), false );
    $vpl->require_capability( VPL_MANAGE_CAPABILITY );
    $PAGE->set_url( new moodle_url( '/mod/vpl/forms/testcasesfile.json.php', [
            'id' => $id,
            'action' => $action,
    ] ) );
    echo $OUTPUT->header(); // Send headers.
    $actiondata = json_decode(file_get_contents( 'php://input' ), null, 512, JSON_INVALID_UTF8_SUBSTITUTE);
    switch ($action) {
        case 'save' :
            $postfiles = mod_vpl_edit::filesfromide($actiondata->files);
            $fgm = $vpl->get_execution_fgm();
            $result->response->requestsconfirmation = false;
            $oldversion = $fgm->getversion();
            if ($actiondata->version != 0 && $actiondata->version != $oldversion) {
                $result->response->question = get_string('replacenewer', VPL);
                $result->response->requestsconfirmation = true;
                $result->response->version = $oldversion;
                break;
            }
            foreach ($postfiles as $filename => $content) {
                if (!vpl_is_valid_path_name($filename)) {
                    throw new Exception( get_string( 'error:invalidfile', VPL, $filename ) );
                }
                if ($content > '') {
                    $fgm->addFile( $filename, $content);
                }
            }
            $result->response->version = $fgm->getversion();
            $vpl->update();
            break;
        case 'load' :
            $result->response = mod_vpl_edit::load($vpl, $USER->id);
            $testfiles = ['vpl_evaluate.cases' => ''];
            $evaluatorname = $vpl->get_effective_setting('evaluator');
            if ($evaluatorname) {
                try {
                    $evaluator = \mod_vpl\plugininfo\vplevaluator::get_evaluator($evaluatorname);
                    $testfiles = $evaluator->get_test_files();
                } catch (Exception $e) {
                    $testfiles = [];
                }
            }
            $fgm = $vpl->get_execution_fgm();
            $files = [];
            foreach ($testfiles as $filename => $content) {
                $savedcontent = $fgm->getfiledata($filename);
                if ($savedcontent > '') {
                    $files[$filename] = $savedcontent;
                } else {
                    $files[$filename] = $content;
                }
            }
            $result->response->files = mod_vpl_edit::filestoide($files);
            $result->response->version = $fgm->getversion();
            break;
        default :
            throw new Exception( 'ajax action error: ' + $action );
    }
} catch (\Throwable $e ) {
    $result->success = false;
    $result->error = $e->getMessage();
}
echo json_encode( $result );
die();
