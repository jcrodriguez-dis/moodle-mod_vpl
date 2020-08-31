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
 * Unit tests for mod/vpl/lib.php.
 *
 * @package mod_vpl
 * @copyright  Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

use core_privacy\local\request\contextlist;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/lib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');
require_once($CFG->dirroot . '/mod/vpl/tests/base_test.php');
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/mod/vpl/vpl_submission_CE.class.php');

/**
 * Unit tests for \mod_vpl\privacy\provider class.
 * @group mod_vpl
 */
class mod_vpl_privacy_provider_testcase extends mod_vpl_base_testcase {
    /**
     * Fixture object of class \mod_vpl\privacy\provider
     */
    private $provider;
    /**
     * Method to create lib test fixture
     */
    protected function setUp() {
        parent::setUp();
        $this->setupinstances();
        $this->provider = new \mod_vpl\privacy\provider();
    }
    
    protected function check_vpls_contexts(array $vpls, contextlist $contexts) {
        $this->assertEquals(count($vpls), count($contexts));
        foreach ($vpls as $vpl) {
            $found = false;
            foreach ($contexts as $context) {
                if ($vpl->get_course_module()->id == $context->instanceid) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found);
        }
    }
    /**
     * Method to test get_contexts_for_userid.
     */
    public function test_get_contexts_for_userid() {
        // Student 0.
        $contexts = $this->provider->get_contexts_for_userid($this->students[0]->id);
        $this->assertCount(4, $contexts);
        $vpls = array($this->vplonefile, $this->vplmultifile, $this->vplvariations, $this->vplteamwork);
        $this->check_vpls_contexts($vpls, $contexts);
        
        // Student 1.
        $contexts = $this->provider->get_contexts_for_userid($this->students[1]->id);
        $this->assertCount(3, $contexts);
        $vpls = array($this->vplmultifile, $this->vplvariations, $this->vplteamwork);
        $this->check_vpls_contexts($vpls, $contexts);
    }
}
