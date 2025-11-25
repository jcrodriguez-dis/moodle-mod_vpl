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
 * Backend script to get grading help information
 *
 * @package mod_vpl
 * @copyright 2025 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

/**
 * Get grading information help
 * @param mod_vpl $vpl VPL instance
 * @return string grade comments summary in html format
 */
function get_grading_help($vpl) {
    global $OUTPUT;
    $list = [];
    $submissions = $vpl->all_last_user_submission();
    foreach ($submissions as $submission) {
        $sub = new mod_vpl_submission($vpl, $submission);
        $sub->filter_feedback($list);
    }
    $all = [];
    foreach ($list as $text => $info) {
        $astext = s(addslashes_js($text));
        $html = '';
        $html .= s($text);
        foreach (array_keys($info->grades) as $grade) {
            if ($grade >= 0) { // No grade.
                $jscript = 'window.VPL.addComment(\'' . $astext . '\')';
            } else {
                $jscript = 'VPL.addComment(\'' . $astext . ' (' . $grade . ')\')';
            }
            $link = '<a href="javascript:void(0)" onclick="' . $jscript . '">' . $grade . '</a>';
            $html .= ' (' . $link . ')';
        }
        $html .= '<br>';
        if (isset($all[$info->count])) {
            $all[$info->count] .= '(' . $info->count . ') ' . $html;
        } else {
            $all[$info->count] = '(' . $info->count . ') ' . $html;
        }
    }
    // Sort comments by number of occurrences.
    krsort($all);

    $html = $OUTPUT->box_start();
    $html .= '<b>' . get_string('listofcomments', VPL) . '</b><hr />';
    foreach ($all as $info) {
        $html .= $info;
    }
    $html .= $OUTPUT->box_end();
    return $html;
}

global $PAGE, $OUTPUT;

$result = new stdClass();
$result->success = false;
$result->response = "";
$result->error = '';
try {
    require_once(dirname(__FILE__) . '/../vpl.class.php');
    require_once(dirname(__FILE__) . '/../vpl_submission.class.php');
    if (! isloggedin()) {
        throw new Exception(get_string('loggedinnot'));
    }
    $id = required_param('id', PARAM_INT); // Course module id.
    $vpl = new mod_vpl($id);
    require_login($vpl->get_course(), false);

    $PAGE->set_url(new moodle_url('/mod/vpl/forms/grading_help.php', ['id' => $id]));
    echo $OUTPUT->header(); // Send headers.
    $result->response = get_grading_help($vpl);
    $result->success = true;
} catch (\Throwable $e) {
    $result->success = false;
    $result->error = $e->getMessage();
}

echo json_encode($result);
die();
