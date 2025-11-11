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

/**
 * Class vpl_graph
 *
 * Class to draw graphs for VPL activities.
 *
 * @package mod_vpl
 */
class vpl_graph {
    /**
     * Draw a graph image. Staked area
     *
     * @param string $title title of graph
     * @param string $xlabel x label
     * @param string $ylabel y label
     * @param array $xdata array of x labels
     * @param array $ydata array of numbers first array is indexed by legend.
     * @param bool $legends legends for the graph, if false, only one serie is drawn
     * @param bool $typebar if true, the graph is a bar graph
     * @return void
     */
    public static function draw($title, $xlabel, $ylabel, $xdata, $ydata, $legends = false, $typebar = false) {
        global $OUTPUT;
        $chart = new \core\chart_bar();
        $chart->set_stacked(true);
        $chart->set_title($title);
        $chart->set_labels($xdata);
        $chart->get_xaxis(0, true)->set_label($xlabel);
        $chart->get_yaxis(0, true)->set_label($ylabel);
        $chart->get_xaxis(0, true)->set_labels($xdata);
        if ($legends == false) {
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
