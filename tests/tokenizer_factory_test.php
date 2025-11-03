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
 * Unit tests for mod_vpl\tokenizer\tokenizer_factory
 *
 * @package mod_vpl
 * @copyright David Parreño Barbuzano
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author  David Parreño Barbuzano <david.parreno101@alu.ulpgc.es>
 */
namespace mod_vpl;

use Exception;
use mod_vpl\util\assertf;
use mod_vpl\tokenizer\tokenizer_factory;
use mod_vpl\tests\tokenizer_similarity_utils;

/**
 * Unit tests for \mod_vpl\tokenizer\tokenizer_factory class.
 *
 * @group mod_vpl
 * @group mod_vpl_vplt
 * @group mod_vpl_tokenizer
 * @group mod_vpl_tokenizer_base
 * @group mod_vpl_tokenizer_factory
 * @covers \mod_vpl\tokenizer\tokenizer_factory
 */
final class tokenizer_factory_test extends \advanced_testcase {
    /**
     * Method to test tokenizer_factory::get when tokenizer is not valid
     */
    public function test_unexisted_tokenizer(): void {
        try {
            tokenizer_factory::get('not_a_valid_language');
        } catch (\Throwable $exe) {
            $mssg = 'not_a_valid_language is not available';
            $expectedmssg = assertf::get_error('not_a_valid_language', $mssg);
            $this->assertSame($expectedmssg, $exe->getMessage());
        }
    }

    /**
     * Method to test tokenizer_factory::get when old tokenizer is used
     */
    public function test_old_tokenizer(): void {
        $tokenizer = tokenizer_factory::get('prolog');
        $this->check_tokenizer($tokenizer, 'prolog', false);
    }

    /**
     * Method to test tokenizer_factory::get when new tokenizer is used
     */
    public function test_new_tokenizer(): void {
        $tokenizerlangs = tokenizer_similarity_utils::get_tokenizer_langs();

        foreach ($tokenizerlangs as $namelang) {
            $tokenizer = tokenizer_factory::get($namelang);
            $this->check_tokenizer($tokenizer, $namelang, true);
        }
    }

    /**
     * Method to check if the tokenizer is valid
     *
     * @param object $tokenizer The tokenizer object to check
     * @param string $namelang The name of the language for the tokenizer
     * @param bool $newtokenizer If true, checks for new tokenizer class
     */
    private function check_tokenizer($tokenizer, $namelang, $newtokenizer = false) {
        $this->assertTrue(isset($tokenizer) === true);
        $classname = $newtokenizer === false ? 'vpl_tokenizer_' . $namelang : 'mod_vpl\tokenizer\tokenizer';
        $this->assertSame($classname, get_class($tokenizer));
    }
}
