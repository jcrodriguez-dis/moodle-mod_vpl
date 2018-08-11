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

require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/list_util.class.php');
require_once($CFG->dirroot.'/course/lib.php');

/**
 * Create/update grade item for given VPL activity.
 *
 * @param stdClass VPL record with extra cmidnumber
 * @param array $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 * (Code and comments adaptes from Moodle assign)
 */
function vpl_grade_item_update($instance, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $itemdetails = array('itemname' => $instance->name);
    $itemdetails ['hidden'] = ($instance->visiblegrade > 0) ? 0 : 1;
    if ( isset($instance->cmidnumber) ) {
        $itemdetails['idnumber'] = $instance->cmidnumber;
    }
    if ($instance->grade > 0) {
        $itemdetails['gradetype'] = GRADE_TYPE_VALUE;
        $itemdetails['grademax']  = $instance->grade;
        $itemdetails['grademin']  = 0;

    } else if ($instance->grade < 0) {
        $itemdetails['gradetype'] = GRADE_TYPE_SCALE;
        $itemdetails['scaleid']   = -$instance->grade;

    }
    if ($instance->grade == 0 || $instance->example != 0) {
        $itemdetails['gradetype'] = GRADE_TYPE_NONE;
        $itemdetails['deleted'] = 1;
    }
    if ($grades === 'reset') {
        $itemdetails['reset'] = true;
        $grades = null;
    }
    if ($grades != null) {
        $itemdetails = null;
    }
    return grade_update('mod/vpl', $instance->course, 'mod', 'vpl',
                        $instance->id, 0, $grades, $itemdetails);
}

/**
 * Update activity grades.
 *
 * @param stdClass VPL database record
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone - not used
 * (API and comment taken from Moodle assign)
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
            if ( $sub->grader > 0 ) {
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
 * Delete grade_item from a vpl instance+id
 *
 * @param $instance vpl
 *            instance
 */
function vpl_delete_grade_item($instance) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $itemdetails = array ( 'deleted' => 1 );
    grade_update( 'mod/vpl', $instance->course, 'mod', VPL, $instance->id, 0, null, $itemdetails );
}

/**
 * Create an event object from a vpl instance+id
 *
 * @param $instance vpl
 *            instance
 * @param $id vpl
 *            instance id
 * @return event object
 */
function vpl_create_event($instance, $id) {
    $event = new stdClass();
    $event->name = $instance->name;
    $event->description = $instance->shortdescription;
    $event->format = FORMAT_PLAIN;
    $event->courseid = $instance->course;
    $event->modulename = VPL;
    $event->instance = $id;
    $event->eventtype = 'duedate';
    $event->timestart = $instance->duedate;
    return $event;
}

/**
 * Add a new vpl instance and return the id
 *
 * @param
 *            object from the form in mod_form.html
 * @return int id of the new vpl
 */
function vpl_add_instance($instance) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/calendar/lib.php');
    vpl_truncate_vpl( $instance );
    $id = $DB->insert_record( VPL, $instance );
    // Add event.
    if ($instance->duedate) {
        calendar_event::create( vpl_create_event( $instance, $id ) );
    }
    // Add grade to grade book.
    $instance->id = $id;
    vpl_grade_item_update( $instance );
    return $id;
}

/**
 * Update a vpl instance
 *
 * @param object from the form in mod.html
 * @return boolean OK
 */
function vpl_update_instance($instance) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/calendar/lib.php');
    vpl_truncate_vpl( $instance );
    $instance->id = $instance->instance;
    // Update event.
    $event = vpl_create_event( $instance, $instance->id );
    if ($eventid = $DB->get_field( 'event', 'id', array (
            'modulename' => VPL,
            'instance' => $instance->id
    ) )) {
        $event->id = $eventid;
        $calendarevent = calendar_event::load( $eventid );
        if ($instance->duedate) {
            $calendarevent->update( $event );
        } else {
            $calendarevent->delete();
        }
    } else {
        if ($instance->duedate) {
            calendar_event::create( $event );
        }
    }
    $cm = get_coursemodule_from_instance( VPL, $instance->id, $instance->course );
    $instance->cmidnumber = $cm->id;
    vpl_grade_item_update( $instance );
    return $DB->update_record( VPL, $instance );
}

/**
 * Delete an instance by id
 *
 * @param int $id instance Id
 * @return boolean OK
 */
function vpl_delete_instance( $id ) {
    global $DB, $CFG;
    // Delete all data files.
    $instance = $DB->get_record( VPL, array ( "id" => $id ) );
    if ( $instance === false ) {
        return false;
    }
    vpl_delete_dir( $CFG->dataroot . '/vpl_data/' . $id );
    // Delete grade_item.
    vpl_delete_grade_item( $instance );
    // Delete event.
    $DB->delete_records( 'event',
            array (
                    'modulename' => VPL,
                    'instance' => $id
            ) );
    // Delete all submissions records.
    $DB->delete_records( 'vpl_submissions', array ( 'vpl' => $id ) );
    // Delete vpl record.
    $DB->delete_records( VPL, array ( 'id' => $id ) );

    // Locate related VPLs and reset its basedon $id to 0.
    $related = $DB->get_records_select( VPL, 'basedon = ?', array ( $id ) );
    foreach ($related as $other) {
        $other->basedon = 0;
        $DB->update_record( VPL, $other );
    }
    return true;
}

/**
 *
 * @param string $feature
 *            FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function vpl_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS :
            return true;
        case FEATURE_GROUPINGS :
            return true;
        case FEATURE_GROUPMEMBERSONLY :
            return true;
        case FEATURE_MOD_INTRO :
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS : // TODO FEATURE_COMPLETION_TRACKS_VIEWS.
            return false;
        case FEATURE_COMPLETION_HAS_RULES : // TODO FEATURE_COMPLETION_HAS_RULES.
            return false;
        case FEATURE_GRADE_HAS_GRADE :
            return true;
        case FEATURE_GRADE_OUTCOMES :
            return true;
        case FEATURE_BACKUP_MOODLE2 :
            return true;
        case FEATURE_SHOW_DESCRIPTION :
            return true;
        case FEATURE_ADVANCED_GRADING :
            return false;
        default :
            return null;
    }
}

/**
 * Return an object with short information about what a user has done with a given particular
 * instance of this module $return->time = the time they did it $return->info = a short text
 * description
 *
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
            $info .= '<br />' . get_string( 'grade' ) . ': ' . $submission->get_grade_core();
        }
        $url = vpl_mod_href( 'forms/submissionview.php', 'id', $vpl->get_course_module()->id, 'userid', $user->id );
        $return->info = '<a href="' . $url . '">' . $info . '</a>';
    }
    return $return;
}

/**
 * Print a detailed report of what a user has done with a given particular instance of this
 * module
 *
 */
function vpl_user_complete($course, $user, $mod, $vpl) {
    require_once('vpl_submission.class.php');
    // TODO Print a detailed report of what a user has done with a given particular instance.
    // Search submisions for $user $instance.
    $vpl = new mod_vpl( null, $vpl->id );
    $sub = $vpl->last_user_submission( $user->id );
    if (! $sub) {
        $return = null;
    } else {
        $submission = new mod_vpl_submission( $vpl, $sub );
        $submission->print_info( true );
        $submission->print_grade( true );
    }
    return true;
}
/**
 * Returns all VPL assignments since a given time
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
        $activities [$index ++] = $activity;
    }
    return true;
}

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
        echo '<img src="' . $OUTPUT->pix_url('icon', 'vpl') . '" ' . 'class="icon" alt="' . $modname . '">';
        echo '<a href="' . $CFG->wwwroot . '/mod/vpl/view.php?id=' . $activity->cmid . '">';
        echo $activity->name;
        echo '</a>';
        echo '</div>';
    }
    if (isset($activity->grade)) {
        echo '<div class="grade">';
        echo get_string('grade').': ';
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

function vpl_extend_navigation(navigation_node $vplnode, $course, $module, $cm) {
    global $CFG, $USER, $DB;

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
    $strdescription = get_string( 'description', VPL );
    $strsubmission = get_string( 'submission', VPL );
    $stredit = get_string( 'edit', VPL );
    $strsubmissionview = get_string( 'submissionview', VPL );
    $urlforms = '/mod/' . VPL . '/forms/';
    $urlviews = '/mod/' . VPL . '/views/';
    if ($viewer) {
        $vplnode->add( $strdescription, new moodle_url( '/mod/vpl/view.php', $parm ), navigation_node::TYPE_SETTING );
    }
    $example = $vpl->get_instance()->example;
    $submitable = $manager || ($grader && $USER->id != $userid) || (! $grader && $submiter && $vpl->is_submit_able());
    if ($submitable && ! $example && ! $vpl->get_instance()->restrictededitor) {
        $vplnode->add( $strsubmission, new moodle_url( '/mod/vpl/forms/submission.php', $parm ), navigation_node::TYPE_SETTING );
    }
    if ($submitable) {
        $vplnode->add( $stredit, new moodle_url( '/mod/vpl/forms/edit.php', $parm ), navigation_node::TYPE_SETTING );
    }
    if (! $example) {
        if ($grader && $USER->id != $userid) {
            $text = get_string( 'grade' );
            $vplnode->add( $text, new moodle_url( '/mod/vpl/forms/gradesubmission.php', $parm ), navigation_node::TYPE_SETTING );
        }
        $vplnode->add( $strsubmissionview, new moodle_url( '/mod/vpl/forms/submissionview.php', $parm )
                       , navigation_node::TYPE_SETTING );
        if ($grader || $similarity) {
            $strlistprevoiussubmissions = get_string( 'previoussubmissionslist', VPL );
            $vplnode->add( $strlistprevoiussubmissions, new moodle_url( '/mod/vpl/views/previoussubmissionslist.php', $parm )
                           , navigation_node::TYPE_SETTING );
        }
        if ($grader || $manager) {
            $strsubmissionslist = get_string( 'submissionslist', VPL );
            $vplnode->add( $strsubmissionslist, new moodle_url( '/mod/vpl/views/submissionslist.php', $parm )
                           , navigation_node::TYPE_SETTING );
        }
        if ($similarity) {
            $strssimilarity = get_string( 'similarity', VPL );
            $vplnode->add( $strssimilarity, new moodle_url( '/mod/vpl/similarity/similarity_form.php', $parm )
                           , navigation_node::TYPE_SETTING );
        }
    }
}
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
        $strbasic = get_string( 'basic', VPL );
        $strtestcases = get_string( 'testcases', VPL );
        $strexecutionoptions = get_string( 'executionoptions', VPL );
        $menustrexecutionoptions = get_string( 'menuexecutionoptions', VPL );
        $strrequestedfiles = get_string( 'requestedfiles', VPL );
        $strexecution = get_string( 'execution', VPL );
        $vplindex = get_string( 'modulenameplural', VPL );
        $klist = $vplnode->get_children_key_list();
        if (count( $klist ) > 1) {
            $fkn = $klist [1];
        } else {
            $fkn = null;
        }
        if ( $userid != $USER->id ) {
            $parms = array ( 'id' => $cmid, 'userid' => $userid );
        } else {
            $parms = array ( 'id' => $cmid );
        }
        $node = $vplnode->create( $strtestcases, new moodle_url( '/mod/vpl/forms/testcasesfile.php', array (
                'id' => $PAGE->cm->id,
                'edit' => 3
        ) ), navigation_node::TYPE_SETTING );
        $vplnode->add_node( $node, $fkn );
        $urlexecutionoptions = new moodle_url( '/mod/vpl/forms/executionoptions.php', $parms );
        $node = $vplnode->create( $strexecutionoptions, $urlexecutionoptions, navigation_node::TYPE_SETTING );
        $vplnode->add_node( $node, $fkn );
        $urlrequiredfiles = new moodle_url( '/mod/vpl/forms/requiredfiles.php', $parms );
        $node = $vplnode->create( $strrequestedfiles, $urlrequiredfiles, navigation_node::TYPE_SETTING );
        $vplnode->add_node( $node, $fkn );
        $urlexecutionfiles = new moodle_url( '/mod/vpl/forms/executionfiles.php', $parms );
        $stradvancedsettings = get_string( 'advancedsettings' );
        $advance = $vplnode->create( $stradvancedsettings, $urlexecutionfiles, navigation_node::TYPE_CONTAINER);
        $vplnode->add_node( $advance, $fkn );
        $strexecutionlimits = get_string( 'maxresourcelimits', VPL );
        $strexecutionfiles = get_string( 'executionfiles', VPL );
        $menustrexecutionfiles = get_string( 'menuexecutionfiles', VPL );
        $menustrexecutionlimits = get_string( 'menuresourcelimits', VPL );
        $strvariations = get_string( 'variations', VPL );
        $strexecutionkeepfiles = get_string( 'keepfiles', VPL );
        $strexecutionlimits = get_string( 'maxresourcelimits', VPL );
        $strcheckjails = get_string( 'check_jail_servers', VPL );
        $strsetjails = get_string( 'local_jail_servers', VPL );
        $menustrexecutionkeepfiles = get_string( 'menukeepfiles', VPL );
        $menustrcheckjails = get_string( 'menucheck_jail_servers', VPL );
        $menustrsetjails = get_string( 'menulocal_jail_servers', VPL );
        $advance->add( $strexecutionfiles, new moodle_url( '/mod/vpl/forms/executionfiles.php', $parms )
                       , navigation_node::TYPE_SETTING );
        $advance->add( $strexecutionlimits, new moodle_url( '/mod/vpl/forms/executionlimits.php', $parms )
                       , navigation_node::TYPE_SETTING );
        $advance->add( $strexecutionkeepfiles, new moodle_url( '/mod/vpl/forms/executionkeepfiles.php', $parms )
                       , navigation_node::TYPE_SETTING );
        $advance->add( $strvariations, new moodle_url( '/mod/vpl/forms/variations.php', $parms )
                       , navigation_node::TYPE_SETTING );
        $advance->add( $strcheckjails, new moodle_url( '/mod/vpl/views/checkjailservers.php', $parms )
                       , navigation_node::TYPE_SETTING );
        if ($setjails) {
            $advance->add( $strsetjails, new moodle_url( '/mod/vpl/forms/local_jail_servers.php', $parms )
                       , navigation_node::TYPE_SETTING );
        }
        $testact = $vplnode->create( get_string( 'test', VPL ), new moodle_url( '/mod/vpl/forms/submissionview.php', $parms),
                    navigation_node::TYPE_CONTAINER);
        $vplnode->add_node( $testact, $fkn );
        $strdescription = get_string( 'description', VPL );
        $strsubmission = get_string( 'submission', VPL );
        $stredit = get_string( 'edit', VPL );
        $parmsuser = array (
                'id' => $PAGE->cm->id,
                'userid' => $USER->id
        );
        $strsubmissionview = get_string( 'submissionview', VPL );
        $testact->add( $strsubmission, new moodle_url( '/mod/vpl/forms/submission.php', $parms ), navigation_node::TYPE_SETTING );
        $testact->add( $stredit, new moodle_url( '/mod/vpl/forms/edit.php', $parms ), navigation_node::TYPE_SETTING );
        $testact->add( $strsubmissionview, new moodle_url( '/mod/vpl/forms/submissionview.php', $parms )
                       , navigation_node::TYPE_SETTING );
        $testact->add( get_string( 'grade' ), new moodle_url( '/mod/vpl/forms/gradesubmission.php', $parmsuser )
                       , navigation_node::TYPE_SETTING );
        $testact->add( get_string( 'previoussubmissionslist', VPL ), new moodle_url( '/mod/vpl/views/previoussubmissionslist.php'
                       , $parmsuser )
                       , navigation_node::TYPE_SETTING );
        $nodeindex = $vplnode->create( $vplindex, new moodle_url( '/mod/vpl/index.php', array (
                'id' => $PAGE->cm->course
        ) ), navigation_node::TYPE_SETTING );
        $vplnode->add_node( $nodeindex, $fkn );
    }
}

/**
 * Run periodically to check for vpl visibility update
 *
 * @uses $CFG
 * @return boolean
 *
 */
function vpl_cron() {
    global $DB;
    $rebuilds = array ();
    $now = time();
    $sql = 'SELECT id, startdate, duedate, course, name
    FROM {vpl}
    WHERE startdate > ?
      and startdate <= ?
      and (duedate > ? or duedate = 0)';
    $parms = array (
            $now - (2 * 3600),
            $now,
            $now
    );
    $vpls = $DB->get_records_sql( $sql, $parms );
    foreach ($vpls as $instance) {
        if (! instance_is_visible( VPL, $instance )) {
            $vpl = new mod_vpl( null, $instance->id );
            echo 'Setting visible "' . s( $vpl->get_printable_name() ) . '"';
            $cm = $vpl->get_course_module();
            $rebuilds [$cm->id] = $cm;
        }
    }
    foreach ($rebuilds as $cmid => $cm) {
        set_coursemodule_visible( $cm->id, true );
        rebuild_course_cache( $cm->course );
    }
    return true;
}

/**
 * Must return an array of user records (all data) who are participants for a given instance
 * of vpl. Must include every user involved in the instance, independient of his role
 * (student, teacher, admin...) See other modules as example.
 *
 * @param int $vplid ID of an instance of this module
 * @return mixed boolean/array of users
 */
function vpl_get_participants($vplid) {
    global $CFG, $DB;
    // Locate students.
    $submiters = $DB->get_records_sql( 'SELECT DISTINCT userid
    FROM {vpl_submissions}
    WHERE vpl = ?', array (
            $vplid
    ) );
    // Locate graders.
    $graders = $DB->get_records_sql( 'SELECT DISTINCT grader
    FROM {vpl_submissions}
    WHERE vpl = ? AND grader > 0', array (
            $vplid
    ) );

    // TODO Refactor to only one query.
    // Read users records.
    $participants = array ();
    foreach ($submiters as $submiter) {
        $user = $DB->get_record( 'user', array (
                'id' => $submiter->userid
        ) );
        if ($user) {
            $participants [$user->id] = $user;
        }
    }
    foreach ($graders as $grader) {
        if ($grader->grader > 0) { // Exist and Not automatic grader.
            $user = $DB->get_record( 'user', array (
                    'id' => $grader->grader
            ) );
            if ($user) {
                $participants [$user->id] = $user;
            }
        }
    }
    if (count( $participants ) > 0) {
        return $participants;
    } else {
        return false;
    }
}
function vpl_scale_used($vplid, $scaleid) {
    global $DB;
    return $scaleid and $DB->record_exists( VPL, array (
            'id' => "$vplid",
            'grade' => "-$scaleid"
    ) );
}

/**
 * Checks if scale is being used by any instance of vpl. This is used to find out if scale
 * used anywhere
 *
 * @param $scaleid int
 * @return boolean True if the scale is used by any vpl
 */
function vpl_scale_used_anywhere($scaleid) {
    global $DB;
    return $scaleid and $DB->record_exists( VPL, array (
            'grade' => "-$scaleid"
    ) );
}
function vpl_get_view_actions() {
    return array (
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
    );
}
function vpl_get_post_actions() {
    return array (
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
    );
}

/**
 * Removes all grades from gradebook
 *
 * @param int $courseid
 * @param
 *            string optional type
 */
function vpl_reset_gradebook($courseid, $type = '') {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    if ($cms = get_coursemodules_in_course( VPL, $course->id )) {
        foreach ($cms as $cmid => $cm) {
            $vpl = new mod_vpl( $cmid );
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
 * This function is used by the reset_course_userdata function in moodlelib. This function
 * will remove all posts from the specified vpl instance and clean up any related data.
 *
 * @param $data the
 *            data submitted from the reset course.
 * @return array status array
 */
function vpl_reset_userdata($data) {
    global $CFG, $DB;
    $status = array ();
    if ($data->reset_vpl_submissions) {
        $componentstr = get_string( 'modulenameplural', VPL );
        if ($cms = get_coursemodules_in_course( VPL, $data->courseid )) {
            foreach ($cms as $cmid => $cm) { // For each vpl instance in course.
                $vpl = new mod_vpl( $cmid );
                $instance = $vpl->get_instance();
                // Delete submissions records.
                $DB->delete_records( VPL_SUBMISSIONS, array (
                        'vpl' => $instance->id
                ) );
                // Delete variations assigned.
                $DB->delete_records( VPL_ASSIGNED_VARIATIONS, array (
                        'vpl' => $instance->id
                ) );
                // Delete submission files.
                fulldelete( $CFG->dataroot . '/vpl_data/' . $instance->id . '/usersdata' );
                $status [] = array (
                        'component' => $componentstr,
                        'item' => get_string( 'resetvpl', VPL, $instance->name ),
                        'error' => false
                );
            }
        }
    }
    return $status;
}

/**
 * Implementation of the function for printing the form elements that control whether
 * the course reset functionality affects the assignment.
 *
 * @param $mform form
 *            passed by reference
 */
function vpl_reset_course_form_definition(&$mform) {
    $mform->addElement( 'header', 'vplheader', get_string( 'modulenameplural', VPL ) );
    $mform->addElement( 'advcheckbox', 'reset_vpl_submissions', get_string( 'deleteallsubmissions', VPL ) );
}

/**
 * Course reset form defaults.
 */
function vpl_reset_course_form_defaults($course) {
    return array (
            'reset_vpl_submissions' => 1
    );
}

/*
 * any/all functions defined by the module should be in here. If the modulename is called
 * widget, then the required functions include: Other functions available but not required
 * are: o widget_delete_course() - code to clean up anything that would be leftover after
 * all instances are deleted o widget_process_options() - code to pre-process the form data
 * from module settings o widget_reset_course_form() and widget_delete_userdata() - used to
 * implement Reset course feature. To avoid possible conflict, any module functions should
 * be named starting with widget_ and any constants you define should start with WIDGET_
 */
