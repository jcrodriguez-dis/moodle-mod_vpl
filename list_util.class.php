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
 * List utility class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
class vpl_list_util {
    /**
     * @var array $fields
     * Static array of fields to compare.
     * This array contains the fields that will be used for sorting VPL instances.
     * The order of the fields matters, as it determines the priority of comparison.
     * For example, if 'duedate' is first, it will be compared before 'startdate'.
     */
    protected static $fields;

    /**
     * @var int $ascending
     * Value to return when ascending or descending order.
     * -1 for ascending order, 1 for descending order.
     */
    protected static $ascending;

    /**
     * Compare two VPL instances for sorting.
     *
     * This method compares two VPL instances based on the fields set in
     * the static $fields array. It returns a negative value if the first
     * instance should come before the second, a positive value if the first
     * instance should come after the second, and zero if they are equal.
     * It uses the static $ascending value to determine the order.
     *
     * @param object $avpl First VPL instance to compare.
     * @param object $bvpl Second VPL instance to compare.
     * @return int Returns -1, 0, or 1 based on the comparison
     */
    public static function cpm($avpl, $bvpl) {
        $a = $avpl->get_instance();
        $b = $bvpl->get_instance();
        foreach (self::$fields as $field) {
            $avalue = $a->$field;
            $bvalue = $b->$field;
            if ($avalue == $bvalue) {
                continue;
            } else if ($avalue < $bvalue) {
                return self::$ascending;
            } else {
                return - self::$ascending;
            }
        }
        return 0;
    }

    /**
     * Set field and order to sort by.
     *
     * This method sets the fields to sort by and the order (ascending or descending).
     * If the field is not known, it will default to 'duedate'.
     * The order is set to ascending by default.
     *
     * @param string $field Field to sort by.
     * @param bool $ascending True for ascending order, false for descending.
     */
    public static function set_order($field, $ascending = true) {
        $sortfields = [
                'name' => [
                        'name',
                ],
                'startdate' => [
                        'startdate',
                        'duedate',
                        'name',
                ],
                'duedate' => [
                        'duedate',
                        'startdate',
                        'name',
                ],
                'automaticgrading' => [
                        'automaticgrading',
                        'duedate',
                        'name',
                ],
        ];
        if (isset($sortfields[$field])) {
            self::$fields = $sortfields[$field];
        } else { // Unknow field.
            self::$fields = $sortfields['duedate'];
        }
        if ($ascending) {
            self::$ascending = - 1;
        } else {
            self::$ascending = 1;
        }
    }
    /**
     * Return a URL for selecting order ascent or descending.
     *
     * @param string $burl Base URL to add parameters.
     * @param string $sort Sort field.
     * @param string $instanceselection Instance selection.
     * @param string $selsort Current selected sort field.
     * @param string $seldir Current selected sort direction.
     * @return string HTML link with icon to change sort direction.
     */
    public static function vpl_list_arrow($burl, $sort, $instanceselection, $selsort, $seldir) {
        global $OUTPUT;
        $newdir = 'down'; // Dir to go if clicked.
        $url = vpl_url_add_param($burl, 'sort', $sort);
        $url = vpl_url_add_param($url, 'selection', $instanceselection);
        if ($sort == $selsort) {
            $sortdir = $seldir;
            if ($sortdir == 'up') {
                $newdir = 'down';
            } else if ($sortdir == 'down') {
                $newdir = 'up';
            } else { // Unknow sortdir.
                $sortdir = 'down';
            }
            $url = vpl_url_add_param($url, 'sortdir', $newdir);
        } else {
            $sortdir = 'move';
        }
        return '<a href="' . $url . '">' . ($OUTPUT->pix_icon('t/' . $sortdir, get_string($sortdir))) . '</a>';
    }

    /**
     * Count the number of graded submissions in a VPL instance.
     *
     * @param object $vpl VPL instance to count graded submissions for.
     * @return array An associative array with two keys:
     *               - 'submissions': Total number of submissions.
     *               - 'graded': Number of graded submissions.
     */
    public static function count_graded($vpl) {
        $subs = $vpl->all_last_user_submission('s.dategraded, s.userid, s.groupid');
        $students = $vpl->get_students();
        $subs = $vpl->filter_submissions_by_students($subs, $students);
        return [
                'submissions' => count($subs),
                'graded' => $vpl->number_of_graded_submissions($subs),
        ];
    }
}
