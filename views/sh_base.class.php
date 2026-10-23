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
 * VPL Syntaxhighlighters base class
 *
 * @package mod_vpl
 * @copyright 2009 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
class vpl_sh_base {
    /**
     * Get the header HTML for a file, including its icon.
     *
     * @param string $filename name of the file
     * @param string|null $data content of the file (optional)
     * @param string|null $icon icon name (optional)
     * @return string HTML header for the file
     */
    public function get_header($filename, $data = null, $icon = null) {
        if ($icon === null) {
            $icon = \mod_vpl\util\file_icon::get_name($filename, $data);
        }
        $iconimg = \mod_vpl\util\file_icon::get_img($icon);
        return "<h4>$iconimg " . s($filename) . "</h4>\n";
    }
    /**
     * This function prints the content of a file.
     *
     * @param string $filename name of the file
     * @param string $data content of the file
     * @return void
     */
    public function print_file($filename, $data) {
    }
}
