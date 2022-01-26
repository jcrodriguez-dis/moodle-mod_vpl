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
 * Social breadth indicator of VPL.
 * Inspired by/copy from mod_assign 2017 David Monllao {@link http://www.davidmonllao.com}
 *
 * @package mod_vpl
 * @copyright 2018 onward Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl\analytics\indicator;

/**
 * Social breadth indicator of VPL.
 * Inspired by/copy from mod_assign 2017 David Monllao {@link http://www.davidmonllao.com}
 * @package mod_vpl
 * @copyright 2018 onward Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
class social_breadth extends activity_base {

    /**
     * Returns the name of the indicator.
     *
     * @return \lang_string
     */
    public static function get_name() : \lang_string {
        return new \lang_string('indicator:socialbreadth', 'mod_vpl');
    }

    /**
     * Returns the indicator type.
     *
     * @return string
     */
    public function get_indicator_type() {
        return self::INDICATOR_SOCIAL;
    }

    /**
     * Returns the indicator breadth level.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return int
     */
    public function get_social_breadth_level(\cm_info $cm) {
        return self::SOCIAL_LEVEL_2;
    }
}
