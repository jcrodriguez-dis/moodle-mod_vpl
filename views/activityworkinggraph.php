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
 * Show activity working graph for all students
 *
 * @package VPL. Grade submission
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_login();
$id = required_param( 'id', PARAM_INT );
$vpl = new mod_vpl( $id );
$vpl->prepare_page( 'views/activityworkinggraph.php', array (
        'id' => $id
) );

$course = $vpl->get_course();
$instance = $vpl->get_instance();
$vpl->require_capability( VPL_GRADE_CAPABILITY );

$vpl->print_header_simple();
echo '<div class="clearer"> </div>';
echo '<div style="text-align: center">';
echo '<img src="' . vpl_rel_url( 'workinggraph.php', 'id', $id, 'userid', - 1 ) . '" alt="Working periods" />';
echo '</div>';
$vpl->print_footer_simple();
