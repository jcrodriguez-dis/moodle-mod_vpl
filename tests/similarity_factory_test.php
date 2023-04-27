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
 * Unit tests for mod_vpl\similarity\similarity_factory and vpl_similarity_factory.class
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
require_once($CFG->dirroot . '/mod/vpl/similarity/similarity_factory.class.php');

use mod_vpl\similarity\similarity_factory;
use \vpl_similarity_factory;

/**
 * Unit tests for \mod_vpl\similarity\similarity_factory class.
 *
 * @group mod_vpl
 * @group mod_vpl_vplt
 * @group mod_vpl_similarity
 * @group mod_vpl_similarity_factory
 * @covers \mod_vpl\similarity\similarity_factory
 */
class similarity_factory_test extends \advanced_testcase {
    /**
     * Method to test similarity_factory::ext2type
     */
    public function test_ext2type() {
        $validext = similarity_factory::ext2type('java');
        $this->assertSame('java', $validext);

        $validext = vpl_similarity_factory::ext2type('java');
        $this->assertSame('java', $validext);

        $invalidext = similarity_factory::ext2type('unexisted_extension');
        $this->assertFalse($invalidext);

        $invalidext = vpl_similarity_factory::ext2type('unexisted_extension');
        $this->assertFalse($invalidext);
    }

    /**
     * Method to test similarity_factory::get
     */
    public function test_invalid_ext_get() {
        $invalidext = similarity_factory::get('unexisted_extension');
        $this->assertTrue(is_null($invalidext));

        $invalidext = vpl_similarity_factory::get('unexisted_extension');
        $this->assertTrue(is_null($invalidext));
    }

    /**
     * Method to test similarity_factory::get
     */
    public function test_get() {
        $tokenizerlangs = testable_similarity_factory::get_available_languages();

        foreach ($tokenizerlangs as $namelang) {
            if (similarity_factory::ext2type($namelang) !== false) {
                $filelang = 'test_file.' . $namelang;

                $similarityclass = similarity_factory::get($filelang);
                $this->check_similarity($similarityclass, $namelang);

                $similarityclass = similarity_factory::get($filelang);
                $this->check_similarity($similarityclass, $namelang);

                $similarityclass = vpl_similarity_factory::get($filelang);
                $this->check_similarity($similarityclass, $namelang);

                $similarityclass = vpl_similarity_factory::get($filelang);
                $this->check_similarity($similarityclass, $namelang);
            }
        }
    }

    /**
     * Method to test similarity_factory::get when generic_similarity is used
     */
    public function test_get_with_generic_similarity() {
        $filelang = 'test_file.java';

        $similarityclass = similarity_factory::get($filelang, 1);
        $this->check_similarity($similarityclass, 'java', 1);

        $similarityclass = similarity_factory::get($filelang, 1);
        $this->check_similarity($similarityclass, 'java', 1);

        $similarityclass = vpl_similarity_factory::get($filelang, 1);
        $this->check_similarity($similarityclass, 'java', 1);

        $similarityclass = vpl_similarity_factory::get($filelang, 1);
        $this->check_similarity($similarityclass, 'java', 1);
    }

    /**
     * Method to test similarity_factory::get when old similarity is used
     */
    public function test_get_with_old_similarity() {
        $filelang = 'test_file.pl';

        $similarityclass = similarity_factory::get($filelang);
        $this->check_similarity($similarityclass, 'prolog', 2);

        $similarityclass = similarity_factory::get($filelang);
        $this->check_similarity($similarityclass, 'prolog', 2);

        $similarityclass = vpl_similarity_factory::get($filelang);
        $this->check_similarity($similarityclass, 'prolog', 2);

        $similarityclass = vpl_similarity_factory::get($filelang);
        $this->check_similarity($similarityclass, 'prolog', 2);
    }

    private function check_similarity($similarityclass, $namelang, $similaritytype=null) {
        $this->assertTrue(isset($similarityclass) === true);

        $similarityclasses = [
            'mod_vpl\similarity\similarity_' . $namelang,
            'mod_vpl\similarity\similarity_generic',
            'vpl_similarity_' . $namelang
        ];

        if (!is_numeric($similaritytype)) {
            $this->assertTrue(
                strcmp($similarityclasses[0], get_class($similarityclass)) == 0 ||
                strcmp($similarityclasses[1], get_class($similarityclass)) == 0 ||
                strcmp($similarityclasses[2], get_class($similarityclass)) == 0
            );
        } else {
            $expectedclass = $similarityclasses[$similaritytype];
            $this->assertSame($expectedclass, get_class($similarityclass));
        }
    }
}
