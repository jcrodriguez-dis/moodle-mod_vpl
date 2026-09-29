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
 * Check status of running tasks
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

/**
 * Removes path(jail security) from URL
 *
 * @param string $url URL to clean
 *
 * @return string URL without path
 */
function remove_path($url) {
    $path = parse_url($url, PHP_URL_PATH);
    if ($path > '/') {
        $lenpath = strlen($path);
        $url = substr_replace($url, '/*****', -$lenpath, $lenpath);
    }
    return $url;
}
global $PAGE, $COURSE, $COURSE, $DB;

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'vpl');
require_login($course, true, $cm);
$vpl = new mod_vpl($id);
$vpl->prepare_page('views/checkrunningtasks.php', ['id' => $id]);

$vpl->require_capability(VPL_MANAGE_CAPABILITY);

// Display page.
$vpl->print_header(get_string('check_running_tasks', VPL));
$vpl->print_heading_with_help('check_running_tasks');
\mod_vpl\event\vpl_running_tasks_checked::log($vpl);

$plugin = new stdClass();
require_once(dirname(__FILE__) . '/../version.php');
$pluginversion = $plugin->version;

$taskstable = new html_table();
$taskstable->head = [
        '#',
        get_string('user'),
        get_string('activity'),
        get_string('server', VPL),
        get_string('startingfrom'),
        get_string('status'),
];
$taskstable->align = [
        'right',
        'left',
        'left',
        'left',
        'left',
        'left',
];

$taskstable->data = [];
$num = 0;
$processes = vpl_running_processes::lanched_processes($COURSE->id);
foreach ($processes as $process) {
    $data = new stdClass();
    $data->adminticket = $process->adminticket;
    $data->pluginversion = $pluginversion;
    $request = vpl_jailserver_manager::get_action_request('running', $data);
    $error = '';
    $response = vpl_jailserver_manager::get_response($process->server, $request, $error);
    if ($response === false || ( isset($response['running']) && $response['running'] != 1)) {
        // Removes zombi tasks.
        vpl_running_processes::delete($process->userid, $process->vpl, $process->adminticket);
    }
    $status = '';
    if (isset($response['running']) && $response['running'] == 1) {
        $status = get_string('running', VPL);
    }
    $serverurl = remove_path($process->server);
    $num++;
    $vpl = new mod_vpl(false, $process->vpl);
    $user = $DB->get_record('user', [
            'id' => $process->userid,
    ]);
    $taskstable->data[] = [
            $num,
            $vpl->fullname($user),
            $vpl->get_printable_name(),
            $serverurl,
            userdate($process->start_time),
            $status,
    ];
}

echo html_writer::table($taskstable);

$vpl->print_footer();
