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
 * Download all submissions of an activity in zip file
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once(dirname(__FILE__).'/../vpl_submission_CE.class.php');

global $CFG, $USER;

function vpl_user_zip_dirname( $name ) {
    // Prepare name.
    $name = trim( $name );
    $name = str_replace( '?', '_', $name );
    $name = str_replace( '.', '_', $name );
    $name = str_replace( ',', '_', $name );
    $name = str_replace( ':', '_', $name );
    $name = str_replace( '*', '_', $name );
    $name = str_replace( '\\', '_', $name );
    $name = str_replace( '<', '_', $name );
    $name = str_replace( '>', '_', $name );
    $name = str_replace( '|', '_', $name );
    return $name;
}

function vpl_add_files_to_zip($zip, $sourcedir, $zipdirname, $fgm, &$ziperrors) {
    foreach ($fgm->getFileList() as $filename) {
        $source = file_group_process::encodeFileName( $filename );
        $filepathorigen = $sourcedir . $source;
        $filepathtarget = $zipdirname . $filename;
        if ( ! $zip->addFile( $filepathorigen, $filepathtarget ) ) {
            if ( ! file_exists($filepathorigen) ) {
                $ziperrors .= 'File "'.$filepathorigen . "\" don't exists\n";
            } else {
                $ziperrors .= 'File "'.$filepathorigen . '" in "' . $filepathtarget . '" ';
                $ziperrors .= 'generate ' . $zip->getStatusString () ."\n";
            }
        }
    }
}

require_login();
$id = required_param( 'id', PARAM_INT );
$group = optional_param( 'group', - 1, PARAM_INT );
$all = optional_param( 'all', 0, PARAM_INT );

$subselection = vpl_get_set_session_var( 'subselection', 'allsubmissions', 'selection' );
$vpl = new mod_vpl( $id );
$cm = $vpl->get_course_module();
$vpl->require_capability( VPL_SIMILARITY_CAPABILITY );
\mod_vpl\event\vpl_all_submissions_downloaded::log( $vpl );
// Get students.
$currentgroup = groups_get_activity_group( $cm );
$extraname = '';
if (! $currentgroup) {
    $currentgroup = '';
} else {
    $extraname = ' ' . groups_get_group_name( $currentgroup );
}
$list = $vpl->get_students( $currentgroup );

if ($all) {
    $asortedsubmissions = $vpl->all_last_user_submission();
} else {
    $asortedsubmissions = $vpl->all_user_submission();
}
// Organize information by user id.
$submissions = array();
foreach ($asortedsubmissions as $instance) {
    if ( ! isset($submissions[$instance->userid]) ) {
        $submissions[$instance->userid] = array();
    }
    $submissions[$instance->userid][] = $instance;
}

// Get all information by user.
$alldata = array ();
foreach ($list as $userinfo) {
    if ($vpl->is_group_activity() && $userinfo->id != $vpl->get_group_leaderid( $userinfo->id )) {
        continue;
    }
    if (! isset( $submissions [$userinfo->id] )) {
        continue;
    }
    $data = new stdClass();
    $data->userinfo = $userinfo;
    // When group activity => change leader object lastname to groupname for order porpouse.
    if ($vpl->is_group_activity()) {
        $data->userinfo->firstname = '';
        $data->userinfo->lastname = $vpl->fullname( $userinfo, false );
    }
    $usersubmissions = array();
    foreach ($submissions [$userinfo->id] as $subinstance) {
        $usersubmissions[] = new mod_vpl_submission_CE( $vpl, $subinstance );
    }
    $data->submissions = $usersubmissions;
    $alldata [] = $data;
}

$zip = new ZipArchive();
$zipfilename = tempnam( $CFG->dataroot . '/temp/', 'vpl_zipdownloadsubmissions' );

if ($zip->open( $zipfilename, ZIPARCHIVE::CREATE )) {
    $ziperrors = '';
    foreach ($alldata as $data) {
        $user = $data->userinfo;
        $zipdirname = vpl_user_zip_dirname( $user->lastname . ' ' . $user->firstname );
        $zipdirname .= ' ' . $user->id;
        // Create directory.
        $zip->addEmptyDir( $zipdirname );
        $zipdirname .= '/';
        foreach ($data->submissions as $submission) {
            $zipsubdirname = $zipdirname;
            if ( $all ) {
                $zipsubdirname .= $submission->get_instance()->id . '/';
            }
            $fgm = $submission->get_submitted_fgm();
            $sourcedir = $submission->get_submission_directory() . '/';
            vpl_add_files_to_zip($zip, $sourcedir, $zipsubdirname, $fgm, $ziperrors);
            // TODO Adds code to save user comments .
            // TODO Adds code to save execution result ( use get_ce() ) .
        }
    }
    if ( $ziperrors > '' ) {
        $zip->addFromString( 'errors.txt', $ziperrors );
    }
    $zip->close();
    vpl_output_zip($zipfilename, $vpl->get_instance()->name . $extraname);
}
