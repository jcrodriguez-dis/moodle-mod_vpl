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

namespace mod_vpl\plugininfo;

/**
 * Base class for VPL evaluators.
 * This class is used to define the interface for VPL evaluators.
 *
 * @package   mod_vpl
 * @copyright 2024 Juan Calos Rodriguez del Pino {@jc.rodriguezdelpino@ulpgc.es}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vplevaluator_base {

    /**
     * Name of the evaluator.
     * @var string
     */
    protected $name;

    /**
     * Constructor.
     * @param string $name of the evaluator.
     */
    public function __construct($name) {
        $this->name = $name;
    }
    /**
     * Returns the files to add to the execution files.
     * Commonly include at least the file 'vpl_evaluate.sh',
     * if not default vpl_evaluate.sh is used.
     * This files contents the evaluation framework and how to run it.
     * @return array of files file_name => contents
     */
    public function get_execution_files(): array {
        return [];
    }

    /**
     * Returns the path to the script to start the evaluation.
     * @return string path to the start script
     */
    public function get_execution_script(): string {
        return 'vpl_evaluate.sh';
    }
    /**
     * Files to use as base for setting test cases
     * These files will be saved in the execution files section.
     * Contains the initial values for test cases.
     * Names must not collide with other execution files.
     * @return array of files file_name => contents
     */
    public function get_test_files(): array {
        return [];
    }

    /**
     * Returns the help for the evaluator in MD format.
     * This help is shown in the evaluator settings.
     * @return string
     */
    public function get_help(): string {
        global $CFG;
        $help = '';
        $helpfilename = $CFG->dirroot . "/mod/vpl/evaluator/{$this->name}/help.md";
        if (file_exists($helpfilename)) {
            $help = file_get_contents($helpfilename);
        }
        return $help;
    }

    /**
     * Return the files to keep when running after compiling
     * @return array of file_names
     */
    public function get_files_to_keep_when_running(): array {
        return [];
    }

    /**
     * Get i18n strings for the evaluator.
     * The strings are send as bash variables to the evaluator.
     * The bash variables will be send in the vpl_environment.sh file.
     * The variables will have form: VPLEVALUATOR_STR_<string_key>
     * @return array of strings: string_key => string_value
     */
    public function get_strings(): array {
        global $CFG;
        $stringsfilename = $CFG->dirroot . "/mod/vpl/evaluator/{$this->name}/lang/en/vplevaluator_{$this->name}.php";
        $strlist = [];
        if (file_exists($stringsfilename)) {
            $string = [];
            include_once($stringsfilename);
            $modname = 'vplevaluator_' . $this->name;
            foreach (array_keys($string) as $key) {
                // Ignore key with : => generate bad variable names.
                if (strpos($key, ':') === false) {
                    $strlist[$key] = get_string($key, $modname);
                }
            }
        }
        return $strlist;
    }
}
