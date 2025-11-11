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
 * Information of a file from a zip file
 */
class file_from_zip extends file_from_dir {
    /**
     * Returns the file information.
     * @return string HTML string with the file information
     */
    public function show_info() {
        $ret = '';
        $ret .= s($this->filename);
        if ($this->userid != '') {
            $ret .= ' ' . self::$usersname[$this->userid];
        }
        return $ret;
    }

    /**
     * Returns if the file can be accessed.
     */
    public function can_access() {
        return true;
    }

    /**
     * Returns the parameters to link to this file.
     *
     * @param int $t Type of link (1 for directory, 2 for activity, 3 for zip)
     * @return array Associative array with parameters for the link
     */
    public function link_parms($t) {
        $res = [
                'type' . $t => 3,
                'vplid' . $t => $this->vplid,
                'zipfile' . $t => $this->dirname,
                'filename' . $t => $this->filename,
        ];
        if ($this->userid != '') {
            $res['username' . $t] = self::$usersname[$this->userid];
        }
        return $res;
    }
}
