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
 * Similarity utility class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\similarity;

/**
 * Utility class to get list of preprocessed files
 */
class utility {
    /**
     * Get the selected files based on their similarity scores.
     *
     * @param array $files The array of file_pair objects to process.
     * @param int $maxselected The maximum number of files to select.
     * @param int $slimit The limit of files to process.
     * @param status_box $spb The status box to update progress.
     * @return array An array of selected file_pair objects.
     */
    public static function get_selected(&$files, $maxselected, $slimit, $spb) {
        $vs1 = [];
        $vs2 = [];
        $vs3 = [];
        $minlevel1 = 0;
        $minlevel2 = 0;
        $minlevel3 = 0;
        $maxlevel1 = 100;
        $maxlevel2 = 100;
        $maxlevel3 = 100;
        $selected = [];
        $jlimit = count($files);
        if ($jlimit < $slimit) {
            $slimit = $jlimit;
        }
        $spb->set_max($slimit);
        for ($i = 0; $i < $slimit; $i++) { // Search similarity with.
            $spb->set_value($i + 1);
            $current = $files[$i];
            $currenttype = $current->get_type();
            $userid = $current->get_userid();
            for ($j = $i + 1; $j < $jlimit; $j++) { // Compare with all others.
                $other = $files[$j];
                // If not the same language then skip.
                if ($currenttype != $other->get_type() || ($userid != '' && $userid == $other->get_userid())) {
                    continue;
                }
                // Calculate metrics.
                $s1 = $current->similarity1($other);
                $s2 = $current->similarity2($other);
                $s3 = $current->similarity3($other);
                if ($s1 >= $minlevel1 || $s2 >= $minlevel2 || $s3 >= $minlevel3) {
                    $case = new file_pair($files[$i], $files[$j], $s1, $s2, $s3);
                    $maxlevel1 = max($s1, $maxlevel1);
                    $maxlevel2 = max($s2, $maxlevel2);
                    $maxlevel3 = max($s3, $maxlevel3);
                    if ($s1 >= $minlevel1) {
                        $vs1[] = $case;
                        if (count($vs1) > 2 * $maxselected) {
                            self::filter_selected($vs1, $maxselected, $minlevel1, 1);
                        }
                    }
                    if ($s2 >= $minlevel2) {
                        $vs2[] = $case;
                        if (count($vs2) > 2 * $maxselected) {
                            self::filter_selected($vs2, $maxselected, $minlevel2, 2);
                        }
                    }
                    if ($s3 >= $minlevel3) {
                        $vs3[] = $case;
                        if (count($vs3) > 2 * $maxselected) {
                            self::filter_selected($vs3, $maxselected, $minlevel3, 3);
                        }
                    }
                }
            }
        }
        self::filter_selected($vs1, $maxselected, $minlevel1, 1, true);
        self::filter_selected($vs2, $maxselected, $minlevel2, 2, true);
        self::filter_selected($vs3, $maxselected, $minlevel3, 3, true);
        file_pair::set_mins($minlevel1, $minlevel2, $minlevel3);
        file_pair::set_maxs($maxlevel1, $maxlevel2, $maxlevel3);
        // Merge vs1, vs2 and vs3.
        $max = count($vs1);
        for ($i = 0; $i < $max; $i++) {
            if (! $vs1[$i]->selected) {
                $selected[] = $vs1[$i];
                $vs1[$i]->selected = true;
            }
            if (! $vs2[$i]->selected) {
                $selected[] = $vs2[$i];
                $vs2[$i]->selected = true;
            }
            if (! $vs3[$i]->selected) {
                $selected[] = $vs3[$i];
                $vs3[$i]->selected = true;
            }
        }
        return $selected;
    }

    /**
     * Static variable to hold the order comparator for selected files.
     * This is used to sort the files based on their similarity scores.
     * @var utility|null
     */
    protected static $corder = null;

    /**
     * Filter the selected files based on the maximum number of selections allowed.
     *
     * @param array $vec The vector of file_pair objects to filter.
     * @param int $maxselected The maximum number of selections allowed.
     * @param int $minlevel The minimum level to keep in the filtered vector.
     * @param int $sid The similarity score identifier (1, 2, or 3).
     * @param bool $last Whether to apply the filter to the last element.
     */
    public static function filter_selected(&$vec, $maxselected, &$minlevel, $sid, $last = false) {
        if (count($vec) > $maxselected || ($last && count($vec) > 0)) {
            if (self::$corder === null) {
                self::$corder = new utility();
            }
            if (
                ! usort($vec, [
                self::$corder,
                'cmp_selected' . $sid,
                ])
            ) {
                debugging('usort error');
            }
            $field = 's' . $sid;
            $vec = array_slice($vec, 0, $maxselected);
            $minlevel = $vec[count($vec) - 1]->$field;
        }
    }

    /**
     * Compare two file_pair objects based on their s1 similarity score.
     *
     * @param file_pair $a First object to compare.
     * @param file_pair $b Second object to compare.
     * @return int Returns -1 if $a is less than $b, 1 if $a is greater than $b, and 0 if they are equal.
     */
    public static function cmp_selected1($a, $b) {
        if ($a->s1 == $b->s1) {
            if ($a->s3 == $b->s3) {
                if ($a->s2 == $b->s2) {
                    return 0;
                }
                return ($a->s2 > $b->s2) ? - 1 : 1;
            }
            return ($a->s3 > $b->s3) ? - 1 : 1;
        }
        return ($a->s1 > $b->s1) ? - 1 : 1;
    }

    /**
     * Compare two file_pair objects based on their s2 similarity score.
     *
     * @param file_pair $a First object to compare.
     * @param file_pair $b Second object to compare.
     * @return int Returns -1 if $a is less than $b, 1 if $a is greater than $b, and 0 if they are equal.
     */
    public static function cmp_selected2($a, $b) {
        if ($a->s2 == $b->s2) {
            if ($a->s1 == $b->s1) {
                if ($a->s3 == $b->s3) {
                    return 0;
                }
                return ($a->s3 > $b->s3) ? - 1 : 1;
            }
            return ($a->s1 > $b->s1) ? - 1 : 1;
        }
        return ($a->s2 > $b->s2) ? - 1 : 1;
    }

    /**
     * Compare two file_pair objects based on their s3 similarity score.
     *
     * @param file_pair $a First object to compare.
     * @param file_pair $b Second object to compare.
     * @return int Returns -1 if $a is less than $b, 1 if $a is greater than $b, and 0 if they are equal.
     */
    public static function cmp_selected3($a, $b) {
        if ($a->s3 == $b->s3) {
            if ($a->s1 == $b->s1) {
                if ($a->s2 == $b->s2) {
                    return 0;
                }
                return ($a->s2 > $b->s2) ? - 1 : 1;
            }
            return ($a->s1 > $b->s1) ? - 1 : 1;
        }
        return ($a->s3 > $b->s3) ? - 1 : 1;
    }
}
