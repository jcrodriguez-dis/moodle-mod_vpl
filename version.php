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
 * Version config
 *
 * @package mod_vpl.
 * @copyright 2023 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 *
 * Define the plugin global var attributes.
 * @var object $plugin
 */


defined('MOODLE_INTERNAL') || die();

$plugin->version = 2023061712;
$plugin->requires = 2018051713; // Moodle 3.5!
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '4.1.1';

$plugin->component = 'mod_vpl';
