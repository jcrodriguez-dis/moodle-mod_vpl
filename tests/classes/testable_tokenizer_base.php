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
 * Class to use instead of tokenizer_base.
 * This derived class of tokenizer_base exposes protected methods as public to test it
 */
class testable_tokenizer_base extends \mod_vpl\tokenizer\tokenizer_base {
    /**
     * Get the maximum token count for the tokenizer.
     *
     * @param \mod_vpl\tokenizer\tokenizer $tokenizer The tokenizer instance.
     * @return int The maximum token count.
     */
    public static function get_states_from($tokenizer): array {
        return $tokenizer->get_states();
    }

    /**
     * Get the match mappings from the tokenizer.
     *
     * @param \mod_vpl\tokenizer\tokenizer $tokenizer The tokenizer instance.
     * @return array The match mappings used by the tokenizer.
     */
    public static function get_matchmappings_from($tokenizer): array {
        return $tokenizer->get_matchmappings();
    }

    /**
     * Get the regular expressions from the tokenizer.
     *
     * @param \mod_vpl\tokenizer\tokenizer $tokenizer The tokenizer instance.
     * @return array The regular expressions used by the tokenizer.
     */
    public static function get_regexprs_from($tokenizer): array {
        return $tokenizer->get_regexprs();
    }

    /**
     * Check if a value matches a given type.
     *
     * @param mixed $value The value to check.
     * @param string $typename The type name to check against.
     * @return bool True if the value matches the type, false otherwise.
     */
    public static function check_type($value, string $typename) {
        return \mod_vpl\tokenizer\tokenizer_base::check_type($value, $typename);
    }

    /**
     * Check if a rule is in the list of available rules.
     *
     * @param array $state The current state of the tokenizer.
     * @param object $rule The rule to check.
     * @return bool True if the rule is available, false otherwise.
     */
    public static function contains_rule(array $state, object $rule): bool {
        return \mod_vpl\tokenizer\tokenizer_base::contains_rule($state, $rule);
    }

    /**
     * Check if a token is in the list of available tokens.
     *
     * @param string $token The token to check.
     * @param array $availabletokens The list of available tokens.
     * @return bool True if the token is available, false otherwise.
     */
    public static function check_token($token, array $availabletokens): bool {
        return \mod_vpl\tokenizer\tokenizer_base::check_token($token, $availabletokens);
    }

    /**
     * Remove capturing groups from a regex source string.
     *
     * @param string $src The source regex string.
     * @return string The modified regex string without capturing groups.
     */
    public static function remove_capturing_groups(string $src): string {
        return \mod_vpl\tokenizer\tokenizer_base::remove_capturing_groups($src);
    }

    /**
     * Get a token array.
     *
     * @param int $numline The line number of the token.
     * @param array $type The type of the token.
     * @param string $value The value of the token.
     * @param string $regex The regex pattern for the token.
     * @return array The token array.
     */
    public static function get_token_array(int $numline, array $type, string $value, string $regex): array {
        return \mod_vpl\tokenizer\tokenizer_base::get_token_array($numline, $type, $value, $regex);
    }
}
