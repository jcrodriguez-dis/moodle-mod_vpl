<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Activity base class.
 * Inspired by/copy from mod_assign 2017 David Monllao {@link http://www.davidmonllao.com}
 *
 * @package mod_vpl
 * @copyright 2018 onward Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl\analytics\indicator;

/**
 * Activity base class.
 *
 * @package mod_vpl
 * @copyright 2018 onward Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
abstract class activity_base extends \core_analytics\local\indicator\community_of_inquiry_activity {

    /**
     * return the list of events of feedback viewed
     *
     * @return string[]
     */
    protected function feedback_viewed_events() {
        return ['\mod_vpl\event\submission_grade_viewed', '\mod_vpl\event\submission_viewed'];
    }

    /**
     * We need the grade to be released to the student to consider that feedback has been provided
     *
     * @return bool
     */
    protected function feedback_check_grades() {
        return true;
    }

    protected function feedback_submitted_events() {
        return ['\mod_vpl\event\submission_uploaded'];
    }

    /**
     * Returns the field that close the activity by time.
     *
     * @return string
     */
    protected function get_timeclose_field() {
        return 'duedate';
    }

}
