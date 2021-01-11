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
 * Unit tests for class \mod_vpl\util\phpconfig
 *
 * @package   mod_vpl
 * @copyright Juan Carlos Rodríguez-del-Pino
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../locallib.php');

/**
 * Unit tests for \mod_vpl\util\phpconfig class.
 *
 * @group mod_vpl
 */
class mod_vpl_util_phpconfig extends advanced_testcase {
    /**
     * Method to test function get_bytes(string $value): int
     */
    public function test_get_bytes() {
        $cases = [[123, '123'], [13 * 1024, '13k'], [7 * 1024 * 1024, '7M'], [1024 * 1024 * 1024, '1G']];
        foreach ($cases as $case) {
            $this->assertEquals( $case[0], \mod_vpl\util\phpconfig::get_bytes($case[1]), $case[1]);
        }

    }
    /**
     * Method to test function get_post_max_size(): int
     */
    public function test_get_post_max_size() {
        // Untestable, checks only callable.
        $this->assertTrue(\mod_vpl\util\phpconfig::get_post_max_size() > 0);
    }
    /**
     * Method to test function increase_memory_limit(): void
     */
    public function test_increase_memory_limit() {
        $maxpost = \mod_vpl\util\phpconfig::get_post_max_size();
        \mod_vpl\util\phpconfig::increase_memory_limit();
        $memorylimit = \mod_vpl\util\phpconfig::get_bytes(ini_get('memory_limit'));
        $this->assertTrue($maxpost * 3 <= $memorylimit);
    }
}
