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
 * Class to use instead of tokenizer.
 * This derived class of tokenizer exposes protected methods as public to test it
 */
class testable_tokenizer extends \mod_vpl\tokenizer\tokenizer {
    /**
     * Get the maximum token count for the tokenizer.
     *
     * @param \mod_vpl\tokenizer\tokenizer $tokenizer The tokenizer instance.
     * @return int The maximum token count.
     */
    public static function get_max_token_count_from($tokenizer): int {
        return $tokenizer->get_max_token_count();
    }

    /**
     * Get the name of the tokenizer.
     *
     * @param \mod_vpl\tokenizer\tokenizer $tokenizer The tokenizer instance.
     * @return string The name of the tokenizer.
     */
    public static function get_name($tokenizer): string {
        return $tokenizer->name;
    }

    /**
     * Get the extensions for the tokenizer.
     *
     * @param \mod_vpl\tokenizer\tokenizer $tokenizer The tokenizer instance.
     * @return array The extensions.
     */
    public static function get_extensions($tokenizer): array {
        return $tokenizer->extension;
    }

    /**
     * Get the available tokens for the tokenizer.
     *
     * @param \mod_vpl\tokenizer\tokenizer $tokenizer The tokenizer instance.
     * @return array The available tokens.
     */
    public static function get_available_tokens($tokenizer): array {
        return $tokenizer->availabletokens;
    }
}
