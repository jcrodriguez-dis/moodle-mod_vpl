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
 * Graph working time for a vpl instance and/or a user
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
function vpl_get_working_periods($vpl,$userid){
    $submissionslist = $vpl->user_submissions($userid);
    if(count($submissionslist) == 0){
        return array();
    }
    $submissionslist=array_reverse($submissionslist);
    $workperiods=array();
    if($submissionslist){
        $last_save_time=0;
        $rest_time=20*60; //20 minutes. Rest period before next work
        $first_work=10*59; //10 minutes. Work before first save
        $intervals=-1;
        $work_start=0;
        foreach ($submissionslist as $submission) {
            /*Start new work period*/
            if($submission->datesubmitted-$last_save_time >= $rest_time){
                if($work_start>0){ //Is not the first submission
                    if($intervals>0){//First work as average
                        $first_work = (float)($last_save_time-$work_start)/$intervals;
                    }//else use the last $first_work
                    $workperiods[]=($last_save_time-$work_start+$first_work)/(3600.0);
                }
                $work_start=$submission->datesubmitted;
                $intervals=0;
            }else{//Count interval
                $intervals++;
            }
            $last_save_time=$submission->datesubmitted;
        }
        if($intervals>0){//First work as average
            $first_work = (float)($last_save_time-$work_start)/$intervals;
        }//else use the last $first_work
        $workperiods[]=($last_save_time-$work_start+$first_work)/(3600.0);
    }
    return $workperiods;
}

require_once dirname(__FILE__).'/vpl_graph.class.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../vpl_submission.class.php';

require_login();

$id = required_param('id', PARAM_INT);
$userid = optional_param('userid',-1, PARAM_INT);
$type = optional_param('type', 0, PARAM_INT);
$vpl = new mod_vpl($id);
$course = $vpl->get_course();
$vpl->require_capability(VPL_GRADE_CAPABILITY);
//No log
if($userid<0){
    $cm = $vpl->get_course_module();
    $context_module = $vpl->get_context();
    $currentgroup = groups_get_activity_group($cm);
    if(!$currentgroup){
        $currentgroup='';
    }
    $list = $vpl->get_students($currentgroup);
    $submissions = $vpl->all_last_user_submission();
    //Get all information
    $all_data = array();
    foreach ($list as $userinfo) {
        if($vpl->is_group_activity() && $userinfo->id != $vpl->get_group_leaderid($userinfo->id)){
            continue;
        }
        $working_periods = vpl_get_working_periods($vpl, $userinfo->id);
        if(count($working_periods)>0){
            $all_data[]=$working_periods;
            $users_id[]=$userinfo->id;
        }
    }
    session_write_close();
    //for every student, total time, number of period
    $total_time=0;
    $max_student_time=0;
    $max_period_time=0;
    $total_periods=0;
    $times=array();
    foreach ($all_data as $working_periods) {
        $total_periods += count($working_periods);
        $time=0;
        foreach($working_periods as $period){
            $time+=$period;
            $max_period_time = max($max_period_time,$period);
        }
        $total_time += $time;
        $max_student_time = max($max_student_time,$time);
        $times[]=$time;
    }
    if($max_student_time <= 3){
        $time_slice=0.25;
        $x_format="%3.2f-%3.2f";
    }elseif($max_student_time <= 6){
        $time_slice=0.50;
        $x_format="%3.1f-%3.1f";
    }else{
        $time_slice=1;
        $x_format="%3.0f-%3.0f";
    }
    $y_data=array();
    $x_data=array();
    for($slice=0; $slice <= $max_student_time; $slice+=$time_slice){
        $y_data[]=0;
        $x_data[]=sprintf($x_format,$slice,($slice+$time_slice));
    }
    foreach($times as $time){
        $y_data[(int)($time/$time_slice)]++;
    }
    $title=$vpl->get_printable_name();
    $n=count($times);
    $straveragetime = get_string('averagetime',VPL,sprintf('%3.1f',((float)$total_time/$n)));
    $straverageperiods = get_string('averageperiods',VPL,sprintf('%3.1f',((float)$total_periods/$n)));
    $strvmaximumperiod = get_string('maximumperiod',VPL,sprintf('%3.1f',((float)$max_period_time)));
    $x_title=sprintf('%s - %s - %s - %s',
            get_string('hours'),
            $straveragetime,
            $straverageperiods,
            $strvmaximumperiod
            );
    $y_title=get_string('defaultcoursestudents');
    vpl_graph::draw($title,$x_title,$y_title,
            $x_data,$y_data,null,true);

}else{
    $y_data=vpl_get_working_periods($vpl, $userid);
    session_write_close();
    $x_data=array();
    $hours=0.0;
    for($i=0; $i<count($y_data); $i++){
        $x_data[]=$i+1;
        $hours+=$y_data[$i];
    }
    $user = $DB->get_record('user',array('id' => $userid));
    $title =sprintf("%s - %s",
            $vpl->fullname($user,false),
            get_string('numhours','',sprintf('%3.2f',$hours)));
    $title_x = get_string('workingperiods',VPL).' - '.$vpl->get_printable_name();
    vpl_graph::draw($title,$title_x,get_string('hours'),
                    $x_data,$y_data,null,true);
}
