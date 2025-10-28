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
 * Functions to coordinate with moodle
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/list_util.class.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Creates/updates grade item for given VPL activity.
 * (Code and comments adapted from Moodle assign)
 *
 * @param stdClass $instance VPL record with extra cmidnumber
 * @param Array    $grades   Optional array/object of grade(s);
 *                           'reset' means reset grades in gradebook
 *
 * @return int 0 if ok, error code otherwise
 */
function vpl_grade_item_update($instance, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = [];
    $params['itemname'] = $instance->name;
    $params['hidden'] = ($instance->visiblegrade > 0) ? 0 : 1;
    if (isset($instance->cmidnumber)) {
        $params['idnumber'] = $instance->cmidnumber;
    }
    if ($instance->grade == 0 || $instance->example != 0) {
        $params['gradetype'] = GRADE_TYPE_NONE;
        $params['deleted'] = true;
    } else if ($instance->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $instance->grade;
        $params['grademin']  = 0;
    } else {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$instance->grade;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update(
        'mod/vpl',
        $instance->course,
        'mod',
        VPL,
        $instance->id,
        0,
        $grades,
        $params
    );
}

/**
 * Updates activity grades.
 * API and comment taken from Moodle assign.
 *
 * @param stdClass $instance   of VPL database record
 * @param int      $userid     specific user only, 0 means all
 * @param bool     $nullifnone - not used
 */
function vpl_update_grades($instance, $userid = 0, $nullifnone = true) {
    global $CFG, $USER;
    require_once($CFG->libdir . '/gradelib.php');
    require_once(dirname(__FILE__) . '/vpl_submission_CE.class.php');

    if (! isset($instance->cmidnumber)) {
        if ($cm = get_coursemodule_from_id(VPL, $instance->coursemodule)) {
            $instance = clone $instance;
            $instance->cmidnumber = $cm->idnumber;
        }
    }

    if ($instance->grade == 0) {
        return vpl_grade_item_update($instance);
    } else if ($userid == 0) {
        $vpl = new mod_vpl(false, $instance->id);
        $subs = $vpl->all_last_user_submission();
    } else {
        $vpl = new mod_vpl(false, $instance->id);
        $sub = $vpl->last_user_submission($userid);
        if ($sub === false) {
            $subs = [];
        } else {
            $subs = [$sub];
        }
    }
    $grades = [];
    foreach ($subs as $sub) {
        if ($sub->dategraded > 0) {
            $subc = new mod_vpl_submission_CE($vpl, $sub);
            $feedback = $subc->result_to_html($subc->get_grade_comments(), false);
            $grade = new stdClass();
            $grade->userid = $sub->userid;
            $grade->rawgrade = $subc->reduce_grade($sub->grade);
            $grade->feedback = $feedback;
            $grade->feedbackformat = FORMAT_HTML;
            if ($sub->grader > 0) {
                $grade->usermodified = $sub->grader;
            } else {
                $grade->usermodified = $USER->id;
            }
            $grade->dategraded = $sub->dategraded;
            $grade->datesubmitted = $sub->datesubmitted;
            $grades[$grade->userid] = $grade;
        }
    }
    vpl_grade_item_update($instance, $grades);
}
/**
 * Deletes grade_item from a vpl instance+id
 *
 * @param Object $instance of vpl DB with id
 */
function vpl_delete_grade_item($instance) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $itemdetails = [ 'deleted' => 1 ];
    grade_update('mod/vpl', $instance->course, 'mod', VPL, $instance->id, 0, null, $itemdetails);
}
/**
 * Creates an event object from a vpl instance+id
 *
 * @param stdClass $instance of vpl DB record
 * @param int $id vpl DB record id
 * @return Object with event information
 */
function vpl_create_event($instance, $id) {
    $event = new stdClass();
    $event->eventtype = VPL_EVENT_TYPE_DUE;
    $event->type = CALENDAR_EVENT_TYPE_ACTION;
    $event->name = get_string('dueevent', VPL, $instance->name);
    $event->description = $instance->shortdescription;
    $event->format = FORMAT_PLAIN;
    $event->courseid = $instance->course;
    $event->groupid = 0;
    $event->userid = 0;
    $event->modulename = VPL;
    $event->instance = $id;
    $event->timestart = $instance->duedate;
    $event->timesort = $instance->duedate;
    $event->timeduration = 0;
    $event->priority = null;
    return $event;
}

/**
 * Callback function to determine if the event is visible for the $userid or current user.
 *
 * @param calendar_event $event
 * @param int $userid optional.
 * @return bool Returns true if the event is visible, false if not visible.
 */
function mod_vpl_core_calendar_is_event_visible(calendar_event $event, $userid = false) {
    $vpl = new mod_vpl(null, $event->instance);
    return $vpl->is_visible($userid);
}

/**
 * Callback function to set the event action if available.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory objet to generate the action
 * @param int $userid (optional) User id for checking capabilities, etc.
 * @return \core_calendar\action_factory|null The action object or null
 */
function mod_vpl_core_calendar_provide_event_action(
    calendar_event $event,
    \core_calendar\action_factory $factory,
    $userid = 0
) {
    global $USER;
    if ($userid == 0) {
        $userid = $USER->id;
    }
    $vpl = new mod_vpl(null, $event->instance);
    $vplduedate = $vpl->get_effective_setting('duedate', $userid);
    $showdue = 60 * 60 * 12; // Half day.
    if ($vplduedate > 0 && (($vplduedate + $showdue) < time())) {
        return null;
    }
    if ($vpl->is_visible($userid)) {
        if ($vpl->has_capability(VPL_GRADE_CAPABILITY)) {
            $text = get_string('submissionslist', VPL);
            $cmid = $vpl->get_course_module()->id;
            $link = new \moodle_url('/mod/vpl/views/submissionslist.php', ['id' => $cmid]);
            return $factory->create_instance($text, $link, 1, true);
        } else {
            if ($vpl->last_user_submission($userid) !== false) {
                return null;
            } else {
                $text = get_string('dueeventaction', VPL);
                $cmid = $vpl->get_course_module()->id;
                $link = new \moodle_url('/mod/vpl/forms/edit.php', ['id' => $cmid]);
                return $factory->create_instance($text, $link, 1, $vpl->is_submit_able());
            }
        }
    } else {
        return null;
    }
}

/**
 * Callback function to know if the event must show its item count.
 *
 * @param calendar_event $event
 * @param int $itemcount item count.
 * @return bool True if the event must show the item count.
 */
function mod_vpl_core_calendar_event_action_shows_item_count(
    calendar_event $event,
    int $itemcount = 0
) {
    return $itemcount < 0; // Must always return false.
}

/**
 * Callback to fetch the activity event type lang string.
 *
 * @param string $eventtype The event type.
 * @return lang_string The event type lang string.
 */
function mod_vpl_core_calendar_get_event_action_string(string $eventtype): string {
    if ($eventtype == VPL_EVENT_TYPE_DUE) {
        return get_string('calendardue', VPL);
    } else { // Must be an event of type submission expected on.
        return get_string('calendarexpectedon', VPL);
    }
}


/**
 * Adds a new vpl instance and return the id
 *
 * @param Object $instance from the form in mod_form
 * @return int id of the new vpl
 */
function vpl_add_instance($instance) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/calendar/lib.php');
    vpl_truncate_vpl($instance);
    $id = $DB->insert_record(VPL, $instance);
    // Add event.
    if ($instance->duedate) {
        calendar_event::create(vpl_create_event($instance, $id), false);
    }
    // Add grade to grade book.
    $instance->id = $id;
    vpl_grade_item_update($instance);
    if (!empty($instance->completionexpected)) {
        $cmid = $instance->coursemodule;
        $completionexpected = $instance->completionexpected;
        \core_completion\api::update_completion_date_event($cmid, 'vpl', $instance, $completionexpected);
    }
    return $id;
}

/**
 * Updates a vpl instance event.
 *
 * @param object $instance VPL DB record
 * @return void
 */
function vpl_update_instance_event($instance): void {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/calendar/lib.php');
    $event = vpl_create_event($instance, $instance->id);
    $searchfields = [
        'modulename' => VPL,
        'instance' => $instance->id,
        'eventtype' => VPL_EVENT_TYPE_DUE,
        'priority' => null,
    ];
    if ($eventid = $DB->get_field('event', 'id', $searchfields)) {
        $event->id = $eventid;
        $calendarevent = \calendar_event::load($eventid);
        if ($instance->duedate) {
            $calendarevent->update($event, false);
        } else {
            $calendarevent->delete();
        }
    } else {
        if ($instance->duedate) {
            \calendar_event::create($event, false);
        }
    }
}

/**
 * Updates a vpl instance
 *
 * @param object $instance from the form in mod.html
 * @return boolean True if updated, false if not found
 */
function vpl_update_instance($instance) {
    global $DB;
    vpl_truncate_vpl($instance);
    $instance->id = $instance->instance;
    vpl_update_instance_event($instance);
    $cm = get_coursemodule_from_instance(VPL, $instance->id, $instance->course);
    if (!isset($instance->cmidnumber)) {
        $instance->cmidnumber = $cm->idnumber;
    }
    vpl_grade_item_update($instance);
    $completionexpected = (!empty($instance->completionexpected)) ? $instance->completionexpected : null;
    \core_completion\api::update_completion_date_event($cm->id, 'vpl', $instance, $completionexpected);
    return $DB->update_record(VPL, $instance);
}

/**
 * Deletes an instance by id
 *
 * @param int $id instance Id
 * @return boolean True if deleted, false if not found
 */
function vpl_delete_instance($id) {
    global $DB, $CFG;

    $instance = $DB->get_record(VPL, ["id" => $id]);
    if ($instance === false) {
        return false;
    }

    // Delete all data files.
    vpl_delete_dir($CFG->dataroot . '/vpl_data/' . $id);

    // Delete grade_item.
    vpl_delete_grade_item($instance);

    // Delete relate event.
    $DB->delete_records('event', ['modulename' => VPL, 'instance' => $id]);

    // Delete all related records.
    $tables = [
            VPL_SUBMISSIONS,
            VPL_VARIATIONS,
            VPL_ASSIGNED_VARIATIONS,
            VPL_OVERRIDES,
            VPL_ASSIGNED_OVERRIDES,
    ];
    foreach ($tables as $table) {
        $DB->delete_records($table, ['vpl' => $id]);
    }

    // Delete vpl record.
    $DB->delete_records(VPL, ['id' => $id]);
    mod_vpl::reset_db_cache(VPL, $id);
    return true;
}

/**
 * Returns if VPL support the requested feature.
 * @param string $feature FEATURE_* constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function vpl_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_ADVANCED_GRADING:
            return true;
        case FEATURE_CONTROLS_GRADE_VISIBILITY:
            return true;
        default:
            if (defined('FEATURE_MOD_PURPOSE')) {
                if ($feature == FEATURE_MOD_PURPOSE) {
                    return MOD_PURPOSE_ASSESSMENT;
                }
            }
            return null;
    }
}

/**
 * Return object with information of what a user has done in a VPL instance.
 *
 * Returns an object with time and info properties if the user has submitted,
 * null otherwise.
 *
 * @param stdClass $course Course object
 * @param stdClass $user User object
 * @param stdClass $mod Course module object
 * @param stdClass $instance VPL instance object
 * @return stdClass|null Returns an info object
 */
function vpl_user_outline($course, $user, $mod, $instance) {
    // Search submisions for $user $instance.
    $vpl = new mod_vpl(null, $instance->id);
    $subinstance = $vpl->last_user_submission($user->id);
    if (! $subinstance) {
        $return = null;
    } else {
        require_once('vpl_submission.class.php');
        $return = new stdClass();
        $submission = new mod_vpl_submission($vpl, $subinstance);
        $return->time = $subinstance->datesubmitted;
        $subs = $vpl->user_submissions($user->id);
        if (count($subs) > 1) {
            $info = get_string('nsubmissions', VPL, count($subs));
        } else {
            $info = get_string('submission', VPL, count($subs));
        }
        if ($subinstance->dategraded) {
            $info .= '<br>' . get_string(vpl_get_gradenoun_str()) . ': ' . $submission->get_grade_core();
        }
        $url = vpl_mod_href('forms/submissionview.php', 'id', $vpl->get_course_module()->id, 'userid', $user->id);
        $return->info = '<a href="' . $url . '">' . $info . '</a>';
    }
    return $return;
}

/**
 * Prints a detailed report of what a user has done in a VPL instance.
 *
 * @param stdClass $course Course object
 * @param stdClass $user User object
 * @param stdClass $mod Course module object
 * @param stdClass $vpl VPL instance object
 */
function vpl_user_complete($course, $user, $mod, $vpl) {
    require_once('vpl_submission.class.php');
    // TODO Print a detailed report of what a user has done with a given particular instance.
    // Search submisions for $user $instance.
    $vpl = new mod_vpl(null, $vpl->id);
    $sub = $vpl->last_user_submission($user->id);
    if ($sub !== false) {
        require_once(dirname(__FILE__) . '/vpl_submission_CE.class.php');
        $submission = new mod_vpl_submission($vpl, $sub);
        $submission->print_info(true);
        $submission->print_grade(true);
    }
}

/**
 * Returns all VPL submissions since a given time
 *
 * @param array $activities Array to append activities to
 * @param int $index Current index in the activities array
 * @param int $timestart Timestamp to start from
 * @param int $courseid Course ID
 * @param int $cmid Course module ID
 * @param int $userid User ID (0 for all users)
 * @param int $groupid Group ID (0 for all groups)
 * @return bool True if activities were found, false otherwise
 * @codeCoverageIgnore
 */
function vpl_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0) {
    global $CFG, $USER, $DB;
    $grader = false;
    $vpl = new mod_vpl($cmid);
    $modinfo = get_fast_modinfo($vpl->get_course());
    $cm = $modinfo->get_cm($cmid);
    $vplid = $vpl->get_instance()->id;
    $grader = $vpl->has_capability(VPL_GRADE_CAPABILITY);
    if (! $vpl->is_visible() && ! $grader) {
        return false; // No activity if not visible and not grader.
    }
    $select = 'select * from {vpl_submissions} subs';
    $where = ' where (subs.vpl = :vplid) and ((subs.datesubmitted >= :timestartsub) or (subs.dategraded >= :timestartgrade))';
    $parms = [ 'vplid' => $vplid, 'timestartsub' => $timestart, 'timestartgrade' => $timestart];
    if (! $grader || ($userid != 0)) { // User activity.
        if (! $grader) {
            $userid = $USER->id;
        }
        $parms['userid'] = $userid;
        $where .= ' and (subs.userid = :userid)';
    }
    if ($groupid != 0) { // Group activity.
        $parms['groupid'] = $groupid;
        $select .= ' join {groups_members} gm on gm.userid=subs.userid ';
        $where .= ' and gm.groupid = :groupid';
    }
    $where .= ' order by subs.datesubmitted DESC';
    $subs = $DB->get_records_sql($select . $where, $parms);
    if ($grader) {
        require_once($CFG->libdir . '/gradelib.php');
        $userids = [];
        foreach ($subs as $sub) {
            $userids[] = $sub->userid;
        }
        $grades = grade_get_grades($courseid, 'mod', 'vpl', $cm->instance, $userids);
    }

    $aname = format_string($vpl->get_printable_name(), true);
    foreach ($subs as $sub) { // Show recent activity.
        $activity = new stdClass();
        $activity->type = 'vpl';
        $activity->cmid = $cm->id;
        $activity->name = $aname;
        $activity->sectionnum = $cm->sectionnum;
        $activity->timestamp = $sub->datesubmitted;
        if ($grader && isset($grades->items[0]) && isset($grades->items[0]->grades[$sub->userid])) {
            $activity->grade = $grades->items[0]->grades[$sub->userid]->str_long_grade;
        }
        $activity->user = $DB->get_record('user', [ 'id' => $sub->userid ]);
        $activities[$index++] = $activity;
    }
    return true;
}

/**
 * Prints recent activity for a VPL module.
 *
 * @param stdClass $activity Activity object containing user, type, cmid, name, sectionnum, timestamp, and grade.
 * @param int $courseid Course ID.
 * @param bool $detail Whether to show detailed information.
 * @param array $modnames Array of module names indexed by type.
 * @param bool $viewfullnames Whether to show full names of users.
 * @return void
 * @codeCoverageIgnore
 */
function vpl_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
    // TODO improve.
    global $CFG, $OUTPUT;
    echo '<table border="0" cellpadding="3" cellspacing="0" class="vpl-recent">';
    echo '<tr><td class="userpicture" valign="top">';
    echo $OUTPUT->user_picture($activity->user);
    echo '</td><td>';
    if ($detail) {
        $modname = $modnames[$activity->type];
        echo '<div class="title">';
        echo $OUTPUT->image_icon('icon', $modname, VPL);
        echo '<a href="' . $CFG->wwwroot . '/mod/vpl/view.php?id=' . $activity->cmid . '">';
        echo $activity->name;
        echo '</a>';
        echo '</div>';
    }
    if (isset($activity->grade)) {
        echo '<div class="grade">';
        echo get_string('gradedon', 'core_grades', $activity->grade);
        echo '</div>';
    }
    echo '<div class="user">';
    $fullname = fullname($activity->user, $viewfullnames);
    echo "<a href=\"{$CFG->wwwroot}/user/view.php?id={$activity->user->id}&amp;course=$courseid\">" . "{$fullname}</a> - ";
    $link = vpl_mod_href('forms/submissionview.php', 'id', $activity->cmid, 'userid', $activity->user->id, 'inpopup', 1);
    echo '<a href="' . $link . '">' . userdate($activity->timestamp) . '</a>';
    echo '</div>';
    echo "</td></tr></table>";
}

/**
 * Get icon mapping for font-awesome.
 *
 * @return array
 * @codeCoverageIgnore
 */
function mod_vpl_get_fontawesome_icon_map() {
    return [
            'mod_vpl:testcases' => 'fa-check-square-o',
            'mod_vpl:basic' => 'fa-cog',
            'mod_vpl:test' => 'fa-flask',
            'mod_vpl:executionoptions' => 'fa-sliders',
            'mod_vpl:requestedfiles' => 'fa-shield',
            'mod_vpl:maxresourcelimits' => 'fa-tachometer',
            'mod_vpl:resourcelimits' => 'fa-tachometer',
            'mod_vpl:executionfiles' => 'fa-code',
            'mod_vpl:local_jail_servers' => 'fa-server',
            'mod_vpl:check_jail_servers' => 'fa-rocket',
            'mod_vpl:variations' => 'fa-random',
            'mod_vpl:overrides' => 'fa-unlock-alt',
            'mod_vpl:keepfiles' => 'fa-link',
            'mod_vpl:advancedsettings' => 'fa-cogs',
            'mod_vpl:submission' => 'fa-cloud-upload',
            'mod_vpl:submissionview' => 'fa-archive',
            'mod_vpl:edit' => 'fa-code',
            'mod_vpl:evaluate' => 'fa-check-square-o',
            'mod_vpl:calculate' => 'fa-calculator',
            'mod_vpl:comments' => 'fa-align-left',
            'mod_vpl:startdate' => 'fa-calendar-plus-o',
            'mod_vpl:duedate' => 'fa-calendar-check-o',
            'mod_vpl:password' => 'fa-lock',
            'mod_vpl:restrictededitor' => 'fa-ban',
            'mod_vpl:maxfilesize' => 'fa-tachometer',
            'mod_vpl:visible' => 'fa-eye',
            'mod_vpl:hidden' => 'fa-eye-slash',
            'mod_vpl:locked' => 'fa-lock',
            'mod_vpl:basedon' => 'fa-level-up',
            'mod_vpl:maxexetime' => 'fa-clock-o',
            'mod_vpl:maxexememory' => 'fa-microchip',
            'mod_vpl:maxexefilesize' => 'fa-tachometer',
            'mod_vpl:maxexeprocesses' => 'fa-microchip',
            'mod_vpl:maxfiles' => 'fa-files-o',
            'mod_vpl:run' => 'fa-rocket',
            'mod_vpl:debug' => 'fa-bug',
            'mod_vpl:grade' => 'fa-check-circle',
            'mod_vpl:gradenoun' => 'fa-check-circle',
            'mod_vpl:previoussubmissionslist' => 'fa-history',
            'mod_vpl:modulenameplural' => 'fa-list-ul',
            'mod_vpl:checkgroups' => 'fa-group',
            'mod_vpl:description' => 'fa-tasks',
            'mod_vpl:similarity' => 'fa-binoculars',
            'mod_vpl:submissionslist' => 'fa-list-ul',
            'mod_vpl:loading' => 'fa-spinner fa-pulse',
            'mod_vpl:copy' => 'fa-copy',
            'mod_vpl:submissions' => 'fa-bar-chart',
            'mod_vpl:gradercomments' => 'fa-check-square',
            'mod_vpl:download' => 'fa-download',
            'mod_vpl:downloadsubmissions' => 'fa-cloud-download',
            'mod_vpl:downloadallsubmissions' => 'fa-history',
            'mod_vpl:user' => 'fa-user',
            'mod_vpl:group' => 'fa-group',
            'mod_vpl:save' => 'fa-save',
            'mod_vpl:cancel' => 'fa-remove',
            'mod_vpl:delete' => 'fa-trash',
            'mod_vpl:editthis' => 'fa-edit',
            'mod_vpl:exitrole' => 'fa-close',
    ];
}


/**
 * Creates e new navigation node with icon
 *
 * @param navigation_node $vplnode
 * @param string $str string to be i18n
 * @param moodle_url $url
 * @param navigation_node::TYPE $type
 * @param string $comp component by default VPL
 * @return navigation_node
 */
function vpl_navi_node_create(navigation_node $vplnode, $str, $url, $type = navigation_node::NODETYPE_LEAF, $comp = 'mod_vpl') {
    $stri18n = get_string($str, $comp);
    $node = $vplnode->create($stri18n, $url, $type, null, null, new pix_icon($str, '', 'mod_vpl'));
    if ($type == navigation_node::NODETYPE_BRANCH) {
        $node->collapse = true;
        $node->forceopen = false;
    }
    $node->mainnavonly = true;
    return $node;
}

/**
 * Extends the navigation for VPL module.
 *
 * @param navigation_node $vplnode
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 * @codeCoverageIgnore
 */
function vpl_extend_navigation(navigation_node $vplnode, $course, $module, $cm) {
    global $USER;
    $vpl = new mod_vpl($cm->id);
    $viewer = $vpl->has_capability(VPL_VIEW_CAPABILITY);
    $submiter = $vpl->has_capability(VPL_SUBMIT_CAPABILITY);
    $similarity = $vpl->has_capability(VPL_SIMILARITY_CAPABILITY);
    $grader = $vpl->has_capability(VPL_GRADE_CAPABILITY);
    $manager = $vpl->has_capability(VPL_MANAGE_CAPABILITY);
    $userid = optional_param('userid', false, PARAM_INT);
    if (! $userid && $USER->id != $userid) {
        $parm = [ 'id' => $cm->id, 'userid' => $userid ];
    } else {
        $userid = $USER->id;
        $parm = [ 'id' => $cm->id ];
    }
    if ($viewer) {
        $url = new moodle_url('/mod/vpl/view.php', $parm);
        $node = vpl_navi_node_create($vplnode, 'description', $url);
        $vplnode->add_node($node);
    }
    $example = $vpl->get_instance()->example;
    $submitable = $manager || ($grader && $USER->id != $userid) || (! $grader && $submiter && $vpl->is_submit_able());
    if ($submitable && ! $example && ! $vpl->get_instance()->restrictededitor) {
        $url = new moodle_url('/mod/vpl/forms/submission.php', $parm);
        $node = vpl_navi_node_create($vplnode, 'submission', $url);
        $vplnode->add_node($node);
    }
    if ($submitable) {
        $url = new moodle_url('/mod/vpl/forms/edit.php', $parm);
        $node = vpl_navi_node_create($vplnode, 'edit', $url);
        $vplnode->add_node($node);
    }
    if (! $example) {
        if ($grader && $USER->id != $userid) {
            $url = new moodle_url('/mod/vpl/forms/gradesubmission.php', $parm);
            $node = vpl_navi_node_create($vplnode, vpl_get_gradenoun_str(), $url, navigation_node::TYPE_SETTING, 'core');
            $vplnode->add_node($node);
        }
        $url = new moodle_url('/mod/vpl/forms/submissionview.php', $parm);
        $node = vpl_navi_node_create($vplnode, 'submissionview', $url);
        $vplnode->add_node($node);
        if ($grader || $similarity) {
            $url = new moodle_url('/mod/vpl/views/previoussubmissionslist.php', $parm);
            $node = vpl_navi_node_create($vplnode, 'previoussubmissionslist', $url);
            $vplnode->add_node($node);
        }
        if ($grader || $manager) {
            $url = new moodle_url('/mod/vpl/views/submissionslist.php', $parm);
            $node = vpl_navi_node_create($vplnode, 'submissionslist', $url);
            $vplnode->add_node($node);
        }
        if ($similarity) {
            $url = new moodle_url('/mod/vpl/similarity/similarity_form.php', $parm);
            $node = vpl_navi_node_create($vplnode, 'similarity', $url);
            $vplnode->add_node($node);
        }
    }
}

/**
 * Extends the settings navigation for VPL module.
 *
 * @param settings_navigation $settings
 * @param navigation_node $vplnode
 * @codeCoverageIgnore
 */
function vpl_extend_settings_navigation(settings_navigation $settings, navigation_node $vplnode) {
    global $CFG, $PAGE, $USER;
    if (! isset($PAGE->cm->id)) {
        return;
    }
    $cmid = $PAGE->cm->id;
    $context = context_module::instance($cmid);
    $manager = has_capability(VPL_MANAGE_CAPABILITY, $context);
    $setjails = has_capability(VPL_SETJAILS_CAPABILITY, $context);
    if ($manager) {
        $userid = optional_param('userid', $USER->id, PARAM_INT);
        $klist = $vplnode->get_children_key_list();
        $fkn = null;
        $kpos = array_search('modedit', $klist);
        if ($kpos === false && array_key_exists(0, $klist)) {
            $fkn = $klist[0];
        } else if (array_key_exists($kpos + 1, $klist)) {
            $fkn = $klist[$kpos + 1];
        }
        if ($userid != $USER->id) {
            $parms = [ 'id' => $cmid, 'userid' => $userid ];
        } else {
            $parms = [ 'id' => $cmid ];
        }
        $url = new moodle_url('/mod/vpl/forms/testcasesfile.php', $parms);
        $node = vpl_navi_node_create($vplnode, 'testcases', $url, navigation_node::TYPE_SETTING);
        $vplnode->add_node($node, $fkn);
        $url = new moodle_url('/mod/vpl/forms/executionoptions.php', $parms);
        $node = vpl_navi_node_create($vplnode, 'executionoptions', $url, navigation_node::TYPE_SETTING);
        $vplnode->add_node($node, $fkn);
        $url = new moodle_url('/mod/vpl/forms/requiredfiles.php', $parms);
        $node = vpl_navi_node_create($vplnode, 'requestedfiles', $url, navigation_node::TYPE_SETTING);
        $vplnode->add_node($node, $fkn);

        if ($CFG->release >= '4.0') { // Remove submenu for compatibility with Moodle >= 4.0.
            $advance = $vplnode;
            $keybefore = $fkn;
        } else {
            $advance = vpl_navi_node_create($vplnode, 'advancedsettings', null, navigation_node::NODETYPE_BRANCH, 'moodle');
            $vplnode->add_node($advance, $fkn);
            $keybefore = null;
        }
        $url = new moodle_url('/mod/vpl/forms/executionfiles.php', $parms);
        $node = vpl_navi_node_create($advance, 'executionfiles', $url, navigation_node::TYPE_SETTING);
        $advance->add_node($node, $keybefore);
        $url = new moodle_url('/mod/vpl/forms/executionlimits.php', $parms);
        $node = vpl_navi_node_create($advance, 'maxresourcelimits', $url, navigation_node::TYPE_SETTING);
        $advance->add_node($node, $keybefore);
        $url = new moodle_url('/mod/vpl/forms/executionkeepfiles.php', $parms);
        $node = vpl_navi_node_create($advance, 'keepfiles', $url, navigation_node::TYPE_SETTING);
        $advance->add_node($node, $keybefore);
        $url = new moodle_url('/mod/vpl/forms/variations.php', $parms);
        $node = vpl_navi_node_create($advance, 'variations', $url, navigation_node::TYPE_SETTING);
        $advance->add_node($node, $keybefore);
        $url = new moodle_url('/mod/vpl/forms/overrides.php', $parms);
        $node = vpl_navi_node_create($advance, 'overrides', $url, navigation_node::TYPE_SETTING);
        $advance->add_node($node, $keybefore);
        $url = new moodle_url('/mod/vpl/views/checkjailservers.php', $parms);
        $node = vpl_navi_node_create($advance, 'check_jail_servers', $url, navigation_node::TYPE_SETTING);
        $advance->add_node($node, $keybefore);
        if ($setjails) {
            $url = new moodle_url('/mod/vpl/forms/local_jail_servers.php', $parms);
            $node = vpl_navi_node_create($advance, 'local_jail_servers', $url, navigation_node::TYPE_SETTING);
            $advance->add_node($node, $keybefore);
        }

        if ($CFG->release >= '4.') { // Remove submenu for compatibility with Moodle >= 4.0.
            $testact = $vplnode;
        } else {
            $testact = vpl_navi_node_create($vplnode, 'test', null);
            $vplnode->add_node($testact, $fkn);
        }

        $url = new moodle_url('/mod/vpl/forms/submission.php', $parms);
        $node = vpl_navi_node_create($testact, 'submission', $url, navigation_node::TYPE_SETTING);
        $testact->add_node($node, $keybefore);
        $url = new moodle_url('/mod/vpl/forms/edit.php', $parms);
        $node = vpl_navi_node_create($testact, 'edit', $url, navigation_node::TYPE_SETTING);
        $testact->add_node($node, $keybefore);
        if ($userid != $USER->id) { // Auto grading has sense?
            $url = new moodle_url('/mod/vpl/forms/gradesubmission.php', $parms);
            $node = vpl_navi_node_create($testact, vpl_get_gradenoun_str(), $url, navigation_node::TYPE_SETTING, 'core');
            $testact->add_node($node, $keybefore);
        }
        $url = new moodle_url('/mod/vpl/views/previoussubmissionslist.php', $parms);
        $node = vpl_navi_node_create($testact, 'previoussubmissionslist', $url, navigation_node::TYPE_SETTING);
        $testact->add_node($node, $keybefore);
        $url = new moodle_url('/mod/vpl/index.php', ['id' => $PAGE->cm->course]);
        $node = vpl_navi_node_create($vplnode, 'modulenameplural', $url, navigation_node::TYPE_SETTING);
        $vplnode->add_node($node, $fkn);
        $url = new moodle_url('/mod/vpl/views/checkvpls.php', ['id' => $PAGE->cm->course]);
        $node = vpl_navi_node_create($vplnode, 'checkgroups', $url, navigation_node::TYPE_SETTING);
        $vplnode->add_node($node, $fkn);
    }
}

/**
 * Extend the course navigation with VPL link.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context $context
 * @codeCoverageIgnore
 */
function vpl_extend_navigation_course(navigation_node $navigation, $course, $context) {
    global $DB;
    $capability = has_capability(VPL_MANAGE_CAPABILITY, $context) ||
                  has_capability(VPL_GRADE_CAPABILITY, $context);
    if ($capability && $DB->record_exists(VPL, ['course' => $course->id])) {
        $navlocation = $navigation->find('coursereports', navigation_node::TYPE_CONTAINER);
        if (! $navlocation) {
            $navlocation = $navigation;
        }
        $url = new moodle_url('/mod/vpl/index.php', ['id' => $course->id]);
        $node = vpl_navi_node_create($navlocation, 'modulenameplural', $url, navigation_node::TYPE_SETTING);
        $navlocation->add_node($node);
    }
}

/**
 * Checks if a scale is being used by a particular instance of VPL.
 *
 * @param int $vplid VPL instance ID
 * @param int $scaleid Scale ID
 * @return boolean True if the scale is used by the VPL instance, false otherwise
 * @codeCoverageIgnore
 */
function vpl_scale_used($vplid, $scaleid) {
    global $DB;
    return $scaleid && $DB->record_exists(VPL, [
            'id' => "$vplid",
            'grade' => "-$scaleid",
    ]);
}

/**
 * Checks if scale is being used by any instance of VPL.
 *
 * This is used to find out if scale used anywhere
 *
 * @param int $scaleid Scale ID
 * @return boolean True if the scale is used by any VPL
 * @codeCoverageIgnore
 */
function vpl_scale_used_anywhere($scaleid) {
    global $DB;
    return $scaleid && $DB->record_exists(VPL, ['grade' => "-$scaleid"]);
}

/**
 * Returns a list of actions that can be performed on a VPL view.
 *
 * @return array List of actions
 * @codeCoverageIgnore
 */
function vpl_get_view_actions() {
    return [
            'view',
            'view all',
            'view all submissions',
            'run',
            'debug',
            'edit submission',
            'execution keep file form',
            'execution limits form',
            'edit full description',
            'view grade',
            'Diff',
            'view similarity',
            'view watermarks',
            'similarity form',
            'view previous',
    ];
}

/**
 * Returns a list of actions that can be performed on a VPL post.
 *
 * @return array List of actions
 * @codeCoverageIgnore
 */
function vpl_get_post_actions() {
    return [
            'save submision',
            'evaluate',
            'execution save keeplist',
            'execution save limits',
            'execution save options',
            'execution options form',
            'save full description',
            'remove grade',
            'upload submission',
            'variations form',
    ];
}

/**
 * Removes all grades from gradebook
 *
 * @param int $courseid
 * @param string $type optional
 * @codeCoverageIgnore
 */
function vpl_reset_gradebook($courseid, $type = '') {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    if ($cms = get_coursemodules_in_course(VPL, $courseid)) {
        foreach ($cms as $cm) {
            $vpl = new mod_vpl($cm->id);
            $instance = $vpl->get_instance();
            vpl_grade_item_update($instance, 'reset');
        }
    }
}

/**
 * Removes all user data from a vpl instance
 *
 * @param int $vplid Id of the VPL instance
 * @return void
 */
function vpl_reset_instance_userdata($vplid) {
    global $CFG, $DB;

    // Delete submissions records.
    $paramselectingvpl = ['vpl' => $vplid];
    $DB->delete_records(VPL_SUBMISSIONS, $paramselectingvpl);
    // Delete variations assigned.
    $DB->delete_records(VPL_ASSIGNED_VARIATIONS, $paramselectingvpl);
    // Delete overrides and associated events.
    $vpl = new mod_vpl(null, $vplid);
    $overrides = vpl_get_overrides($vplid);
    foreach ($overrides as $override) {
        $vpl->update_override_calendar_events($override, null, true);
    }
    $DB->delete_records(VPL_ASSIGNED_OVERRIDES, $paramselectingvpl);

    // Delete submission, execution and evaluation files.
    fulldelete($CFG->dataroot . '/vpl_data/' . $vplid . '/usersdata');
}

/**
 * This function is used by the reset VPL submissions for reset course funcion.
 *
 * This function remove all submissions from the specified vpl instances
 * and clean up any related data.
 *
 * @param string $vplselection with partial SQL to select VPL related records of a course.
 * @param array $vplids vpl ids of a course
 * @param int $courseid course id
 * @return bool true if successful, false otherwise
 * @codeCoverageIgnore
 */
function vpl_reset_submissions($vplselection, $vplids, $courseid): bool {
    global $DB, $CFG;
    try {
        $DB->delete_records_select(VPL_SUBMISSIONS, $vplselection, [$courseid]);
        $DB->delete_records_select(VPL_ASSIGNED_VARIATIONS, $vplselection, [$courseid]);
        foreach ($vplids as $vplid) {
            fulldelete($CFG->dataroot . '/vpl_data/' . $vplid . '/usersdata');
        }
        vpl_reset_gradebook($courseid);
    } catch (\Throwable $e) {
        debugging('Error reseting VPL submissions: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
    return true;
}

/**
 * This function is used by the reset VPL overrides by the reset course function.
 *
 * This function will remove all overrides from the specified vpl instances
 * and clean up calendar events.
 *
 * @param string $vplselection with partial SQL to select VPL related records of a course.
 * @param int $courseid course id
 * @return bool true if successful, false otherwise
 * @codeCoverageIgnore
 */
function vpl_reset_overrides($vplselection, $courseid): bool {
    global $DB, $CFG;
    $result = true;
    try {
        $overrides = vpl_get_overrides_incourse($courseid);
        foreach ($overrides as $override) {
            $vpl = new mod_vpl(null, $override->vpl);
            try {
                $vpl->update_override_calendar_events($override, null, true);
            } catch (\Throwable $e) {
                debugging('Error removing VPL overrides calendar events: ' . $e->getMessage(), DEBUG_DEVELOPER);
                $result = false;
            }
        }
        $DB->delete_records_select(VPL_ASSIGNED_OVERRIDES, $vplselection, [$courseid]);
        $DB->delete_records_select(VPL_OVERRIDES, $vplselection, [$courseid]);
    } catch (\Throwable $e) {
        debugging('Error reseting VPL overrides: ' . $e->getMessage(), DEBUG_DEVELOPER);
        $result = false;
    }
    return $result;
}


/**
 * This function is used to reset VPL user data by the reset course function.
 *
 * This function remove all submissions from the specified vpl instance
 * and clean up any related data.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 * @codeCoverageIgnore
 */
function vpl_reset_userdata($data) {
    global $DB;
    $vplselection = 'vpl IN (SELECT id FROM {vpl} WHERE course = ?)';
    $courseparams = [$data->courseid];
    $vplids = $DB->get_fieldset_select(VPL, 'id', 'course = ?', $courseparams);
    $course = $DB->get_record('course', ['id' => $data->courseid], '*', MUST_EXIST);
    $componentstr = get_string('modulenameplural', VPL);
    $status = [];
    if ($data->reset_vpl_submissions) {
        $error = ! vpl_reset_submissions($vplselection, $vplids, $data->courseid);
        $status[] = [
            'component' => $componentstr,
            'item' => get_string('resetvpl', VPL, $course->shortname),
            'error' => $error,
        ];
    }
    if ($data->reset_vpl_overrides) {
        $error = ! vpl_reset_overrides($vplselection, $data->courseid);
        $status[] = [
            'component' => $componentstr,
            'item' => get_string('removeoverrides', VPL),
            'error' => $error,
        ];
    } else if ($data->reset_vpl_group_overrides || $data->reset_vpl_user_overrides) {
        $error = false;
        $overrides = vpl_get_overrides_incourse($course->id);
        foreach ($overrides as $override) {
            try {
                $vpl = new mod_vpl(null, $override->vpl);
                $newoverride = clone $override;
                if ($data->reset_vpl_group_overrides) {
                    $newoverride->groupids = '';
                }
                if ($data->reset_vpl_user_overrides) {
                    $newoverride->userids = '';
                }
                $vpl->update_override_calendar_events($newoverride, $override);
            } catch (\Throwable $e) {
                debugging('Error updating VPL overrides calendar events after course reset: ' . $e->getMessage(), DEBUG_DEVELOPER);
                $error = true;
            }
        }
        if ($data->reset_vpl_group_overrides) {
            $selection = $vplselection . ' AND NOT (groupid IS NULL OR groupid = 0)';
            $DB->delete_records_select(VPL_ASSIGNED_OVERRIDES, $selection, $courseparams);
            $status[] = [
                'component' => $componentstr,
                'item' => get_string('removegroupoverrides', VPL),
                'error' => $error,
            ];
        }
        if ($data->reset_vpl_user_overrides) {
            $selection = $vplselection . ' AND NOT (userid IS NULL OR userid = 0)';
            $DB->delete_records_select(VPL_ASSIGNED_OVERRIDES, $selection, $courseparams);
            $status[] = [
                'component' => $componentstr,
                'item' => get_string('removeuseroverrides', VPL),
                'error' => $error,
            ];
        }
    }

    // Updating dates - shift may be negative too.
    if ($data->timeshift != 0) {
        // Shift dates in all vpl overrides in the course.
        $error = false;
        $overrides = vpl_get_overrides_incourse($course->id);
        $params = ['timeshift' => $data->timeshift, 'courseid' => $data->courseid];
        foreach (['startdate', 'duedate'] as $field) {
            $sql = "UPDATE {vpl_overrides}
                        SET $field = $field + :timeshift
                        WHERE vpl IN (SELECT id FROM {vpl} WHERE course = :courseid)
                              AND NOT ($field IS NULL OR $field = 0)";
            $DB->execute($sql, $params);
        }

        $newoverrides = vpl_get_overrides_incourse($course->id);
        foreach ($overrides as $override) {
            try {
                $vpl = new mod_vpl(null, $override->vpl);
                if (isset($newoverrides[$override->id])) {
                    $vpl->update_override_calendar_events($newoverrides[$override->id]);
                } else {
                    $vpl->update_override_calendar_events($override, null, true);
                }
            } catch (\Throwable $e) {
                debugging('Error updating VPL overrides calendar events after time shifting: ' . $e->getMessage(), DEBUG_DEVELOPER);
                $error = true;
            }
        }

        // Shift dates in all vpl instances in the course.
        foreach (['startdate', 'duedate'] as $field) {
            $sql = "UPDATE {vpl}
                        SET $field = $field + :timeshift
                        WHERE course = :courseid
                              AND NOT ($field IS NULL OR $field = 0)";
            $DB->execute($sql, $params);
        }
        mod_vpl::reset_db_cache();
        $vplinstances = $DB->get_records_select(VPL, 'course = ?', [$data->courseid]);
        foreach ($vplinstances as $vplinstance) {
            try {
                vpl_update_instance_event($vplinstance);
            } catch (\Throwable $e) {
                debugging('Error updating VPL calendar events after time shiting: ' . $e->getMessage(), DEBUG_DEVELOPER);
                $error = true;
            }
        }

        $status[] = [
                'component' => $componentstr,
                'item' => get_string('timeshift', VPL, format_time($data->timeshift)),
                'error' => $error,
            ];
    }
    return $status;
}

/**
 * Add the form elements that control VPL for the course reset functionality.
 *
 * @param moodleform $mform
 * @codeCoverageIgnore
 */
function vpl_reset_course_form_definition($mform) {
    $mform->addElement('header', 'vplheader', get_string('modulenameplural', VPL));
    $mform->addElement('static', 'reset_vpl_delete', get_string('delete'));
    $mform->addElement(
        'advcheckbox',
        'reset_vpl_submissions',
        get_string('removeallsubmissions', VPL)
    );
    $mform->addHelpButton('reset_vpl_submissions', 'removeallsubmissions', VPL);
    $mform->addElement(
        'advcheckbox',
        'reset_vpl_overrides',
        get_string('removeoverrides', VPL)
    );
    $mform->addHelpButton('reset_vpl_overrides', 'removeoverrides', VPL);
    $mform->addElement(
        'advcheckbox',
        'reset_vpl_user_overrides',
        get_string('removeuseroverrides', 'vpl')
    );
    $mform->addHelpButton('reset_vpl_user_overrides', 'removeuseroverrides', VPL);
    $mform->hideIf('reset_vpl_user_overrides', 'reset_vpl_overrides', 'checked');
    $mform->addElement(
        'advcheckbox',
        'reset_vpl_group_overrides',
        get_string('removegroupoverrides', 'vpl')
    );
    $mform->addHelpButton('reset_vpl_group_overrides', 'removegroupoverrides', VPL);
    $mform->hideIf('reset_vpl_group_overrides', 'reset_vpl_overrides', 'checked');
}

/**
 * Course reset form defaults.
 *
 * This function is used by the reset_course_form in moodlelib.php to set the default values
 * for the course reset form. It returns an array with the default values for the VPL
 * reset options.
 *
 * @param stdClass $course The course object (Not used in this function).
 * @codeCoverageIgnore
 */
function vpl_reset_course_form_defaults($course) {
    return [
        'reset_vpl_submissions' => 1,
        'reset_vpl_overrides' => 1,
        'reset_vpl_user_overrides' => 0,
        'reset_vpl_group_overrides' => 0,
    ];
}
