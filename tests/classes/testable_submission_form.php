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
 * Submission form helper for unit tests.
 *
 * @package mod_vpl
 * @copyright 2026 Juan Carlos Rodriguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_vpl\tests;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/forms/submission_form.php');

/**
 * Submission form exposing its internal controls for tests.
 */
class testable_submission_form extends \mod_vpl_submission_form {
    /**
     * Get the internal form for assertions.
     * Exposes the protected parent method to tests.
     * @return \MoodleQuickForm
     */
    public function getinternalform() { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
        return parent::getinternalform();
    }
}
