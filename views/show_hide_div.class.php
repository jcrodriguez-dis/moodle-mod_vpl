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
 * Show/hide HTML div [+]/[-]
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined( 'MOODLE_INTERNAL' ) || die();

require_once(dirname(__FILE__).'/../locallib.php');

class vpl_hide_show_div {
    private static $globalid = 0;
    protected $id;
    protected $show;
    public function __construct($show = false) {
        if (self::$globalid == 0) {
            echo vpl_include_jsfile( 'hideshow.js' );
        }
        $this->id = self::$globalid;
        $this->show = $show;
        self::$globalid ++;
    }
    public function generate($return = false) {
        $html = ' <a id="sht' . $this->id . '" style="cursor:pointer"';
        $html .= ' onclick="VPL.showHideDiv(' . $this->id . ');">';
        if ($this->show) {
            $html .= '<i class="fa fa-eye-slash" aria-hidden="true"></i> [-]';
        } else {
            $html .= '<i class="fa fa-eye" aria-hidden="true"></i> [+]';
        }
        $html .= '</a>';
        if ($return) {
            return $html;
        } else {
            echo $html;
            return '';
        }
    }
    public function begin_div($return = false) {
        $html = '<div id="shd' . $this->id . '" class="vpl_show_hide_content"';
        if (! ($this->show)) {
            $html .= ' style="display:none"';
        }
        $html .= '>';
        if ($return) {
            return $html;
        } else {
            echo $html;
            return '';
        }
    }

    public function end_div($return = false) {
        if ($return) {
            return '</div>';
        } else {
            echo '</div>';
            return '';
        }
    }

    public function get_div_id() {
        return 'shd' . $this->id;
    }

}
