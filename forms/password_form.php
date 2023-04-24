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
 * Get password to access form
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined( 'MOODLE_INTERNAL' ) || die();

global $CFG;

require_once($CFG->libdir.'/formslib.php');
class mod_vpl_password_form extends moodleform {
    public function __construct($page, & $vpl) {
        $this->vpl = & $vpl;
        parent::__construct( $page );
    }
    protected function definition() {
        $mform = & $this->_form;
        $mform->addElement( 'header', 'headerpassword', get_string( 'requiredpassword', VPL ) );
        $mform->addElement( 'hidden', 'id', required_param( 'id', PARAM_INT ) );
        $mform->setType( 'id', PARAM_INT );
        $parms = array (
                'userid',
                'submissionid',
                'popup',
                'fullscreen',
                'privatecopy'
        );
        foreach ($parms as $parm) {
            $value = optional_param( $parm, - 1, PARAM_INT );
            if ($value >= 0) {
                $mform->addElement( 'hidden', $parm, $value );
                $mform->setType( $parm, PARAM_INT );
            }
        }
        $mform->addElement( 'passwordunmask', 'password', get_string( 'password' ) );
        $mform->setType( 'password', PARAM_TEXT );
        $mform->setDefault( 'password', '' );
        $this->add_action_buttons(false, get_string('continue'));
    }
}
