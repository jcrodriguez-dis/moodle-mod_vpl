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
 * Check status of jail servers
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../vpl.class.php');
require_once(dirname(__FILE__) . '/../jail/jailserver_manager.class.php');
require_once(dirname(__FILE__) . '/../jail/running_processes.class.php');

global $PAGE, $COURSE, $COURSE, $DB;

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'vpl');
require_login($course, true, $cm);
$vpl = new mod_vpl($id);
$vpl->prepare_page('views/checkjailservers.php', ['id' => $id]);

$vpl->require_capability(VPL_MANAGE_CAPABILITY);
// Display page.
$vpl->print_header(get_string('check_jail_servers', VPL));
$vpl->print_heading_with_help('check_jail_servers');

\mod_vpl\event\vpl_jail_servers_tested::log($vpl);
$serversinfo = vpl_jailserver_manager::check_servers($vpl);
$serverstable = new html_table();
$serverstable->head = [
        '#',
        get_string('server', VPL),
        get_string('currentstatus', VPL),
        get_string('lasterror', VPL),
        get_string('lasterrordate', VPL),
        get_string('totalnumberoferrors', VPL),
];
$serverstable->align = [
        'right',
        'left',
        'left',
        'left',
        'left',
        'right',
];
$havels = vpl_jailserver_manager::there_are_ls($serversinfo);
if ($havels) {
    $serverstable->head[] = 'LS';
    $serverstable->align[] = 'left';
}

$plugin = new stdClass();
require_once(dirname(__FILE__) . '/../version.php');
$pluginversion = $plugin->version;

$serverstable->data = [];
$num = 0;
foreach ($serversinfo as $info) {
    $serverurl = vpl_jailserver_manager::get_server_short_name_html($info->server);
    $serverissues = vpl_jailserver_manager::get_server_issues($info->server);
    if (count($serverissues) > 0) {
        $serverurl .= '<br><div class="vpl_server_warning">' . implode("<br>\n", $serverissues) . '</div>';
    }
    $num++;
    if ($info->offline) {
        $status = '<div class="vpl_server_failed">' . $info->current_status . '</div>';
    } else {
        $status = $info->current_status;
    }
    $row = [
            $num,
            $serverurl,
            $status,
            $info->laststrerror,
            $info->lastfail > 0 ? userdate($info->lastfail) : '',
            $info->nfails,
    ];
    if ($havels) {
        $row[] = implode(', ', $info->ls);
    }
    $serverstable->data[] = $row;
}
echo html_writer::table($serverstable);
// Button to check running tasks.
echo html_writer::link(
    new moodle_url('/mod/vpl/views/checkrunningtasks.php', ['id' => $id]),
    get_string('check_running_tasks', VPL),
    ['class' => 'btn btn-primary']
);
$vpl->print_footer();
