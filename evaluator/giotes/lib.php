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
 * GIOTES evaluator plugin for VPL activities.
 *
 * @package vplevaluator_giotes
 * @copyright 2025 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */
class giotes extends \mod_vpl\plugininfo\vplevaluator_base {
    /**
     * Files to add to the execution files.
     * Commonly include at least the file 'vpl_evaluate.sh'
     * @return array of files file_name => contents
     */
    public function get_execution_files(): array {
        $files = [];
        $srcdir = __DIR__ . '/src';
        $pattern = '{*.cpp,*.hpp}';
        $foundfiles = glob("$srcdir/$pattern", GLOB_BRACE);
        foreach ($foundfiles as $filepath) {
            $filename = basename($filepath);
            $files[".giotes/$filename"] = file_get_contents($filepath);
        }
        $files['vpl_evaluate.sh'] = file_get_contents(__DIR__ . '/src/vpl_evaluate.sh');
        return $files;
    }

    /**
     * Files to use as base for setting test cases
     * These files will be save in the execution files section.
     * Names must not collide with other execution files.
     * @return array of files file_name => contents
     */
    public function get_test_files(): array {
        return ['vpl_evaluate.cases' => file_get_contents(__DIR__ . '/src/vpl_evaluate.cases')];
    }
}
