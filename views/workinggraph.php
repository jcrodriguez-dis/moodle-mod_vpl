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
 * Graph working time for a vpl instance and/or a user
 *
 * @package        mod_vpl
 * @copyright    2012 Juan Carlos Rodríguez-del-Pino
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author        Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname( __FILE__ ) . '/../../../config.php');
require_once(dirname( __FILE__ ) . '/vpl_graph.class.php');
require_once(dirname( __FILE__ ) . '/../locallib.php');
require_once(dirname( __FILE__ ) . '/../vpl.class.php');
require_once(dirname( __FILE__ ) . '/../vpl_submission.class.php');

function vpl_get_working_periods($vpl, $userid) {
    $submissionslist = $vpl->user_submissions( $userid );
    if (count( $submissionslist ) == 0) {
        return array ();
    }
    $submissionslist = array_reverse( $submissionslist );
    $workperiods = array ();
    if ($submissionslist) {
        $lastsavetime = 0;
        $resttime = 20 * 60; // Rest period before next work 20 minutes.
        $firstwork = 10 * 59; // Work before first save 10 minutes.
        $intervals = - 1;
        $workstart = 0;
        foreach ($submissionslist as $submission) {
            /* Start new work period */
            if ($submission->datesubmitted - $lastsavetime >= $resttime) {
                if ($workstart > 0) { // Is not the first submission.
                    if ($intervals > 0) { // First work as average.
                        $firstwork = ( float ) ($lastsavetime - $workstart) / $intervals;
                    }
                    // Else use the last $firstwork.
                    $workperiods [] = ($lastsavetime - $workstart + $firstwork) / (3600.0);
                }
                $workstart = $submission->datesubmitted;
                $intervals = 0;
            } else { // Count interval.
                $intervals ++;
            }
            $lastsavetime = $submission->datesubmitted;
        }
        if ($intervals > 0) { // First work as average.
            $firstwork = ( float ) ($lastsavetime - $workstart) / $intervals;
        } // Else use the last $firstwork.
        $workperiods [] = ($lastsavetime - $workstart + $firstwork) / (3600.0);
    }
    return $workperiods;
}

require_login();

$id = required_param( 'id', PARAM_INT );
$userid = optional_param( 'userid', - 1, PARAM_INT );
$type = optional_param( 'type', 0, PARAM_INT );
$vpl = new mod_vpl( $id );
$course = $vpl->get_course();
$vpl->require_capability( VPL_GRADE_CAPABILITY );
// No log.
if ($userid < 0) {
    $cm = $vpl->get_course_module();
    $currentgroup = groups_get_activity_group( $cm );
    if (! $currentgroup) {
        $currentgroup = '';
    }
    $list = $vpl->get_students( $currentgroup );
    $submissions = $vpl->all_last_user_submission();
    // Get all information.
    $alldata = array ();
    foreach ($list as $userinfo) {
        if ($vpl->is_group_activity() && $userinfo->id != $vpl->get_group_leaderid( $userinfo->id )) {
            continue;
        }
        $workingperiods = vpl_get_working_periods( $vpl, $userinfo->id );
        if (count( $workingperiods ) > 0) {
            $alldata [] = $workingperiods;
        }
    }
    session_write_close();
    // For every student, total time, number of period.
    $totaltime = 0;
    $maxstudenttime = 0;
    $maxperiodtime = 0;
    $totalperiods = 0;
    $times = array ();
    foreach ($alldata as $workingperiods) {
        $totalperiods += count( $workingperiods );
        $time = 0;
        foreach ($workingperiods as $period) {
            $time += $period;
            $maxperiodtime = max( $maxperiodtime, $period );
        }
        $totaltime += $time;
        $maxstudenttime = max( $maxstudenttime, $time );
        $times [] = $time;
    }
    if ($maxstudenttime <= 3) {
        $timeslice = 0.25;
        $xformat = "%3.2f-%3.2f";
    } else if ($maxstudenttime <= 6) {
        $timeslice = 0.50;
        $xformat = "%3.1f-%3.1f";
    } else {
        $timeslice = 1;
        $xformat = "%3.0f-%3.0f";
    }
    $ydata = array ();
    $xdata = array ();
    for ($slice = 0; $slice <= $maxstudenttime; $slice += $timeslice) {
        $ydata [] = 0;
        $xdata [] = sprintf( $xformat, $slice, ($slice + $timeslice) );
    }
    foreach ($times as $time) {
        $ydata [( int ) ($time / $timeslice)] ++;
    }
    $title = $vpl->get_printable_name();
    $n = count( $times );
    $straveragetime = get_string( 'averagetime', VPL, sprintf( '%3.1f', (( float ) $totaltime / $n) ) );
    $straverageperiods = get_string( 'averageperiods', VPL, sprintf( '%3.1f', (( float ) $totalperiods / $n) ) );
    $strvmaximumperiod = get_string( 'maximumperiod', VPL, sprintf( '%3.1f', (( float ) $maxperiodtime) ) );
    $xtitle = sprintf( '%s - %s - %s - %s', get_string( 'hours' ), $straveragetime, $straverageperiods, $strvmaximumperiod );
    $ytitle = get_string( 'defaultcoursestudents' );
    vpl_graph::draw( $title, $xtitle, $ytitle, $xdata, $ydata, null, true );
} else {
    $ydata = vpl_get_working_periods( $vpl, $userid );
    session_write_close();
    $xdata = array ();
    $hours = 0.0;
    for ($i = 0; $i < count( $ydata ); $i ++) {
        $xdata [] = $i + 1;
        $hours += $ydata [$i];
    }
    $user = $DB->get_record( 'user', array (
            'id' => $userid
    ) );
    $title = sprintf( "%s - %s", $vpl->fullname( $user, false ), get_string( 'numhours', '', sprintf( '%3.2f', $hours ) ) );
    $titlex = get_string( 'workingperiods', VPL ) . ' - ' . $vpl->get_printable_name();
    vpl_graph::draw( $title, $titlex, get_string( 'hours' ), $xdata, $ydata, null, true );
}
