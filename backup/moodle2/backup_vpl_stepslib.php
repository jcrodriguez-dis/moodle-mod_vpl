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
 * Provides support for backup VPL antivities in the moodle2 backup format
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
defined( 'MOODLE_INTERNAL' ) || die();
require_once(dirname( __FILE__ ) . '/../../vpl.class.php');
class backup_nested_filegroup extends backup_nested_element {
    private function get_files($base, $dirname) {
        $files = array ();
        $filelst = $dirname . '.lst';
        $extrafiles = array (
                $filelst,
                $filelst . '.keep',
                'compilation.txt',
                'execution.txt',
                'grade_comments.txt'
        );
        foreach ($extrafiles as $file) {
            if (file_exists( $base . $file )) {
                $data = new stdClass();
                $data->name = $file;
                $data->content = file_get_contents( $base . $file );
                $files [] = $data;
            }
        }
        $dirpath = $base . $dirname;
        if (file_exists( $dirpath )) {
            $dirlst = opendir( $dirpath );
            while ( false !== ($filename = readdir( $dirlst )) ) {
                if ($filename == "." || $filename == "..") {
                    continue;
                }
                $data = new stdClass();
                $data->name = $dirname . '/' . $filename;
                $data->content = file_get_contents( $dirpath . '/' . $filename );
                $files [] = $data;
            }
            closedir( $dirlst );
        }
        return new backup_array_iterator( $files );
    }
    protected function get_iterator($processor) {
        global $CFG;

        $files = array ();
        switch ($this->get_name()) {
            case 'required_file' :
                $vplid = $this->find_first_parent_by_name( 'id' )->get_value();
                $path = $CFG->dataroot . '/vpl_data/' . $vplid . '/';
                return $this->get_files( $path, 'required_files' );
            case 'execution_file' :
                $vplid = $this->find_first_parent_by_name( 'id' )->get_value();
                $path = $CFG->dataroot . '/vpl_data/' . $vplid . '/';
                return $this->get_files( $path, 'execution_files' );
                break;
            case 'submission_file' :
                $vplid = $this->find_first_parent_by_name( 'vpl' )->get_value();
                $subid = $this->find_first_parent_by_name( 'id' )->get_value();
                $userid = $this->find_first_parent_by_name( 'userid' )->get_value();
                $path = $CFG->dataroot . '/vpl_data/' . $vplid . '/usersdata/' . $userid . '/' . $subid . '/';
                return $this->get_files( $path, 'submittedfiles' );
                break;
            default :
                throw new Exception( 'Type of element error for backup_nested_group' );
        }
    }
}
class backup_vpl_activity_structure_step extends backup_activity_structure_step {
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value( 'userinfo' );

        // Define each element separated.

        $vpl = new backup_nested_element( 'vpl', array (
                'id'
        ), array (
                'name',
                'shortdescription',
                'intro',
                'introformat',
                'startdate',
                'duedate',
                'maxfiles',
                'maxfilesize',
                'requirednet',
                'password',
                'grade',
                'visiblegrade',
                'usevariations',
                'variationtitle',
                'basedon',
                'run',
                'debug',
                'evaluate',
                'evaluateonsubmission',
                'automaticgrading',
                'maxexetime',
                'restrictededitor',
                'example',
                'maxexememory',
                'maxexefilesize',
                'maxexeprocesses',
                'jailservers',
                'emailteachers',
                'worktype'
        ) );
        $requiredfiles = new backup_nested_element( 'required_files' );
        $requiredfile = new backup_nested_filegroup( 'required_file', array (
                'id'
        ), array (
                'name',
                'content'
        ) );
        $executionfiles = new backup_nested_element( 'execution_files' );
        $executionfile = new backup_nested_filegroup( 'execution_file', array (
                'id'
        ), array (
                'name',
                'content'
        ) );
        $variations = new backup_nested_element( 'variations' );
        $variation = new backup_nested_element( 'variation', array (
                'id'
        ), array (
                'vpl',
                'identification',
                'description'
        ) );
        $asignedvariations = new backup_nested_element( 'asigned_variations' );
        $asignedvariation = new backup_nested_element( 'asigned_variation', array (
                'id'
        ), array (
                'userid',
                'vpl',
                'variation'
        ) );
        $submissions = new backup_nested_element( 'submissions' );
        $submission = new backup_nested_element( 'submission', array (
                'id'
        ), array (
                'vpl',
                'userid',
                'datesubmitted',
                'comments',
                'grader',
                'dategraded',
                'grade',
                'mailed',
                'highlight'
        ) );
        $submissionfiles = new backup_nested_element( 'submission_files' );
        $submissionfile = new backup_nested_filegroup( 'submission_file', array (
                'id'
        ), array (
                'name',
                'content'
        ) );
        // Build the tree.
        $vpl->add_child( $requiredfiles );
        $vpl->add_child( $executionfiles );
        $vpl->add_child( $variations );
        $vpl->add_child( $submissions );
        $requiredfiles->add_child( $requiredfile );
        $executionfiles->add_child( $executionfile );
        $variations->add_child( $variation );
        $variation->add_child( $asignedvariations );
        $asignedvariations->add_child( $asignedvariation );
        $submissions->add_child( $submission );
        $submission->add_child( $submissionfiles );
        $submissionfiles->add_child( $submissionfile );
        // Define sources.
        $vpl->set_source_table( 'vpl', array (
                'id' => backup::VAR_ACTIVITYID
        ) );
        $variation->set_source_table( 'vpl_variations', array (
                'vpl' => backup::VAR_ACTIVITYID
        ) );
        if ($userinfo) {
            $asignedvariation->set_source_table( 'vpl_assigned_variations', array (
                    'vpl' => backup::VAR_ACTIVITYID,
                    'variation' => backup::VAR_ACTIVITYID
            ) );
            /*
             * Uncomment next line and comment nexts to backup all student's submissions, not only last one.
             * $submission->set_source_table('vpl_submissions', array('vpl' => backup::VAR_ACTIVITYID));
             */
            $query = 'SELECT s.* FROM {vpl_submissions} AS s';
            $query .= ' inner join';
            $query .= ' (SELECT max(id) as maxid FROM {vpl_submissions}';
            $query .= '   WHERE {vpl_submissions}.vpl = ? GROUP BY {vpl_submissions}.userid) AS ls';
            $query .= ' on ls.maxid = s.id';
            $submission->set_source_sql( $query, array (
                    backup::VAR_ACTIVITYID
            ) );
        }

        // Define id annotations.
        $vpl->annotate_ids( 'scale', 'grade' );
        $vpl->annotate_ids( 'vpl', 'basedon' );
        $asignedvariation->annotate_ids( 'user', 'userid' );
        $submission->annotate_ids( 'user', 'userid' );
        $submission->annotate_ids( 'user', 'grader' );
        // Define file annotations.
        $vpl->annotate_files( 'mod_vpl', 'intro', null );
        return $this->prepare_activity_structure( $vpl );
    }
}