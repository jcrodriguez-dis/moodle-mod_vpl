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
 * Base fixture for unit tests
 * Code inspired on mod/assign/tests/base_test.php
 * @package mod_vpl
 * @copyright Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */

namespace mod_vpl\tests;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');

/**
 * Utilities for tokenizer/similarity tests
 */
class tokenizer_similarity_utils {
    /**
     * Get the list of available tokenizer languages.
     *
     * @return array List of available tokenizer languages.
     */
    public static function get_tokenizer_langs(): array {
        global $CFG;
        $dir = $CFG->dirroot . '/mod/vpl/similarity/tokenizer_rules';
        $scanarr = scandir($dir);
        $filesarr = array_diff($scanarr, ['.', '..']);

        $tokenizerlangs = [];

        foreach ($filesarr as $filename) {
            if (!is_dir($dir . '/' . $filename)) {
                $namelang = preg_split("/_/", $filename)[0];
                $tokenizerlangs[] = $namelang;
            }
        }

        return $tokenizerlangs;
    }
}
