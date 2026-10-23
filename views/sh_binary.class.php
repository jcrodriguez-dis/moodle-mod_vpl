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
 * VPL Syntaxhighlighters for binary files
 *
 * @package mod_vpl
 * @copyright 2014 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/sh_base.class.php');

/**
 * VPL Syntaxhighlighters for binary files
 *
 * This class is used to print the content of a binary file.
 */
class vpl_sh_binary extends vpl_sh_base {
    /**
     * @var array mime types for the binary files
     * This array contains the mime types for the binary files.
     */
    private $mime;

    /**
     * Constructor
     *
     * Initializes the mime types for the binary files.
     */
    public function __construct() {
        $this->mime = [
            'pdf' => 'application/pdf',
            'rtf' => 'application/rtf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
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
        return isset($this->mime[$ext]) ? $this->mime[$ext] : '';
    }


    /**
     * This method prints the content of a binary file.
     *
     * @param string $name name of the file
     * @param string $data content of the file (ignored for binary files)
     * @return void
     */
    public function print_file($name, $data) {
        echo $this->get_header($name, $data, 'binary');
        $mime = $this->get_mime($name);
        if ($mime) {
            $encodeddata = base64_encode($data);
            $style = 'width: 100%; height: 500px;';
            echo '<div class="vpl_sh vpl_g">';
            echo "<embed src='data:$mime;base64,$encodeddata' style='$style'></embed>";
            echo '</div>';
        }
        $strbinaryfile = get_string('binaryfile', VPL);
        $size = vpl_conv_size_to_string(strlen($data));
        echo "$strbinaryfile ($size)<br>";
    }
}
