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

global $CFG;
require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once(dirname(__FILE__).'/../vpl_submission.class.php');
require_once(dirname(__FILE__).'/../editor/editor_utility.php');
header("Pragma: no-cache"); // Browser must reload page.
vpl_editor_util::generate_requires();
require_login();
$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', false, PARAM_INT);
$copy = optional_param('privatecopy', false, PARAM_INT);
$subid = optional_param( 'submissionid', false, PARAM_INT );
$vpl = new mod_vpl($id);
$pageparms = array('id' => $id);
if ($userid && ! $copy) {
    $pageparms ['userid'] = $userid;
}
if ($copy) {
    $pageparms ['privatecopy'] = 1;
}
$vpl->prepare_page( 'forms/edit.php', $pageparms );
if (! $vpl->is_visible()) {
    notice( get_string( 'notavailable' ) );
}
if (! $vpl->is_submit_able()) {
    print_error( 'notavailable' );
}
if (! $userid || $userid == $USER->id) { // Edit own submission.
    $userid = $USER->id;
    $vpl->require_capability( VPL_SUBMIT_CAPABILITY );
} else { // Edit other user submission.
    $vpl->require_capability( VPL_MANAGE_CAPABILITY );
}
$vpl->network_check();
$vpl->password_check();

$instance = $vpl->get_instance();
$manager = $vpl->has_capability(VPL_MANAGE_CAPABILITY);
$grader = $vpl->has_capability(VPL_GRADE_CAPABILITY);

// This code allow to edit previous versions (only managers).
if ($subid && $vpl->has_capability( VPL_MANAGE_CAPABILITY )) {
    $parms = array (
            'id' => $subid,
            'vpl' => $instance->id,
            'userid' => $userid
    );
    $res = $DB->get_records( 'vpl_submissions', $parms );
    if (count( $res ) == 1) {
        $lastsub = $res [$subid];
    } else {
        $lastsub = false;
    }
} else {
    $lastsub = $vpl->last_user_submission( $userid );
}
$options = Array();
$options ['id'] = $id;
$options ['restrictededitor'] = $instance->restrictededitor && ! $grader;
$options ['save'] = ! $instance->example;
$options ['run'] = ($instance->run || $manager);
$options ['debug'] = ($instance->debug || $manager);
$options ['evaluate'] = ($instance->evaluate || $manager);
$options ['example'] = true && $instance->example;
$linkuserid = $copy ? $USER->id : $userid;
$options ['ajaxurl'] = "edit.json.php?id={$id}&userid={$linkuserid}&action=";
$options ['download'] = "../views/downloadsubmission.php?id={$id}&userid={$linkuserid}";
// Get files.
$files = Array ();
$reqfgm = $vpl->get_required_fgm();
$options ['resetfiles'] = ($reqfgm->is_populated() && ! $instance->example);
$options ['maxfiles'] = intval($instance->maxfiles);
$reqfilelist = $reqfgm->getFileList();
$min = count( $reqfilelist );
$options ['minfiles'] = $min;
$nf = count( $reqfilelist );
for ($i = 0; $i < $nf; $i ++) {
    $filename = $reqfilelist [$i];
    $filedata = $reqfgm->getFileData( $reqfilelist [$i] );
    $files [$filename] = $filedata;
}
if ($lastsub) {
    $submission = new mod_vpl_submission( $vpl, $lastsub );
    $fgp = $submission->get_submitted_fgm();
    $filelist = $fgp->getFileList();
    $nf = count( $filelist );
    for ($i = 0; $i < $nf; $i ++) {
        $filename = $filelist [$i];
        $filedata = $fgp->getFileData( $filelist [$i] );
        $files [$filename] = $filedata;
    }
    $compilationexecution = $submission->get_CE_for_editor();
    \mod_vpl\event\submission_edited::log( $submission );
}
session_write_close();
if ($copy && $grader) {
    $userid = $USER->id;
}
$vpl->print_header( get_string( 'edit', VPL ) );
$vpl->print_view_tabs( basename( __FILE__ ) );
echo $OUTPUT->box_start();
vpl_editor_util::print_tag( $options, $files, ($lastsub && ! $copy) );
echo $OUTPUT->box_end();
if ($lastsub) {
    echo vpl_editor_util::send_ce( $compilationexecution );
}
$vpl->print_footer();
