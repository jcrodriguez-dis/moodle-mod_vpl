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
 * Similarity base and utility class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

use mod_vpl\similarity\similarity_base;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/tokenizer_factory.class.php');
require_once(dirname(__FILE__) . '/similarity_sources.class.php');
require_once(dirname(__FILE__) . '/../views/status_box.class.php');

/**
 * Similarity preprocesing information of a file
 * from any source (directory, zip file or vpl activity)
 */
abstract class vpl_similarity_base extends similarity_base {
}

/**
 * Class to hold a pair of files and their similarity metrics.
 * It is used to display the similarity results in the VPL activity.
 */
class vpl_files_pair {
    /**
     * Static variable to generate the unique identifier for each file pair.
     * It is incremented each time a new file pair is created.
     *
     * @var int
     */
    protected static $idcounter = 0;

    /**
     * Minimum similarity scores for the first metric.
     * This is used to filter out pairs with low similarity.
     * @var float
     */
    protected static $mins1 = 100;

    /**
     * Minimum similarity scores for the second metric.
     * This is used to filter out pairs with low similarity.
     * @var float
     */
    protected static $mins2 = 100;

    /**
     * Minimum similarity scores for the third metric.
     * This is used to filter out pairs with low similarity.
     * @var float
     */
    protected static $mins3 = 100;

    /**
     * Minimum similarity scores for the first metric.
     * @var float
     */
    protected static $maxs1 = 100;

    /**
     * Maximum similarity scores for the second metric.
     * @var float
     */
    protected static $maxs2 = 100;

    /**
     * Maximum similarity scores for the third metric.
     * @var float
     */
    protected static $maxs3 = 100;

    /**
     * The first file in the pair.
     * This is used to compare with the second file for similarity metrics.
     *
     * @var mixed
     */
    public $first;

    /**
     * The second file in the pair.
     * This is used to compare with the first file for similarity metrics.
     *
     * @var mixed
     */
    public $second;

    /**
     * Indicates whether this file pair is selected.
     * This is used to mark the file pair as selected for further processing.
     * @var bool
     */
    public $selected;

    /**
     * Similarity score for the first metric.
     * @var float
     */
    public $s1;

    /**
     * Similarity score for the second metric.
     * @var float
     */
    public $s2;

    /**
     * Similarity score for the third metric.
     * @var float
     */
    public $s3;

    /**
     * Unique identifier for the file pair.
     * This is used to differentiate between different pairs of files.
     *
     * @var int
     */
    public $id;

    /**
     * Level of similarity for this file pair.
     * @var float
     */
    public $level;

    /**
     * Level 1 is used for the first similarity metric.
     * It is calculated based on the first similarity score.
     *
     * @var float
     */
    public $level1;

    /**
     * Level 2 is used for the second similarity metric.
     * It is calculated based on the second similarity score.
     *
     * @var number
     */
    public $level2;

    /**
     * Level 3 is used for the third similarity metric.
     * It is calculated based on the third similarity score.
     *
     * @var number
     */
    public $level3;

    /**
     * Cluster number for grouping similar files.
     * This is used to identify which cluster this file pair belongs to.
     *
     * @var int
     */
    private $clusternumber;

    /**
     * Constructor for the vpl_files_pair class.
     * Initializes the pair of files and their similarity scores.
     *
     * @param mixed $first The first file in the pair.
     * @param mixed $second The second file in the pair.
     * @param float $s1 The similarity score for the first metric.
     * @param float $s2 The similarity score for the second metric.
     * @param float $s3 The similarity score for the third metric.
     */
    public function __construct($first = null, $second = null, $s1 = 0, $s2 = 0, $s3 = 0) {
        $this->first = $first;
        $this->second = $second;
        $this->selected = false;
        $this->s1 = $s1;
        $this->s2 = $s2;
        $this->s3 = $s3;
        $this->id = self::$idcounter++;
        $this->clusternumber = 0;
    }

    /**
     * Sets the minimum similarity scores for the three metrics.
     * This method is used to update the minimum similarity scores
     * for the first, second, and third metrics.
     *
     * @param float $s1 The minimum score for the first metric.
     * @param float $s2 The minimum score for the second metric.
     * @param float $s3 The minimum score for the third metric.
     */
    public static function set_mins($s1, $s2, $s3) {
        self::$mins1 = $s1;
        self::$mins2 = $s2;
        self::$mins3 = $s3;
    }

    /**
     * Sets the maximum similarity scores for the three metrics.
     * This method is used to update the maximum similarity scores
     * for the first, second, and third metrics.
     *
     * @param float $s1 The maximum score for the first metric.
     * @param float $s2 The maximum score for the second metric.
     * @param float $s3 The maximum score for the third metric.
     */
    public static function set_maxs($s1, $s2, $s3) {
        self::$maxs1 = $s1;
        self::$maxs2 = $s2;
        self::$maxs3 = $s3;
    }

    /**
     * Returns the type of similarity.
     * This method is used to identify the type of similarity for this file pair.
     *
     * @param mixed $a The first file in the pair.
     * @param mixed $b The second file in the pair.
     * @return int The type of similarity, which is 0 for generic file pairs.
     */
    public static function cmp($a, $b) {
        $al = $a->get_level();
        $bl = $b->get_level();
        if ($al == $bl) {
            return 0;
        }
        return $al > $bl ? 1 : - 1;
    }

    /**
     * Returns link to the similarity metrics.
     * This method generates an HTML link that displays the similarity scores
     * for the first, second, and third metrics.
     *
     * @return string The HTML link with the similarity scores.
     */
    public function get_link() {
        global $OUTPUT;
        $text = '<span class="vpl_sim' . (int) $this->get_level1() . '">';
        $text .= (int) $this->s1;
        $text .= '</span>';
        $text .= '|';
        $text .= '<span class="vpl_sim' . (int) $this->get_level2() . '">';
        $text .= (int) $this->s2;
        $text .= '</span>';
        $text .= '|';
        $text .= '<span class="vpl_sim' . (int) $this->get_level3() . '">';
        $text .= (int) $this->s3;
        $text .= '</span>';
        if ($this->first->can_access() && $this->second->can_access()) {
            $url = vpl_mod_href('similarity/diff.php');
            foreach ($this->first->link_parms('1') as $parm => $value) {
                $url = vpl_url_add_param($url, $parm, $value);
            }
            foreach ($this->second->link_parms('2') as $parm => $value) {
                $url = vpl_url_add_param($url, $parm, $value);
            }
            $options = [
                'height' => 800,
                'width' => 900,
                'directories' => 0,
                'location' => 0,
                'menubar' => 0,
                'personalbar' => 0,
                'status' => 0,
                'toolbar' => 0,
            ];
            $action = new popup_action('click', $url, 'viewdiff' . $this->id, $options);
            $html = $OUTPUT->action_link($url, $text, $action);
        } else {
            $html = $text;
        }
        $html .= $this->s1 >= self::$mins1 ? '*' : '';
        $html .= $this->s2 >= self::$mins2 ? '*' : '';
        $html .= $this->s3 >= self::$mins3 ? '*' : '';
        $html = '<div class="vpl_sim' . (int) $this->get_level() . '">' . $html . '</div>';
        return $html;
    }

    /**
     * Normalizes the similarity level to a range of 0-11.
     *
     * @param float $value The similarity score to normalize.
     * @param float $min The minimum score for normalization.
     * @param float $max The maximum score for normalization.
     * @return float The normalized level, capped at 11.
     */
    public static function normalize_level($value, $min, $max) {
        if (abs($max - $min) < 0.001) {
            return 0;
        }
        return min((1.0 - (($value - $min) / ($max - $min))) * 11, 11);
    }

    /**
     * Returns the level of similarity for the first metric.
     * It normalizes the similarity score to a range of 0-11.
     *
     * @return int The normalized level of similarity for the first metric.
     */
    public function get_level1() {
        if (! isset($this->level1)) {
            $this->level1 = (int) self::normalize_level($this->s1, self::$mins1, self::$maxs1);
        }
        return $this->level1;
    }

    /**
     * Returns the level of similarity for the second metric.
     * It normalizes the similarity score to a range of 0-11.
     *
     * @return int The normalized level of similarity for the second metric.
     */
    public function get_level2() {
        if (! isset($this->level2)) {
            $this->level2 = (int) self::normalize_level($this->s2, self::$mins2, self::$maxs2);
        }
        return $this->level2;
    }

    /**
     * Returns the level of similarity for the third metric.
     * It normalizes the similarity score to a range of 0-11.
     *
     * @return int The normalized level of similarity for the third metric.
     */
    public function get_level3() {
        if (! isset($this->level3)) {
            $this->level3 = (int) self::normalize_level($this->s3, self::$mins3, self::$maxs3);
        }
        return $this->level3;
    }

    /**
     * Returns the minimum level of similarity for this file pair.
     * It calculates the minimum of the three levels and limits it to a maximum of 11.
     *
     * @return int The minimum level of similarity, capped at 11.
     */
    public function get_level() {
        if (! isset($this->level)) {
            $level1 = $this->get_level1();
            $level2 = $this->get_level2();
            $level3 = $this->get_level3();
            $this->level = min($level1, $level2, $level3, 11);
        }
        return $this->level;
    }

    /**
     * Sets the cluster number for this file pair.
     *
     * @param int $value The cluster number to set.
     */
    public function set_cluster($value) {
        $this->clusternumber = $value;
    }

    /**
     * Returns the cluster number as a link.
     *
     * @return string The HTML link to the cluster number or an empty string if the cluster number is 0.
     */
    public function get_cluster() {
        if ($this->clusternumber > 0) {
            return '<a href="#clu' . $this->clusternumber . '">' . $this->clusternumber . '</a>';
        } else {
            return '';
        }
    }
}

/**
 * Utility class to get list of preprocessed files
 */
class vpl_similarity {
    /**
     * Get the selected files based on their similarity scores.
     *
     * @param array $files The array of vpl_files_pair objects to process.
     * @param int $maxselected The maximum number of files to select.
     * @param int $slimit The limit of files to process.
     * @param status_box $spb The status box to update progress.
     * @return array An array of selected vpl_files_pair objects.
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
                    $case = new vpl_files_pair($files[$i], $files[$j], $s1, $s2, $s3);
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
        vpl_files_pair::set_mins($minlevel1, $minlevel2, $minlevel3);
        vpl_files_pair::set_maxs($maxlevel1, $maxlevel2, $maxlevel3);
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
     * @var vpl_similarity|null
     */
    protected static $corder = null;

    /**
     * Filter the selected files based on the maximum number of selections allowed.
     *
     * @param array $vec The vector of vpl_files_pair objects to filter.
     * @param int $maxselected The maximum number of selections allowed.
     * @param int $minlevel The minimum level to keep in the filtered vector.
     * @param int $sid The similarity score identifier (1, 2, or 3).
     * @param bool $last Whether to apply the filter to the last element.
     */
    public static function filter_selected(&$vec, $maxselected, &$minlevel, $sid, $last = false) {
        if (count($vec) > $maxselected || ($last && count($vec) > 0)) {
            if (self::$corder === null) {
                self::$corder = new vpl_similarity();
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
     * Compare two vpl_files_pair objects based on their s1 similarity score.
     *
     * @param vpl_files_pair $a First object to compare.
     * @param vpl_files_pair $b Second object to compare.
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
     * Compare two vpl_files_pair objects based on their s2 similarity score.
     *
     * @param vpl_files_pair $a First object to compare.
     * @param vpl_files_pair $b Second object to compare.
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
     * Compare two vpl_files_pair objects based on their s3 similarity score.
     *
     * @param vpl_files_pair $a First object to compare.
     * @param vpl_files_pair $b Second object to compare.
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
