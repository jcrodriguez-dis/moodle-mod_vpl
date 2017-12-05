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

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/form.class.php');
require_once(dirname(__FILE__).'/../vpl_submission_CE.class.php');

class mod_vpl_grade_form extends vpl_form {
    protected $submission;
    protected function get_scale_selection() {
        global $DB;
        $vpl = $this->submission->get_vpl();
        $vplinstance = $vpl->get_instance();
        $scaleid = $vpl->get_grade();
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
    public function __construct($page, $submission) {
        $this->submission = $submission;
        parent::__construct( $page );
    }
    protected function definition() {
        global $CFG, $OUTPUT;
        $vpl = $this->submission->get_vpl();
        $vplinstance = $vpl->get_instance();
        $instance = $this->submission->get_instance();
        $id = required_param( 'id', PARAM_INT );
        $userid = optional_param( 'userid', null, PARAM_INT );
        $inpopup = optional_param( 'inpopup', 0, PARAM_INT );
        $this->addHidden( 'id', $id );
        $this->addHidden( 'userid', $userid );
        $this->addHidden( 'submissionid', $instance->id );
        $this->addHidden( 'inpopup', $inpopup );
        // TODO Improve grade form (recalculate grade).
        // Show assesment criteria.
        // Show others evaluation.
        // Type value => introduce value.
        $grade = $vpl->get_grade();
        if ($grade != 0) {
            $this->addHTML( s( get_string( 'grade' ) . ' ' ) );
            if ($grade > 0) {
                $this->addText( 'grade', '', 6 );
                $this->submission->grade_reduction($reduction, $percent);
                if ($reduction > 0) {
                    $value = $reduction;
                    if ($percent) {
                        $value = (100 - ( $value * 100 ) );
                        $value = format_float($value, 2, true, true) . '%';
                    } else {
                        $value = format_float($value, 2, true, true);
                    }
                    $this->addHTML( ' -' . $value . ' ' );
                }
            } else {
                $this->addSelect( 'grade', $this->get_scale_selection() );
            }
            $this->addHTML( ' &nbsp;' );
        }
        $class = " class='btn btn-secondary'";
        $this->addSubmitButton( 'save', get_string( 'grade' ) );
        if ($inpopup) {
            $this->addSubmitButton( 'savenext', get_string( 'gradeandnext', VPL ) );
        }
        $this->addSubmitButton( 'removegrade', get_string( 'removegrade', VPL ) );
        $this->addHTML( '<br>' );
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
        $atributes = array('class' => 'btn btn-secondary');
        $this->addHTML( ' ' . $OUTPUT->action_link( $url, get_string( 'copy', VPL ), $action,  $atributes) );

        if ($vplinstance->evaluate) {
            // Link to recalculate numeric grade from comments.
            $url = vpl_mod_href( 'forms/evaluation.php', 'id', $id, 'userid', $userid, 'grading', 1, 'inpopup', $inpopup );
            $html = " <a href='$url' $class>" . s( get_string( 'evaluate', VPL ) ) . '</a>';
            $this->addHTML( $html );
        }
        // Numeric grade.
        if ($grade > 0) {
            // Link to recalculate numeric grade from comments.
            $jscript = 'VPL.calculateGrade(' . $grade . ')';
            $html = " <a href='javascript:void(0);' onclick='$jscript' $class>" . s( get_string( 'calculate', VPL ) ) . '</a>';
            $this->addHTML( $html );
        }

        $this->addHTML( '<br />' );
        if ($grade != 0) {
            $this->addHTML( s( get_string( 'comments', VPL ) ) . '<br />' );
            $this->addTextArea( 'comments', '', 8, 70 );
            $this->addHTML( '<br />' );
        }
        if (! empty( $CFG->enableoutcomes )) {
            $gradinginfo = grade_get_grades( $vpl->get_course()->id, 'mod', 'vpl', $vplinstance->id, $userid );
            if (! empty( $gradinginfo->outcomes )) {
                $this->addHTML( '<table border="0">' );
                foreach ($gradinginfo->outcomes as $oid => $outcome) {
                    $this->addHTML( '<tr><td align="right">' );
                    $options = make_grades_menu( - $outcome->scaleid );
                    $options [0] = get_string( 'nooutcome', 'grades' );
                    $this->addHTML( s( $outcome->name ) );
                    $this->addHTML( '</td><td>' );
                    $this->addSelect( 'outcome_grade_' . $oid, $options, $outcome->grades [$userid]->grade );
                    $this->addHTML( '</td></tr>' );
                }
                $this->addHTML( '</table>' );
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
