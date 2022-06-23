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
 * Base fixture for unit tests for tokenizer
 *
 * This class was designed to have public access to protected
 * methods which could not be called during production.
 *
 * @package mod_vpl
 * @copyright David Parreño Barbuzano
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author  David Parreño Barbuzano <david.parreno101@alu.ulpgc.es>
 */
namespace mod_vpl;

defined('MOODLE_INTERNAL') || die();

use mod_vpl\tokenizer\tokenizer;

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');

class libtokenizer extends tokenizer {
    // Dump method just for compatibility with abstract declaration
    protected function init_tokenizer(string $rulefilename): void {

    }

    // Dump method just for compatibility with abstract declaration
    public function parse(string $filename): array {
        return [];
    }

    // Dump method just for compatibility with abstract declaration
    public function get_line_tokens(string $line, string $startstate=""): array {
        return [];
    }

    /**
     * Public access for create_splitter_regex
     */
    public static function create_splitter_regex(string $src): string {
        return tokenizer::create_splitter_regexp($src);
    }

    /**
     * Public access for remove_capturing_groups
     */
    public static function remove_capturing_groups(string $src): string {
        return tokenizer::remove_capturing_groups($src);
    }

    /**
     * Public access for check_type
     */
    public static function check_type($value, string $typename) {
        return tokenizer::check_type($value, $typename);
    }
}