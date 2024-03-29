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
 * Provides support for the conversion of moodle1 backup to
 * the moodle2 backup format
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/**
 * VPL moodle1 backup conversion handler
 *
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
class moodle1_mod_vpl_handler extends moodle1_mod_handler {

    /**
     *
     * @var moodle1_file_manager
     */
    protected $fileman = null;

    /**
     *
     * @var int cmid
     */
    protected $moduleid = null;

    /**
     *
     * @var int module id
     */
    protected $instanceid = null;

    /**
     * Declare the paths in moodle.xml we are able to convert
     *
     * The method returns list of {@ link convert_path} instances.
     * For each path returned, the corresponding conversion method must be
     * defined.
     *
     * Note that the path /MOODLE_BACKUP/COURSE/MODULES/MOD/VPL does not
     * actually exist in the file. The last element with the module name was
     * appended by the moodle1_converter class.
     *
     * @return array of {@ link convert_path} instances
     */
    public function get_paths() {
        return [
                new convert_path ( 'vpl', '/MOODLE_BACKUP/COURSE/MODULES/MOD/VPL', [
                        'newfields' => [
                                'emailteachers' => 0,
                                'worktype' => 0,
                        ],
                        'renamefields' => [
                                'availablefrom' => 'startdate',
                        ],
                        'dropfields' => [
                                'visiblefrom',
                        ],
                ] ),
                new convert_path ( 'vpl_variations', '/MOODLE_BACKUP/COURSE/MODULES/MOD/VPL/VARIATIONS' ),
                new convert_path ( 'vpl_variation', '/MOODLE_BACKUP/COURSE/MODULES/MOD/VPL/VARIATIONS/VARIATION' ),
                new convert_path ( 'vpl_submissions', '/MOODLE_BACKUP/COURSE/MODULES/MOD/VPL/SUBMISSIONS' ),
                new convert_path ( 'vpl_submission', '/MOODLE_BACKUP/COURSE/MODULES/MOD/VPL/SUBMISSIONS/SUBMISSION', [
                        'newfields' => [
                                'mailed' => 0,
                                'highlight' => 0,
                        ],
                ] ),
        ];
    }
    /**
     * Get path to tmp vpl data dir
     * @return string path to tmp vpl data dir
     */
    protected function get_data_path() {
        return $this->converter->get_tempdir_path () . '/moddata/vpl/';
    }
    /**
     * Get path to instance config dir
     * @return string path to instance config dir
     */
    protected function get_config_path() {
        return $this->get_data_path () . $this->instanceid . '/config/';
    }
    /**
     * Get path to vpl submission dir
     * @param int $userid user id
     * @param int $subid submission id
     * @return string path to vpl submission dir
     */
    protected function get_submission_path($userid, $subid) {
        return $this->get_data_path () . $this->instanceid . '/usersdata/' . $userid . '/' . $subid . '/';
    }
    /**
     * Get the vpl description
     * @return string description
     */
    protected function get_fulldescription() {
        $path = $this->get_config_path () . 'fulldescription.html';
        if (file_exists ( $path )) {
            return file_get_contents ( $path );
        }
        return '';
    }
    /**
     * Get config files
     * @param string $base base directory
     * @param string $dirname submission directory
     * @return string[][] array fo array with files as ['name'] and ['content']
     */
    protected function get_files($base, $dirname) {
        $files = [];
        $filelst = $dirname . '.lst';
        $extrafiles = [
                $filelst,
                $filelst . '.keep',
                'submitedfilelist.txt',
                'compilation.txt',
                'execution.txt',
                'grade_comments.txt',
        ];
        foreach ($extrafiles as $file) {
            if (file_exists ( $base . $file )) {
                $data = [];
                $data['name'] = $file;
                $data['content'] = file_get_contents ( $base . $file );
                $files[] = $data;
            }
        }
        $dirpath = $base . $dirname;
        if (file_exists ( $dirpath )) {
            $dirlst = opendir ( $dirpath );
            while ( false !== ($filename = readdir ( $dirlst )) ) {
                if ($filename == "." || $filename == "..") {
                    continue;
                }
                $data = [];
                $data['name'] = $dirname . '/' . $filename;
                $data['content'] = file_get_contents ( $dirpath . '/' . $filename );
                $files[] = $data;
            }
            closedir ( $dirlst );
        }
        return $files;
    }
    /**
     * Store execution files
     */
    protected function process_execution_files() {
        $files = $this->get_files ( $this->get_config_path (), 'execution_files' );
        if (count ( $files ) > 0) {
            $this->xmlwriter->begin_tag ( 'execution_files' );
            foreach ($files as $file) {
                $this->write_xml ( 'execution_file', $file );
            }
            $this->xmlwriter->end_tag ( 'execution_files' );
        }
    }
    /**
     * Store required files
     */
    protected function process_required_files() {
        $files = $this->get_files ( $this->get_config_path (), 'required_files' );
        if (count ( $files ) > 0) {
            $this->xmlwriter->begin_tag ( 'required_files' );
            foreach ($files as $file) {
                $this->write_xml ( 'required_file', $file );
            }
            $this->xmlwriter->end_tag ( 'required_files' );
        }
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/MOD/VPL
     * data available (VPL INSTANCE)
     * @param array $data vpl instance as array
     */
    public function process_vpl($data) {
        // Get the course module id and context id.
        $this->instanceid = $data['id'];
        $cminfo = $this->get_cminfo ( $this->instanceid );
        $this->moduleid = $cminfo['id'];
        $contextid = $this->converter->get_contextid ( CONTEXT_MODULE, $this->moduleid );

        // Get intro from file.
        $data['intro'] = $this->get_fulldescription ();
        $data['introformat'] = 1;

        // Start writing vpl.xml.
        $this->open_xml_writer ( "activities/vpl_{$this->moduleid}/vpl.xml" );
        $this->xmlwriter->begin_tag ( 'activity', [
                'id' => $this->instanceid,
                'moduleid' => $this->moduleid,
                'modulename' => 'vpl',
                'contextid' => $contextid,
        ] );
        $this->xmlwriter->begin_tag ( 'vpl', [
                'id' => $this->instanceid,
        ] );

        foreach ($data as $field => $value) {
            if ($field != 'id') {
                $this->xmlwriter->full_tag ( $field, $value );
            }
        }
        $this->process_required_files ();
        $this->process_execution_files ();
        return $data;
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/MOD/VPL/VARIATIONS/VARIATIONS
     * data available
     * Start tag
     */
    public function on_vpl_variations_start() {
        $this->xmlwriter->begin_tag ( 'variations' );
    }
    /**
     * End tag for variation instance
     */
    public function on_vpl_variations_end() {
        $this->xmlwriter->end_tag ( 'variations' );
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/MOD/VPL/VARIATIONS/VARIATIONS/VARIATION
     * data available
     * @param array $data variant instance
     */
    public function process_vpl_variation($data) {
        $this->write_xml ( 'variation', $data, [
                '/variation/id',
        ] );
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/MOD/VPL/SUBMISSIONS
     * data available
     */
    public function on_vpl_submissions_start() {
        $this->xmlwriter->begin_tag ( 'submissions' );
    }
    /**
     * Write en tag of vpl submissions
     */
    public function on_vpl_submissions_end() {
        $this->xmlwriter->end_tag ( 'submissions' );
    }
    /**
     * Store submitted files
     * @param int $userid
     * @param int $subid
     */
    public function process_submitted_files($userid, $subid) {
        $path = $this->get_submission_path ( $userid, $subid );
        $files = $this->get_files ( $path, 'submitedfiles' );
        if (count ( $files ) > 0) {
            $this->xmlwriter->begin_tag ( 'submission_files' );
            foreach ($files as $file) {
                $this->write_xml ( 'submission_file', $file );
            }
            $this->xmlwriter->end_tag ( 'submission_files' );
        }
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/MOD/VPL/SUBMISSIONS/SUBMISSION
     * @param array $data available
     */
    public function process_vpl_submission($data) {
        $this->xmlwriter->begin_tag ( 'submission', $data, [
                '/submission/id',
        ] );
        $this->process_submitted_files ( $data['userid'], $data['id'] );
        $this->xmlwriter->end_tag ( 'submission' );
    }

    /**
     * This is executed when we reach the closing </MOD> tag of 'vpl'
     */
    public function on_vpl_end() {
        // Finish writing vpl.xml.
        $this->xmlwriter->end_tag ( 'vpl' );
        $this->xmlwriter->end_tag ( 'activity' );
        $this->close_xml_writer ();

        // Write inforef.xml.
        $this->open_xml_writer ( "activities/vpl_{$this->moduleid}/inforef.xml" );
        $this->xmlwriter->begin_tag ( 'inforef' );
        $this->xmlwriter->end_tag ( 'inforef' );
        $this->close_xml_writer ();
    }
}
