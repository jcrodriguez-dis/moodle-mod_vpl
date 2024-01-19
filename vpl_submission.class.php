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
 * Submission class definition
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/**
 * Module instance files
 * path= vpl_data//vpl_instance#
 * Submission info
 * path/usersdata/userid#/submissionid#/submittedfiles.lst
 * path/usersdata/userid#/submissionid#/submittedfiles/
 * path/usersdata/userid#/submissionid#/grade_comments.txt
 * path/usersdata/userid#/submissionid#/teachertest.txt
 * path/usersdata/userid#/submissionid#/studenttest.txt
 */
defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__).'/vpl.class.php');
require_once(dirname(__FILE__).'/views/sh_factory.class.php');

// Non static due to usort error.
function vpl_compare_filenamebylengh($f1, $f2) {
    return strlen( $f2 ) - strlen( $f1 );
}

class mod_vpl_submission {
    protected $vpl;
    protected $instance;

    /**
     * Internal var object to submitted file group manager
     *
     * @var object of file group manager
     */
    protected $submittedfgm;

    /**
     * Constructor
     *
     * @param mod_vpl $vpl
     * @param Object/id $mix submission DB record instance object or record id
     */
    public function __construct(mod_vpl $vpl, $rid) {
        global $DB;
        $this->vpl = $vpl;
        if (is_object( $rid )) {
            $this->instance = $rid;
        } else {
            $this->instance = $DB->get_record( 'vpl_submissions', [
                    'id' => $rid,
            ] );
            if (! $this->instance) {
                throw new Exception( 'vpl_submission id error' );
            }
        }
        $this->submittedfgm = null;
    }

    /**
     * get submission instance
     *
     * @return object submission instance
     */
    public function get_instance() {
        return $this->instance;
    }

    /**
     * get vpl object related
     *
     * @return object vpl
     */
    public function get_vpl() {
        return $this->vpl;
    }
    /**
     * Return the proper userid
     *
     * @return int user id
     */
    public function get_userid() {
        if ($this->vpl->is_group_activity()) {
            $users = $this->vpl->get_group_members($this->instance->groupid);
            if ( count($users) == 0 ) {
                return $this->instance->userid;
            }
            $user = reset( $users );
            return $user->id;
        }
        return $this->instance->userid;
    }
    /**
     * get path to data submission directory
     *
     * @return string submission data directory
     */
    public function get_data_directory() {
        return $this->vpl->get_users_data_directory() . '/' . $this->instance->userid . '/' . $this->instance->id . '/';
    }

    /**
     * get path to files submission directory
     *
     * @return string files submission directory
     */
    public function get_submission_directory() {
        return $this->get_data_directory() . 'submittedfiles/';
    }

    /**
     * get absolute path to name of file with list of submitted files
     *
     * @return string file name
     */
    public function get_submissionfilelistname() {
        return $this->get_data_directory() . 'submittedfiles.lst';
    }

    /**
     *
     * @return object file group manager for submitted files
     */
    public function get_submitted_fgm() {
        if (! $this->submittedfgm) {
            $this->submittedfgm = new file_group_process( $this->get_submission_directory() );
        }
        return $this->submittedfgm;
    }

    /**
     * get absolute path to the grade comments file name
     *
     * @return string file name
     */
    public function get_gradecommentsfilename() {
        return $this->get_data_directory() . 'grade_comments.txt';
    }

    /**
     * get submitted files
     *
     * @return array of array 'name' and 'data'
     */
    public function get_submitted_files() {
        $fg = $this->get_submitted_fgm();
        return $fg->getallfiles();
    }
    public function set_submitted_file($files, $othersub = null) {
        $fg = $this->get_submitted_fgm();
        if ($othersub != null) {
            $otherdir = $othersub->get_submission_directory();
            $otherfln = $othersub->get_submissionfilelistname();
            $fg->addallfiles($files, $otherdir, $otherfln);
        } else {
            $fg->addallfiles($files);
        }
    }
    public function is_equal_to(&$files, $comment = '') {
        if ($this->instance->comments != $comment) {
            return false;
        }
        $subfiles = $this->get_submitted_files();
        if (count( $files ) != count( $subfiles )) {
            return false;
        }
        $oldnames = array_keys( $subfiles );
        $newnames = array_keys( $files );
        foreach ($oldnames as $pos => $name) {
            if ( $name != $newnames[$pos] ) {
                return false;
            }
        }
        foreach ($files as $name => $data) {
            if ( ! isset ($subfiles[$name]) ) {
                return false;
            }
            if ($subfiles[$name] != $data ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Delete submitted files and own record
     *
     * @return void
     *
     */
    public function delete() {
        global $DB;
        \mod_vpl\event\submission_deleted::log($this);
        vpl_delete_dir($this->get_data_directory());
        $DB->delete_records('vpl_submissions', ['id' => $this->instance->id]);
    }

    /**
     * Return if submission has been graded
     *
     * @return bool
     */
    public function is_graded() {
        return $this->instance->dategraded > 0;
    }

    private static $gradecache = [];
    public static function load_gradebook_grades($vpl) {
        if ($vpl->get_grade() != 0) {
            $cm = $vpl->get_course_module();
            $currentgroup = groups_get_activity_group($cm, true);
            $users = $vpl->get_students($currentgroup);
            $usersids = array_keys($users);
            $courseid = $vpl->get_course()->id;
            $vplid = $vpl->get_instance()->id;
            $grades = grade_get_grades($courseid, 'mod', 'vpl', $vplid, $usersids);
            if (!isset(self::$gradecache[$vplid])) {
                self::$gradecache[$vplid] = [];
            }
            if ($grades) {
                self::$gradecache[$vplid] += $grades->items[0]->grades;
            }
        }
    }
    public static function reset_gradebook_cache() {
        self::$gradecache = [];
    }

    public function get_gradebook_grade($userid = 0) {
        $vplid = $this->vpl->get_instance()->id;
        if ($userid == 0) {
            $userid = $this->instance->userid;
        }
        if (!isset(self::$gradecache[$vplid][$userid])) {
            $courseid = $this->vpl->get_course()->id;
            $grades = grade_get_grades($courseid, 'mod', 'vpl', $vplid, $userid);
            if (! isset($grades->items[0]->grades[$userid])) {
                return false;
            }
            self::$gradecache[$vplid][$userid] = $grades->items[0]->grades[$userid];
        }
        return self::$gradecache[$vplid][$userid];
    }
    /**
     * Remove grade.
     *
     * @return true if removed and false if not
     */
    public function remove_grade() {
        global $CFG;
        global $DB;
        ignore_user_abort( true );
        if ($this->vpl->is_group_activity()) {
            $usersid = array_keys($this->vpl->get_group_members($this->instance->groupid));
        } else {
            $usersid = [$this->instance->userid];
        }
        $grades = [];
        foreach ($usersid as $userid) {
            $gradeinfo = [];
            $gradeinfo['userid'] = $userid;
            $gradeinfo['rawgrade'] = null;
            $gradeinfo['feedback'] = '';
            $grades[$userid] = $gradeinfo;
        }
        $vplinstance = $this->vpl->get_instance();
        self::reset_gradebook_cache();
        if (vpl_grade_item_update( $vplinstance, $grades ) != GRADE_UPDATE_OK) {
            return false;
        }
        if (! empty( $CFG->enableoutcomes )) {
            foreach ($usersid as $userid) {
                $gradinginfo = grade_get_grades( $this->vpl->get_course()->id
                                                 , 'mod'
                                                 , 'vpl'
                                                 , $this->vpl->get_instance()->id
                                                 , $userid );
                if (! empty( $gradinginfo->outcomes )) {
                    $outcomes = array_fill_keys(array_keys($gradinginfo->outcomes), null);
                    grade_update_outcomes( 'mod/vpl'
                                          , $this->vpl->get_course()->id
                                          , 'mod'
                                          , VPL
                                          , $this->vpl->get_instance()->id
                                          , $userid, $outcomes );
                }
            }
        }
        $this->instance->grader = 0;
        $this->instance->dategraded = 0;
        $this->instance->grade = null;
        $fn = $this->get_gradecommentsfilename();
        if (! $DB->update_record( VPL_SUBMISSIONS, $this->instance )) {
            throw new moodle_exception( 'error:recordnotupdated', 'mod_vpl', '', VPL_SUBMISSIONS );
        } else {
            if (file_exists( $fn )) {
                unlink( $fn );
            }
        }
        return true;
    }

    /**
     * Get current grade reduction.
     *
     * @param & $reduction
     *          value or factor
     * @param & $percent bool
     *          if true then $reduction is factor
     * @return float grade reduction
     */
    public function grade_reduction(& $reduction, & $percent) {
        $reduction = 0;
        $percent = false;
        $vplinstance = $this->vpl->get_instance();
        if (! ($vplinstance->reductionbyevaluation > 0) ||
            $vplinstance->freeevaluations >= $this->instance->nevaluations ) {
            return;
        }
        $mul = $this->instance->nevaluations - $vplinstance->freeevaluations;
        if ( substr($vplinstance->reductionbyevaluation, -1, 1) == '%' ) {
            $reduction = substr($vplinstance->reductionbyevaluation, 0, -1);
            $reduction = pow( (100.0 - $reduction) / 100.0, $mul);
            $percent = true;
        } else {
            $reduction = $vplinstance->reductionbyevaluation * $mul;
        }
    }
    /**
     * String with the reduction policy.
     *
     * @return string reduction policy in HTML format
     */
    public function reduce_grade_string() {
        global $OUTPUT;
        $vplinstance = $this->vpl->get_instance();
        if ( ! ($vplinstance->reductionbyevaluation > 0 ) ) {
            return '';
        }
        $reduction = 0;
        $percent = false;
        $this->grade_reduction($reduction, $percent);
        $value = $reduction;
        if ($percent) {
            $value = (100 - ( $value * 100 ) );
            $value = format_float($value, 2, true, true) . '%';
        } else {
            $value = format_float($value, 2, true, true);
        }
        $vplinstance = $this->vpl->get_instance();
        $html = $this->vpl->str_restriction('finalreduction', $value);
        $html .= ' [' . $this->instance->nevaluations;
        $html .= ' / ' . $vplinstance->freeevaluations;
        $html .= ' -' . $vplinstance->reductionbyevaluation . ']';
        $html .= $OUTPUT->help_icon('finalreduction', VPL);
        return $html;
    }

    /**
     * Reduce grade based en number of evaluations.
     *
     * @param float $grade value
     * @return float new grade
     */
    public function reduce_grade($grade) {
        $reduction = 0;
        $percent = false;
        $this->grade_reduction($reduction, $percent);
        if ($reduction > 0) {
            if ($percent) {
                return $grade * $reduction;
            } else {
                return $grade - $reduction;
            }
        }
        return $grade;
    }

    /**
     * Set/update grade
     *
     * @param object $info with grade and comments fields
     * @param boolean $automatic if automatic grading (default false)
     * @return boolean. true => OK
     */
    public function set_grade($info, $automatic = false) {
        global $USER;
        global $CFG;
        global $DB;
        ignore_user_abort( true );
        $scaleid = $this->vpl->get_grade();
        if ($scaleid == 0 && empty( $CFG->enableoutcomes )) { // No scale no outcomes.
            return false;
        }
        if ($automatic) { // Who grade.
            $this->instance->grader = 0;
        } else {
            $this->instance->grader = $USER->id;
        }
        if ($this->vpl->is_group_activity()) {
            $usersid = array_keys($this->vpl->get_group_members($this->instance->groupid));
        } else {
            $usersid = [$this->instance->userid];
        }
        $this->instance->dategraded = time();
        if ($scaleid != 0) {
            // Sanitize grade.
            if ($scaleid > 0) {
                 $floatn = unformat_float($info->grade);
                if ( $floatn !== false ) {
                    $info->grade = $floatn;
                }
            } else {
                $info->grade = ( int ) $info->grade;
            }
            $this->instance->grade = $info->grade;
            // Save assessment comments.
            $comments = $info->comments;
            $fn = $this->get_gradecommentsfilename();
            if ($comments) {
                vpl_fwrite( $fn, $comments );
            } else if (file_exists( $fn )) {
                unlink( $fn );
            }
            // Update gradebook.
            $grades = [];
            $gradeinfo = [];
            // If no grade then don't set rawgrade and feedback.
            if ( $scaleid != 0 ) {
                $gradeinfo['rawgrade'] = $this->reduce_grade($info->grade);
                $gradeinfo['feedback'] = $this->result_to_html( $comments, false );
                $gradeinfo['feedbackformat'] = FORMAT_HTML;
            }
            if ($this->instance->grader > 0) { // Don't add grader if automatic.
                $gradeinfo['usermodified'] = $this->instance->grader;
            } else { // This avoid to use an unexisting userid (0) in the gradebook.
                $gradeinfo['usermodified'] = $USER->id;
            }
            $gradeinfo['datesubmitted'] = $this->instance->datesubmitted;
            $gradeinfo['dategraded'] = $this->instance->dategraded;
            foreach ($usersid as $userid) {
                $usergrade = $gradeinfo + [];
                $usergrade['userid'] = $userid;
                $grades[$userid] = $usergrade;
            }
            if (vpl_grade_item_update( $this->vpl->get_instance(), $grades ) != GRADE_UPDATE_OK ) {
                return false;
            }
            // The function vpl_grade_item_update say OK but may be overridden.
            // Check if grade is overridden by comparing save time.
            // Other option is checking the grade_item state.
            try {
                $dategraded = $this->get_gradebook_grade($usersid[0])->dategraded;
            } catch (\Throwable $e) {
                return false;
            }
            if ($dategraded != $gradeinfo['dategraded']) {
                return false;
            }
        }
        if (! empty( $CFG->enableoutcomes )) {
            foreach ($usersid as $userid) {
                $gradinginfo = grade_get_grades( $this->vpl->get_course()->id, 'mod'
                                                , 'vpl', $this->vpl->get_instance()->id, $userid );
                if (! empty( $gradinginfo->outcomes )) {
                    $outcomes = [];
                    foreach (array_keys($gradinginfo->outcomes) as $oid) {
                        $field = 'outcome_grade_' . $oid;
                        if (isset( $info->$field )) {
                            $outcomes[$oid] = $info->$field;
                        } else {
                            $outcomes[$oid] = null;
                        }
                    }
                    $ret = grade_update_outcomes( 'mod/vpl'
                                           , $this->vpl->get_course()->id
                                           , 'mod'
                                           , VPL
                                           , $this->vpl->get_instance()->id
                                           , $userid, $outcomes );
                    if ( ! $ret ) {
                        return false;
                    }
                }
            }
        }
        if (! $DB->update_record( 'vpl_submissions', $this->instance )) {
            throw new moodle_exception( 'error:recordnotupdated', 'mod_vpl', '', VPL_SUBMISSIONS );
        }
        return true;
    }

    /**
     * Removes in title grade reduction if exists
     *
     * @param string title
     *
     * @return string
     */
    public static function remove_grade_reduction($title) {
        $regexp = '/^(-.*)(\([ \t]*-[ \t]*[0-9]*\.?[0-9]*[ \t]*\)[ \t]*)$/m';
        return preg_replace($regexp, '$1', $title);
    }

    /**
     * Get grade comments
     *
     * @param bool $forceremove True removes grade reduction information from titles
     *
     * @return string
     */
    public function get_grade_comments(bool $forceremove = false) {
        $ret = '';
        $fn = $this->get_gradecommentsfilename();
        if (file_exists( $fn )) {
            $ret = file_get_contents( $fn );
            // Remove grade reduction information from titles [-*(-#)].
            if ( $forceremove || ! $this->vpl->has_capability(VPL_GRADE_CAPABILITY) ) {
                $ret = self::remove_grade_reduction($ret);
            }
        }
        return $ret;
    }

    /**
     * is visible this submission instance?
     *
     * @return bool
     */
    public function is_visible() {
        global $USER;
        $instance = $this->instance;
        $ret = $this->vpl->is_visible();
        // Submission owner?
        $ret = $ret && ($USER->id == $instance->userid);
        if ($ret) {
            // Is last submission?
            $lastsub = $this->vpl->last_user_submission( $instance->userid );
            $ret = $ret && ($instance->id == $lastsub->id);
        }
        $ret = $ret || $this->vpl->has_capability( VPL_GRADE_CAPABILITY );
        return $ret;
    }

    /**
     * is possible to grade/update this submission instance
     *
     * @return bool
     */
    public function is_grade_able() {
        global $USER;
        if ($this->vpl->get_grade() == 0) { // Is grade_able the instance.
            return false;
        }
        $instance = $this->instance;
        $ret = $this->vpl->has_capability( VPL_GRADE_CAPABILITY );
        // New grade or update if grader.
        $ret = $ret && ($instance->dategraded || $USER->id == $instance->grader);
        $ret = $ret || $this->vpl->has_capability( VPL_MANAGE_CAPABILITY );
        if ($ret) {
            // Is last submission?
            $lastsub = $this->vpl->last_user_submission( $instance->userid );
            $ret = $ret && ($instance->id == $lastsub->id);
        }
        return $ret;
    }

    /**
     * Return a fake object for the automatic grader with standard user name fields.
     *
     * @return object with standard user name fields.
     */
    public static function get_automatic_grader() {
        $graderuser = new StdClass();
        // Polyfill version.
        if (method_exists('\core_user\fields', 'get_name_fields')) {
            foreach (\core_user\fields::get_name_fields() as $name) {
                $graderuser->$name = '';
            }
        } else { // Deprecated. To be removed in a future version.
            $funcname = 'get_all_user_name_fields';
            if (function_exists($funcname)) {
                foreach ($funcname() as $name) {
                    $graderuser->$name = '';
                }
            }
        }
        $graderuser->firstname = '';
        $graderuser->lastname = get_string( 'automaticgrading', VPL );
        return $graderuser;
    }

    /**
     *
     * @var array cache of users(graders) objects
     */
    protected static $graders = [];

    /**
     * Return user from DB with cache (automatic grader info for $id===0)
     *
     * @param $id Grader id (user id record) or 0 for automatic grader
     * @return false/user object
     */
    public static function get_grader($id) {
        global $DB;
        if ($id === null) {
            $id = 0;
        }
        if (isset( self::$graders[$id] )) {
            $graderuser = self::$graders[$id];
        } else {
            if ($id <= 0) { // Automatic grading.
                $graderuser = self::get_automatic_grader();
            } else {
                $graderuser = $DB->get_record('user', ['id' => $id]);
            }
            self::$graders[$id] = $graderuser;
        }
        return $graderuser;
    }

    /**
     * Get core grade @parm optional grade to show
     *
     * @return string
     */
    public function get_grade_core($grade = null) {
        global $CFG;
        $ret = '';
        $inst = $this->instance;
        if ($inst->dategraded > 0 || $grade != null) {
            $vplinstance = $this->vpl->get_instance();
            $scaleid = $this->vpl->get_grade();
            $options = [];
            if ($scaleid == 0) {
                return get_string( 'nograde' );
            } else if ($grade == null) {
                // If group activity don't retrieve grade from gradebook.
                if ( $this->vpl->is_group_activity() ) {
                    return format_float(floatval($this->get_instance()->grade), 2, true, true);
                }
                if (! function_exists( 'grade_get_grades' )) {
                    require_once($CFG->libdir . '/gradelib.php');
                }
                $gradeobj = $this->get_gradebook_grade();
                try {
                    $gradestr = $gradeobj->str_long_grade;
                    if ($this->vpl->has_capability(VPL_GRADE_CAPABILITY)) {
                        $gradestr .= $gradeobj->hidden ? (' <b>' . get_string( 'hidden', 'core_grades' )) . '</b>' : '';
                        $gradestr .= $gradeobj->locked ? (' <b>' . get_string( 'locked', 'core_grades' )) . '</b>' : '';
                        $gradestr .= $gradeobj->overridden ? (' <b>' . get_string( 'overridden', 'core_grades' )) . '</b>' : '';
                    }
                    return $gradestr;
                } catch (\Throwable $e) {
                    debugging( 'Error getting grade in html format ' . $e->getMessage(), DEBUG_DEVELOPER );
                }
            }
            if ($grade === null) {
                return '';
            }
            if ($scaleid > 0) {
                try {
                    $grade = format_float($this->reduce_grade($grade), 2, true, true);
                } catch (\Throwable $e) {
                    $strggrade = s($grade);
                    $grade = "Numeric grade format error: '$strggrade'";
                }
                $ret = $grade . ' / ' . $scaleid;
            } else if ($scaleid < 0) {
                $scaleid = - $scaleid;
                $grade = ( int ) $grade;
                if ($scale = $this->vpl->get_scale()) {
                    $options = [];
                    $options[- 1] = get_string( 'nograde' );
                    $options = $options + make_menu_from_list( $scale->scale );
                    if (isset( $options[$grade] )) {
                        $ret = $options[$grade];
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * Return processed comment
     *
     * @param string $title Title to show
     * @param string $comment Content to process
     * @param bool $empty Process empty comment
     * @return string
     */
    public function get_processed_comment($title, $comment, $empty = false) {
        GLOBAL $PAGE;
        $ret = '';
        if (strlen($comment) > 0 || $empty) {
            $div = new mod_vpl\util\hide_show( true );
            $ret = '<b>' . get_string( $title, VPL ) . $div->generate() . '</b><br>';
            $ret .= $div->content_in_div(s($comment));
            $PAGE->requires->js_call_amd('mod_vpl/vplutil', 'addResults', [$div->get_tag_id(), false, true]);
        }
        return $ret;
    }

    /**
     * Return sudmission detailed grade part in html format
     * @return string
     */
    public function get_detailed_grade($process = true) {
        GLOBAL $PAGE;
        $ret = $this->reduce_grade_string() . '<br>';
        $feedback = $this->get_grade_comments($process);
        $ret .= $this->get_processed_comment('gradercomments', $feedback);
        return $ret;
    }

    /**
     * Print sudmission grade
     *
     * @param boolean $detailed show detailed grade (default false)
     * @param boolean $return. Return string/ false print grade (default false)
     * @return string/void
     */
    public function print_grade($detailed = false, $return = false) {
        global $CFG, $OUTPUT, $PAGE;
        $ret = '';
        $inst = $this->instance;
        if ($inst->dategraded > 0) {
            $grader = $this->get_grader( $inst->grader );
            $a = new stdClass();
            $a->date = userdate( $inst->dategraded );
            $a->gradername = fullname( $grader );
            $ret .= get_string( 'gradedonby', VPL, $a ) . '<br>';
            if ($this->vpl->get_grade() != 0) {
                $ret .= $this->vpl->str_restriction('grade', $this->get_grade_core(), false, 'core') . '<br>';
                if ($detailed) {
                    $ret .= $this->get_detailed_grade();
                }
            }
            if (! empty( $CFG->enableoutcomes )) {
                // Bypass unknow gradelib not load.
                if (! function_exists( 'grade_get_grades' )) {
                    require_once($CFG->libdir . '/gradelib.php');
                }
                $gradinginfo = grade_get_grades( $this->vpl->get_course()->id, 'mod'
                                                 , 'vpl', $this->vpl->get_instance()->id
                                                 , $this->instance->userid );
                if (! empty( $gradinginfo->outcomes )) {
                    $ret .= '<b>' . get_string( 'outcomes', 'core_grades' ) . '</b><br>';
                    foreach ($gradinginfo->outcomes as $outcome) {
                        $ret .= s( $outcome->name );
                        $ret .= ' ' . s( $outcome->grades[$inst->userid]->str_grade ) . '<br>';
                    }
                }
            }
        }
        if ($return) {
            return $ret;
        } else {
            if ($ret) {
                echo $OUTPUT->box( $ret );
            }
        }
    }

    /**
     * Print sudmission info
     * @param boolean $autolink. Add links. default = false
     */
    public function print_info($autolink = false) {
        // TODO improve show submission info.
        global $OUTPUT, $DB;
        $id = $this->vpl->get_course_module()->id;
        $userid = $this->instance->userid;
        $submissionid = $this->instance->id;
        echo vpl_get_awesome_icon('duedate') . ' ';
        if ($autolink) {
            $url = vpl_mod_href( 'forms/submissionview.php', 'id', $id, 'userid', $userid, 'submissionid', $submissionid );
            echo '<a href="' . $url . '">';
        }
        $subdate = userdate( $this->instance->datesubmitted );
        p( get_string( 'submittedonp', VPL, $subdate ) );
        if ($autolink) {
            echo '</a>';
        }
        $url = vpl_mod_href( 'views/downloadsubmission.php', 'id', $id, 'userid', $userid, 'submissionid', $submissionid );
        echo ' (' . vpl_get_awesome_icon('download');
        echo '<a href="' . $url . '">' . get_string( 'download', VPL );
        echo '</a>)';
        // Show evaluation link.
        if ( ($this->vpl->get_instance()->evaluate && ! $this->is_graded()) ||
             $this->vpl->has_capability(VPL_GRADE_CAPABILITY)) {
            $url = vpl_mod_href( 'forms/evaluation.php', 'id', $id, 'userid', $userid );
            echo ' (' . vpl_get_awesome_icon('evaluate');
            echo '<a href="' . $url . '">' . get_string( 'evaluate', VPL );
            echo '</a>)';
        }
        echo '<br>';
        if ( $this->vpl->is_group_activity() ) {
            $user = $DB->get_record( 'user', [
                        'id' => $userid,
                ] );
            if ( $user ) {
                $userinfo = $OUTPUT->user_picture( $user ) . ' '  . fullname( $user );
                echo get_string( 'submittedby', VPL, $userinfo );
            }
            $users = $this->vpl->get_group_members($this->instance->groupid);
            foreach ($users as $u) {
                if ( $u->id != $userid ) {
                    echo '<br>';
                    break;
                }
            }
            $needbr = false;
            foreach ($users as $u) {
                if ( $u->id != $userid ) {
                    echo $OUTPUT->user_picture( $u ) . ' ' . fullname( $u );
                    $needbr = true;
                }
            }
            if ($needbr) {
                echo '<br>';
            }
        }
        $commmets = $this->instance->comments;
        if ($commmets > '') {
            echo '<br>';
            echo '<b>' . get_string( 'comments', VPL ) . '</b>';
            echo $OUTPUT->box( nl2br( s( $commmets ) ) );
        }
    }

    /**
     * Print compilation and execution
     *
     * @param bool $return True return string, false print string
     * @return string empty or html
     */
    public function print_ce($return = false) {
        global $OUTPUT, $PAGE;
        $ce = $this->getce();
        if ($ce['compilation'] === 0) {
            return '';
        }
        $ret = '';
        $compilation = '';
        $execution = '';
        $grade = '';
        $this->get_ce_html( $ce, $compilation, $execution, $grade, true, true );
        if (strlen( $compilation ) + strlen( $execution ) + strlen( $grade ) > 0) {
            $div = new mod_vpl\util\hide_show( ! $this->is_graded() || ! $this->vpl->get_visiblegrade() );
            $ret .= '<b>' . get_string( 'automaticevaluation', VPL ) . $div->generate() . '</b>';
            $ret .= $div->begin_div();
            $ret .= $OUTPUT->box_start();
            if (strlen( $grade ) > 0) {
                $ret .= '<b>' . $grade . '</b><br>';
                $ret .= $this->reduce_grade_string() . '<br>';
            }
            $compilation = $ce['compilation'];
            $ret .= $this->get_processed_comment('compilation', $compilation);
            if (strlen( $execution ) > 0) {
                $proposedcomments = $this->proposedcomment( $ce['execution'] );
                $ret .= $this->get_processed_comment( 'comments', $proposedcomments, true);
            }
            $ret .= $OUTPUT->box_end();
            $ret .= $div->end_div();
        }
        if ($return) {
            return $ret;
        } else {
            echo $ret;
            return '';
        }
    }

    /**
     * Print sudmission
     */
    public function print_submission() {
        $this->print_info();
        if ($this->vpl->has_capability( VPL_GRADE_CAPABILITY )) {
            $this->vpl->print_variation( $this->instance->userid );
        }
        // Not automatic graded show proposed evaluation.
        if (! $this->is_graded() || ! $this->vpl->get_visiblegrade() || $this->vpl->has_capability( VPL_GRADE_CAPABILITY )) {
            $this->print_CE();
        }
        $this->get_submitted_fgm()->print_files();
    }

    const GRADETAG = 'Grade :=>>';
    const COMMENTTAG = 'Comment :=>>';
    const BEGINCOMMENTTAG = '<|--';
    const ENDCOMMENTTAG = '--|>';

    public static function find_proposedgrade(&$text) {
        $reggrademark = '/^Grade :=>>(.*)$/m';
        $grademark = '';
        $offset = 0;
        while (true) {
            $matches = [];
            $res = preg_match($reggrademark, $text, $matches, PREG_OFFSET_CAPTURE, $offset);
            if ( $res == 1) {
                $grademark = trim($matches[1][0]);
                $offset = $matches[1][1];
            } else {
                break;
            }
        }
        return $grademark;
    }

    public function proposedgrade(&$text) {
        return self::find_proposedgrade($text);
    }

    public static function find_proposedcomment(&$text) {
        $usecrnl = vpl_detect_newline($text) == "\r\n";
        if ($usecrnl) {
            $startcommentreg = '/^(Comment :=>>([^\\r\\n]*)|<\\|--)\\r?$/m';
            $endcommentreg = '/^--\\|>\\r?$/m';
        } else {
            $startcommentreg = '/^(Comment :=>>(.*)|<\\|--)$/m';
            $endcommentreg = '/^--\\|>$/m';
        }
        $comments = '';
        $offset = 0;
        while (true) {
            $matches = [];
            $result = preg_match($startcommentreg, $text, $matches, PREG_OFFSET_CAPTURE, $offset);
            if ( $result == 1) {
                $found = $matches[1][0];
                if ( $found == self::BEGINCOMMENTTAG ) { // Block comment start.
                    $posstart = $matches[0][1] + strlen($matches[0][0]) + 1;
                    $result = preg_match($endcommentreg, $text, $matches, PREG_OFFSET_CAPTURE, $posstart);
                    if ($result == 1) { // Block comment end.
                        $offset = $matches[0][1];
                        $blockcomment = substr($text, $posstart, $offset - $posstart);
                        if ($usecrnl) {
                            $blockcomment = str_replace("\r\n", "\n", $blockcomment);
                        }
                        $comments .= $blockcomment;
                        $offset += strlen($matches[0][0]) + 1;
                    } else { // End of block comment not found.
                        $comments .= substr($text, $posstart);
                        break;
                    }
                } else {
                    $found = $matches[2][0] . "\n";
                    $comments .= $found;
                    $offset = $matches[0][1] + strlen($matches[0][0]) + 1;
                }
            } else {
                break;
            }
        }
        return $comments;
    }

    public function proposedcomment(&$text) {
        return self::find_proposedcomment($text);
    }

    /**
     * Add link to file line format filename:linenumber:
     *
     * @param string $text Text to be converted
     * @return string text with links
     */
    public function add_filelink($text) {
        // Format filename:linenumber.
        $ret = '';
        $list = $this->get_submitted_fgm()->getFileList();
        usort( $list, 'vpl_compare_filenamebylengh' );
        $nl = vpl_detect_newline( $text );
        $lines = explode( $nl, $text );
        // Prepare reg expressions.
        $regexps = [];
        foreach ($list as $filename) {
            $escapefilename = preg_quote( $filename, "/" );
            $regexps[] = '/(.*?)(' . $escapefilename . ')\:( *)([0-9]+)(.*)/';
        }
        // Process lines.
        foreach ($lines as $line) {
            foreach ($regexps as $regexp) {
                $r = [];
                if (preg_match( $regexp, $line, $r )) {
                    $line = $r[1] . '<a href="#' . $r[2] . '.' . $r[4] . '">' . $r[2] . ':' . $r[3] . $r[4] . '</a>' . $r[5];
                    break;
                }
            }
            $ret .= $line . "\n";
        }
        return $ret;
    }
    /**
     * Convert last comment compilation/execution result to HTML.
     *
     * @param string $title Title of comment.
     * @param string &$comment Comment to change.
     * @param bool $dropdown Show as dropdown or not.
     * @return string HTML.
     */
    private function get_last_comment($title, &$comment, $dropdown) {
        $html = '';
        if ($title > '') { // Previous comment.
            if ($comment == '' || ! $dropdown) {
                $html .= '<b>';
                $html .= s( $title );
                $html .= '</b><br>';
                $html .= $comment;
            } else {
                $div = new mod_vpl\util\hide_show( false );
                $html .= $div->generate( true );
                $html .= '<b>';
                $html .= s( $title );
                $html .= '</b><br>';
                $html .= $div->content_in_div($comment);
            }
        } else if ($comment > '') { // No title comment.
            $html .= $comment;
        }
        $comment = ''; // Reset comment.
        return $html;
    }
    /**
     * Convert compilation/execution result to HTML
     *
     * @param string $text to be converted
     * @param bool Show as dropdown or not.
     * @return string HTML
     */
    public function result_to_html($text, $dropdown = true) {
        if ($text == '' || $text == null) {
            return '';
        }
        $html = ''; // Total html output.
        $title = ''; // Title of comment.
        $comment = ''; // Comment.
        $nl = vpl_detect_newline( $text );
        $lines = explode( $nl, $text );
        $casetoshow = ''; // Pre to show.
        foreach ($lines as $line) {
            $clean = trim( $line );
            // End of case?
            if (strlen( $casetoshow ) > 0 && ! (strlen( $clean ) > 0 && $clean[0] == '>')) {
                $comment .= '<pre><i>';
                $comment .= s( $casetoshow );
                $comment .= '</i></pre>';
                $casetoshow = '';
            }
            // Is title line.
            if (strlen( $line ) > 1 && $line[0] == '-') { // Title.
                $html .= $this->get_last_comment( $title, $comment, $dropdown );
                $line = trim( substr( $line, 1 ) );
                if ($line[strlen( $line ) - 1] == ')') { // Has grade?
                    $posopen = strrpos( $line, '(' );
                    if ($posopen !== false) {
                        $grade = substr( $line, $posopen + 1, strlen( $line ) - $posopen - 2 );
                        $grade = trim( $grade );
                        if ($grade < 0) {
                            $title = substr( $line, 0, $posopen );
                            // TODO implement grader information.
                            continue;
                        }
                    }
                }
                $title = $line;
            } else if (strlen( $clean ) > 0 && $clean[0] == '>') { // Case.
                $pos = strpos( $line, '>' );
                $rest = substr( $line, $pos + 1 );
                $casetoshow .= $rest . "\n";
            } else if (strlen( $clean ) > 8 && (substr( $clean, 0, 5 ) == "http:" || substr( $clean, 0, 6 ) == "https:")) {
                // Is url?
                // Output spaces.
                $nspaces = strpos( $line, 'h' );
                for ($i = 0; $i < $nspaces; $i ++) {
                    $comment .= '&nbsp;';
                }
                $spacepos = strpos( $clean, ' ' );
                if ($spacepos) {
                    $comment .= '<a href="';
                    $comment .= urlencode( substr( $clean, 0, $spacepos ) );
                    $comment .= '">';
                    $comment .= s( substr( $clean, $spacepos + 1, strlen( $clean ) - $spacepos - 1 ) );
                    $comment .= '</a>';
                } else {
                    $comment .= '<a href="';
                    $comment .= urlencode( $clean );
                    $comment .= '">';
                    $comment .= s( $clean );
                    $comment .= '</a>';
                }
                $comment .= '<br>';
            } else { // Regular text.
                $comment .= $this->add_filelink(s($line)) . '<br>';
            }
        }
        if (strlen( $casetoshow ) > 0) {
            $comment .= '<pre><i>';
            $comment .= s( $casetoshow );
            $comment .= '</i></pre>';
        }
        $html .= $this->get_last_comment( $title, $comment, $dropdown );
        return $html;
    }
    /**
     * Add a new text to the list
     */
    public function filter_feedback_add(&$list, $text, $grade = 0) {
        $text = trim( $text );
        if (! isset( $list[$text] )) {
            $list[$text] = new StdClass();
            $list[$text]->count = 0;
            $list[$text]->grades = [];
        }
        $list[$text]->count ++;
        $list[$text]->grades[$grade] = true;
    }
    /**
     * Filter Convert compilation/execution result to HTML
     *
     * @param
     *            text to be filter
     * @return array of mensajes
     */
    public function filter_feedback(&$list) {
        $text = $this->get_grade_comments();
        $nl = vpl_detect_newline( $text );
        $lines = explode( $nl, $text );
        foreach ($lines as $line) {
            $line = rtrim( $line );
            // Is title line.
            if (strlen( $line ) > 2 && $line[0] == '-') { // Title.
                $line = substr( $line, 1 );
                if ($line[strlen( $line ) - 1] == ')') { // Has grade?
                    $posopen = strrpos( $line, '(' );
                    if ($posopen !== false) {
                        $grade = substr( $line, $posopen + 1, strlen( $line ) - $posopen - 2 );
                        $grade = trim( $grade );
                        if ($grade < 0) {
                            $this->filter_feedback_add( $list, substr( $line, 0, $posopen ), $grade );
                            continue;
                        }
                    }
                }
                $this->filter_feedback_add( $list, $line, 0 );
            }
        }
    }
    const COMPILATIONFN = 'compilation.txt';
    const EXECUTIONFN = 'execution.txt';

    /**
     * Save Compilation Execution result to files
     *
     * @param $result array
     *            response from server
     * @return void
     */
    public function savece($result) {
        global $DB;
        ignore_user_abort( true );
        $oldce = $this->getce();
        // Count new evaluaions.
        $newevaluation = false;
        if ( $oldce['executed'] == 0 && $result['executed'] > 0
             && ! $this->vpl->has_capability(VPL_GRADE_CAPABILITY) ) {
            $newevaluation = true;
        }
        // After first execution, keep execution state of the submission.
        if ( $oldce['executed'] > 0 && $result['executed'] == 0) {
            $result['executed'] = 1;
            $result['execution'] = '';
        }

        $compfn = $this->get_data_directory() . '/' . self::COMPILATIONFN;
        if (file_exists( $compfn )) {
            unlink( $compfn );
        }
        $execfn = $this->get_data_directory() . '/' . self::EXECUTIONFN;
        if (file_exists( $execfn )) {
            unlink( $execfn );
        }
        file_put_contents( $compfn, $result['compilation'] );
        if ($result['executed'] > 0) {
            file_put_contents( $execfn, $result['execution'] );
        }
        if ( $newevaluation ) {
            $this->instance->nevaluations ++;
            $DB->update_record(VPL_SUBMISSIONS, $this->instance);
        }
    }

    /**
     * Get Compilation Execution information from files
     *
     * @return array with server response fields
     */
    public function getce() {
        $ret = [];
        $compfn = $this->get_data_directory() . '/' . self::COMPILATIONFN;
        if (file_exists( $compfn )) {
            $ret['compilation'] = file_get_contents( $compfn );
        } else {
            $ret['compilation'] = 0;
        }
        $execfn = $this->get_data_directory() . '/' . self::EXECUTIONFN;
        if (file_exists( $execfn )) {
            $ret['executed'] = 1;
            $ret['execution'] = file_get_contents( $execfn );
        } else {
            $ret['executed'] = 0;
        }
        $ret['nevaluations'] = $this->instance->nevaluations;
        $vplinstance = $this->vpl->get_instance();
        $ret['freeevaluations'] = $vplinstance->freeevaluations;
        $ret['reductionbyevaluation'] = $vplinstance->reductionbyevaluation;
        return $ret;
    }

    /**
     * Get compilation, execution and proposed grade from array
     *
     * @param array $response Response from server
     * @param string &$compilation in HTML
     * @param string &$execution in HTML
     * @param string &$grade in HTML
     * @return void
     */
    public function get_ce_html($response, &$compilation, &$execution, &$grade, $dropdown, $returnrawexecution = false) {
        $compilation = '';
        $execution = '';
        $grade = '';
        if ($response['compilation']) {
            $compilation = $this->result_to_html( $response['compilation'], $dropdown );
            if (strlen( $compilation )) {
                $compilation = '<b>' . get_string( 'compilation', VPL ) . '</b><br>' . $compilation;
            }
        }
        if ($response['executed'] > 0) {
            $rawexecution = $response['execution'];
            $proposedcomments = $this->proposedcomment( $rawexecution );
            $proposedgrade = $this->proposedgrade( $rawexecution );
            $execution = $this->result_to_html( $proposedcomments, $dropdown );
            if (strlen( $execution )) {
                $execution = '<b>' . get_string( 'comments', VPL ) . "</b><br>\n" . $execution;
            }
            if (strlen( $proposedgrade )) {
                $sgrade = $this->get_grade_core( $proposedgrade );
                $grade = get_string( 'proposedgrade', VPL, $sgrade );
            }
            // Show raw ejecution if no grade or comments.
            if (strlen( $rawexecution ) > 0 && (strlen( $execution ) + strlen( $proposedgrade ) == 0)) {
                $execution .= "<br>\n";
                $execution .= '<b>' . get_string( 'execution', VPL ) . "</b><br>\n";
                $execution .= '<pre>' . s( $rawexecution ) . '</pre>';
            } else if ($returnrawexecution && strlen( $rawexecution ) > 0
                       && ($this->vpl->has_capability( VPL_MANAGE_CAPABILITY ))) {
                // Show raw ejecution if manager and $returnrawexecution.
                $div = new mod_vpl\util\hide_show();
                $execution .= "<br>\n";
                $execution .= '<b>' . get_string( 'execution', VPL ) . $div->generate() . "</b><br>\n";
                $execution .= $div->content_in_div('<pre>' . s( $rawexecution ) . '</pre>');
            }
        }
    }
    public function get_ce_for_editor($response = null) {
        $ce = new stdClass();
        $ce->compilation = '';
        $ce->evaluation = '';
        $ce->execution = '';
        $ce->grade = '';
        $ce->nevaluations = $this->instance->nevaluations;
        $vplinstance = $this->vpl->get_instance();
        $ce->freeevaluations = $vplinstance->freeevaluations;
        $ce->reductionbyevaluation = $vplinstance->reductionbyevaluation;

        if ($response == null) {
            $response = $this->getce();
        }
        if ($response['compilation']) {
            $ce->compilation = $response['compilation'];
        }
        if ($response['executed'] > 0) {
            $rawexecution = $response['execution'];
            $evaluation = $this->proposedcomment( $rawexecution );
            $proposedgrade = $this->proposedgrade( $rawexecution );
            $ce->evaluation = $evaluation;
            if (strlen( $proposedgrade ) && $this->vpl->get_instance()->grade) {
                $sgrade = $this->get_grade_core( $proposedgrade );
                $ce->grade = get_string( 'proposedgrade', VPL, $sgrade );
            }
            // Show raw ejecution if no grade or comments.
            $manager = $this->vpl->has_capability( VPL_MANAGE_CAPABILITY );
            if ((strlen( $rawexecution ) > 0 && (strlen( $evaluation ) + strlen( $proposedgrade ) == 0)) || $manager) {
                $ce->execution = $rawexecution;
            }
        }
        return $ce;
    }
    public function get_detail() {
        $ret = '';
        $subf = $this->get_submitted_fgm();
        $filelist = $subf->getFileList();
        foreach ($filelist as $filename) {
            $data = $subf->getFileData( $filename );
            if ($ret > '') {
                $ret .= ', ';
            }
            // TODO too slow calculus.
            $nl = vpl_detect_newline( $data );
            $ret .= $filename . ' ' . strlen( $data ) . 'b ' . count( explode( $nl, $data ) ) . 'l';
        }
        return $ret;
    }
}
