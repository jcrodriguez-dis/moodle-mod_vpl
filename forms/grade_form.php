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
 * Grade form definition
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
 
defined( 'MOODLE_INTERNAL' ) || die();
 
require_once(dirname(__FILE__).'/../../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once(dirname(__FILE__).'/../locallib.php');
 
class mod_vpl_grade_form extends moodleform {
    protected $vpl;
    protected $submission;
    protected function get_scale_selection() {
        global $DB;
        $vplinstance = $this->vpl->get_instance();
        $scaleid = $this->vpl->get_grade();
        $options = array ();
        $options [- 1] = get_string( 'nograde' );
        if ($scaleid > 0) {
       for ($i = 0; $i <= $scaleid; $i ++) {
                $options [$i] = $i . ' / ' . $scaleid;
            }
        } else if ($scaleid < 0) {
            $scaleid = - $scaleid;
            if ($scale = $DB->get_record( 'scale', array (
                    'id' => $scaleid
            ) )) {
                $options = $options + make_menu_from_list( $scale->scale );
            }
        }
        return $options;
    }
    public function __construct($page, & $vpl,& $submission) {
        $this->vpl = & $vpl;
        $this->submission = & $submission;
        parent::__construct( $page );
    }
    protected function definition() {
        global $CFG, $OUTPUT;
        $mform = & $this->_form;
        $id = required_param( 'id', PARAM_INT );
        $userid = optional_param( 'userid', null, PARAM_INT );
        $inpopup = optional_param( 'inpopup', 0, PARAM_INT );
        $mform->addElement('hidden', 'id', $id );
        $mform->setType( 'id', PARAM_INT );
        $mform->addElement('hidden', 'userid', $userid );
        $mform->setType( 'userid', PARAM_INT );
        $submissionid = optional_param( 'submissionid', 0, PARAM_INT );
        if ($submissionid > 0) {
            $mform->addElement('hidden', 'submissionid', $submissionid );
        }
        $mform->addElement('hidden', 'inpopup', $inpopup );
        $mform->setType( 'inpopup', PARAM_INT );
        $vplinstance = $this->vpl->get_instance();
        $grade = $this->vpl->get_grade();
        // TODO Improve grade form (recalculate grade).
        // Show assesment criteria.
        // Show others evaluation.
        // Type value => introduce value.
        // Add advanced grading.
        $gradeid = $this->vpl->get_grade_info();
          $gradinginstance = $this->submission->get_grading_instance();
 
        if ($gradinginstance) {
            $res = $this->submission->getCE();
            if ($res ['executed']) {
                $graderaw = $this->submission->proposedGrade($res['execution']);
            }else{
                $graderaw = 0;
            }
            $gridscore = $gradinginstance->get_controller()->get_min_max_score()['maxscore'];
           
            $mform->addElement('header','hAdvancedGrading', get_string( 'gradingmanagement','grading') );
            $mform->addElement('grading',
                               'advancedgrading',
                                '',
                       array('gradinginstance' => $gradinginstance)); 
            $mform->addElement('hidden','advancedgradinginstanceid', $gradinginstance->get_id());
            $mform->setType('advancedgradinginstanceid', PARAM_INT);
        }
        // Numeric grade.
            if ($grade > 0) {
                // Link to recalculate numeric grade from comments.
                $jscript = 'VPL.mergeGrade(' . $grade . ','.$graderaw.','.$gridscore.')';
                //~ $html = ' <a class="btn btn-default" href="javascript:void(0);" onclick="' . $jscript . '">' . s( get_string( 'merge', VPL ) ) . '</a>';
                //~ $mform->addElement('html', $html );
                $mform->addElement('button','btnmerge', get_string( 'merge', VPL ),'onclick="' . $jscript . '"' );
                //$mform->registerNoSubmitButton('btnmerge');
            }
        $mform->addElement('header','hGrade', get_string( 'grade') );
        //$mform->addElement('html','<div id="vpl_grade_form">');
            
        $buttonarray=array();
        if ($grade != 0) {
            if ($grade > 0) {
                $buttonarray[] =& $mform->createElement('text', 'grade', '', 'size="6"' );
                $mform->setType( 'grade', PARAM_INT );
            } else {
                $buttonarray[] =& $mform->createElement('select', 'grade', $this->get_scale_selection() );
            }
        }
        $buttonarray[] =& $mform->createElement('submit', 'save', get_string( 'grade' ) );
                if ($inpopup) {
            $buttonarray[] =& $mform->createElement('submit', 'savenext', get_string( 'gradeandnext', VPL ) );
        }
        $buttonarray[] =& $mform->createElement('submit', 'removegrade', get_string( 'removegrade', VPL ) );
        // Tranfer files to teacher's work area.
        $url = vpl_mod_href( 'forms/edit.php', 'id', $id, 'userid', $userid, 'privatecopy', 1 );
        $options = array (
                'height' => 550,
                'width' => 780,
                'directories' => 0,
                'location' => 0,
                'menubar' => 0,
                'personalbar' => 0,
                'status' => 0,
                'toolbar' => 0
        );
        $action = new popup_action( 'click', $url, 'privatecopy' . ($vplinstance->id), $options );
        //~ $mform->addElement('html', $OUTPUT->action_link( $url, get_string( 'copy', VPL ), $action,array('class' =>'btn btn-primary') ) );
        //~ $buttonarray[] =& $mform->createElement('html', $OUTPUT->action_link( $url, get_string( 'copy', VPL ), $action,array('class' =>'btn btn-primary') ) );
        $buttonarray[] =& $mform->createElement('button', 'copy',get_string( 'copy', VPL ),'onclick="windows.open(' .  $url. ');"' );
 
        if ($vplinstance->evaluate) {
            // Link to recalculate numeric grade from comments.
            $url = vpl_mod_href( 'forms/evaluation.php', 'id', $id, 'userid', $userid, 'grading', 1, 'inpopup', $inpopup );
            $html = ' <a class="btn btn-primary" href="' . $url . '">' . s( get_string( 'evaluate', VPL ) ) . '</a>';
            //~ $mform->addElement('html', $html );
            $buttonarray[] =& $mform->createElement('button', 'evaluate',get_string( 'evaluate', VPL ),'onclick="windows.open(' . $url . ');"');
        }
        // Numeric grade.
        if ($grade > 0) {
            // Link to recalculate numeric grade from comments.
            $jscript = 'VPL.calculateGrade(' . $grade . ')';
 
            $html = ' <a class="btn btn-primary" href="javascript:void(0);" onclick="' . $jscript . '">' . s( get_string( 'calculate', VPL ) ) . '</a>';
            //~ $mform->addElement('html', $html );
            $buttonarray[] =& $mform->createElement('button', 'calculate',get_string( 'calculate', VPL ),'onclick="' . $jscript . '"' );
        }
        $mform->addGroup($buttonarray, 'buttonar', get_string('grades'), array(' '), false);
        //$mform->addElement('html', '</div>' );
        $textarray=array();
        if ($grade != 0) {
            //$mform->addElement('html','</br><b>'.s( get_string( 'comments', VPL )).'</b></br>' );
            $textarray[] =& $mform->createElement('textarea', 'comments', get_string( 'comments', VPL ), 'rows="18" cols="70"' );
        }
         
 
        //$output = '<div id="vpl_grade_comments">';
        $comments = $this->vpl->get_grading_help();
        if ($comments > '') {
            $output .= $OUTPUT->box_start();
            $output .= '<b>' . s(get_string( 'listofcomments', VPL )) . '</b><hr />';
            $output .= $comments;
            $output .= $OUTPUT->box_end();
        }
        //$output .= '</div>';
        
        $textarray[] =& $mform->createElement('static', $comments);
        $mform->addGroup($textarray, 'textar', get_string( 'comments', VPL ), array(' '), false);
       
        
        if (! empty( $CFG->enableoutcomes )) {
            $gradinginfo = grade_get_grades( $vpl->get_course()->id, 'mod', 'vpl', $vplinstance->id, $userid );
            if (! empty( $gradinginfo->outcomes )) {
                $mform->addElement('html', '<table border="0">' );
                foreach ($gradinginfo->outcomes as $oid => $outcome) {
                    $mform->addElement('html', '<tr><td align="right">' );
                    $options = make_grades_menu( - $outcome->scaleid );
                    $options [0] = get_string( 'nooutcome', 'grades' );
                    $mform->addElement('html', s( $outcome->name ) );
                    $mform->addElement('html', '</td><td>' );
                    $mform->addElement('select', 'outcome_grade_' . $oid, $options, $outcome->grades [$userid]->grade );
                    $mform->addElement('html', '</td></tr>' );
                }
                $mform->addElement('html', '</table>' );
            }
        }
    }
    public function display() {
        global $OUTPUT;
        echo $OUTPUT->box_start();
        parent::display();
        echo $OUTPUT->box_end();
    }
}
 