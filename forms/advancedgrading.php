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
 * Variation definitions form
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
 
require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once($CFG->libdir.'/formslib.php');
 
 
 
class mod_vpl_advancedgrading_form extends moodleform {
    protected $vpl;
    protected $submission;
    // Parm $varid = -1 new variation.
    public function __construct($page, $vplid, $submissionid) {
        global $DB;
        $this->vpl = new mod_vpl( $vplid);
        if ($submissionid>0) {
            $this->submission = new mod_vpl_submission( $this->vpl,$submissionid );
        } else {
            $this->submission =  $this->vpl->last_user_submission( $userid );
        }
         parent::__construct( $page );
    }
    protected function definition() {
       
        $userid = optional_param( 'userid', null, PARAM_INT );
        // Add advanced grading.
        $gradeid = $this->vpl->get_grade_info()->id;
        $gradinginstance = $this->submission->get_grading_instance($userid, $gradeid);
 
        if ($gradinginstance) {
            $grade = $this->vpl->get_grade();
            $res = $this->submission->getCE();
            if ($res ['executed']) {
                $graderaw = $this->submission->proposedGrade($res['execution']);
            }else{
                $graderaw = 0;
            }
            $gridscore = $gradinginstance->get_controller()->get_min_max_score()['maxscore'];
            $mform = & $this->_form;
            
            $mform->addElement( 'header', 'hadvancedgrading' , get_string('gradingmanagement', 'core_grading') );
            $mform->addElement( 'hidden', 'id', required_param( 'id', PARAM_INT ) );
            $mform->setType( 'id', PARAM_INT );
            $mform->addElement( 'hidden', 'userid', $userid );
            $mform->setType( 'userid', PARAM_INT );
        
            $mform->addElement('grading',
                                'advancedgrading',
                                 '',
                               array('gradinginstance' => $gradinginstance)); 
             $mform->addElement('hidden','advancedgradinginstanceid', $gradinginstance->get_id());
             $mform->setType('advancedgradinginstanceid', PARAM_INT);
            // Numeric grade.
            if ($grade > 0) {
                // Link to recalculate numeric grade from comments.
                $jscript = 'VPL.mergeGrade(' . $grade . ','.$graderaw.','.$gridscore.')';
                $html = ' <a class="btn btn-default" href="javascript:void(0);" onclick="' . $jscript . '">' . s( get_string( 'merge', VPL ) ) . '</a>';
                $mform->addElement('html', $html );
            }
            //$this->add_action_buttons( true, get_string( 'submit' ) );
            
        }
    }
}
