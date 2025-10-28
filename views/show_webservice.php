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
 * @copyright 2014 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../vpl.class.php');
require_once(dirname(__FILE__) . '/../locallib.php');

global $PAGE, $OUTPUT;
require_login();

$id = required_param('id', PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('views/show_webservice.php', [ 'id' => $id ]);
$vpl->require_capability(VPL_VIEW_CAPABILITY);
$vpl->restrictions_check();
if (! $vpl->is_visible()) {
    notice(get_string('notavailable'));
}
\mod_vpl\event\vpl_security_webservice::log($vpl);
$PAGE->requires->css(new moodle_url('/mod/vpl/css/webservice.css'));
$vpl->print_header(get_string('webservice', VPL));
$vpl->print_view_tabs('view.php');
echo $OUTPUT->heading_with_help($vpl->get_printable_name() . ' - ' . get_string('webservice', VPL), 'webservice', VPL, '', '', 1);
echo $OUTPUT->box_start('mb-3');

// VPL ID info.
vpl_print_copyable_info(get_string('webservicevplid', VPL), $id);

$wsmanager = new \mod_vpl\webservice\manager($vpl);

echo $OUTPUT->heading_with_help(get_string('webserviceglobal', VPL), 'webserviceglobal', VPL, '', '', 4, 'mt-2');

if (\core\session\manager::is_loggedinas()) {
    // Do not display token info if login as, to prevent access to personal info outside the course.
    echo html_writer::div(get_string('webserviceloginasnotice', VPL));
} else {
    $wsmanager->print_webservice(\mod_vpl\webservice\manager::GLOBAL);
}
echo $OUTPUT->heading_with_help(get_string('webservicelocal', VPL), 'webservicelocal', VPL, '', '', 4, 'mt-2');
$wsmanager->print_webservice(\mod_vpl\webservice\manager::LOCAL);

echo $OUTPUT->box_end();
echo $OUTPUT->continue_button(vpl_mod_href('view.php', 'id', $id));
$vpl->print_footer();
