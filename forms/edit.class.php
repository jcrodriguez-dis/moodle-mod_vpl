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

defined( 'MOODLE_INTERNAL' ) || die();
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once(dirname(__FILE__).'/../vpl_submission_CE.class.php');
require_once(dirname(__FILE__).'/../vpl_example_CE.class.php');
class mod_vpl_edit{
    public static function filesfromide(& $postfiles) {
        $files = Array ();
        foreach ($postfiles as $file) {
            if ( $file->encoding == 1 ) {
                $files [$file->name] = base64_decode( $file->contents );
            } else {
                $files [$file->name] = $file->contents;
            }
        }
        return $files;
    }
    public static function filestoide(& $from) {
        $files = Array ();
        foreach ($from as $name => $data) {
            $file = new stdClass();
            $file->name = $name;
            if ( vpl_is_binary($name, $data) ) {
                $file->contents = base64_encode( $data );
                $file->encoding = 1;
            } else {
                $file->contents = $data;
                $file->encoding = 0;
            }
            $files [] = $file;
        }
        return $files;
    }
    public static function files2object(& $arrayfiles) {
        $files = array ();
        foreach ($arrayfiles as $name => $data) {
            $file = array (
                    'name' => $name,
                    'data' => $data
            );
            $files [] = $file;
        }
        return $files;
    }

    public static function save($vpl, $userid, & $files, $comments='') {
        global $USER;
        if ($subid = $vpl->add_submission( $userid, $files, $comments, $errormessage )) {
            $id = $vpl->get_course_module()->id;
            \mod_vpl\event\submission_uploaded::log( array (
                    'objectid' => $subid,
                    'context' => $vpl->get_context(),
                    'relateduserid' => ($USER->id != $userid ? $userid : null)
            ) );
        } else {
            throw new Exception( get_string( 'notsaved', VPL ) . ': ' . $errormessage );
        }
    }

    public static function get_requested_files($vpl) {
        $reqfgm = $vpl->get_required_fgm();
        return $reqfgm->getallfiles();
    }
    public static function get_submitted_files($vpl, $userid, & $compilationexecution) {
        $compilationexecution = false;
        $lastsub = $vpl->last_user_submission( $userid );
        if ($lastsub) {
            $submission = new mod_vpl_submission( $vpl, $lastsub );
            $fgp = $submission->get_submitted_fgm();
            $files = $fgp->getallfiles();
            $compilationexecution = $submission->get_CE_for_editor();
        } else {
            $files = self::get_requested_files( $vpl );
            $compilationexecution = new stdClass();
            $compilationexecution->nevaluations = 0;
            $vplinstance = $vpl->get_instance();
            $compilationexecution->freeevaluations = $vplinstance->freeevaluations;
            $compilationexecution->reductionbyevaluation = $vplinstance->reductionbyevaluation;

        }
        return $files;
    }
    public static function load($vpl, $userid, $submissionid = false) {
        global $DB;
        $response = new stdClass();
        $response->id = 0;
        $response->comments = '';
        $response->compilationexecution = false;
        if ( $submissionid != false ) {
            $parms = array('id' => $submissionid, 'vpl' => $instance->id, 'userid' => $userid);
            $res = $DB->get_records('vpl_submissions', $parms);
            if ( count($res) == 1 ) {
                 $subreg = $res[$subid];
            } else {
                 $subreg = false;
            }
        } else {
            $subreg = $vpl->last_user_submission( $userid );
        }
        $response->files = self::get_requested_files( $vpl );
        if ($subreg) {
            $submission = new mod_vpl_submission( $vpl, $subreg );
            $fgp = $submission->get_submitted_fgm();
            $response->id = $subreg->id;
            $response->comments = $subreg->comments;
            $response->files = array_merge($response->files, $fgp->getallfiles());
            $response->compilationexecution = $submission->get_CE_for_editor();
        } else {
            $compilationexecution = new stdClass();
            $compilationexecution->grade = '';
            $compilationexecution->nevaluations = 0;
            $vplinstance = $vpl->get_instance();
            $compilationexecution->freeevaluations = $vplinstance->freeevaluations;
            $compilationexecution->reductionbyevaluation = $vplinstance->reductionbyevaluation;
            $response->compilationexecution = $compilationexecution;
        }
        return $response;
    }

    public static function execute($vpl, $userid, $action, $options = array()) {
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
        $eventclass::log( $submission );
        return $submission->run( $code [$action], $options );
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
