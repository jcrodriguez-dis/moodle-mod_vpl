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
 * Classes to manage file from difrerent soruces
 *
 * @package mod_vpl
 * @copyright 2015 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\similarity;

/**
 * Class to represent files from any source.
 */
class file_from_base {
    /**
     * Returns the file information.
     * @return string HTML string with the file information.
     */
    public function show_info() {
        return '';
    }

    /**
     * Returns if the file can be accessed.
     * @return bool True if the file can be accessed, false otherwise.
     */
    public function can_access() {
        return false;
    }

    /**
     * Returns the user ID of the file.
     * @return string The user ID associated with the file.
     */
    public function get_userid() {
        return '';
    }
}
