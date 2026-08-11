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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/vpl_submission.class.php');

use mod_vpl\util\file_group;
use mod_vpl\util\file_group_execution;
use mod_vpl\util\activity_modes;

/**
 * Class mod_vpl
 *
 * This class to manage VPL activities.
 *
 * @package mod_vpl
 */
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
     * An internal array of notices describing to show
     *
     * @var string[][]
     */
    protected $notices = ['error' => [], 'warning' => [], 'success' => [], 'info' => []];

    /**
     * $script of the current page, used to set some page options
     *
     * @var string
     */
    protected $script = '';

    /**
     * An internal cache for grade information.
     *
     * @var object|null
     */
    protected $gradeinfo = null;

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
            $instance = $DB->get_record($table, ["id" => $id]);
            if ($instance !== false && $table == VPL) {
                self::set_null_field_empty($instance);
            }
            self::$instancescache[$table][$id] = $instance;
        }
        return self::$instancescache[$table][$id];
    }

    /**
     * Remove one record, table or all (*) records from cache
     *
     * @param string $table Table name or '*' for all
     * @param int $id The id of the register of the table to remove from cache
     * @return void
     */
    public static function reset_db_cache($table = '*', $id = -1) {
        if ($table == '*') {
            self::$instancescache = [];
        } else {
            if ($id == -1) {
                self::$instancescache[$table] = [];
            } else if (isset(self::$instancescache[$table][$id])) {
                unset(self::$instancescache[$table][$id]);
            }
        }
    }

    /**
     * Set string field with null to empty string.
     *
     * @param object $vplinstace
     * @return void
     */
    public static function set_null_field_empty($vplinstace) {
        $fields = [
            'shortdescription',
            'intro',
            'requirednet',
            'password',
            'variationtitle',
            'jailservers',
            'reductionbyevaluation',
            'sebkeys',
            'runscript',
            'debugscript',
        ];
        foreach ($fields as $field) {
            if (property_exists($vplinstace, $field) && $vplinstace->$field == null) {
                $vplinstace->$field = '';
            }
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
                throw new moodle_exception('error:inconsistency', 'mod_vpl', '', VPL . $a);
            }
            $this->course = self::get_db_record("course", $this->instance->course);
            if (!$this->course) {
                throw new moodle_exception('invalidcourseid');
            }
            $this->cm = get_coursemodule_from_instance(VPL, $this->instance->id, $this->course->id);
            if (! ($this->cm)) {
                // Don't stop on error. This let delete a corrupted course.
                $this->add_notice(get_string('invalidcoursemodule', 'error'), 'error');
            } else {
                $this->instance->cmidnumber = $this->cm->idnumber;
            }
        }
        if (! $this->basedon_is_ok()) {
            // Don't stop on error. This allow to repare based on chain.
            if (! self::get_db_record(VPL, $this->instance->basedon)) {
                $this->add_notice(get_string('basedon_deleted', VPL), 'error');
                $this->instance->basedon = 0; // Avoid missing VPL errors.
            } else {
                $this->add_notice(get_string('basedon_chain_broken', VPL), 'error');
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
     * Set the required files
     * @param array $files array of file names to set
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
            $this->requiredfgm = new file_group($this->get_required_files_directory(), $this->instance->maxfiles);
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
            $this->executionfgm = new file_group_execution($this->get_execution_files_directory());
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
        if (! empty($CFG->enablegroupings) && ($this->cm->groupingid > 0)) {
            $grouping = groups_get_grouping($this->cm->groupingid);
            if ($grouping !== false) {
                $ret .= ' (' . $grouping->name . ')';
            }
        }
        if (count($this->notices['error'])) {
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
            return format_module_intro(VPL, $this->get_instance(), $this->get_course_module()->id);
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
        if (! $this->is_visible()) {
            return '';
        }
        $ret = '';
        if ($this->instance->basedon) { // Show recursive variations.
            $basevpl = new mod_vpl(false, $this->instance->basedon);
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
     * Get password to access the activity, taken into account exceptions.
     * If $userid is provided, get the password for that user, if not get the password for the current user.
     *
     * @param int|null $userid optional user id to get the password for, if null get the password for the current user
     * @return string password
     */
    protected function get_password($userid = null) {
        return trim($this->get_effective_setting('password', $userid));
    }

    /**
     * Get password md5
     */
    protected function get_password_md5() {
        return md5($this->instance->id . sesskey());
    }

    /**
     * Check if pass password restriction
     * @param string $passset Password to check
     * @return bool true if passed
     */
    public function pass_password_check($passset = '') {
        $password = $this->get_password();
        if ($password > '' && ! $this->is_teacher()) {
            global $SESSION;
            $passwordmd5 = $this->get_password_md5();
            $passvar = 'vpl_password_' . $this->instance->id;
            $passattempt = 'vpl_password_attempt' . $this->instance->id;
            if (isset($SESSION->$passvar) && $SESSION->$passvar == $passwordmd5) {
                return true;
            }
            if ($passset == '') {
                $passset = optional_param('password', '', PARAM_TEXT);
            }
            if ($passset > '') {
                if ($passset == $password) {
                    $SESSION->$passvar = $passwordmd5;
                    unset($SESSION->$passattempt);
                    return true;
                }
                if (isset($SESSION->$passattempt)) {
                    $SESSION->$passattempt++;
                } else {
                    $SESSION->$passattempt = 1;
                }
                // Wait vpl_password_attempt seconds to limit force brute crack.
                sleep($SESSION->$passattempt);
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
        if ($this->get_password() == '' || $this->is_teacher()) {
            return;
        }
        if (! $this->pass_password_check()) {
            if (constant('AJAX_SCRIPT')) {
                throw new Exception(get_string('requiredpassword', VPL));
            }
            require_once('forms/password_form.php');
            $this->print_header();
            $posturl = $_SERVER['SCRIPT_NAME'] . "?id={$this->cm->id}";
            $mform = new mod_vpl_password_form($posturl, $this);
            $passattempt = 'vpl_password_attempt' . $this->get_instance()->id;
            if (isset($SESSION->$passattempt)) {
                vpl_notice(
                    get_string('attemptnumber', VPL, $SESSION->$passattempt),
                    'warning'
                );
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
        if ($this->instance->requirednet > '' && ! $this->is_teacher()) {
            return vpl_check_network($this->instance->requirednet);
        }
        return true;
    }

    /**
     * Check network restriction and show error if not passed
     * @return void
     */
    protected function network_check() {
        if ($this->instance->requirednet == '' || $this->is_teacher()) {
            return;
        }
        if (! $this->pass_network_check()) {
            $str = get_string('opnotallowfromclient', VPL) . ' ' . getremoteaddr();
            if (constant('AJAX_SCRIPT')) {
                throw new Exception($str);
            }
            $this->add_notice($str, 'warning');
            $this->print_header();
            $this->print_footer();
            die();
        }
    }
    /**
     * Return true if the browser is SEB.
     * @return bool
     */
    protected function is_seb_browser() {
        return strpos($_SERVER['HTTP_USER_AGENT'], 'SEB') !== false;
    }
    /**
     * Checks if SEB key is valid
     * @return bool
     */
    protected function is_sebkey_valid() {
        global $FULLME;
        $keys = $this->get_sebkeys();
        if ($keys == '') {
            return true;
        }
        if (isset($_SERVER['HTTP_X_SAFEEXAMBROWSER_REQUESTHASH'])) {
            $key = $_SERVER['HTTP_X_SAFEEXAMBROWSER_REQUESTHASH'];
            foreach (preg_split('/\s+/', $keys) as $testkey) {
                if (hash('sha256', $FULLME . $testkey) === $key) {
                    return true;
                }
            }
        }
        if (isset($_SERVER['HTTP_X_SAFEEXAMBROWSER_CONFIGKEYHASH'])) {
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
     * Return SEB keys.
     * @return string
     */
    public function get_sebkeys() {
        return trim($this->get_instance()->sebkeys);
    }

    /**
     * Checks SEB restrictions returns true if passed.
     *
     * @return bool
     */
    public function pass_seb_check() {
        $inst = $this->get_instance();
        if ($inst->sebrequired > 0) {
            $passbrowser = $this->is_seb_browser();
        } else {
            $passbrowser = true;
        }
        if ($this->get_sebkeys() > '') {
            $passkey = $this->is_sebkey_valid();
        } else {
            $passkey = true;
        }
        return $passbrowser && $passkey;
    }

    /**
     * Checks SEB restrictions and shows error if not passed.
     *
     * @return void
     */
    protected function seb_check() {
        if (! $this->use_seb() || $this->is_teacher()) {
            return;
        }
        if (! $this->pass_seb_check()) {
            sleep(5); // Avoid force brute crack.
            $str = get_string("sebrequired", COMPVPL);
            if ($this->is_seb_browser()) {
                $str .= '<br>' . get_string('sebkeys_bad', COMPVPL);
            } else {
                $str .= '<br>' . get_string('sebrequired_bad', COMPVPL);
            }
            if (constant('AJAX_SCRIPT')) {
                throw new Exception($str);
            }
            $this->add_notice($str, 'warning');
            $this->print_header();
            $this->print_footer();
            die();
        }
    }

    /**
     * Return true if is set to use SEB.
     * @return bool
     */
    protected function use_seb() {
        return $this->get_instance()->sebrequired || $this->get_sebkeys() > '';
    }

    /**
     * Checks all restrictions and shows error if not passed
     * @return void
     */
    public function restrictions_check() {
        // If students read-only mode, do no check.
        if ($this->is_mode(activity_modes::STUDENTSREADONLY)) {
            return;
        }
        $this->network_check();
        $this->seb_check();
        $this->password_check();
        $this->check_first_access();
    }

    /**
     * Check submission restriction
     * @param array $alldata Array of submitted files (name => data)
     * @param string $error Error message to return if not passed
     * @return bool
     *
     */
    public function pass_submission_restriction(&$alldata, &$error) {
        $max = $this->get_maxfilesize();
        $rfn = $this->get_required_fgm();
        $list = $rfn->getFilelist();
        $error = '';
        if (count($alldata) > $this->instance->maxfiles) {
            $error .= get_string('maxfilesexceeded', VPL) . "\n";
        }
        $lr = count($list);
        $i = 0;
        foreach ($alldata as $name => $data) {
            if (strlen($data) > $max) {
                $error .= '"' . s($name) . '" ' . get_string('maxfilesizeexceeded', VPL) . "<br>\n";
            }
            if (! vpl_is_valid_path_name($name)) {
                $error .= '"' . s($name) . '" ' . get_string('incorrect_file_name', VPL) . "<br>\n";
            }
            if ($i < $lr && $list[$i] != $name) {
                $a = new stdClass();
                $a->expected = $list[$i];
                $a->found = $name;
                $error .= s(get_string('unexpected_file_name', VPL, $a)) . "<br>\n";
            }
            $i++;
        }
        return strlen($error) == 0;
    }

    /**
     * Remove readonly files for students in submissions.
     * They are there as information for students but not to be submitted.
     * @param array $files Array of submitted files (name => data)
     */
    public function remove_readonly_files(&$files) {
        $readonly = $this->get_readonly_files();
        foreach ($readonly as $name) {
            if (isset($files[$name])) {
                unset($files[$name]);
            }
        }
    }
    /**
     * Internal checks and adds submission if possible. Removes unneeded submissions.
     *
     * @param mod_vpl $vpl
     * @param int $userid
     * @param array $files submitted files
     * @param string $comments
     * @param string $error Error message
     * @return int|false Submission id or false if error
     */
    public static function internal_add_submission($vpl, $userid, &$files, $comments, &$error) {
        global $USER, $DB;
        if (! $vpl->pass_submission_restriction($files, $error)) {
            return false;
        }
        $group = false;
        if ($vpl->is_group_activity()) {
            $group = $vpl->get_usergroup($userid);
            if ($group === false) {
                $error = get_string('notsaved', VPL) . "\n" . get_string('inconsistentgroup', VPL);
                return false;
            }
        }
        $submittedby = '';
        if ($USER->id != $userid) {
            if ($vpl->is_teacher()) {
                $user = self::get_db_record('user', $USER->id);
                $submittedby = get_string('submittedby', VPL, fullname($user)) . "\n";
                if (strpos($comments, $submittedby) !== false) {
                    $submittedby = '';
                }
            } else {
                $error = get_string('notsaved', VPL);
                return false;
            }
        }
        $vpl->remove_readonly_files($files);
        $lastsub = false;
        if (($lastsubins = $vpl->last_user_submission($userid)) !== false) {
            $lastsub = new mod_vpl_submission($vpl, $lastsubins);
            if ($lastsub->is_equal_to($files, $submittedby . $comments)) {
                return $lastsubins->id;
            }
        }
        // Create submission record.
        $submissiondata = new stdClass();
        $submissiondata->vpl = $vpl->get_instance()->id;
        $submissiondata->userid = $userid;
        if ($group !== false) {
            $submissiondata->groupid = $group->id;
        }
        $submissiondata->datesubmitted = time();
        $submissiondata->comments = $submittedby . $comments;
        if ($lastsubins !== false) {
            $submissiondata->nevaluations = $lastsubins->nevaluations;
            $submissiondata->save_count = $lastsubins->save_count;
            $submissiondata->run_count = $lastsubins->run_count;
            $submissiondata->debug_count = $lastsubins->debug_count;
        } else {
            $submissiondata->nevaluations = 0;
            $submissiondata->save_count = 0;
            $submissiondata->run_count = 0;
            $submissiondata->debug_count = 0;
        }
        $submissiondata->save_count++; // Increment save count.
        $submissionid = $DB->insert_record('vpl_submissions', $submissiondata, true);
        if (! $submissionid) {
            $error = get_string('notsaved', VPL) . "\ninserting vpl_submissions record";
            return false;
        }
        // Save files.
        $submission = new mod_vpl_submission($vpl, $submissionid);
        try {
            $submission->set_submitted_file($files, $lastsub);
        } catch (file_exception $fe) {
            $DB->delete_records(VPL_SUBMISSIONS, ['id' => $submissionid]);
            $error = $fe->getMessage();
            return false;
        }
        $submission->remove_grade();
        // If no submitted by grader, remove near submmissions.
        if ($USER->id == $userid) {
            $vpl->delete_overflow_submissions($userid);
        }
        return $submissionid;
    }
    /**
     * Checks and adds submission if possible. Removes unneeded submissions.
     *
     * @param int $userid
     * @param array $files submitted files
     * @param string $comments
     * @param string $error Error message
     * @return int|false Submission id or false if error
     */
    public function add_submission($userid, &$files, $comments, &$error) {
        global $USER;
        if ($USER->id != $userid) {
            if (! $this->is_teacher()) {
                $error = get_string('notavailable');
                return false;
            }
        }
        $vplid = $this->get_instance()->id;
        $locktype = 'vpl:submission';
        $resource = "$vplid:$userid";
        $funcname = 'mod_vpl::internal_add_submission';
        $parms = [$this, $userid, $files, $comments, $error];
        $result = vpl_call_with_lock($locktype, $resource, $funcname, $parms);
        $error = $parms[4];
        return $result;
    }

    /**
     * Get user submissions, order reverse submission id
     *
     * @param int $userid the user id to retrieve submissions
     * @param bool $groupifga if group activity get group submissions. default true
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
        return $DB->get_records_select('vpl_submissions', $select, $parms, 'id DESC');
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
        $query = <<<SQL
        SELECT s.$idfield, s.vpl, s.id, $fields FROM {vpl_submissions} s
            JOIN
                (SELECT max(id) as maxid, vpl, $idfield FROM {vpl_submissions}
                    WHERE vpl=?
                    GROUP BY $idfield, vpl) as ls
            ON s.vpl = ls.vpl AND s.$idfield = ls.$idfield AND s.id = ls.maxid;
        SQL;
        return $DB->get_records_sql($query, [ $id ]);
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
        return $DB->get_records_sql($query, [ $id ]);
    }
    /**
     * Get number of user submissions
     *
     * @return Array of objects with 'submissions' atribute as number of submissions saved
     */
    public function get_submissions_number() {
        global $DB;
        if ($this->is_group_activity()) {
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
        return $DB->get_records_sql($query, $parms);
    }

    /**
     * This is for compatibility to old group scheme.
     * Update the submission groupid for VPL version <= 3.2
     *
     * Set the correct groupid when groupid = 0
     * @param int $groupid If no $groupid => update $groupid of all groups of the activity
     * @return void
     */
    public function update_group_v32($groupid = '') {
        global $DB;
        if (! $this->is_group_activity()) {
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
     * @param int $userid the user id to retrieve submission
     * @return false/object re gister of vpl_submissions table or false if no submission
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
                $res = $DB->get_records_select('vpl_submissions', $select, $params, 'id DESC', '*', 0, 1);
                foreach ($res as $sub) {
                    return $sub;
                }
                $this->update_group_v32($group->id);
                $res = $DB->get_records_select('vpl_submissions', $select, $params, 'id DESC', '*', 0, 1);
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
        $res = $DB->get_records_select('vpl_submissions', $select, $params, 'id DESC', '*', 0, 1);
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
        return context_module::instance($this->cm->id);
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
        if (defined('WS_SERVER') && WS_SERVER) {
            $has = has_capability($capability, $this->get_context(), $userid);
        } else {
            $has = is_enrolled($this->get_context(), $userid, $capability, true);
        }
        return $has || is_siteadmin();
    }

    /**
     * Delete overflow submissions. If three submissions within the period central is delete
     *
     * @param int $userid User id to check.
     * @return void
     *
     */
    public function delete_overflow_submissions($userid) {
        global $DB;
        $plugincfg = get_config('mod_vpl');
        if (! isset($plugincfg->discard_submission_period)) {
            return;
        }
        if ($plugincfg->discard_submission_period == 0) {
            // Keep all submissions.
            return;
        }
        if ($plugincfg->discard_submission_period > 0) {
            $vplid = $this->get_instance()->id;
            $select = "(userid = ?) AND (vpl = ?)";
            $params = [
                    $userid,
                    $vplid,
            ];
            $res = $DB->get_records_select(VPL_SUBMISSIONS, $select, $params, 'id DESC', '*', 0, 3);
            if (count($res) == 3) {
                $i = 0;
                foreach ($res as $sub) {
                    switch ($i) {
                        case 0:
                            $last = $sub;
                            break;
                        case 1:
                            $second = $sub;
                            break;
                        case 2:
                            $first = $sub;
                            break;
                    }
                    $i++;
                }
                // Check time consistence.
                if (! ($last->datesubmitted > $second->datesubmitted && $second->datesubmitted > $first->datesubmitted)) {
                    return;
                }
                if (($last->datesubmitted - $first->datesubmitted) < $plugincfg->discard_submission_period) {
                    // Remove second submission.
                    $submission = new mod_vpl_submission($this, $second);
                    if ($this->is_vpl_question_mode()) {
                        $processesinfo = vpl_running_processes::get_by_submission_id($vplid, $userid, $second->id);
                        if ($processesinfo == false) {
                            // No process, remove submission.
                            $submission->delete();
                        }
                    } else {
                        $submission->delete();
                    }
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
     * @param int $userid (optional) Check for given user, current user if null.
     * @return bool
     */
    public function is_visible($userid = null) {
        global $USER;
        if ($userid === null) {
            $userid = $USER->id;
        }
        $cm = $this->get_course_module();
        $modinfo = get_fast_modinfo($cm->course, $userid);
        $ret = true;
        $ret = $ret && $modinfo->get_cm($cm->id)->uservisible;
        $ret = $ret && $this->has_capability(VPL_VIEW_CAPABILITY, $userid);
        $ret = $ret && !$this->mode_prevents_viewing($userid);
        // Grader and manager always view.
        $ret = $ret || $this->is_teacher($userid);
        $ret = $ret || $this->is_mode(activity_modes::STUDENTSREADONLY);
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
        $modinfo = get_fast_modinfo($cm->course, $userid);
        $ret = true;
        $ret = $ret && $this->has_capability(VPL_SUBMIT_CAPABILITY, $userid);
        $ret = $ret && $this->is_submission_period($userid);
        $ret = $ret && $this->is_visible($userid);
        $ret = $ret && !$this->mode_prevents_modification($userid);
        // Manager or grader can always submit.
        $ret = $ret || $this->is_teacher($userid);
        $ret = $ret || ($this->is_vpl_question_mode() && activity_modes::called_from_vplquestion());
        return $ret;
    }

    /**
     * Is a group activity?
     *
     * @return bool
     */
    public function is_group_activity() {
        $cm = $this->get_course_module();
        return $cm->groupingid > 0 && $this->get_instance()->worktype == 1;
    }

    /**
     * Return HTML code to show user picture and user fullname
     * @param object $user DB instance
     * @return string HTML code
     */
    public function user_fullname_picture($user) {
        return $this->user_picture($user) . ' ' . $this->fullname($user);
    }

    /**
     * Return HTML code to show user picture
     * @param object $user DB instance
     * @return string HTML code
     */
    public function user_picture($user) {
        global $OUTPUT;
        if ($this->is_group_activity()) {
            $group = $this->get_usergroup($user->id);
            if ($group === false) {
                return '';
            }
            $courseid = $this->get_course()->id;
            return print_group_picture($group, $courseid, false, true);
        } else {
            $options = ['courseid' => $this->get_instance()->course, 'link' => ! $this->use_seb()];
            return $OUTPUT->user_picture($user, $options);
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
            $group = $this->get_usergroup($user->id);
            if ($group !== false) {
                if ($withlink) {
                    $url = vpl_abs_href('/user/index.php', 'id', $this->get_course()->id, 'group', $group->id);
                    return '<a href="' . $url . '">' . s($group->name) . '</a>';
                } else {
                    return $group->name;
                }
            }
            return '';
        } else {
            $fullname = s(fullname($user));
            if ($withlink) {
                $url = vpl_abs_href('/user/view.php', 'id', $user->id, 'course', $this->get_course()->id);
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
        if (! is_int($groupid)) {
            $groupid = intval($groupid);
        }
        if (! array_key_exists($groupid, $this->graders)) {
            $fields = vpl_get_picture_fields();
            $this->graders[$groupid] = get_enrolled_users(
                $this->get_context(),
                VPL_GRADE_CAPABILITY,
                $groupid,
                $fields,
                'u.lastname ASC'
            );
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
        if (! is_int($groupid)) {
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
            if ($extrafields > '' && $extrafields[0] != ',') {
                $extrafields = ',' . $extrafields;
            }
            $fields = vpl_get_picture_fields() . $extrafields;
            $all = get_enrolled_users(
                $this->get_context(),
                VPL_SUBMIT_CAPABILITY,
                $groupid,
                $fields,
                'u.lastname ASC'
            );
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
     * Return if the current user is inconsistent with the real user.
     *
     * @param int $current Current user id
     * @param int $real Real user id
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
     * @param int $userid User id to retrieve group leader
     * @return int Leader id or $userid if not found
     */
    public function get_group_leaderid($userid) {
        $leaderid = $userid;
        $group = $this->get_usergroup($userid);
        if ($group) {
            foreach ($this->get_usergroup_members($group->id) as $user) {
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
     * @param int $userid User id to retrieve group
     * @return object|false
     */
    public function get_usergroup($userid) {
        if ($this->is_group_activity()) {
            $courseid = $this->get_course()->id;
            $groupingid = $this->get_course_module()->groupingid;
            $groups = groups_get_all_groups($courseid, $userid, $groupingid);
            if ($groups === false || count($groups) > 1) {
                return false;
            }
            return reset($groups);
        }
        return false;
    }

    /**
     * Cache of user groups
     * @var array
     */
    protected static $usergroupscache = [];

    /**
     * If is a group activity return group members for the groupid
     *
     * @param int $groupid Group id to retrieve members
     * @return Array of user objects
     */
    public function get_group_members($groupid) {
        if (! isset(self::$usergroupscache[$groupid])) {
            $gm = groups_get_members($groupid);
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
     * @param int $userid User id to retrieve group members
     * @return array of user objects
     */
    public function get_usergroup_members($userid) {
        $group = $this->get_usergroup($userid);
        if ($group !== false) {
            return $this->get_group_members($group->id);
        }
        return [];
    }

    /**
     * Property for cache scale record
     * @var object
     */
    private $scale;

    /**
     * Return scale record if grade < 0
     *
     * @return Object or false
     */
    public function get_scale() {
        global $DB;
        if (! isset($this->scale)) {
            if ($this->get_grade() < 0) {
                $gradeid = - $this->get_grade();
                $this->scale = self::get_db_record('scale', $gradeid);
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
        if (! isset($this->gradeinfo)) {
            $this->gradeinfo = false;
            if ($this->get_grade() != 0) { // If 0 then NO GRADE.
                if ($this->is_vpl_question_mode()) {
                    // Fake grade item for question mode.
                    // Grade is stored in question attempt and gradebook is not used.
                    $fakegradeitem = new stdClass();
                    $fakegradeitem->courseid = $this->get_course()->id;
                    $fakegradeitem->itemtype = 'mod';
                    $fakegradeitem->itemmodule = 'vpl';
                    $fakegradeitem->iteminstance = $this->get_instance()->id;
                    $fakegradeitem->grademin = 0;
                    $fakegradeitem->grademax = $this->get_grade();
                    $fakegradeitem->gradepass = 0;
                    $fakegradeitem->hidden = true;
                    $fakegradeitem->locked = false;
                    $fakegradeitem->scaleid = 0;
                    $fakegradeitem->grades = null;
                    $fakegradeitem->outcomes = null;

                    $this->gradeinfo = $fakegradeitem;
                } else {
                    $userid = $this->is_teacher() ? null : $USER->id;
                    require_once($CFG->libdir . '/gradelib.php');
                    $gradinginfo = grade_get_grades($this->get_course()->id, 'mod', 'vpl', $this->get_instance()->id, $userid);
                    foreach ($gradinginfo->items as $gi) {
                        $this->gradeinfo = $gi;
                    }
                }
            }
        }
        return $this->gradeinfo;
    }

    /**
     * Return visiblegrade from gradebook and for every user
     *
     * @return boolean
     */
    public function get_visiblegrade() {
        if ($gi = $this->get_grade_info()) {
            if (is_array($gi->grades)) {
                $usergi = reset($gi->grades);
                return ! ($gi->hidden || (is_object($usergi) && $usergi->hidden));
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
        if (! $this->use_seb()) {
            $style = "float:right; right:10px; padding:8px; background-color: white;text-align:center;";
            echo '<div style="' . $style . '">';
            echo '<a href="http://vpl.dis.ulpgc.es/">';
            echo 'VPL ' . vpl_get_version();
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

    /**
     * Map of actions to pagelayouts
     *
     * @var array
     */
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

    /**
     * Get pagelayout for the action
     *
     * @param string $action
     * @return string
     */
    public function get_pagelayout($action) {
        if (isset(self::$pagelayoutmap[$action])) {
            return self::$pagelayoutmap[$action];
        }
        return 'standard';
    }
    /**
     * Prepare page initialy
     *
     * @param string $script the url to set, if false then no url is set
     * @param array $parms parameters to add to the url
     */
    public function prepare_page($script = false, $parms = []) {
        global $PAGE, $CFG;
        $this->script = $script;
        // Next line resolve problem of classic theme not showing setting menu.
        require_login();
        $action = basename($script, '.php');
        if ($script) {
            $PAGE->set_url(new moodle_url('/mod/vpl/' . $script, $parms));
            $PAGE->set_pagetype('mod-vpl-' . $action);
        }
        $PAGE->set_pagelayout(self::get_pagelayout($action));
        if ($CFG->version >= 2022041900) { // Checks is running on Moodle 4.
            $PAGE->activityheader->set_description('');
            $PAGE->activityheader->set_hidecompletion($script != 'view.php');
            if ($script == 'view.php') {
                $PAGE->activityheader->set_title('');
            }
        }
    }

    /**
     * Save if print header was printed
     *
     * @var bool
     */
    protected static $headerisout = false;

    /**
     * Return if header is already printed
     *
     * @return bool
     */
    public static function header_is_out() {
        return self::$headerisout;
    }

    /**
     * Set warnings to show in header
     */
    public function set_warnings() {
        if ($this->script == 'view.php' && $this->is_teacher()) {
            if (! $this->is_mode(activity_modes::NORMAL)) {
                $strmode = activity_modes::get_i18n_key($this->instance->activity_mode);
                $warningmessage = '<b>' . get_string('activity_mode', VPL) . ': </b>';
                $warningmessage .= get_string($strmode, VPL) . "<br>\n";
                $warningmessage .= get_string($strmode . '_help', VPL);
                $this->add_notice($warningmessage, 'warning');
                $instance = $this->get_instance();
                if ($this->is_example() && $instance->run == 0 && $instance->debug == 0) {
                    $warningmessage = '<b>' . get_string('notice') . ': </b><br>';
                    $strno = get_string('no');
                    $warningmessage .= $this->str_setting_with_icon('run', $strno, false, false);
                    $warningmessage .= ' ' . $this->str_setting_with_icon('debug', $strno, false, false);
                    $this->add_notice($warningmessage, 'warning');
                }
            }
        }
    }

    /**
     * Add notification to show after header
     *
     * @param string $message The message to show
     * @param string $type The type of notification: 'error', 'warning', 'success', 'info'
     */
    public function add_notice($message, $type = 'info') {
        if (isset($this->notices[$type])) {
            $this->notices[$type][] = $message;
        } else {
            debugging("Invalid notice type '$type'", DEBUG_DEVELOPER);
        }
    }

    /**
     * Show of notifications collected with add_notice and clear the notifications
     *
     * @return void
     */
    public function show_notifications() {
        foreach ($this->notices as $type => $messages) {
            foreach ($messages as $message) {
                vpl_notice($message, $type);
            }
            $this->notices[$type] = [];
        }
    }

    /**
     * print header
     *
     * @param string $info title and last nav option
     * @param bool $setheading whether to set the page heading
     */
    public function print_header($info = '', $setheading = true) {
        global $PAGE, $OUTPUT;
        if (self::$headerisout) {
            return;
        }
        $tittle = trim($this->get_printable_name() . ' ' . $info);
        $notavaliable = ! $this->is_visible();
        if ($notavaliable) {
            $tittle = get_string('notavailable');
            $this->add_notice($tittle, 'error');
            $setheading = false;
        }
        $PAGE->set_title($this->get_course()->fullname . ' ' . $tittle);
        if ($setheading) {
            $PAGE->set_heading($this->get_course()->fullname);
        }
        if ($this->use_seb() && ! $this->is_teacher()) {
            $PAGE->set_heading($this->get_course()->fullname . ' - ' . $tittle);
            $PAGE->set_popup_notification_allowed(false);
            $PAGE->set_pagelayout('secure');
        }
        echo $OUTPUT->header();
        $this->set_warnings();
        self::$headerisout = true;
        $this->show_notifications();
        if ($notavaliable) {
            $this->print_footer_simple();
            die();
        }
    }

    /**
     * Print header with simple title
     *
     * @param string $info title and last nav option
     */
    public function print_header_simple($info = '') {
        $this->print_header($info, false);
    }

    /**
     * Print heading action with help
     *
     * @param string $action base text and help
     */
    public function print_heading_with_help($action) {
        if (! $this->is_visible()) {
            return;
        }
        global $OUTPUT;
        $title = get_string($action, VPL) . ': ' . $this->get_printable_name();
        echo $OUTPUT->heading_with_help(vpl_get_awesome_icon($action) . $title, $action, 'vpl');
        self::$headerisout = true;
    }

    /**
     * Create tabs to view_description/submit/view_submission/edit
     *
     * @param string $path to get the active tab
     *
     */
    public function print_view_tabs($path) {
        $active = basename($path);
        \mod_vpl\views\activity_menu::print_menu($this, $active);
    }

    /**
     * Show vpl name
     */
    public function print_name() {
        global $OUTPUT, $CFG;
        if ($CFG->version < 2022041900) {
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
    public function str_setting($str, $value = null, $raw = false, $comp = 'mod_vpl') {
        $html = '<b>';
        if ($raw) {
            $html .= s($str);
        } else {
            $html .= s(get_string($str, $comp));
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
     * @param bool $raw if true $str if raw string, default false
     * @param bool $newline if true print new line after setting, default false
     * @param string $comp component for i18n, default mod_vpl
     * @return string HTML
     */
    public function str_setting_with_icon($str, $value = null, $raw = false, $newline = true, $comp = 'mod_vpl') {
        $html = vpl_get_awesome_icon($str);
        $html .= $this->str_setting($str, $value, $raw, $comp);
        if ($newline) {
            $html .= "<br>\n";
        } else {
            $html .= '. ';
        }
        return $html;
    }

    /**
     * Generate HTML fragment for overriden icon.
     * @return string HTML
     */
    public function overriden_icon() {
        $iconclass = mod_vpl_get_fontawesome_icon_map()['mod_vpl:overrides'];
        $title = get_string('overriden', VPL);
        return '<i class="fa ' . $iconclass . ' mx-2" title="' . $title . '" aria-label="' . $title . '"></i>';
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
            $text = userdate($startdate);
            if ($startdate != $this->instance->startdate) {
                $text .= $this->overriden_icon();
            }
            $html .= $this->str_setting_with_icon('startdate', $text);
        }
        $duedate = $this->get_effective_setting('duedate', $userid);
        if ($duedate) {
            $text = userdate($duedate);
            if ($duedate != $this->instance->duedate) {
                $text .= $this->overriden_icon();
            }
            $html .= $this->str_setting_with_icon('duedate', $text);
        }
        return $html;
    }

    /**
     * Show vpl submission status if user is grader.
     * From parameters if supplied or calculated if not.
     * @param int $nstudents Number of students
     * @param int $nsubmissions Number of submissions
     * @param int $ngraded Number of graded submissions
     */
    public function print_submissions_status($nstudents = null, $nsubmissions = 0, $ngraded = 0) {
        $isgrader = $this->has_capability(VPL_GRADE_CAPABILITY);
        if ($isgrader) {
            echo vpl_get_awesome_icon('submissions');
            echo $this->str_submissions_status($nstudents, $nsubmissions, $ngraded) . "<br>\n";
        }
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
        $isgrader = $this->has_capability(VPL_GRADE_CAPABILITY);
        if ($isgrader && !$this->is_mode(activity_modes::NORMAL)) {
            $strmode = activity_modes::get_i18n_key($this->instance->activity_mode);
            $html .= $this->str_setting_with_icon('activity_mode', get_string($strmode, VPL), false, true);
        }
        $filegroup = $this->get_required_fgm();
        $files = $filegroup->getfilelist();
        if (count($files)) {
            $text = '';
            $needcomma = false;
            foreach ($files as $file) {
                if ($needcomma) {
                    $text .= ', ';
                }
                $text .= s($file);
                $needcomma = true;
            }
            $link = ' (' . vpl_get_awesome_icon('download');
            $link .= '<a href="';
            $link .= vpl_mod_href('views/downloadrequiredfiles.php', 'id', $this->get_course_module()->id);
            $link .= '">';
            $link .= get_string('download', VPL);
            $link .= '</a>)';
            $html .= $this->str_setting_with_icon('requestedfiles', $text . $link);
        }
        $instance = $this->get_instance();
        if (count($files) != $instance->maxfiles) {
            $html .= $this->str_setting_with_icon('maxfiles');
        }
        if ($instance->maxfilesize) {
            $mfs = $this->get_maxfilesize();
            $html .= $this->str_setting_with_icon('maxfilesize', vpl_conv_size_to_string($mfs));
        }
        $worktype = $instance->worktype;
        $values = [
                0 => vpl_get_awesome_icon('user') . ' ' . get_string('individualwork', VPL),
                1 => vpl_get_awesome_icon('group') . ' ' . get_string('groupwork', VPL),
        ];
        if ($worktype) {
            $html .= $this->str_setting_with_icon('worktype', $values[$worktype] . ' ' . $this->fullname($USER));
        } else {
            $html .= $this->str_setting_with_icon('worktype', $values[$worktype]);
        }
        $stryes = get_string('yes');
        $strno = get_string('no');
        $strgradessettings = get_string('gradessettings', 'core_grades');
        if ($isgrader) {
            require_once($CFG->libdir . '/gradelib.php');
            $html .= vpl_get_awesome_icon('grade');
            if ($gie = $this->get_grade_info()) {
                if ($gie->scaleid == 0) {
                    $info = get_string('grademax', 'core_grades')
                            . ': ' . format_float($gie->grademax, 5, true, true);
                    $info .= $gie->hidden ? (' <b>' . vpl_get_awesome_icon('hidden')
                                           . get_string('hidden', 'core_grades') . '</b>') : '';
                    $info .= $gie->locked ? (' <b>' . vpl_get_awesome_icon('locked')
                                           . get_string('locked', 'core_grades') . '</b>') : '';
                } else {
                    $info = get_string('typescale', 'core_grades');
                }
                $html .= $this->str_setting_with_icon($strgradessettings, $info, true);
            } else {
                $html .= $this->str_setting_with_icon($strgradessettings, get_string('nograde'), true);
            }
        }
        $html .= $this->str_gradereduction($userid);
        if ($isgrader) {
            $password = trim($this->get_effective_setting('password'));
            if ($password) {
                $html .= $this->str_setting_with_icon('password', $stryes, false, false, 'moodle');
                $infohs = new mod_vpl\util\hide_show();
                $html .= $infohs->generate();
                $html .= $infohs->content_in_tag('span', s($password));
                if ($password != $this->instance->password) {
                    $html .= $this->overriden_icon();
                }
                $html .= "<br>\n";
            }
            if (trim($instance->requirednet) > '') {
                $info = s($instance->requirednet);
                if ($this->script == 'view.php') {
                    if (vpl_check_network($instance->requirednet)) {
                        $text = get_string('requirednet_pass', COMPVPL, getremoteaddr());
                        $type = 'success';
                    } else {
                        $text = get_string('requirednet_bad', COMPVPL, getremoteaddr());
                        $type = 'warning';
                    }
                    $info .= " <span class='alert alert-$type'>$text</span>";
                }
                $html .= $this->str_setting_with_icon('requirednet', $info);
            }
            if ($instance->sebrequired > 0) {
                $info = $stryes;
                if ($this->script == 'view.php') {
                    if ($this->is_seb_browser()) {
                        $text = get_string('sebrequired_pass', COMPVPL, getremoteaddr());
                        $type = 'success';
                    } else {
                        $text = get_string('sebrequired_bad', COMPVPL, getremoteaddr());
                        $type = 'warning';
                    }
                    $info .= " <span class='alert alert-$type'>$text</span>";
                }
                $html .= $this->str_setting_with_icon('sebrequired', $info);
            }
            if ($this->get_sebkeys() > '') {
                $info = $stryes;
                $infohs = new mod_vpl\util\hide_show();
                $info .= $infohs->generate();
                $info .= $infohs->content_in_tag('div', nl2br(s($this->get_sebkeys())));
                if ($this->script == 'view.php') {
                    if ($this->is_sebkey_valid()) {
                        $text = get_string('sebkeys_pass', COMPVPL);
                        $type = 'success';
                    } else {
                        if ($this->is_seb_browser()) {
                            $text = get_string('sebkeys_bad', COMPVPL);
                            $type = 'warning';
                        } else {
                            $text = get_string('sebrequired_bad', COMPVPL);
                            $type = 'warning';
                        }
                    }
                    $info .= " <span class='alert alert-$type'>$text</span>";
                }
                $html .= $this->str_setting_with_icon('sebkeys', $info);
            }
            if ($instance->restrictededitor) {
                $html .= $this->str_setting_with_icon('restrictededitor', $stryes);
            }
            if (! $this->get_course_module()->visible) {
                $html .= vpl_get_awesome_icon('hidden') . ' ';
                $html .= $this->str_setting_with_icon(get_string('visible'), $strno, true);
            }
            if ($instance->basedon) {
                $basedon = new mod_vpl(null, $instance->basedon);
                $link = '<a href="';
                $link .= vpl_mod_href('view.php', 'id', $basedon->cm->id);
                $link .= '">';
                $link .= $basedon->get_printable_name();
                $link .= '</a>';
                $html .= $this->str_setting_with_icon('basedon', $link);
            }
            $noyes = [
                    $strno,
                    $stryes,
            ];
            $html .= $this->str_setting_with_icon('run', $noyes[$instance->run], false, false);
            $customized = $this->get_customized_scripts();
            if ($instance->run) {
                if ($customized['run']) {
                    $runscript = get_string('customizedscript', VPL);
                } else {
                    $runscript = $instance->runscript ? strtoupper($instance->runscript) : '';
                    if (! $runscript) {
                        $inheritedrun = $this->get_closest_set_field_in_base_chain('runscript', '');
                        if ($inheritedrun) {
                            $runscript = get_string('inheritvalue', VPL, strtoupper($inheritedrun));
                        }
                    }
                }
                if ($runscript) {
                    $html .= $this->str_setting_with_icon('runscript', $runscript, false, false);
                }
            }
            if ($instance->debug) {
                $html .= $this->str_setting_with_icon('debug', $noyes[1], false, false);
                if ($customized['debug']) {
                    $debugscript = get_string('customizedscript', VPL);
                } else {
                    $debugscript = $instance->debugscript ? strtoupper($instance->debugscript) : '';
                    if (! $debugscript) {
                        $inheriteddebug = $this->get_closest_set_field_in_base_chain('debugscript', '');
                        if ($inheriteddebug) {
                            $debugscript = get_string('inheritvalue', VPL, strtoupper($inheriteddebug));
                        }
                    }
                }
                if ($debugscript) {
                    $html .= $this->str_setting_with_icon('debugscript', $debugscript, false, false);
                }
            }
            $html .= $this->str_setting_with_icon(
                'evaluate',
                $noyes[$instance->evaluate],
                false,
                ! $instance->evaluate
            );
            $evaluator = $instance->evaluator ? strtoupper($instance->evaluator) : '';
            if (! $evaluator) {
                $inheritedevaluator = $this->get_closest_set_field_in_base_chain('evaluator', '');
                if ($inheritedevaluator) {
                    $evaluator = get_string('inheritvalue', VPL, strtoupper($inheritedevaluator));
                }
            }
            if (! $evaluator && $customized['evaluate']) {
                $evaluator = get_string('customizedscript', VPL);
            }
            if ($evaluator) {
                $addnewline = ! ($instance->evaluate && $instance->evaluateonsubmission);
                $html .= $this->str_setting_with_icon('evaluator', $evaluator, false, $addnewline);
            }
            if ($instance->evaluate && $instance->evaluateonsubmission) {
                $html .= $this->str_setting_with_icon('evaluateonsubmission', $noyes[1]);
            }
            if ($instance->automaticgrading) {
                $html .= $this->str_setting_with_icon('automaticgrading', $noyes[1], false, false);
            }
            if ($instance->maxexetime) {
                $html .= $this->str_setting_with_icon('maxexetime', $instance->maxexetime . ' s', false, false);
            }
            if ($instance->maxexememory) {
                $size = vpl_conv_size_to_string($instance->maxexememory);
                $html .= $this->str_setting_with_icon('maxexememory', $size, false, false);
            }
            if ($instance->maxexefilesize) {
                $size = vpl_conv_size_to_string($instance->maxexefilesize);
                $html .= $this->str_setting_with_icon('maxexefilesize', $size, false, false);
            }
            if ($instance->maxexeprocesses) {
                $html .= $this->str_setting_with_icon('maxexeprocesses', null, false, false);
            }
            $html .= "<br>\n";
            $html .= $this->get_overriden_summary();
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
     * Get overriden/exception summary in the activity description
     * @return string HTML
     */
    public function get_overriden_summary() {
        $vplid = $this->instance->id;
        $vplcmid = $this->cm->id;
        $overrides = vpl_get_overrides($vplid);
        $html = '';
        if ($overrides) {
            // Calculate overriden settings.
            $ngroupoverrides = 0;
            $nuseroverrides = 0;
            $noverrides = count($overrides);
            foreach ($overrides as $override) {
                if ($override->groupids) {
                    $ngroupoverrides += count(explode(',', $override->groupids));
                }
                if ($override->userids) {
                    $nuseroverrides += count(explode(',', $override->userids));
                }
            }
            $a = new stdClass();
            $a->noverrides = $noverrides;
            $a->nuseroverrides = $nuseroverrides;
            $a->ngroupoverrides = $ngroupoverrides;
            $overridesummary = get_string('overridesummary', VPL, $a);
            $url = new moodle_url('/mod/vpl/forms/overrides.php', ['id' => $vplcmid]);
            $html .= vpl_get_awesome_icon('overrides');
            $html .= html_writer::link($url, $overridesummary);
            $html .= "<br>\n";
        }
        return $html;
    }

    /**
     * Show short description
     */
    public function print_shortdescription() {
        global $OUTPUT;
        if (! $this->is_visible()) {
            return;
        }
        if ($this->instance->shortdescription) {
            echo $OUTPUT->box_start();
            echo format_text($this->instance->shortdescription, FORMAT_PLAIN);
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
            $html .= $this->str_setting('reductionbyevaluation', $reductionbyevaluation);
            if ($reductionbyevaluation != $this->instance->reductionbyevaluation) {
                $html .= $this->overriden_icon();
            }
            $freeevaluations = $this->get_effective_setting('freeevaluations', $userid);
            if ($freeevaluations > 0) {
                $html .= ' ' . $this->str_setting('freeevaluations', $freeevaluations);
                if ($freeevaluations != $this->instance->freeevaluations) {
                    $html .= $this->overriden_icon();
                }
            }
            $html .= "<br>\n";
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
            echo $OUTPUT->box($full);
        } else {
            $this->print_shortdescription();
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
            $html .= "<br>\n";
            $html .= vpl_get_awesome_icon('variations');
            $html .= ' <b>' . get_string('variations', VPL) . $div->generate() . "</b><br>\n";
            $html .= $div->begin('div');
            if (! $this->instance->usevariations) {
                $html .= '<b>' . get_string('variations_unused', VPL) . "</b><br>\n";
            }
            if ($this->instance->variationtitle) {
                $html .= '<b>' . get_string('variationtitle', VPL) . ': ' . s($this->instance->variationtitle) . "</b><br>\n";
            }
            $number = 1;
            foreach ($variations as $variation) {
                $html .= '<b>' . get_string('variation_n', VPL, $number) . '</b>: ';
                $html .= s($variation->identification) . "<br>\n";
                $html .= $OUTPUT->box($variation->description);
                $number++;
            }
            $html .= $div->end();
        }
        return $html;
    }

    /**
     * Get user variation. Assign one if needed
     * @param int $userid User Id
     * @return stdClass|false Variation object or false if no variation assigned.
     * @throws moodle_exception if variation assigned is not valid.
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
            $variations = $DB->get_records(VPL_VARIATIONS, ['vpl' => $this->instance->id]);
            if (count($variations) == 0) { // No variation set.
                return false;
            }
            // Select a random variation.
            shuffle($variations);
            $variation = $variations[0];
            $assign = new stdClass();
            $assign->vpl = $this->instance->id;
            $assign->variation = $variation->id;
            $assign->userid = $userid;
            if (! $DB->insert_record(VPL_ASSIGNED_VARIATIONS, $assign)) {
                throw new moodle_exception('invalidcoursemodule');
            }
            \mod_vpl\event\variation_assigned::logvpl($this, $variation->id, $userid);
        } else {
            $variation = self::get_db_record(VPL_VARIATIONS, $varassigned->variation);
            if ($variation == false || $variation->vpl != $varassigned->vpl) { // Checks consistency.
                $DB->delete_records(
                    VPL_ASSIGNED_VARIATIONS,
                    [
                        'id' => $varassigned->id,
                    ]
                );
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
        if (isset($already[$this->instance->id])) { // Avoid infinite recursion.
            return '';
        }
        if (! $this->is_visible()) {
            return '';
        }
        $html = '';
        $already[$this->instance->id] = true; // Mark as visited.
        if ($this->instance->basedon) { // Show recursive varaitions.
            $basevpl = new mod_vpl(false, $this->instance->basedon);
            $html .= $basevpl->get_variation_html($userid, $already);
        }
        // If user with grade or manage capability print all variations.
        if (
            $this->has_capability(VPL_GRADE_CAPABILITY, $userid) || $this->has_capability(
                VPL_MANAGE_CAPABILITY,
                $userid
            )
        ) {
            $html .= $this->get_all_variations_html();
        }
        // Show user variation if active.
        if ($this->instance->usevariations) { // Variations actived.
            $variation = $this->get_variation($userid);
            if ($variation !== false) { // Variations defined.
                if ($this->instance->variationtitle > '') {
                    $html .= '<b>' . format_text($this->instance->variationtitle, FORMAT_HTML) . "</b><br>\n";
                }
                $html .= $OUTPUT->box($variation->description);
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
     * Get variations identification for this user
     * @param integer $userid User Id default value 0
     * @param array $already array of based on visited, default empty
     * return an array with variations for this user
     */
    public function get_variation_identification($userid = 0, &$already = []): array {
        if (! ($this->instance->usevariations) || isset($already[$this->instance->id])) { // Avoid infinite recursion.
            return [];
        }
        $already[$this->instance->id] = true;
        if ($this->instance->basedon) {
            $basevpl = new mod_vpl(false, $this->instance->basedon);
            $ret = $basevpl->get_variation_identification($userid, $already);
        } else {
            $ret = [];
        }
        $variation = $this->get_variation($userid);
        if ($variation !== false) {
            $ret[] = $variation->identification;
        }
        return $ret;
    }

    /**
     * Get submissions status from parameters or calculated.
     * @param int $nstudents Number of students or groups. If null, it will be calculated.
     * @param int $nsubmissions Number of submissions.
     * @param int $ngraded Number of graded submissions.
     * @return object with nstudents, nsubmissions and ngraded
     */
    public function get_submissions_status($nstudents = null, $nsubmissions = 0, $ngraded = 0) {
        global $PAGE;
        $result = new stdClass();
        $result->ugcount = $nstudents;
        $result->subcount = $nsubmissions;
        $result->gradedcount = $ngraded;
        if ($nstudents === null) {
            if ($this->is_group_activity()) {
                $groupingid = $this->get_course_module()->groupingid;
                $courseid = $this->get_course()->id;
                $allstudents = groups_get_all_groups($courseid, 0, $groupingid);
                $allstudents = $this->filter_empty_groups($allstudents);
            } else {
                $allstudents = $this->get_students();
            }
            $submissions = $this->all_last_user_submission('s.dategraded, s.userid, s.groupid');
            $submissions = $this->filter_submissions_by_students($submissions, $allstudents);
            $result->ugcount = count($allstudents);
            $result->subcount = count($submissions);
            if ($this->get_grade() != 0 && $result->subcount != 0) {
                $result->gradedcount = $this->number_of_graded_submissions($submissions);
            }
        }
        return $result;
    }

    /**
     * Get HTML string with submissions status.
     * @param int $nstudents Number of students or groups. If null, it will be calculated.
     * @param int $nsubmissions Number of submissions.
     * @param int $ngraded Number of graded submissions.
     * @return string HTML
     */
    public function str_submissions_status($nstudents = null, $nsubmissions = 0, $ngraded = 0) {
        global $PAGE;
        $status = $this->get_submissions_status($nstudents, $nsubmissions, $ngraded);
        if ($status->ugcount == 0) {
            $nsubmissionspc = '-';
        } else {
            $nsubmissionspc = round(100 * $status->subcount / $status->ugcount, 2);
        }
        $data = new stdClass();
        $urlbase = '/mod/vpl/views/submissionslist.php';
        $params = ['id' => $this->cm->id, 'selection' => 'all'];
        $data->name = get_string($this->is_group_activity() ? 'groups' : 'students');
        $data->ugcount = html_writer::link(new moodle_url($urlbase, $params), $status->ugcount);
        $params['selection'] = 'allsubmissions';
        $data->subcount = html_writer::link(new moodle_url($urlbase, $params), $status->subcount);
        $data->subpercent = $nsubmissionspc;
        if ($this->get_grade() != 0) {
            if ($status->subcount == 0) {
                $ngraded = 0;
                $ngradedpc = '-';
                $nnotgraded = 0;
                $nnotgradedpc = '-';
            } else {
                $ngraded = $status->gradedcount;
                $ngradedpc = round(100 * $ngraded / $status->subcount, 2);
                $nnotgraded = $status->subcount - $ngraded;
                $nnotgradedpc = round(100 * $nnotgraded / $status->subcount, 2);
            }
            $params['selection'] = 'graded';
            $data->gradedcount = html_writer::link(new moodle_url($urlbase, $params), $ngraded);
            $data->gradedpercent = $ngradedpc;
            $params['selection'] = 'notgraded';
            $data->notgradedcount = html_writer::link(new moodle_url($urlbase, $params), $nnotgraded);
            $data->notgradedpercent = $nnotgradedpc;
            $strname = 'submissions_graded_overview';
            $html = get_string('submissions_graded_overview', VPL, $data);
        } else {
            $strname = 'submissions_overview';
            $html = get_string('submissions_overview', VPL, $data);
        }
        $html = get_string($strname, VPL, $data);
        $output = $PAGE->get_renderer('core');
        $html .= $output->help_icon($strname, VPL, true);
        return $html;
    }

    /**
     * Filter submissions by students.
     * @param array $submissions Array of submissions.
     * @param array $students Array of students or groups.
     * @return array Filtered submissions.
     */
    public function filter_submissions_by_students($submissions, $students) {
        $filtersubmissions = [];
        $field = $this->is_group_activity() ? 'groupid' : 'userid';
        foreach ($submissions as $sub) {
            if (isset($students[$sub->$field])) {
                $filtersubmissions[$sub->$field] = $sub;
            }
        }
        return $filtersubmissions;
    }

    /**
     * Filter empty groups.
     * @param array $groups Array of groups.
     * @return array Filtered groups.
     */
    public function filter_empty_groups($groups) {
        global $DB;
        $sql = <<<SQL
            SELECT gm.groupid, COUNT(gm.userid) AS nmembers
            FROM {groups_members} gm
            INNER JOIN {groupings_groups} gg ON gm.groupid = gg.groupid
            WHERE gg.groupingid = :groupingid
            GROUP BY gm.groupid;
        SQL;
        $groupingid = $this->get_course_module()->groupingid;
        $groupmembers = $DB->get_records_sql($sql, ['groupingid' => $groupingid]);
        $filtergroups = [];
        foreach ($groups as $group) {
            if (isset($groupmembers[$group->id]) && $groupmembers[$group->id]->nmembers > 0) {
                $filtergroups[$group->id] = $group;
            }
        }
        return $filtergroups;
    }

    /**
     * Get number of graded submissions.
     * @param array $submissions Array of submissions.
     * @return int Number of graded submissions.
     */
    public function number_of_graded_submissions($submissions) {
        $ngraded = 0;
        foreach ($submissions as $sub) {
            if ($sub->dategraded > 0) {
                $ngraded++;
            }
        }
        return $ngraded;
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
                        ORDER BY aoid DESC';
            // Get all overrides for this user and any group, ordered by most recent.
            // User overrides will have precedence over group overrides.
            // The most recent will have precedence over previous ones.
            $overrides = $DB->get_records_sql($sql, ['vplid' => $this->instance->id, 'userid' => $userid]);
            $useroverrides = false;
            foreach ($overrides as $override) {
                if (!empty($override->userid)) {
                    // Found record for user.
                    foreach ($fields as $field) {
                        self::$overridensettings[$this->cm->id][$userid]->$field = $override->$field;
                    }
                    $useroverrides = true;
                    break;
                }
            }
            if (! $useroverrides) {
                foreach ($overrides as $override) {
                    if (!empty($override->groupid) && groups_is_member($override->groupid, $userid)) {
                        foreach ($fields as $field) {
                            self::$overridensettings[$this->cm->id][$userid]->$field = $override->$field;
                        }
                        break;
                    }
                }
            }
        }
        if (
            isset(self::$overridensettings[$this->cm->id][$userid]->$setting) &&
            self::$overridensettings[$this->cm->id][$userid]->$setting !== null
        ) {
            return self::$overridensettings[$this->cm->id][$userid]->$setting;
        } else {
            return $this->instance->$setting;
        }
    }

    /**
     * Check first activity access if has password or using SEB.
     * Must be used after checks passed.
     * If fist time access save an empty submission for register the access.
     * @return void
     **/
    public function check_first_access() {
        global $USER, $DB, $SESSION;
        $sessvarname = 'vpl_first_access_checked';
        // If teacher or already checked ignore.
        if ($this->is_teacher() || ($SESSION->$sessvarname ?? false) == $this->instance->id) {
            return;
        }
        // Needed if password or SEB is required.
        $needcheck = $this->instance->password > '';
        $needcheck = $needcheck || $this->use_seb();
        if ($needcheck) {
            $lastsubmission = $this->last_user_submission($USER->id);
            if ($lastsubmission) {
                $SESSION->$sessvarname = $this->instance->id;
            } else {
                $error = '';
                $files = [];
                if ($this->add_submission($USER->id, $files, '', $error) != false) {
                    $SESSION->$sessvarname = $this->instance->id;
                }
            }
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
                    $currenteventid = $DB->get_field('event', 'id', $params); // Get current calendar event.
                    if (isset($override->duedate) && !$delete) {
                        if ($target == 'userid') {
                            $userorgroupname = fullname(self::get_db_record('user', $userorgroupid));
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
                            calendar_event::create($newevent);
                        } else {
                            // An event already exists, update it.
                            calendar_event::load($currenteventid)->update($newevent);
                        }
                    } else {
                        if ($currenteventid !== false) {
                            calendar_event::load($currenteventid)->delete();
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
                    $eventid = $DB->get_field('event', 'id', $params);
                    if ($eventid !== false) {
                        calendar_event::load($eventid)->delete();
                    }
                }
            }
        }
    }

    /**
     * Retrieve the first non-empty setting in the basedon chain.
     * @param string $field Setting name (DB column name)
     * @param mixed $default Default to return if no VPL in basedon chain defines the setting
     */
    public function get_closest_set_field_in_base_chain($field, $default = null) {
        $instance = $this->instance;
        $basedons = [ $instance->id => true ];
        while ($instance->basedon) {
            if (isset($basedons[$instance->basedon])) {
                throw new moodle_exception('error:recursivedefinition', 'mod_vpl');
            }
            $basedons[$instance->basedon] = true;
            $instance = self::get_db_record(VPL, $instance->basedon);
            if ($instance->{$field}) {
                return $instance->{$field};
            }
        }
        return $default;
    }

    /**
     * Get customized scripts
     * Return an array saying if the run/debug or evaluated script is customized.
     * In the current VPL instance or in any of its bases.
     * @return array An associative array indicating customization status.
     */
    public function get_customized_scripts() {
        require_once('vpl_submission_CE.class.php');
        $customized = [];
        $data = mod_vpl_submission_CE::prepare_execution_base($this, mod_vpl_submission_CE::TEVALUATE);
        $customized['run'] = isset($data->files[mod_vpl_submission_CE::TYPE_TO_SCRIPT[mod_vpl_submission_CE::TRUN]]) &&
             trim($data->files[mod_vpl_submission_CE::TYPE_TO_SCRIPT[mod_vpl_submission_CE::TRUN]]) != '';
        $customized['debug'] = isset($data->files[mod_vpl_submission_CE::TYPE_TO_SCRIPT[mod_vpl_submission_CE::TDEBUG]]) &&
             trim($data->files[mod_vpl_submission_CE::TYPE_TO_SCRIPT[mod_vpl_submission_CE::TDEBUG]]) != '';
        $customized['evaluate'] = isset($data->files[mod_vpl_submission_CE::TYPE_TO_SCRIPT[mod_vpl_submission_CE::TEVALUATE]]) &&
             trim($data->files[mod_vpl_submission_CE::TYPE_TO_SCRIPT[mod_vpl_submission_CE::TEVALUATE]]) != '';
        return $customized;
    }

    /**
     * Check if the activity is in a specific mode.
     * @param int $mode Activity mode to check
     * @return bool
     */
    public function is_mode($mode) {
        return (int)$this->instance->activity_mode == (int)$mode;
    }

    /**
     * Check if the activity is in example mode.
     * @return bool
     */
    public function is_example() {
        return $this->is_mode(activity_modes::EXAMPLE);
    }

    /**
     * Check if the activity is in VPL question mode.
     * @return bool
     */
    public function is_vpl_question_mode() {
        return $this->is_mode(activity_modes::VPLQUESTION);
    }

    /**
     * Return if is user is a teacher in this activity.
     * Has capability to grade or manage is considered teacher.
     * @param int $userid (optional) Check for given user, current user if null.
     * @return bool
     */
    public function is_teacher($userid = null) {
        global $USER;
        if ($userid === null) {
            $userid = $USER->id;
        }
        return ($this->has_capability(VPL_GRADE_CAPABILITY, $userid) ||
                $this->has_capability(VPL_MANAGE_CAPABILITY, $userid));
    }

    /**
     * Return if the activity mode prevents showing the activity to the user.
     * @param int $userid (optional) Check for given user, current user if null.
     * @return bool
     */
    public function mode_prevents_viewing($userid = null) {
        global $USER;
        if ($userid === null) {
            $userid = $USER->id;
        }
        if ($this->is_teacher($userid)) {
            return false;
        }
        return activity_modes::mode_prevents_viewing((int)$this->instance->activity_mode);
    }
    /**
     * Return if the activity mode prevent modification of the activity for the user.
     * @param int $userid (optional) Check for given user, current user if null.
     * @return bool
     */
    public function mode_prevents_modification($userid = null) {
        global $USER;
        if ($userid === null) {
            $userid = $USER->id;
        }
        if ($this->is_teacher($userid)) {
            return false;
        }
        return activity_modes::mode_prevents_modification((int)$this->instance->activity_mode);
    }
}
