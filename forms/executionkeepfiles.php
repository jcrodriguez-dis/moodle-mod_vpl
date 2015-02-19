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
 * Form to set files to keep during execution
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
require_once dirname( __FILE__ ) . '/../../../config.php';
require_once dirname( __FILE__ ) . '/../locallib.php';
require_once dirname( __FILE__ ) . '/../vpl.class.php';
require_once $CFG->libdir . '/formslib.php';
class mod_vpl_executionkeepfiles_form extends moodleform {
    protected $fgp;
    function __construct($page, $fgp) {
        $this->fgp = $fgp;
        parent::__construct( $page );
    }
    function definition() {
        $mform = & $this->_form;
        $mform->addElement( 'hidden', 'id', required_param( 'id', PARAM_INT ) );
        $mform->setType( 'id', PARAM_INT );
        $mform->addElement( 'header', 'header_keepfiles', get_string( 'keepfiles', VPL ) );
        $list = $this->fgp->getFileList();
        $keeplist = $this->fgp->getFileKeepList();
        $num = 0;
        foreach ($list as $filename) {
            $mform->addElement( 'checkbox', 'keepfile' . $num, $filename );
            $mform->setDefault( 'keepfile' . $num, in_array( $filename, $keeplist ) );
            $num ++;
        }
        $mform->addElement( 'submit', 'savekeepfiles', get_string( 'saveoptions', VPL ) );
    }
}

require_login();

$id = required_param( 'id', PARAM_INT );
$vpl = new mod_vpl( $id );
$vpl->prepare_page( 'forms/executionkeepfiles.php', array (
        'id' => $id
) );
vpl_include_jsfile( 'hideshow.js' );
$vpl->require_capability( VPL_MANAGE_CAPABILITY );
//Display page
$vpl->print_header( get_string( 'execution', VPL ) );
$vpl->print_heading_with_help( 'keepfiles' );
$vpl->print_configure_tabs( basename( __FILE__ ) );
$course = $vpl->get_course();
$fgp = $vpl->get_execution_fgm();
$mform = new mod_vpl_executionkeepfiles_form( 'executionkeepfiles.php', $fgp );
if ($fromform = $mform->get_data()) {
    if (isset( $fromform->savekeepfiles )) {
        $list = $fgp->getFileList();
        $nlist = count( $list );
        $keeplist = array ();
        for($i = 0; $i < $nlist; $i ++) {
            $name = 'keepfile' . $i;
            if (isset( $fromform->$name )) {
                $keeplist [] = $list [$i];
            }
        }
        $fgp->setFileKeepList( $keeplist );
        \mod_vpl\event\vpl_execution_keeplist_updated::log( $vpl );
        vpl_notice( get_string( 'optionssaved', VPL ) );
    }
}
\mod_vpl\event\vpl_execution_keeplist_viewed::log( $vpl );
$mform->display();
$vpl->print_footer();
