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
 * @copyright 2026 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl\forms;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
/**
 * Class to define the password form for VPL
 *
 * This form is used to request a password before accessing certain functionalities of VPL.
 */
class activity_password extends \moodleform {
    /**
     * @var \mod_vpl $vpl The VPL instance for which the password is being requested.
     */
    public $vpl;

    /**
     * Constructor
     * @param \mod_vpl $vpl The VPL instance.
     */
    public function __construct($vpl) {
        $this->vpl = $vpl;
        parent::__construct();
    }

    /**
     * Defines the form elements
     */
    protected function definition() {
        global $USER;
        global $SESSION;
        $mform = & $this->_form;
        $mform->addElement('header', 'headerpassword', get_string('requiredpassword', VPL));
        $parms = [
                'id' => $this->vpl->get_course_module()->id,
                'userid' => $USER->id,
                'submissionid' => optional_param('submissionid', -1, PARAM_INT),
                'popup' => optional_param('popup', -1, PARAM_INT),
                'fullscreen' => optional_param('fullscreen', -1, PARAM_INT),
                'privatecopy' => optional_param('privatecopy', -1, PARAM_INT),
        ];
        foreach ($parms as $parm => $value) {
            if ($value >= 0) {
                $mform->addElement('hidden', $parm, $value);
                $mform->setType($parm, PARAM_INT);
            }
        }
        $mform->addElement('passwordunmask', 'password', get_string('password'));
        $mform->setType('password', PARAM_TEXT);
        $mform->setDefault('password', '');

        $attemptnumber = self::get_attempt($this->vpl);
        if ($attemptnumber > 0) {
            $warning = get_string('attemptnumber', VPL, $attemptnumber);
            $mform->addElement(
                'static',
                'attemptnumber',
                '',
                \html_writer::div($warning, 'alert alert-warning')
            );
        }
        $buttongroup = [];
        $buttongroup[] = $mform->createElement('submit', 'save', get_string('save', VPL));
        $mform->addGroup($buttongroup);
    }

    /**
     * Get the session variable name for the password attempt count for the given VPL instance.
     * @param \mod_vpl $vpl The VPL instance.
     * @return string The session variable name.
     */
    public static function get_attempt_var($vpl) {
        return 'vpl_password_attempt_' . $vpl->get_instance()->id;
    }

    /**
     * Get the current password attempt count for the given VPL instance.
     * @param \mod_vpl $vpl The VPL instance.
     * @return int The number of password attempts.
     */
    public static function get_attempt($vpl) {
        global $SESSION;
        $passattempt = self::get_attempt_var($vpl);
        return isset($SESSION->$passattempt) ? $SESSION->$passattempt : 0;
    }

    /**
     * Increment the password attempt count for the given VPL instance.
     * @param \mod_vpl $vpl The VPL instance.
     * @return void
     */
    public static function increment_attempt($vpl) {
        global $SESSION;
        $passattempt = self::get_attempt_var($vpl);
        if (isset($SESSION->$passattempt)) {
            $SESSION->$passattempt++;
        } else {
            $SESSION->$passattempt = 1;
        }
        sleep($SESSION->$passattempt - 1);
    }

    /**
     * Reset the password attempt count for the given VPL instance.
     * @param \mod_vpl $vpl The VPL instance.
     * @return void
     */
    public static function reset_attempt($vpl) {
        global $SESSION;
        $passattempt = self::get_attempt_var($vpl);
        unset($SESSION->$passattempt);
    }
}
