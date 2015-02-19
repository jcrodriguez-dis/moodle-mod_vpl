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
 * Class to show a process status in a box
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../locallib.php';

class vpl_status_box{
    static $last_id=0;
    protected $id;
    protected $start_time;

    /**
     * Constructor
     **/
    function __construct($text='') {
        global $OUTPUT;
        $this->id='vpl_sb_'.(self::$last_id+1);
        $this->start_time = time();
        self::$last_id++;
        echo $OUTPUT->box($text,'',$this->id);
    }

    /**
     * print text
     **/
    function print_text($text){
        $JS = 'window.document.getElementById(\'';
        $JS .= $this->id;
        $JS .= '\').innerHTML =\'';
        $JS .= addslashes($text);
        $JS .= '\';';
        echo vpl_include_js($JS);
        @ob_flush();
        flush();
    }

    /**
     * hide box
     **/
    function hide(){
        $JS = 'window.document.getElementById(\'';
        $JS .= $this->id;
        $JS .= '\').style.display=\'none\';';
        echo vpl_include_js($JS);
        @ob_flush();
        flush();
    }
}
class vpl_progress_bar extends vpl_status_box{
    protected $min;
    protected $max;
    protected $last_time;
    protected $text;
    /**
     * Constructor
     **/
    function __construct($text='', $min=0, $max=100) {
        parent::__construct($text);
        $this->text = $text;
        $this->min = $min;
        $this->max = $max;
        $this->last_time = 0;
    }

    function set_value($value){
        if(is_string($value)){
            $this->print_text($this->text.' ('.$value.')');
            return;
        }
        $current_time=time();
        $percent = ((($value-$this->min)*100)/($this->max-$this->min));
        if($this->last_time != $current_time || $percent>=100){
            if($percent>100){
                $percent = 100;
            }
            $this->last_time = $current_time;
            if($percent == 100){
                $text = $this->text.' ('.sprintf("%5.1f",$percent).'%)';
                $text .= ' '.get_string('numseconds','',$current_time-$this->start_time);
                //if(function_exists('memory_get_usage')){
                //    $text .= sprintf(" %5.1fMB",memory_get_usage()/1024000);
                //}
                $this->print_text($text);
            }else{
                $this->print_text($this->text.' ('.sprintf("%5.1f",$percent).'%)');
            }
        }
    }
    function set_max($max){
        $this->max = $max;
    }
}
