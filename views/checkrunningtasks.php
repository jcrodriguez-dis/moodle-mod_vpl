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

define('NO_OUTPUT_BUFFERING', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../vpl.class.php');
require_once(dirname(__FILE__) . '/../jail/jailserver_manager.class.php');
require_once(dirname(__FILE__) . '/../jail/running_processes.class.php');

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
$nprocesses = count($processes);
$progressbar = new \mod_vpl\util\progress_bar(get_string('check_running_tasks', VPL), 0, $nprocesses);
foreach ($processes as $process) {
    $progressbar->set_value($num);
    $running = vpl_running_processes::is_running($process);
    $status = '';
    if ($running) {
        $status = get_string('running', VPL);
    }
    $serverurl = vpl_jailserver_manager::get_server_short_name_html($process->server);
    $num++;
    $vpl = new mod_vpl(false, $process->vpl);
    $user = $DB->get_record('user', ['id' => $process->userid]);
    $taskstable->data[] = [
            $num,
            $vpl->fullname($user),
            html_writer::link(
                new moodle_url('/mod/vpl/forms/submissionview.php', [
                    'id' => $vpl->get_course_module()->id,
                    'userid' => $process->userid,
                ]),
                $vpl->get_printable_name()
            ),
            $serverurl,
            userdate($process->start_time),
            $status,
    ];
}

$progressbar->set_value($nprocesses);
$progressbar->hide();

echo html_writer::table($taskstable);

$vpl->print_footer();
