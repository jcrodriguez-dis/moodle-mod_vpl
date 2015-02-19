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
require_once dirname(__FILE__).'/locallib.php';
require_once dirname(__FILE__).'/list_util.class.php';
require_once $CFG->dirroot.'/course/lib.php';
/**
 * Create grade item for vpl
 *
 * @param object $assignment object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function vpl_update_grade_item($instance,$cm=null) {
    global $CFG;
    require_once $CFG->libdir.'/gradelib.php';
    $itemdetails = array('itemname' => $instance->name);
    $itemdetails['hidden'] = ($instance->visiblegrade>0)?0:1;
    if($cm!==null){
        $itemdetails['idnumber']= $cm->id;
    }
    if ($instance->grade > 0) {
        $itemdetails['gradetype'] = GRADE_TYPE_VALUE;
        $itemdetails['grademax']  = $instance->grade;
        $itemdetails['grademin']  = 0;
    } else if ($instance->grade < 0) {
        $itemdetails['gradetype'] = GRADE_TYPE_SCALE;
        $itemdetails['scaleid']   = -$instance->grade;
    } else {
        $itemdetails = array('deleted'=>1);
    }
    if($instance->example){
        $itemdetails = array('deleted'=>1);
    }
    grade_update('mod/vpl', $instance->course, 'mod', VPL,
                    $instance->id, 0, NULL, $itemdetails);
}

/**
 * Delete grade_item from a vpl instance+id
 *
 * @param $instance vpl instance
 **/
function vpl_delete_grade_item($instance) {
    global $CFG;
    require_once $CFG->libdir.'/gradelib.php';
    $itemdetails = array('deleted'=>1);
    grade_update('mod/vpl', $instance->course, 'mod', VPL,$instance->id, 0, NULL, $itemdetails);
}


/**
 * Create an event object from a vpl instance+id
 *
 * @param $instance vpl instance
 * @param $id vpl instance id
 * @return event object
 **/
function vpl_create_event($instance,$id){
    $event = new stdClass();
    $event->name        = $instance->name;
    $event->description = $instance->shortdescription;
    $event->format        = FORMAT_PLAIN;
    $event->courseid    = $instance->course;
    $event->modulename  = VPL;
    $event->instance    = $id;
    $event->eventtype   = 'duedate';
    $event->timestart   = $instance->duedate;
    return $event;
}

/**
 * Add a new vpl instance and return the id
 *
 * @param object from the form in mod_form.html
 * @return int id of the new vpl
 **/
function vpl_add_instance($instance) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/calendar/lib.php');
    vpl_truncate_VPL($instance);
    $id = $DB->insert_record(VPL, $instance);
    //Add event
    if ($instance->duedate) {
        calendar_event::create(vpl_create_event($instance,$id));
    }
    //Add grade to grade book
    $instance->id=$id;
    vpl_update_grade_item($instance);
    return $id;
}

/**
 * Update a vpl instance
 *
 * @param object from the form in mod.html
 * @return boolean OK
 *
 **/
function vpl_update_instance($instance) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/calendar/lib.php');
    vpl_truncate_VPL($instance);
    $instance->id = $instance->instance;
    //Update event
    $event = vpl_create_event($instance,$instance->id);
    if ($eventid = $DB->get_field('event', 'id', array('modulename' => VPL, 'instance' => $instance->id))) {
        $event->id = $eventid;
        $calendarevent = calendar_event::load($eventid);
        if ($instance->duedate) {
            $calendarevent->update($event);
        }else{
            $calendarevent->delete();
        }
    } else {
        if ($instance->duedate) {
            calendar_event::create($event);
        }
    }
    $cm = get_coursemodule_from_instance(VPL, $instance->id, $instance->course);
    vpl_update_grade_item($instance,$cm);
    return $DB->update_record(VPL, $instance);
}

/**
 * Delete an instance by id
 *
 * @param int $id Id instance
 * @return boolean OK
 **/
function vpl_delete_instance($id) {
    global $DB;
    $vpl = new mod_vpl(false,$id);
    $res = $vpl->delete_all();
    //Locate related VPLs and reset its basedon $id to 0
    $related = $DB->get_records_select(VPL,'basedon = ?',array($id), 'id','id,basedon');
    foreach($related as $other){
        $other->basedon=0;
        $DB->update_record(VPL,$other);
    }
    return $res;
}


/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function vpl_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_ADVANCED_GRADING:        return false;

        default: return null;
    }
}

/**
 * Return an object with short information about what a
 * user has done with a given particular instance of this module
 * $return->time = the time they did it
 * $return->info = a short text description
 * @param $course
 * @param $user
 * @param $mod
 * @param $instance
 * @return the object info
 **/
//TODO check uncomment

function vpl_user_outline($course, $user, $mod, $instance) {
    //Search submisions for $user $instance
    $vpl = new mod_vpl(null,$instance->id);
    $subinstance = $vpl->last_user_submission($user->id);
    if(!$subinstance) {
        $return = null;
    }
    else{
        require_once('vpl_submission.class.php');
        $return = new stdClass;
        $submission = new mod_vpl_submission($vpl,$subinstance);
        $return->time= $subinstance->datesubmitted;
        $subs=$vpl->user_submissions($user->id);
        if(count($subs)>1){
            $info = get_string('nsubmissions',VPL,count($subs));
        }else{
            $info = get_string('submission',VPL,count($subs));
        }
        if($subinstance->dategraded){
            $info .='<br />'.get_string('grade').': '.$submission->print_grade_core();
        }
        $url=vpl_mod_href('forms/submissionview.php','id',$vpl->get_course_module()->id,'userid',$user->id);
        $return->info = '<a href="'.$url.'">'.$info.'</a>';
    }
    return $return;
}

/**
 * Print a detailed report of what a user has done
 * with a given particular instance of this module
 * @param $course
 * @param $user
 * @param $mod
 * @param $instance
 * @return boolean OK
 **/
//TODO check uncomment

function vpl_user_complete($course, $user, $mod, $vpl) {
    require_once('vpl_submission.class.php');
    //TODO Print a detailed report of what a user has done with a given particular instance
    //Search submisions for $user $instance
    $vpl = new mod_vpl(null,$vpl->id);
    $sub = $vpl->last_user_submission($user->id);
    if(!$sub) {
        $return = null;
    }
    else{
        $submission = new mod_vpl_submission($vpl,$sub);
        $submission->print_info(true);
        $submission->print_grade(true);
    }
    return true;
}
/**
 * Returns all VPL assignments since a given time
 */
//TODO check uncomment

function vpl_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0)  {
    global $CFG, $USER, $DB;
    $grader = false;
    $vpl = new mod_vpl($cmid);
    $modinfo =& get_fast_modinfo($vpl->get_course());
    $cm = $modinfo->cms[$cmid];
    if ($vpl->is_visible()) {
        $vplid = $vpl->get_instance()->id;
        $grader = $vpl->has_capability(VPL_GRADE_CAPABILITY);
    }else{
        return;
    }
    $select = '(vpl = ?)';
    $select .= ' and ((datesubmitted >= ?) or (dategraded >= ?))';
    $parms = array($vplid, $timestart, $timestart);
    if(!$grader){ //Own activity
        array_unshift($parms, $USER->id);
        $select = '(userid = ?) and '.$select;
    }
    $subs = $DB->get_records_select(VPL_SUBMISSIONS,$select,$parms,'datesubmitted DESC');
    $aname = format_string($vpl->get_printable_name(),true);
    foreach ($subs as $sub) { //Show recent activity
        $activity = new stdClass();

        $activity->type         = 'vpl';
        $activity->cmid         = $cm->id;
        $activity->name         = $aname;
        $activity->sectionnum   = $cm->sectionnum;
        $activity->timestamp    = $sub->datesubmitted;

        if ($grader) {
            $activity->grade = $sub->grade;
        }
        $activity->user = $DB->get_record('user',array('id' => $sub->userid));
        $activities[$index++] = $activity;

    }
    return true;
}


/**
 *
 */
//TODO check uncomment

function vpl_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
    //TODO improve
    global $CFG,$OUTPUT;
    echo '<table border="0" cellpadding="3" cellspacing="0" class="forum-recent">';
    echo "<tr><td class=\"userpicture\" valign=\"top\">";
    echo $OUTPUT->user_picture($activity->user);
    echo '</td><td>';
    echo '<div class="user">';
    $fullname = fullname($activity->user, $viewfullnames);
    echo "<a href=\"$CFG->wwwroot/user/view.php?id={$activity->user->id}&amp;course=$courseid\">"
         ."{$fullname}</a> - ";
    $link = vpl_mod_href('forms/submissionview.php','id',$activity->cmid,'userid',$activity->user->id,'inpopup',1);
    echo '<a href="'.$link.'">'.userdate($activity->timestamp).'</a>';
    echo '</div>';
    echo "</td></tr></table>";

    return;

}



/**
 * Print activity of a course since a time
 *
 * @uses $CFG
 * @param $course instance object
 * @param $isteacher (not used here)
 * @param $timestart activity from
 * @return boolean (true==something printed)
 **/
//TODO check uncomment
/*
function vpl_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG, $USER, $DB;
    if (!$cms = get_coursemodules_in_course(VPL, $course->id)) {
        return false;
    }
    $vpls = array();
    $grader = false;
    //Get and select vpls to scan
    foreach ($cms as $cmid => $cm) {
        $vpl = new mod_vpl($cmid);
        if ($vpl->is_visible()) {
            $vplid = $vpl->get_instance()->id;
            $vpls[$vplid]=$vpl;
            $grader = $grader || $vpl->has_capability(VPL_GRADE_CAPABILITY);
        }
    }
    if(count($vpls) ==0){ //No VPL to scan
        return false;
    }
    $select = '(vpl IN ('.implode(',',array_keys($vpls)).'))';
    $select .= ' and ((datesubmitted >= '.$timestart.') or (dategraded >= '.$timestart.'))';
    if(!$grader){ //Own activity
        $select = '(userid = '.$USER->id.') and '.$select;
    }
    $subs = $DB->get_records_select(VPL_SUBMISSIONS,$select,'datesubmitted DESC');
    $activities = array();
    $norepeat = array();
    $norepeatuser = array();
    foreach ($subs as $sub) {
        //Show if own user or VPL_GRADE_CAPABILITY
        if(isset($norepeat[$sub->vpl]) || isset($norepeatuser[$sub->userid])){ //No repeat activity
            continue;
        }
        $vpl = $vpls[$sub->vpl];
        if(($USER->id == $sub->userid && $sub->datesubmitted >= $timestart) || $vpl->has_capability(VPL_GRADE_CAPABILITY) ){
            $activities[] = $sub;
        }
        if(!$vpl->has_capability(VPL_GRADE_CAPABILITY)){ //No repeat activity if no grader capability
            $norepeat[$sub->vpl] = true;
        }else{
            $norepeatuser[$sub->userid] = 1;
        }
    }
    if(count($activities)){
        print_headline(get_string('modulenameplural', VPL).':');
        foreach ($activities as $sub) { //Show recent activity
            $vpl = $vpls[$sub->vpl];
               $url=vpl_mod_href('forms/submissionview.php','id',$vpl->get_course_module()->id,
                                   'userid',$sub->userid,'submissionid',$sub->id);
               if($sub->datesubmitted > $sub->dategraded || !$vpl->has_capability(VPL_GRADE_CAPABILITY)){
                   $str = $vpl->get_printable_name().' ('.get_string('submission',VPL).')';
                   $date = $sub->datesubmitted;
               }else{
                   $str = $vpl->get_printable_name().' ('.get_string('grade').')';
                   $date = $sub->dategraded;
               }
               $user = $DB->get_record('user',array('id' => $sub->userid));
            print_recent_activity_note($date, $user, $str, $url, false, $grader);
        }
        return true;
    }
    return false;
}
*/
/**
 * Given a course_module object, this function returns any "extra" information that may be needed
 * whenprinting this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param $coursemodule object The coursemodule object (record).
 * @return object An object on information that the coures will know about (most noticeably, an icon).
 * fields all optional extra, icon, name.
 *
 */
/*
function vpl_get_coursemodule_info($coursemodule) {
    global $CFG;
    print_r($coursemodule);
    $ret = new Object();
        $ret->icon = $CFG->wwwroot.'/mod/vpl/icon.gif';
    $vpl = new mod_vpl($coursemodule->id);
    $instance=$vpl->get_instance();
    if($instance->example){ //Is example
        $ret->icon = $CFG->wwwroot.'/mod/vpl/icon_yellow.gif';
        $ret->name=$vpl->get_instance()->name.' '.get_string('example',VPL);
        return;
    }
    if($instance->grade==0){ //Not grade_able
        $ret->icon = $CFG->wwwroot.'/mod/vpl/icon_green.gif';
        return;
    }
    if($instance->automaticgrading){ //Automatic grading
        $ret->icon = $CFG->wwwroot.'/mod/vpl/icon_red.gif';
    }
    if($instance->duedate>0 && $instance->duedate<time()){ //Closed
        $ret->icon = $CFG->wwwroot.'/mod/vpl/icon_black.gif';
        return;
    }
    return $ret;
}*/

function vpl_extend_navigation(navigation_node $vplnode, $course, $module, $cm){
    global $CFG, $USER, $DB;
    //FIXME
    //Student
        //Descripción
        //Entrega
        //Edición
        //Ver entrega
        //En grupos visibles lista de entregas
    //Profesor
        //Descripción
        //Lista de entregas
        //Similaridad
        //Si se esta accediendo a la información de un alumno
            //Entrega
            //Editar
            //Ver entrega
            //Calificar
            //Lista de entregas previas
    $vpl = new mod_vpl($cm->id);
    $viewer = $vpl->has_capability(VPL_VIEW_CAPABILITY);
    $submiter = $vpl->has_capability(VPL_SUBMIT_CAPABILITY);
    $similarity = $vpl->has_capability(VPL_SIMILARITY_CAPABILITY);
    $grader = $vpl->has_capability(VPL_GRADE_CAPABILITY);
    $manager = $vpl->has_capability(VPL_MANAGE_CAPABILITY);
    $userid = optional_param('userid',false,PARAM_INT);
    if(!$userid && $USER->id != $userid){
        $parm = array('id' => $cm->id,'userid' => $userid);
    }else{
        $userid=$USER->id;
        $parm = array('id' => $cm->id);
    }
    $strdescription = get_string('description',VPL);
    $strsubmission = get_string('submission',VPL);
    $stredit = get_string('edit',VPL);
    $strsubmissionview = get_string('submissionview',VPL);
    $urlforms = '/mod/'.VPL.'/forms/';
    $urlviews = '/mod/'.VPL.'/views/';
    if($viewer){
        $vplnode->add($strdescription,new moodle_url('/mod/vpl/view.php', $parm), navigation_node::TYPE_SETTING);
    }
    $example = $vpl->get_instance()->example;
    $submit_able = $manager || ($grader && $USER->id != $userid)
                 || (!$grader && $submiter && $vpl->is_submit_able());
    if($submit_able    && !$example && !$vpl->get_instance()->restrictededitor){
        $vplnode->add($strsubmission,new moodle_url('/mod/vpl/forms/submission.php', $parm), navigation_node::TYPE_SETTING);
    }
    if($submit_able){
        $vplnode->add($stredit,new moodle_url('/mod/vpl/forms/edit.php', $parm), navigation_node::TYPE_SETTING);
    }
    if(!$example){
        if($grader && $USER->id != $userid){
            $text=get_string('grade');
            $vplnode->add($text,new moodle_url('/mod/vpl/forms/gradesubmission.php', $parm), navigation_node::TYPE_SETTING);
        }
        $vplnode->add($strsubmissionview,new moodle_url('/mod/vpl/forms/submissionview.php', $parm), navigation_node::TYPE_SETTING);
        if($grader || $similarity){
            $strlistprevoiussubmissions = get_string('previoussubmissionslist',VPL);
            $vplnode->add($strlistprevoiussubmissions, new moodle_url('/mod/vpl/views/previoussubmissionslist.php', $parm), navigation_node::TYPE_SETTING);
        }
        if($grader || $manager){
            $strsubmissionslist = get_string('submissionslist',VPL);
            $vplnode->add($strsubmissionslist, new moodle_url('/mod/vpl/views/submissionslist.php', $parm), navigation_node::TYPE_SETTING);
        }
        if($similarity){
            $strssimilarity = get_string('similarity',VPL);
            $vplnode->add($strssimilarity, new moodle_url('/mod/vpl/similarity/similarity_form.php', $parm), navigation_node::TYPE_SETTING);
        }
    }
}

function vpl_extend_settings_navigation(settings_navigation $settings, navigation_node $vplnode){
    global $CFG,$PAGE,$USER;
    if(!isset($PAGE->cm->id)){
        return;
    }
    $cmid=$PAGE->cm->id;
    $context = context_module::instance($cmid);
    $manager = has_capability(VPL_MANAGE_CAPABILITY,$context);
    $setjails = has_capability(VPL_SETJAILS_CAPABILITY,$context);
    if($manager){
        $userid = optional_param('userid',NULL,PARAM_INT);
        $strbasic = get_string('basic',VPL);
        $strtestcases = get_string('testcases',VPL);
        $strexecutionoptions = get_string('executionoptions',VPL);
        $menustrexecutionoptions = get_string('menuexecutionoptions',VPL);
        $strrequestedfiles = get_string('requestedfiles',VPL);
        $strexecution = get_string('execution',VPL);
        $vplindex = get_string('modulenameplural',VPL);
        $klist = $vplnode->get_children_key_list();
        if(count($klist)>1){
            $fkn = $klist[1];
        }else{
            $fkn = null;
        }
        $parms = array('id' => $PAGE->cm->id);
        $node = $vplnode->create($strtestcases, new moodle_url('/mod/vpl/forms/testcasesfile.php', array('id' => $PAGE->cm->id, 'edit' => 3)), navigation_node::TYPE_SETTING);
        $vplnode->add_node($node,$fkn);
        $node = $vplnode->create($strexecutionoptions, new moodle_url('/mod/vpl/forms/executionoptions.php', $parms), navigation_node::TYPE_SETTING);
        $vplnode->add_node($node,$fkn);
        $node = $vplnode->create($strrequestedfiles, new moodle_url('/mod/vpl/forms/requiredfiles.php', $parms), navigation_node::TYPE_SETTING);
        $vplnode->add_node($node,$fkn);
        $advance = $vplnode->create(get_string('advancedsettings'), null, navigation_node::TYPE_CONTAINER);
        $vplnode->add_node($advance,$fkn);
        $strexecutionlimits = get_string('maxresourcelimits',VPL);
        $strexecutionfiles = get_string('executionfiles',VPL);
        $menustrexecutionfiles = get_string('menuexecutionfiles',VPL);
        $menustrexecutionlimits = get_string('menuresourcelimits',VPL);
        $strvariations = get_string('variations',VPL);
        $strexecutionkeepfiles = get_string('keepfiles',VPL);
        $strexecutionlimits = get_string('maxresourcelimits',VPL);
        $strcheckjails = get_string('check_jail_servers',VPL);
        $strsetjails = get_string('local_jail_servers',VPL);
        $menustrexecutionkeepfiles = get_string('menukeepfiles',VPL);
        $menustrcheckjails = get_string('menucheck_jail_servers',VPL);
        $menustrsetjails = get_string('menulocal_jail_servers',VPL);
        $advance->add($strexecutionfiles,new moodle_url('/mod/vpl/forms/executionfiles.php', $parms), navigation_node::TYPE_SETTING);
        $advance->add($strexecutionlimits,new moodle_url('/mod/vpl/forms/executionlimits.php', $parms), navigation_node::TYPE_SETTING);
        $advance->add($strexecutionkeepfiles,new moodle_url('/mod/vpl/forms/executionkeepfiles.php', $parms), navigation_node::TYPE_SETTING);
        $advance->add($strvariations,new moodle_url('/mod/vpl/forms/variations.php', $parms), navigation_node::TYPE_SETTING);
        $advance->add($strcheckjails,new moodle_url('/mod/vpl/views/checkjailservers.php', $parms), navigation_node::TYPE_SETTING);
        if($setjails){
            $advance->add($strsetjails,new moodle_url('/mod/vpl/forms/local_jail_servers.php', $parms), navigation_node::TYPE_SETTING);
        }
        $testact = $vplnode->create(get_string('test',VPL), null, navigation_node::TYPE_CONTAINER);
        $vplnode->add_node($testact,$fkn);
        $strdescription = get_string('description',VPL);
        $strsubmission = get_string('submission',VPL);
        $stredit = get_string('edit',VPL);
        $parmsuser = array('id' => $PAGE->cm->id, 'userid' => $USER->id);
        $strsubmissionview = get_string('submissionview',VPL);
        $testact->add($strsubmission,new moodle_url('/mod/vpl/forms/submission.php', $parms), navigation_node::TYPE_SETTING);
        $testact->add($stredit,new moodle_url('/mod/vpl/forms/edit.php', $parms), navigation_node::TYPE_SETTING);
        $testact->add($strsubmissionview,new moodle_url('/mod/vpl/forms/submissionview.php', $parms), navigation_node::TYPE_SETTING);
        $testact->add(get_string('grade'),new moodle_url('/mod/vpl/forms/gradesubmission.php', $parmsuser), navigation_node::TYPE_SETTING);
        $testact->add(get_string('previoussubmissionslist',VPL),new moodle_url('/mod/vpl/views/previoussubmissionslist.php', $parmsuser), navigation_node::TYPE_SETTING);
        $nodeindex = $vplnode->create($vplindex, new moodle_url('/mod/vpl/index.php', array('id'=>$PAGE->cm->course)), navigation_node::TYPE_SETTING);
        $vplnode->add_node($nodeindex,$fkn);
    }
}

/**
 * Run periodically to check for vpl visibility update
 *
 * @uses $CFG
 * @return boolean
 **/
function vpl_cron() {
    global $DB;
    $rebuilds = array();
    $now = time();
    $sql = 'SELECT id, startdate, duedate, course, name
    FROM {vpl}
    WHERE startdate > ?
      and startdate <= ?
      and (duedate > ? or duedate = 0)';
    $parms = array($now-(2*3600),$now,$now);
    $vpls = $DB->get_records_sql($sql,$parms);
    foreach ($vpls as $instance) {
        if(!instance_is_visible(VPL,$instance)){
            $vpl = new mod_vpl(null,$instance->id);
            echo 'Setting visible "'.s($vpl->get_printable_name()).'"';
            $cm = $vpl->get_course_module();
            $rebuilds[$cm->id] = $cm;
        }
    }
    foreach($rebuilds as $cmid => $cm){
        set_coursemodule_visible($cm->id,true);
        rebuild_course_cache($cm->course);
    }
    return true;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of vpl. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $vplid ID of an instance of this module
 * @return mixed boolean/array of users
 **/
function vpl_get_participants($vplid) {
    global $CFG,$DB;
    //Locate students
    $submiters = $DB->get_records_sql('SELECT DISTINCT userid
    FROM {vpl_submissions}
    WHERE vpl = ?',array($vplid));
    //Locate graders
    $graders = $DB->get_records_sql('SELECT DISTINCT grader
    FROM {vpl_submissions}
    WHERE vpl = ? AND grader > 0',array($vplid));

    //TODO Refactor to only one query
    //Read users records
    $participants = array();
    foreach ($submiters as $submiter) {
        $user = $DB->get_record('user',array('id' => $submiter->userid));
        if($user){
            $participants[$user->id] = $user;
        }
    }
    foreach ($graders as $grader) {
        if($grader->grader>0){ //Exist and Not automatic grader
            $user = $DB->get_record('user',array('id' => $grader->grader));
            if($user){
                $participants[$user->id] = $user;
            }
        }
    }
    if(count($participants)>0){
        return $participants;
    }else{
        return false;
    }
}

function vpl_scale_used ($vplid,$scaleid) {
    global $DB;
    return $scaleid and $DB->record_exists(VPL, array('id' => "$vplid",'grade' => "-$scaleid"));
}


/**
 * Checks if scale is being used by any instance of vpl.
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any vpl
 */
function vpl_scale_used_anywhere($scaleid) {
    global $DB;
    return $scaleid and $DB->record_exists(VPL, array('grade' => "-$scaleid"));
}

function vpl_get_view_actions() {
    return array('view', 'view all', 'view all submissions',  'run','debug', 'edit submission',
                 'execution keep file form', 'execution limits form',
                'edit full description', 'view grade', 'Diff', 'view similarity',
                'view watermarks', 'similarity form', 'view previous'
                );
}

function vpl_get_post_actions() {
    return array('save submision', 'evaluate', 'execution save keeplist',
                'execution save limits', 'execution save options', 'execution options form',
                'save full description', 'remove grade', 'upload submission', 'variations form'
                );
}

/**
 * Removes all grades from gradebook
 * @param int $courseid
 * @param string optional type
 */
function vpl_reset_gradebook($courseid, $type='') {
    global $CFG;
    require_once $CFG->libdir.'/gradelib.php';
    if ($cms = get_coursemodules_in_course(VPL, $course->id)) {
        foreach ($cms as $cmid => $cm) {
            $vpl = new mod_vpl($cmid);
            $instance = $vpl->get_instance();
            $itemdetails = array('reset'=>1);
            grade_update('mod/vpl', $instance->course, 'mod', VPL,$instance->id, 0, NULL, $itemdetails);
        }
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all posts from the specified vpl instance
 * and clean up any related data.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function vpl_reset_userdata($data) {
    global $CFG,$DB;
    $status = array();
    if($data->reset_vpl_submissions){
        $componentstr = get_string('modulenameplural', VPL);
        if ($cms = get_coursemodules_in_course(VPL, $data->courseid)) {
            foreach ($cms as $cmid => $cm) { //For each vpl instance in course
                $vpl = new mod_vpl($cmid);
                $instance = $vpl->get_instance();
                //Delete submissions records
                $DB->delete_records(VPL_SUBMISSIONS, array('vpl' => $instance->id));
                //Delete variations assigned
                $DB->delete_records(VPL_ASSIGNED_VARIATIONS,array('vpl' => $instance->id));
                //Delete submission files
                fulldelete(    $CFG->dataroot.'/vpl_data/'.$data->courseid.'/'.$instance->id.'/usersdata');
                $status[] = array('component'=>$componentstr, 'item'=>get_string('resetvpl', VPL, $instance->name), 'error'=>false);
            }
        }
    }
    return $status;
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the assignment.
 * @param $mform form passed by reference
 */
function vpl_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'vplheader', get_string('modulenameplural', VPL));
    $mform->addElement('advcheckbox', 'reset_vpl_submissions', get_string('deleteallsubmissions',VPL));
}

/**
 * Course reset form defaults.
 */
function vpl_reset_course_form_defaults($course) {
    return array('reset_vpl_submissions'=>1);
}

/*
 * any/all functions defined by the module should be in here. If the modulename is called widget, then the required functions include:
 * Other functions available but not required are:
 o widget_delete_course() - code to clean up anything that would be leftover after all instances are deleted
 o widget_process_options() - code to pre-process the form data from module settings
 o widget_reset_course_form() and widget_delete_userdata() - used to implement Reset course feature.
 * To avoid possible conflict, any module functions should be named starting with widget_ and any constants you define should start with WIDGET_
 */
