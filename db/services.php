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
 * web service definition
 *
 * @package mod_vpl
 * @copyright 2014 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
// Definition of functions of the web service.
$functions = array (
        'mod_vpl_info' => array (
                'classname' => 'mod_vpl_webservice',
                'methodname' => 'info',
                'classpath' => 'mod/vpl/externallib.php',
                'description' => 'Get the information/description about a VPL activity',
                'requiredcapability' => 'mod/vpl:view',
                'type' => 'read'
        ),
        'mod_vpl_save' => array (
                'classname' => 'mod_vpl_webservice',
                'methodname' => 'save',
                'classpath' => 'mod/vpl/externallib.php',
                'description' => 'Save/submit the student\'s files of a VPL activity',
                'requiredcapability' => 'mod/vpl:submit',
                'type' => 'write'
        ),
        'mod_vpl_open' => array (
                'classname' => 'mod_vpl_webservice',
                'methodname' => 'open',
                'classpath' => 'mod/vpl/externallib.php',
                'description' => 'Open/Download the student\'s files of the last submission of a VPL activity',
                'requiredcapability' => 'mod/vpl:view',
                'type' => 'read'
        ),
        'mod_vpl_evaluate' => array (
                'classname' => 'mod_vpl_webservice',
                'methodname' => 'evaluate',
                'classpath' => 'mod/vpl/externallib.php',
                'description' => 'Evaluate the student\'s submission',
                'requiredcapability' => 'mod/vpl:submit',
                'type' => 'write'
        ),
        'mod_vpl_get_result' => array (
                'classname' => 'mod_vpl_webservice',
                'methodname' => 'get_result',
                'classpath' => 'mod/vpl/externallib.php',
                'description' => 'Get result of the evalaution',
                'requiredcapability' => 'mod/vpl:view',
                'type' => 'write'
        )
);
// Define web service.
$services = array (
        'VPL web service' => array (
                'functions' => array (
                        'mod_vpl_info',
                        'mod_vpl_save',
                        'mod_vpl_open',
                        'mod_vpl_evaluate',
                        'mod_vpl_get_result'
                ),
                'shortname' => 'mod_vpl_edit',
                'restrictedusers' => 0,
                'enabled' => 0
        )
);
