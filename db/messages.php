<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines message providers
 *
 * @package mod_vpl
 * @copyright 2024 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */

 defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    'bad_jailservers' => [
        'defaults' => [
            'pop-up' => MESSAGE_PERMITTED,
            // Equivalent to email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED.
            'email' => MESSAGE_PERMITTED + 0x01, // Using number for backward compatibility.
            'airnotifier' => MESSAGE_PERMITTED,
        ],
    ],
];
