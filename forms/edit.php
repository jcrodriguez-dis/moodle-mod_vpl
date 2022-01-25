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
 * Launches IDE
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once(dirname(__FILE__).'/../vpl_submission.class.php');
require_once(dirname(__FILE__).'/../editor/editor_utility.php');

global $USER, $DB;
require_login();
$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', false, PARAM_INT);
$copy = optional_param('privatecopy', false, PARAM_INT);
$subid = optional_param( 'submissionid', false, PARAM_INT );
$vpl = new mod_vpl($id);
$pageparms = array('id' => $id);
if ($userid && ! $copy) {
    $pageparms['userid'] = $userid;
}
if ($copy) {
    $pageparms['privatecopy'] = 1;
}
if ($subid) {
    $pageparms['submissionid'] = $subid;
}
$vpl->prepare_page( 'forms/edit.php', $pageparms );
if (! $vpl->is_visible()) {
    vpl_redirect('?id=' . $id, get_string( 'notavailable' ), 'error' );
}
if (! $vpl->is_submit_able()) {
    vpl_redirect('?id=' . $id, get_string( 'notavailable' ), 'error' );
}
if (! $userid || $userid == $USER->id) { // Edit own submission.
    $userid = $USER->id;
    $vpl->require_capability( VPL_SUBMIT_CAPABILITY );
} else { // Edit other user submission.
    $vpl->require_capability( VPL_GRADE_CAPABILITY );
}
$vpl->restrictions_check();

$instance = $vpl->get_instance();

$grader = $vpl->has_capability(VPL_GRADE_CAPABILITY);

// This code allow to edit previous versions.
if ($subid && $grader) {
    $parms = array (
            'id' => $subid,
            'vpl' => $instance->id,
            'userid' => $userid
    );
    $res = $DB->get_records( 'vpl_submissions', $parms );
    if (count( $res ) == 1) {
        $lastsub = $res[$subid];
    } else {
        $lastsub = false;
    }
} else {
    $lastsub = $vpl->last_user_submission( $userid );
}
$options = Array();
$options['id'] = $id;
$options['restrictededitor'] = $instance->restrictededitor && ! $grader;
$options['save'] = ! $instance->example;
$options['run'] = ($instance->run || $grader);
$options['debug'] = ($instance->debug || $grader);
$options['evaluate'] = ($instance->evaluate || $grader);
$options['example'] = true && $instance->example;
$options['comments'] = ! $options['example'];
$options['username'] = $vpl->fullname($DB->get_record( 'user', array ( 'id' => $userid ) ), false);
$linkuserid = $copy ? $USER->id : $userid;
$ajaxurl = "edit.json.php?id={$id}&userid={$linkuserid}";
$options['ajaxurl'] = $ajaxurl . '&action=';
if ( $copy ) {
    $loadajaxurl = "edit.json.php?id={$id}&userid={$userid}&privatecopy=1";
    if ( $subid && $lastsub ) {
        $loadajaxurl .= "&subid={$lastsub->id}";
    }
    $options['loadajaxurl'] = $loadajaxurl . '&action=';
}
$options['download'] = "../views/downloadsubmission.php?id={$id}&userid={$linkuserid}";
$duedate = $vpl->get_effective_setting('duedate', $linkuserid);
$timeleft = $duedate - time();
$hour = 60 * 60;
if ( $duedate > 0 && $timeleft > -$hour ) {
    $options['timeLeft'] = $timeleft;
}
if ( $subid ) {
    $options['submissionid'] = $subid;
}

$reqfgm = $vpl->get_required_fgm();
$options['resetfiles'] = ($reqfgm->is_populated() && ! $instance->example);
$options['maxfiles'] = intval($instance->maxfiles);
$reqfilelist = $reqfgm->getFileList();
$options['minfiles'] = count( $reqfilelist );
if ($options['example']) {
    $options['maxfiles'] = $options['minfiles'];
}
$options['saved'] = $lastsub && ! $copy;
if ($lastsub) {
    $submission = new mod_vpl_submission( $vpl, $lastsub );
    \mod_vpl\event\submission_edited::log( $submission );
}

if ($copy && $grader) {
    $userid = $USER->id;
}
vpl_editor_util::generate_requires($vpl, $options);
$vpl->print_header( get_string( 'edit', VPL ) );
$vpl->print_view_tabs( basename( __FILE__ ) );

vpl_editor_util::print_tag();
vpl_editor_util::print_js_i18n();
vpl_editor_util::print_js_description($vpl, $userid);

$vpl->print_footer();
