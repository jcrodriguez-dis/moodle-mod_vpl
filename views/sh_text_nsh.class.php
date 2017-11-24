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
 * VPL show text/code whitout sh
 *
 * @package mod_vpl
 * @copyright 2014 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname ( __FILE__ ) . '/sh_base.class.php');

class vpl_sh_text_nsh extends vpl_sh_base {
    public function print_file($name, $data) {
        echo "<h4>" . s( $name ) . '</h4>';
        echo '<pre class="vpl_sh vpl_g">';
        $lines = preg_split("/\r\n|\n|\r/", $data);
        $nl = 1;
        foreach ($lines as $line) {
            printf("%4d  ", $nl);
            echo s($line).'<br>';
            $nl++;
        }
        echo '</pre>';
    }
}
