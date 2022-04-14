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
require_once(dirname(__FILE__).'/workinggraph.php');
require_once(dirname(__FILE__).'/submissionsgraph.php');

global $CFG, $USER, $OUTPUT;

require_login();

function vpl_actions_menu($id, $userid, $subid) {
    $menu = new action_menu();
    $linkparms = array('id' => $id, 'userid' => $userid, 'submissionid' => $subid, 'privatecopy' => 1);
    $link = new moodle_url('/mod/vpl/forms/submissionview.php', $linkparms);
    $stri18n = get_string('submissionview', 'mod_vpl');
    $action = new action_menu_link_secondary($link, new pix_icon('submissionview', '', 'mod_vpl'),  $stri18n);
    $menu->add($action);
    $stri18n = get_string('copy', 'mod_vpl');
    $link = new moodle_url('/mod/vpl/forms/edit.php', $linkparms);
    $action = new action_menu_link_secondary($link, new pix_icon('copy', '', 'mod_vpl'),  $stri18n);
    $menu->add($action);
    return $menu;
}


$id = required_param( 'id', PARAM_INT );
$userid = optional_param( 'userid', null, PARAM_INT );
$detailed = abs( optional_param( 'detailed', 0, PARAM_INT ) ) % 2;
$vpl = new mod_vpl( $id );
if ( $userid == null ) {
    if ( $vpl->has_capability( VPL_GRADE_CAPABILITY ) ) { // TODO add VPL course setting check.
        $userid = $USER->id;
    } else {
        vpl_notice( get_string( 'notavailable' ), 'error');
        die;
    }
}
// TODO add VPL course setting check and user information available.
$vpl->prepare_page( 'views/previoussubmissionslist.php', array (
        'id' => $id,
        'userid' => $userid
) );

$vpl->require_capability( VPL_GRADE_CAPABILITY );
\mod_vpl\event\submission_previous_upload_viewed::log( array (
        'objectid' => $vpl->get_instance()->id,
        'context' => context_module::instance( $id ),
        'relateduserid' => $userid
) );
$strdatesubmitted = get_string( 'datesubmitted', VPL );
$strdescription = get_string( 'description', VPL );
$straction = get_string( 'action' );
if ($detailed) {
    require_once(dirname(__FILE__).'/../views/sh_factory.class.php');
    vpl_sh_factory::include_js();
}

$vpl->print_header( get_string( 'previoussubmissionslist', VPL ) );
$vpl->print_view_tabs( basename( __FILE__ ) );

$table = new html_table();
$table->head = array (
        '#',
        $strdatesubmitted,
        $strdescription,
        $straction
);
$table->align = array (
        'right',
        'left',
        'right',
        'left'
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
    $submissions[] = $sub;
    $actions = vpl_actions_menu($id, $userid, $submission->id);
    $table->data[] = array (
            $nsub --,
            $date,
            s( $sub->get_detail() ),
            $OUTPUT->render($actions)
    );
}

echo '<div class="clearer"> </div>';
vpl_submissions_graph($vpl, $userid);
vpl_user_working_periods_graph($vpl, $userid);

echo html_writer::table( $table );
echo '<div style="text-align:center">';
$urlbase = $CFG->wwwroot . '/mod/vpl/views/previoussubmissionslist.php?id=' . $id . '&userid=' . $userid . '&detailed=';
$urls = array (
        $urlbase . '0',
        $urlbase . '1'
);
echo $OUTPUT->url_select( array (
        $urls[0] => get_string( 'detailedless' ),
        $urls[1] => get_string( 'detailedmore' )
), $urls[$detailed] );
echo '</div>';
if ($detailed) {
    $nsub = count( $submissionslist );
    foreach ($submissions as $sub) {
        echo '<hr><h2><a name="f' . $nsub . '"># ' . $nsub . '</a></h2>';

        $nsub --;
        $sub->print_submission();
    }
}
$vpl->print_footer();
if ($detailed) {
    vpl_sh_factory::syntaxhighlight();
}
