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
 * File pair class for similarity results
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\similarity;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/locallib.php');
use popup_action;

/**
 * Class to hold a pair of files and their similarity metrics.
 * It is used to display the similarity results in the VPL activity.
 */
class file_pair {
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
     * Constructor for the file_pair class.
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
