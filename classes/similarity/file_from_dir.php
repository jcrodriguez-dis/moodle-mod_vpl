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
 * Information of a file from a directory
 */
class file_from_dir extends file_from_base {
    /**
     * @var array $usersname Static array to store user names indexed by their IDs.
     * This is static to avoid loading the same user multiple times.
     */
    protected static $usersname = [];

    /**
     * @var string $dirname Directory name where the file is located.
     */
    protected $dirname;

    /**
     * @var string $filename Name of the file.
     */
    protected $filename;

    /**
     * @var string $userid User ID associated with the file.
     */
    protected $userid;

    /**
     * @var int $vplid ID of the VPL activity.
     */
    protected $vplid;

    /**
     * Processes a GAP user file to extract user information.
     * This method reads the contents of a file named 'datospersonales.gap'
     * and extracts the user's NIF, name, and surname.
     * It then stores the user's full name in the static $usersname array.
     * This is for compatibility with GAP 2.x application.
     * @param string $filepath The path to the file to process.
     */
    public static function process_gap_userfile($filepath) {
        if (strtolower(basename($filepath)) == 'datospersonales.gap') {
            $nif = '';
            $nombre = '';
            $apellidos = '';
            $lines = explode("\n", file_get_contents($filepath));
            if (count($lines) > 3) {
                if (strpos($lines[1], 'NIF=') !== false) {
                    $nif = substr($lines[1], 4);
                }
                if (strpos($lines[2], 'Nombre=') !== false) {
                    $nombre = substr($lines[2], 7);
                }
                if (strpos($lines[3], 'Apellidos=') !== false) {
                    $apellidos = substr($lines[3], 10);
                }
            }
            if ($nif > '' && $nombre > '' && $apellidos > '') {
                global $CFG;
                if ($CFG->fullnamedisplay == 'lastname firstname') {
                    self::$usersname[$nif] = mb_convert_encoding($apellidos . ', ' . $nombre, 'utf-8');
                } else {
                    self::$usersname[$nif] = mb_convert_encoding($nombre . ' ' . $apellidos, 'utf-8');
                }
            }
        }
    }

    /**
     * Returns the user ID from the filename.
     * This is for compatibility with GAP 2.x application.
     * @param string $filename The name of the file.
     * @return string The user ID if found, otherwise an empty string.
     */
    public static function get_user_id_from_file($filename) {
        if (count(self::$usersname)) {
            $filename = strtolower($filename);
            foreach (array_keys(self::$usersname) as $userid) {
                if (strpos($filename, $userid) !== false) {
                    return $userid;
                }
            }
        }
        return '';
    }

    /**
     * Constructor for the file_from_dir class.
     *
     * @param string $filename The name of the file.
     * @param int $vplid The ID of the VPL activity.
     * @param string $dirname The directory name where the file is located.
     * @param string $userid The user ID associated with the file (optional).
     */
    public function __construct(&$filename, $vplid, $dirname, $userid = '') {
        $this->filename = $filename;
        $this->dirname = $dirname;
        $this->vplid = $vplid;
        $this->userid = self::get_user_id_from_file($filename);
    }

    /**
     * Returns the user ID of the file.
     * @return string The user ID associated with the file.
     */
    public function get_userid() {
        return $this->userid;
    }

    /**
     * Returns the file information.
     * @return string HTML string with the file information
     */
    public function show_info() {
        $ret = '';
        $ret .= '<a href="' . 'file.php' . '">';
        $ret .= s($this->filename) . ' ';
        $ret .= '</a>';
        if ($this->userid != '') {
            $ret .= ' ' . self::$usersname[$this->userid];
        }
        return $ret;
    }

    /**
     * Returns if the file can be accessed.
     * @return bool True if the file can be accessed, false otherwise
     */
    public function can_access() {
        return $this->filename != preprocess::JOINEDFILENAME;
    }

    /**
     * Returns the parameters to link to this file.
     *
     * @param int $t Type of link (1 for directory, 2 for activity, 3 for zip)
     * @return array Associative array with parameters for the link
     */
    public function link_parms($t) {
        $res = [
                'type' . $t => 2,
                'dirname' . $t => $this->dirname,
                'filename' . $t => $this->filename,
        ];
        if ($this->userid != '') {
            $res['username' . $t] = self::$usersname[$this->userid];
        }
        return $res;
    }
}
