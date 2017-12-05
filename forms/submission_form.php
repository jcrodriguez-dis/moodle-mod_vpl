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
 * Submission form definition
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined( 'MOODLE_INTERNAL' ) || die();

require_once($CFG->libdir.'/formslib.php');
require_once(dirname(__FILE__).'/../locallib.php');

class mod_vpl_submission_form extends moodleform {
    protected $vpl;
    protected function getinternalform() {
        return $this->_form;
    }
    public function __construct($page, $vpl) {
        $this->vpl = $vpl;
        parent::__construct( $page );
    }
    protected function definition() {
        global $CFG;
        $mform = & $this->_form;
        $mform->addElement( 'header', 'headersubmission', get_string( 'submission', VPL ) );
        // Identification info.
        $mform->addElement( 'hidden', 'id' );
        $mform->setType( 'id', PARAM_INT );
        $mform->addElement( 'hidden', 'userid', 0 );
        $mform->setType( 'userid', PARAM_INT );
        // Comments.
        $mform->addElement( 'textarea', 'comments', get_string( 'comments', VPL ), array (
                'cols' => '40',
                'rows' => 2
        ) );
        $mform->setType( 'comments', PARAM_TEXT );

        // Files upload.
        $instance = $this->vpl->get_instance();
        $files = $this->vpl->get_required_files();
        $nfiles = count( $files );
        for ($i = 0; $i < $instance->maxfiles; $i ++) {
            $field = 'file' . $i;
            if ($i < $nfiles) {
                $mform->addElement( 'filepicker', $field, $files [$i] );
            } else {
                $mform->addElement( 'filepicker', $field, get_string( 'anyfile', VPL ) );
            }
        }
        $this->add_action_buttons( true, get_string( 'submit' ) );
    }
}
