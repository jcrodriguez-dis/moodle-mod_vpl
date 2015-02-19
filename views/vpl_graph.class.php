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
 * Graph submissions statistics for a vpl instance and a user
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
class vpl_graph{
    static private $colors= array(
            'maroon','green','ltgreen','ltltgreen',
            'olive','navy','purple','gray','red','ltred',
            'ltltred','orange','ltorange','ltltorange','lime',
            'blue','ltblue','ltltblue','aqua');
    /**
     * Draw a graph image. Staked area
     * @param $title string : title of graph
     * @param $x_label string : x label
     * @param $y_label string : y label
     * @param $legends array : array of strings, values are the names of every serie
     * @param $x_data array : x labels
     * @param $y_data matrix : array of array of numbers first array is indexed by legend.
     * @return nothing
     */
    static public function draw($title,$x_label, $y_label,$x_data,$y_data,$legends=null,$type_bar=false){
        $graph = new graph(750, 400);
        $graph->parameter['title'] = $title;
        $graph->parameter['x_label'] = $x_label;
        $graph->parameter['y_label_left'] = $y_label;
        $graph->x_data= $x_data;
        if($legends == null){
            $graph->parameter['shadow'] = 'gray66';
            $graph->y_data= array('default_serie' => $y_data);
            $legends = array('default_serie');
            $cn=0;
        }else{
            $graph->parameter['shadow'] = 'none';
            $graph->y_data= $y_data;
            $cn=1;
        }
        $graph->y_order = $legends;
        $graph->y_format = array();
        $are_ints=true;
        $max_value=0;
        foreach($legends as $name){
            $graph->y_format[$name] = array('colour' => self::$colors[($cn++)%count(self::$colors)]);
            if($name != 'default_serie'){
                $graph->y_format[$name]['legend'] = $name;
            }
            if($type_bar){
                $graph->y_format[$name]['bar']='fill';
                $graph->y_format[$name]['bar_size']=0.9;
            }else{
                $graph->y_format[$name]['area']='fill';
            }
            foreach($graph->y_data[$name] as $value){
                $max_value=max($max_value,$value);
                $are_ints=$are_ints && (((int)($value*100)) % 100 == 0);
            }
        }
        if($are_ints && $max_value<=14){
            $graph->parameter['y_axis_gridlines']=$max_value+1;
            $graph->parameter['y_decimal_left']=0;
        }else{
            $range=0.1;
            for($i=0; $i<30; $i++){
                if($range*15 > $max_value) break;
                $range = ($range*2.5);
                if(($range*15) > $max_value) break;
                $range *= 2;
                if(($range*15) > $max_value) break;
                $range *= 2;
            }
            $rem =$max_value - ((int)($max_value/$range))*$range;
            if($rem>0){
                $max_value = $max_value+$range-$rem;
            }
            $gridlines =(int)($max_value/$range)+1;
            $graph->parameter['y_axis_gridlines']=$gridlines;
            $graph->parameter['y_max_left']=$max_value;
            $graph->parameter['y_min_left']=0;
            if($max_value>=1){
                $graph->parameter['y_resolution_left']=4; //Don't ajust y_max
            }else{
                $graph->parameter['y_resolution_left']=0;
            }
            if((int)$range == $range){
                $graph->parameter['y_decimal_left']=0;
            }elseif((int)($range*10)==(10*$range)){
                $graph->parameter['y_decimal_left']=1;
            }else{
                $graph->parameter['y_decimal_left']=2;
            }
        }
        $graph->parameter['label_size']        = "12";
        $graph->parameter['legend_size']    = "10";
        $graph->parameter['x_label_angle']    = 0;
        $graph->parameter['tick_length']    = 0;
        $graph->parameter['legend']            = 'inside-top';
        $graph->parameter['shadow_offset']    = 5;
        $graph->parameter['shadow_below_axis'] = false;
        error_reporting(5); // ignore warnings
        $graph->draw_stack();
    }
}

require_once dirname(__FILE__).'/../../../config.php';
global $CFG;
require_once("$CFG->libdir/graphlib.php");
