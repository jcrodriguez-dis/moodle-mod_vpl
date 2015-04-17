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
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
defined( 'MOODLE_INTERNAL' ) || die();

$capabilities = array (
        'mod/vpl:view' => array ( // Allows to view complete vpl description.
                'riskbitmask' => 0,
                'captype' => 'read',
                'contextlevel' => CONTEXT_MODULE,
                'archetypes' => array (
                        'guest' => CAP_PREVENT,
                        'student' => CAP_ALLOW,
                        'teacher' => CAP_ALLOW,
                        'editingteacher' => CAP_ALLOW,
                        'coursecreator' => CAP_ALLOW,
                        'manager' => CAP_ALLOW
                )
        ),

        'mod/vpl:submit' => array ( // Allows to submit a vpl assingment.
                'riskbitmask' => 0,
                'captype' => 'write',
                'contextlevel' => CONTEXT_MODULE,
                'archetypes' => array (
                        'guest' => CAP_PROHIBIT,
                        'student' => CAP_ALLOW,
                        'teacher' => CAP_PREVENT,
                        'editingteacher' => CAP_ALLOW,
                        'coursecreator' => CAP_ALLOW,
                        'manager' => CAP_ALLOW
                )
        ),

        'mod/vpl:grade' => array ( // Allows to grade a vpl submission.
                'riskbitmask' => RISK_PERSONAL,
                'captype' => 'write',
                'contextlevel' => CONTEXT_MODULE,
                'archetypes' => array (
                        'guest' => CAP_PROHIBIT,
                        'student' => CAP_PREVENT,
                        'teacher' => CAP_ALLOW,
                        'editingteacher' => CAP_ALLOW,
                        'coursecreator' => CAP_ALLOW,
                        'manager' => CAP_ALLOW
                )
        ),
        'mod/vpl:similarity' => array ( // Allows to show submissions similarity.
                'riskbitmask' => RISK_PERSONAL,
                'captype' => 'read',
                'contextlevel' => CONTEXT_MODULE,
                'archetypes' => array (
                        'guest' => CAP_PROHIBIT,
                        'student' => CAP_PREVENT,
                        'teacher' => CAP_ALLOW,
                        'editingteacher' => CAP_ALLOW,
                        'coursecreator' => CAP_ALLOW,
                        'manager' => CAP_ALLOW
                )
        ),
        'mod/vpl:addinstance' => array ( // Allows to add new vpl instance.
                'riskbitmask' => RISK_SPAM | RISK_XSS,
                'captype' => 'write',
                'contextlevel' => CONTEXT_MODULE,
                'archetypes' => array (
                        'guest' => CAP_PROHIBIT,
                        'student' => CAP_PROHIBIT,
                        'teacher' => CAP_PREVENT,
                        'editingteacher' => CAP_ALLOW,
                        'coursecreator' => CAP_ALLOW,
                        'manager' => CAP_ALLOW
                )
        ),
        'mod/vpl:manage' => array ( // Allows to manage a vpl instance.
                'riskbitmask' => RISK_SPAM | RISK_XSS | RISK_PERSONAL,
                'captype' => 'write',
                'contextlevel' => CONTEXT_MODULE,
                'archetypes' => array (
                        'guest' => CAP_PROHIBIT,
                        'student' => CAP_PROHIBIT,
                        'teacher' => CAP_PREVENT,
                        'editingteacher' => CAP_ALLOW,
                        'coursecreator' => CAP_ALLOW,
                        'manager' => CAP_ALLOW
                )
        ),
        'mod/vpl:setjails' => array ( // Allows to set the jails for a vpl instance.
                'riskbitmask' => RISK_PERSONAL,
                'captype' => 'write',
                'contextlevel' => CONTEXT_MODULE,
                'archetypes' => array (
                        'guest' => CAP_PROHIBIT,
                        'student' => CAP_PROHIBIT,
                        'teacher' => CAP_PROHIBIT,
                        'editingteacher' => CAP_ALLOW,
                        'coursecreator' => CAP_ALLOW,
                        'manager' => CAP_ALLOW
                )
        )
);
