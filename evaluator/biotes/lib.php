<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_vpl\evaluator;

/**
 * BIOTES evaluator plugin for VPL activities.
 *
 * @package vplevaluator_biotes
 * @copyright 2025 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */
class biotes extends \mod_vpl\plugininfo\vplevaluator_base {
    /**
     * Files to add to the execution files.
     * @return array of files file_name => contents
     */
    public function get_execution_files(): array {
        global $CFG;
        $files = [];
        $path = $CFG->dirroot . '/mod/vpl/jail/default_scripts/';
        $files['vpl_evaluate.sh'] = file_get_contents($path . 'default_evaluate.sh');
        $files['vpl_evaluate.cpp'] = file_get_contents($path . 'vpl_evaluate.cpp');
        return $files;
    }

    /**
     * Files to use as base for setting test cases.
     * @return array of files file_name => contents
     */
    public function get_test_files(): array {
        return ['vpl_evaluate.cases' => ''];
    }

    /**
     * Help content for this evaluator in markdown format.
     * @return string Help content in markdown format
     */
    public function get_help(): string {
        global $CFG;
        $imagespath = $CFG->wwwroot . '/mod/vpl/evaluator/biotes/helpimages/';
        $mdhelp = parent::get_help();
        return str_replace('@@@IMAGESPATH@@@', $imagespath, $mdhelp);
    }
}
