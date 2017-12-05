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
 * Graph submissions statistics for a vpl instance and a user
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname( __FILE__ ) . '/../../../config.php');
require_once(dirname(__FILE__).'/vpl_graph.class.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once(dirname(__FILE__).'/../vpl_submission.class.php');

require_login();

$id = required_param( 'id', PARAM_INT );
$userid = required_param( 'userid', PARAM_INT );
$type = optional_param( 'type', 0, PARAM_INT );
$vpl = new mod_vpl( $id );
$course = $vpl->get_course();
$vpl->require_capability( VPL_GRADE_CAPABILITY );
// No log.
$subsn = array ();
$series = array ();
$names = array ();
$submissionslist = $vpl->user_submissions( $userid );
if (count( $submissionslist ) > 0) {
    $submissionslist = array_reverse( $submissionslist );
    // Create submissions object.
    $subs = array ();
    foreach ($submissionslist as $submission) {
        $subs [] = new mod_vpl_submission( $vpl, $submission );
    }
    foreach ($subs as $sub) {
        $filesarray = $sub->get_submitted_fgm()->getfilelist();
        foreach ($filesarray as $name) {
            if (! in_array( $name, $names, true )) {
                $names [] = $name;
                $series [$name] = array ();
            }
        }
    }
    // Initial value.
    $subsn [] = 0;
    foreach ($names as $name) {
        $series [$name] [] = 0;
    }
    $subshowl = ( int ) (count( $subs ) / 20);
    if ($subshowl < 1) {
        $subshow = 1;
    } else {
        $subshow = 5;
        while ( true ) {
            if ($subshow >= $subshowl) {
                break;
            }
            $subshow *= 2;
            if ($subshow >= $subshowl) {
                break;
            }
            $subshow = ( int ) (2.5 * $subshow);
            if ($subshow >= $subshowl) {
                break;
            }
            $subshow *= 2;
            if ($subshow >= $subshowl) {
                break;
            }
        }
    }
    $nsub = 1;
    foreach ($subs as $sub) {
        $subsn [] = $nsub % $subshow == 0 ? $nsub : '';
        $nsub ++;
        $filesarray = $sub->get_submitted_files();
        $files = array ();
        // Used to give stack format last bar has less size.
        $totalsize = 0;
        foreach ($filesarray as $name => $data) {
            $size = strlen( $data );
            $files [$name] = $size;
            $totalsize += $size;
        }
        foreach ($names as $name) {
            if (isset( $files [$name] )) {
                $series [$name] [] = $totalsize;
                $totalsize -= $files [$name];
            } else {
                $series [$name] [] = $totalsize;
            }
        }
    }
}
$user = $DB->get_record( 'user', array (
        'id' => $userid
) );
vpl_graph::draw( $vpl->get_printable_name() . ' - ' . $vpl->fullname( $user, false )
               , get_string( 'submissions', VPL ) , get_string( "sizeb" ), $subsn, $series, $names );
