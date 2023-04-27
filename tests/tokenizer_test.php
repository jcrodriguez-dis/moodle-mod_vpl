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

use mod_vpl\tokenizer\token;
use mod_vpl\tokenizer\tokenizer;
use mod_vpl\tokenizer\token_type;
use mod_vpl\tokenizer\tokenizer_factory;
use mod_vpl\util\assertf;
use Exception;

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');

/**
 * Unit tests for \mod_vpl\tokenizer\tokenizer class.
 *
 * @group mod_vpl
 * @group mod_vpl_vplt
 * @group mod_vpl_tokenizer
 * @group mod_vpl_tokenizer_ext
 * @covers \mod_vpl\tokenizer\tokenizer
 */
class tokenizer_test extends \advanced_testcase {
    /**
     * Location for all JSON files which would be tested.
     *
     * Path would be defined using current location, so do not
     * move this file unless you know what you're doing
     *
     * @return string
     */
    protected static function gettestpath(): string {
        return dirname(__FILE__) . '/vpl_tokenizer/';
    }

    /**
     * List of invalid test cases
     *
     * key: input value to test
     * value: expected error message to catch
     */
    protected static array $invalidtestcases;

    /**
     * List of invalid test cases for get_all_tokens
     *
     * key: path of testable JSON file
     * value: expected error message to catch
     */
    protected static array $invalidpreparsecases;

    /**
     * List of test cases for tokenizer::init_set_max_token_count
     *
     * key: path of a testable JSON file
     * value: expected value for max_token_count
     */
    protected static array $maxtokencountcases;

    /**
     * List of test cases for tokenizer::init_override_tokens
     *
     * key: path of a testable JSON file
     * value: expected list of override tokens
     */
    protected static array $overridetokenscases;

    /**
     * List of test cases for tokenizer::apply_inheritance
     *
     * key: path of a testable JSON file
     * value: expected list of states
     */
    protected static array $mergetestcases;

    /**
     * List of test cases for tokenizer::prepare_tokenizer
     *
     * key: path of a testable JSON file
     * value: expected regexprs and matchmappings
     */
    protected static array $preparetestcases;

    /**
     * List of valid test cases for tokenizer::get_line_tokens
     *
     * key: path of a testable JSON file
     * value: input and expected result of tokenizer::get_line_tokens
     */
    protected static array $getlinetokenstestcases;

    /**
     * List of valid test cases for tokenizer::get_line_tokens when overflow has been detected
     *
     * key: path of a testable JSON file
     * value: input and expected result of tokenizer::get_line_tokens
     */
    protected static array $getlinetokenoverflowstestcases;

    /**
     * List of test cases for tokenizer::get_all_tokens
     *
     * key: path of a testable JSON file
     * value: expected list of tokens and state
     */
    protected static array $preparsetestcases;

    /**
     * List of test cases for tokenizer::parse
     *
     * key: path of a testable JSON file
     * value: expected list of tokens
     */
    protected static array $parsetestcases;

    /**
     * Prepare test cases before the execution
     */
    public static function setUpBeforeClass(): void {
        self::setup_invalid_cases();
        self::setup_max_token_count_cases();
        self::setup_override_tokens_cases();
        self::setup_merge_cases();
        self::setup_prepare_cases();
        self::setup_get_line_tokens_cases();
        self::setup_preparse_cases();
        self::setup_parse_cases();
    }

    /**
     * Method to test tokenizer::discard_comments
     */
    public function test_discard_comments() {
        $dir = self::gettestpath() . 'valid/comments';

        $scanarr = scandir($dir);
        $filesarr = array_diff($scanarr, array('.', '..'));

        foreach ($filesarr as $filename) {
            $filename = $dir . '/' . $filename;

            try {
                new tokenizer($filename);
            } catch (Exception $exe) {
                $this->fail($exe->getMessage() . "\n");
                break;
            }
        }
    }

    /**
     * Method to test tokenizer::__construct with files located at similary folder
     *
     * This test also provides output files at /tests/vpl_tokenizer/behat
     * with expected tokens for behat tests just for manual check.
     */
    public function test_static_check() {
        try {
            $dir = dirname(__FILE__) . '/../similarity/tokenizer_rules';

            $scanarr = scandir($dir);
            $filesarr = array_diff($scanarr, array('.', '..'));

            foreach ($filesarr as $filename) {
                if (!is_dir($dir . '/' . $filename)) {
                    $namelang = preg_split("/_/", $filename)[0];

                    $tokenizer = tokenizer_factory::get($namelang, true);
                    $extensions = testable_tokenizer::get_extensions($tokenizer);
                    self::get_behat($tokenizer, $extensions, true);

                    $tokenizer = tokenizer_factory::get($namelang, false);
                    self::get_behat($tokenizer, $extensions, false);
                }
            }
        } catch (Exception $exe) {
            $this->fail($exe->getMessage() . "\n");
        }
    }

    /**
     * Method to test tokenizer::init with invalid files
     */
    public function test_invalid_files() {
        foreach (self::$invalidtestcases as $filename => $mssg) {
            try {
                new tokenizer($filename);
            } catch (Exception $exe) {
                $expectedmssg = assertf::get_error($filename, $mssg);
                $this->assertSame($expectedmssg, $exe->getMessage());
                continue;
            }

            $this->fail('An expection was expected');
        }
    }

    /**
     * Method to test tokenizer::set_max_token_count
     */
    public function test_set_max_token_count() {
        foreach (self::$maxtokencountcases as $filename => $expectedmaxtokencount) {
            $tokenizer = new tokenizer($filename);
            $maxtokencount = testable_tokenizer::get_max_token_count_from($tokenizer);
            $this->assertSame($expectedmaxtokencount, $maxtokencount);
        }
    }

    /**
     * Method to test tokenizer::override_tokens
     */
    public function test_override_tokens() {
        foreach (self::$overridetokenscases as $filename => $expectedtokens) {
            $tokenizer = new tokenizer($filename);
            $availabletokens = testable_tokenizer::get_available_tokens($tokenizer);

            foreach ($expectedtokens as $tokename => $tokentype) {
                $this->assertTrue(isset($availabletokens[$tokename]));
                $this->assertSame($tokentype, $availabletokens[$tokename]);
            }
        }
    }

    /**
     * Method to test tokenizer::apply_inheritance
     */
    public function test_apply_inheritance() {
        foreach (self::$mergetestcases as $filename => $expectedstates) {
            $tokenizer = new tokenizer($filename);
            $states = testable_tokenizer_base::get_states_from($tokenizer);

            $this->assertTrue(count($expectedstates) === count($states));

            foreach ($expectedstates as $expectedstatename => $expectedrules) {
                $this->assertTrue(in_array($expectedstatename, array_keys($states)));
                $this->assertTrue(count($expectedrules) === count($states[$expectedstatename]));

                foreach ($states[$expectedstatename] as $rule) {
                    $cond = testable_tokenizer_base::contains_rule($expectedrules, $rule);
                    $this->assertTrue($cond === true);
                }
            }
        }
    }

    /**
     * Method to test tokenizer::prepare_tokenizer
     */
    public function test_prepare_tokenizer() {
        foreach (self::$preparetestcases as $filename => $expectedresult) {
            $expectecmatchmappings = $expectedresult['matchmappings'];
            $expectedregexprs = $expectedresult['regexprs'];

            $tokenizer = new tokenizer($filename);
            $regexprs = testable_tokenizer_base::get_regexprs_from($tokenizer);
            $matchmappings = testable_tokenizer_base::get_matchmappings_from($tokenizer);

            $this->assertTrue(count($regexprs) === count($expectedregexprs));

            foreach ($regexprs as $statename => $regex) {
                $this->assertTrue(isset($expectedregexprs[$statename]));
                $this->assertSame($expectedregexprs[$statename], $regex);
            }

            $this->assertTrue(count($matchmappings) === count($expectecmatchmappings));

            foreach ($matchmappings as $statename => $map) {
                $this->assertTrue(isset($expectecmatchmappings[$statename]));

                $expectedmap = $expectecmatchmappings[$statename];
                $this->assertTrue(count($map) === count($expectedmap));

                foreach ($map as $key => $value) {
                    $this->assertTrue(isset($expectedmap[$key]));
                    $this->assertSame($expectedmap[$key], $value);
                }
            }
        }
    }

    /**
     * Method to test tokenizer::get_line_tokens
     */
    public function test_get_line_tokens() {
        foreach (self::$getlinetokenstestcases as $filename => $expectedresult) {
            $input = $expectedresult['input'];
            $expectedresult = $expectedresult['output'];

            $tokenizer = new tokenizer($filename);
            $result = $tokenizer->get_line_tokens($input, "", 0);

            $this->assertTrue(count($result) === 2);
            $this->assertSame($expectedresult['state'], $result['state']);
            $this->assertTrue(count($result['tokens']) === count($expectedresult['tokens']));

            for ($i = 0; $i < count($result['tokens']); $i++) {
                $this->assertTrue($result['tokens'][$i]->equals_to($expectedresult['tokens'][$i]));
            }
        }
    }

    /**
     * Method to test tokenizer::get_line_tokens when startstate is invalid
     */
    public function test_get_line_tokens_with_invalid_startstate() {
        foreach (self::$getlinetokenstestcases as $filename => $expectedresult) {
            $input = $expectedresult['input'];
            $expectedresult = $expectedresult['output'];

            $tokenizer = new tokenizer($filename);
            $result = $tokenizer->get_line_tokens($input, 'stupid_startstate', 0);

            $this->assertTrue(count($result) === 2);
            $this->assertSame($expectedresult['state'], $result['state']);
            $this->assertTrue(count($result['tokens']) === count($expectedresult['tokens']));

            for ($i = 0; $i < count($result['tokens']); $i++) {
                $this->assertTrue($result['tokens'][$i]->equals_to($expectedresult['tokens'][$i]));
            }
        }
    }

    /**
     * Method to test tokenizer::get_line_tokens when overflow has been detected
     */
    public function test_get_line_tokens_with_overflow() {
        foreach (self::$getlinetokenoverflowstestcases as $filename => $expectedresult) {
            $input = $expectedresult['input'];
            $expectedresult = $expectedresult['output'];

            $tokenizer = new tokenizer($filename);
            $tokenizer->set_max_token_count($input['max_token_count']);
            $result = $tokenizer->get_line_tokens($input['value'], "", 0);

            $this->assertTrue(count($result) === 2);
            $this->assertSame($expectedresult['state'], $result['state']);
            $this->assertTrue(count($result['tokens']) === count($expectedresult['tokens']));

            for ($i = 0; $i < count($result['tokens']); $i++) {
                $this->assertTrue($result['tokens'][$i]->equals_to($expectedresult['tokens'][$i]));
            }
        }
    }

    /**
     * Method to test tokenizer::get_all_tokens when parameters are invalid
     */
    public function test_invalid_get_all_tokens() {
        foreach (self::$invalidpreparsecases as $filename => $expectedresult) {
            $input = $expectedresult['input'];
            $expectedresult = $expectedresult['output'];
            $tokenizer = new tokenizer($filename);

            try {
                $tokenizer->get_all_tokens($input);
            } catch (Exception $exe) {
                $expectedmssg = assertf::get_error('default', $expectedresult);
                $this->assertSame($expectedmssg, $exe->getMessage());
                continue;
            }

            $this->fail('An expection was expected');
        }
    }

    /**
     * Method to test tokenizer::get_all_tokens
     */
    public function test_get_all_tokens() {
        foreach (self::$preparsetestcases as $filename => $expectedresult) {
            $input = $expectedresult['input'];
            $expectedresult = $expectedresult['output'];

            $tokenizer = new tokenizer($filename);
            $result = $tokenizer->get_all_tokens($input);

            foreach ($result as $k => $valueforline) {
                $this->assertEquals(2, count($valueforline));
                $this->assertSame($expectedresult[$k]['state'], $valueforline['state']);
                $expectedtokens = $expectedresult[$k]['tokens'];
                $resulttokens = $valueforline['tokens'];
                $this->assertEquals(count($expectedtokens), count($resulttokens));

                for ($i = 0; $i < count($resulttokens); $i++) {
                    $this->assertEquals($expectedtokens[$i], $resulttokens[$i], "File $filename $k");
                }
            }
        }
    }

    /**
     * Method to test tokenizer::parse
     */
    public function test_parse() {
        foreach (self::$parsetestcases as $filename => $expectedresult) {
            $input = $expectedresult['input'];
            $expectedresult = $expectedresult['output'];

            $tokenizer = new tokenizer($filename);
            $result = $tokenizer->parse($input);

            $this->assertTrue(count($result) === count($expectedresult));
            $this->assertTrue(count($tokenizer->get_tokens()) === count($expectedresult));

            for ($i = 0; $i < count($expectedresult); $i++) {
                $this->assertTrue($result[$i]->equals_to($expectedresult[$i]));
                $this->assertTrue($tokenizer->get_tokens()[$i]->equals_to($expectedresult[$i]));
            }
        }
    }

    private static function setup_invalid_cases(): void {
        self::$invalidtestcases = array(
            self::gettestpath() . 'invalid/dump_test.json' => (
                'file ' . self::gettestpath()  . 'invalid/dump_test.json must exist'
            ),
            self::gettestpath() . 'invalid/general/not_good_suffix.json' => (
                self::gettestpath() . 'invalid/general/not_good_suffix.json' . ' must have suffix _tokenizer_rules.json'
            ),
            self::gettestpath() . 'invalid/general/empty_tokenizer_rules.json' => (
                'file ' . self::gettestpath() . 'invalid/general/empty_tokenizer_rules.json' . ' is empty'
            ),
            self::gettestpath() . 'invalid/general/undefined_option_tokenizer_rules.json' => (
                'invalid options: example'
            ),
            self::gettestpath() . 'invalid/general/invalid_check_rules_tokenizer_rules.json' => (
                '"check_rules" option must be a boolean'
            ),
            self::gettestpath() . 'invalid/general/invalid_max_token_count_tokenizer_rules.json' => (
                '"max_token_count" option must be numeric'
            ),
            self::gettestpath() . 'invalid/general/invalid_number_max_token_count_tokenizer_rules.json' => (
                '"max_token_count" option must be a positive integer'
            ),
            self::gettestpath() . 'invalid/general/invalid_name_tokenizer_rules.json' => (
                '"name" option must be a string'
            ),
            self::gettestpath() . 'invalid/general/invalid_extension_no_string_tokenizer_rules.json' => (
                '"extension" option must be a string or an array of strings'
            ),
            self::gettestpath() . 'invalid/general/invalid_extension_no_array_tokenizer_rules.json' => (
                '"extension" option must be a string or an array of strings'
            ),
            self::gettestpath() . 'invalid/general/invalid_extension_no_dot_tokenizer_rules.json' => (
                'extension c must start with .'
            ),
            self::gettestpath() . 'invalid/general/invalid_inherit_rules_tokenizer_rules.json' => (
                '"inherit_rules" option must be a string'
            ),
            self::gettestpath() . 'invalid/states/invalid_data_states_tokenizer_rules.json' => (
                '"states" option must be an object'
            ),
            self::gettestpath() . 'invalid/states/states_with_no_name_tokenizer_rules.json' => (
                'state 0 must have a name'
            ),
            self::gettestpath() . 'invalid/states/state_not_object_tokenizer_rules.json' => (
                'state 0 must be an array'
            ),
            self::gettestpath() . 'invalid/states/one_state_with_no_name_tokenizer_rules.json' => (
                'state 1 must have a name'
            ),
            self::gettestpath() . 'invalid/rules/invalid_rule_tokenizer_rules.json' => (
                'rule 0 of state "state1" nº0 must be an object'
            ),
            self::gettestpath() . 'invalid/rules/invalid_rule_option_value_tokenizer_rules.json' => (
                'invalid data type for token at rule 0 of state "state1" nº0'
            ),
            self::gettestpath() . 'invalid/rules/undefined_rule_option_tokenizer_rules.json' => (
                'invalid option example at rule 0 of state "state1" nº0'
            ),
            self::gettestpath() . 'invalid/rules/invalid_next_tokenizer_rules.json' => (
                'invalid data type for next at rule 0 of state "state1" nº0'
            ),
            self::gettestpath() . 'invalid/rules/regex_not_found_tokenizer_rules.json' => (
                'option token must be defined next to regex at rule 0 of state "state1" nº0'
            ),
            self::gettestpath() . 'invalid/rules/token_not_found_tokenizer_rules.json' => (
                'option regex must be defined next to token at rule 0 of state "state1" nº0'
            ),
            self::gettestpath() . 'invalid/rules/invalid_token_value_tokenizer_rules.json' => (
                'invalid token at rule 0 of state "start" nº0'
            ),
            self::gettestpath() . 'invalid/rules/invalid_default_token_tokenizer_rules.json' => (
                'invalid data type for default_token at rule 0 of state "start" nº0'
            ),
            self::gettestpath() . 'invalid/rules/default_token_not_alone_tokenizer_rules.json' => (
                'option default_token must be alone at rule 0 of state "start" nº0'
            ),
            self::gettestpath() . 'invalid/general/invalid_json_inheritance_tokenizer_rules.json' => (
                'inherit JSON file ' . self::gettestpath() . 'invalid/general/dump_tokenizer_rules.json does not exist'
            ),
            self::gettestpath() . 'invalid/general/invalid_override_tokens_tokenizer_rules.json' => (
                '"override_tokens" option must be an object'
            ),
            self::gettestpath() . 'invalid/general/invalid_token_at_override_tokens_tokenizer_rules.json' => (
                'this_is_not_a_good_token_name does not exist'
            ),
            self::gettestpath() . 'invalid/general/vpl_type_not_overrided_tokenizer_rules.json' => (
                'vpl_literal could not be overrided'
            )
        );

        self::$invalidpreparsecases = array(
            self::gettestpath() . 'invalid/general/invalid_file_at_preparse_tokenizer_rules.json' => (
                [
                    'input' => 'dump_test.js',
                    'output' => 'file dump_test.js does not exist',
                ]
            ),
            self::gettestpath() . 'invalid/general/invalid_ext_at_preparse_tokenizer_rules.json' => (
                [
                    'input' => self::gettestpath() . 'invalid/general/test_ext.java',
                    'output' => self::gettestpath() . 'invalid/general/test_ext.java must end with one of the extensions .c,.h',
                ]
            )
        );
    }

    private static function setup_max_token_count_cases(): void {
        self::$maxtokencountcases = array(
            self::gettestpath() . 'valid/max_token_count/max_token_count_zero_tokenizer_rules.json' => 0,
            self::gettestpath() . 'valid/max_token_count/max_token_count_ten_tokenizer_rules.json' => 10
        );
    }

    private static function setup_override_tokens_cases(): void {
        self::$overridetokenscases = array(
            self::gettestpath() . 'valid/override_tokens/empty_override_tokens_tokenizer_rules.json' => [ ],
            self::gettestpath() . 'valid/override_tokens/one_override_token_tokenizer_rules.json' => (
                [
                    'comment' => token_type::LITERAL
                ]
            ),
            self::gettestpath() . 'valid/override_tokens/two_override_token_tokenizer_rules.json' => (
                [
                    'comment' => token_type::LITERAL,
                    'comment.line' => token_type::LITERAL
                ]
            ),
            self::gettestpath() . 'valid/override_tokens/two_complex_override_token_tokenizer_rules.json' => (
                [
                    'string.start' => token_type::LITERAL,
                    'string.end' => token_type::LITERAL
                ]
            ),
            self::gettestpath() . 'valid/override_tokens/complex_override_token_tokenizer_rules.json' => (
                [
                    'string.start' => token_type::LITERAL,
                    'string.end' => token_type::LITERAL
                ]
            ),
            self::gettestpath() . 'valid/override_tokens/inheritance_override_tokens_tokenizer_rules.json' => (
                [
                    'string.start' => token_type::LITERAL,
                    'string.end' => token_type::LITERAL,
                    'comment' => token_type::LITERAL
                ]
            )
        );
    }

    private static function setup_merge_cases(): void {
        self::$mergetestcases = array(
            self::gettestpath() . 'valid/merge/merge_one_to_one_state_tokenizer_rules.json' => (
                [
                    "start" => [ 0 => (object)[ "token" => "comment", "regex" => "\\/\\/", "next" => "text-state" ] ],
                    "text-state" => [ 0 => (object)[ "token" => "text", "regex" => ".*" ] ]
                ]
            ),
            self::gettestpath() . 'valid/merge/merge_one_to_two_states_tokenizer_rules.json' => (
                [
                    "start" => [ 0 => (object)[ "token" => "comment", "regex" => "\\/\\/", "next" => "text-state" ] ],
                    "eol" => [ 0 => (object)[ "token" => "eol", "regex" => "\n" ] ],
                    "text-state" => [ 0 => (object)[ "token" => "text", "regex" => ".*" ] ]
                ]
            ),
            self::gettestpath() . 'valid/merge/merge_two_to_one_states_tokenizer_rules.json' => (
                [
                    "start" => [ 0 => (object)[ "token" => "comment", "regex" => "\\/\\/", "next" => "text-state" ] ],
                    "eol" => [ 0 => (object)[ "token" => "eol", "regex" => "\n" ] ],
                    "text-state" => [ 0 => (object)[ "token" => "text", "regex" => ".*" ] ]
                ]
            ),
            self::gettestpath() . 'valid/merge/merge_with_same_states_tokenizer_rules.json' => (
                [
                    "start" => [ 0 => (object)[ "next" => "text-state" ] ],
                    "text-state" => [
                        0 => (object)[ "token" => "comment", "regex" => "\\/\\/", ],
                        1 => (object)[ "token" => "text", "regex" => ".*" ]
                    ]
                ]
            )
        );
    }

    private static function setup_prepare_cases(): void {
        self::$preparetestcases = array(
            self::gettestpath() . 'valid/prepare/prepare_with_one_state_tokenizer_rules.json' => (
                [
                    "regexprs" => [ "start" => "/(\/\/)|(\/\*)|($)/" ],
                    "matchmappings" => [ "start" => [ "default_token" => "text", 0 => 0, 1 => 1 ] ]
                ]
            ),
            self::gettestpath() . 'valid/prepare/prepare_with_two_states_tokenizer_rules.json' => (
                [
                    "regexprs" => [ "start" => "/(\/\/)|(\/\*)|($)/", "another_start" => "/(\/\/)|(\/\*)|($)/" ],
                    "matchmappings" => [
                        "start" => [ "default_token" => "text", 0 => 0, 1 => 1 ],
                        "another_start" => [ "default_token" => "text", 0 => 0, 1 => 1 ]
                    ]
                ]
            ),
            self::gettestpath() . 'valid/prepare/prepare_with_groups_tokenizer_rules.json' => (
                [
                    "regexprs" => [ "start" => "/(\/\/)|((?:.*)(?:b))|($)/" ],
                    "matchmappings" => [ "start" => [ "default_token" => "comment", 0 => 0, 1 => 1 ], ]
                ]
            ),
            self::gettestpath() . 'valid/prepare/prepare_with_more_rules_tokenizer_rules.json' => (
                [
                    "regexprs" => [
                        "start" => "/(\/\/)|((?:void)(?:[a-z]+(?:[a-zA-Z0-9]|_)*)(?:\()(?:\)))|($)/",
                        "statement" => "/(([a-z]+([a-zA-Z0-9]|_)*))|($)/",
                        "comment" => "/(\/\/)|($)/"
                    ],
                    "matchmappings" => [
                        "start" => [ "default_token" => "comment.line.double-slash", 0 => 0, 1 => 1 ],
                        "statement" => [ "default_token" => "text", 0 => 0 ],
                        "comment" => [ "default_token" => "comment", 0 => 0 ]
                    ]
                ]
            ),
            self::gettestpath() . 'valid/prepare/prepare_with_complex_matching_tokenizer_rules.json' => (
                [
                    "regexprs" => [
                        "start" =>
                            "/([+-]?\d[\d_]*(?:(?:\.[\d_]*)?(?:[eE][+-]?[[0-9]_]+)?)?[LlSsDdFfYy]?\b)|((?:true|false)\b)|" .
                            "((?:open(?:\s+))?module(?=\s*\w))|($)/",
                        "body-module" => "/({)|(\s+)|(\w+)|(\.)|(\s+)|($)/",
                        "end-module" => "/(})|(\b(?:requires|transitive|exports|opens|to|uses|provides|with)\b)|($)/"
                    ],
                    "matchmappings" => [
                        "start" => [ "default_token" => "text", 0 => 0, 1 => 1, 2 => 2 ],
                        "body-module" => [ "default_token" => "text", 0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4 ],
                        "end-module" => [ "default_token" => "text", 0 => 0, 1 => 1 ]
                    ]
                ]
            ),
            self::gettestpath() . 'valid/prepare/prepare_with_one_group_tokenizer_rules.json' => (
                [
                    "regexprs" => [ "start" => "/((?:\/\/))|($)/" ],
                    "matchmappings" => [ "start" => [ "default_token" => "text", 0 => 0 ] ]
                ]
            ),
            self::gettestpath() . 'valid/prepare/prepare_not_enough_groups_at_regex_tokenizer_rules.json' => (
                [
                    "regexprs" => [ "start" => "/((?:int))|($)/"],
                    "matchmappings" => [ "start" => [ "default_token" => "text", 0 => 0 ] ]
                ]
            ),
            self::gettestpath() . 'valid/prepare/prepare_with_number_ref_tokenizer_rules.json' => (
                [
                    "regexprs" => [ "start" => "/((a)(b)\\2\\3)|($)/" ],
                    "matchmappings" => [ "start" => [ "default_token" => "text", 0 => 0 ] ]
                ]
            )
        );
    }

    private static function setup_preparse_cases(): void {
        self::$preparsetestcases = array(
            self::gettestpath() . 'valid/get_all_tokens/no_line_tokenizer_rules.json' => (
                [
                    'input' => self::gettestpath() . 'valid/get_all_tokens/no_line.c',
                    'output' => [ 0 => [ 'state' => 'start', 'tokens' => [] ] ]
                ]
            ),
            self::gettestpath() . 'valid/get_all_tokens/one_line_tokenizer_rules.json' => (
                [
                    'input' => self::gettestpath() . 'valid/get_all_tokens/one_line.c',
                    'output' => [
                        0 => [
                            'state' => 'start',
                            'tokens' => [ new token('comment.line', '// This is an example', 0) ]
                        ]
                    ]
                ]
            ),
            self::gettestpath() . 'valid/get_all_tokens/two_lines_tokenizer_rules.json' => (
                [
                    'input' => self::gettestpath() . 'valid/get_all_tokens/two_lines.java',
                    'output' => [
                        0 => [
                            'state' => 'comment',
                            'tokens' => [ new token('comment', '/*', 0) ]
                        ],
                        1 => [
                            'state' => 'start',
                            'tokens' => [
                                new token('comment', '    This is a comment ', 1),
                                new token('comment', '*/', 1)
                            ]
                        ]
                    ]
                ]
            ),
            self::gettestpath() . 'valid/get_all_tokens/more_lines_tokenizer_rules.json' => (
                [
                    'input' => self::gettestpath() . 'valid/get_all_tokens/more_lines.c',
                    'output' => [
                        0 => [
                            'state' => 'start',
                            'tokens' => [
                                new token('keyword', '#include', 0),
                                new token('constant.other', ' <stdio.h>', 0)
                            ]
                        ],
                        1 => [ 'state' => 'start', 'tokens' => [ ] ],
                        2 => [
                            'state' => 'start',
                            'tokens' => [
                                new token('storage.type', 'int', 2), new token('text', ' ', 2),
                                new token('identifier', 'main', 2), new token('paren.lparen', '(', 2),
                                new token('storage.type', 'int', 2), new token('text', ' ', 2),
                                new token('identifier', 'nargc', 2), new token('punctuation.operator', ',', 2),
                                new token('text', ' ', 2), new token('storage.type', 'char', 2),
                                new token('text', ' ', 2), new token('keyword.operator', '*', 2),
                                new token('identifier', 'argv', 2), new token('paren.lparen', '[', 2),
                                new token('paren.rparen', ']', 2), new token('paren.rparen', ')', 2)
                            ]
                        ],
                        3 => [ 'state' => 'start', 'tokens' => [ new token('paren.lparen', '{', 3) ] ],
                        4 => [
                            'state' => 'start',
                            'tokens' => [
                                new token('text', '    ', 4), new token('keyword.control', 'if', 4),
                                new token('text', ' ', 4), new token('paren.lparen', '(', 4),
                                new token('identifier', 'nargc', 4), new token('text', ' ', 4),
                                new token('keyword.operator', '>', 4), new token('text', ' ', 4),
                                new token('constant.numeric', '1', 4), new token('paren.rparen', ')', 4)
                            ]
                            ],
                        5 => [
                            'state' => 'start',
                            'tokens' => [ new token('text', '    ', 5), new token('paren.lparen', '{', 5) ]
                        ],
                        6 => [
                            'state' => 'start',
                            'tokens' => [
                                new token('text', '        ', 6), new token('keyword.control', 'for', 6),
                                new token('text', ' ', 6), new token('paren.lparen', '(', 6),
                                new token('storage.type', 'int', 6), new token('text', ' ', 6), new token('identifier', 'i', 6),
                                new token('text', ' ', 6), new token('keyword.operator', '=', 6),
                                new token('text', ' ', 6), new token('constant.numeric', '0', 6),
                                new token('punctuation.operator', ';', 6), new token('text', ' ', 6),
                                new token('identifier', 'i', 6), new token('text', ' ', 6),
                                new token('keyword.operator', '<', 6), new token('text', ' ', 6),
                                new token('identifier', 'nargc', 6), new token('punctuation.operator', ';', 6),
                                new token('text', ' ', 6), new token('identifier', 'i', 6),
                                new token('keyword.operator', '++', 6), new token('paren.rparen', ')', 6)
                            ]
                        ],
                        7 => [
                            'state' => 'start',
                            'tokens' => [ new token('text', '        ', 7), new token('paren.lparen', '{', 7) ]
                        ],
                        8 => [
                            'state' => 'start',
                            'tokens' => [
                                new token('text', '            ', 8), new token('support.function', 'printf', 8),
                                new token('paren.lparen', '(', 8), new token('string.start', '"', 8),
                                new token('constant.language.escape', '%d', 8), new token('constant.language.escape', '\n', 8),
                                new token('string.end', '"', 8), new token('punctuation.operator', ',', 8),
                                new token('text', ' ', 8), new token('identifier', 'i', 8),
                                new token('paren.rparen', ')', 8), new token('punctuation.operator', ';', 8)
                            ]
                        ],
                        9 => [ 'state' => 'start', 'tokens' => [ ] ],
                        10 => [
                            'state' => 'start',
                            'tokens' => [
                                new token('text', '            ', 10),
                                new token('comment', '// This is just a comment', 10)
                            ]
                        ],
                        11 => [
                            'state' => 'start',
                            'tokens' => [
                                new token('text', '            ', 11), new token('storage.type', 'char', 11),
                                new token('text', ' ', 11), new token('keyword.operator', '*', 11),
                                new token('identifier', 'str', 11), new token('text', ' ', 11),
                                new token('keyword.operator', '=', 11), new token('text', ' ', 11),
                                new token('string.start', '"', 11), new token('string', 'Hello world', 11),
                                new token('string.end', '"', 11), new token('punctuation.operator', ';', 11)
                            ]
                        ],
                        12 => [
                            'state' => 'start',
                            'tokens' => [ new token('text', '        ', 12), new token('paren.rparen', '}', 12) ]
                        ],
                        13 => [
                            'state' => 'start',
                            'tokens' => [ new token('text', '    ', 13), new token('paren.rparen', '}', 13) ]
                        ],
                        14 => [ 'state' => 'start', 'tokens' => [ ] ],
                        15 => [
                            'state' => 'start',
                            'tokens' => [
                                new token('text', '    ', 15), new token('keyword.control', 'return', 15),
                                new token('text', ' ', 15), new token('constant.numeric', '0', 15),
                                new token('punctuation.operator', ';', 15)
                            ]
                        ],
                        16 => [ 'state' => 'start', 'tokens' => [ new token('paren.rparen', '}', 16) ] ]
                    ]
                ]
            )
        );
    }

    private static function setup_get_line_tokens_cases(): void {
        self::$getlinetokenstestcases = array(
            self::gettestpath() . 'valid/get_line_tokens/no_matchs_tokenizer_rules.json' => (
                [
                    'input' => '/* test comments',
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('text', '/* test comments', 0)
                    ) ]
                ]
            ),
            self::gettestpath() . 'valid/get_line_tokens/one_rule_tokenizer_rules.json' => (
                [
                    'input' => 'int',
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('storage.type', 'int', 0)
                    ) ]
                ]
            ),
            self::gettestpath() . 'valid/get_line_tokens/two_rules_tokenizer_rules.json' => (
                [
                    'input' => 'int ',
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('storage.type', 'int', 0)
                    ) ]
                ]
            ),
            self::gettestpath() . 'valid/get_line_tokens/more_rules_tokenizer_rules.json' => (
                [
                    'input' => 'int a = 10;',
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('storage.type', 'int', 0), new token('text', ' ', 0),
                        new token('identifier', 'a', 0), new token('text', ' ', 0),
                        new token('keyword.operator', '=', 0), new token('text', ' ', 0),
                        new token('constant.numeric', '10', 0), new token('text', ';', 0)
                    ) ]
                ]
            ),
            self::gettestpath() . 'valid/get_line_tokens/for_tokenizer_rules.json' => (
                [
                    'input' => 'for (int i = 0; i < 10; i++) {',
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('identifier', 'for', 0), new token('text', ' ', 0),
                        new token('paren.lparen', '(', 0), new token('storage.type', 'int', 0),
                        new token('text', ' ', 0), new token('identifier', 'i', 0),
                        new token('text', ' ', 0), new token('keyword.operator', '=', 0),
                        new token('text', ' ', 0), new token('constant.numeric', '0', 0),
                        new token('text', ';', 0), new token('text', ' ', 0),
                        new token('identifier', 'i', 0), new token('text', ' ', 0),
                        new token('keyword.operator', '<', 0), new token('text', ' ', 0),
                        new token('constant.numeric', '10', 0), new token('text', ';', 0),
                        new token('text', ' ', 0), new token('identifier', 'i', 0),
                        new token('keyword.operator', '++', 0), new token('paren.rparen', ')', 0),
                        new token('text', ' ', 0), new token('paren.lparen', '{', 0)
                    ) ]
                ]
            ),
            self::gettestpath() . 'valid/get_line_tokens/two_states_tokenizer_rules.json' => (
                [
                    'input' => '/* test comments */',
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('comment.multiple', '/*', 0),
                        new token('text', ' test comments ', 0),
                        new token('comment', '*/', 0),
                    ) ]
                ]
            ),
            self::gettestpath() . 'valid/get_line_tokens/unexisted_state_tokenizer_rules.json' => (
                [
                    'input' => '// test comment',
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('comment', '//', 0),
                        new token('text', ' test comment', 0)
                    ) ]
                ]
            ),
            self::gettestpath() . 'valid/get_line_tokens/token_array_tokenizer_rules.json' => (
                [
                    'input' => 'hello () {',
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('identifier', 'hello', 0),
                        new token('text', ' ', 0),
                        new token('paren.lparen', '(', 0),
                        new token('paren.rparen', ')', 0),
                        new token('text', ' ', 0),
                        new token('paren.lparen', '{', 0)
                    ) ]
                ]
            ),
            self::gettestpath() . 'valid/get_line_tokens/token_array_two_rules_tokenizer_rules.json' => (
                [
                    'input' => 'hello () {',
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('identifier', 'hello', 0),
                        new token('text', ' ', 0),
                        new token('paren.lparen', '(', 0),
                        new token('paren.rparen', ')', 0),
                        new token('text', ' ', 0),
                        new token('paren.lparen', '{', 0)
                    ) ]
                ]
            )
        );

        self::$getlinetokenoverflowstestcases = array(
            self::gettestpath() . 'valid/get_line_tokens/no_matchs_tokenizer_rules.json' => (
                [
                    'input' => [
                        'max_token_count' => 0,
                        'value' => '/* test comments',
                    ],
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('overflow', '/* test comments', 0)
                    ) ]
                ]
            ),
            self::gettestpath() . 'valid/get_line_tokens/one_rule_tokenizer_rules.json' => (
                [
                    'input' => [
                        'max_token_count' => 1,
                        'value' => 'int a',
                    ],
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('storage.type', 'int', 0),
                        new token('overflow', ' a', 0)
                    ) ]
                ]
            ),
        );
    }

    private static function setup_parse_cases(): void {
        self::$parsetestcases = array(
            self::gettestpath() . 'valid/parse/no_line_tokenizer_rules.json' => (
                [
                    'input' => self::gettestpath() . 'valid/parse/no_line.c',
                    'output' => [ ]
                ]
            ),
            self::gettestpath() . 'valid/parse/one_line_tokenizer_rules.json' => (
                [
                    'input' => self::gettestpath() . 'valid/parse/one_line.c',
                    'output' => [ ]
                ]
            ),
            self::gettestpath() . 'valid/parse/two_lines_tokenizer_rules.json' => (
                [
                    'input' => self::gettestpath() . 'valid/parse/two_lines.java',
                    'output' => [ ]
                ]
            ),
            self::gettestpath() . 'valid/parse/more_lines_tokenizer_rules.json' => (
                [
                    'input' => self::gettestpath() . 'valid/parse/more_lines.c',
                    'output' => [
                        new token(token_type::RESERVED, '#include', 0),
                        new token(token_type::LITERAL, '<stdio.h>', 0),
                        new token(token_type::RESERVED, 'int', 2),
                        new token(token_type::IDENTIFIER, 'main', 2),
                        new token(token_type::OPERATOR, '(', 2),
                        new token(token_type::RESERVED, 'int', 2),
                        new token(token_type::IDENTIFIER, 'nargc', 2),
                        new token(token_type::OPERATOR, ',', 2),
                        new token(token_type::RESERVED, 'char', 2),
                        new token(token_type::OPERATOR, '*', 2),
                        new token(token_type::IDENTIFIER, 'argv', 2),
                        new token(token_type::OPERATOR, '[', 2),
                        new token(token_type::OPERATOR, ']', 2),
                        new token(token_type::OPERATOR, ')', 2),
                        new token(token_type::OPERATOR, '{', 3),
                        new token(token_type::RESERVED, 'if', 4),
                        new token(token_type::OPERATOR, '(', 4),
                        new token(token_type::IDENTIFIER, 'nargc', 4),
                        new token(token_type::OPERATOR, '>', 4),
                        new token(token_type::LITERAL, '1', 4),
                        new token(token_type::OPERATOR, ')', 4),
                        new token(token_type::OPERATOR, '{', 5),
                        new token(token_type::RESERVED, 'for', 6),
                        new token(token_type::OPERATOR, '(', 6),
                        new token(token_type::RESERVED, 'int', 6),
                        new token(token_type::IDENTIFIER, 'i', 6),
                        new token(token_type::OPERATOR, '=', 6),
                        new token(token_type::LITERAL, '0', 6),
                        new token(token_type::OPERATOR, ';', 6),
                        new token(token_type::IDENTIFIER, 'i', 6),
                        new token(token_type::OPERATOR, '<', 6),
                        new token(token_type::IDENTIFIER, 'nargc', 6),
                        new token(token_type::OPERATOR, ';', 6),
                        new token(token_type::IDENTIFIER, 'i', 6),
                        new token(token_type::OPERATOR, '++', 6),
                        new token(token_type::OPERATOR, ')', 6),
                        new token(token_type::OPERATOR, '{', 7),
                        new token(token_type::RESERVED, 'printf', 8),
                        new token(token_type::OPERATOR, '(', 8),
                        new token(token_type::LITERAL, '"', 8),
                        new token(token_type::LITERAL, '%d', 8),
                        new token(token_type::LITERAL, '\n', 8),
                        new token(token_type::LITERAL, '"', 8),
                        new token(token_type::OPERATOR, ',', 8),
                        new token(token_type::IDENTIFIER, 'i', 8),
                        new token(token_type::OPERATOR, ')', 8),
                        new token(token_type::OPERATOR, ';', 8),
                        new token(token_type::RESERVED, 'char', 11),
                        new token(token_type::OPERATOR, '*', 11),
                        new token(token_type::IDENTIFIER, 'str', 11),
                        new token(token_type::OPERATOR, '=', 11),
                        new token(token_type::LITERAL, '"Hello world', 11),
                        new token(token_type::LITERAL, '"', 11),
                        new token(token_type::OPERATOR, ';', 11),
                        new token(token_type::OPERATOR, '}', 12),
                        new token(token_type::OPERATOR, '}', 13),
                        new token(token_type::RESERVED, 'return', 15),
                        new token(token_type::LITERAL, '0', 15),
                        new token(token_type::OPERATOR, ';', 15),
                        new token(token_type::OPERATOR, '}', 16),
                    ]
                ]
            )
        );
    }

    private static function get_behat($tokenizer, array $extensions, bool $isnew): void {
        global $CFG;
        $outputdir = $CFG->dataroot . '/temp/vpl/';
        $dirbehat = dirname(__FILE__) . '/behat/datafiles/similarity/';
        $extincluded = array();

        foreach ($extensions as $ext) {
            if (!isset($extincluded[$ext])) {
                $parsefilename = $dirbehat . substr($ext, 1) . '_similarity' . $ext;

                if (file_exists($parsefilename)) {
                    if ($isnew === true) {
                        $tokens = $tokenizer->parse($parsefilename);
                        $outputfilename = $outputdir . 'new_tokenizer/with_vpl/tokens_' . substr($ext, 1) . '.txt';
                        vpl_fwrite($outputfilename, json_encode($tokens, JSON_UNESCAPED_UNICODE));

                        $tokens = $tokenizer->get_all_tokens($parsefilename);
                        $outputfilename = $outputdir . 'new_tokenizer/without_vpl/tokens_' . substr($ext, 1) . '.txt';
                        vpl_fwrite($outputfilename, json_encode($tokens, JSON_UNESCAPED_UNICODE));
                    } else {
                        $data = file_get_contents($parsefilename);
                        $tokenizer->parse($data, false);
                        $tokens = $tokenizer->get_tokens();
                        $outputfilename = $outputdir . 'old_tokenizer/tokens_' . substr($ext, 1) . '.txt';
                        vpl_fwrite($outputfilename, json_encode($tokens, JSON_UNESCAPED_UNICODE));
                    }
                }

                $extincluded[$ext] = true;
            }
        }
    }
}
