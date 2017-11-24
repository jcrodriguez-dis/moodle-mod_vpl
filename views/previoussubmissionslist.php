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
 * List previous submissions for a vpl and user
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once(dirname(__FILE__).'/../vpl_submission.class.php');

require_login();

$id = required_param( 'id', PARAM_INT );
$userid = required_param( 'userid', PARAM_INT );
$detailed = abs( optional_param( 'detailed', 0, PARAM_INT ) ) % 2;
$vpl = new mod_vpl( $id );
$vpl->prepare_page( 'views/previoussubmissionslist.php', array (
        'id' => $id,
        'userid' => $userid
) );

$course = $vpl->get_course();
$vpl->require_capability( VPL_GRADE_CAPABILITY );
\mod_vpl\event\submission_previous_upload_viewed::log( array (
        'objectid' => $vpl->get_instance()->id,
        'context' => context_module::instance( $id ),
        'relateduserid' => $userid
) );
$strdatesubmitted = get_string( 'datesubmitted', VPL );
$strdescription = get_string( 'description', VPL );
if ($detailed) {
    require_once(dirname(__FILE__).'/../views/sh_factory.class.php');
    vpl_sh_factory::include_js();
}
$PAGE->requires->css( new moodle_url( '/mod/vpl/css/sh.css' ) );

$vpl->print_header( get_string( 'previoussubmissionslist', VPL ) );
$vpl->print_view_tabs( basename( __FILE__ ) );

$table = new html_table();
$table->head = array (
        '#',
        $strdatesubmitted,
        $strdescription
);
$table->align = array (
        'right',
        'left',
        'right'
);
$table->nowrap = array (
        true,
        true,
        true
);
$submissionslist = $vpl->user_submissions( $userid );
$submissions = array ();
$nsub = count( $submissionslist );
foreach ($submissionslist as $submission) {
    if ($detailed) {
        $link = '#f' . $nsub;
    } else {
        $link = vpl_mod_href( 'forms/submissionview.php', 'id', $id, 'userid', $userid, 'submissionid', $submission->id );
    }
    $date = '<a href="' . $link . '">' . userdate( $submission->datesubmitted ) . '</a>';
    $sub = new mod_vpl_submission( $vpl, $submission );
    $submissions [] = $sub;
    $table->data [] = array (
            $nsub --,
            $date,
            s( $sub->get_detail() )
    );
}

echo '<div class="clearer"> </div>';
echo '<div style="text-align: center">';
echo '<img src="' . vpl_rel_url( 'submissionsgraph.php', 'id', $id, 'userid', $userid ) . '" alt="files size evolution" />';
echo '</div>';
echo '<div class="clearer"> </div>';
echo '<div class="clearer"> </div>';
echo '<div style="text-align: center">';
echo '<img src="' . vpl_rel_url( 'workinggraph.php', 'id', $id, 'userid', $userid ) . '" alt="workingperiods" />';
echo '</div>';
echo '<div class="clearer"> </div>';
echo html_writer::table( $table );
echo '<div style="text-align:center">';
$urlbase = $CFG->wwwroot . '/mod/vpl/views/previoussubmissionslist.php?id=' . $id . '&userid=' . $userid . '&detailed=';
$urls = array (
        $urlbase . '0',
        $urlbase . '1'
);
echo $OUTPUT->url_select( array (
        $urls [0] => get_string( 'detailedless' ),
        $urls [1] => get_string( 'detailedmore' )
), $urls [$detailed] );
echo '</div>';
if ($detailed) {
    $nsub = count( $submissionslist );
    foreach ($submissions as $index => $sub) {
        echo '<hr><h2><a name="f' . $nsub . '"># ' . $nsub . '</a></h2>';

        $nsub --;
        $sub->print_submission();
    }
}
$vpl->print_footer();
if ($detailed) {
    vpl_sh_factory::syntaxhighlight();
}
