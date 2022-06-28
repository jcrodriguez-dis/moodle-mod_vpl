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
use mod_vpl\util\assertf;
use Exception;

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');

/**
 * Unit tests for \mod_vpl\tokenizer\tokenizer class.
 *
 * @group mod_vpl
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
    protected static function testpath(): string {
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
     * List of invalid test cases for parse
     *
     * key: path of testable JSON file
     * value: expected error message to catch
     */
    protected static array $invalidparsecases;

    /**
     * List of valid test cases for tokenizer::get_line_tokens
     *
     * key: path of a testable JSON file
     * value: input and expected result of tokenizer::get_line_tokens
     */
    protected static array $validtestcases;

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
     * List of test cases for tokenizer::parse
     *
     * key: path of a testable JSON file
     * value: expected list of tokens and state
     */
    protected static array $parsetestcases;

    /**
     * Prepare test cases before the execution
     */
    public static function setUpBeforeClass(): void {
        self::$validtestcases = array(
            self::testpath() . 'valid/get_line_tokens/no_matchs_highlight_rules.json' => (
                [
                    'input' => '/* test comments',
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('text', '/* test comments')
                    ) ]
                ]
            ),
            self::testpath() . 'valid/get_line_tokens/one_rule_highlight_rules.json' => (
                [
                    'input' => 'int',
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('storage.type', 'int')
                    ) ]
                ]
            ),
            self::testpath() . 'valid/get_line_tokens/two_rules_highlight_rules.json' => (
                [
                    'input' => 'int ',
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('storage.type', 'int'), new token('text', ' ')
                    ) ]
                ]
            ),
            self::testpath() . 'valid/get_line_tokens/more_rules_highlight_rules.json' => (
                [
                    'input' => 'int a = 10;',
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('storage.type', 'int'), new token('text', ' '),
                        new token('identifier', 'a'), new token('text', ' '),
                        new token('keyword.operator', '='), new token('text', ' '),
                        new token('constant.numeric', '10'), new token('text', ';')
                    ) ]
                ]
            ),
            self::testpath() . 'valid/get_line_tokens/for_highlight_rules.json' => (
                [
                    'input' => 'for (int i = 0; i < 10; i++) {',
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('identifier', 'for'), new token('text', ' '),
                        new token('paren.lparen', '('), new token('storage.type', 'int'),
                        new token('text', ' '), new token('identifier', 'i'),
                        new token('text', ' '), new token('keyword.operator', '='),
                        new token('text', ' '), new token('constant.numeric', '0'),
                        new token('text', ';'), new token('text', ' '),
                        new token('identifier', 'i'), new token('text', ' '),
                        new token('keyword.operator', '<'), new token('text', ' '),
                        new token('constant.numeric', '10'), new token('text', ';'),
                        new token('text', ' '), new token('identifier', 'i'),
                        new token('keyword.operator', '++'), new token('paren.rparen', ')'),
                        new token('text', ' '), new token('paren.lparen', '{')
                    ) ]
                ]
            ),
            self::testpath() . 'valid/get_line_tokens/two_states_highlight_rules.json' => (
                [
                    'input' => '/* test comments */',
                    'output' => [ 'state' => 'start', 'tokens' => array(
                        new token('comment.multiple', '/*'),
                        new token('text', ' test comments '),
                        new token('comment', '*/'),
                    ) ]
                ]
            )
        );

        self::$invalidtestcases = array(
            self::testpath() . 'invalid/dump_test.json' => (
                'file ' . self::testpath()  . 'invalid/dump_test.json must exist'
            ),
            self::testpath() . 'invalid/general/not_good_suffix.json' => (
                self::testpath() . 'invalid/general/not_good_suffix.json' . ' must have suffix _highlight_rules.json'
            ),
            self::testpath() . 'invalid/general/empty_highlight_rules.json' => (
                'file ' . self::testpath() . 'invalid/general/empty_highlight_rules.json' . ' is empty'
            ),
            self::testpath() . 'invalid/general/undefined_option_highlight_rules.json' => (
                'invalid options: example'
            ),
            self::testpath() . 'invalid/general/invalid_check_rules_highlight_rules.json' => (
                '"check_rules" option must be a boolean'
            ),
            self::testpath() . 'invalid/general/invalid_extension_no_string_highlight_rules.json' => (
                '"extension" option must be a string or an array of strings'
            ),
            self::testpath() . 'invalid/general/invalid_extension_no_array_highlight_rules.json' => (
                '"extension" option must be a string or an array of strings'
            ),
            self::testpath() . 'invalid/general/invalid_extension_no_dot_highlight_rules.json' => (
                'extension c must start with .'
            ),
            self::testpath() . 'invalid/general/invalid_inherit_rules_highlight_rules.json' => (
                '"inherit_rules" option must be a string'
            ),
            self::testpath() . 'invalid/states/invalid_data_states_highlight_rules.json' => (
                '"states" option must be an object'
            ),
            self::testpath() . 'invalid/states/states_with_no_name_highlight_rules.json' => (
                'state 0 must have a name'
            ),
            self::testpath() . 'invalid/states/state_not_object_highlight_rules.json' => (
                'state 0 must be an array'
            ),
            self::testpath() . 'invalid/states/one_state_with_no_name_highlight_rules.json' => (
                'state 1 must have a name'
            ),
            self::testpath() . 'invalid/rules/invalid_rule_highlight_rules.json' => (
                'rule 0 of state "state1" nº0 must be an object'
            ),
            self::testpath() . 'invalid/rules/invalid_rule_option_value_highlight_rules.json' => (
                'invalid data type for token at rule 0 of state "state1" nº0'
            ),
            self::testpath() . 'invalid/rules/undefined_rule_option_highlight_rules.json' => (
                'invalid option example at rule 0 of state "state1" nº0'
            ),
            self::testpath() . 'invalid/rules/invalid_next_highlight_rules.json' => (
                'invalid data type for next at rule 0 of state "state1" nº0'
            ),
            self::testpath() . 'invalid/rules/regex_not_found_highlight_rules.json' => (
                'option token must be defined next to regex at rule 0 of state "state1" nº0'
            ),
            self::testpath() . 'invalid/rules/token_not_found_highlight_rules.json' => (
                'option regex must be defined next to token at rule 0 of state "state1" nº0'
            ),
            self::testpath() . 'invalid/rules/invalid_token_value_highlight_rules.json' => (
                'invalid token at rule 0 of state "start" nº0'
            ),
            self::testpath() . 'invalid/rules/invalid_default_token_highlight_rules.json' => (
                'invalid data type for default_token at rule 0 of state "start" nº0'
            ),
            self::testpath() . 'invalid/rules/default_token_not_alone_highlight_rules.json' => (
                'option default_token must be alone at rule 0 of state "start" nº0'
            ),
            self::testpath() . 'invalid/general/invalid_json_inheritance_highlight_rules.json' => (
                'inherit JSON file ' . self::testpath() . 'invalid/general/dump_highlight_rules.json does not exist'
            )
        );

        self::$invalidparsecases = array(
            self::testpath() . 'invalid/general/invalid_file_at_parse_highlight_rules.json' => (
                [
                    'input' => 'dump_test.js',
                    'output' => 'file dump_test.js does not exist',
                ]
            ),
            self::testpath() . 'invalid/general/invalid_ext_at_parse_highlight_rules.json' => (
                [
                    'input' => self::testpath() . 'invalid/general/test_ext.java',
                    'output' => self::testpath() . 'invalid/general/test_ext.java must end with one of the extensions .c,.h',
                ]
            )
        );

        self::$mergetestcases = array(
            self::testpath() . 'valid/merge/merge_one_to_one_state_highlight_rules.json' => (
                [
                    "start" => [ 0 => (object)[ "token" => "comment", "regex" => "\\/\\/", "next" => "text-state" ] ],
                    "text-state" => [ 0 => (object)[ "token" => "text", "regex" => ".*" ] ]
                ]
            ),
            self::testpath() . 'valid/merge/merge_one_to_two_states_highlight_rules.json' => (
                [
                    "start" => [ 0 => (object)[ "token" => "comment", "regex" => "\\/\\/", "next" => "text-state" ] ],
                    "eol" => [ 0 => (object)[ "token" => "eol", "regex" => "\n" ] ],
                    "text-state" => [ 0 => (object)[ "token" => "text", "regex" => ".*" ] ]
                ]
            ),
            self::testpath() . 'valid/merge/merge_two_to_one_states_highlight_rules.json' => (
                [
                    "start" => [ 0 => (object)[ "token" => "comment", "regex" => "\\/\\/", "next" => "text-state" ] ],
                    "eol" => [ 0 => (object)[ "token" => "eol", "regex" => "\n" ] ],
                    "text-state" => [ 0 => (object)[ "token" => "text", "regex" => ".*" ] ]
                ]
            ),
            self::testpath() . 'valid/merge/merge_with_same_states_highlight_rules.json' => (
                [
                    "start" => [ 0 => (object)[ "next" => "text-state" ] ],
                    "text-state" => [
                        0 => (object)[ "token" => "comment", "regex" => "\\/\\/", ],
                        1 => (object)[ "token" => "text", "regex" => ".*" ]
                    ]
                ]
            )
        );

        self::$preparetestcases = array(
            self::testpath() . 'valid/prepare/prepare_with_one_state_highlight_rules.json' => (
                [
                    "regexprs" => [ "start" => "/(\/\/)|(\/\*)|($)/" ],
                    "matchmappings" => [ "start" => [ "default_token" => "text", 0 => 0, 1 => 1 ] ]
                ]
            ),
            self::testpath() . 'valid/prepare/prepare_with_two_states_highlight_rules.json' => (
                [
                    "regexprs" => [ "start" => "/(\/\/)|(\/\*)|($)/", "another_start" => "/(\/\/)|(\/\*)|($)/" ],
                    "matchmappings" => [
                        "start" => [ "default_token" => "text", 0 => 0, 1 => 1 ],
                        "another_start" => [ "default_token" => "text", 0 => 0, 1 => 1 ]
                    ]
                ]
            ),
            self::testpath() . 'valid/prepare/prepare_with_groups_highlight_rules.json' => (
                [
                    "regexprs" => [ "start" => "/(\/\/)|((?:.*)(?:b))|($)/" ],
                    "matchmappings" => [ "start" => [ "default_token" => "comment", 0 => 0, 1 => 1 ], ]
                ]
            ),
            self::testpath() . 'valid/prepare/prepare_with_more_rules_highlight_rules.json' => (
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
            self::testpath() . 'valid/prepare/prepare_with_complex_matching_highlight_rules.json' => (
                [
                    "regexprs" => [
                        "start" =>
                            "/([+-]?\d[\d_]*((\.[\d_]*)?([eE][+-]?[[0-9]_]+)?)?[LlSsDdFfYy]?" .
                            "\b)|((?:true|false)\b)|((?:open(?:\s+))?module(?=\s*\w))|($)/",
                        "body-module" => "/({)|(\s+)|(\w+)|(\.)|(\s+)|($)/",
                        "end-module" => "/(})|(\b(?:requires|transitive|exports|opens|to|uses|provides|with)\b)|($)/"
                    ],
                    "matchmappings" => [
                        "start" => [ "default_token" => "text", 0 => 0, 4 => 1, 5 => 2 ],
                        "body-module" => [ "default_token" => "text", 0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4 ],
                        "end-module" => [ "default_token" => "text", 0 => 0, 1 => 1 ]
                    ]
                ]
            )
        );

        self::$parsetestcases = array(
            self::testpath() . 'valid/parse/no_line_highlight_rules.json' => (
                [
                    'input' => self::testpath() . 'valid/parse/no_line.c',
                    'output' => [
                        0 => [
                            'state' => 'start',
                            'tokens' => []
                        ]
                    ]
                ]
            ),
            self::testpath() . 'valid/parse/one_line_highlight_rules.json' => (
                [
                    'input' => self::testpath() . 'valid/parse/one_line.c',
                    'output' => [
                        0 => [
                            'state' => 'start',
                            'tokens' => [
                                0 => new token('comment.line', '// This is an example')
                            ]
                        ]
                    ]
                ]
            ),
            self::testpath() . 'valid/parse/two_lines_highlight_rules.json' => (
                [
                    'input' => self::testpath() . 'valid/parse/two_lines.java',
                    'output' => [
                        0 => [
                            'state' => 'comment',
                            'tokens' => [
                                0 => new token('comment', '/*')
                            ]
                        ],
                        1 => [
                            'state' => 'start',
                            'tokens' => [
                                0 => new token('comment', '    This is a comment '),
                                1 => new token('comment', '*/')
                            ]
                        ]
                    ]
                ]
            )/*,
            self::testpath() . 'valid/parse/more_lines_highlight_rules.json' => (
                [
                    'input' => self::testpath() . 'valid/parse/more_lines.f',
                    'output' => []
                ]
            )*/
        );
    }

    /**
     * Method to test tokenizer::discard_comments
     */
    public function test_discard_comments() {
        $dir = self::testpath() . 'valid/comments';

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
     */
    public function test_static_check() {
        try {
            $dir = dirname(__FILE__) . '/../similarity/rules';

            $scanarr = scandir($dir);
            $filesarr = array_diff($scanarr, array('.', '..'));

            foreach ($filesarr as $filename) {
                $filename = $dir . '/' . $filename;
                new tokenizer($filename, false);
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
        foreach (self::$validtestcases as $filename => $expectedresult) {
            $input = $expectedresult['input'];
            $expectedresult = $expectedresult['output'];

            $tokenizer = new tokenizer($filename);
            $result = $tokenizer->get_line_tokens($input);

            $this->assertTrue(count($result) === 2);
            $this->assertSame($expectedresult['state'], $result['state']);
            $this->assertTrue(count($result['tokens']) === count($expectedresult['tokens']));

            for ($i = 0; $i < count($result['tokens']); $i++) {
                $this->assertTrue($result['tokens'][$i]->equals_to($expectedresult['tokens'][$i]));
            }
        }
    }

    /**
     * Method to test tokenizer::parse when parameters are invalid
     */
    public function test_invalid_parse() {
        foreach (self::$invalidparsecases as $filename => $expectedresult) {
            $input = $expectedresult['input'];
            $expectedresult = $expectedresult['output'];
            $tokenizer = new tokenizer($filename);

            try {
                $tokenizer->parse($input);
            } catch (Exception $exe) {
                $expectedmssg = assertf::get_error('default', $expectedresult);
                $this->assertSame($expectedmssg, $exe->getMessage());
                continue;
            }

            $this->fail('An expection was expected');
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

            foreach ($result as $k => $valueforline) {
                $this->assertTrue(count($valueforline) === 2);
                $this->assertSame($expectedresult[$k]['state'], $valueforline['state']);
                $this->assertTrue(count($valueforline['tokens']) === count($expectedresult[$k]['tokens']));

                for ($i = 0; $i < count($valueforline['tokens']); $i++) {
                    $this->assertTrue($valueforline['tokens'][$i]->equals_to($expectedresult[$k]['tokens'][$i]));
                }
            }
        }
    }
}
