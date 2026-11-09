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
 * Processes AJAX requests from IDE
 *
 * @package mod_vpl
 * @copyright 2017 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

define('AJAX_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
use mod_vpl\util\userpreferences;

$result = (object)[];
$result->success = false;
$result->error = '';
$result->preferences = (object)[];
try {
    require_login();
    $rawdata = file_get_contents("php://input");
    $data = json_decode($rawdata, null, 512, JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_object($data)) {
        throw new \Exception('Invalid input data');
    }
    if (!confirm_sesskey(isset($data->sesskey) ? $data->sesskey : '')) {
        throw new \moodle_exception('invalidsesskey');
    }
    $preferences = userpreferences::update($data);
    $result->preferences = $preferences;
    $result->success = true;
} catch (\Throwable $e) {
    $result->error = $e->getMessage();
}
echo json_encode($result);
die();
