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
 * Downloading phase1 SEB configuration for a VPL instance.
 * Requires public token to validate the request.
 *
 * @package mod_vpl
 * @copyright 2026 Alejandro David Arzola Saavedra
 * @copyright 2026-8 Juan Carlos Rodríguez del Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Alejandro David Arzola Saavedra <alejandro.arzola101@alu.ulpgc.es>
 * @author Juan Carlos Rodríguez del Pino <jc.rodriguezdelpino@ulpgc.es>
 */

// This endpoint intentionally allows unauthenticated access.
// Access is authorised using a plugin-generated token that must be in the request.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../locallib.php');
require_once(__DIR__ . '/../vpl.class.php');

/**
 * Send a 400 Bad Request response and terminate the script.
 */
function vpl_bad_request($message = 'Bad Request') {
    global $CFG;
    header('HTTP/1.0 400 Bad Request');
    if ($CFG->debug >= DEBUG_DEVELOPER) {
        // Developer debugging is enabled.
        echo "<html><body><p>Bad request: $message</p></body></html>";
    } else {
        echo "<html><body><p>Bad request</p></body></html>";
    }
    die();
}

$vplid = optional_param('vplid', 0, PARAM_INT);
$token = optional_param('token', '', PARAM_ALPHANUMEXT);
if ($vplid === 0 || $token === '') {
    vpl_bad_request("Missing required parameters.");
}
try {
    $vpl = new mod_vpl(false, $vplid);
} catch (\moodle_exception $e) {
    // Downloading SEB config for a non-existing VPL instance.
    vpl_bad_request("VPL instance not found.");
}
if ($vpl->get_instance()->sebrequired != 2) {
    // No manual configuration download for this VPL instance.
    vpl_bad_request("Manual SEB configuration download is not allowed for this VPL instance.");
}
$session = \mod_vpl\seb\session_manager::get_session_by_public_token($token, $vplid);
if (empty($session)) {
    // No session found for this VPL instance and token.
    vpl_bad_request("No session found.");
}

$sebsettings = \mod_vpl\seb\settings::get_values_from_vplid($vplid);
$baduserifsessionenabled = $sebsettings->enablesebsession && $session->userid == 0;
$baduserifsessiondisabled = !$sebsettings->enablesebsession && $session->userid != 0;
if ($baduserifsessionenabled || $baduserifsessiondisabled) {
    vpl_bad_request("Bad user for the current session.");
}

$config = \mod_vpl\seb\session_manager::get_phase1_config_xml($sebsettings, $session);
\mod_vpl\seb\session_manager::send_seb_config($config);
