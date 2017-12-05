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
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/graphlib.php");

class vpl_graph {
    private static $colors = array (
            'maroon',
            'green',
            'ltgreen',
            'ltltgreen',
            'olive',
            'navy',
            'purple',
            'gray',
            'red',
            'ltred',
            'ltltred',
            'orange',
            'ltorange',
            'ltltorange',
            'lime',
            'blue',
            'ltblue',
            'ltltblue',
            'aqua'
    );
    /**
     * Draw a graph image. Staked area
     *
     * @param $title string
     *            : title of graph
     * @param $xlabel string
     *            : x label
     * @param $ylabel string
     *            : y label
     * @param $legends array
     *            : array of strings, values are the names of every serie
     * @param $xdata array
     *            : x labels
     * @param $ydata matrix
     *            : array of array of numbers first array is indexed by legend.
     * @return nothing
     */
    static public function draw($title, $xlabel, $ylabel, $xdata, $ydata, $legends = null, $typebar = false) {
        $graph = new graph( 750, 400 );
        $graph->parameter ['title'] = $title;
        $graph->parameter ['x_label'] = $xlabel;
        $graph->parameter ['y_label_left'] = $ylabel;
        $graph->x_data = $xdata;
        if ($legends == null) {
            $graph->parameter ['shadow'] = 'gray66';
            $graph->y_data = array (
                    'default_serie' => $ydata
            );
            $legends = array (
                    'default_serie'
            );
            $cn = 0;
        } else {
            $graph->parameter ['shadow'] = 'none';
            $graph->y_data = $ydata;
            $cn = 1;
        }
        $graph->y_order = $legends;
        $graph->y_format = array ();
        $areints = true;
        $maxvalue = 0;
        foreach ($legends as $name) {
            $graph->y_format [$name] = array (
                    'colour' => self::$colors [($cn ++) % count( self::$colors )]
            );
            if ($name != 'default_serie') {
                $graph->y_format [$name] ['legend'] = $name;
            }
            if ($typebar) {
                $graph->y_format [$name] ['bar'] = 'fill';
                $graph->y_format [$name] ['bar_size'] = 0.9;
            } else {
                $graph->y_format [$name] ['area'] = 'fill';
            }
            foreach ($graph->y_data [$name] as $value) {
                $maxvalue = max( $maxvalue, $value );
                $areints = $areints && ((( int ) ($value * 100)) % 100 == 0);
            }
        }
        if ($areints && $maxvalue <= 14) {
            $graph->parameter ['y_axis_gridlines'] = $maxvalue + 1;
            $graph->parameter ['y_decimal_left'] = 0;
        } else {
            $range = 0.1;
            for ($i = 0; $i < 30; $i ++) {
                if ($range * 15 > $maxvalue) {
                    break;
                }
                $range = ($range * 2.5);
                if (($range * 15) > $maxvalue) {
                    break;
                }
                $range *= 2;
                if (($range * 15) > $maxvalue) {
                    break;
                }
                $range *= 2;
            }
            $rem = $maxvalue - (( int ) ($maxvalue / $range)) * $range;
            if ($rem > 0) {
                $maxvalue = $maxvalue + $range - $rem;
            }
            $gridlines = ( int ) ($maxvalue / $range) + 1;
            $graph->parameter ['y_axis_gridlines'] = $gridlines;
            $graph->parameter ['y_max_left'] = $maxvalue;
            $graph->parameter ['y_min_left'] = 0;
            if ($maxvalue >= 1) {
                $graph->parameter ['y_resolution_left'] = 4; // Don't ajust y_max.
            } else {
                $graph->parameter ['y_resolution_left'] = 0;
            }
            if (( int ) $range == $range) {
                $graph->parameter ['y_decimal_left'] = 0;
            } else if (( int ) ($range * 10) == (10 * $range)) {
                $graph->parameter ['y_decimal_left'] = 1;
            } else {
                $graph->parameter ['y_decimal_left'] = 2;
            }
        }
        $graph->parameter ['label_size'] = "12";
        $graph->parameter ['legend_size'] = "10";
        $graph->parameter ['x_label_angle'] = 0;
        $graph->parameter ['tick_length'] = 0;
        $graph->parameter ['legend'] = 'inside-top';
        $graph->parameter ['shadow_offset'] = 5;
        $graph->parameter ['shadow_below_axis'] = false;
        error_reporting( 5 ); // Ignore warnings.
        $graph->draw_stack();
    }
}

