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
 * Unit tests for mod_vpl\tokenizer\tokenizer_base
 *
 * @package mod_vpl
 * @copyright David Parreño Barbuzano
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author  David Parreño Barbuzano <david.parreno101@alu.ulpgc.es>
 */
namespace mod_vpl;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');

use mod_vpl\tokenizer\token;

/**
 * Unit tests for \mod_vpl\tokenizer\tokenizer_base class.
 *
 * @group mod_vpl
 * @group mod_vpl_vplt
 * @group mod_vpl_tokenizer
 * @group mod_vpl_tokenizer_base
 * @covers \mod_vpl\tokenizer\tokenizer_base
 */
class tokenizer_base_test extends \advanced_testcase {
    /**
     * Test cases for tokenizer_base::remove_capturing_groups
     *
     * - key   => input value to test
     * - value => expected result.
     */
    protected static array $testcasesrcg;

    /**
     * Test cases for tokenizer_base::check_type
     *
     * - key   => available data type
     * - value => [ true => [ list_of_valid_values ], false => [ list_of_invalid_values ] ]
     */
    protected static array $testcasesckt;

    /**
     * Test cases for tokenizer_base::check_token
     *
     * - key   => expected value to get
     * - value => list of input tokens to test
     */
    protected static array $testcasesctk;

    /**
     * Test cases for tokenizer_base::check_token
     *
     * - key      => integer value, not a real use
     * - value    => [ input => [ types, value, and regex to test ], output => expected value to get ]
     */
    protected static array $testcasesgat;

    /**
     * State to use to test tokenizer_base::contains_rule
     */
    protected static array $statetosearchrules;

    /**
     * Available tokens to use to test tokenizer::check_token
     */
    protected const AVAILABLETOKENS = [
        "text",
        "comment",
        "comment.line",
        "constant",
        "constant.character",
        "constant.character.escape",
        "storage",
        "storage.type"
    ];

    /**
     * Prepare test cases before the execution
     */
    public static function setUpBeforeClass(): void {
        self::$testcasesrcg = [
            "()"           => "()",
            "(a)"          => "(?:a)",
            "(ab)"         => "(?:ab)",
            "(a)(b)"       => "(?:a)(?:b)",
            "(ab)(d)"      => "(?:ab)(?:d)",
            "(ab)(d)()"    => "(?:ab)(?:d)()",
            "(ax(by))[()]" => "(?:ax(?:by))[()]"
        ];

        self::$testcasesckt = [
            "number"       => [ true => [ 10, 30.5, 0 ], false => [ true, "not_a_number" ] ],
            "bool"         => [ true => [ true, false ], false => [ 10, "not_a_bool" ] ],
            "string"       => [ true => [ "", "example" ], false => [ 10, true ] ],
            "array"        => [ true => [ [], [ 10, 20, 30 ] ], false => [ 10, true, "not_an_array" ] ],
            "object"       => [ true => [ (object)["attr" => 10], (object)["attr" => ""] ], false => [ "not_an_object", 20 ] ],
            "array_number" => [ true => [ [ 10, 20, 30], [10] ], false => [ [ 10, "", 30 ], 10 ] ],
            "array_bool"   => [ true => [ [ true, false, true], [ false ] ], false => [ [ true, "", false ], true ] ],
            "array_string" => [ true => [ [ "example", "", "10"], [ "test" ] ], false => [ [ "10", "", 30 ], "10" ] ],
            "array_array"  => [ true => [ [ [ 10, 20, 30 ] ], [[10]] ], false => [ [ 10, "", 30 ], [10] ] ],
            "array_object" => [ true => [ [ (object)["h" => 10] ], [(object)[]] ], false => [ [ 10, "", 30 ], 10 ] ],
            "array_not_valid_type" => [ false => [ [ 10, 20, 30 ] ] ]
        ];

        self::$testcasesctk = [
            true  => [
                "text", "comment", "comment.line", "constant", "constant.character",
                "constant.character.escape", "storage", "storage.type",
                [ "text" ], [ "text", "comment" ], [ "text", "comment.line", "constant.character" ]
            ],
            false => [
                "", "hello", "comment.multiple", "constant.regex", "variable",
                [], [ "text.line"], [ "text", "comment.multiple", "character" ]
            ]
        ];

        self::$testcasesgat = [
            0 => [ 'input'  => [ 'type' => [ ], 'value' => '', 'regex' => '' ], 'output' => [] ],
            1 => [ 'input'  => [ 'type' => [ 'type', 'id' ], 'value' => '', 'regex' => '/((?:int))|($)/' ], 'output' => [] ],
            2 => [ 'input'  => [ 'type' => [ 'type' ], 'value' => '', 'regex' => '/((?:int)(?:a))|($)/' ], 'output' => [] ],
            3 => [
                'input'  => [ 'type' => [ 'type' ], 'value' => 'int', 'regex' => '((?:int))|($)' ],
                'output' => [ new token('type', 'int', 0) ]
            ],
            4 => [
                'input'  => [
                    'type' => [ 'storage.type', 'text', 'identifier' ],
                    'value' => 'int a',
                    'regex' => '/((?:int)(?:\s+)(?:[a-z]))|($)/'
                ],
                'output' => [ new token('storage.type', 'int', 0), new token('text', ' ', 0), new token('identifier', 'a', 0) ]
            ],
            5 => [
                'input' => [
                    'type' => [ 'id', 'text', 'paren.lparen', 'paren.rparen', 'text', 'paren.lparen' ],
                    'value' => 'hello () {',
                    'regex' => '/((?:[a-z]+)(?:\s*)(?:\()(?:\))(?:\s+)(?:\{))|($)/'
                ],
                'output' => [
                    new token('id', 'hello', 0), new token('text', ' ', 0),
                    new token('paren.lparen', '(', 0), new token('paren.rparen', ')', 0),
                    new token('text', ' ', 0), new token('paren.lparen', '{', 0)
                ]
            ]
        ];

        self::$statetosearchrules = [
            (object)["token" => "string.double", "regex" => "\".*\""],
            (object)["token" => "comment", "regex" => "\\/\\/", "next" => "start"],
            (object)["default_token" => "comment"]
        ];
    }

    /**
     * Method to test tokenizer_base::remove_capturing_groups
     *
     * Test cases based on Ace Editor unit tests:
     * (https://github.com/ajaxorg/ace/blob/master/lib/ace/tokenizer_test.js)
     */
    public function test_remove_capturing_groups() {
        foreach (self::$testcasesrcg as $src => $expectedregex) {
            $regex = testable_tokenizer_base::remove_capturing_groups($src);
            $this->assertSame($expectedregex, $regex);
        }
    }

    /**
     * Method to test tokenizer_base::check_type
     */
    public function test_check_type() {
        foreach (self::$testcasesckt as $type => $values) {
            if (isset($values[true])) {
                foreach ($values[true] as $validvalue) {
                    $cond = testable_tokenizer_base::check_type($validvalue, $type);
                    $this->assertTrue($cond);
                }
            }

            if (isset($values[false])) {
                foreach ($values[false] as $invalidvalue) {
                    $cond = testable_tokenizer_base::check_type($invalidvalue, $type);

                    if (is_bool($cond) === true) {
                        $this->assertFalse($cond);
                    } else {
                        $this->assertTrue(is_numeric($cond));
                        $this->assertTrue($cond >= 0 && $cond < count($invalidvalue));
                        $this->assertFalse(testable_tokenizer_base::check_type($invalidvalue[$cond], $type));
                    }
                }
            }
        }
    }

    /**
     * Method to test tokenizer_base::check_token
     *
     * Naming conventions are inspired in TextMate manual,
     * see https://macromates.com/manual/en/language_grammars#naming-conventions
     */
    public function test_check_token() {
        foreach (self::$testcasesctk as $expectedvalue => $tokens) {
            foreach ($tokens as $token) {
                $result = testable_tokenizer_base::check_token($token, self::AVAILABLETOKENS);
                $this->assertSame(boolval($expectedvalue), $result);
            }
        }
    }

    /**
     * Method to test tokenizer_base::contains_rule
     */
    public function test_contains_rule() {
        foreach (self::$statetosearchrules as $rule) {
            $cond = testable_tokenizer_base::contains_rule(self::$statetosearchrules, $rule);
            $this->assertTrue($cond);

            $invalidrule = clone $rule;
            $invalidrule->dump = "this_change_makes_current_rule_invalid";
            $cond = testable_tokenizer_base::contains_rule(self::$statetosearchrules, $invalidrule);
            $this->assertFalse($cond);
        }
    }

    /**
     * Method to test tokenizer_base::get_array_tokens
     */
    public function test_get_array_tokens() {
        foreach (self::$testcasesgat as $expected) {
            $type = $expected['input']['type'];
            $value = $expected['input']['value'];
            $regex = $expected['input']['regex'];
            $expectedresult = $expected['output'];

            $result = testable_tokenizer_base::get_token_array(0, $type, $value, $regex);
            $this->assertTrue(count($expectedresult) === count($result));

            for ($i = 0; $i < count($result); $i++) {
                $this->assertTrue($result[$i]->equals_to($expectedresult[$i]));
            }
        }
    }
}
