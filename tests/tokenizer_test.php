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
 * Unit tests for mod_vpl\tokenizer\tokenizer
 *
 * @package mod_vpl
 * @copyright David Parreño Barbuzano
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author  David Parreño Barbuzano <david.parreno101@alu.ulpgc.es>
 */
namespace mod_vpl;

defined('MOODLE_INTERNAL') || die();

use mod_vpl\tokenizer\tokenizer;
use mod_vpl\util\assertf;
use Exception;

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');

/**
 * Unit tests for \mod_vpl\tokenizer\tokenizer class.
 *
 * @group mod_vpl
 * @group mod_vpl_tokenizer
 * @covers \mod_vpl\tokenizer\tokenizer
 */
class tokenizer_test extends \advanced_testcase {
    protected static array $testcases;

    /**
     * Prepare test cases before the execution
     */
    public static function setUpBeforeClass(): void {
        $dirpath = self::get_test_path() . 'invalid/';

        self::$testcases = array(
            $dirpath . 'dump_test.json' => 'file ' . $dirpath  . 'dump_test.json must exist',
            $dirpath . 'general/not_good_suffix.json' => $dirpath . 'general/not_good_suffix.json must have suffix _highlight_rules.json',
            $dirpath . 'general/empty_highlight_rules.json' => 'file ' . $dirpath . 'general/empty_highlight_rules.json is empty',
            $dirpath . 'general/undefined_option_highlight_rules.json' => 'invalid options: example',
            $dirpath . 'general/invalid_check_rules_highlight_rules.json' => '"check_rules" option must be a boolean',
            $dirpath . 'general/invalid_extension_no_string_highlight_rules.json' => '"extension" option must be a string or an array of strings',
            $dirpath . 'general/invalid_extension_no_array_highlight_rules.json' => '"extension" option must be a string or an array of strings',
            $dirpath . 'general/invalid_extension_no_dot_highlight_rules.json' => 'extension c must start with .',
            $dirpath . 'general/invalid_inherit_rules_highlight_rules.json' => '"inherit_rules" option must be a string',
            $dirpath . 'states/invalid_data_states_highlight_rules.json' => '"states" option must be an array',
            $dirpath . 'states/states_with_no_name_highlight_rules.json' => 'state 0 must have a name',
            $dirpath . 'states/state_not_object_highlight_rules.json' => 'state 0 must be an object',
            $dirpath . 'states/invalid_state_name_highlight_rules.json' => 'name for state 0 must be a string',
            $dirpath . 'states/invalid_data_state_highlight_rules.json' => 'data section for state "state1" nº0 must be an array',
            $dirpath . 'states/state_with_no_data_highlight_rules.json' => 'state "state1" nº0 must have a data section',
            $dirpath . 'states/duplicated_state_name_highlight_rules.json' => 'name "state1" of state 2 is duplicated',
            $dirpath . 'states/one_state_with_no_name_highlight_rules.json' => 'state 1 must have a name',
            $dirpath . 'rules/invalid_rule_highlight_rules.json' => 'rule 0 of state "state1" nº0 must be an object',
            $dirpath . 'rules/invalid_rule_option_value_highlight_rules.json' => 'invalid data type for token at rule 0 of state "state1" nº0',
            $dirpath . 'rules/undefined_rule_option_highlight_rules.json' => 'invalid option example at rule 0 of state "state1" nº0',
            $dirpath . 'rules/invalid_next_highlight_rules.json' => 'invalid data type for next at rule 0 of state "state1" nº0 (next: 0)',
            $dirpath . 'rules/invalid_next_option_highlight_rules.json' => 'invalid data type for token at rule 0 of state "state1" nº0 (next: 0)',
            $dirpath . 'rules/invalid_next_one_rule_highlight_rules.json' => 'invalid data type for next at rule 1 of state "state1" nº0 (next: 0)',
            $dirpath . 'rules/invalid_sub_next_highlight_rules.json' => 'invalid data type for next at rule 0 of state "state1" nº0 (next: 0)',
            $dirpath . 'rules/regex_not_found_highlight_rules.json' => 'option token must be defined next to regex at rule 0 of state "state1" nº0',
            $dirpath . 'rules/token_not_found_highlight_rules.json' => 'option regex must be defined next to token at rule 0 of state "state1" nº0',
            $dirpath . 'rules/invalid_token_value_highlight_rules.json' => 'invalid token at rule 0 of state "root" nº0',
            $dirpath . 'general/invalid_json_inheritance_highlight_rules.json' => 'inherit JSON file ' . $dirpath . 'general/dump_highlight_rules.json does not exist'

        );
    }

    /**
     * Method to test if static checks for similarity does not have any error
     */
    public function test_static_check() {
        try {
            tokenizer::check_rules_syntax();
        } catch (Exception $exe) {
            print_r($exe->getMessage() . "\n");
            $this->fail();
        }
    }

    /**
     * Method to test expected messages when JSON file is invalid
     */
    public function test_invalid_files() {
        foreach (self::$testcases as $filename => $mssg) {
            try {
                new tokenizer($filename);
            } catch (Exception $exe) {
                $expectedmssg = assertf::get_error($filename, $mssg);
                $this->assertSame($expectedmssg, $exe->getMessage());
                continue;
            }

            $this->fail();
        }
    }

    /**
     * Method to test tokenizer::discard_comments
     */
    public function test_discard_comments() {
        $dir = self::get_test_path() . 'valid/comments';

        $scanarr = scandir($dir);
        $filesarr = array_diff($scanarr, array('.', '..'));

        foreach ($filesarr as $filename) {
            $filename = $dir . '/' . $filename;

            try {
                new tokenizer($filename);
            } catch (Exception $exe) {
                echo($exe->getMessage() . "\n");
                $this->fail();
                break;
            }
        }
    }

    /**
     * Method to test tokenizer::merge_json_files when inherit is just one state
     */
    public function test_merge_one_state() {
        $filename = self::get_test_path() . 'valid/merge/merge_one_to_one_state_highlight_rules.json';

        try {
            $tokenizer = new tokenizer($filename);
            $states = $tokenizer->get_states();

            $this->assertTrue(count($states) == 2);

            $this->assertTrue(in_array("start", array_keys($states)));
            $this->assertSame("start", $states["start"]->name);
            $this->assertTrue(count($states["start"]->data) == 1);
            $this->assertSame("comment", $states["start"]->data[0]->token);
            $this->assertSame("//", $states["start"]->data[0]->regex);
            $this->assertSame("text-state", $states["start"]->data[0]->next);

            $this->assertTrue(in_array("text-state", array_keys($states)));
            $this->assertSame("text-state", $states["text-state"]->name);
            $this->assertTrue(count($states["text-state"]->data) == 1);
            $this->assertSame("text", $states["text-state"]->data[0]->token);
            $this->assertSame(".*", $states["text-state"]->data[0]->regex);
        } catch (Exception $exe) {
            echo($exe->getMessage() . "\n");
            $this->fail();
        }
    }

    /**
     * Method to test tokenizer::merge_json_files when inherit is just one state but there are two states at start file
     */
    public function test_merge_two_states_from_root() {
        $filename = self::get_test_path() . 'valid/merge/merge_one_to_two_states_highlight_rules.json';

        try {
            $tokenizer = new tokenizer($filename);
            $states = $tokenizer->get_states();

            $this->assertTrue(count($states) == 3);

            $this->assertTrue(in_array("start", array_keys($states)));
            $this->assertSame("start", $states["start"]->name);
            $this->assertTrue(count($states["start"]->data) == 1);
            $this->assertSame("comment", $states["start"]->data[0]->token);
            $this->assertSame("//", $states["start"]->data[0]->regex);
            $this->assertSame("text-state", $states["start"]->data[0]->next);

            $this->assertTrue(in_array("eol", array_keys($states)));
            $this->assertSame("eol", $states["eol"]->name);
            $this->assertTrue(count($states["eol"]->data) == 1);
            $this->assertSame("eol", $states["eol"]->data[0]->token);
            $this->assertSame("\n", $states["eol"]->data[0]->regex);

            $this->assertTrue(in_array("text-state", array_keys($states)));
            $this->assertSame("text-state", $states["text-state"]->name);
            $this->assertTrue(count($states["text-state"]->data) == 1);
            $this->assertSame("text", $states["text-state"]->data[0]->token);
            $this->assertSame(".*", $states["text-state"]->data[0]->regex);
        } catch (Exception $exe) {
            echo($exe->getMessage() . "\n");
            $this->fail();
        }
    }

    /**
     * Method to test tokenizer::merge_json_files when inherit has two states
     */
    public function test_merge_two_states_from_inheritance() {
        $filename = self::get_test_path() . 'valid/merge/merge_two_to_one_states_highlight_rules.json';

        try {
            $tokenizer = new tokenizer($filename);
            $states = $tokenizer->get_states();

            $this->assertTrue(count($states) == 3);

            $this->assertTrue(in_array("start", array_keys($states)));
            $this->assertSame("start", $states["start"]->name);
            $this->assertTrue(count($states["start"]->data) == 1);
            $this->assertSame("comment", $states["start"]->data[0]->token);
            $this->assertSame("//", $states["start"]->data[0]->regex);
            $this->assertSame("text-state", $states["start"]->data[0]->next);

            $this->assertTrue(in_array("eol", array_keys($states)));
            $this->assertSame("eol", $states["eol"]->name);
            $this->assertTrue(count($states["eol"]->data) == 1);
            $this->assertSame("eol", $states["eol"]->data[0]->token);
            $this->assertSame("\n", $states["eol"]->data[0]->regex);

            $this->assertTrue(in_array("text-state", array_keys($states)));
            $this->assertSame("text-state", $states["text-state"]->name);
            $this->assertTrue(count($states["text-state"]->data) == 1);
            $this->assertSame("text", $states["text-state"]->data[0]->token);
            $this->assertSame(".*", $states["text-state"]->data[0]->regex);
        } catch (Exception $exe) {
            echo($exe->getMessage() . "\n");
            $this->fail();
        }
    }

    /**
     * Method to test tokenizer::merge_json_files when inherit has the same states
     */
    public function test_merge_same_states_from_inheritance() {
        $filename = self::get_test_path() . 'valid/merge/merge_with_same_states_highlight_rules.json';

        try {
            $tokenizer = new tokenizer($filename);
            $states = $tokenizer->get_states();

            $this->assertTrue(count($states) == 2);

            $this->assertTrue(in_array("start", array_keys($states)));
            $this->assertSame("start", $states["start"]->name);
            $this->assertTrue(count($states["start"]->data) == 1);
            $this->assertSame("text-state", $states["start"]->data[0]->next);

            $this->assertTrue(in_array("text-state", array_keys($states)));
            $this->assertSame("text-state", $states["text-state"]->name);
            $this->assertTrue(count($states["text-state"]->data) == 2);
            $this->assertSame("comment", $states["text-state"]->data[0]->token);
            $this->assertSame("//", $states["text-state"]->data[0]->regex);
            $this->assertSame("text", $states["text-state"]->data[1]->token);
            $this->assertSame(".*", $states["text-state"]->data[1]->regex);
        } catch (Exception $exe) {
            echo($exe->getMessage() . "\n");
            $this->fail();
        }
    }

    private static function get_test_path(): string {
        return dirname(__FILE__) . '/vpl_tokenizer/';
    }
}
