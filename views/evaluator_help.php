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
 * Show URL to web service with token
 *
 * @package mod_vpl
 * @copyright 2025 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../vpl.class.php');
require_once(dirname(__FILE__) . '/../locallib.php');

global $PAGE, $OUTPUT;
require_login();

$id = required_param('id', PARAM_INT);
$evaluatorname = required_param('evaluator', PARAM_ALPHANUMEXT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('views/evaluator_help.php', [ 'id' => $id, 'evaluator' => $evaluatorname ]);
$vpl->print_header(get_string('help'));
$vpl->require_capability(VPL_MANAGE_CAPABILITY);
try {
    $evaluator = \mod_vpl\plugininfo\vplevaluator::get_evaluator($evaluatorname);
    echo $evaluator->get_printable_help($vpl, false);
} catch (\moodle_exception $e) {
    echo $OUTPUT->notification(get_string('error:invalidevaluator', VPL, $evaluatorname), 'notifyproblem');
}
$vpl->print_footer();
