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

/**
 * Povide backup of group of files
 *
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 * @see backup_nested_element
 */
class backup_nested_filegroup extends backup_nested_element {
    /**
     * Read a file a retun as Object encoded if needed
     * @param string $base directory
     * @param string $filename name of file
     * @return Object
     * @throws Exception
     */
    private function load_file($base, $filename) {
        $data = file_get_contents( $base . $filename );
        $info = new stdClass();
        if ( vpl_is_binary($filename, $data) ) {
            $info->name = $filename . '.b64'; // For backward compatibility.
            $info->content = base64_encode( $data );
            $info->encoding = 1;
        } else {
            $info->name = $filename;
            $info->content = $data;
            $info->encoding = 0;
        }
        return $info;
    }

    /**
     * Read files in a directory
     * @param string $base directory
     * @param string $dirname containing files to backup
     * @return backup_array_iterator
     */
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
                $files[] = $this->load_file($base, $file);
            }
        }
        $dirpath = $base . $dirname;
        if (file_exists( $dirpath )) {
            $dirlst = opendir( $dirpath );
            while ( false !== ($filename = readdir( $dirlst )) ) {
                if ($filename == "." || $filename == "..") {
                    continue;
                }
                $files[] = $this->load_file($base, $dirname . '/' . $filename);
            }
            closedir( $dirlst );
        }
        return new backup_array_iterator( $files );
    }

    /**
     * {@inheritDoc}
     * @see backup_nested_element::get_iterator()
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function get_iterator($processor) {
        global $CFG;

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

/**
 * Povide structure of VPL data
 *
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 * @see backup_activity_structure_step
 */
class backup_vpl_activity_structure_step extends backup_activity_structure_step {

    /**
     * @var array VPL table fields list
     */
    protected $vplfields = array (
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
        'basedonname',
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
        'worktype',
        'timemodified',
        'freeevaluations',
        'reductionbyevaluation',
        'sebrequired',
        'sebkeys',
        'runscript',
        'debugscript'
    );

    /**
     * @var array Submission table fields list
     */
    protected $submissionfields = array (
        'vpl',
        'userid',
        'datesubmitted',
        'comments',
        'grader',
        'dategraded',
        'grade',
        'mailed',
        'highlight',
        'nevaluations',
        'groupid'
    );

    /**
     * @var array Variation table fields list
     */
    protected $variationfields = array (
        'vpl',
        'identification',
        'description'
    );

    /**
     * @var array Asigned Variation table fields list
     */
    protected $asivariationfields = array (
        'userid',
        'vpl',
        'variation'
    );

    /**
     * @var array Overrides table fields list
     */
    protected $overridefields = array (
            'vpl',
            'startdate',
            'duedate',
            'freeevaluations',
            'reductionbyevaluation'
    );

    /**
     * @var array Assigned Overrides table fields list
     */
    protected $assioverridefields = array (
            'vpl',
            'userid',
            'groupid',
            'override'
    );

    /**
     * Define the full structure of a VPL instance with user data
     * {@inheritDoc}
     * @see backup_structure_step::define_structure()
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value( 'userinfo' );

        // Define each element separated.

        $idfield = array ('id');
        $filefields = array ('name', 'content', 'encoding');
        $vpl = new backup_nested_element( 'vpl', $idfield, $this->vplfields);
        $requiredfiles = new backup_nested_element( 'required_files' );
        $requiredfile = new backup_nested_filegroup( 'required_file', $idfield,  $filefields);
        $executionfiles = new backup_nested_element( 'execution_files' );
        $executionfile = new backup_nested_filegroup( 'execution_file', $idfield,  $filefields);
        $variations = new backup_nested_element( 'variations' );
        $variation = new backup_nested_element( 'variation', $idfield, $this->variationfields );
        $asignedvariations = new backup_nested_element( 'asigned_variations' );
        $asignedvariation = new backup_nested_element( 'asigned_variation',
                $idfield,
                $this->asivariationfields );
        $overrides = new backup_nested_element( 'overrides' );
        $override = new backup_nested_element( 'override', $idfield, $this->overridefields );
        $assignedoverrides = new backup_nested_element( 'assigned_overrides' );
        $assignedoverride = new backup_nested_element( 'assigned_override',
                $idfield,
                $this->assioverridefields );
        $submissions = new backup_nested_element( 'submissions' );
        $submission = new backup_nested_element( 'submission', $idfield, $this->submissionfields );
        $submissionfiles = new backup_nested_element( 'submission_files' );
        $submissionfile = new backup_nested_filegroup( 'submission_file', $idfield,  $filefields);
        // Build the tree.
        $vpl->add_child( $requiredfiles );
        $vpl->add_child( $executionfiles );
        $vpl->add_child( $variations );
        $vpl->add_child( $overrides );
        $vpl->add_child( $assignedoverrides );
        $vpl->add_child( $submissions );
        $requiredfiles->add_child( $requiredfile );
        $executionfiles->add_child( $executionfile );
        $variations->add_child( $variation );
        $variation->add_child( $asignedvariations );
        $asignedvariations->add_child( $asignedvariation );
        $overrides->add_child( $override );
        $assignedoverrides->add_child( $assignedoverride );
        $submissions->add_child( $submission );
        $submission->add_child( $submissionfiles );
        $submissionfiles->add_child( $submissionfile );
        // Define sources.
        // VPL record with basedonname field.
        $parmvplid = array ( 'vpl' => backup::VAR_ACTIVITYID );
        $query = 'SELECT s.*, o.name basedonname FROM {vpl} s';
        $query .= ' LEFT join {vpl} o';
        $query .= '   ON s.basedon = o.id';
        $query .= '   WHERE s.id = ?';
        $vpl->set_source_sql( $query, array ( backup::VAR_ACTIVITYID ) );
        $variation->set_source_table( 'vpl_variations', $parmvplid );
        $override->set_source_table( 'vpl_overrides', $parmvplid );
        if ($userinfo) {
            $asignedvariation->set_source_table( 'vpl_assigned_variations', $parmvplid );
            $assignedoverride->set_source_table( 'vpl_assigned_overrides', $parmvplid );
            /*
             * Uncomment next line and comment nexts to backup all student's submissions, not only last one.
             * $submission->set_source_table('vpl_submissions', $parmvplid);
             */
            $query = 'SELECT s.* FROM {vpl_submissions} s';
            $query .= ' inner join';
            $query .= ' (SELECT max(id) maxid FROM {vpl_submissions}';
            $query .= '   WHERE {vpl_submissions}.vpl = ? GROUP BY {vpl_submissions}.userid) ls';
            $query .= ' on ls.maxid = s.id ORDER BY s.id';
            $submission->set_source_sql( $query, array ( backup::VAR_ACTIVITYID ) );
        }

        // Define id annotations.
        $vpl->annotate_ids( 'scale', 'grade' );
        $vpl->annotate_ids( 'vpl', 'basedon' );
        $asignedvariation->annotate_ids( 'user', 'userid' );
        $assignedoverride->annotate_ids( 'user', 'userid' );
        $assignedoverride->annotate_ids( 'group', 'groupid' );
        $submission->annotate_ids( 'user', 'userid' );
        $submission->annotate_ids( 'user', 'grader' );
        $submission->annotate_ids( 'group', 'groupid' );
        // Define file annotations.
        $vpl->annotate_files( 'mod_vpl', 'intro', null );
        return $this->prepare_activity_structure( $vpl );
    }
}
