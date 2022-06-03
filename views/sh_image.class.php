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
 * VPL show images
 *
 * @package mod_vpl
 * @copyright 2014 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname ( __FILE__ ) . '/sh_base.class.php');

class vpl_sh_image extends vpl_sh_base {
    private $mime;
    public function __construct() {
        $this->mime = array (
                'jpg' => 'jpeg',
                'jpeg' => 'jpeg',
                'gif' => 'gif',
                'png' => 'png',
                'ico' => 'vnd.microsoft.icon'
        );
    }
    public function get_mime($name) {
        $ext = strtolower( vpl_fileextension( $name ) );
        return $this->mime[$ext];
    }
    public function print_file($name, $data) {
        echo "<h4>" . s( $name ) . '</h4>';
        echo '<div class="vpl_sh vpl_g">';
        echo '<img src="data:image/' . $this->get_mime( $name ) . ';base64,';
        echo base64_encode( $data );
        echo '" alt="' . s( $name ) . '" />';
        echo '</div>';
    }
}
