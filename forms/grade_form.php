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
 * @version        $Id: grade_form.php,v 1.27 2013-07-09 13:30:03 juanca Exp $
 * @package mod_vpl. Grade form definition
 * @copyright    2012 Juan Carlos Rodríguez-del-Pino
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author        Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once $CFG->libdir.'/formslib.php';
require_once $CFG->libdir.'/gradelib.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/form.class.php';

class mod_vpl_grade_form extends vpl_form {
    protected $vpl;
    protected function get_scale_selection(){
        global $DB;
        $vplinstance = $this->vpl->get_instance();
        $scaleid = $this->vpl->get_grade();
        $options = array();
        $options[-1]= get_string('nograde');
        if ($scaleid > 0) {
            for($i = 0 ; $i <= $scaleid; $i++){
                $options[$i] = $i.' / '.$scaleid;
            }
        } elseif($scaleid < 0) {
            $scaleid = -$scaleid;
            if ($scale = $DB->get_record('scale', array('id' => $scaleid))) {
                $options = $options + make_menu_from_list($scale->scale);
            }
        }
        return $options;
    }

    function __construct($page,& $vpl){
        $this->vpl = & $vpl;
        parent::__construct($page);
    }
    function definition(){
        global $CFG, $OUTPUT;
        $id = required_param('id',PARAM_INT);
        $userid = optional_param('userid',null,PARAM_INT);
        $inpopup = optional_param('inpopup',0,PARAM_INT);
        $this->addHidden('id',$id);
        $this->addHidden('userid',$userid);
        $submissionid=optional_param('submissionid',0,PARAM_INT);
        if($submissionid>0){
            $this->addHidden('submissionid',$submissionid);
        }
        $this->addHidden('inpopup',$inpopup);
        $vpl_instance = $this->vpl->get_instance();
        //TODO Improve grade form (recalculate grade)
        //Show assesment criteria
        //Show others evaluation
        //Type value => introduce value
        $grade = $this->vpl->get_grade();
        if($grade !=0){
            $this->addHTML(s(get_string('grade').' '));
            if($grade>0){
                $this->addText('grade','',6);
            }
            else{
                $this->addSelect('grade',$this->get_scale_selection());
            }
            $this->addHTML(' &nbsp;');
        }
        $this->addSubmitButton('save',get_string('grade'));
        if($inpopup){
            $this->addSubmitButton('savenext',get_string('gradeandnext',VPL));
        }
        $this->addSubmitButton('removegrade',get_string('removegrade',VPL));
        //tranfer files to teacher's work area
        $url=vpl_mod_href('forms/edit.php','id',
                $id,'userid',$userid,'privatecopy',1);
        $options = array('height' => 550, 'width' => 780, 'directories' =>0, 'location' =>0, 'menubar'=>0,
            'personalbar'=>0,'status'=>0,'toolbar'=>0);
        $action = new popup_action('click', $url,'privatecopy'.($vpl_instance->id),$options);
        $this->addHTML($OUTPUT->action_link($url, get_string('copy',VPL),$action));

        if($vpl_instance->evaluate){
            //Link to recalculate numeric grade from comments
            $url=vpl_mod_href('forms/evaluation.php','id',
                     $id,'userid',$userid,'grading',1,'inpopup',$inpopup);
            $html=' <a href="'.$url.'">'.s(get_string('evaluate',VPL)).'</a>';
            $this->addHTML($html);
        }
        //Numeric grade
        if($grade >0){
            //Link to recalculate numeric grade from comments
            $jscript='VPL.calculateGrade('.$grade.')';
            $html=' <a href="javascript:void(0);" onclick="'.$jscript.'">'.s(get_string('calculate',VPL)).'</a>';
            $this->addHTML($html);
        }
        //TODO user similarity
/*        $url=vpl_mod_href('similarity/user_similarity.php','id',$id,'userid',$userid);
        $html=link_to_popup_window($url,'similarity'.$id.'-'.$userid,get_string('similarity',VPL),800,900,null,null,true);
        $this->addHTML(' '.$html);*/
        $this->addHTML('<br />');
        if($grade !=0){
            $this->addHTML(s(get_string('comments',VPL)).'<br />');
            $this->addTextArea('comments','',8,70);
            $this->addHTML('<br />');
        }
        if(!empty($CFG->enableoutcomes)){
            $grading_info = grade_get_grades($this->vpl->get_course()->id, 'mod', 'vpl',
                    $vpl_instance->id, $userid);
            if (!empty($grading_info->outcomes)) {
                $this->addHTML('<table border="0">');
                 foreach($grading_info->outcomes as $oid=>$outcome) {
                    $this->addHTML('<tr><td align="right">');
                     $options = make_grades_menu(-$outcome->scaleid);
                     $options[0] = get_string('nooutcome', 'grades');
                    $this->addHTML(s($outcome->name));
                    $this->addHTML('</td><td>');
                    $this->addSelect('outcome_grade_'.$oid,$options,$outcome->grades[$userid]->grade);
                    $this->addHTML('</td></tr>');
                 }
                 $this->addHTML('</table>');
             }
        }
    }
    function display(){
        global $OUTPUT;
        echo $OUTPUT->box_start();
        parent::display();
        echo $OUTPUT->box_end();
    }
}

