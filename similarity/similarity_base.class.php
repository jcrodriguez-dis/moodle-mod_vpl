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

require_once(dirname(__FILE__).'/tokenizer_factory.class.php');
require_once(dirname(__FILE__).'/similarity_sources.class.php');
require_once(dirname(__FILE__).'/../views/status_box.class.php');


/**
 * Similarity preprocesing information of a file
 * from any source (directory, zip file or vpl activity)
 */
abstract class vpl_similarity_base extends similarity_base {

}

// TODO refactor to a protected class.
class vpl_files_pair {
    static protected $idcounter = 0;
    static protected $mins1 = 100;
    static protected $mins2 = 100;
    static protected $mins3 = 100;
    static protected $maxs1 = 100;
    static protected $maxs2 = 100;
    static protected $maxs3 = 100;
    public $first;
    public $second;
    public $selected;
    public $s1;
    public $s2;
    public $s3;
    public $id;
    private $clusternumber;
    public function __construct($first = null, $second = null, $s1 = 0, $s2 = 0, $s3 = 0) {
        $this->first = $first;
        $this->second = $second;
        $this->selected = false;
        $this->s1 = $s1;
        $this->s2 = $s2;
        $this->s3 = $s3;
        $this->id = self::$idcounter ++;
        $this->clusternumber = 0;
    }
    public static function set_mins($s1, $s2, $s3) {
        self::$mins1 = $s1;
        self::$mins2 = $s2;
        self::$mins3 = $s3;
    }
    public static function set_maxs($s1, $s2, $s3) {
        self::$maxs1 = $s1;
        self::$maxs2 = $s2;
        self::$maxs3 = $s3;
    }
    public static function cmp($a, $b) {
        $al = $a->get_level();
        $bl = $b->get_level();
        if ($al == $bl) {
            return 0;
        }
        return $al > $bl ? 1 : - 1;
    }
    public function get_link() {
        global $OUTPUT;
        $text = '<span class="vpl_sim' . ( int ) $this->get_level1() . '">';
        $text .= ( int ) $this->s1;
        $text .= '</span>';
        $text .= '|';
        $text .= '<span class="vpl_sim' . ( int ) $this->get_level2() . '">';
        $text .= ( int ) $this->s2;
        $text .= '</span>';
        $text .= '|';
        $text .= '<span class="vpl_sim' . ( int ) $this->get_level3() . '">';
        $text .= ( int ) $this->s3;
        $text .= '</span>';
        if ($this->first->can_access() && $this->second->can_access()) {
            $url = vpl_mod_href( 'similarity/diff.php' );
            foreach ($this->first->link_parms( '1' ) as $parm => $value) {
                $url = vpl_url_add_param( $url, $parm, $value );
            }
            foreach ($this->second->link_parms( '2' ) as $parm => $value) {
                $url = vpl_url_add_param( $url, $parm, $value );
            }
            $options = array (
                'height' => 800,
                'width' => 900,
                'directories' => 0,
                'location' => 0,
                'menubar' => 0,
                'personalbar' => 0,
                'status' => 0,
                'toolbar' => 0
            );
            $action = new popup_action( 'click', $url, 'viewdiff' . $this->id, $options );
            $html = $OUTPUT->action_link( $url, $text, $action );
        } else {
            $html = $text;
        }
        $html .= $this->s1 >= self::$mins1 ? '*' : '';
        $html .= $this->s2 >= self::$mins2 ? '*' : '';
        $html .= $this->s3 >= self::$mins3 ? '*' : '';
        $html = '<div class="vpl_sim' . ( int ) $this->get_level() . '">' . $html . '</div>';
        return $html;
    }
    // Return normalize levels to 0-11.
    public static function normalize_level($value, $min, $max) {
        if (abs( $max - $min ) < 0.001) {
            return 0;
        }
        return min( (1.0 - (($value - $min) / ($max - $min))) * 11, 11 );
    }
    public function get_level1() {
        if (! isset( $this->level1 )) {
            $this->level1 = ( int ) self::normalize_level( $this->s1, self::$mins1, self::$maxs1 );
        }
        return $this->level1;
    }
    public function get_level2() {
        if (! isset( $this->level2 )) {
            $this->level2 = ( int ) self::normalize_level( $this->s2, self::$mins2, self::$maxs2 );
        }
        return $this->level2;
    }
    public function get_level3() {
        if (! isset( $this->level3 )) {
            $this->level3 = ( int ) self::normalize_level( $this->s3, self::$mins3, self::$maxs3 );
        }
        return $this->level3;
    }
    public function get_level() {
        if (! isset( $this->level )) {
            $level1 = $this->get_level1();
            $level2 = $this->get_level2();
            $level3 = $this->get_level3();
            $this->level = min( $level1, $level2, $level3, 11 );
        }
        return $this->level;
    }
    public function set_cluster($value) {
        $this->clusternumber = $value;
    }
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
    public static function get_selected(&$files, $maxselected, $slimit, $spb) {
        $vs1 = array ();
        $vs2 = array ();
        $vs3 = array ();
        $minlevel1 = 0;
        $minlevel2 = 0;
        $minlevel3 = 0;
        $maxlevel1 = 100;
        $maxlevel2 = 100;
        $maxlevel3 = 100;
        $selected = array ();
        $jlimit = count( $files );
        if ($jlimit < $slimit) {
            $slimit = $jlimit;
        }
        $spb->set_max( $slimit );
        for ($i = 0; $i < $slimit; $i ++) { // Search similarity with.
            $spb->set_value( $i + 1 );
            $current = $files[$i];
            $currenttype = $current->get_type();
            $userid = $current->get_userid();
            for ($j = $i + 1; $j < $jlimit; $j ++) { // Compare with all others.
                $other = $files[$j];
                // If not the same language then skip.
                if ($currenttype != $other->get_type() || ($userid != '' && $userid == $other->get_userid())) {
                    continue;
                }
                // Calculate metrics.
                $s1 = $current->similarity1( $other );
                $s2 = $current->similarity2( $other );
                $s3 = $current->similarity3( $other );
                if ($s1 >= $minlevel1 || $s2 >= $minlevel2 || $s3 >= $minlevel3) {
                    $case = new vpl_files_pair( $files[$i], $files[$j], $s1, $s2, $s3 );
                    $maxlevel1 = max( $s1, $maxlevel1 );
                    $maxlevel2 = max( $s2, $maxlevel2 );
                    $maxlevel3 = max( $s3, $maxlevel3 );
                    if ($s1 >= $minlevel1) {
                        $vs1[] = $case;
                        if (count( $vs1 ) > 2 * $maxselected) {
                            self::filter_selected( $vs1, $maxselected, $minlevel1, 1 );
                        }
                    }
                    if ($s2 >= $minlevel2) {
                        $vs2[] = $case;
                        if (count( $vs2 ) > 2 * $maxselected) {
                            self::filter_selected( $vs2, $maxselected, $minlevel2, 2 );
                        }
                    }
                    if ($s3 >= $minlevel3) {
                        $vs3[] = $case;
                        if (count( $vs3 ) > 2 * $maxselected) {
                            self::filter_selected( $vs3, $maxselected, $minlevel3, 3 );
                        }
                    }
                }
            }
        }
        self::filter_selected( $vs1, $maxselected, $minlevel1, 1, true );
        self::filter_selected( $vs2, $maxselected, $minlevel2, 2, true );
        self::filter_selected( $vs3, $maxselected, $minlevel3, 3, true );
        vpl_files_pair::set_mins( $minlevel1, $minlevel2, $minlevel3 );
        vpl_files_pair::set_maxs( $maxlevel1, $maxlevel2, $maxlevel3 );
        // Merge vs1, vs2 and vs3.
        $max = count( $vs1 );
        for ($i = 0; $i < $max; $i ++) {
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
    static protected $corder = null;
    public static function filter_selected(&$vec, $maxselected, &$minlevel, $sid, $last = false) {
        if (count( $vec ) > $maxselected || ($last && count( $vec ) > 0)) {
            if (self::$corder === null) {
                self::$corder = new vpl_similarity();
            }
            if (! usort( $vec, array (
                self::$corder,
                'cmp_selected' . $sid
            ) )) {
                debugging( 'usort error' );
            }
            $field = 's' . $sid;
            $vec = array_slice( $vec, 0, $maxselected );
            $minlevel = $vec[count( $vec ) - 1]->$field;
        }
    }
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
