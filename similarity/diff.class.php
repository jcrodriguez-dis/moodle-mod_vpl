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
 * Class to show two files diff
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once(dirname(__FILE__).'/../vpl_submission.class.php');
require_once(dirname(__FILE__).'/similarity_factory.class.php');
require_once(dirname(__FILE__).'/similarity_sources.class.php');

class vpl_diff {
    /**
     * Remove chars and digits
     *
     * @param $line string
     *            to process
     * @return string without chars and digits
     */
    static public function removealphanum($line) {
        $ret = '';
        $l = strlen( $line );
        // Parse line to remove alphanum chars.
        for ($i = 0; $i < $l; $i ++) {
            $c = $line [$i];
            if (! ctype_alnum( $c ) && $c != ' ') {
                $ret .= $c;
            }
        }
        return $ret;
    }

    /**
     * Calculate the similarity of two lines
     *
     * @param
     *            $line1
     * @param
     *            $line2
     * @return int (3 => trimmed equal, 2 =>removealphanum , 1 => start of line , 0 => not equal)
     */
    static public function diffline($line1, $line2) {
        // TODO Refactor.
        // This is a bad solution that must be rebuild to consider diferent languages.
        // Compare trimed text.
        $line1 = trim( $line1 );
        $line2 = trim( $line2 );
        if ($line1 == $line2) {
            if (strlen( $line1 ) > 0) {
                return 3;
            } else {
                return 1;
            }
        }
        // Compare filtered text (removing alphanum).
        $ran1 = self::removealphanum( $line1 );
        $limit = strlen( $ran1 );
        if ($limit > 0) {
            if ($limit > 3) {
                $limite = 3;
            }
            if (strncmp( $ran1, self::removealphanum( $line2 ), $limit ) == 0) {
                return 2;
            }
        }
        // Compare start of line.
        $l = 4;
        if ($l > strlen( $line1 )) {
            $l = strlen( $line1 );
        }
        if ($l > strlen( $line2 )) {
            $l = strlen( $line2 );
        }
        for ($i = 0; $i < $l; ++ $i) {
            if ($line1 [$i] != $line2 [$i]) {
                break;
            }
        }
        return $i > 0 ? 1 : 0;
    }
    static public function newlineinfo($type, $ln1, $ln2 = 0) {
        $ret = new StdClass();
        $ret->type = $type;
        $ret->ln1 = $ln1;
        $ret->ln2 = $ln2;
        return $ret;
    }

    /**
     * Initialize used matrix
     *
     * @param $matrix matrix
     *            to initialize
     * @param $prev matrix
     *            to initialize
     * @param $nl1 number
     *            of rows
     * @param $nl2 number
     *            of columns
     * @return void
     */
    static public function initauxiliarmatrices(&$matrix, &$prev, $nl1, $nl2) {
        // Set the matrix[0..nl1+1][0..nl2+1] to 0.
        $row = array_pad( array (), $nl2 + 1, 0 );
        $matrix = array_pad( array (), $nl1 + 1, $row );
        // Set the prev matrix [0..nl1+1][0..nl2+1] to 0.
        $prev = $matrix;

        // Update first column.
        for ($i = 0; $i <= $nl1; $i ++) {
            $matriz [$i] [0] = 0;
            $prev [$i] [0] = - 1;
        }
        // Update first row.
        for ($j = 1; $j <= $nl2; $j ++) {
            $matriz [0] [$j] = 0;
            $prev [0] [$j] = 1;
        }
    }

    static public function similine($line1, $line2, $pattern) {
        return preg_replace($pattern, '', $line1) == preg_replace($pattern, '', $line2);
    }

    /**
     * Calculate diff for two array of lines
     *
     * @param $lines1 array
     *            of string
     * @param $lines2 array
     *            of string
     * @return array of objects with info to show the two array of lines
     */
    static public function calculatediff($lines1, $lines2) {
        $ret = array ();
        $nl1 = count( $lines1 );
        $nl2 = count( $lines2 );
        if ($nl1 == 0 && $nl2 == 0) {
            return false;
        }
        if ($nl1 == 0) { // There is no first file.
            foreach ($lines2 as $pos => $line) {
                $ret [] = self::newlineinfo( '>', 0, $pos + 1 );
            }
            return $ret;
        }
        if ($nl2 == 0) { // There is no second file.
            foreach ($lines1 as $pos => $line) {
                $ret [] = self::newlineinfo( '<', $pos + 1 );
            }
            return $ret;
        }
        self::initauxiliarmatrices( $matrix, $prev, $nl1, $nl2 );

        // Matrix processing.
        for ($i = 1; $i <= $nl1; $i ++) {
            $line = $lines1 [$i - 1];
            for ($j = 1; $j <= $nl2; $j ++) {
                if ($matrix [$i] [$j - 1] > $matrix [$i - 1] [$j]) {
                    $max = $matrix [$i] [$j - 1];
                    $best = 1;
                } else {
                    $max = $matrix [$i - 1] [$j];
                    $best = - 1;
                }
                $prize = self::diffline( $line, $lines2 [$j - 1] );
                if ($matrix [$i - 1] [$j - 1] + $prize >= $max) {
                    $max = $matrix [$i - 1] [$j - 1] + $prize;
                    $best = 0;
                }
                $matrix [$i] [$j] = $max;
                $prev [$i] [$j] = $best;
            }
        }

        // Calculate show info.
        $limit = $nl1 + $nl2;
        $pairs = array ();
        $pi = $nl1;
        $pj = $nl2;
        while ( (! ($pi == 0 && $pj == 0)) && $limit > 0 ) {
            $pair = new stdClass();
            $pair->i = $pi;
            $pair->j = $pj;
            $pairs [] = $pair;
            $p = $prev [$pi] [$pj];
            if ($p == 0) {
                $pi --;
                $pj --;
            } else if ($p == - 1) {
                $pi --;
            } else if ($p == 1) {
                $pj --;
            } else {
                debbuging('error');
            }
            $limit --;
        }

        krsort( $pairs );
        $prevpair = new stdClass();
        $prevpair->i = 0;
        $prevpair->j = 0;
        foreach ($pairs as $pair) {
            if ($pair->i == $prevpair->i + 1 && $pair->j == $prevpair->j + 1) { // Regular advance.
                $l1 = $lines1 [$pair->i - 1];
                $l2 = $lines2 [$pair->j - 1];
                if ($l1 == $l2) { // Equals.
                    $ret [] = self::newlineinfo( '=', $pair->i, $pair->j );
                } else if ( self::similine($l1, $l2, '/\s/')) {
                    $ret [] = self::newlineinfo( '1', $pair->i, $pair->j );
                } else if ( self::similine($l1, $l2, '/(\s|[0-9]|[a-z])/i')) {
                    $ret [] = self::newlineinfo( '2', $pair->i, $pair->j );
                } else {
                    $ret [] = self::newlineinfo( '#', $pair->i, $pair->j );
                }
            } else if ($pair->i == $prevpair->i + 1) { // Removed next line.
                $ret [] = self::newlineinfo( '<', $pair->i, false );
            } else if ($pair->j == $prevpair->j + 1) { // Added one line.
                $ret [] = self::newlineinfo( '>', false, $pair->j );
            } else {
                debugging( "Internal error " . s( $pair ) . " " . s( $prevpair) );
            }
            $prevpair = $pair;
        }
        return $ret;
    }
    static public function show($filename1, $data1, $htmlheader1, $filename2, $data2, $htmlheader2) {
        // Get file lines.
        $nl = vpl_detect_newline( $data1 );
        $lines1 = explode( $nl, $data1 );
        $nl = vpl_detect_newline( $data2 );
        $lines2 = explode( $nl, $data2 );
        // Get dif as an array of info.
        $diff = self::calculatediff( $lines1, $lines2 );
        if ($diff === false) {
            return;
        }
        $separator = array (
                '<' => ' <<< ',
                '>' => ' >>> ',
                '=' => ' === ',
                '1' => ' ==# ',
                '2' => ' =## ',
                '#' => ' ### '
        );
        $emptyline = "\n";
        $data1 = '';
        $data2 = '';
        $datal1 = '';
        $datal2 = '';
        $diffl = '';
        $lines1 [- 1] = '';
        $lines2 [- 1] = '';
        foreach ($diff as $line) {
            $diffl .= $separator [$line->type] . "\n";
            if ($line->ln1) {
                $datal1 .= sprintf("%4d\n", $line->ln1);
                $data1 .= $lines1 [$line->ln1 - 1] . "\n";
            } else {
                if ( $data1 == '' ) {
                    $data1 .= $emptyline;
                    $datal1 .= $emptyline;
                }
                $data1 .= $emptyline;
                $datal1 .= $emptyline;
            }
            if ($line->ln2) {
                $datal2 .= sprintf("%4d\n", $line->ln2);
                $data2 .= $lines2 [$line->ln2 - 1] . "\n";
            } else {
                if ( $data2 == '' ) {
                    $data2 .= $emptyline;
                    $datal2 .= $emptyline;
                }
                $data2 .= $emptyline;
                $datal2 .= $emptyline;
            }
        }
        echo '<div style="width: 100%;min-width: 950px; overflow: auto">';
        // Header.
        echo '<div style="float:left; width: 445px">';
        echo $htmlheader1;
        echo '</div>';
        echo '<div style="float:left; width: 445px">';
        echo $htmlheader2;
        echo '</div>';
        echo '<div style="clear:both;"></div>';
        // Files.
        $pre = '<pre clas="vpl_g">';
        echo '<div style="float:left; text-align: right; width: 3em">';
        $shower = vpl_sh_factory::get_sh( 'a.txt' );
        $shower->print_file( 'a.txt', $datal1, false, count($diff) + 1, false );
        echo '</div>';
        echo '<div style="float:left; width: 390px; overflow:auto">';
        $shower = vpl_sh_factory::get_sh( $filename1 );
        $shower->print_file( $filename1, $data1, false, count($diff) + 1, false );
        echo '</div>';
        echo '<div style="float:left; width: 3em"">';
        $shower = vpl_sh_factory::get_sh( 'b.txt' );
        $shower->print_file( 'b.txt', $diffl, false, count($diff) + 1, false );
        echo '</div>';
        echo '<div style="float:left; text-align: right; width: 3em"">';
        $shower = vpl_sh_factory::get_sh( 'b.txt' );
        $shower->print_file( 'b.txt', $datal2, false, count($diff) + 1, false );
        echo '</div>';
        echo '<div style="float:left; width: 390px; overflow:auto">';
        $shower = vpl_sh_factory::get_sh( $filename2 );
        $shower->print_file( $filename2, $data2, false, count($diff) + 1, false );
        echo '</div>';
        echo '</div>';
        echo '<div style="clear:both;"></div>';
        vpl_sh_factory::syntaxhighlight();
    }
    static public function vpl_get_similfile($f, &$htmlheader, &$filename, &$data) {
        global $DB;
        $htmlheader = '';
        $filename = '';
        $data = '';
        $type = required_param( 'type' . $f, PARAM_INT );
        if ($type == 1) {
            $subid = required_param( 'subid' . $f, PARAM_INT );
            $filename = required_param( 'filename' . $f, PARAM_TEXT );
            $subinstance = $DB->get_record( 'vpl_submissions', array (
                    'id' => $subid
            ) );
            if ($subinstance !== false) {
                $vpl = new mod_vpl( false, $subinstance->vpl );
                $vpl->require_capability( VPL_SIMILARITY_CAPABILITY );
                $submission = new mod_vpl_submission( $vpl, $subinstance );
                $user = $DB->get_record( 'user', array (
                        'id' => $subinstance->userid
                ) );
                if ($user) {
                    $link = vpl_mod_href( '/forms/submissionview.php', 'id', $vpl->get_course_module()->id
                                          , 'userid', $subinstance->userid );
                    $htmlheader .= '<a href="' . $link . '">';
                }
                $htmlheader .= s( $filename ) . ' ';
                if ($user) {
                    $htmlheader .= '</a>';
                    $htmlheader .= $vpl->user_fullname_picture( $user );
                }
                $fg = $submission->get_submitted_fgm();
                $data = $fg->getFileData( $filename );
                \mod_vpl\event\vpl_diff_viewed::log( $submission );
            }
        } else if ($type == 3) {
            global $CFG;
            $data = '';
            $vplid = required_param( 'vplid' . $f, PARAM_INT );
            $vpl = new mod_vpl( false, $vplid );
            $vpl->require_capability( VPL_SIMILARITY_CAPABILITY );
            $zipname = required_param( 'zipfile' . $f, PARAM_RAW );
            $filename = required_param( 'filename' . $f, PARAM_RAW );
            $htmlheader .= $filename . ' ' . optional_param( 'username' . $f, '', PARAM_TEXT );
            $ext = strtoupper( pathinfo( $zipname, PATHINFO_EXTENSION ) );
            if ($ext != 'ZIP') {
                print_error( 'nozipfile' );
            }
            $zip = new ZipArchive();
            $zipfilename = vpl_similarity_preprocess::get_zip_filepath( $vplid, $zipname );
            if ($zip->open( $zipfilename ) === true) {
                $data = $zip->getFromName( $filename );
                $zip->close();
            }
        } else {
            print_error( 'type error' );
        }
    }
}
