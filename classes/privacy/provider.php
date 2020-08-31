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
 * @author Juan Carlos Rodr�guez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;

defined( 'MOODLE_INTERNAL' ) || die();

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
    public static function get_metadata(collection $collection) : collection {
        $vplfields = [
            'name' => 'privacy:metadata:vpl:name',
        ];
        $submisionsfields = [
            'vpl' => 'privacy:metadata:vpl_assigned_variations:vplid',
            'userid' => 'privacy:metadata:vpl_submissions:userid',
            'groupid' => 'privacy:metadata:vpl_submissions:groupid',
            'datesubmitted' => 'privacy:metadata:vpl_submissions:datesubmitted',
            'comments' => 'privacy:metadata:vpl_submissions:studentcomments',
            'dategraded' => 'privacy:metadata:vpl_submissions:dategraded',
            'grade' => 'privacy:metadata:vpl_submissions:grade',
            'nevaluations' => 'privacy:metadata:vpl_submissions:nevaluations',
            'submission' => 'privacy:metadata:vpl_submissions:filessubmitted',
            'gradercomments' => 'privacy:metadata:vpl_submissions:gradercomments',
        ];
        $evaluationfields = [
            'vpl' => 'privacy:metadata:vpl_assigned_variations:vplid',
            'grader' => 'privacy:metadata:vpl_submissions:graderid',
            'datesubmitted' => 'privacy:metadata:vpl_submissions:datesubmitted',
            'dategraded' => 'privacy:metadata:vpl_submissions:dategraded',
            'grade' => 'privacy:metadata:vpl_submissions:grade',
            'gradercomments' => 'privacy:metadata:vpl_submissions:gradercomments',
        ];
        $variationsfields = [
            'userid' => 'privacy:metadata:vpl_assigned_variations:userid',
            'vpl' => 'privacy:metadata:vpl_assigned_variations:vplid',
            'variation' => 'privacy:metadata:vpl_assigned_variations:variationdescription',
        ];
        $collection->add_database_table('vpl', $vplfields, 'privacy:metadata:vpl');
        $collection->add_database_table('vpl_submissions', $submisionsfields, 'privacy:metadata:vpl_submissions');
        $collection->add_database_table('vpl_evaluations', $evaluationfields, 'privacy:metadata:vpl_evaluations');
        $collection->add_database_table('vpl_assigned_variations', $variationsfields, 'privacy:metadata:vpl_assigned_variations');
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
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        self::add_contexts_for_submissions($contextlist, $userid);
        self::add_contexts_for_evaluations($contextlist, $userid);
        self::add_contexts_for_variations($contextlist, $userid);

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

            $vpl = self::get_vpl_by_context($context);
            if ($vpl === null) {
                continue;
            }

            $contentwriter = writer::with_context($context);

            // Get vpl details object for output.
            $vploutput = self::get_vpl_output($vpl);
            $contentwriter->export_data([], $vploutput);

            // Get the vpl submissions of a vpl related to a user.
            $submissions = self::get_vpl_submissions_by_vpl_and_user($vpl->id, $userid);

            foreach ($submissions as $submission) {
                if ($submission->userid == $userid) {
                    $subcontexts = [
                        $submission->id
                    ];
                    $dataoutput = self::get_vpl_submission_output($submission);
                    $contentwriter->export_data($subcontexts, $dataoutput);
                    // TODO read files
                    $files = [];
                    foreach ($files as $filename => $filecontent) {
                        $contentwriter->export_custom_file($subcontexts, $filename, $filecontent);
                    }
                }
                if ($submission->grader == $userid) {
                    $subcontexts = [
                        $submission->id
                    ];
                    $dataoutput = self::get_vpl_evaluation_output($submission);
                    writer::with_context($context)->export_data($subcontexts, $dataoutput);
                }
            }
        }
    }

    /**
     * Exports user preferences of mod_vpl.
     *
     * @param int $userid The userid of the user to export preferences
     */
    public static function export_user_preferences(int $userid) {
        $context = \context_system::instance();

        $preferences = ['vpl_editor_fontsize', 'vpl_acetheme', 'vpl_terminaltheme'];
        foreach ($preferences as $key) {
            $value = get_user_preferences($key, null, $userid);
            if (isset($value)) {
                $str = get_string('privacy:metadata:' . $key, 'mod_vpl');
                writer::with_context($context)
                    ->export_user_preference('mod_vpl', $key, $value, $str);
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context):void {
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

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        // For each context retrieve VPL and remove user submissions and related directories.
        $submissions = self::get_vpl_submissions_by_contextlist($contextlist, $userid);
        // Submisions ids of the $userid.
        $submissionids = [];
        // Submisions ids of evaluations of the $userid.
        $evaluationids = [];
        // VPL ids of the submisisions to delete
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

        // Delete submissions
        $DB->delete_records_list('vpl_submissions', 'id', $submissionids);
        foreach (array_keys($vplids) as $vplid) {
            fulldelete( $CFG->dataroot . '/vpl_data/'. $vplid . '/usersdata/' . $userid );
        }

        // Change grader as 0
        if (count($evaluationids) > 0) {
            list($insql, $inparams) = $DB->get_in_or_equal($evaluationids);
            $sql = "UPDATE {vpl_submissions} s
                        SET s.grader = 0
                        WHERE s.id $insql;";
            $DB->execute($sql, $inparams);
        }
    }

    /**
     * Update userlist context with all user who hold any personal data in a specific context.
     *
     * @param userlist $context context of VPL instance.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if (!($context instanceof \context_module)) {
            return;
        }

        $params = [
                'instanceid'    => $context->instanceid,
                'modulename'    => 'vpl',
        ];

        // Submissions.
        $sql = "SELECT DISTINCT s.userid
                   FROM {vpl_submissions} s
                   JOIN {course_modules} cm ON s.vpl = cm.instance
                   JOIN {modules} m ON m.id = cm.module
                   WHERE cm.id = :instanceid AND m.name = :modulename;";
        $userlist->add_from_sql('userid', $sql, $params);

        // Graders.
        $sql = "SELECT DISTINCT s.grader
                    FROM {vpl_submissions} s
                    JOIN {course_modules} cm ON s.vpl = cm.instance
                    JOIN {modules} m ON m.id = cm.module
                    WHERE s.grader > 0 AND cm.id = :instanceid AND m.name = :modulename;";
        $userlist->add_from_sql('grader', $sql, $params);

        // Variations assigned.
        $sql = "SELECT DISTINCT av.userid
                    FROM {vpl_assigned_variations} av
                    JOIN {course_modules} cm ON av.vpl = cm.instance
                    JOIN {modules} m ON m.id = cm.module
                    WHERE cm.id = :instanceid AND m.name = :modulename;";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist):void {
        global $DB, $CFG;

        $context = $userlist->get_context();
        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);

        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);

        $params = array_merge(['chatid' => $chat->id], $userinparams);
        $sql = "chatid = :chatid AND userid {$userinsql}";

        // For each context retrieve VPL and remove user submissions and related directories.
        $submissions = self::get_vpl_submissions_by_contextlist($contextlist, $userid);
        // Submisions ids of the $userid.
        $submissionids = [];
        // Submisions ids of evaluations of the $userid.
        $evaluationids = [];
        // VPL ids of the submisisions to delete
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

        // Delete submissions
        $DB->delete_records_list('vpl_submissions', 'id', $submissionids);
        foreach (array_keys($vplids) as $vplid) {
            fulldelete( $CFG->dataroot . '/vpl_data/'. $vplid . '/usersdata/' . $userid );
        }

        // Change grader as 0
        if (count($evaluationids) > 0) {
            list($insql, $inparams) = $DB->get_in_or_equal($evaluationids);
            $sql = "UPDATE {vpl_submissions} s
                        SET s.grader = 0
                        WHERE s.id $insql;";
            $DB->execute($sql, $inparams);
        }
    }

    // Start of helper functions.

    /**
     * Add contexts for submissions of the specified user.
     *
     * @param contextlist $list the list of context.
     * @param int $userid the userid.
     * @return void.
     */
    protected static function add_contexts_for_submissions(contextlist $list, int $userid) : void {
        $sql = "SELECT DISTINCT
                       ctx.id
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
     * Add contexts for evaluations of the specified user.
     *
     * @param contextlist $list the list of context.
     * @param int $userid the userid.
     * @return void.
     */
    protected static function add_contexts_for_evaluations(contextlist $list, int $userid) : void {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {vpl_submissions} s ON s.vpl = cm.instance
                  WHERE s.grader = :userid";

        $params = [
                'contextmodule' => CONTEXT_MODULE,
                'modulename'    => 'vpl',
                'userid'       => $userid,
        ];

        $list->add_from_sql($sql, $params);
    }

    /**
     * Add contexts for assigned variations to the specified user.
     *
     * @param contextlist $list the list of context.
     * @param int $userid the userid.
     * @return void.
     */
    protected static function add_contexts_for_variations(contextlist $list, int $userid) : void {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {vpl_assigned_variations} va ON va.vpl = cm.instance
                  WHERE va.userid = :userid";

        $params = [
                'contextmodule' => CONTEXT_MODULE,
                'modulename'    => 'vpl',
                'userid'       => $userid,
        ];

        $list->add_from_sql($sql, $params);
    }

    /**
     * Return if a user has graded submissions for a given VPL activity.
     *
     * @param int $vplid The id of the VPL to check.
     * @param int $userid The id of the user.
     * @return bool If user has graded submissions returns true, otherwise false.
     * @throws \dml_exception
     */
    protected static function has_graded_vpl_submissions($vplid, $userid) {
        global $DB;
        if ( $userid < 1 ) {
            return false;
        }
        $params = [
                'vpl' => $vplid,
                'grader' => $userid
        ];
        $marks = $DB->count_records('vpl_submissions', $params);
        return $marks > 0;
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

        $sql = "SELECT *
                  FROM {vpl} v
                  JOIN {course_modules} cm ON v.id = cm.instance AND cm.id = :coursemoduleid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename;";

        return $DB->get_record_sql($sql, $params);
    }

    /**
     * Return VPL submissions submitted or graded by a user and their contextlist.
     *
     * @param object $contextlist Object with the contexts related to a userid to retrieve vpl submissions by.
     * @param int $userid The user ID to find vpl submissions that were submitted by.
     * @return array Array of vpl submission details.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected static function get_vpl_submissions_by_contextlist($contextlist, $userid) {
        global $DB;
//TODO Finish
        // Get vplids for submissions search.
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $params = [
            'contextmodule' => CONTEXT_MODULE,
            'modulename' => 'vpl',
        ];

        $sql = "SELECT vpl.id as id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {vpl} vpl ON cm.instance = a.id";
        $sql .= " WHERE ctx.id {$contextsql}";
        $params += $contextparams;
        $vplids = $DB->get_records_sql($sql, $params);
        if ($teacher == true) {
            $sql .= " OR s.teacher = :teacher";
            $params['teacher'] = $userid;
        }
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Helper function to retrieve vpl submissions related with user (submitted or graded).
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
                  WHERE s.vpl = :vplid
                        AND (s.userid = :userid OR s.grader = :grader)";
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Helper function generate vpl output object for exporting.
     *
     * @param object $vplinstance Object containing vpl record.
     * @return object Formatted vpl output object for exporting.
     */
    protected static function get_vpl_output($vpldata) {
        $vpl = (object) [
            'name' => $vpldata->name,
        ];
        return $vpl;
    }

    /**
     * Helper function generate vpl submission output object for exporting.
     *
     * @param object $submission Object containing an instance record of vpl submission.
     * @return object Formatted vpl submission output for exporting.
     */
    protected static function get_vpl_submission_output($submission) {
    }
    /**
     * Helper function generate vpl evaluation output object for exporting.
     *
     * @param object $submission Object containing an instance record of vpl submission.
     * @return object Formatted vpl evaluation output for exporting.
     */
    protected static function get_vpl_evaluation_output($submission) {
    }
}
