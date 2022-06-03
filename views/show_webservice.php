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

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../vpl.class.php');

global $OUTPUT;
require_login();

$id = required_param( 'id', PARAM_INT );
$vpl = new mod_vpl( $id );
$vpl->prepare_page( 'views/show_webservice.php', array (
        'id' => $id
) );
$vpl->require_capability( VPL_VIEW_CAPABILITY );
if (! $vpl->is_visible()) {
    \mod_vpl\event\vpl_security_webservice::log( $vpl );
    notice( get_string( 'notavailable' ) );
}
$vpl->print_header( get_string( 'createtokenforuser', 'core_webservice' ) );
$vpl->print_view_tabs( 'view.php' );
echo '<h1>' . get_string( 'webservice', 'core_webservice' ) . '</h1>';
echo '<h3>' . get_string( 'createtokenforuserdescription', 'core_webservice' ) . '</h3>';
$serviceurl = vpl_get_webservice_urlbase( $vpl );
echo $OUTPUT->box( '<div style="white-space: pre-wrap">' . s( $serviceurl ) . '</div>' );
notice( '', vpl_mod_href( 'view.php', 'id', $id ) );
$vpl->print_footer();
