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
 * SPRESAI evaluator plugin for VPL activities.
 *
 * @package vplevaluator_spresai
 * @copyright 2025 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */
class spresai extends \mod_vpl\plugininfo\vplevaluator_base {

    /**
     * Check if a file is populated in the evaluation data.
     * @param string $filename
     * @return bool
     */
    private function is_file_populated($filename): bool {
        if ($this->evaluationdata === null) {
            return false;
        }
        $files = $this->evaluationdata->files;
        if (! isset($files[$filename])) {
            return false;
        }
        return strlen(trim($files[$filename])) > 8;
    }

    /**
     * Files to add to the execution files.
     * Commonly include at least the file 'vpl_evaluate.sh'
     * @return array of files file_name => contents
     */
    public function get_execution_files(): array {
        $filenames = [
            'system_prompt.txt',
            'evaluate_prompt.txt',
            'explain_prompt.txt',
            'fix_prompt.txt',
            'tip_prompt.txt',
        ];
        $base = __DIR__ . "/src";
        $files = [];
        // Always include core files.
        foreach (['utils.py', 'evaluator.py'] as $filename) {
            $files["spresai/$filename"] = file_get_contents("$base/$filename");
        }
        // Include default prompt files only if not populated in evaluation data.
        foreach ($filenames as $filename) {
            $relativepath = "spresai/$filename";
            if ($this->is_file_populated($relativepath)) {
                continue;
            }
            $files[$relativepath] = file_get_contents("$base/$filename");
        }
        // Include assignment prompt taken from activity if not populated.
        $relativepath = 'spresai/assignment_prompt.txt';
        if (! $this->is_file_populated($relativepath) && $this->vpl !== null) {
            $assignment = $this->vpl->get_fulldescription_with_basedon();
            $files[$relativepath] = format_text_email($assignment, FORMAT_HTML);
        }
        // Include rubric prompt as empty file if not populated.
        $relativepath = 'spresai/rubric_prompt.txt';
        if (! $this->is_file_populated($relativepath) && $this->vpl !== null) {
            $files[$relativepath] = '';
        }
        $files[$this->get_execution_script()] = file_get_contents("$base/vpl_evaluate.sh");
        return $files;
    }

    /**
     * Returns the path to the script to start the evaluation.
     * @return string path to the start script
     */
    public function get_execution_script(): string {
        return 'vpl_evaluate_spresai.sh';
    }

    /**
     * Return the files to keep when running after compiling
     * @return array of file_names
     */
    public function get_files_to_keep_when_running(): array {
        return [
            'spresai/config.py',
            'spresai/utils.py',
            'spresai/evaluator.py',
            'spresai/system_prompt.txt',
            'spresai/evaluate_prompt.txt',
            'spresai/explain_prompt.txt',
            'spresai/fix_prompt.txt',
            'spresai/tip_prompt.txt',
            'spresai/rubric_prompt.txt',
            'spresai/assignment_prompt.txt',
        ];
    }

    /**
     * Return the files to exclude from send if not evaluating.
     * Removing these files can improve security.
     * @return array of file_names
     */
    public function get_files_to_exclude_when_not_evaluating(): array {
        return $this->get_files_to_keep_when_running();
    }

    /**
     * Files to use as base for setting test cases
     * These files will be save in the execution files section.
     * Names must not collide with other execution files.
     * @return array of files file_name => contents
     */
    public function get_test_files(): array {
        $files = [
            'spresai/config.py' => file_get_contents(__DIR__ . '/src/config.py'),
            'spresai/rubric_prompt.txt' => '',
        ];
        return $files;
    }
}
