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
 * Page to test JavaScript with BeHat
 *
 * @package mod_vpl.
 * @copyright 2021 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname( __FILE__ ) . '/../vpl.class.php');
$PAGE->requires->js_call_amd('mod_vpl/jstests-lazy', 'start');
$course = $DB->get_record( "course", array ( 'id' => $COURSE->id ) );

require_course_login( $course );

$title = 'VPL JavaScript Tests';
$PAGE->set_url('/mod/vpl/tests/test_javascript.php');
$PAGE->set_title( $title );
$PAGE->set_heading( $title );
echo $OUTPUT->header();
echo "<div id='test_results'></div>";
echo $OUTPUT->footer();
