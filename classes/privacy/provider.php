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
 * Privacy subsystem implementation for mod_vpl.
 * Note: this software has been developed to comply with the
 * General Data Protection Regulation (GDPR 2018) of the EU.
 * This code has been based on the code of Moodle Assignment Module.
 *
 * @package mod_vpl
 * @copyright 2020 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\content_writer;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;

/**
 * VPL provider class
 *
 */

class provider implements \core_privacy\local\metadata\provider,
                          \core_privacy\local\request\user_preference_provider,
                          \core_privacy\local\request\core_userlist_provider {

    /**
     * Return the fields and user preferences which are considered personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection): collection {
        $vplfields = [
            'id' => 'privacy:metadata:vpl:id',
            'course' => 'privacy:metadata:vpl:course',
            'name' => 'privacy:metadata:vpl:name',
            'shortdescription' => 'privacy:metadata:vpl:shortdescription',
            'startdate' => 'privacy:metadata:vpl:startdate',
            'duedate' => 'privacy:metadata:vpl:duedate',
            'grade' => 'privacy:metadata:vpl:grade',
            'reductionbyevaluation' => 'privacy:metadata:vpl:reductionbyevaluation',
            'freeevaluations' => 'privacy:metadata:vpl:freeevaluations',
        ];
        $submisionsfields = [
            'userid' => 'privacy:metadata:vpl_submissions:userid',
            'groupid' => 'privacy:metadata:vpl_submissions:groupid',
            'datesubmitted' => 'privacy:metadata:vpl_submissions:datesubmitted',
            'comments' => 'privacy:metadata:vpl_submissions:studentcomments',
            'nevaluations' => 'privacy:metadata:vpl_submissions:nevaluations',
            'dategraded' => 'privacy:metadata:vpl_submissions:dategraded',
            'grade' => 'privacy:metadata:vpl_submissions:grade',
            'grader' => 'privacy:metadata:vpl_submissions:graderid',
            'gradercomments' => 'privacy:metadata:vpl_submissions:gradercomments',
        ];
        $variationsfields = [
            'userid' => 'privacy:metadata:vpl_assigned_variations:userid',
            'vpl' => 'privacy:metadata:vpl_assigned_variations:vplid',
            'variation' => 'privacy:metadata:vpl_assigned_variations:description',
        ];
        $overridesfields = [
            'vpl' => 'privacy:metadata:vpl_assigned_overrides:vplid',
            'userid' => 'privacy:metadata:vpl_assigned_overrides:userid',
            'override' => 'privacy:metadata:vpl_assigned_overrides:overrideid',
        ];
        $runningfields = [
            'userid' => 'privacy:metadata:vpl_running_processes:userid',
            'vpl' => 'privacy:metadata:vpl_running_processes:vplid',
            'server' => 'privacy:metadata:vpl_running_processes:server',
            'start_time' => 'privacy:metadata:vpl_running_processes:starttime',
        ];

        $collection->add_database_table('vpl', $vplfields, 'privacy:metadata:vpl');
        $collection->add_database_table('vpl_submissions', $submisionsfields, 'privacy:metadata:vpl_submissions');
        $collection->add_database_table('vpl_assigned_variations', $variationsfields, 'privacy:metadata:vpl_assigned_variations');
        $collection->add_database_table('vpl_assigned_overrides', $overridesfields, 'privacy:metadata:vpl_assigned_overrides');
        $collection->add_database_table('vpl_running_processes', $runningfields, 'privacy:metadata:vpl_running_processes');
        // IDE user preferences.
        $collection->add_user_preference('vpl_editor_fontsize', 'privacy:metadata:vpl_editor_fontsize');
        $collection->add_user_preference('vpl_acetheme', 'privacy:metadata:vpl_acetheme');
        $collection->add_user_preference('vpl_terminaltheme', 'privacy:metadata:vpl_terminaltheme');

        return $collection;
    }


    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        self::add_contexts_for_submissions($contextlist, $userid);
        self::add_contexts_for_evaluations($contextlist, $userid);
        self::add_contexts_for_variations($contextlist, $userid);
        self::add_contexts_for_overrides($contextlist, $userid);
        self::add_contexts_for_running($contextlist, $userid);

        return $contextlist;
    }


    /**
     * Export personal data for the given approved_contextlist.
     * User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        if (empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            $vplinstance = self::get_vpl_by_context($context);
            if ($vplinstance === null) {
                continue;
            }
            $vplid = $vplinstance->id;
            $contentwriter = writer::with_context($context);
            self::export_vpl_data($contentwriter, $vplinstance);
            self::export_user_assigned_variation_data($contentwriter, $vplid, $userid);
            self::export_user_assigned_override_data($contentwriter, $vplid, $userid);
            self::export_user_submissions_data($contentwriter, $vplid, $userid);
            self::export_user_running_processes_data($contentwriter, $vplid, $userid);
        }
    }

    /**
     * Export vpl description for related personal data.
     *
     * @param content_writer $contentwriter data writer object.
     * @param object $vplinstance vpl instance
     */
    public static function export_vpl_data(content_writer $contentwriter, $vplinstance) {
            // Get vpl details object for output.
            $vploutput = self::get_vpl_output($vplinstance);
            $contentwriter->export_data([], $vploutput);
    }
    /**
     * Export variation for related personal data.
     *
     * @param content_writer $contentwriter data writer object.
     * @param int $vplid vpl DB id
     * @param int $userid user DB id
     */
    public static function export_user_assigned_variation_data(content_writer $contentwriter, int $vplid, int $userid) {
        // Get assigned variation related to the user if any.
        $variation = self::get_assigned_variation_by_vpl_and_user($vplid, $userid);
        if (count($variation) == 1) {
            $variationoutput = self::get_vpl_assigned_variation_output(reset($variation));
            $contentwriter->export_data([get_string('privacy:variationpath', 'vpl')], $variationoutput);
        }
    }

    /**
     * Export override for related personal data.
     *
     * @param content_writer $contentwriter data writer object.
     * @param int $vplid vpl DB id
     * @param int $userid user DB id
     */
    public static function export_user_assigned_override_data(content_writer $contentwriter, int $vplid, int $userid) {
        // Get assigned override related to the user if any.
        $override = self::get_assigned_override_by_vpl_and_user($vplid, $userid);
        if (count($override) == 1) {
            $overrideoutput = self::get_vpl_assigned_override_output(reset($override));
            $contentwriter->export_data([get_string('privacy:overridepath', 'vpl')], $overrideoutput);
        }
    }

    /**
     * Export submissions personal data.
     *
     * @param content_writer $contentwriter data writer object.
     * @param int $vplid vpl DB id
     * @param int $userid user DB id
     */
    public static function export_user_submissions_data(content_writer $contentwriter, int $vplid, int $userid) {
        // Get the vpl submissions related to the user.
        $vpl = new \mod_vpl(false, $vplid);
        $submissions = self::get_vpl_submissions_by_vpl_and_user($vplid, $userid);
        $zipfilename = get_string('submission', 'vpl') . '.zip';
        $subcontextsecuence = 1;
        foreach ($submissions as $submissioninstance) {
            $subcontext = [ get_string('privacy:submissionpath', 'vpl', $subcontextsecuence++) ];
            $submission = new \mod_vpl_submission_CE($vpl, $submissioninstance);
            $nograder = $submissioninstance->userid == $userid;
            if ( $submissioninstance->dategraded > 0) {
                $submissioninstance->gradercomments = $submission->get_grade_comments($nograder);
                if ( $nograder ) {
                    unset($submissioninstance->grader);
                } else {
                    unset($submissioninstance->userid);
                }
            }
            $dataoutput = self::get_vpl_submission_output($submissioninstance);
            $contentwriter->export_data($subcontext, $dataoutput);
            if ($nograder) {
                $tempfilename = $submission->get_submitted_fgm()->generate_zip_file();
                if ($tempfilename !== false) {
                    $filecontent = file_get_contents($tempfilename);
                    $contentwriter->export_custom_file($subcontext, $zipfilename, $filecontent);
                    unlink($tempfilename);
                }
            }
        }
    }

    /**
     * Export running processes personal data.
     *
     * @param content_writer $contentwriter data writer object.
     * @param int $vplid vpl DB id
     * @param int $userid user DB id
     */
    public static function export_user_running_processes_data(content_writer $contentwriter, int $vplid, int $userid) {
        // Get the vpl submissions related to the user.
        $runningprocesses = self::get_running_processes_by_vpl_and_user($vplid, $userid);
        $subcontextsecuence = 1;
        foreach ($runningprocesses as $runningprocess) {
            $subcontext = [ get_string('privacy:runningprocesspath', 'vpl', $subcontextsecuence++) ];
            $dataoutput = self::get_vpl_running_process_output($runningprocess);
            $contentwriter->export_data($subcontext, $dataoutput);
        }
    }

    /**
     * Exports user preferences of mod_vpl.
     *
     * @param int $userid The userid of the user to export preferences
     */
    public static function export_user_preferences(int $userid) {
        $context = \context_system::instance();

        $preferences = self::get_user_preferences($userid);
        foreach ($preferences as $key => $value) {
            $str = get_string('privacy:metadata:' . $key, 'mod_vpl');
            writer::with_context($context)->export_user_preference('mod_vpl', $key, $value, $str);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if ($context->contextlevel == CONTEXT_MODULE) {
            // Delete all submissions and relate data for the VPL associated with the context module.
            $vplinstance = self::get_vpl_by_context($context);
            if ($vplinstance != null) {
                vpl_reset_instance_userdata($vplinstance->id);
            }
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB, $CFG;

        if ($contextlist->count() === 0) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        // For each context retrieve VPL and remove user submissions and related directories.
        $submissions = self::get_vpl_submissions_by_contextlist($contextlist, $userid);
        // Submisions ids of the $userid.
        $submissionids = [];
        // Submisions ids of evaluations of the $userid.
        $evaluationids = [];
        // VPL ids of the submisisions to delete.
        $vplids = [];
        foreach ($submissions as $submission) {
            if ($submission->userid == $userid) {
                $submissionids[] = $submission->id;
                $vplids[$submission->vpl] = true;
            }
            if ($submission->grader == $userid) {
                $evaluationids[] = $submission->id;
            }
        }

        // Delete submissions.
        $DB->delete_records_list('vpl_submissions', 'id', $submissionids);
        foreach (array_keys($vplids) as $vplid) {
            fulldelete( $CFG->dataroot . '/vpl_data/'. $vplid . '/usersdata/' . $userid );
        }

        // Change grader to 0.
        if (count($evaluationids) > 0) {
            list($insql, $inparams) = $DB->get_in_or_equal($evaluationids);
            $sql = "UPDATE {vpl_submissions}
                       SET grader = 0
                     WHERE id $insql";
            $DB->execute($sql, $inparams);
        }

        // Delete asigned variations.
        self::delete_assigned_variations_by_contextlist($contextlist, $userid);

        // Delete asigned overrides.
        self::delete_assigned_overrides_by_contextlist($contextlist, $userid);

        // Delete running processes.
        self::delete_running_processes_by_contextlist($contextlist, $userid);
    }

    /**
     * Update userlist context with all user who hold any personal data in a specific context.
     *
     * @param userlist $userlist List of user to update.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if (!($context instanceof \context_module)) {
            return;
        }

        $params = [
            'instanceid' => $context->instanceid,
            'modulename' => 'vpl',
        ];

        // Submissions.
        $sql = "SELECT DISTINCT s.userid
                  FROM {vpl_submissions} s
                  JOIN {course_modules} cm ON s.vpl = cm.instance
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.id = :instanceid AND m.name = :modulename";
        $userlist->add_from_sql('userid', $sql, $params);

        // Graders.
        $sql = "SELECT DISTINCT s.grader
                  FROM {vpl_submissions} s
                  JOIN {course_modules} cm ON s.vpl = cm.instance
                  JOIN {modules} m ON m.id = cm.module
                 WHERE s.grader > 0 AND cm.id = :instanceid AND m.name = :modulename";
        $userlist->add_from_sql('grader', $sql, $params);

        // Variations assigned.
        $sql = "SELECT DISTINCT av.userid
                  FROM {vpl_assigned_variations} av
                  JOIN {course_modules} cm ON av.vpl = cm.instance
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.id = :instanceid AND m.name = :modulename";
        $userlist->add_from_sql('userid', $sql, $params);

        // Assigned overrides.
        $sql = "SELECT DISTINCT ao.userid
                  FROM {vpl_assigned_overrides} ao
                  JOIN {course_modules} cm ON ao.vpl = cm.instance
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.id = :instanceid AND m.name = :modulename";
        $userlist->add_from_sql('userid', $sql, $params);

        // Running process.
        $sql = "SELECT DISTINCT rp.userid
                  FROM {vpl_running_processes} rp
                  JOIN {course_modules} cm ON rp.vpl = cm.instance
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.id = :instanceid AND m.name = :modulename";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB, $CFG;

        if ($userlist->count() === 0) {
            return;
        }

        $vplinstace = self::get_vpl_by_context($userlist->get_context());
        if ($vplinstace === null) {
            return;
        }
        $vplid = $vplinstace->id;
        $userids = $userlist->get_userids();

        $params = [
            'vplid' => $vplid,
        ];
        // Get sql partial where of users ids.
        list($userssql, $usersparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Delete selected submissions.
        $sql = "DELETE
                  FROM {vpl_submissions}
                 WHERE vpl = :vplid AND userid {$userssql}";
        $DB->execute($sql, $params + $usersparams);
        foreach ($userids as $userid) {
            fulldelete( $CFG->dataroot . '/vpl_data/'. $vplid . '/usersdata/' . $userid );
        }
        // Anonymizes graders identification.
        $sql = "UPDATE {vpl_submissions}
                   SET grader = 0
                 WHERE vpl = :vplid AND grader {$userssql}";
        $DB->execute($sql, $params + $usersparams);
        // Delete related assigned variations.
        $sql = "DELETE
                  FROM {vpl_assigned_variations}
                 WHERE vpl = :vplid AND userid {$userssql}";
        $DB->execute($sql, $params + $usersparams);
        // Delete related assigned overrides.
        $sql = "DELETE
                  FROM {vpl_assigned_overrides}
                 WHERE vpl = :vplid AND userid {$userssql}";
        $DB->execute($sql, $params + $usersparams);
        // Delete related running processes.
        $sql = "DELETE
                  FROM {vpl_running_processes}
                 WHERE vpl = :vplid AND userid {$userssql}";
        $DB->execute($sql, $params + $usersparams);
    }

    // Start of helper functions.

    /**
     * Adds contexts of submissions of the specified user.
     *
     * @param contextlist $list the list of context.
     * @param int $userid the userid.
     * @return void.
     */
    protected static function add_contexts_for_submissions(contextlist $list, int $userid): void {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {vpl_submissions} s ON s.vpl = cm.instance
                 WHERE s.userid = :userid";

        $params = [
            'contextmodule' => CONTEXT_MODULE,
            'modulename'    => 'vpl',
            'userid'       => $userid,
        ];

        $list->add_from_sql($sql, $params);
    }

    /**
     * Adds contexts of evaluations of the specified user.
     *
     * @param contextlist $list the list of context.
     * @param int $userid the userid.
     * @return void.
     */
    protected static function add_contexts_for_evaluations(contextlist $list, int $userid): void {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {vpl_submissions} s ON s.vpl = cm.instance
                 WHERE s.grader = :userid";

        $params = [
            'contextmodule' => CONTEXT_MODULE,
            'modulename'    => 'vpl',
            'userid'        => $userid,
        ];

        $list->add_from_sql($sql, $params);
    }

    /**
     * Adds contexts of assigned variations to the specified user.
     *
     * @param contextlist $list the list of context.
     * @param int $userid the userid.
     * @return void.
     */
    protected static function add_contexts_for_variations(contextlist $list, int $userid): void {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {vpl_assigned_variations} va ON va.vpl = cm.instance
                 WHERE va.userid = :userid";

        $params = [
            'contextmodule' => CONTEXT_MODULE,
            'modulename'    => 'vpl',
            'userid'        => $userid,
        ];

        $list->add_from_sql($sql, $params);
    }

    /**
     * Add contexts for assigned overrides to the specified user.
     *
     * @param contextlist $list the list of context.
     * @param int $userid the userid.
     * @return void.
     */
    protected static function add_contexts_for_overrides(contextlist $list, int $userid) : void {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {vpl_assigned_overrides} ao ON ao.vpl = cm.instance
                 WHERE ao.userid = :userid";

        $params = [
            'contextmodule' => CONTEXT_MODULE,
            'modulename'    => 'vpl',
            'userid'        => $userid,
        ];

        $list->add_from_sql($sql, $params);
    }

    /**
     * Adds contexts of running process for the specified user.
     *
     * @param contextlist $list the list of context.
     * @param int $userid the userid.
     * @return void.
     */
    protected static function add_contexts_for_running(contextlist $list, int $userid): void {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {vpl_running_processes} rp ON rp.vpl = cm.instance
                 WHERE rp.userid = :userid";

        $params = [
            'contextmodule' => CONTEXT_MODULE,
            'modulename'    => 'vpl',
            'userid'        => $userid,
        ];

        $list->add_from_sql($sql, $params);
    }

    /**
     * Returns preference key => value for the user
     *
     * @param int $userid The userid of the preferences to return
     */
    protected static function get_user_preferences(int $userid): array {
        $pref = array();
        $preferences = ['vpl_editor_fontsize', 'vpl_acetheme', 'vpl_terminaltheme'];
        foreach ($preferences as $key) {
            $value = get_user_preferences($key, null, $userid);
            if (isset($value)) {
                $pref[$key] = $value;
            }
        }
        return $pref;
    }

    /**
     * Return VPL instance for a context module.
     *
     * @param object $context The context module object of the VPL to return.
     * @return mixed The vpl record associated with the context module or null if not found.
     * @throws \dml_exception
     */
    protected static function get_vpl_by_context($context) {
        global $DB;

        $params = [
            'modulename' => 'vpl',
            'contextmodule' => CONTEXT_MODULE,
            'coursemoduleid' => $context->instanceid
        ];
        $sql = "SELECT v.*
                  FROM {vpl} v
                  JOIN {course_modules} cm ON v.id = cm.instance
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.id = :coursemoduleid AND m.name = :modulename";
        return $DB->get_record_sql($sql, $params);
    }

    /**
     * Return VPL submissions submitted or graded by the user in the contextlist.
     *
     * @param object $contextlist Object with the contexts related to a userid.
     * @param int $userid The user ID to find vpl submissions that were submitted by.
     * @return array Array of vpl submission details.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected static function get_vpl_submissions_by_contextlist($contextlist, $userid) {
        global $DB;
        // Get sql partial where of contexts.
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $params = [
            'contextmodule' => CONTEXT_MODULE,
            'modulename' => 'vpl',
            'userid' => $userid,
            'grader' => $userid,
        ];

        $sql = " SELECT s.id, s.vpl, s.userid, s.grader
                   FROM {vpl_submissions} s
                   JOIN {vpl} v ON v.id = s.vpl
                   JOIN {course_modules} cm ON cm.instance = v.id
                   JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                   JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  WHERE (s.userid = :userid OR s.grader = :grader) AND ctx.id {$contextsql}";
        $params += $contextparams;
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Delete the assigned variations for the user and their contextlist.
     *
     * @param object $contextlist Object with the contexts related to a userid.
     * @param int $userid The user ID.
     */
    protected static function delete_assigned_variations_by_contextlist($contextlist, $userid) {
        global $DB;
        // Get sql partial where of contexts.
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $params = [
            'contextmodule' => CONTEXT_MODULE,
            'modulename' => 'vpl',
            'userid' => $userid,
        ];

        $sql = "DELETE
                  FROM {vpl_assigned_variations}
                 WHERE userid = :userid AND
                          vpl IN (
                       SELECT cm.instance
                         FROM {course_modules} cm
                         JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                         JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                        WHERE ctx.id {$contextsql} )";
        $params += $contextparams;
        $DB->execute($sql, $params);
    }

    /**
     * Delete the assigned overrides for the user and their contextlist.
     *
     * @param object $contextlist Object with the contexts related to a userid.
     * @param int $userid The user ID.
     */
    protected static function delete_assigned_overrides_by_contextlist($contextlist, $userid) {
        global $DB;
        // Get sql partial where of contexts.
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $params = [
            'contextmodule' => CONTEXT_MODULE,
            'modulename' => 'vpl',
            'userid' => $userid,
        ];

        $sql = "DELETE
                  FROM {vpl_assigned_overrides}
                 WHERE userid = :userid AND
                          vpl IN (
                       SELECT cm.instance
                         FROM {course_modules} cm
                         JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                         JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                        WHERE ctx.id {$contextsql} )";
        $params += $contextparams;
        $DB->execute($sql, $params);
    }

    /**
     * Delete running processes for the user and their contextlist.
     *
     * @param object $contextlist Object with the contexts related to a userid.
     * @param int $userid The user ID.
     */
    protected static function delete_running_processes_by_contextlist($contextlist, $userid) {
        global $DB;
        // Get sql partial where of contexts.
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $params = [
            'contextmodule' => CONTEXT_MODULE,
            'modulename' => 'vpl',
            'userid' => $userid,
        ];

        $sql = "DELETE
                  FROM {vpl_running_processes}
                 WHERE userid = :userid AND
                          vpl IN (
                       SELECT cm.instance
                         FROM {course_modules} cm
                         JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                         JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                        WHERE ctx.id {$contextsql} )";
        $params += $contextparams;
        $DB->execute($sql, $params);
    }

    /**
     * Helper function to retrieve vpl submissions related with user (submitted or grader).
     *
     * @param int $vplid The vpl id to retrieve submissions.
     * @param int $userid The user id to retrieve vpl submissions submitted or graded by.
     * @return array Array of vpl submissions details.
     * @throws \dml_exception
     */
    protected static function get_vpl_submissions_by_vpl_and_user($vplid, $userid) {
        global $DB;

        $params = [
            'vplid' => $vplid,
            'userid' => $userid,
            'graderid' => $userid
        ];

        $sql = "SELECT *
                  FROM {vpl_submissions} s
                 WHERE s.vpl = :vplid AND (s.userid = :userid OR s.grader = :graderid)
                 ORDER BY s.id";
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Helper function to retrieve assigned variation related with user.
     *
     * @param int $vplid The vpl id to retrieve variation.
     * @param int $userid The user id to retrieve assigned variation.
     * @return array Array of assigned variation details.
     * @throws \dml_exception
     */
    protected static function get_assigned_variation_by_vpl_and_user($vplid, $userid) {
        global $DB;
        $params = [
            'vplid' => $vplid,
            'userid' => $userid,
        ];
        $sql = "SELECT *
                  FROM {vpl_variations} v
                  JOIN {vpl_assigned_variations} av ON v.id = av.variation
                 WHERE av.vpl = :vplid AND av.userid = :userid";
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Helper function to retrieve assigned override related with user.
     *
     * @param int $vplid The vpl id to retrieve override.
     * @param int $userid The user id to retrieve assigned override.
     * @return array Array of assigned override details.
     * @throws \dml_exception
     */
    protected static function get_assigned_override_by_vpl_and_user($vplid, $userid) {
        global $DB;
        $params = [
            'vplid' => $vplid,
            'userid' => $userid,
        ];
        $sql = "SELECT ao.id as aoid, ao.userid, o.*
                    FROM {vpl_assigned_overrides} ao
                    JOIN {vpl_overrides} o ON ao.override = o.id
                    WHERE ao.vpl = :vplid AND ao.userid = :userid";
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Helper function to retrieve running processes of a user.
     *
     * @param int $vplid The vpl id to retrieve running processes.
     * @param int $userid The user id to retrieve running processes.
     * @return array Array of running processes details.
     * @throws \dml_exception
     */
    protected static function get_running_processes_by_vpl_and_user($vplid, $userid) {
        global $DB;
        $params = [
            'vplid' => $vplid,
            'userid' => $userid,
        ];
        $sql = "SELECT *
                  FROM {vpl_running_processes} rp
                 WHERE rp.vpl = :vplid AND rp.userid = :userid";
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Helper function to copy object fields
     *
     * @param object $from  Object containing data.
     * @param object $to    Object to modify.
     * @param array  $fiels List of fields to copy.
     * @return void
     */
    protected static function copy_fields($from, $to, $fields) {
        foreach ($fields as $field) {
            if (isset($from->$field)) {
                $to->$field = $from->$field;
            }
        }
    }

    /**
     * Helper function to copy and convert object date fields
     *
     * @param object $from  Object containing data.
     * @param object $to    Object to modify.
     * @param array  $fiels List of fields to copy.
     * @return void
     */
    protected static function copy_date_fields($from, $to, $datefields) {
        foreach ($datefields as $field) {
            if (isset($from->$field) && $from->$field > 0) {
                $to->$field = transform::datetime($from->$field);
            }
        }
    }
    /**
     * Helper function generate vpl output object for exporting.
     *
     * @param object $vplinstance Object containing vpl record.
     * @return object Formatted vpl output object for exporting.
     */
    protected static function get_vpl_output($vpldata) {
        $vpl = new \stdClass;
        $fields = ['id', 'course', 'name', 'shortdescription'];
        $datefields = ['startdate', 'duedate'];
        self::copy_fields($vpldata, $vpl, $fields);
        if ($vpldata->grade != 0) { // If 0 then NO GRADE.
            if ($vpldata->grade > 0) {
                $vpl->grade = get_string('grademax', 'core_grades')
                . ': ' . format_float($vpldata->grade, 5, true, true);
            } else {
                $vpl->grade = get_string( 'typescale', 'core_grades' );
            }
            if ($vpldata->reductionbyevaluation != 0) { // If penalizaions for automatic evaluation requests.
                $vpl->reductionbyevaluation = $vpldata->reductionbyevaluation;
                $vpl->freeevaluations = $vpldata->freeevaluations;
            }
        } else {
            $vpl->grade = get_string('nograde');
        }
        self::copy_date_fields($vpldata, $vpl, $datefields);
        return $vpl;
    }

    /**
     * Helper function generate vpl submission output object for exporting.
     *
     * @param object $submission Object containing an instance record of vpl submission.
     * @return object Formatted vpl submission output for exporting.
     */
    protected static function get_vpl_submission_output($submission) {
        $subfields = ['userid', 'groupid', 'comments', 'nevaluations'];
        $gradefields = ['grade', 'gradercomments'];
        $datefields = ['datesubmitted', 'dategraded'];
        $data = new \stdClass();
        self::copy_fields($submission, $data, $subfields);
        self::copy_date_fields($submission, $data, $datefields);
        if ($submission->dategraded > 0) {
            self::copy_fields($submission, $data, $gradefields);
        }
        return $data;
    }
    /**
     * Helper function generate assigned variation output object for exporting.
     *
     * @param object $assignedvariation Object containing an instance record of assigned variation.
     * @return object Formatted assigned variation output for exporting.
     */
    protected static function get_vpl_assigned_variation_output($assignedvariation) {
        $fields = ['userid', 'vpl'];
        $data = new \stdClass();
        self::copy_fields($assignedvariation, $data, $fields);
        $data->variation = $assignedvariation->description;
        return $data;
    }

    /**
     * Helper function generate assigned override output object for exporting.
     *
     * @param object $assignedoverride Object containing an instance record of assigned override.
     * @return object Formatted assigned override output for exporting.
     */
    protected static function get_vpl_assigned_override_output($assignedoverride) {
        $fields = ['userid', 'vpl', 'reductionbyevaluation', 'freeevaluations'];
        $datefields = ['startdate', 'duedate'];
        $data = new \stdClass();
        self::copy_fields($assignedoverride, $data, $fields);
        self::copy_date_fields($assignedoverride, $data, $datefields);
        return $data;
    }

    /**
     * Helper function generate running process output object for exporting.
     *
     * @param object $assignedvariation Object containing an instance record of running process.
     * @return object Formatted running process output for exporting.
     */
    protected static function get_vpl_running_process_output($runningprocess) {
        $fields = ['userid', 'vpl', 'server'];
        $data = new \stdClass();
        self::copy_fields($runningprocess, $data, $fields);
        self::copy_date_fields($runningprocess, $data, ['start_time']);
        $data->server = parse_url($data->server, PHP_URL_HOST);
        return $data;
    }
}
