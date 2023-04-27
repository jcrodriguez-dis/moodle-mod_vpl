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
 * Unit tests for mod_vpl\tokenizer\tokenizer_factory and vpl_tokenizer_factory.class
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
require_once($CFG->dirroot . '/mod/vpl/similarity/tokenizer_factory.class.php');

use Exception;

use mod_vpl\util\assertf;
use mod_vpl\tokenizer\tokenizer_factory;
use \vpl_tokenizer_factory;

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
class tokenizer_factory_test extends \advanced_testcase {
    /**
     * Method to test tokenizer_factory::get when tokenizer is not valid
     */
    public function test_unexisted_tokenizer() {
        try {
            tokenizer_factory::get('not_a_valid_language');
        } catch (Exception $exe) {
            $mssg = 'not_a_valid_language is not available';
            $expectedmssg = assertf::get_error('not_a_valid_language', $mssg);
            $this->assertSame($expectedmssg, $exe->getMessage());
        }
    }

    /**
     * Method to test vpl_tokenizer_factory::get when tokenizer is not valid
     */
    public function test_unexisted_tokenizer_with_vpl() {
        try {
            vpl_tokenizer_factory::get('not_a_valid_language');
        } catch (Exception $exe) {
            $mssg = 'not_a_valid_language is not available';
            $expectedmssg = assertf::get_error('not_a_valid_language', $mssg);
            $this->assertSame($expectedmssg, $exe->getMessage());
        }
    }

    /**
     * Method to test tokenizer_factory::get when old tokenizer is used
     */
    public function test_old_tokenizer() {
        $tokenizer = tokenizer_factory::get('prolog');
        $this->check_tokenizer($tokenizer, 'prolog', false);

        $tokenizer = tokenizer_factory::get('prolog');
        $this->check_tokenizer($tokenizer, 'prolog', false);

        $tokenizer = vpl_tokenizer_factory::get('prolog');
        $this->check_tokenizer($tokenizer, 'prolog', false);

        $tokenizer = vpl_tokenizer_factory::get('prolog');
        $this->check_tokenizer($tokenizer, 'prolog', false);
    }

    /**
     * Method to test tokenizer_factory::get when new tokenizer is used
     */
    public function test_new_tokenizer() {
        $tokenizerlangs = tokenizer_similarity_utils::get_tokenizer_langs();

        foreach ($tokenizerlangs as $namelang) {
            $tokenizer = tokenizer_factory::get($namelang);
            $this->check_tokenizer($tokenizer, $namelang, true);

            $tokenizer = tokenizer_factory::get($namelang);
            $this->check_tokenizer($tokenizer, $namelang, true);

            $tokenizer = vpl_tokenizer_factory::get($namelang);
            $this->check_tokenizer($tokenizer, $namelang, true);

            $tokenizer = vpl_tokenizer_factory::get($namelang);
            $this->check_tokenizer($tokenizer, $namelang, true);
        }
    }

    private function check_tokenizer($tokenizer, $namelang, $newtokenizer=false) {
        if ($newtokenizer === false) {
            $this->assertTrue(isset($tokenizer) === true);
            $this->assertSame('vpl_tokenizer_' . $namelang, get_class($tokenizer));
        } else {
            $this->assertTrue(isset($tokenizer) === true);
            $this->assertSame('mod_vpl\tokenizer\tokenizer', get_class($tokenizer));
        }
    }
}
