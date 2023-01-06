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

require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/list_util.class.php');
require_once($CFG->dirroot.'/course/lib.php');

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
function vpl_grade_item_update($instance, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $itemdetails = array('itemname' => $instance->name);
    $itemdetails['hidden'] = ($instance->visiblegrade > 0) ? 0 : 1;
    if ( isset($instance->cmidnumber) ) {
        $itemdetails['idnumber'] = $instance->cmidnumber;
    }
    if ($instance->grade == 0 || $instance->example != 0) {
        $itemdetails['gradetype'] = GRADE_TYPE_NONE;
        $itemdetails['deleted'] = 1;
    } else if ($instance->grade > 0) {
        $itemdetails['gradetype'] = GRADE_TYPE_VALUE;
        $itemdetails['grademax']  = $instance->grade;
        $itemdetails['grademin']  = 0; // I don't know if this is correct updating.

    } else {
        $itemdetails['gradetype'] = GRADE_TYPE_SCALE;
        $itemdetails['scaleid']   = -$instance->grade;

    }

    if ($grades === 'reset') {
        $itemdetails['reset'] = true;
        $grades = null;
    }

    return grade_update(
        'mod/vpl', $instance->course, 'mod', 'vpl',
        $instance->id, 0, $grades, $itemdetails
    );
}

/**
 * Updates activity grades.
 * API and comment taken from Moodle assign.
 *
 * @param stdClass $instance   of VPL database record
 * @param int      $userid     specific user only, 0 means all
 * @param bool     $nullifnone - not used
 *
 * @return bollean true correct, false fail
 */
function vpl_update_grades($instance, $userid=0, $nullifnone=true) {
    global $CFG, $USER;
    require_once($CFG->libdir.'/gradelib.php');
    require_once(dirname( __FILE__ ) . '/vpl_submission_CE.class.php');

    if ($instance->grade == 0) {
        return vpl_grade_item_update($instance);
    } else if ($userid == 0) {
        $vpl = new mod_vpl( false, $instance->id);
        $subs = $vpl->all_last_user_submission();

    } else {
        $vpl = new mod_vpl( false, $instance->id);
        $sub = $vpl->last_user_submission($userid);
        if ($sub === false) {
            $subs = array();
        } else {
            $subs = array($sub);
        }
    }
    $grades = array();
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
    return vpl_grade_item_update($instance, $grades);
}

/**
 * Deletes grade_item from a vpl instance+id
 *
 * @param Object $instance of vpl DB with id
 */
function vpl_delete_grade_item($instance) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $itemdetails = array ( 'deleted' => 1 );
    grade_update( 'mod/vpl', $instance->course, 'mod', VPL, $instance->id, 0, null, $itemdetails );
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
 * Callback function to determine if the event is visible for the current user.
 *
 * @param calendar_event $event
 * @return bool Returns true if the event is visible, false if not visible.
 */
function mod_vpl_core_calendar_is_event_visible(calendar_event $event) {
    $vpl = new mod_vpl(null, $event->instance);
    return $vpl->is_visible();
}

/**
 * Callback function to set the event action if available.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory objet to generate the action
 * @return \core_calendar\action_factory|null The action object or null
 */
function mod_vpl_core_calendar_provide_event_action(calendar_event $event,
                                                    \core_calendar\action_factory $factory) {
    $vpl = new mod_vpl(null, $event->instance);
    if ($vpl->is_visible()) {
        $text = get_string('dueeventaction', VPL);
        $cmid = $vpl->get_course_module()->id;
        $link = new \moodle_url('/mod/vpl/forms/edit.php', ['id' => $cmid]);
        return $factory->create_instance($text, $link, 1, $vpl->is_submit_able());
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
function mod_vpl_core_calendar_event_action_shows_item_count(calendar_event $event,
                                                    int $itemcount = 0) {
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
    vpl_truncate_vpl( $instance );
    $id = $DB->insert_record( VPL, $instance );
    // Add event.
    if ($instance->duedate) {
        calendar_event::create( vpl_create_event( $instance, $id ), false );
    }
    // Add grade to grade book.
    $instance->id = $id;
    vpl_grade_item_update( $instance );
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
 * @return boolean OK
 */
function vpl_update_instance_event($instance) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/calendar/lib.php');
    $event = vpl_create_event($instance, $instance->id);
    $searchfields = [
        'modulename' => VPL,
        'instance' => $instance->id,
        'eventtype' => VPL_EVENT_TYPE_DUE,
        'priority' => null
    ];
    if ($eventid = $DB->get_field( 'event', 'id', $searchfields)) {
        $event->id = $eventid;
        $calendarevent = \calendar_event::load( $eventid );
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
 * @param object from the form in mod.html
 * @return boolean OK
 */
function vpl_update_instance($instance) {
    global $DB;
    vpl_truncate_vpl( $instance );
    $instance->id = $instance->instance;
    vpl_update_instance_event($instance);
    $cm = get_coursemodule_from_instance( VPL, $instance->id, $instance->course );
    $instance->cmidnumber = $cm->id;
    vpl_grade_item_update( $instance );
    $completionexpected = (!empty($instance->completionexpected)) ? $instance->completionexpected : null;
    \core_completion\api::update_completion_date_event($instance->cmidnumber, 'vpl', $instance, $completionexpected);

    return $DB->update_record( VPL, $instance );
}

/**
 * Deletes an instance by id
 *
 * @param int $id instance Id
 * @return boolean OK
 */
function vpl_delete_instance( $id ) {
    global $DB, $CFG;

    $instance = $DB->get_record( VPL, array ( "id" => $id ) );
    if ( $instance === false ) {
        return false;
    }

    // Delete all data files.
    vpl_delete_dir( $CFG->dataroot . '/vpl_data/' . $id );

    // Delete grade_item.
    vpl_delete_grade_item( $instance );

    // Delete relate event.
    $DB->delete_records( 'event',
            array (
                    'modulename' => VPL,
                    'instance' => $id
            ) );

    // Delete all related records.
    $tables = [
            VPL_SUBMISSIONS,
            VPL_VARIATIONS,
            VPL_ASSIGNED_VARIATIONS,
            VPL_OVERRIDES,
            VPL_ASSIGNED_OVERRIDES
    ];
    foreach ($tables as $table) {
        $DB->delete_records( $table, array ('vpl' => $id) );
    }

    // Reset basedon $id to 0.
    $resetbasedon = 'UPDATE {vpl}
                         set basedon = 0
                         WHERE basedon = :vplid';
    $DB->execute($resetbasedon, array ( 'vplid' => $id ));

    // Delete vpl record.
    $DB->delete_records( VPL, array ( 'id' => $id ) );

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
            return false;
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
 * Returns an object with short information about what a user has done with a given particular
 * instance of this module $return->time = the time they did it $return->info = a short text
 * description
 */
function vpl_user_outline($course, $user, $mod, $instance) {
    // Search submisions for $user $instance.
    $vpl = new mod_vpl( null, $instance->id );
    $subinstance = $vpl->last_user_submission( $user->id );
    if (! $subinstance) {
        $return = null;
    } else {
        require_once('vpl_submission.class.php');
        $return = new stdClass();
        $submission = new mod_vpl_submission( $vpl, $subinstance );
        $return->time = $subinstance->datesubmitted;
        $subs = $vpl->user_submissions( $user->id );
        if (count( $subs ) > 1) {
            $info = get_string( 'nsubmissions', VPL, count( $subs ) );
        } else {
            $info = get_string( 'submission', VPL, count( $subs ) );
        }
        if ($subinstance->dategraded) {
            $info .= '<br>' . get_string( 'grade', 'core_grades' ) . ': ' . $submission->get_grade_core();
        }
        $url = vpl_mod_href( 'forms/submissionview.php', 'id', $vpl->get_course_module()->id, 'userid', $user->id );
        $return->info = '<a href="' . $url . '">' . $info . '</a>';
    }
    return $return;
}

/**
 * Prints a detailed report of what a user has done with a given particular instance of this
 * module
 *
 */
function vpl_user_complete($course, $user, $mod, $vpl) {
    require_once('vpl_submission.class.php');
    // TODO Print a detailed report of what a user has done with a given particular instance.
    // Search submisions for $user $instance.
    $vpl = new mod_vpl( null, $vpl->id );
    $sub = $vpl->last_user_submission( $user->id );
    if ($sub !== false) {
        $submission = new mod_vpl_submission( $vpl, $sub );
        $submission->print_info( true );
        $submission->print_grade( true );
    }
}

/**
 * @codeCoverageIgnore
 *
 * Returns all VPL submissions since a given time
 */
function vpl_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0) {
    global $CFG, $USER, $DB;
    $grader = false;
    $vpl = new mod_vpl( $cmid );
    $modinfo = get_fast_modinfo( $vpl->get_course() );
    $cm = $modinfo->get_cm($cmid);
    $vplid = $vpl->get_instance()->id;
    $grader = $vpl->has_capability( VPL_GRADE_CAPABILITY );
    if (! $vpl->is_visible() && ! $grader) {
        return;
    }
    $select = 'select * from {vpl_submissions} subs';
    $where = ' where (subs.vpl = :vplid) and ((subs.datesubmitted >= :timestartsub) or (subs.dategraded >= :timestartgrade))';
    $parms = array ( 'vplid' => $vplid, 'timestartsub' => $timestart, 'timestartgrade' => $timestart);
    if (! $grader || ($userid != 0)) { // User activity.
        if ( ! $grader ) {
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
    $subs = $DB->get_records_sql( $select . $where , $parms);
    if ($grader) {
        require_once($CFG->libdir.'/gradelib.php');
        $userids = array();
        foreach ($subs as $sub) {
            $userids[] = $sub->userid;
        }
        $grades = grade_get_grades($courseid, 'mod', 'vpl', $cm->instance, $userids);
    }

    $aname = format_string( $vpl->get_printable_name(), true );
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
        $activity->user = $DB->get_record( 'user', array ( 'id' => $sub->userid ) );
        $activities[$index ++] = $activity;
    }
    return true;
}

/**
 * @codeCoverageIgnore
 */
function vpl_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
    // TODO improve.
    global $CFG, $OUTPUT;
    echo '<table border="0" cellpadding="3" cellspacing="0" class="vpl-recent">';
    echo '<tr><td class="userpicture" valign="top">';
    echo $OUTPUT->user_picture( $activity->user );
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
        echo get_string('grade', 'core_grades') . ': ';
        echo $activity->grade;
        echo '</div>';
    }
    echo '<div class="user">';
    $fullname = fullname( $activity->user, $viewfullnames );
    echo "<a href=\"{$CFG->wwwroot}/user/view.php?id={$activity->user->id}&amp;course=$courseid\">" . "{$fullname}</a> - ";
    $link = vpl_mod_href( 'forms/submissionview.php', 'id', $activity->cmid, 'userid', $activity->user->id, 'inpopup', 1 );
    echo '<a href="' . $link . '">' . userdate( $activity->timestamp ) . '</a>';
    echo '</div>';
    echo "</td></tr></table>";
    return;
}

/**
 * @codeCoverageIgnore
 *
 * Given a course_module object, this function returns any "extra" information
 * that may be needed whenprinting this activity in a course listing.
 * See get_array_of_activities() in course/lib.php.
 *
 * @param $coursemodule object
 *            The coursemodule object (record).
 * @return object An object on information that the coures will know about
 *      (most noticeably, an icon). fields all optional extra, icon, name.
 */

function vpl_get_coursemodule_info_not_valid($coursemodule) {
    global $CFG;
    $ret = new stdClass();
    $ret->icon = $CFG->wwwroot.'/mod/vpl/icon.gif';
    $vpl = new mod_vpl($coursemodule->id);
    $instance = $vpl->get_instance();
    if ($instance->example) { // Is example.
        $ret->icon = $CFG->wwwroot.'/mod/vpl/icon_yellow.gif';
        $ret->name = $vpl->get_instance()->name.' '.get_string('example', VPL);
        return;
    }
    if ($instance->grade == 0) { // Not grade_able .
        $ret->icon = $CFG->wwwroot.'/mod/vpl/icon_green.gif';
        return;
    }
    if ($instance->automaticgrading) { // Automatic grading.
        $ret->icon = $CFG->wwwroot.'/mod/vpl/icon_red.gif';
    }
    if ($instance->duedate > 0 && $instance->duedate < time()) { // Closed.
        $ret->icon = $CFG->wwwroot.'/mod/vpl/icon_black.gif';
        return;
    }
    return $ret;
}

/**
 * @codeCoverageIgnore
 *
 * Get icon mapping for font-awesome.
 *
 * @return  array
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
            'mod_vpl:previoussubmissionslist' => 'fa-history',
            'mod_vpl:modulenameplural' => 'fa-list-ul',
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
    ];
}


/**
 * Creates e new navigation node with icon
 * @param navigation_node $vplnode
 * @param string $str string to be i18n
 * @param moodle_url $url
 * @param navigation_node::TYPE $type
 * @param string $comp component by default VPL
 * @return navigation_node
 */
function vpl_navi_node_create(navigation_node $vplnode, $str, $url, $type = navigation_node::NODETYPE_LEAF , $comp = 'mod_vpl' ) {
    $stri18n = get_string($str, $comp);
    $node = $vplnode->create( $stri18n, $url, $type, null, null, new pix_icon( $str, '', 'mod_vpl') );
    if ( $type == navigation_node::NODETYPE_BRANCH ) {
        $node->collapse = true;
        $node->forceopen = false;
    }
    $node->mainnavonly = true;
    return $node;
}

/**
 * @codeCoverageIgnore
 */
function vpl_extend_navigation(navigation_node $vplnode, $course, $module, $cm) {
    global $USER;
    $vpl = new mod_vpl( $cm->id );
    $viewer = $vpl->has_capability( VPL_VIEW_CAPABILITY );
    $submiter = $vpl->has_capability( VPL_SUBMIT_CAPABILITY );
    $similarity = $vpl->has_capability( VPL_SIMILARITY_CAPABILITY );
    $grader = $vpl->has_capability( VPL_GRADE_CAPABILITY );
    $manager = $vpl->has_capability( VPL_MANAGE_CAPABILITY );
    $userid = optional_param( 'userid', false, PARAM_INT );
    if (! $userid && $USER->id != $userid) {
        $parm = array ( 'id' => $cm->id, 'userid' => $userid );
    } else {
        $userid = $USER->id;
        $parm = array ( 'id' => $cm->id );
    }
    if ($viewer) {
        $url = new moodle_url( '/mod/vpl/view.php', $parm );
        $node = vpl_navi_node_create($vplnode, 'description', $url);
        $vplnode->add_node( $node );
    }
    $example = $vpl->get_instance()->example;
    $submitable = $manager || ($grader && $USER->id != $userid) || (! $grader && $submiter && $vpl->is_submit_able());
    if ($submitable && ! $example && ! $vpl->get_instance()->restrictededitor) {
        $url = new moodle_url( '/mod/vpl/forms/submission.php', $parm);
        $node = vpl_navi_node_create($vplnode, 'submission', $url);
        $vplnode->add_node( $node );
    }
    if ($submitable) {
        $url = new moodle_url( '/mod/vpl/forms/edit.php', $parm);
        $node = vpl_navi_node_create($vplnode, 'edit', $url);
        $vplnode->add_node( $node );
    }
    if (! $example) {
        if ($grader && $USER->id != $userid) {
            $url = new moodle_url( '/mod/vpl/forms/gradesubmission.php', $parm);
            $node = vpl_navi_node_create($vplnode, 'grade', $url, navigation_node::TYPE_SETTING, 'core_grades');
            $vplnode->add_node( $node );
        }
        $url = new moodle_url( '/mod/vpl/forms/submissionview.php', $parm );
        $node = vpl_navi_node_create($vplnode, 'submissionview', $url);
        $vplnode->add_node( $node );
        if ($grader || $similarity) {
            $url = new moodle_url( '/mod/vpl/views/previoussubmissionslist.php', $parm );
            $node = vpl_navi_node_create($vplnode, 'previoussubmissionslist', $url);
            $vplnode->add_node( $node );
        }
        if ($grader || $manager) {
            $url = new moodle_url( '/mod/vpl/views/submissionslist.php', $parm );
            $node = vpl_navi_node_create($vplnode, 'submissionslist', $url);
            $vplnode->add_node( $node );
        }
        if ($similarity) {
            $url = new moodle_url( '/mod/vpl/similarity/similarity_form.php', $parm );
            $node = vpl_navi_node_create($vplnode, 'similarity', $url);
            $vplnode->add_node( $node );
        }
    }
}

/**
 * @codeCoverageIgnore
 */
function vpl_extend_settings_navigation(settings_navigation $settings, navigation_node $vplnode) {
    global $CFG, $PAGE, $USER;
    if (! isset( $PAGE->cm->id )) {
        return;
    }
    $cmid = $PAGE->cm->id;
    $context = context_module::instance( $cmid );
    $manager = has_capability( VPL_MANAGE_CAPABILITY, $context );
    $setjails = has_capability( VPL_SETJAILS_CAPABILITY, $context );
    if ($manager) {
        $userid = optional_param( 'userid', $USER->id, PARAM_INT );
        $klist = $vplnode->get_children_key_list();
        if (count( $klist ) > 1) {
            $fkn = $klist[1];
            $vplnode->get($klist[0])->icon = new pix_icon('a/setting', '');
        } else {
            $fkn = null;
        }
        if ( $userid != $USER->id ) {
            $parms = array ( 'id' => $cmid, 'userid' => $userid );
        } else {
            $parms = array ( 'id' => $cmid );
        }
        $url = new moodle_url( '/mod/vpl/forms/testcasesfile.php', $parms );
        $node = vpl_navi_node_create($vplnode, 'testcases', $url, navigation_node::TYPE_SETTING);
        $vplnode->add_node( $node, $fkn );
        $url = new moodle_url( '/mod/vpl/forms/executionoptions.php', $parms );
        $node = vpl_navi_node_create($vplnode, 'executionoptions', $url, navigation_node::TYPE_SETTING);
        $vplnode->add_node( $node, $fkn );
        $url = new moodle_url( '/mod/vpl/forms/requiredfiles.php', $parms );
        $node = vpl_navi_node_create($vplnode, 'requestedfiles', $url, navigation_node::TYPE_SETTING);
        $vplnode->add_node( $node, $fkn );

        if ( $CFG->release >= '4.0' ) { // Remove submenu for compatibility with Moodle >= 4.0.
            $advance = $vplnode;
            $keybefore = $fkn;
        } else {
            $advance = vpl_navi_node_create($vplnode, 'advancedsettings', null, navigation_node::NODETYPE_BRANCH, 'moodle');
            $vplnode->add_node( $advance, $fkn );
            $keybefore = null;
        }
        $url = new moodle_url( '/mod/vpl/forms/executionfiles.php', $parms );
        $node = vpl_navi_node_create($advance, 'executionfiles', $url, navigation_node::TYPE_SETTING);
        $advance->add_node( $node, $keybefore );
        $url = new moodle_url( '/mod/vpl/forms/executionlimits.php', $parms );
        $node = vpl_navi_node_create($advance, 'maxresourcelimits', $url, navigation_node::TYPE_SETTING);
        $advance->add_node( $node, $keybefore );
        $url = new moodle_url( '/mod/vpl/forms/executionkeepfiles.php', $parms );
        $node = vpl_navi_node_create($advance, 'keepfiles', $url, navigation_node::TYPE_SETTING);
        $advance->add_node( $node, $keybefore );
        $url = new moodle_url( '/mod/vpl/forms/variations.php', $parms );
        $node = vpl_navi_node_create($advance, 'variations', $url, navigation_node::TYPE_SETTING);
        $advance->add_node( $node, $keybefore );
        $url = new moodle_url( '/mod/vpl/forms/overrides.php', $parms );
        $node = vpl_navi_node_create($advance, 'overrides', $url, navigation_node::TYPE_SETTING);
        $advance->add_node( $node, $keybefore );
        $url = new moodle_url( '/mod/vpl/views/checkjailservers.php', $parms );
        $node = vpl_navi_node_create($advance, 'check_jail_servers', $url, navigation_node::TYPE_SETTING);
        $advance->add_node( $node, $keybefore );
        if ($setjails) {
            $url = new moodle_url( '/mod/vpl/forms/local_jail_servers.php', $parms );
            $node = vpl_navi_node_create($advance, 'local_jail_servers', $url, navigation_node::TYPE_SETTING);
            $advance->add_node( $node, $keybefore );
        }

        if ( $CFG->release >= '4.' ) { // Remove submenu for compatibility with Moodle >= 4.0.
            $testact = $vplnode;
        } else {
            $testact = vpl_navi_node_create($vplnode, 'test', null);
            $vplnode->add_node( $testact, $fkn );
        }

        $url = new moodle_url( '/mod/vpl/forms/submission.php', $parms );
        $node = vpl_navi_node_create($testact, 'submission', $url, navigation_node::TYPE_SETTING);
        $testact->add_node( $node, $keybefore );
        $url = new moodle_url( '/mod/vpl/forms/edit.php', $parms );
        $node = vpl_navi_node_create($testact, 'edit', $url, navigation_node::TYPE_SETTING);
        $testact->add_node( $node, $keybefore );
        if ( $userid != $USER->id ) { // Auto grading has sense?
            $url = new moodle_url( '/mod/vpl/forms/gradesubmission.php', $parms );
            $node = vpl_navi_node_create($testact, 'grade', $url, navigation_node::TYPE_SETTING, 'core_grades');
            $testact->add_node( $node, $keybefore );
        }
        $url = new moodle_url( '/mod/vpl/views/previoussubmissionslist.php', $parms );
        $node = vpl_navi_node_create($testact, 'previoussubmissionslist', $url, navigation_node::TYPE_SETTING);
        $testact->add_node( $node, $keybefore );
        $url = new moodle_url( '/mod/vpl/index.php', array ('id' => $PAGE->cm->course));
        $node = vpl_navi_node_create($vplnode, 'modulenameplural', $url, navigation_node::TYPE_SETTING);
        $vplnode->add_node( $node, $fkn );
    }
}

/**
 * @codeCoverageIgnore
 */
function vpl_scale_used($vplid, $scaleid) {
    global $DB;
    return $scaleid && $DB->record_exists( VPL, [
            'id' => "$vplid",
            'grade' => "-$scaleid"
    ] );
}

/**
 * @codeCoverageIgnore
 *
 * Checks if scale is being used by any instance of VPL. This is used to find out if scale
 * used anywhere
 *
 * @param $scaleid int
 * @return boolean True if the scale is used by any VPL
 */
function vpl_scale_used_anywhere($scaleid) {
    global $DB;
    return $scaleid && $DB->record_exists( VPL, [
            'grade' => "-$scaleid"
    ] );
}

/**
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
            'view previous'
    ];
}

/**
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
            'variations form'
    ];
}

/**
 * @codeCoverageIgnore
 *
 * Removes all grades from gradebook
 *
 * @param int $courseid
 * @param
 *            string optional type
 */
function vpl_reset_gradebook($courseid, $type = '') {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    if ($cms = get_coursemodules_in_course( VPL, $courseid )) {
        foreach ($cms as $cm) {
            $vpl = new mod_vpl( $cm->id );
            $instance = $vpl->get_instance();
            $itemdetails = array (
                    'reset' => 1
            );
            grade_update( 'mod/vpl', $instance->course, 'mod', VPL, $instance->id
                          , 0, null, $itemdetails );
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
    $DB->delete_records( VPL_SUBMISSIONS, array (
            'vpl' => $vplid
    ) );
    // Delete variations assigned.
    $DB->delete_records( VPL_ASSIGNED_VARIATIONS, array (
            'vpl' => $vplid
    ) );
    // Delete overrides and associated events.
    require_once(dirname(__FILE__) . '/vpl.class.php');
    $vpl = new mod_vpl(null, $vplid);
    $sql = 'SELECT ao.id as aid, o.*, ao.userid as userids, ao.groupid as groupids
                FROM {vpl_overrides} o
                LEFT JOIN {vpl_assigned_overrides} ao ON ao.override = o.id
                WHERE o.vpl = :vplid';
    $overridesseparated = $DB->get_records_sql($sql, array('vplid' => $vplid));
    $overrides = vpl_agregate_overrides($overridesseparated);
    foreach ($overrides as $override) {
        $vpl->update_override_calendar_events($override, null, true);
    }
    $DB->delete_records( VPL_ASSIGNED_OVERRIDES, array (
            'vpl' => $vplid
    ) );

    // Delete submission, execution and evaluation files.
    fulldelete( $CFG->dataroot . '/vpl_data/'. $vplid . '/usersdata' );
}

/**
 * @codeCoverageIgnore
 *
 * This function is used by the reset_course_userdata function in moodlelib. This function
 * will remove all submissions from the specified vpl instance and clean up any related data.
 *
 * @param $data stdClass the data submitted from the reset course.
 * @return array status array
 */
function vpl_reset_userdata($data) {
    $status = array ();
    if ($data->reset_vpl_submissions) {
        $componentstr = get_string( 'modulenameplural', VPL );
        if ($cms = get_coursemodules_in_course( VPL, $data->courseid )) {
            foreach ($cms as $cm) { // For each vpl instance in course.
                $vpl = new mod_vpl( $cm->id );
                $instance = $vpl->get_instance();
                $instancestatus = array (
                        'component' => $componentstr,
                        'item' => get_string( 'resetvpl', VPL, $instance->name ),
                        'error' => false
                );
                try {
                    vpl_reset_instance_userdata($instance->id);
                } catch (Exception $e) {
                    $instancestatus['error'] = true;
                }
                $status[] = $instancestatus;
            }
        }
    }
    return $status;
}

/**
 * @codeCoverageIgnore
 *
 * Implementation of the function for printing the form elements that control whether
 * the course reset functionality affects VPL.
 *
 * @param $mform moodleform passed by reference
 */
function vpl_reset_course_form_definition(&$mform) {
    $mform->addElement( 'header', 'vplheader', get_string( 'modulenameplural', VPL ) );
    $mform->addElement( 'advcheckbox', 'reset_vpl_submissions', get_string( 'deleteallsubmissions', VPL ) );
}

/**
 * @codeCoverageIgnore
 *
 * Course reset form defaults.
 */
function vpl_reset_course_form_defaults($course) {
    return array (
            'reset_vpl_submissions' => 1
    );
}
