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
 * Advanced grading mapping definition.
 * @package    mod_vpl
 * @copyright  2023 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_vpl\grades;

/**
 * Advanced grading mapping definition.
 * @copyright  2023 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradeitems implements
    \core_grades\local\gradeitem\advancedgrading_mapping,
    \core_grades\local\gradeitem\itemnumber_mapping {
    /**
     * Get the list of advanced grading item names for this component.
     *
     * @return array
     */
    public static function get_advancedgrading_itemnames(): array {
        return ['submissions'];
    }

    /**
     * Get the grade item mapping of item number to item name.
     *
     * @return array
     */
    public static function get_itemname_mapping_for_component(): array {
        return [0 => 'submissions'];
    }
}
