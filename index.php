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
 * List all VPL instances in a course
 *
 * @package mod_vpl
 * @copyright 2009 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodriguez-del-Pino
 **/

require_once dirname(__FILE__).'/../../config.php';
require_once dirname(__FILE__).'/locallib.php';
require_once dirname(__FILE__).'/list_util.class.php';
require_once dirname(__FILE__).'/vpl_submission.class.php';

$id = required_param('id', PARAM_INT);   // course

$sort=vpl_get_set_session_var('sort','');
$sortdir = vpl_get_set_session_var('sortdir','down');
$instanceselection = vpl_get_set_session_var('selection','all');

//Check course existence
if (! $course = $DB->get_record("course", array('id' => $id))) {
    print_error('invalidcourseid','',$id);
}
require_course_login($course);
//Load strings
$burl = vpl_rel_url(basename(__FILE__),'id',$id);
$strname                  = get_string('name').' '.vpl_list_util::vpl_list_arrow($burl,'name',$instanceselection,$sort,$sortdir);
$strvpls                 = get_string('modulenameplural',VPL);
$strshortdescription     = get_string('shortdescription', VPL).' '.vpl_list_util::vpl_list_arrow($burl,'shortdescription',$instanceselection,$sort,$sortdir);
$strstartdate        = get_string('startdate', VPL).' '.vpl_list_util::vpl_list_arrow($burl,'startdate',$instanceselection,$sort,$sortdir);
$strduedate                = get_string('duedate', VPL).' '.vpl_list_util::vpl_list_arrow($burl,'duedate',$instanceselection,$sort,$sortdir);
$strnopls                 = get_string('novpls', VPL);

$PAGE->set_url('/mod/vpl/index.php',array('id' => $id));
$PAGE->navbar->add($strvpls);
$PAGE->requires->css(new moodle_url('/mod/vpl/css/index.css'));
$PAGE->set_title($strvpls);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($strvpls);

$einfo = array('context' => \context_course::instance($course->id));
$event = \mod_vpl\event\course_module_instance_list_viewed::create($einfo);
$event->trigger();

//Print selection by instance state

$url_base= new moodle_url('/mod/vpl/index.php',array('id' => $id,'sort' => $sort,'sortdir' => $sortdir));
$urls=array();
$urlindex = array();
$url_base->param('selection','all');
$selected=$url_base->out(false);
$urls[$selected] = get_string('all');
$urlindex['all'] = $selected;
foreach(array('open','closed','timelimited','timeunlimited',
                'automaticgrading','manualgrading','examples') as $sel){
    $url_base->param('selection',$sel);
    $urls[$url_base->out(false)] = get_string($sel,VPL);
    $urlindex[$sel] = $url_base->out(false);
}
echo $OUTPUT->url_select($urls,$urlindex[$instanceselection],array());

if (!$cms = get_coursemodules_in_course(VPL, $course->id,"m.shortdescription, m.startdate, m.duedate")) {
    notice($strnopls, vpl_abs_href('/course/view.php','id',$course->id));
    die;
}
$ovpls = get_all_instances_in_course(VPL,$course);
$timenow = time();
$vpls = array();
//Get and select vpls to show
foreach ($ovpls as $ovpl) {
    $vpl = new mod_vpl(false,$ovpl->id);
    $instance = $vpl->get_instance();
    if ($vpl->is_visible()) {
        switch($instanceselection){
            case 'all':
                $vpls[]=$vpl;
                break;
            case 'open':
                $min = $instance->startdate;
                $max = $instance->duedate == 0 ? PHP_INT_MAX:$instance->duedate;
                if($timenow >= $min && $timenow <= $max){
                    $vpls[]=$vpl;
                }
                break;
            case 'closed':
                $min = $instance->startdate;
                $max = $instance->duedate == 0 ? PHP_INT_MAX:$instance->duedate;
                if($timenow < $min || $timenow > $max){
                    $vpls[]=$vpl;
                }
                break;
            case 'timelimited':
                if($instance->duedate > 0){
                    $vpls[]=$vpl;
                }
                break;
            case 'timeunlimited':
                if($instance->duedate == 0){
                    $vpls[]=$vpl;
                }
                break;
            case 'automaticgrading':
                if($instance->grade !=0 && $instance->automaticgrading > 0){
                    $vpls[]=$vpl;
                }
                break;
            case 'manualgrading':
                if($vpl->get_grade() !=0 && $instance->automaticgrading == 0){
                    $vpls[]=$vpl;
                }
                break;
            case 'examples':
                if($instance->example){
                    $vpls[]=$vpl;
                }
                break;
        }
    }
}
//Is the user a grader?
$grader = false;
$student = false;
$startdate = false;
$duedate = false;
$no_grade = true;
foreach ($vpls as $vpl) {
    if($vpl->has_capability(VPL_GRADE_CAPABILITY)){
        $grader = true;
    }elseif($vpl->has_capability(VPL_SUBMIT_CAPABILITY)){
        $student =true;
    }
    $instance = $vpl->get_instance();
    if($vpl->get_grade() !=0 && !$instance->example){
        $no_grade=false;
    }
    if($instance->startdate>0){
        $startdate=true;
    }
    if($instance->duedate>0){
        $duedate=true;
    }
}
//if no instance with grade
$grader = $grader && !$no_grade;
$student = $student && !$no_grade;

//usort of old PHP versions don't call static class functions
if($sort>''){
    $corder = new vpl_list_util;
    $corder->set_order($sort,$sortdir=='down');
    usort($vpls,array($corder,'cpm'));
}
//Generate table
$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
$table->head  = array ('#',$strname, $strshortdescription);
$table->align = array ('left','left', 'left');
if($startdate){
    $table->head[]  = $strstartdate;
    $table->align[] = 'center';
}
if($duedate){
    $table->head[]  = $strduedate;
    $table->align[] = 'center';
}
if($grader && !$no_grade){
    $table->head[] = get_string('submissions',VPL);
    $table->head[] = get_string('graded',VPL);
    $table->align[] = 'right';
    $table->align[] = 'right';
}
if($student && !$no_grade){
    $table->head[] = get_string('grade');
    $table->align[] = 'left';
}
$table->data = array();
$totalsubs=0;
$totalgraded=0;
foreach ($vpls as $vpl) {
    $instance = $vpl->get_instance();
    $url = vpl_rel_url('view.php','id',$vpl->get_course_module()->id);
    $row = array (count($table->data)+1,"<a href='$url'>{$vpl->get_printable_name()}</a>",
            s($instance->shortdescription));
    if($startdate){
        $row[] = $instance->startdate>0?userdate($instance->startdate):'';
    }
    if($duedate){
        $row[] = $instance->duedate>0?userdate($instance->duedate):'';
    }
    if($grader){
        if($vpl->has_capability(VPL_GRADE_CAPABILITY)
            && $vpl->get_grade() != 0
            && !$instance->example){
            $info = vpl_list_util::count_graded($vpl);
            $totalsubs += $info['submissions'];
            $totalgraded += $info['graded'];
            $url = vpl_rel_url('views/submissionslist.php','id',$vpl->get_course_module()->id,'selection','allsubmissions');
            $row[]='<a href="'.$url.'">'.$info['submissions'].'</a>';
            //Need mark?
            if( $info['submissions'] > $info['graded'] &&
                $vpl->get_grade() != 0 &&
                !($instance->duedate != 0 && $instance->duedate > time())){
                $url = vpl_rel_url('views/submissionslist.php','id',$vpl->get_course_module()->id,'selection','notgraded');
                $diff=$info['submissions'] - $info['graded'];
                $row[]='<div class="vpl_nm">'.$info['graded'].' <a href="'.$url.'">('.$diff.')</a><div>';
            }else{
                //No grade able
                if($vpl->get_grade() == 0 && $info['graded']==0){
                    $row[]='-';
                }else{
                    $row[]=$info['graded'];
                }
            }
        }else{
            $row[]='';
            $row[]='';
        }
    }
    if($student){
        if(!$vpl->has_capability(VPL_GRADE_CAPABILITY)
            && $vpl->has_capability(VPL_SUBMIT_CAPABILITY)
            && $vpl->get_grade() != 0
            && !$instance->example){
            $subinstance = $vpl->last_user_submission($USER->id);
            if($subinstance){ //Submitted
                $submission = new mod_vpl_submission($vpl,$subinstance);
                if($subinstance->dategraded>0 && $vpl->get_visiblegrade()){
                    $text = $submission->print_grade_core();
                }else{
                    $result=$submission->getCE();
                    $text='';
                    if($result['executed']!==0){
                        $prograde=$submission->proposedGrade($result['execution']);
                        if($prograde>''){
                            $text=get_string('proposedgrade',VPL,$submission->print_grade_core($prograde));
                        }
                    }else{
                        $text=get_string('nograde');
                    }
                }
            }else{//No submitted
                $text = get_string('nosubmission',VPL);
                if($vpl->is_submit_able()){
                    $text = '<div class="vpl_nm">'.$text.'</div>';
                }
            }
            $row[]=$text;
        }else{
            $row[]='-';
        }
    }else{

    }
    $table->data[] = $row;
}
if($totalsubs>0){
    $row = array ('','','');
    if($startdate){
        $row[] = '';
    }
    if($duedate){
        $row[] = '';
    }
    end($row);
    $row[key($row)] = get_string('total');
    $row[] = $totalsubs;
    $row[] = $totalgraded;
    $table->data[] = $row;
}
echo "<br />";
echo html_writer::table($table);

echo $OUTPUT->footer();
