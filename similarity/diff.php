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
 * Show two files diff
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once(dirname(__FILE__).'/diff.class.php');

global $COURSE, $PAGE, $OUTPUT;

require_course_login( $COURSE );
$strdiff = get_string( 'diff', VPL );
$PAGE->set_url( '/mod/vpl/similarity/diff.php' );
$PAGE->requires->css( new moodle_url( '/mod/vpl/css/sh.css' ) );
$PAGE->set_title( $strdiff );
$PAGE->set_pagelayout( 'popup' );
vpl_sh_factory::include_js();
echo $OUTPUT->header();

$htmlheader1 = '';
$filename1 = '';
$data1 = '';
// Get left file. Control access done inside.
vpl_diff::vpl_get_similfile( '1', $htmlheader1, $filename1, $data1 );

$htmlheader2 = '';
$filename2 = '';
$data2 = '';
// Get right file.Control access done inside.
vpl_diff::vpl_get_similfile( '2', $htmlheader2, $filename2, $data2 );

// Show files.
vpl_diff::show( $filename1, $data1, $htmlheader1, $filename2, $data2, $htmlheader2 );
// Show tokens if requested.
if (optional_param('debug', '', PARAM_RAW) == 'yes') {
    $ext = pathinfo($filename1, PATHINFO_EXTENSION);
    $type = mod_vpl\similarity\similarity_factory::ext2type($ext);
    $tokenizer1 = mod_vpl\tokenizer\tokenizer_factory::get($type);
    $tokens1 = $tokenizer1->parse($data1, false);
    $tokensstr1 = '';
    foreach ($tokens1 as $token) {
        $tokensstr1 .= $token . "\n";
    }
    $ext = pathinfo($filename2, PATHINFO_EXTENSION);
    $type = mod_vpl\similarity\similarity_factory::ext2type($ext);
    $tokenizer1 = mod_vpl\tokenizer\tokenizer_factory::get($type);
    $tokens2 = $tokenizer1->parse($data2, false);
    $tokensstr2 = '';
    foreach ($tokens2 as $token) {
        $tokensstr2 .= $token . "\n";
    }
    vpl_diff::show( $filename1, $tokensstr1, $htmlheader1, $filename2, $tokensstr2, $htmlheader2 );
}
echo $OUTPUT->footer();
