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
 * VPL class definition
 *
 * @package mod_vpl
 * @copyright 2013 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/**
 * Module instance files
 * path= vpl_data/vpl_instance#
 * General info
 * path/required_files.lst
 * path/required_files/
 * path/execution_files.lst
 * path/execution_files/
 * path/execution_files/vpl_run.sh
 * path/execution_files/vpl_debug.sh
 * path/execution_files/vpl_evaluate.sh
 *  * Submission info
 * path/usersdata/userid#/submissionid#/submittedfiles/
 * path/usersdata/userid#/submissionid#/submittedfiles.lst
 * path/usersdata/userid#/submissionid#/grade_comments.txt
 * path/usersdata/userid#/submissionid#/teachertest.txt
 * path/usersdata/userid#/submissionid#/studenttest.txt
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/filegroup.class.php');
require_once(dirname(__FILE__).'/lib.php');

class file_group_execution extends file_group_process {
    /**
     * Name of fixed file names
     *
     * @var string[]
     */
    protected static $basefiles = [
            'vpl_run.sh',
            'vpl_debug.sh',
            'vpl_evaluate.sh',
            'vpl_evaluate.cases',
    ];

    /**
     * Number of $basefiles elements
     *
     * @var int
     */
    protected static $numbasefiles;

    /**
     * Constructor
     *
     * @param string $filelistname
     * @param string $dir
     */
    public function __construct($dir) {
        self::$numbasefiles = count( self::$basefiles );
        parent::__construct( $dir, 1000, self::$numbasefiles );
    }

    /**
     * Get list of files
     *
     * @return string[]
     */
    public function getfilelist() {
        return array_values( array_unique( array_merge( self::$basefiles, parent::getfilelist() ) ) );
    }

    /**
     * Get the file comment by number
     *
     * @param int $num
     * @return string
     */
    public function getfilecomment($num) {
        if ($num < self::$numbasefiles) {
            return get_string( self::$basefiles[$num], VPL );
        } else {
            return get_string( 'file' ) . ' ' . ($num + 1 - self::$numbasefiles);
        }
    }

    /**
     * Get list of files to keep when running
     *
     * @return string[]
     */
    public function getfilekeeplist() {
        return file_group_process::read_list( $this->filelistname . '.keep' );
    }

    /**
     * Set the file list to keep when running
     *
     * @param string[] $filelist
     */
    public function setfilekeeplist($filelist) {
        file_group_process::write_list( $this->filelistname . '.keep', $filelist );
    }
}

class mod_vpl {
    /**
     * Internal var for course_module
     *
     * @var object $cm
     */
    protected $cm;

    /**
     * Internal var for course
     *
     * @var object $course
     */
    protected $course;

    /**
     * Internal var for vpl
     *
     * @var object $instance
     */
    protected $instance;

    /**
     * Internal var object to requied file group manager
     *
     * @var object of file group manager
     */
    protected $requiredfgm;

    /**
     * An internal var object of the execution file group manager
     *
     * @var object of file group manager
     */
    protected $executionfgm;

    /**
     * An internal array of messages describing errors found
     *
     * @var string[]
     */
    protected $errors = [];

    /**
     * The graders of this activity and group cached
     *
     * @var object[][]
     */
    protected $graders = [];

    /**
     * The students of this activity and group cached
     *
     * @var object[][]
     */
    protected $students = [];

    /**
     * An internal array of DB instances cached
     *
     * @var object[]
     */
    protected static $instancescache = [];

    /**
     * Returns a record instance, cached or from DB
     *
     * @param string $table Table name
     * @param int $id The id of the register to get from the table
     * @return object this the register or null if not found
     */
    public static function get_db_record($table, $id) {
        global $DB;
        if (! isset(self::$instancescache[$table])) {
            self::$instancescache[$table] = [];
        }
        if (! isset(self::$instancescache[$table][$id])) {
            self::$instancescache[$table][$id] = $DB->get_record($table, ["id" => $id]);
        }
        return self::$instancescache[$table][$id];
    }
    /**
     * Remove one record or all records from cache
     *
     * @param string $table Table name
     * @param int $id The id of the register to get from the table
     * @return void
     */
    public static function reset_db_cache($table = 'all', $id = -1) {
        if ($table == 'all') {
            self::$instancescache = [];
        } else {
            unset(self::$instancescache[$table][$id]);
        }
    }

    /**
     * Constructor
     *
     * @param int $id optional course_module id
     * @param int $a optional VPL instance id
     */
    public function __construct($id, $a = null) {
        global $DB;
        if ($id) {
            $this->cm = get_coursemodule_from_id(VPL, $id);
            if (! $this->cm) {
                throw new moodle_exception('invalidcoursemodule');
            }
            $this->course = self::get_db_record("course", $this->cm->course);
            if (! $this->course) {
                throw new moodle_exception('invalidcourseid');
            }
            $this->instance = self::get_db_record(VPL, $this->cm->instance);
            if (! $this->instance) {
                throw new moodle_exception('invalidcoursemodule');
            }
            $this->instance->cmidnumber = $this->cm->idnumber;
        } else {
            $this->instance = self::get_db_record(VPL, $a);
            if (! $this->instance) {
                throw new moodle_exception('error:inconsistency', 'mod_vpl', '', VPL.$a);
            }
            $this->course = self::get_db_record("course", $this->instance->course);
            if (!$this->course ) {
                throw new moodle_exception('invalidcourseid');
            }
            $this->cm = get_coursemodule_from_instance( VPL, $this->instance->id, $this->course->id );
            if (! ($this->cm)) {
                // Don't stop on error. This let delete a corrupted course.
                $this->errors[] = get_string( 'invalidcoursemodule', 'error' );
            } else {
                $this->instance->cmidnumber = $this->cm->idnumber;
            }
        }
        if (! $this->basedon_is_ok()) {
            // Don't stop on error. This allow to repare based on chain.
            if (! self::get_db_record(VPL, $this->instance->basedon)) {
                $this->errors[] = get_string('basedon_deleted', VPL);
                $this->instance->basedon = 0; // Avoid missing VPL errors.
            } else {
                $this->errors[] = get_string('basedon_chain_broken', VPL);
            }
        }
        $this->requiredfgm = null;
        $this->executionfgm = null;
    }

    /**
     *
     * @return Object of module DB instance
     */
    public function get_instance() {
        return $this->instance;
    }

    /**
     *
     * @return Object of course DB instance
     *
     */
    public function get_course() {
        return $this->course;
    }

    /**
     *
     * @return Object of course_module DB instance
     *
     */
    public function get_course_module() {
        return $this->cm;
    }

    /**
     * Delete a vpl instance
     *
     * @return bool true if all OK
     */
    public function delete_all() {
        return vpl_delete_instance($this->instance->id);
    }

    /**
     * Update a VPL instance including timemodified field
     *
     * @return bool true if all OK
     */
    public function update() {
        global $DB;
        $this->instance->timemodified = time();
        self::reset_db_cache(VPL, $this->instance->id);
        return $DB->update_record(VPL, $this->instance);
    }

    /**
     * Get data directory path
     * @return string data directory path
     */
    public function get_data_directory() {
        global $CFG;
        return $CFG->dataroot . '/vpl_data/' . $this->instance->id;
    }

    /**
     * Get config data directory path
     * @return string config data directory path
     */
    public function get_users_data_directory() {
        return $this->get_data_directory() . '/usersdata';
    }

    /**
     *
     * @return directory to stored initial required files
     */
    public function get_required_files_directory() {
        return $this->get_data_directory() . '/required_files/';
    }

    /**
     * Get path to filename to store required files
     * @return string path to filename to store required files
     */
    public function get_required_files_filename() {
        return $this->get_data_directory() . '/required_files.lst';
    }

    /**
     * Get array of files required file names
     * @return array of strings
     */
    public function get_required_files() {
        return $this->get_required_fgm()->getfilelist();
    }

    /**
     *
     * @param $files array
     *            of required files
     */
    public function set_required_files($files) {
        $this->get_required_fgm()->setfilelist($files);
    }

    /**
     *
     * @return object file group manager for required files
     */
    public function get_required_fgm() {
        if (! $this->requiredfgm) {
            $this->requiredfgm = new file_group_process( $this->get_required_files_directory()
                                                       , $this->instance->maxfiles );
        }
        return $this->requiredfgm;
    }

    /**
     *
     * @return directory to stored execution files
     */
    public function get_execution_files_directory() {
        return $this->get_data_directory() . '/execution_files/';
    }

    /**
     * Get path filename to store execution files
     * @return string path filename to store execution files
     */
    public function get_execution_files_filename() {
        return $this->get_data_directory() . '/execution_files.lst';
    }

    /**
     *
     * @return array of files execution name
     */
    public function get_execution_files() {
        return $this->get_execution_fgm()->getfilelist();
    }

    /**
     *
     * @return object file group manager for execution files
     */
    public function get_execution_fgm() {
        if (! $this->executionfgm) {
            $this->executionfgm = new file_group_execution( $this->get_execution_files_directory() );
        }
        return $this->executionfgm;
    }

    /**
     * Return the list of readonly files for students
     *
     * @return array readonly files for students
     */
    public function get_readonly_files() {
        $exeflist = $this->get_execution_files();
        $reqflist = $this->get_required_files();
        return array_values(array_intersect($exeflist, $reqflist));
    }

    /**
     * get instance name with groupping name if available
     *
     * @return string with name+(grouping name)
     */
    public function get_name() {
        global $CFG;
        $ret = $this->instance->name;
        if (! empty( $CFG->enablegroupings ) && ($this->cm->groupingid > 0)) {
            $grouping = groups_get_grouping( $this->cm->groupingid );
            if ($grouping !== false) {
                $ret .= ' (' . $grouping->name . ')';
            }
        }
        if (count($this->errors)) {
            $ret .= ' (' . get_string('error') . ')';
        }
        return $ret;
    }

    /**
     * get instance filtered name with groupping name if available
     *
     * @return string with name+(grouping name)
     */
    public function get_printable_name() {
        return format_string($this->get_name());
    }

    /**
     * Get fulldescription
     *
     * @return string fulldescription
     *
     */
    public function get_fulldescription() {
        $instance = $this->get_instance();
        if ($instance->intro) {
            return format_module_intro( VPL, $this->get_instance(), $this->get_course_module()->id );
        } else {
            return '';
        }
    }

    /**
     * Get fulldescription adding basedon descriptions
     *
     * @return string fulldescription
     *
     */
    public function get_fulldescription_with_basedon() {
        $ret = '';
        if ($this->instance->basedon) { // Show recursive variations.
            $basevpl = new mod_vpl( false, $this->instance->basedon );
            $ret .= $basevpl->get_fulldescription_with_basedon();
        }
        return $ret . $this->get_fulldescription();
    }
    /**
     * Return maximum file size allowed
     *
     * @return int
     *
     */
    public function get_maxfilesize() {
        $plugincfg = get_config('mod_vpl');
        $max = \mod_vpl\util\phpconfig::get_post_max_size();
        if ($plugincfg->maxfilesize > 0 && $plugincfg->maxfilesize < $max) {
            $max = $plugincfg->maxfilesize;
        }
        if ($this->instance->maxfilesize > 0 && $this->instance->maxfilesize < $max) {
            $max = $this->instance->maxfilesize;
        }
        return $max;
    }

    /**
     * Get grading information help
     *
     * @return string grade comments summary in html format
     */
    public function get_grading_help() {
        $list = [];
        $submissions = $this->all_last_user_submission();
        foreach ($submissions as $submission) {
            $sub = new mod_vpl_submission( $this, $submission );
            $sub->filter_feedback( $list );
        }
        // TODO show evaluation criteria with show hidde button.
        $all = [];
        foreach ($list as $text => $info) {
            $astext = s( addslashes_js( $text ) );
            $html = '';
            $html .= s( $text );
            foreach (array_keys($info->grades) as $grade) {
                if ($grade >= 0) { // No grade.
                    $jscript = 'VPL.addComment(\'' . $astext . '\')';
                } else {
                    $jscript = 'VPL.addComment(\'' . $astext . ' (' . $grade . ')\')';
                }
                $link = '<a href="javascript:void(0)" onclick="' . $jscript . '">' . $grade . '</a>';
                $html .= ' (' . $link . ')';
            }
            $html .= '<br>';
            if (isset( $all[$info->count] )) {
                $all[$info->count] .= '(' . $info->count . ') ' . $html;
            } else {
                $all[$info->count] = '(' . $info->count . ') ' . $html;
            }
        }
        // Sort comments by number of occurrences.
        krsort( $all );
        $html = '';
        foreach ($all as $info) {
            $html .= $info;
        }
        // TODO show info about others review with show hidde button.
        return $html;
    }
    /**
     * Get password
     */
    protected function get_password() {
        return trim($this->get_effective_setting('password'));
    }

    /**
     * Get password md5
     */
    protected function get_password_md5() {
        return md5($this->instance->id . sesskey());
    }

    /**
     * Check if pass password restriction
     */
    public function pass_password_check($passset = '') {
        $password = $this->get_password();
        if ($password > '' && ! $this->has_capability(VPL_GRADE_CAPABILITY)) {
            global $SESSION;
            $passwordmd5 = $this->get_password_md5();
            $passvar = 'vpl_password_' . $this->instance->id;
            $passattempt = 'vpl_password_attempt' . $this->instance->id;
            if (isset( $SESSION->$passvar ) && $SESSION->$passvar == $passwordmd5) {
                return true;
            }
            if ($passset == '') {
                $passset = optional_param( 'password', '', PARAM_TEXT );
            }
            if ($passset > '') {
                if ($passset == $password) {
                    $SESSION->$passvar = $passwordmd5;
                    unset( $SESSION->$passattempt );
                    return true;
                }
                if (isset( $SESSION->$passattempt )) {
                    $SESSION->$passattempt ++;
                } else {
                    $SESSION->$passattempt = 1;
                }
                // Wait vpl_password_attempt seconds to limit force brute crack.
                sleep( $SESSION->$passattempt );
            }
            return false;
        }
        return true;
    }

    /**
     * Check password restriction
     */
    protected function password_check() {
        global $SESSION;
        if (! $this->pass_password_check()) {
            if (constant('AJAX_SCRIPT') ) {
                throw new Exception(get_string('requiredpassword', VPL));
            }
            require_once('forms/password_form.php');
            $this->print_header();
            $mform = new mod_vpl_password_form($_SERVER['SCRIPT_NAME'], $this);
            $passattempt = 'vpl_password_attempt' . $this->get_instance()->id;
            if (isset( $SESSION->$passattempt)) {
                vpl_notice(get_string('attemptnumber', VPL, $SESSION->$passattempt),
                            'warning');
            }
            $mform->display();
            $this->print_footer();
            die();
        }
    }

    /**
     * Check network restriction and return true o false
     * @return boolean
     */
    public function pass_network_check() {
        if ($this->instance->requirednet > '' && ! $this->has_capability(VPL_GRADE_CAPABILITY)) {
            return vpl_check_network( $this->instance->requirednet );
        }
        return true;
    }

    /**
     * Check network restriction and show error if not passed
     * @return void
     */
    protected function network_check() {
        if (! $this->pass_network_check()) {
            $str = get_string('opnotallowfromclient', VPL) . ' ' . getremoteaddr();
            if (constant('AJAX_SCRIPT')) {
                throw new Exception($str);
            }
            $this->print_header();
            vpl_notice($str , 'warning');
            $this->print_footer();
            die();
        }
    }

    /**
     * Checks if SEB key is valid
     * @return void
     */
    protected function is_sebkey_valid() {
        global $FULLME;
        $keys = trim($this->get_instance()->sebkeys);
        if ( $keys == '') {
            return true;
        }
        if (isset($_SERVER['HTTP_X_SAFEEXAMBROWSER_REQUESTHASH']) ) {
            $key = $_SERVER['HTTP_X_SAFEEXAMBROWSER_REQUESTHASH'];
            foreach (preg_split('/\s+/', $keys) as $testkey) {
                if (hash('sha256', $FULLME . $testkey) === $key) {
                    return true;
                }
            }
        }
        if (isset($_SERVER['HTTP_X_SAFEEXAMBROWSER_CONFIGKEYHASH']) ) {
            $key = $_SERVER['HTTP_X_SAFEEXAMBROWSER_CONFIGKEYHASH'];
            foreach (preg_split('/\s+/', $keys) as $testkey) {
                if (hash('sha256', $FULLME . $testkey) === $key) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Checks SEB restrictions returns true if passed.
     *
     * @return bool
     */
    public function pass_seb_check() {
        $inst = $this->get_instance();
        $fail = $inst->sebrequired > 0;
        $fail = $fail && strpos($_SERVER['HTTP_USER_AGENT'], 'SEB') === false;
        $fail = $fail || ! $this->is_sebkey_valid();
        return ! $fail;
    }

    /**
     * Checks SEB restrictions and shows error if not passed.
     *
     * @return void
     */
    protected function seb_check() {
        if (! $this->pass_seb_check() && ! $this->has_capability(VPL_GRADE_CAPABILITY)) {
            $str = get_string('sebrequired_help', VPL);
            if (constant('AJAX_SCRIPT')) {
                throw new Exception($str);
            }
            $this->print_header();
            vpl_notice($str, 'warning');
            $this->print_footer();
            die();
        }
    }

    /**
     * Return true if is set to use SEB.
     * @return void
     */
    protected function use_seb() {
        $inst = $this->get_instance();
        return $inst->sebrequired || $inst->sebkeys > '';
    }

    /**
     * Checks all restrictions and shows error if not passed
     * @return void
     */
    public function restrictions_check() {
        $this->network_check();
        $this->password_check();
        $this->seb_check();
    }

    /**
     * Check submission restriction
     *
     * @param $data Object
     *            with submitted data
     * @param & $error
     *            string
     * @return bool
     *
     */
    public function pass_submission_restriction(& $alldata, & $error) {
        $max = $this->get_maxfilesize();
        $rfn = $this->get_required_fgm();
        $list = $rfn->getFilelist();
        $error = '';
        if (count( $alldata ) > $this->instance->maxfiles) {
            $error .= get_string( 'maxfilesexceeded', VPL ) . "\n";
        }
        $lr = count( $list );
        $i = 0;
        foreach ($alldata as $name => $data) {
            if (strlen( $data ) > $max) {
                $error .= '"' . s( $name ) . '" ' . get_string( 'maxfilesizeexceeded', VPL ) . "<br>";
            }
            if (! vpl_is_valid_path_name( $name )) {
                $error .= '"' . s( $name ) . '" ' . get_string( 'incorrect_file_name', VPL ) . "<br>";
            }
            if ($i < $lr && $list[$i] != $name) {
                $a = new stdClass();
                $a->expected = $list[$i];
                $a->found = $name;
                $error .= s( get_string( 'unexpected_file_name', VPL, $a ) ) . "<br>";
            }
            $i++;
        }
        return strlen( $error ) == 0;
    }

    /**
     * Internal checks and adds submission if possible. Removes unneeded submissions.
     *
     * @param Object $vpl
     * @param int $userid
     * @param array & $files submitted files
     * @param string $comments
     * @param string & $error Error message
     * @return int|false Submission id or false if error
     */
    public static function internal_add_submission($vpl, $userid, & $files, $comments, & $error) {
        global $USER, $DB;
        if (! $vpl->pass_submission_restriction( $files, $error )) {
            $error = get_string('notavailable');
            return false;
        }
        $group = false;
        if ($vpl->is_group_activity()) {
            $group = $vpl->get_usergroup($userid);
            if ($group === false) {
                $error = get_string( 'notsaved', VPL ) . "\n" . get_string( 'inconsistentgroup', VPL );
                return false;
            }
        }
        $submittedby = '';
        if ($USER->id != $userid ) {
            if ($vpl->has_capability(VPL_MANAGE_CAPABILITY) ||
                $vpl->has_capability(VPL_GRADE_CAPABILITY) ) {
                $user = self::get_db_record('user', $USER->id);
                $submittedby = get_string('submittedby', VPL, fullname($user)) . "\n";
                if (strpos($comments, $submittedby) !== false ) {
                    $submittedby = '';
                }
            } else {
                $error = get_string('notsaved', VPL);
                return false;
            }
        }
        $lastsub = false;
        if (($lastsubins = $vpl->last_user_submission( $userid )) !== false) {
            $lastsub = new mod_vpl_submission($vpl, $lastsubins);
            if ($lastsub->is_equal_to($files, $submittedby . $comments)) {
                return $lastsubins->id;
            }
        }
        // Create submission record.
        $submissiondata = new stdClass();
        $submissiondata->vpl = $vpl->get_instance()->id;
        $submissiondata->userid = $userid;
        $submissiondata->datesubmitted = time();
        $submissiondata->comments = $submittedby . $comments;
        if ( $lastsubins !== false ) {
            $submissiondata->nevaluations = $lastsubins->nevaluations;
        }
        if ( $group !== false ) {
            $submissiondata->groupid = $group->id;
        }
        $submissionid = $DB->insert_record( 'vpl_submissions', $submissiondata, true );
        if (! $submissionid) {
            $error = get_string( 'notsaved', VPL ) . "\ninserting vpl_submissions record";
            return false;
        }
        // Save files.
        $submission = new mod_vpl_submission( $vpl, $submissionid );
        try {
            $submission->set_submitted_file( $files, $lastsub );
        } catch (file_exception $fe) {
            $DB->delete_records( VPL_SUBMISSIONS, ['id' => $submissionid]);
            $error = $fe->getMessage();
            return false;
        }
        $submission->remove_grade();
        // If no submitted by grader, remove near submmissions.
        if ($USER->id == $userid) {
            $vpl->delete_overflow_submissions( $userid );
        }
        return $submissionid;
    }
    /**
     * Checks and adds submission if possible. Removes unneeded submissions.
     *
     * @param int $userid
     * @param array & $files submitted files
     * @param string $comments
     * @param string & $error Error message
     * @return int|false Submission id or false if error
     */
    public function add_submission($userid, & $files, $comments, & $error) {
        global $USER;
        if ($USER->id != $userid ) {
            if (!$this->has_capability( VPL_MANAGE_CAPABILITY ) &&
                !$this->has_capability( VPL_GRADE_CAPABILITY )) {
                    $error = get_string('notavailable');
                    return false;
            }
        }
        $vplid = $this->get_instance()->id;
        $locktype = 'vpl:submission';
        $resource = "$vplid:$userid";
        $funcname = 'mod_vpl::internal_add_submission';
        $parms = [$this, $userid, $files, $comments, $error];
        $result = vpl_call_with_lock($locktype, $resource, $funcname, $parms );
        $error = $parms[4];
        return $result;
    }

    /**
     * Get user submissions, order reverse submission id
     *
     * @param $userid int the user id to retrieve submissions
     * @param $groupifga boolean if group activity get group submissions. default true
     * @return array of objects
     */
    public function user_submissions($userid, $groupifga = true) {
        global $DB;
        if ($groupifga && $this->is_group_activity()) {
            $group = $this->get_usergroup($userid);
            if ($group) {
                $select = '(groupid = ?) AND (vpl = ?)';
                $parms = [
                        $group->id,
                        $this->instance->id,
                ];
            } else {
                return [];
            }
        } else {
            $select = '(userid = ?) AND (vpl = ?)';
            $parms = [
                    $userid,
                    $this->instance->id,
            ];
        }
        return $DB->get_records_select( 'vpl_submissions', $select, $parms, 'id DESC' );
    }

    /**
     * Get the last submission of all users
     *
     * @param string $fields conma separeted
     *            to retrieve from submissions table, default s.*. userid is always retrieved
     * @return object array
     */
    public function all_last_user_submission($fields = 's.*') {
        // Get last submissions records for this vpl module.
        global $DB;
        $id = $this->get_instance()->id;
        if ($this->is_group_activity()) {
            $idfield = 'groupid';
        } else {
            $idfield = 'userid';
        }
        $query = "SELECT s.$idfield, $fields FROM {vpl_submissions} s";
        $query .= ' inner join ';
        $query .= ' (SELECT max(id) as maxid FROM {vpl_submissions} ';
        $query .= '  WHERE {vpl_submissions}.vpl=? ';
        $query .= "  GROUP BY {vpl_submissions}.$idfield) as ls";
        $query .= ' on s.id = ls.maxid';
        return $DB->get_records_sql( $query, [ $id ] );
    }

    /**
     * Get all saved submission of all users
     *
     * @param string $fields fields conma separeted
     *            to retrieve from submissions table, default s.*
     * @return object array
     */
    public function all_user_submission($fields = 's.*') {
        global $DB;
        $id = $this->get_instance()->id;
        $query = "SELECT s.id, $fields FROM {vpl_submissions} s WHERE vpl=?";
        return $DB->get_records_sql( $query, [ $id ] );
    }
    /**
     * Get number of user submissions
     *
     * @return Array of objects with 'submissions' atribute as number of submissions saved
     */
    public function get_submissions_number() {
        global $DB;
        if ( $this->is_group_activity() ) {
            $field = 'groupid';
        } else {
            $field = 'userid';
        }
        $query = "SELECT $field, COUNT(*) as submissions FROM {vpl_submissions}";
        $query .= ' WHERE {vpl_submissions}.vpl=?';
        $query .= " GROUP BY {vpl_submissions}.$field";
        $parms = [
                $this->get_instance()->id,
        ];
        return $DB->get_records_sql( $query, $parms );
    }

    /**
     * This is for compatibility to old group scheme.
     * Update the submission groupid for VPL version <= 3.2
     *
     * Set the correct groupid when groupid = 0
     * @param int $groupid. If no $groupid => update $groupid of all groups of the activity
     * @return void
     */
    public function update_group_v32($groupid = '') {
        global $DB;
        if ( ! $this->is_group_activity() ) {
            return;
        }
        // All groups.
        if ($groupid == '') {
            $cm = $this->get_course_module();
            $groups = groups_get_all_groups($this->get_course()->id, 0, $cm->groupingid);
            foreach ($groups as $cgroup) {
                $this->update_group_v32($cgroup->id);
            }
        }
        $students = $this->get_students($groupid);
        if (count($students) > 0) {
            $studentsids = array_keys($students);
            $insql = $DB->get_in_or_equal($studentsids);
            $vplid = $this->get_instance()->id;
            $select = 'userid ' . ($insql[0]) . ' and vpl = ? and groupid = 0';
            $params = $insql[1];
            $params[] = $vplid;
            $DB->set_field_select(VPL_SUBMISSIONS, 'groupid', $groupid, $select, $params);
        }
    }

    /**
     * Get last user submission
     *
     * @param int $userid
     * @return false/object
     *
     */
    public function last_user_submission($userid) {
        global $DB;
        if ($this->is_group_activity()) {
            $group = $this->get_usergroup($userid);
            if ($group !== false) {
                $select = "(groupid = ?) AND (vpl = ?)";
                $params = [
                        $group->id,
                        $this->instance->id,
                ];
                $res = $DB->get_records_select( 'vpl_submissions', $select, $params, 'id DESC', '*', 0, 1 );
                foreach ($res as $sub) {
                    return $sub;
                }
                $this->update_group_v32($group->id);
                $res = $DB->get_records_select( 'vpl_submissions', $select, $params, 'id DESC', '*', 0, 1 );
                foreach ($res as $sub) {
                    return $sub;
                }
            }
            return false;
        }
        $select = "(userid = ?) AND (vpl = ?)";
        $params = [
                $userid,
                $this->instance->id,
        ];
        $res = $DB->get_records_select( 'vpl_submissions', $select, $params, 'id DESC', '*', 0, 1 );
        foreach ($res as $sub) {
            return $sub;
        }
        return false;
    }

    /**
     * Return context object for this module instance
     *
     * @return object
     */
    public function get_context() {
        return context_module::instance( $this->cm->id );
    }

    /**
     * Requiere the current user has the capability of performing $capability in this module instance
     *
     * @param string $capability capability name
     * @return void
     */
    public function require_capability($capability) {
        if (! $this->has_capability($capability)) {
            $context = $this->get_context();
            $errormessage = 'nopermissions';
            throw new required_capability_exception($context, $capability, $errormessage, '');
        }
    }

    /**
     * Check if the user has the capability of performing $capability in this module instance
     *
     * @param string $capability The capability name
     * @param int $userid Default null => current user
     * @return bool
     */
    public function has_capability($capability, $userid = null) {
        return is_enrolled($this->get_context(), $userid, $capability, true) ||
               is_siteadmin();
    }

    /**
     * Delete overflow submissions. If three submissions within the period central is delete
     *
     * @param $userid
     * @return void
     *
     */
    public function delete_overflow_submissions($userid) {
        global $DB;
        $plugincfg = get_config('mod_vpl');
        if (! isset( $plugincfg->discard_submission_period )) {
            return;
        }
        if ($plugincfg->discard_submission_period == 0) {
            // Keep all submissions.
            return;
        }
        if ($plugincfg->discard_submission_period > 0) {
            $select = "(userid = ?) AND (vpl = ?)";
            $params = [
                    $userid,
                    $this->instance->id,
            ];
            $res = $DB->get_records_select( VPL_SUBMISSIONS, $select, $params, 'id DESC', '*', 0, 3 );
            if (count( $res ) == 3) {
                $i = 0;
                foreach ($res as $sub) {
                    switch ($i) {
                        case 0 :
                            $last = $sub;
                            break;
                        case 1 :
                            $second = $sub;
                            break;
                        case 2 :
                            $first = $sub;
                            break;
                    }
                    $i ++;
                }
                // Check time consistence.
                if (! ($last->datesubmitted > $second->datesubmitted && $second->datesubmitted > $first->datesubmitted)) {
                    return;
                }
                if (($last->datesubmitted - $first->datesubmitted) < $plugincfg->discard_submission_period) {
                    // Remove second submission.
                    $submission = new mod_vpl_submission( $this, $second );
                    $submission->delete();
                }
            }
        }
    }

    /**
     * Check if it is submission period
     *
     * @param int $userid (optional) Check for given user, current user if null.
     * @return bool
     */
    public function is_submission_period($userid = null) {
        $now = time();
        $startdate = $this->get_effective_setting('startdate', $userid);
        $duedate = $this->get_effective_setting('duedate', $userid);
        return $startdate <= $now && ($duedate == 0 || $duedate >= $now);
    }

    /**
     * is visible this vpl instance
     *
     * @return bool
     */
    public function is_visible($userid = false) {
        global $USER;
        if (empty($userid)) {
            $userid = $USER->id;
        }
        $cm = $this->get_course_module();
        $modinfo = get_fast_modinfo($cm->course, $userid);
        $ret = true;
        $ret = $ret && $modinfo->get_cm($cm->id)->uservisible;
        $ret = $ret && $this->has_capability(VPL_VIEW_CAPABILITY, $userid);
        // Grader and manager always view.
        $ret = $ret || $this->has_capability(VPL_GRADE_CAPABILITY, $userid);
        $ret = $ret || $this->has_capability(VPL_MANAGE_CAPABILITY, $userid);
        return $ret;
    }

    /**
     * this vpl instance admit submission
     *
     * @param int $userid (optional) Check for given user, current user if null.
     * @return bool
     */
    public function is_submit_able($userid = null) {
        $cm = $this->get_course_module();
        $modinfo = get_fast_modinfo( $cm->course );
        $instance = $this->get_instance();
        $ret = true;
        $ret = $ret && $this->has_capability( VPL_SUBMIT_CAPABILITY );
        $ret = $ret && $this->is_submission_period( $userid );
        $ret = $ret && $modinfo->get_cm( $cm->id )->uservisible;
        // Manager or grader can always submit.
        $ret = $ret || $this->has_capability( VPL_GRADE_CAPABILITY );
        $ret = $ret || $this->has_capability( VPL_MANAGE_CAPABILITY );
        return $ret;
    }

    /**
     * Is a group activity?
     *
     * @return bool
     */
    public function is_group_activity() {
        if (! isset( $this->group_activity )) {
            $cm = $this->get_course_module();
            $this->group_activity = $cm->groupingid > 0 && $this->get_instance()->worktype == 1;
            // TODO check groups_get_activity_groupmode($cm)==SEPARATEGROUPS.
        }
        return $this->group_activity;
    }

    /**
     * Return HTML code to show user picture and user fullname
     * @param object $user DB instance
     * @return string HTML code
     */
    public function user_fullname_picture($user) {
        return $this->user_picture( $user ) . ' ' . $this->fullname( $user );
    }

    /**
     * Return HTML code to show user picture
     * @param object $user DB instance
     * @return string HTML code
     */
    public function user_picture($user) {
        global $OUTPUT;
        if ($this->is_group_activity()) {
            return print_group_picture( $this->get_usergroup( $user->id ), $this->get_course()->id, false, true );
        } else {
            $options = ['courseid' => $this->get_instance()->course, 'link' => ! $this->use_seb()];
            return $OUTPUT->user_picture( $user, $options);
        }
    }

    /**
     * Return formated name of user or group
     *
     * @param object $user
     * @param bool $withlink if true and is group activity add link to group. Default true.
     * @return String
     */
    public function fullname($user, $withlink = true) {
        if ($this->is_group_activity()) {
            $group = $this->get_usergroup( $user->id );
            if ($group !== false) {
                if ($withlink) {
                    $url = vpl_abs_href( '/user/index.php', 'id', $this->get_course()->id, 'group', $group->id );
                    return '<a href="' . $url . '">' . $group->name . '</a>';
                } else {
                    return $group->name;
                }
            }
            return '';
        } else {
            $fullname = fullname( $user );
            if ($withlink) {
                $url = vpl_abs_href( '/user/view.php', 'id', $user->id, 'course', $this->get_course()->id);
                $html = "<a href=\"$url\" title=\"$fullname\">$fullname</a>";
            } else {
                $html = $fullname;
            }
            return $html;
        }
    }

    /**
     * Get array of graders for this activity and group (optional)
     *
     * @param string $groupid optional parm with groupid to search for
     * @return array
     */
    public function get_graders($groupid = 0) {
        if (! is_int($groupid) ) {
            $groupid = intval($groupid);
        }
        if (! array_key_exists($groupid, $this->graders)) {
            $fields = vpl_get_picture_fields();
            $this->graders[$groupid] = get_enrolled_users( $this->get_context(), VPL_GRADE_CAPABILITY,
                                                $groupid, $fields, 'u.lastname ASC');
        }
        return $this->graders[$groupid];
    }

    /**
     * Get array of students for this activity. If group is set return only group members
     *
     * @param string|int $groupid optional parm with groupid to search for
     * @param string $extrafields optional parm with extrafields e.g. 'u.nameq, u.name2'
     *
     * @return array of objects
     */
    public function get_students($groupid = 0, $extrafields = '') {
        if (! is_int($groupid) ) {
            $groupid = intval($groupid);
        }
        if (! array_key_exists($groupid, $this->students)) {
            // Generate array of graders indexed.
            $nostudents = [];
            foreach ($this->get_graders($groupid) as $user) {
                $nostudents[$user->id] = true;
            }
            $students = [];
            $extrafields = trim($extrafields);
            if ( $extrafields > '' && $extrafields[0] != ',' ) {
                $extrafields = ',' . $extrafields;
            }
            $fields = vpl_get_picture_fields() . $extrafields;
            $all = get_enrolled_users( $this->get_context(), VPL_SUBMIT_CAPABILITY,
                            $groupid, $fields, 'u.lastname ASC');
            foreach ($all as $user) {
                if (! array_key_exists($user->id, $nostudents)) {
                    $students[$user->id] = $user;
                }
            }
            $this->students[$groupid] = $students;
        }
        return $this->students[$groupid];
    }

    /**
     * Return if is group activity
     *
     * @return bool
     */
    public function is_inconsistent_user($current, $real) {
        if ($this->is_group_Activity()) {
            return false;
        } else {
            return $current != $real;
        }
    }

    /**
     * If is a group activity search for a group leader for the group of the userid (0 is not found)
     *
     * @return Integer userid
     */
    public function get_group_leaderid($userid) {
        $leaderid = $userid;
        $group = $this->get_usergroup($userid);
        if ($group) {
            foreach ($this->get_usergroup_members( $group->id ) as $user) {
                if ($user->id < $leaderid) {
                    $leaderid = $user->id;
                }
            }
        }
        return $leaderid;
    }

    /**
     * If is a group activity return the group of the userid
     *
     * @return Object/false
     */
    public function get_usergroup($userid) {
        if ($this->is_group_activity()) {
            $courseid = $this->get_course()->id;
            $groupingid = $this->get_course_module()->groupingid;
            $groups = groups_get_all_groups( $courseid, $userid, $groupingid );
            if ($groups === false || count( $groups ) > 1) {
                return false;
            }
            return reset( $groups );
        }
        return false;
    }
    protected static $usergroupscache = [];
    /**
     * If is a group activity return group members for the groupid
     *
     * @return Array of user objects
     */
    public function get_group_members($groupid) {
        if (! isset( self::$usergroupscache[$groupid] )) {
            $gm = groups_get_members( $groupid );
            if ($gm) {
                self::$usergroupscache[$groupid] = $gm;
            } else {
                self::$usergroupscache[$groupid] = [];
            }
        }
        return self::$usergroupscache[$groupid];
    }
    /**
     * If is a group activity return group members for the group of the userid
     *
     * @return Array of user objects
     */
    public function get_usergroup_members($userid) {
        $group = $this->get_usergroup( $userid );
        if ($group !== false) {
            return $this->get_group_members($group->id);
        }
        return [];
    }

    /**
     * Return scale record if grade < 0
     *
     * @return Object or false
     */
    public function get_scale() {
        global $DB;
        if (! isset( $this->scale )) {
            if ($this->get_grade() < 0) {
                $gradeid = - $this->get_grade();
                $this->scale = self::get_db_record( 'scale', $gradeid);
            } else {
                $this->scale = false;
            }
        }
        return $this->scale;
    }

    /**
     * Return grade info take from gradebook
     *
     * @return Object or false
     */
    public function get_grade_info() {
        global $CFG, $USER;
        if (! isset( $this->grade_info )) {
            $this->grade_info = false;
            if ($this->get_instance()->grade != 0) { // If 0 then NO GRADE.
                $userid = ($this->has_capability( VPL_GRADE_CAPABILITY ) || $this->has_capability(
                        VPL_MANAGE_CAPABILITY )) ? null : $USER->id;
                require_once($CFG->libdir . '/gradelib.php');
                $gradinginfo = grade_get_grades( $this->get_course()->id, 'mod', 'vpl', $this->get_instance()->id, $userid );
                foreach ($gradinginfo->items as $gi) {
                    $this->grade_info = $gi;
                }
            }
        }
        return $this->grade_info;
    }

    /**
     * Return visiblegrade from gradebook and for every user
     *
     * @return boolean
     */
    public function get_visiblegrade() {
        if ($gi = $this->get_grade_info()) {
            if (is_array( $gi->grades )) {
                $usergi = reset( $gi->grades );
                return ! ($gi->hidden || (is_object( $usergi ) && $usergi->hidden));
            } else {
                return ! ($gi->hidden);
            }
        } else {
            return false;
        }
    }

    /**
     * Return grade (=0 => no grade, >0 max grade, <0 scaleid)
     *
     * @return int
     */
    public function get_grade() {
        return $this->instance->grade;
    }

    /**
     * print end of page
     */
    public function print_footer() {
        global $OUTPUT;
        if (! $this->use_seb() ) {
            $style = "float:right; right:10px; padding:8px; background-color: white;text-align:center;";
            echo '<div style="' . $style . '">';
            echo '<a href="http://vpl.dis.ulpgc.es/">';
            echo 'VPL '. vpl_get_version();
            echo '</a>';
            echo '</div>';
        }
        echo $OUTPUT->footer();
    }

    /**
     * print end of page
     */
    public function print_footer_simple() {
        global $OUTPUT;
        echo $OUTPUT->footer();
    }

    protected static $pagelayoutmap = [
        'executionfiles' => 'admin',
        'executionlimits' => 'admin',
        'executionoptions' => 'admin',
        'local_jail_servers' => 'admin',
        'override' => 'admin',
        'requiredfiles' => 'admin',
        'testcasesfile' => 'admin',
        'listsimilarity' => 'report',
        'activityworkinggraph' => 'report',
        'checkjailservers' => 'report',
        'previoussubmissionslist' => 'report',
        'submissionslist' => 'report',
        'downloadsubmission' => 'popup',
        'gradesubmission' => 'admin',
        'diff' => 'popup',
        'index' => 'incourse',
    ];

    public function get_pagelayout($action) {
        if (isset(self::$pagelayoutmap[$action])) {
            return self::$pagelayoutmap[$action];
        }
        return 'standard';
    }
    /**
     * prepare_page initialy
     */
    public function prepare_page($url = false, $parms = []) {
        global $PAGE, $CFG;
        // Next line resolve problem of classic theme not showing setting menu.
        require_login($this->get_course(), false, $this->get_course_module());
        $action = basename($url, '.php');
        if ($url) {
            $PAGE->set_url( new moodle_url('/mod/vpl/' . $url, $parms) );
            $PAGE->set_pagetype('mod-vpl-' . $action);
        }
        $PAGE->set_pagelayout(self::get_pagelayout($action));
        if ( $CFG->version >= 2022041900) { // Checks is running on Moodle 4.
            $PAGE->activityheader->set_description('');
            $PAGE->activityheader->set_hidecompletion($url != 'view.php');
            if ($url == 'view.php') {
                $PAGE->activityheader->set_title('');
            }
        }
    }

    protected static $headerisout = false;
    public static function header_is_out() {
        return self::$headerisout;
    }

    /**
     * print header
     *
     * @param $info string title and last nav option
     */
    public function print_header($info = '') {
        global $PAGE, $OUTPUT;
        if (self::$headerisout) {
            return;
        }
        $tittle = $this->get_printable_name();
        if ($info) {
            $tittle .= ' ' . $info;
        }
        $PAGE->set_title( $this->get_course()->fullname . ' ' . $tittle );
        $PAGE->set_heading( $this->get_course()->fullname );
        if ( $this->use_seb() && ! $this->has_capability(VPL_GRADE_CAPABILITY)) {
            $PAGE->set_popup_notification_allowed(false);
            $PAGE->set_pagelayout('secure');
        }
        echo $OUTPUT->header();
        self::$headerisout = true;
        foreach ($this->errors as $errormessage) {
            vpl_notice($errormessage, 'error');
        }
    }
    public function print_header_simple($info = '') {
        global $OUTPUT, $PAGE;
        if (self::$headerisout) {
            return;
        }
        $tittle = $this->get_printable_name();
        if ($info) {
            $tittle .= ' ' . $info;
        }
        $PAGE->set_title( $this->get_course()->fullname . ' ' . $tittle );
        if ( $this->use_seb() && ! $this->has_capability(VPL_GRADE_CAPABILITY)) {
            $PAGE->set_popup_notification_allowed(false);
            $PAGE->set_pagelayout('secure');
        }
        echo $OUTPUT->header();
        self::$headerisout = true;
        foreach ($this->errors as $errormessage) {
            vpl_notice($errormessage, 'error');
        }
    }
    /**
     * Print heading action with help
     *
     * @param $action string
     *            base text and help
     */
    public function print_heading_with_help($action) {
        global $OUTPUT;
        $title = get_string( $action, VPL ) . ': ' . $this->get_printable_name();
        echo $OUTPUT->heading_with_help( vpl_get_awesome_icon($action) . $title, $action, 'vpl');
        self::$headerisout = true;
    }

    /**
     * Create tabs to view_description/submit/view_submission/edit
     *
     * @param string $path to get the active tab
     *
     */
    public function print_view_tabs($path) {
        // TODO refactor using functions.
        global $USER, $DB;
        $active = basename( $path );
        $cmid = $this->cm->id;
        $userid = optional_param( 'userid', null, PARAM_INT );
        $copy = optional_param( 'privatecopy', false, PARAM_INT );
        $viewer = $this->has_capability( VPL_VIEW_CAPABILITY );
        $submiter = $this->has_capability( VPL_SUBMIT_CAPABILITY );
        $similarity = $this->has_capability( VPL_SIMILARITY_CAPABILITY );
        $grader = $this->has_capability( VPL_GRADE_CAPABILITY );
        $manager = $this->has_capability( VPL_MANAGE_CAPABILITY );
        $example = $this->instance->example;
        if (! $userid || ! $grader || $copy) {
            $userid = $USER->id;
        }
        $level2 = $grader || $manager || $similarity;

        $maintabs = [];
        $tabs = [];
        $href = vpl_mod_href( 'view.php', 'id', $cmid, 'userid', $userid );
        $viewtab = vpl_create_tabobject('view.php', $href, 'description' );
        if ($level2) {
            if ($viewer) {
                $maintabs[] = $viewtab;
            }
            $href = vpl_mod_href( 'views/submissionslist.php', 'id', $cmid );
            $maintabs[] = vpl_create_tabobject( 'submissionslist.php', $href, 'submissionslist' );
            // Similarity.
            if ($similarity) {
                if ($active == 'listwatermark.php' || $active == 'similarity_form.php' || $active == 'listsimilarity.php') {
                    $tabname = $active;
                } else {
                    $tabname = 'similarity';
                }
                $href = vpl_mod_href( 'similarity/similarity_form.php', 'id', $cmid );
                $maintabs[] = vpl_create_tabobject( $tabname, $href, 'similarity' );
            }
            // Test.
            if ($grader || $manager) {
                if ($active == 'submission.php' || $active == 'edit.php'
                        || $active == 'submissionview.php' || $active == 'gradesubmission.php'
                        || $active == 'previoussubmissionslist.php') {
                            $tabname = $active;
                } else {
                    $tabname = 'test';
                }
                $href = vpl_mod_href( 'forms/submissionview.php', 'id', $cmid, 'userid', $userid );
                if ($userid == $USER->id) {
                    $maintabs[] = vpl_create_tabobject( $tabname, $href, 'test' );
                } else {
                    $user = self::get_db_record( 'user', $userid);
                    if ($this->is_group_activity()) {
                        $text = vpl_get_awesome_icon('group') . ' ';
                    } else {
                        $text = vpl_get_awesome_icon('user') . ' ';
                    }
                    $text .= $this->fullname( $user, false );
                    $maintabs[] = new tabobject( $tabname, $href, $text, $text );
                }
            }
        }
        switch ($active) {
            case 'view.php' :
                if ($level2) {
                    // TODO replace by $OUTPUT->tabtree.
                    print_tabs(
                             [
                                    $maintabs,
                                    $tabs,
                             ], $active );
                    return;
                }
            case 'submission.php' :
            case 'edit.php' :
            case 'submissionview.php' :
            case 'gradesubmission.php' :
            case 'previoussubmissionslist.php' :
                require_once('vpl_submission.class.php');
                $subinstance = $this->last_user_submission( $userid );
                if ($viewer && ! $level2) {
                    $tabs[] = $viewtab;
                }
                if ($manager || ($grader && $USER->id == $userid)
                    || (! $grader && $submiter && $this->is_submit_able()
                    && ! $this->instance->restrictededitor && ! $example)) {
                    $href = vpl_mod_href( 'forms/submission.php', 'id', $cmid, 'userid', $userid );
                    $tabs[] = vpl_create_tabobject( 'submission.php', $href, 'submission' );
                }
                if ($manager || ($grader && $USER->id == $userid)
                    || (! $grader && $submiter && $this->is_submit_able())) {
                    $href = vpl_mod_href( 'forms/edit.php', 'id', $cmid, 'userid', $userid );
                    $stredit = 'edit';
                    if ($example && $this->instance->run) {
                        $stredit = 'run';
                    }
                    $tabs[] = vpl_create_tabobject( 'edit.php', $href, $stredit);
                }
                if (! $example) {
                    $href = vpl_mod_href( 'forms/submissionview.php', 'id', $cmid, 'userid', $userid );
                    $tabs[] = vpl_create_tabobject( 'submissionview.php', $href, 'submissionview');
                    if ($grader && $this->get_grade() != 0 && $subinstance
                        && ($subinstance->dategraded == 0
                            || $subinstance->grader == $USER->id
                            || $subinstance->grader == 0)) {
                        $href = vpl_mod_href( 'forms/gradesubmission.php', 'id', $cmid, 'userid', $userid );
                        $text = get_string('grade');
                        $tabs[] = vpl_create_tabobject( 'gradesubmission.php', $href, 'grade', 'core' );
                    }
                    if ($subinstance && ($grader || $similarity)) {
                        $href = vpl_mod_href( 'views/previoussubmissionslist.php', 'id', $cmid, 'userid', $userid );
                        $tabs[] = vpl_create_tabobject( 'previoussubmissionslist.php', $href, 'previoussubmissionslist' );
                    }
                }
                // Show user picture if this activity require password.
                if (! isset( $user ) && $this->instance->password > '') {
                    $user = self::get_db_record('user', $userid);
                }
                if (isset( $user )) {
                    echo '<div style="position:absolute; right:50px; z-index:50;">';
                    echo $this->user_picture( $user );
                    echo '</div>';
                }
                if ($level2) {
                    print_tabs(
                             [
                                    $maintabs,
                                    $tabs,
                             ], $active );
                    return;
                } else {
                    print_tabs( [
                            $tabs,
                    ], $active );
                    return;
                }

                break;
            case 'submissionslist.php' :
                print_tabs( [
                        $maintabs,
                ], $active );
                return;
            case 'listwatermark.php' :
            case 'similarity_form.php' :
            case 'listsimilarity.php' :
                if ($similarity) {
                    $href = vpl_mod_href( 'similarity/similarity_form.php', 'id', $cmid );
                    $tabs[] = vpl_create_tabobject( 'similarity_form.php', $href, 'similarity' );
                    if ($active == 'listsimilarity.php') {
                        $tabs[] = vpl_create_tabobject( 'listsimilarity.php', '', 'listsimilarity' );
                    }
                    $plugincfg = get_config('mod_vpl');
                    $watermark = isset( $plugincfg->use_watermarks ) && $plugincfg->use_watermarks;
                    if ($watermark) {
                        $href = vpl_mod_href( 'similarity/listwatermark.php', 'id', $cmid );
                        $tabs[] = vpl_create_tabobject( 'listwatermark.php', $href, 'listwatermarks' );
                    }
                }
                print_tabs( [
                        $maintabs,
                        $tabs,
                ], $active );
                break;
        }
    }

    /**
     * Show vpl name
     */
    public function print_name() {
        global $OUTPUT, $CFG;
        if ( $CFG->version < 2022041900) {
            echo $OUTPUT->heading($this->get_printable_name());
        }
    }

    /**
     * Return a VPL setting.
     *
     * @param string $str setting string i18n to get descriptoin
     * @param string $value setting value, default null
     * @param string $raw if set use raw instead off $str string, default false
     * @param string $comp component for i18n, default mod_vpl
     * @return string HTML
     */
    public function str_restriction($str, $value = null, $raw = false, $comp = 'mod_vpl') {
        $html = '<b>';
        if ($raw) {
            $html .= s( $str );
        } else {
            $html .= s( get_string( $str, $comp ) );
        }
        $html .= '</b>: ';
        if ($value === null) {
            $value = $this->instance->$str;
        }
        $html .= $value;
        return $html;
    }

    /**
     * Return a VPL setting with icon
     * @param string $str setting string i18n to get descriptoin
     * @param string $value setting value, default null
     * @param boolean $raw if true $str if raw string, default false
     * @param boolean $newline if true print new line after setting, default false
     */
    public function str_restriction_with_icon($str, $value = null, $raw = false, $newline = true, $comp = 'mod_vpl') {
        $html = vpl_get_awesome_icon($str);
        $html .= $this->str_restriction($str, $value, $raw, $comp);
        if ( $newline ) {
            $html .= '<br>';
        } else {
            $html .= '. ';
        }
        return $html;
    }

    /**
     * Return vpl submission period.
     * @param int $userid (optional) Show for given user, current user if null.
     * @return string HTML
     */
    public function str_submission_period($userid = null) {
        $html = '';
        $startdate = $this->get_effective_setting('startdate', $userid);
        if ($startdate) {
            $html .= $this->str_restriction_with_icon( 'startdate', userdate( $startdate ) );
        }
        $duedate = $this->get_effective_setting('duedate', $userid);
        if ($duedate) {
            $html .= $this->str_restriction_with_icon( 'duedate', userdate( $duedate ) );
        }
        return $html;
    }

    /**
     * Show vpl submission period.
     * @param int $userid (optional) Show for given user, current user if null.
     */
    public function print_submission_period($userid = null) {
        echo $this->str_submission_period($userid);
    }

    /**
     * Return vpl submission restriction.
     * @param int $userid (optional) Show for given user, current user if null.
     * @return string HTML
     */
    public function str_submission_restriction($userid = null) {
        global $CFG, $USER;
        $html = '';
        $filegroup = $this->get_required_fgm();
        $files = $filegroup->getfilelist();
        if (count( $files )) {
            $text = '';
            $needcomma = false;
            foreach ($files as $file) {
                if ($needcomma) {
                    $text .= ', ';
                }
                $text .= s( $file );
                $needcomma = true;
            }
            $link = ' (' . vpl_get_awesome_icon('download');
            $link .= '<a href="';
            $link .= vpl_mod_href( 'views/downloadrequiredfiles.php', 'id', $this->get_course_module()->id );
            $link .= '">';
            $link .= get_string( 'download', VPL );
            $link .= '</a>)';
            $html .= $this->str_restriction_with_icon( 'requestedfiles', $text . $link );
        }
        $instance = $this->get_instance();
        if (count( $files ) != $instance->maxfiles) {
            $html .= $this->str_restriction_with_icon( 'maxfiles' );
        }
        if ($instance->maxfilesize) {
            $mfs = $this->get_maxfilesize();
            $html .= $this->str_restriction_with_icon( 'maxfilesize', vpl_conv_size_to_string( $mfs ) );
        }
        $worktype = $instance->worktype;
        $values = [
                0 => vpl_get_awesome_icon('user'). ' ' . get_string( 'individualwork', VPL ),
                1 => vpl_get_awesome_icon('group'). ' ' .get_string( 'groupwork', VPL ),
        ];
        if ($worktype) {
            $html .= $this->str_restriction_with_icon( 'worktype', $values[$worktype] . ' ' . $this->fullname( $USER ) );
        } else {
            $html .= $this->str_restriction_with_icon( 'worktype', $values[$worktype] );
        }
        $stryes = get_string( 'yes' );
        $strno = get_string( 'no' );
        if ($instance->example) {
            $html .= $this->str_restriction_with_icon( 'isexample', $stryes );
        }
        $grader = $this->has_capability( VPL_GRADE_CAPABILITY );
        $strgradessettings = get_string('gradessettings', 'core_grades');
        if ($grader) {
            require_once($CFG->libdir . '/gradelib.php');
            $html .= vpl_get_awesome_icon('grade');
            if ($gie = $this->get_grade_info()) {
                if ($gie->scaleid == 0) {
                    $info = get_string('grademax', 'core_grades')
                            . ': ' . format_float($gie->grademax, 5, true, true);
                    $info .= $gie->hidden ? (' <b>' . vpl_get_awesome_icon('hidden')
                                           . get_string( 'hidden', 'core_grades' ) . '</b>') : '';
                    $info .= $gie->locked ? (' <b>' . vpl_get_awesome_icon('locked')
                                           . get_string( 'locked', 'core_grades' ) . '</b>') : '';
                } else {
                    $info = get_string( 'typescale', 'core_grades' );
                }
                $html .= $this->str_restriction_with_icon( $strgradessettings, $info, true );
            } else {
                $html .= $this->str_restriction_with_icon( $strgradessettings, get_string('nograde'), true );
            }
        }
        $html .= $this->str_gradereduction($userid);
        if ($grader) {
            $password = trim($this->get_effective_setting('password'));
            if ($password) {
                $html .= $this->str_restriction_with_icon( 'password', $stryes, false, false, 'moodle');
                $infohs = new mod_vpl\util\hide_show();
                $html .= $infohs->generate();
                $html .= $infohs->content_in_span(s($password));
                $html .= "<br>\n";
            }
            if (trim( $instance->requirednet ) > '') {
                $html .= $this->str_restriction_with_icon( 'requirednet', s( $instance->requirednet ));
            }
            if ( $instance->sebrequired > 0) {
                $html .= $this->str_restriction_with_icon('sebrequired', $stryes );
            }
            if (trim( $instance->sebkeys ) > '') {
                $html .= $this->str_restriction_with_icon('sebkeys', $stryes, false, false);
                $infohs = new mod_vpl\util\hide_show();
                $html .= $infohs->generate();
                $html .= $infohs->content_in_div(nl2br(s($instance->sebkeys)));
                $html .= "<br>\n";
            }
            if ($instance->restrictededitor) {
                $html .= $this->str_restriction_with_icon( 'restrictededitor', $stryes );
            }
            if (! $this->get_course_module()->visible) {
                $html .= vpl_get_awesome_icon('hidden') . ' ';
                $html .= $this->str_restriction_with_icon( get_string( 'visible' ), $strno, true );
            }
            if ($instance->basedon) {
                $basedon = new mod_vpl( null, $instance->basedon );
                $link = '<a href="';
                $link .= vpl_mod_href( 'view.php', 'id', $basedon->cm->id );
                $link .= '">';
                $link .= $basedon->get_printable_name();
                $link .= '</a>';
                $html .= $this->str_restriction_with_icon( 'basedon', $link );
            }
            $noyes = [
                    $strno,
                    $stryes,
            ];
            $html .= $this->str_restriction_with_icon( 'run', $noyes[$instance->run], false, false );
            if ($instance->runscript) {
                $html .= $this->str_restriction_with_icon( 'runscript', strtoupper($instance->runscript), false, false );
            }
            if ($instance->debug) {
                $html .= $this->str_restriction_with_icon( 'debug', $noyes[1], false, false );
            }
            if ($instance->debugscript) {
                $html .= $this->str_restriction_with_icon( 'debugscript', strtoupper($instance->debugscript), false, false );
            }
            $html .= $this->str_restriction_with_icon( 'evaluate', $noyes[$instance->evaluate], false,
                    ! ($instance->evaluate && $instance->evaluateonsubmission) );
            if ($instance->evaluate && $instance->evaluateonsubmission) {
                $html .= $this->str_restriction_with_icon( 'evaluateonsubmission', $noyes[1] );
            }
            if ($instance->automaticgrading) {
                $html .= $this->str_restriction_with_icon( 'automaticgrading', $noyes[1], false, false );
            }
            if ($instance->maxexetime) {
                $html .= $this->str_restriction_with_icon( 'maxexetime', $instance->maxexetime . ' s', false, false );
            }
            if ($instance->maxexememory) {
                $size = vpl_conv_size_to_string($instance->maxexememory);
                $html .= $this->str_restriction_with_icon('maxexememory', $size, false, false);
            }
            if ($instance->maxexefilesize) {
                $size = vpl_conv_size_to_string($instance->maxexefilesize);
                $html .= $this->str_restriction_with_icon('maxexefilesize', $size, false, false);
            }
            if ($instance->maxexeprocesses) {
                $html .= $this->str_restriction_with_icon( 'maxexeprocesses', null, false, false );
            }
        }
        return $html;
    }

    /**
     * Show vpl submission restriction
     * @param int $userid (optional) Show for given user, current user if null.
     */
    public function print_submission_restriction($userid = null) {
        echo $this->str_submission_restriction($userid);
    }

    /**
     * Show short description
     */
    public function print_shordescription() {
        global $OUTPUT;
        if ($this->instance->shortdescription) {
            echo $OUTPUT->box_start();
            echo format_text( $this->instance->shortdescription, FORMAT_PLAIN );
            echo $OUTPUT->box_end();
        }
    }

    /**
     * Return grade reduction in HTML format
     * @param int $userid (optional) Print for given user, current user if null.
     * @return string HTML
     */
    public function str_gradereduction($userid = null) {
        $html = '';
        $reductionbyevaluation = $this->get_effective_setting('reductionbyevaluation', $userid);
        if ($reductionbyevaluation > 0) {
            $html .= $this->str_restriction( 'reductionbyevaluation', $reductionbyevaluation);
            $freeevaluations = $this->get_effective_setting('freeevaluations', $userid);
            if ( $freeevaluations > 0) {
                $html .= ' ' . $this->str_restriction( 'freeevaluations', $freeevaluations);
            }
            $html .= $html . '<br>';
        }
        return $html;
    }
    /**
     * Print grade reduction
     * @param int $userid (optional) Print for given user, current user if null.
     */
    public function print_gradereduction($userid = null) {
        echo $this->str_gradereduction($userid);
    }

    /**
     * Show full description
     */
    public function print_fulldescription() {
        global $OUTPUT;
        $full = $this->get_fulldescription_with_basedon();
        if ($full > '') {
            echo $OUTPUT->box( $full );
        } else {
            $this->print_shordescription();
        }
    }

    /**
     * Checks if the chain of basedon activities is ok.
     * @return true/false
     */
    public function basedon_is_ok() {
        global $DB;
        $checked = 0;
        $instance = $this->get_instance();
        while ($instance->basedon) {
            $instance = self::get_db_record(VPL, $instance->basedon);
            if (!$instance) {
                return false;
            }
            if ($checked++ > 100) { // Possible recursive definition.
                return false;
            }
        }
        return true;
    }

    /**
     * Gets html for all variations defined in activity
     * @return string
     */
    public function get_all_variations_html() {
        global $OUTPUT;
        global $DB;
        $html = '';
        $variations = $DB->get_records(VPL_VARIATIONS, ['vpl' => $this->instance->id]);
        if (count($variations) > 0) {
            $div = new mod_vpl\util\hide_show();
            $html .= '<br>';
            $html .= vpl_get_awesome_icon('variations');
            $html .= ' <b>' . get_string( 'variations', VPL ) . $div->generate() . '</b><br>';
            $html .= $div->begin_div();
            if (! $this->instance->usevariations) {
                $html .= '<b>' . get_string( 'variations_unused', VPL ) . '</b><br>';
            }
            if ($this->instance->variationtitle) {
                $html .= '<b>' . get_string( 'variationtitle', VPL ) . ': ' . s( $this->instance->variationtitle ) . '</b><br>';
            }
            $number = 1;
            foreach ($variations as $variation) {
                $html .= '<b>' . get_string( 'variation_n', VPL, $number ) . '</b>: ';
                $html .= s($variation->identification) . '<br>';
                $html .= $OUTPUT->box( $variation->description );
                $number ++;
            }
            $html .= $div->end_div();
        }
        return $html;
    }

    /**
     * Get user variation. Assign one if needed
     */
    public function get_variation($userid) {
        global $DB;
        if ($this->is_group_activity()) { // Variations not compatible with a group activity.
            return false;
        }
        $varassigned = $DB->get_record(
                VPL_ASSIGNED_VARIATIONS,
                ['vpl' => $this->instance->id, 'userid' => $userid]
            );
        if ($varassigned === false) { // Variation not assigned.
            $variations = $DB->get_records( VPL_VARIATIONS, ['vpl' => $this->instance->id]);
            if (count( $variations ) == 0) { // No variation set.
                return false;
            }
            // Select a random variation.
            shuffle( $variations );
            $variation = $variations[0];
            $assign = new stdClass();
            $assign->vpl = $this->instance->id;
            $assign->variation = $variation->id;
            $assign->userid = $userid;
            if (! $DB->insert_record( VPL_ASSIGNED_VARIATIONS, $assign )) {
                throw new moodle_exception('invalidcoursemodule');
            }
            \mod_vpl\event\variation_assigned::logvpl( $this, $variation->id, $userid);
        } else {
            $variation = self::get_db_record(VPL_VARIATIONS, $varassigned->variation);
            if ($variation == false || $variation->vpl != $varassigned->vpl) { // Checks consistency.
                $DB->delete_records(
                    VPL_ASSIGNED_VARIATIONS,
                     [
                        'id' => $varassigned->id,
                    ] );
                throw new moodle_exception('invalidcoursemodule');
            }
        }
        return $variation;
    }

    /**
     * Gets variations in html if actived and defined
     * @param integer $userid User Id default value 0
     * @param array $already array of based on visited, default empty
     * @return string
     */
    public function get_variation_html($userid = 0, $already = []) {
        global $OUTPUT;
        $html = '';
        if (isset( $already[$this->instance->id] )) { // Avoid infinite recursion.
            return;
        }
        $already[$this->instance->id] = true; // Mark as visited.
        if ($this->instance->basedon) { // Show recursive varaitions.
            $basevpl = new mod_vpl( false, $this->instance->basedon );
            $html .= $basevpl->get_variation_html( $userid, $already );
        }
        // If user with grade or manage capability print all variations.
        if ($this->has_capability( VPL_GRADE_CAPABILITY, $userid ) || $this->has_capability( VPL_MANAGE_CAPABILITY,
                $userid )) {
            $html .= $this->get_all_variations_html();
        }
        // Show user variation if active.
        if ($this->instance->usevariations) { // Variations actived.
            $variation = $this->get_variation( $userid );
            if ($variation !== false) { // Variations defined.
                if ($this->instance->variationtitle > '') {
                    $html .= '<b>' . format_text( $this->instance->variationtitle, FORMAT_HTML ) . '</b><br>';
                }
                $html .= $OUTPUT->box( $variation->description );
            }
        }
        return $html;
    }

    /**
     * Print variations if actived and defined
     * @param integer $userid User Id default value 0
     */
    public function print_variation($userid = 0) {
        echo $this->get_variation_html($userid);
    }

    /**
     * return an array with variations for this user
     */
    public function get_variation_identification($userid = 0, &$already = []) {
        if (! ($this->instance->usevariations) || isset( $already[$this->instance->id] )) { // Avoid infinite recursion.
            return [];
        }
        $already[$this->instance->id] = true;
        if ($this->instance->basedon) {
            $basevpl = new mod_vpl( false, $this->instance->basedon );
            $ret = $basevpl->get_variation_identification( $userid, $already );
        } else {
            $ret = [];
        }
        $variation = $this->get_variation( $userid );
        if ($variation !== false) {
            $ret[] = $variation->identification;
        }
        return $ret;
    }

    /**
     * Cached settings of overrides, for get_effective_setting().
     * @var array $overridensettings Array[ cmid => Array[ userid => {settings} ] ]
     */
    protected static $overridensettings = [];
    /**
     * Return effective setting for this vpl instance (taking overrides into account).
     * @param string $setting Setting name (field of database record).
     * @param int $userid (optional) Get for given user, current user if null.
     * @return mixed The effective setting, as a database field.
     */
    public function get_effective_setting($setting, $userid = null) {
        global $USER, $DB;
        $fields = ['startdate', 'duedate', 'reductionbyevaluation', 'freeevaluations', 'password'];
        if (!in_array($setting, $fields)) {
            return $this->instance->$setting;
        }
        if (!$userid) {
            $userid = $USER->id;
        }
        if (!isset(self::$overridensettings[$this->cm->id])) {
            self::$overridensettings[$this->cm->id] = [];
        }
        if (!isset(self::$overridensettings[$this->cm->id][$userid])) {
            self::$overridensettings[$this->cm->id][$userid] = new stdClass();

            $sql = 'SELECT ao.id as aoid, ao.override, ao.userid, ao.groupid, o.*
                        FROM {vpl_assigned_overrides} ao
                        JOIN {vpl_overrides} o ON ao.override = o.id
                        WHERE o.vpl = :vplid AND (ao.userid = :userid OR ao.groupid IS NOT NULL)
                        ORDER BY ao.override DESC';
            $overrides = $DB->get_records_sql($sql, ['vplid' => $this->instance->id, 'userid' => $userid]);

            foreach ($overrides as $override) {
                if (!empty($override->userid)) {
                    // Found record for user.
                    foreach ($fields as $field) {
                        self::$overridensettings[$this->cm->id][$userid]->$field = $override->$field;
                    }
                    break; // User overrides take priority, do not search further.
                }

                if (groups_is_member($override->groupid, $userid)) {
                    foreach ($fields as $field) {
                        self::$overridensettings[$this->cm->id][$userid]->$field = $override->$field;
                    }
                }
            }
        }
        if (isset(self::$overridensettings[$this->cm->id][$userid]->$setting) &&
                self::$overridensettings[$this->cm->id][$userid]->$setting !== null) {
            return self::$overridensettings[$this->cm->id][$userid]->$setting;
        } else {
            return $this->instance->$setting;
        }
    }

    /**
     * Update calendar events for duedate overrides.
     * @param stdClass $override The override being created / updated / deleted.
     *          It should contain joint data from vpl_overrides and vpl_assigned_overrides tables.
     * @param stdClass $oldoverride The old override data (in case of an update).
     * @param boolean $delete If true, simply delete all related events.
     */
    public function update_override_calendar_events($override, $oldoverride = null, $delete = false) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $targets = [
                'userid' => CALENDAR_EVENT_USER_OVERRIDE_PRIORITY,
                'groupid' => $override->id,
        ];
        foreach ($targets as $target => $priority) { // Process once for users and once for groups.
            $params = [
                    'modulename' => VPL,
                    'instance' => $this->instance->id,
                    'priority' => $priority,
            ];
            if ($oldoverride !== null && !empty($oldoverride->{$target . 's'})) {
                $oldtargets = array_fill_keys(explode(',', $oldoverride->{$target . 's'}), false);
            } else {
                $oldtargets = [];
            }
            if (!empty($override->{$target . 's'})) {
                foreach (explode(',', $override->{$target . 's'}) as $userorgroupid) { // Loop over users or groups.
                    $params[$target] = $userorgroupid;
                    $currenteventid = $DB->get_field( 'event', 'id', $params ); // Get current calendar event.
                    if (isset($override->duedate) && !$delete) {
                        if ($target == 'userid') {
                            $userorgroupname = fullname(self::get_db_record( 'user', $userorgroupid));
                            $strname = 'overridefor';
                        } else {
                            $userorgroupname = groups_get_group($userorgroupid)->name;
                            $strname = 'overrideforgroup';
                        }
                        $newevent = vpl_create_event($this->instance, $this->instance->id);
                        $newevent->name = get_string($strname, VPL, [
                                'base' => $newevent->name,
                                'for' => $userorgroupname,
                        ]);
                        if ($target == 'userid') {
                            // User overrides events do not show correctly if courseid is non zero.
                            $newevent->courseid = 0;
                        }
                        $newevent->timestart = $override->duedate;
                        $newevent->timesort = $override->duedate;
                        $newevent->{$target} = $userorgroupid;
                        $newevent->priority = $priority;
                        if ($currenteventid === false) {
                            // No event exist for current user or group, create a new one.
                            calendar_event::create( $newevent );
                        } else {
                            // An event already exists, update it.
                            calendar_event::load( $currenteventid )->update( $newevent );
                        }
                    } else {
                        if ($currenteventid !== false) {
                            calendar_event::load( $currenteventid )->delete();
                        }
                    }
                    // This user or group is in newly processed data (or has already been removed).
                    $oldtargets[$userorgroupid] = true;
                }
            }
            // Discard events related to users or groups removed from override.
            foreach ($oldtargets as $oldtarget => $tokeep) {
                if (!$tokeep) {
                    $params[$target] = $oldtarget;
                    $eventid = $DB->get_field( 'event', 'id', $params );
                    if ($eventid !== false) {
                        calendar_event::load( $eventid )->delete();
                    }
                }
            }
        }
    }
}
