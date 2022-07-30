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
 * Provides support for restore VPL antivities in the moodle2 backup format
 *
 * @package mod_vpl
 * @copyright 2016 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined ( 'MOODLE_INTERNAL' ) || die ();

require_once(dirname(__FILE__).'/../../locallib.php');

/**
 * Provides support for restore VPL antivities in the moodle2 backup format
 *
 * @copyright 2016 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
class restore_vpl_activity_structure_step extends restore_activity_structure_step {

    /**
     * @var array of names of VPL basedon activities indexed by id of linked activities
     */
    protected $basedonnames = array();

    /**
     * @param Object $data VPL DB instance
     * @return int|boolean id of basedon activity or false
     */
    public function get_baseon_by_name($data) {
        global $DB;
        if ( isset($this->basedonnames[$data->id]) ) {
            $basedonname = $this->basedonnames[$data->id];
            $basedon = $DB->get_record( 'vpl', array ( 'course' => $data->course, 'name' => $basedonname ));
            if ( $basedon != false ) {
                return $basedon->id;
            }
        }
        return false;
    }

    /**
     * {@inheritDoc}
     * @see restore_structure_step::define_structure()
     */
    protected function define_structure() {
        $paths = array ();
        $userinfo = $this->get_setting_value ( 'userinfo' );

        $paths[] = new restore_path_element ( 'vpl', '/activity/vpl' );
        $paths[] = new restore_path_element ( 'required_file', '/activity/vpl/required_files/required_file' );
        $paths[] = new restore_path_element ( 'execution_file', '/activity/vpl/execution_files/execution_file' );
        $paths[] = new restore_path_element ( 'variation', '/activity/vpl/variations/variation' );
        $paths[] = new restore_path_element ( 'override', '/activity/vpl/overrides/override' );
        if ($userinfo) {
            $paths[] = new restore_path_element ( 'assigned_variation', '/activity/vpl/assigned_variations/assigned_variation' );
            $paths[] = new restore_path_element ( 'assigned_override', '/activity/vpl/assigned_overrides/assigned_override' );
            $paths[] = new restore_path_element ( 'submission', '/activity/vpl/submissions/submission' );
            $paths[] = new restore_path_element (
                    'submission_file',
                    '/activity/vpl/submissions/submission/submission_files/submission_file'
            );
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure ( $paths );
    }

    /**
     * Preprocess and restore a VPL instance
     * @param array $data
     */
    protected function process_vpl($data) {
        global $DB;
        $data = ( object ) $data;
        $data->course = $this->get_courseid ();
        $data->startdate = $this->apply_date_offset ( $data->startdate );
        $data->duedate = $this->apply_date_offset ( $data->duedate );
        if ($data->grade < 0) {
            $data->grade = - ($this->get_mappingid ( 'scale', - ($data->grade) ));
        }
        // Insert the choice record.
        $newitemid = $DB->insert_record ( 'vpl', $data );
        // Immediately after inserting "activity" record, call this.
        if (isset($data->basedon) && $data->basedon > 0 && isset($data->basedonname)) {
            $this->basedonnames[$newitemid] = $data->basedonname;
        }
        $this->apply_activity_instance ( $newitemid );
    }

    /**
     * Restore a file
     * @param array $data of file [name, enconding, content]
     * @param string $path path to directory where to save file
     */
    private function process_groupfile($data, $path) {
        $data = ( object ) $data;
        $filename = $data->name;
        if ( isset($data->encoding) && ($data->encoding == 1) ) {
            $content = base64_decode($data->content);
            if ( substr($filename, -4) === '.b64' ) { // For backware compatibility.
                $filename = substr($filename, 0, strlen($filename) - 4);
            }
        } else {
            $content = $data->content;
        }
        vpl_fwrite( $path . $filename, $content );
    }

    /**
     * Restore a required file
     * @param array $data of file [name, enconding, content]
     */
    protected function process_required_file($data) {
        global $CFG;
        $vplid = $this->get_new_parentid ( 'vpl' );
        $path = $CFG->dataroot . '/vpl_data/' . $vplid . '/';
        $this->process_groupfile ( $data, $path );
    }

    /**
     * Restore un execution file
     * @param array $data of file [name, encondinf, content]
     */
    protected function process_execution_file($data) {
        global $CFG;
        $vplid = $this->get_new_parentid ( 'vpl' );
        $path = $CFG->dataroot . '/vpl_data/' . $vplid . '/';
        $this->process_groupfile ( $data, $path );
    }

    /**
     * Restore a variation
     * @param array $data variation instance
     */
    protected function process_variation($data) {
        global $DB;

        $data = ( object ) $data;
        $data->vpl = $this->get_new_parentid ( 'vpl' );
        $DB->insert_record ( 'vpl_variations', $data );
    }

    /**
     * Restore a variation asigned
     * @param array $data variation asigned instance
     */
    protected function process_assigned_variation($data) {
        global $DB;
        $data = ( object ) $data;
        $data->vpl = $this->get_new_parentid ( 'vpl' );
        $data->variation = $this->get_new_parentid ( 'vpl_variation' );
        $data->userid = $this->get_mappingid ( 'user', $data->userid );
        $DB->insert_record ( 'vpl_assigned_variations', $data );
    }

    /**
     * Restore an override
     * @param array $data override instance
     */
    protected function process_override($data) {
        global $DB;
        $data = ( object ) $data;
        $data->vpl = $this->get_new_parentid ( 'vpl' );
        $data->startdate = $this->apply_date_offset ( $data->startdate );
        $data->duedate = $this->apply_date_offset ( $data->duedate );
        $newid = $DB->insert_record ( 'vpl_overrides', $data );
        $this->set_mapping('override', $data->id, $newid); // Map new id to be used by process_assigned_override().
    }

    /**
     * Restore an override assignation
     * @param array $data assigned override instance
     */
    protected function process_assigned_override($data) {
        global $DB;
        $data = ( object ) $data;
        $newid = $this->get_mappingid('override', $data->override, null); // Fetch new override id.
        if ($newid !== null) {
            $data->vpl = $this->get_new_parentid ( 'vpl' );
            $data->override = $newid;
            $data->userid = $this->get_mappingid ( 'user', $data->userid, null );
            $data->groupid = $this->get_mappingid ( 'group', $data->groupid, null );
            $DB->insert_record ( 'vpl_assigned_overrides', $data );
        }
    }

    /**
     * Restore a submission
     * @param array $data submission instance
     */
    protected function process_submission($data) {
        global $DB;
        $data = ( object ) $data;
        $oldid = $data->id;
        $data->vpl = $this->get_new_parentid ( 'vpl' );
        $data->userid = $this->get_mappingid ( 'user', $data->userid );
        $data->grader = $this->get_mappingid ( 'user', $data->grader );
        $data->groupid = $this->get_mappingid ( 'group', $data->groupid );
        $newitemid = $DB->insert_record ( 'vpl_submissions', $data );
        $this->set_mapping ( 'submission', $oldid, $newitemid );
    }

    /**
     * Restore a submission details
     * @param array $data submissions files
     * @throws Exception
     */
    protected function process_submission_file($data) {
        global $DB;
        global $CFG;
        static $sub = false;
        $vplid = $this->get_new_parentid ( 'vpl' );
        $subid = $this->get_new_parentid ( 'submission' );
        if ($sub === false || $sub->id != $subid) {
            $sub = $DB->get_record ( 'vpl_submissions', array (
                    'id' => $subid
            ), 'id,userid,vpl' );
        }
        if ($sub === false) {
            throw new Exception ( 'Submission record not found ' . $subid );
        }
        if ($vplid != $sub->vpl) {
            throw new Exception ( 'Submission record vplid inconsistence' );
        }
        $path = $CFG->dataroot . '/vpl_data/' . $vplid . '/usersdata/' . $sub->userid . '/' . $subid . '/';
        $this->process_groupfile ( $data, $path );
    }

    /**
     * {@inheritDoc}
     * @see restore_structure_step::after_execute()
     */
    protected function after_execute() {
        $this->add_related_files('mod_vpl', 'intro', null);
    }
}
