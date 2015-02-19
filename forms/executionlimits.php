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
 * Form to set execution limits
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname( __FILE__ ) . '/../../../config.php';
require_once dirname( __FILE__ ) . '/../locallib.php';
require_once dirname( __FILE__ ) . '/../vpl.class.php';
require_once $CFG->libdir . '/formslib.php';
class mod_vpl_executionlimits_form extends moodleform {
    protected $vpl;
    function __construct($page, $vpl) {
        $this->vpl = $vpl;
        parent::__construct( $page );
    }
    function definition() {
        global $CFG;
        $plugincfg = get_config( 'mod_vpl' );
        $mform = & $this->_form;
        $id = $this->vpl->get_course_module()->id;
        $instance = $this->vpl->get_instance();
        $mform->addElement( 'hidden', 'id', $id );
        $mform->setType( 'id', PARAM_INT );
        $mform->addElement( 'header', 'header_execution_limits', get_string( 'resourcelimits', VPL ) );
        $mform->addElement( 'select', 'maxexetime', get_string( 'maxexetime', VPL ), vpl_get_select_time( ( int ) $plugincfg->maxexetime ) );
        $mform->setType( 'maxexetime', PARAM_INT );
        if ($instance->maxexetime)
            $mform->setDefault( 'maxexetime', $instance->maxexetime );
        $mform->addElement( 'select', 'maxexememory', get_string( 'maxexememory', VPL ), vpl_get_select_sizes( 16 * 1024 * 1024, ( int ) $plugincfg->maxexememory ) );
        $mform->setType( 'maxexememory', PARAM_INT );
        if ($instance->maxexememory)
            $mform->setDefault( 'maxexememory', $instance->maxexememory );
        $mform->addElement( 'select', 'maxexefilesize', get_string( 'maxexefilesize', VPL ), vpl_get_select_sizes( 1024 * 256, ( int ) $plugincfg->maxexefilesize ) );
        $mform->setType( 'maxexefilesize', PARAM_INT );
        if ($instance->maxexefilesize)
            $mform->setDefault( 'maxexefilesize', $instance->maxexefilesize );
        $mform->addElement( 'text', 'maxexeprocesses', get_string( 'maxexeprocesses', VPL ) );
        $mform->setType( 'maxexeprocesses', PARAM_INT );
        if ($instance->maxexeprocesses)
            $mform->setDefault( 'maxexeprocesses', $instance->maxexeprocesses );
        $mform->addElement( 'submit', 'savelimitoptions', get_string( 'saveoptions', VPL ) );
    }
}

require_login();

$id = required_param( 'id', PARAM_INT );
$vpl = new mod_vpl( $id );
$vpl->prepare_page( 'forms/executionlimits.php', array (
        'id' => $id
) );
vpl_include_jsfile( 'hideshow.js' );
$vpl->require_capability( VPL_MANAGE_CAPABILITY );
//Display page
$vpl->print_header( get_string( 'execution', VPL ) );
$vpl->print_heading_with_help( 'resourcelimits' );
$vpl->print_configure_tabs( basename( __FILE__ ) );
$course = $vpl->get_course();
$fgp = $vpl->get_execution_fgm();
$mform = new mod_vpl_executionlimits_form( 'executionlimits.php', $vpl );
if ($fromform = $mform->get_data()) {
    if (isset( $fromform->savelimitoptions )) {
        $instance = $vpl->get_instance();
        \mod_vpl\event\vpl_execution_limits_updated::log( $vpl );
        $instance->maxexetime = $fromform->maxexetime;
        $instance->maxexememory = $fromform->maxexememory;
        $instance->maxexefilesize = $fromform->maxexefilesize;
        $instance->maxexeprocesses = $fromform->maxexeprocesses;
        if ($DB->update_record( VPL, $instance )) {
            vpl_notice( get_string( 'optionssaved', VPL ) );
        } else {
            vpl_error( get_string( 'optionsnotsaved', VPL ) );
        }
    }
}
\mod_vpl\event\vpl_execution_limits_viewed::log( $vpl );
$mform->display();
$vpl->print_footer();
