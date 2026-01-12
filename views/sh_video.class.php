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
 * VPL show video files
 *
 * @package mod_vpl
 * @copyright 2026 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/sh_base.class.php');

/**
 * Class to show a video player
 *
 * This class is used to show a video player.
 * It can be used to show the content of a video file.
 */
class vpl_sh_video extends vpl_sh_base {
    /**
     * @var array mime types for the video files
     * This array contains the mime types for the video files.
     */
    private $mime;

    /**
     * Constructor
     *
     * Initializes the mime types for the video files.
     */
    public function __construct() {
        $this->mime = [
                'mp4' => 'mp4',
                'webm' => 'webm',
                'ogg' => 'ogg',
                'ogv' => 'ogg',
                'avi' => 'x-msvideo',
                'mov' => 'quicktime',
                'wmv' => 'x-ms-wmv',
                'flv' => 'x-flv',
                'mkv' => 'x-matroska',
                'm4v' => 'x-m4v',
                'mpeg' => 'mpeg',
                'mpg' => 'mpeg',
                '3gp' => '3gpp',
        ];
    }

    /**
     * Get the mime type of a file
     *
     * @param string $name name of the file
     * @return string mime type of the file
     */
    public function get_mime($name) {
        $ext = strtolower(vpl_fileextension($name));
        return $this->mime[$ext];
    }

    /**
     * Print a video player for a video file
     *
     * @param string $name name of the file to show
     * @param string $data content of the file to show
     */
    public function print_file($name, $data) {
        echo "<h4>" . s($name) . '</h4>';
        echo '<div class="vpl_sh vpl_g">';
        echo '<video controls style="width: 100%; max-width: 800px;">';
        echo '<source src="data:video/' . $this->get_mime($name) . ';base64,';
        echo base64_encode($data);
        echo '" type="video/' . $this->get_mime($name) . '" />';
        echo 'Your browser does not support the video element.';
        echo '</video>';
        echo '</div>';
    }
}
