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
 * Show/hide HTML div
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
class vpl_hide_show_div{
    static private $globalid=0;
    var $id;
    var $show;
    function __construct($show=false){
        if(self::$globalid == 0){
            echo vpl_include_jsfile('hideshow.js');
        }
        $this->id = self::$globalid;
        $this->show = $show;
        self::$globalid++;
    }
    function generate($return=false){
        $HTML = '<a id="sht'.$this->id.'" href="javascript:void(0);"';
        $HTML .= ' onclick="VPL.show_hide_div('.$this->id.');">';
        if($this->show){
            $HTML .= '[-]';
        }else{
            $HTML .= '[+]';
        }
        $HTML .= '</a>';
        if($return){
            return $HTML;
        }else{
            echo $HTML;
            return '';
        }
    }
    function begin_div($return=false){
        $HTML = '<div id="shd'.$this->id.'"';
        if(!($this->show)){
            $HTML .= ' style="display:none"';
        }
        $HTML .= '>';
        if($return){
            return $HTML;
        }else{
            echo $HTML;
            return '';
        }
    }
    function end_div($return=false){
        if($return){
            return '</div>';
        }else{
            echo '</div>';
            return '';
        }
    }
}
