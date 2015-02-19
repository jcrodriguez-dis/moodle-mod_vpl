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
 * Class to centralize edition/execution operations
 *
 * @package mod_vpl
 * @copyright 2014 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
require_once(dirname( __FILE__ ) . '/../locallib.php');
require_once(dirname( __FILE__ ) . '/../vpl.class.php');
require_once(dirname( __FILE__ ) . '/../vpl_submission_CE.class.php');
require_once(dirname( __FILE__ ) . '/../vpl_example_CE.class.php');
class mod_vpl_edit {
    public static function files2object($array_files) {
        $files = array ();
        foreach ($array_files as $name => $data) {
            $file = array (
                    'name' => $name,
                    'data' => $data
            );
            $files [] = $file;
        }
        return $files;
    }
    public static function save($vpl, $userid, $files) {
        global $USER;
        if ($subid = $vpl->add_submission( $userid, $files, '', $error_message )) {
            $id = $vpl->get_course_module()->id;
            \mod_vpl\event\submission_uploaded::log( array (
                    'objectid' => $subid,
                    'context' => $vpl->get_context(),
                    'relateduserid' => ($USER->id != $userid ? $userid : null)
            ) );
        } else {
            throw new Exception( get_string( 'notsaved', VPL ) . ': ' . $error_message );
        }
    }
    public static function get_requested_files($vpl) {
        $req_fgm = $vpl->get_required_fgm();
        $req_filelist = $req_fgm->getFileList();
        $nf = count( $req_filelist );
        $files = Array ();
        for($i = 0; $i < $nf; $i ++) {
            $filename = $req_filelist [$i];
            $filedata = $req_fgm->getFileData( $req_filelist [$i] );
            $files [$filename] = $filedata;
        }
        return $files;
    }
    public static function get_submitted_files($vpl, $userid, & $CE) {
        $CE = false;
        $lastsub = $vpl->last_user_submission( $userid );
        if ($lastsub) {
            $submission = new mod_vpl_submission( $vpl, $lastsub );
            $fgp = $submission->get_submitted_fgm();
            $filelist = $fgp->getFileList();
            $nf = count( $filelist );
            for($i = 0; $i < $nf; $i ++) {
                $filename = $filelist [$i];
                $filedata = $fgp->getFileData( $filelist [$i] );
                $files [$filename] = $filedata;
            }
            $CE = $submission->get_CE_for_editor();
        } else {
            $files = self::get_requested_files( $vpl );
        }
        return $files;
    }
    public static function execute($vpl, $userid, $action) {
        global $USER;
        $example = $vpl->get_instance()->example;
        $lastsub = $vpl->last_user_submission( $userid );
        if (! $lastsub && ! $example) {
            throw new Exception( get_string( 'nosubmission', VPL ) );
        }
        if ($example) {
            $submission = new mod_vpl_example_CE( $vpl );
        } else {
            $submission = new mod_vpl_submission_CE( $vpl, $lastsub );
        }
        $code = array (
                'run' => 0,
                'debug' => 1,
                'evaluate' => 2
        );
        $traslate = array (
                'run' => 'run',
                'debug' => 'debugged',
                'evaluate' => 'evaluated'
        );
        $eventclass = '\mod_vpl\event\submission_' . $traslate [$action];
        $acode = $code [$action];
        $eventclass::log( $submission );
        return $submission->run( $acode );
    }
    public static function retrieve_result($vpl, $userid) {
        $lastsub = $vpl->last_user_submission( $userid );
        if (! $lastsub) {
            throw new Exception( get_string( 'nosubmission', VPL ) );
        }
        $submission = new mod_vpl_submission_CE( $vpl, $lastsub );
        return $submission->retrieveResult();
    }
    public static function cancel($vpl, $userid) {
        $example = $vpl->get_instance()->example;
        $lastsub = $vpl->last_user_submission( $userid );
        if (! $lastsub && ! $example) {
            throw new Exception( get_string( 'nosubmission', VPL ) );
        }
        if ($example) {
            $submission = new mod_vpl_example_CE( $vpl );
        } else {
            $submission = new mod_vpl_submission_CE( $vpl, $lastsub );
        }
        return $submission->cancelProcess();
    }
}