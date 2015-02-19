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
 * @version        $Id: gradesubmission.php,v 1.25 2013-04-23 11:50:35 juanca Exp $
 * @package mod_vpl. Grade submission
 * @copyright    2012 Juan Carlos Rodríguez-del-Pino
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author        Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
global $CFG, $USER;
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/grade_form.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../vpl_submission.class.php';

function vpl_grade_header($vpl,$inpopup){
    if($inpopup){
        $vpl->print_header_simple();
    }else{
        $vpl->print_header(get_string('grade'));
        $vpl->print_view_tabs(basename(__FILE__));
    }
}
require_login();

vpl_include_jsfile('grade.js',false);
vpl_include_jsfile('hide_footer.js',false);
vpl_include_jsfile('updatesublist.js',false);
$PAGE->requires->css(new moodle_url('/mod/vpl/css/sh.css'));
$PAGE->requires->css(new moodle_url('/mod/vpl/editor/VPLIDE.css'));


$id = required_param('id',PARAM_INT);
$userid = required_param('userid',PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('forms/gradesubmission.php', array('id' => $id, 'userid' => $userid));

$jscript = '';
$inpopup = optional_param('inpopup',0,PARAM_INT);
$vpl->require_capability(VPL_GRADE_CAPABILITY);
//Read records
$submissionid =  optional_param('submissionid',FALSE,PARAM_INT);
if($submissionid){
    $subinstance = $DB->get_record('vpl_submissions',array('id' => $submissionid));
}else{
    $subinstance = $vpl->last_user_submission($userid);
}
//Check consistence
if(!$subinstance){
    vpl_grade_header($vpl,$inpopup);
    notice(get_string('nosubmission',VPL),vpl_mod_href('view.php','id',$id,'userid',$userid));
}
$submissionid = $subinstance->id;

if($vpl->is_inconsistent_user($subinstance->userid,$userid)){
    vpl_grade_header($vpl,$inpopup);
    print_error('vpl submission user inconsistence');
}
if($vpl->get_instance()->id != $subinstance->vpl){
    vpl_grade_header($vpl,$inpopup);
    print_error('vpl submission vpl inconsistence');
}
$submission = new mod_vpl_submission($vpl,$subinstance);
if($inpopup){
    $link = vpl_mod_href('forms/gradesubmission.php','id',$id,'userid',$userid,'inpopup',$inpopup);
}else{
    $link = vpl_mod_href('forms/gradesubmission.php','id',$id,'userid',$userid);
}
$linkrel = vpl_rel_url('forms/gradesubmission.php','id',$id,'userid',$userid);
//no marked or marked by current user or automatic
if($subinstance->dategraded== 0 || $subinstance->grader == $USER->id || $subinstance->grader == 0){
    if($inpopup){
        $href=$link;
        //vpl_rel_url('gradesubmission.php','inpopup',1);
    }else{
        $href='gradesubmission.php';
    }
    $grade_form = new mod_vpl_grade_form($href,$vpl);
    if ($grade_form->is_cancelled()){ //Grading canceled
        vpl_inmediate_redirect($link);
    } else if ($fromform=$grade_form->get_data()){ //Grade (new or update)
        if(isset($fromform->evaluate)){
            $url=vpl_mod_href('forms/evaluation.php','id',
                     $fromform->id,'userid',$fromform->userid,'grading',1,'inpopup',$inpopup);
            vpl_inmediate_redirect($url);
        }
        if(isset($fromform->removegrade)){
            vpl_grade_header($vpl,$inpopup);
            if($submission->remove_grade()){
                \mod_vpl\event\submission_grade_deleted::log($submission);
                if($inpopup){
                    //FIXME don't work
                    //Change grade info at parent window
                    $jscript .='VPL.updatesublist('.$submission->get_instance()->id.',';
                    $jscript.="' ',' ',' ');";
                    echo vpl_include_js($jscript);
                }
                vpl_redirect($link,get_string('graderemoved',VPL),5);
            }else{
                vpl_redirect($link,get_string('gradenotremoved',VPL),5);
            }
            die;
        }
        vpl_grade_header($vpl,$inpopup);
        if(!isset($fromform->grade) && !isset($fromform->savenext)){
            print_error('badinput');
            die;
        }

        if($submission->is_graded()){
            $action = 'update grade';
        }else{
            $action = 'grade';
        }
        //Build log info
        $log_info='grade: '.$fromform->grade;
        foreach($fromform as $key => $value){
            if(strpos($key,'outcome_grade')===0){
                $on= substr($key,strlen('outcome_grade_'));
                $log_info .= ' o'.$on.' '.$value;
            }
        }
        if(!$submission->set_grade($fromform)){
            vpl_redirect($link,get_string('gradenotsaved',VPL),5);
        }
        if($action == 'grade'){
            \mod_vpl\event\submission_graded::log($submission);
        }else{
            \mod_vpl\event\submission_grade_updated::log($submission);
        }

        if($inpopup){
            //Change grade info at parent window
            $text = $submission->print_grade_core();
            $grader = fullname($submission->get_grader($USER->id));
            $gradedon = userdate($submission->get_instance()->dategraded);

            $jscript .='VPL.updatesublist('.$submission->get_instance()->id.',';
            $jscript .='\''.addslashes($text).'\',';
            $jscript .='\''.addslashes($grader).'\',';
            $jscript .='\''.addslashes($gradedon)."');\n";
            if(isset($fromform->savenext)){
                $url=$CFG->wwwroot.'/mod/vpl/forms/gradesubmission.php?id='.$id.'&inpopup=1&userid=';
                $jscript .='VPL.go_next(\''.$submission->get_instance()->id.'\',\''.addslashes($url).'\');';
            }else{
                $jscript .= 'window.close();';
            }
        }else{
            vpl_redirect($link,get_string('graded',VPL),2);
        }
        $vpl->print_footer();
        echo vpl_include_js($jscript);
        die;
    } else {
        //Show grade form
        vpl_grade_header($vpl,$inpopup);

        \mod_vpl\event\submission_grade_viewed::log($submission);
        $data = new stdClass();
        $data->id = $vpl->get_course_module()->id;
        $data->userid = $subinstance->userid;
        $data->submissionid = $submissionid;
        if($submission->is_graded()){
            //format number removing trailing zeros
            $data->grade = rtrim(rtrim($subinstance->grade,'0'),'.,');
            $data->comments = $submission->get_grade_comments();
        }else{
            $res=$submission->getCE();
            if($res['executed']){
                $data->grade = $submission->proposedGrade($res['execution']);
                $data->comments = $submission->proposedComment($res['execution']);;
            }
        }
        if(!empty($CFG->enableoutcomes)){
            $grading_info = grade_get_grades($vpl->get_course()->id, 'mod', 'vpl',
                    $vpl->get_instance()->id, $userid);
            if (!empty($grading_info->outcomes)) {
                 foreach($grading_info->outcomes as $oid=>$outcome) {
                     $field='outcome_grade_'.$oid;
                     $data->$field=$outcome->grades[$userid]->grade;
                 }
             }
        }

        $grade_form->set_data($data);
        echo '<div id="vpl_grade_view" style="height:220px">';
        echo '<div id="vpl_grade_form" style="float:left">';
        $grade_form->display();
        echo '</div>';
        echo '<div id="vpl_grade_comments" style="float:left;width:40%;overflow:auto">';
        $comments=$vpl->get_grading_help();
        if($comments>''){
            echo $OUTPUT->box_start();
            echo '<b>'.get_string('listofcomments',VPL).'</b><hr />';
            echo $comments;
            echo $OUTPUT->box_end();
        }
        echo '</div>';
        echo '</div>';
        echo '<div id="vpl_submission_view" style="clear:both;overflow:auto;" >';
        echo '<hr />';
        $vpl->print_variation( $subinstance->userid);
        $submission->print_submission();
        echo '</div>';
        $jscript .= 'VPL.hlrow('.$submissionid.');';
        $jscript .= 'window.onunload= function(){VPL.unhlrow('.$submissionid.');};';
    }
}else{
    vpl_inmediate_redirect(vpl_mod_href('forms/submissionview.php','id',$id,'userid',$userid));
}
$vpl->print_footer_simple();
echo vpl_include_js($jscript);
