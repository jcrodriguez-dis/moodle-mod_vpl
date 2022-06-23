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

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');
require_once($CFG->dirroot . '/mod/vpl/tests/vpl_tokenizer/libtokenizer.php');

/**
 * Unit tests for \mod_vpl\tokenizer\tokenizer class.
 *
 * @group mod_vpl
 * @group mod_vpl_tokenizer
 * @group mod_vpl_tokenizer_base
 * @covers \mod_vpl\tokenizer\tokenizer_base
 */
class tokenizer_test extends \advanced_testcase {
    /**
     * Array with all test cases for remove_capturing_groups
     */
    protected static array $testcasesrcg;

    /**
     * Array with all test cases for create_splitter_regex
     */
    protected static array $testcasescsr;

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

        self::$testcasescsr = [
            "(a)(b)(?=[x)(])"           => "^(a)(b)$",
            "xc(?=([x)(]))"             => "^xc$",
            "(xc(?=([x)(])))"           => "^(xc)$",
            "(?=r)[(?=)](?=([x)(]))"    => "^(?=r)[(?=)]$",
            "(?=r)[(?=)](\\?=t)"        => "^(?=r)[(?=)](\\?=t)$",
            "[(?=)](\\?=t)"             => "^[(?=)](\\?=t)$"
        ];
    }

    /**
     * Method to test tokenizer::remove_capturing_groups
     *
     * Test cases based on Ace Editor unit tests:
     * (https://github.com/ajaxorg/ace/blob/master/lib/ace/tokenizer_test.js)
     */
    public function test_remove_capturing_groups() {
        foreach (self::$testcasesrcg as $src => $expectedregex) {
            $regex = libtokenizer::remove_capturing_groups($src);
            $this->assertSame($expectedregex, $regex);
        }
    }

    /**
     * Method to test tokenizere::create_splitter_regex
     *
     * Test cases based on Ace Editor unit tests:
     * (https://github.com/ajaxorg/ace/blob/master/lib/ace/tokenizer_test.js)
     */
    public function test_create_splitter_regex() {
        foreach (self::$testcasescsr as $src => $expectedregex) {
            $regex = libtokenizer::create_splitter_regex($src);
            $this->assertSame($expectedregex, $regex);
        }
    }
}