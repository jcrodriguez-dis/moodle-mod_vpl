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
 * Unit tests for mod_vpl\tokenizer\tokenizer_json
 *
 * @package mod_vpl
 * @copyright David Parreño Barbuzano
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author  David Parreño Barbuzano <david.parreno101@alu.ulpgc.es>
 */
namespace mod_vpl;

defined('MOODLE_INTERNAL') || die();

use mod_vpl\tokenizer\tokenizer_json;
use mod_vpl\util\assertf;
use Exception;

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');

/**
 * Unit tests for \mod_vpl\tokenizer\tokenizer_json class.
 *
 * @group mod_vpl
 * @group mod_vpl_tokenizer
 * @group mod_vpl_tokenizer_ext
 * @covers \mod_vpl\tokenizer\tokenizer_json
 */
class tokenizer_json_test extends \advanced_testcase {
    /**
     * Array with all invalid test cases
     */
    protected static array $invalidtestcases;

    /**
     * Prepare test cases before the execution
     */
    public static function setUpBeforeClass(): void {
        $dirpath = self::get_test_path() . 'invalid/';
        self::$invalidtestcases = array();

        self::add_invalid_test_case(
            $dirpath . 'dump_test.json',
            'file ' . $dirpath  . 'dump_test.json must exist'
        );

        self::add_invalid_test_case(
            $dirpath . 'general/not_good_suffix.json',
            $dirpath . 'general/not_good_suffix.json' . ' must have suffix _highlight_rules.json'
        );

        self::add_invalid_test_case(
            $dirpath . 'general/empty_highlight_rules.json',
            'file ' . $dirpath . 'general/empty_highlight_rules.json' . ' is empty'
        );

        self::add_invalid_test_case(
            $dirpath . 'general/undefined_option_highlight_rules.json',
            'invalid options: example'
        );

        self::add_invalid_test_case(
            $dirpath . 'general/invalid_check_rules_highlight_rules.json',
            '"check_rules" option must be a boolean'
        );

        self::add_invalid_test_case(
            $dirpath . 'general/invalid_extension_no_string_highlight_rules.json',
            '"extension" option must be a string or an array of strings'
        );

        self::add_invalid_test_case(
            $dirpath . 'general/invalid_extension_no_array_highlight_rules.json',
            '"extension" option must be a string or an array of strings'
        );

        self::add_invalid_test_case(
            $dirpath . 'general/invalid_extension_no_dot_highlight_rules.json',
            'extension c must start with .'
        );

        self::add_invalid_test_case(
            $dirpath . 'general/invalid_inherit_rules_highlight_rules.json',
            '"inherit_rules" option must be a string'
        );

        self::add_invalid_test_case(
            $dirpath . 'states/invalid_data_states_highlight_rules.json',
            '"states" option must be an object'
        );

        self::add_invalid_test_case(
            $dirpath . 'states/states_with_no_name_highlight_rules.json',
            'state 0 must have a name'
        );

        self::add_invalid_test_case(
            $dirpath . 'states/state_not_object_highlight_rules.json',
            'state 0 must be an array'
        );

        self::add_invalid_test_case(
            $dirpath . 'states/one_state_with_no_name_highlight_rules.json',
            'state 1 must have a name'
        );

        self::add_invalid_test_case(
            $dirpath . 'rules/invalid_rule_highlight_rules.json',
            'rule 0 of state "state1" nº0 must be an object'
        );

        self::add_invalid_test_case(
            $dirpath . 'rules/invalid_rule_option_value_highlight_rules.json',
            'invalid data type for token at rule 0 of state "state1" nº0'
        );

        self::add_invalid_test_case(
            $dirpath . 'rules/undefined_rule_option_highlight_rules.json',
            'invalid option example at rule 0 of state "state1" nº0'
        );

        self::add_invalid_test_case(
            $dirpath . 'rules/invalid_next_highlight_rules.json',
            'invalid data type for next at rule 0 of state "state1" nº0 (next: 0)'
        );

        self::add_invalid_test_case(
            $dirpath . 'rules/invalid_next_option_highlight_rules.json',
            'invalid data type for token at rule 0 of state "state1" nº0 (next: 0)'
        );

        self::add_invalid_test_case(
            $dirpath . 'rules/invalid_next_one_rule_highlight_rules.json',
            'invalid data type for next at rule 1 of state "state1" nº0 (next: 0)'
        );

        self::add_invalid_test_case(
            $dirpath . 'rules/invalid_sub_next_highlight_rules.json',
            'invalid data type for next at rule 0 of state "state1" nº0 (next: 0)'
        );

        self::add_invalid_test_case(
            $dirpath . 'rules/regex_not_found_highlight_rules.json',
            'option token must be defined next to regex at rule 0 of state "state1" nº0'
        );

        self::add_invalid_test_case(
            $dirpath . 'rules/token_not_found_highlight_rules.json',
            'option regex must be defined next to token at rule 0 of state "state1" nº0'
        );

        self::add_invalid_test_case(
            $dirpath . 'rules/invalid_token_value_highlight_rules.json',
            'invalid token at rule 0 of state "start" nº0'
        );

        self::add_invalid_test_case(
            $dirpath . 'rules/invalid_default_token_highlight_rules.json',
            'invalid data type for default_token at rule 0 of state "start" nº0'
        );

        self::add_invalid_test_case(
            $dirpath . 'rules/default_token_not_alone_highlight_rules.json',
            'option default_token must be alone at rule 0 of state "start" nº0'
        );

        self::add_invalid_test_case(
            $dirpath . 'general/invalid_json_inheritance_highlight_rules.json',
            'inherit JSON file ' . $dirpath . 'general/dump_highlight_rules.json does not exist'
        );
    }

    /**
     * Method to test if static checks for similarity does not have any error
     */
    public function test_static_check() {
        try {
            $dir = dirname(__FILE__) . '/../similarity/rules';

            $scanarr = scandir($dir);
            $filesarr = array_diff($scanarr, array('.', '..'));

            foreach ($filesarr as $filename) {
                $filename = $dir . '/' . $filename;
                new tokenizer_json($filename, false, true);
            }
        } catch (Exception $exe) {
            $this->fail($exe->getMessage() . "\n");
        }
    }

    /**
     * Method to test expected messages when JSON file is invalid
     */
    public function test_invalid_files() {
        foreach (self::$invalidtestcases as $filename => $mssg) {
            try {
                new tokenizer_json($filename);
            } catch (Exception $exe) {
                $expectedmssg = assertf::get_error($filename, $mssg);
                $this->assertSame($expectedmssg, $exe->getMessage());
                continue;
            }

            $this->fail('An expection was expected');
        }
    }

    /**
     * Method to test tokenizer_json::discard_comments
     */
    public function test_discard_comments() {
        $dir = self::get_test_path() . 'valid/comments';

        $scanarr = scandir($dir);
        $filesarr = array_diff($scanarr, array('.', '..'));

        foreach ($filesarr as $filename) {
            $filename = $dir . '/' . $filename;

            try {
                new tokenizer_json($filename);
            } catch (Exception $exe) {
                $this->fail($exe->getMessage() . "\n");
                break;
            }
        }
    }

    /**
     * Method to test tokenizer_json::merge_json_files when inherit is just one state
     */
    public function test_merge_one_state() {
        $filename = self::get_test_path() . 'valid/merge/merge_one_to_one_state_highlight_rules.json';

        try {
            $tokenizerjson = new tokenizer_json($filename);
            $states = testable_tokenizer_base::get_states_from($tokenizerjson);

            $this->assertTrue(count($states) == 2);

            $this->assertTrue(in_array("start", array_keys($states)));
            $this->assertTrue(count($states["start"]) == 1);
            $this->assertSame("comment", $states["start"][0]->token);
            $this->assertSame("\\/\\/", $states["start"][0]->regex);
            $this->assertSame("text-state", $states["start"][0]->next);

            $this->assertTrue(in_array("text-state", array_keys($states)));
            $this->assertTrue(count($states["text-state"]) == 1);
            $this->assertSame("text", $states["text-state"][0]->token);
            $this->assertSame(".*", $states["text-state"][0]->regex);
        } catch (Exception $exe) {
            $this->fail($exe->getMessage() . "\n");
        }
    }

    /**
     * Method to test tokenizer_json::merge_json_files when inherit is just one state but there are two states at start file
     */
    public function test_merge_two_states_from_root() {
        $filename = self::get_test_path() . 'valid/merge/merge_one_to_two_states_highlight_rules.json';

        try {
            $tokenizerjson = new tokenizer_json($filename);
            $states = testable_tokenizer_base::get_states_from($tokenizerjson);

            $this->assertTrue(count($states) == 3);

            $this->assertTrue(in_array("start", array_keys($states)));
            $this->assertTrue(count($states["start"]) == 1);
            $this->assertSame("comment", $states["start"][0]->token);
            $this->assertSame("\\/\\/", $states["start"][0]->regex);
            $this->assertSame("text-state", $states["start"][0]->next);

            $this->assertTrue(in_array("eol", array_keys($states)));
            $this->assertTrue(count($states["eol"]) == 1);
            $this->assertSame("eol", $states["eol"][0]->token);
            $this->assertSame("\n", $states["eol"][0]->regex);

            $this->assertTrue(in_array("text-state", array_keys($states)));
            $this->assertTrue(count($states["text-state"]) == 1);
            $this->assertSame("text", $states["text-state"][0]->token);
            $this->assertSame(".*", $states["text-state"][0]->regex);
        } catch (Exception $exe) {
            $this->fail($exe->getMessage() . "\n");
        }
    }

    /**
     * Method to test tokenizer_json::merge_json_files when inherit has two states
     */
    public function test_merge_two_states_from_inheritance() {
        $filename = self::get_test_path() . 'valid/merge/merge_two_to_one_states_highlight_rules.json';

        try {
            $tokenizerjson = new tokenizer_json($filename);
            $states = testable_tokenizer_base::get_states_from($tokenizerjson);

            $this->assertTrue(count($states) == 3);

            $this->assertTrue(in_array("start", array_keys($states)));
            $this->assertTrue(count($states["start"]) == 1);
            $this->assertSame("comment", $states["start"][0]->token);
            $this->assertSame("\\/\\/", $states["start"][0]->regex);
            $this->assertSame("text-state", $states["start"][0]->next);

            $this->assertTrue(in_array("eol", array_keys($states)));
            $this->assertTrue(count($states["eol"]) == 1);
            $this->assertSame("eol", $states["eol"][0]->token);
            $this->assertSame("\n", $states["eol"][0]->regex);

            $this->assertTrue(in_array("text-state", array_keys($states)));
            $this->assertTrue(count($states["text-state"]) == 1);
            $this->assertSame("text", $states["text-state"][0]->token);
            $this->assertSame(".*", $states["text-state"][0]->regex);
        } catch (Exception $exe) {
            $this->fail($exe->getMessage() . "\n");
        }
    }

    /**
     * Method to test tokenizer_json::merge_json_files when inherit has the same states
     */
    public function test_merge_same_states_from_inheritance() {
        $filename = self::get_test_path() . 'valid/merge/merge_with_same_states_highlight_rules.json';

        try {
            $tokenizerjson = new tokenizer_json($filename);
            $states = testable_tokenizer_base::get_states_from($tokenizerjson);

            $this->assertTrue(count($states) == 2);

            $this->assertTrue(in_array("start", array_keys($states)));
            $this->assertTrue(count($states["start"]) == 1);
            $this->assertSame("text-state", $states["start"][0]->next);

            $this->assertTrue(in_array("text-state", array_keys($states)));
            $this->assertTrue(count($states["text-state"]) == 2);
            $this->assertSame("comment", $states["text-state"][0]->token);
            $this->assertSame("\\/\\/", $states["text-state"][0]->regex);
            $this->assertSame("text", $states["text-state"][1]->token);
            $this->assertSame(".*", $states["text-state"][1]->regex);
        } catch (Exception $exe) {
            $this->fail($exe->getMessage() . "\n");
        }
    }

    /**
     * Method to test tokenizer_json::prepare_tokenizer for one state
     */
    public function test_prepare_tokenizer_one_state() {
        $filename = self::get_test_path() . 'valid/prepare/prepare_with_one_state_highlight_rules.json';
        $tokenizerjson = new tokenizer_json($filename);

        $regexprs = testable_tokenizer_base::get_regexprs_from($tokenizerjson);
        $matchmappings = testable_tokenizer_base::get_matchmappings_from($tokenizerjson);

        $this->assertTrue(count($regexprs) == 1);
        $this->assertTrue(isset($regexprs["start"]));
        $this->assertSame("/(\/\/)|(\/\*)|($)/", $regexprs["start"]);

        $this->assertTrue(count($matchmappings) == 1);
        $this->assertTrue(isset($matchmappings["start"]));
        $this->assertTrue(count($matchmappings["start"]) == 3);
        $this->assertTrue(isset($matchmappings["start"]["default_token"]));
        $this->assertSame("text", $matchmappings["start"]["default_token"]);
        $this->assertTrue(isset($matchmappings["start"][0]));
        $this->assertSame(0, $matchmappings["start"][0]);
        $this->assertTrue(isset($matchmappings["start"][1]));
        $this->assertSame(1, $matchmappings["start"][1]);
    }

    /**
     * Method to test tokenizer_json::prepare_tokenizer for two states
     */
    public function test_prepare_tokenizer_two_states() {
        $filename = self::get_test_path() . 'valid/prepare/prepare_with_two_states_highlight_rules.json';
        $tokenizerjson = new tokenizer_json($filename);

        $regexprs = testable_tokenizer_base::get_regexprs_from($tokenizerjson);
        $matchmappings = testable_tokenizer_base::get_matchmappings_from($tokenizerjson);

        $this->assertTrue(count($regexprs) == 2);
        $this->assertTrue(isset($regexprs["start"]));
        $this->assertSame("/(\/\/)|(\/\*)|($)/", $regexprs["start"]);
        $this->assertTrue(isset($regexprs["another_start"]));
        $this->assertSame("/(\/\/)|(\/\*)|($)/", $regexprs["another_start"]);

        $this->assertTrue(count($matchmappings) == 2);
        $this->assertTrue(isset($matchmappings["start"]));
        $this->assertTrue(count($matchmappings["start"]) == 3);
        $this->assertTrue(isset($matchmappings["start"]["default_token"]));
        $this->assertSame("text", $matchmappings["start"]["default_token"]);
        $this->assertTrue(isset($matchmappings["start"][0]));
        $this->assertSame(0, $matchmappings["start"][0]);
        $this->assertTrue(isset($matchmappings["start"][1]));
        $this->assertSame(1, $matchmappings["start"][1]);

        $this->assertTrue(isset($matchmappings["another_start"]));
        $this->assertTrue(count($matchmappings["another_start"]) == 3);
        $this->assertTrue(isset($matchmappings["another_start"]["default_token"]));
        $this->assertSame("text", $matchmappings["another_start"]["default_token"]);
        $this->assertTrue(isset($matchmappings["another_start"][0]));
        $this->assertSame(0, $matchmappings["another_start"][0]);
        $this->assertTrue(isset($matchmappings["another_start"][1]));
        $this->assertSame(1, $matchmappings["another_start"][1]);
    }

    /**
     * Method to test tokenizer_json::prepare_tokenizer with capturing groups
     */
    public function test_prepare_tokenizer_capturing_groups() {
        $filename = self::get_test_path() . 'valid/prepare/prepare_with_groups_highlight_rules.json';
        $tokenizerjson = new tokenizer_json($filename);

        $regexprs = testable_tokenizer_base::get_regexprs_from($tokenizerjson);
        $matchmappings = testable_tokenizer_base::get_matchmappings_from($tokenizerjson);

        $this->assertTrue(count($regexprs) == 1);
        $this->assertTrue(isset($regexprs["start"]));
        $this->assertSame("/(\/\/)|((?:.*)(?:b))|($)/", $regexprs["start"]);

        $this->assertTrue(count($matchmappings) == 1);
        $this->assertTrue(isset($matchmappings["start"]));
        $this->assertTrue(count($matchmappings["start"]) == 3);
        $this->assertTrue(isset($matchmappings["start"]["default_token"]));
        $this->assertSame("comment", $matchmappings["start"]["default_token"]);
        $this->assertTrue(isset($matchmappings["start"][0]));
        $this->assertSame(0, $matchmappings["start"][0]);
        $this->assertTrue(isset($matchmappings["start"][1]));
        $this->assertSame(1, $matchmappings["start"][1]);
    }

    /**
     * Method to test tokenizer_json::prepare_tokenizer with more rules
     */
    public function test_prepare_tokenizer_more_rules() {
        $filename = self::get_test_path() . 'valid/prepare/prepare_with_more_rules_highlight_rules.json';
        $tokenizerjson = new tokenizer_json($filename);

        $regexprs = testable_tokenizer_base::get_regexprs_from($tokenizerjson);
        $matchmappings = testable_tokenizer_base::get_matchmappings_from($tokenizerjson);

        $this->assertTrue(count($regexprs) == 3);
        $this->assertTrue(isset($regexprs["start"]));
        $this->assertSame("/(\/\/)|((?:void)(?:[a-z]+(?:[a-zA-Z0-9]|_)*)(?:\()(?:\)))|($)/", $regexprs["start"]);
        $this->assertTrue(isset($regexprs["statement"]));
        $this->assertSame("/(([a-z]+([a-zA-Z0-9]|_)*))|($)/", $regexprs["statement"]);
        $this->assertTrue(isset($regexprs["comment"]));
        $this->assertSame("/(\/\/)|($)/", $regexprs["comment"]);

        $this->assertTrue(count($matchmappings) == 3);
        $this->assertTrue(isset($matchmappings["start"]));
        $this->assertTrue(count($matchmappings["start"]) == 3);
        $this->assertTrue(isset($matchmappings["start"]["default_token"]));
        $this->assertSame("comment.line.double-slash", $matchmappings["start"]["default_token"]);
        $this->assertTrue(isset($matchmappings["start"][0]));
        $this->assertSame(0, $matchmappings["start"][0]);
        $this->assertTrue(isset($matchmappings["start"][1]));
        $this->assertSame(1, $matchmappings["start"][1]);

        $this->assertTrue(count($matchmappings["statement"]) == 2);
        $this->assertTrue(isset($matchmappings["statement"]["default_token"]));
        $this->assertSame("text", $matchmappings["statement"]["default_token"]);
        $this->assertTrue(isset($matchmappings["statement"][0]));
        $this->assertSame(0, $matchmappings["statement"][0]);

        $this->assertTrue(count($matchmappings["comment"]) == 2);
        $this->assertTrue(isset($matchmappings["comment"]["default_token"]));
        $this->assertSame("comment", $matchmappings["comment"]["default_token"]);
        $this->assertTrue(isset($matchmappings["comment"][0]));
        $this->assertSame(0, $matchmappings["comment"][0]);
    }

    /**
     * Method to test tokenizer_json::prepare_tokenizer with complex matching
     */
    public function test_prepare_tokenizer_complex_matching() {
        $filename = self::get_test_path() . 'valid/prepare/prepare_with_complex_matching_highlight_rules.json';
        $tokenizerjson = new tokenizer_json($filename);

        $regexprs = testable_tokenizer_base::get_regexprs_from($tokenizerjson);
        $matchmappings = testable_tokenizer_base::get_matchmappings_from($tokenizerjson);

        $this->assertTrue(count($regexprs) == 1);
        $this->assertTrue(isset($regexprs["start"]));

        $expected = "/([+-]?\d[\d_]*((\.[\d_]*)?([eE][+-]?[[0-9]_]+)?)?[LlSsDdFfYy]?\b)|((?:true|false)\b)|";
        $expected .= "((?:open(?:\s+))?module(?=\s*\w))|($)/";
        $this->assertSame($expected, $regexprs["start"]);

        $this->assertTrue(count($matchmappings) == 1);
        $this->assertTrue(count($matchmappings["start"]) == 4);
        $this->assertTrue(isset($matchmappings["start"]["default_token"]));
        $this->assertSame("text", $matchmappings["start"]["default_token"]);
        $this->assertTrue(isset($matchmappings["start"][0]));
        $this->assertSame(0, $matchmappings["start"][0]);
        $this->assertTrue(isset($matchmappings["start"][4]));
        $this->assertSame(1, $matchmappings["start"][4]);
        $this->assertTrue(isset($matchmappings["start"][5]));
        $this->assertSame(2, $matchmappings["start"][5]);
    }

    /**
     * Include a new test case for invalid highlight rules
     *
     * @param string $testfilename highlight rules file
     * @param string $expectedmssg expected error message
     */
    protected static function add_invalid_test_case(string $testfilename, string $expectedmssg): void {
        self::$invalidtestcases[$testfilename] = $expectedmssg;
    }

    /**
     * Get path of directory that contains all test files
     *
     * @return string
     */
    protected static function get_test_path(): string {
        return dirname(__FILE__) . '/vpl_tokenizer/';
    }
}
