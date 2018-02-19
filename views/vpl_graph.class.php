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
require_once($CFG->libdir . '/graphlib.php');

class vpl_graph {
    /**
     * Draw a graph image. Staked area
     *
     * @param $title string title of graph
     * @param $xlabel string x label
     * @param $ylabel string y label
     * @param $legends array of strings, values are the names of every serie
     * @param $xdata array x labels
     * @param $ydata array of array of numbers first array is indexed by legend.
     * @return void
     */
    static public function draw($title, $xlabel, $ylabel, $xdata, $ydata, $legends = false, $typebar = false) {
        global $OUTPUT;
        $chart = new \core\chart_bar();
        $chart->set_stacked(true);
        $chart->set_title($title);
        $chart->set_labels($xdata);
        $chart->get_xaxis(0, true)->set_label($xlabel) ;
        $chart->get_yaxis(0, true)->set_label($ylabel) ;
        $chart->get_xaxis(0, true)->set_labels($xdata) ;
        if ( $legends == false) {
            $serie = new \core\chart_series($ylabel, $ydata);
            $chart->add_series($serie);
        } else {
            $chart->set_stacked(true);
            foreach ($legends as $legen) {
                $serie = new \core\chart_series($legen, $ydata[$legen]);
                $serie->set_smooth(true);
                $serie->set_type($serie::TYPE_LINE);
                $chart->add_series($serie);
            }
        }
        echo $OUTPUT->render($chart);
    }
}
/*
function mierda() {
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

}
*/