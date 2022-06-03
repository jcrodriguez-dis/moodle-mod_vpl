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
 * Setjails form
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
global $CFG;
require_once($CFG->libdir.'/formslib.php');

class mod_vpl_setjails_form extends moodleform {
    protected function definition() {
        $mform = & $this->_form;
        $mform->addElement( 'header', 'headersetjails', get_string( 'local_jail_servers', VPL ) );
        $mform->addElement( 'hidden', 'id', required_param( 'id', PARAM_INT ) );
        $mform->setType( 'id', PARAM_INT );
        $mform->addElement( 'textarea', 'jailservers', get_string( 'jail_servers_description', VPL ), array (
                'cols' => 45,
                'rows' => 10,
                'wrap' => 'off'
        ) );
        $mform->setType( 'jailservers', PARAM_RAW );
        $this->add_action_buttons();
    }
}

require_login();

$id = required_param( 'id', PARAM_INT );
$vpl = new mod_vpl( $id );
$vpl->prepare_page( 'forms/local_jail_servers.php', array ( 'id' => $id ) );
vpl_include_jsfile( 'hideshow.js' );
$vpl->require_capability( VPL_SETJAILS_CAPABILITY );
$vpl->print_header( get_string( 'local_jail_servers', VPL ) );
$vpl->print_heading_with_help( 'local_jail_servers' );

$mform = new mod_vpl_setjails_form( 'local_jail_servers.php' );
// Display page.

if (! $mform->is_cancelled() && $fromform = $mform->get_data()) {
    if (isset( $fromform->jailservers )) {
        \mod_vpl\event\vpl_execution_localjails_updated::log( $vpl );
        $instance = $vpl->get_instance();
        $instance->jailservers = $fromform->jailservers;
        if ( $vpl->update() ) {
            vpl_notice( get_string( 'saved', VPL ) );
        } else {
            vpl_notice( get_string( 'optionsnotsaved', VPL ), 'error' );
        }
    } else {
        vpl_notice( get_string( 'optionsnotsaved', VPL ), 'error' );
    }
}
$data = new stdClass();
$data->id = $id;
$data->jailservers = $vpl->get_instance()->jailservers;
$mform->set_data( $data );
\mod_vpl\event\vpl_execution_localjails_viewed::log( $vpl );
$mform->display();
$vpl->print_footer();
