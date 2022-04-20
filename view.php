<?php
// This file is part of VPL - http://vpl.dis.ulpgc.es/
//
// VPL is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// VPL is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with VPL.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   VPL. Show a VPL instance
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/vpl.class.php');

use mod_vpl\output\view;

global $USER, $PAGE;

require_login();
$id = optional_param( 'id', null, PARAM_INT ); // Course Module ID.
$vpl = new mod_vpl( $id );
$urlparms = array ( 'id' => $id );
if ( optional_param( 'userid', -1, PARAM_INT ) != -1 ) {
    $urlparms['userid'] = required_param( 'userid', PARAM_INT );
}
$vpl->prepare_page( 'view.php', $urlparms );
$vpl->require_capability( VPL_VIEW_CAPABILITY );
$id = $vpl->get_course_module()->id;

if (! $vpl->is_visible()) {
    vpl_redirect( '?id=' . $id, get_string( 'notavailable' ) );
    die;
}
if (! $vpl->has_capability( VPL_MANAGE_CAPABILITY ) && ! $vpl->has_capability( VPL_GRADE_CAPABILITY )) {
    $vpl->restrictions_check();
    $userid = $USER->id;
} else {
    $userid = optional_param( 'userid', $USER->id, PARAM_INT );
}

\mod_vpl\event\vpl_description_viewed::log( $vpl );

// Print the page header.
$PAGE->requires->css( new moodle_url( '/mod/vpl/css/sh.css' ) );
$vpl->print_header( get_string( 'description', VPL ) );
$output = $PAGE->get_renderer('mod_vpl');

// Print the main part of the page.
echo $output->render(new view($vpl, $userid));

echo $output->footer();

vpl_sh_factory::syntaxhighlight();
