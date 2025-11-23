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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once(dirname(__FILE__) . '/../locallib.php');

/**
 * Form to grade a VPL submission.
 *
 * This form allows teachers to grade a submission, add comments, and manage advanced grading instances.
 * It includes options for importing grades from previous submissions and merging advanced grading grid points.
 */
class mod_vpl_grade_form extends moodleform {
    /**
     * @var mod_vpl $vpl The VPL instance to grade.
     */
    protected $vpl;

    /**
     * @var mod_vpl_submission $submission Th submission to grade.
     */
    protected $submission;

    /**
     * Constructor for the grade form.
     *
     * @param moodle_page $page The page object.
     * @param mod_vpl $vpl The VPL instance.
     * @param mod_vpl_submission $submission The submission to grade.
     */
    public function __construct($page, &$vpl, &$submission) {
        $this->vpl = & $vpl;
        $this->submission = & $submission;
        parent::__construct($page);
    }

    /**
     * Defines the form elements for grading a submission.
     */
    protected function definition() {
        global $CFG, $OUTPUT, $PAGE;
        $mform = & $this->_form;
        $id = required_param('id', PARAM_INT);
        $userid = optional_param('userid', null, PARAM_INT);
        $inpopup = optional_param('inpopup', 0, PARAM_INT);
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);
        $submissionid = optional_param('submissionid', false, PARAM_INT);
        if ($submissionid !== false) {
            $mform->addElement('hidden', 'submissionid', $submissionid);
            $mform->setType('submissionid', PARAM_INT);
            $islastsubmission = $this->vpl->last_user_submission($userid)->id == $submissionid;
        } else {
            $islastsubmission = true;
        }

        $mform->addElement('hidden', 'inpopup', $inpopup);
        $mform->setType('inpopup', PARAM_INT);
        $vplinstance = $this->vpl->get_instance();
        $grade = $this->vpl->get_grade();
        // TODO Show others evaluation.

        $gradinginstance = $this->submission->get_grading_instance();
        if ($gradinginstance) {
            $res = $this->submission->getCE();
            if ($res['executed']) {
                $graderaw = $this->submission->proposedGrade($res['execution']);
            } else {
                $graderaw = 0;
            }
            $gridscore = $gradinginstance->get_controller()->get_min_max_score()['maxscore'];

            $mform->addElement('header', 'hAdvancedGrading', get_string('gradingmanagement', 'grading'));
            $mform->addElement(
                'grading',
                'advancedgrading',
                '',
                [ 'gradinginstance' => $gradinginstance ]
            );
            $mform->addElement('hidden', 'advancedgradinginstanceid', $gradinginstance->get_id());
            $mform->setType('advancedgradinginstanceid', PARAM_INT);
            // Numeric grade.
            if ($grade > 0) {
                // Button to merge advanced grading grid points with grade.
                $group = [];
                $group[] =& $mform->createElement(
                    'button',
                    null,
                    get_string('merge', VPL),
                    [
                                'data-role' => 'mergegrade',
                                'data-maxgrade' => $grade,
                                'data-currentgrade' => $graderaw,
                                'data-maxgridpoints' => $gridscore,
                        ]
                );

                $group[] =& $mform->createElement('html', $OUTPUT->help_icon('merge', VPL));
                $mform->addGroup($group);
            }
        }
        $mform->addElement('header', 'hGrade', get_string(vpl_get_gradenoun_str()));
        $mform->setExpanded('hGrade');

        $buttonarray = [];
        if ($grade != 0) {
            if ($grade > 0) {
                $buttonarray[] =& $mform->createElement('text', 'grade', '', 'size="6"');
                $mform->setType('grade', PARAM_FLOAT);
            } else {
                $buttonarray[] =& $mform->createElement(
                    'select',
                    'grade',
                    '',
                    [ get_string('nograde') ] + make_grades_menu($grade)
                );
            }
        }
        $buttonarray[] =& $mform->createElement('submit', 'save', get_string('dograde', VPL));
        if ($inpopup) {
            $buttonarray[] =& $mform->createElement('submit', 'savenext', get_string('gradeandnext', VPL));
        }
        $buttonarray[] =& $mform->createElement('submit', 'removegrade', get_string('removegrade', VPL));
        // Tranfer files to teacher's work area.
        $url = new moodle_url('/mod/vpl/forms/edit.php', [ 'id' => $id, 'userid' => $userid, 'privatecopy' => 1 ]);
        if (!$islastsubmission) {
            $url->param('submissionid', $submissionid);
        }
        $buttonarray[] =& $mform->createElement('html', static::get_formgroup_button_link($url, 'copy', true));

        if ($islastsubmission) {
            $url = new moodle_url('/mod/vpl/forms/evaluation.php', [
                    'id' => $id,
                    'userid' => $userid,
                    'grading' => 1,
                    'inpopup' => $inpopup,
            ]);
            $buttonarray[] =& $mform->createElement('html', static::get_formgroup_button_link($url, 'evaluate'));
        }
        // Numeric grade.
        if ($grade > 0) {
            // Link to recalculate numeric grade from comments.
            $buttonarray[] =& $mform->createElement(
                'button',
                null,
                get_string('calculate', VPL),
                [ 'data-role' => 'calculategrade', 'data-maxgrade' => $grade ]
            );
            $buttonarray[] =& $mform->createElement('html', $OUTPUT->help_icon('calculate', VPL));
        }
        $loadgradinghelpbutton = '<button type="button" class="btn btn-secondary" id="vpl_load_grading_help">';
        $loadgradinghelpbutton .= get_string('listofcomments', VPL);
        $loadgradinghelpbutton .= '</button>';
        $buttonarray[] =& $mform->createElement('html', $loadgradinghelpbutton);
        $mform->addGroup($buttonarray, 'buttonar', get_string(vpl_get_gradenoun_str()), '', false);

        if ($grade != 0) {
            // Create the textarea element.
            $textarea = $mform->createElement('textarea', 'comments', get_string('comments', VPL), 'rows="18" cols="70"');
            $mform->setType('comments', PARAM_TEXT);
            // Create the side panel with the grading help button.
            $panelcontent = '<div id="vpl_grading_help_panel" class="d-none"></div>';
            $sidepanel = $mform->createElement('html', $panelcontent);
            // Group them together.
            $group = [];
            $group[] = $textarea;
            $group[] = $sidepanel;

            $mform->addGroup($group, 'comments_group', get_string('comments', VPL), ' ', false);
        }

        if (! empty($CFG->enableoutcomes)) {
            $gradinginfo = grade_get_grades($this->vpl->get_course()->id, 'mod', 'vpl', $vplinstance->id, $userid);
            if (! empty($gradinginfo->outcomes)) {
                $mform->addElement('header', 'hOutcomes', get_string('outcomes', 'grades'));
                $mform->setExpanded('hOutcomes');
                foreach ($gradinginfo->outcomes as $oid => $outcome) {
                    $mform->addElement(
                        'select',
                        'outcome_grade_' . $oid,
                        s($outcome->name),
                        [ get_string('nooutcome', 'grades') ] + make_grades_menu(- $outcome->scaleid)
                    );
                }
            }
        }

        $mform->addElement('header', 'hImport', get_string('import'));

        // Find last graded submission and last manually graded submission.
        $prevsubmanuallygraded = null;
        $prevsubgraded = null;
        $submissionslist = $this->vpl->user_submissions($userid);
        foreach ($submissionslist as $submission) {
            if ($submission->id == $this->submission->get_instance()->id) {
                continue;
            }
            if ($prevsubmanuallygraded === null && $submission->grader != 0) {
                $prevsubmanuallygraded = $submission;
            }
            if ($prevsubgraded === null && $submission->grade !== null) {
                $prevsubgraded = $submission;
            }
            if ($prevsubmanuallygraded !== null && $prevsubgraded !== null) {
                // End search if we have found both submissions.
                break;
            }
        }

        $thissubisgraded = $this->submission->get_instance()->grade !== null;

        $mform->setExpanded('hImport', $prevsubmanuallygraded !== null || ($prevsubgraded !== null && !$thissubisgraded));

        self::add_import_from_submission_button(
            $mform,
            $id,
            $userid,
            'importlastgradedsub',
            get_string('importgrade', VPL),
            get_string('importfromlastgradedsub', VPL),
            $prevsubgraded,
            $gradinginstance
        );
        self::add_import_from_submission_button(
            $mform,
            $id,
            $userid,
            'importlastmgradedsub',
            '',
            get_string('importfromlastmgradedsub', VPL),
            $prevsubmanuallygraded,
            $gradinginstance
        );

        $mform->addHelpButton('importlastgradedsub', 'importgrade', VPL);

        $PAGE->requires->js_call_amd('mod_vpl/gradeform', 'setup', [ $id ]);
    }

    /**
     *
     * @param moodle_url $url
     * @param string $str
     * @param bool $newtab
     * @param string $component
     * @return string
     */
    protected static function get_formgroup_button_link($url, $str, $newtab = false, $component = VPL) {
        $attributes = [
                'href' => $url->out(false),
                'title' => get_string($str . '_help', $component),
                'class' => 'fitem btn btn-secondary',
        ];
        if ($newtab) {
            $attributes['target'] = '_blank';
        }
        return html_writer::tag('a', get_string($str, $component), $attributes);
    }

    /**
     * Adds a button to import grade and comments from a previous submission.
     *
     * @param moodleform $mform The form to add the button to.
     * @param int $id The VPL activity ID.
     * @param int $userid The user ID to import from.
     * @param string $name The name of the button element.
     * @param string $title The title of the button group.
     * @param string $label The label for the button.
     * @param mod_vpl_submission|null $subinstance The submission instance to import from, or null if not applicable.
     */
    protected function add_import_from_submission_button(&$mform, $id, $userid, $name, $title, $label, $subinstance) {
        global $DB;
        $group = [];
        $attributes = [];

        if ($subinstance !== null) {
            $submission = new mod_vpl_submission($this->vpl, $subinstance);
            $canimport = $submission->is_graded();
        } else {
            $canimport = false;
        }

        if ($canimport) {
            $grade = $subinstance->grade;
            if (strlen($grade) > 0) {
                $grade = format_float($grade, 2, true, true);
            }

            $comments = $submission->get_grade_comments();

            $gradingmanager = get_grading_manager($this->vpl->get_context(), 'mod_vpl', 'submissions');
            $gradingmethod = $gradingmanager->get_active_method();
            $advgradinginstance = $submission->get_grading_instance();
            if ($gradingmethod !== null && $advgradinginstance) {
                $advgradinginstanceid = $advgradinginstance->get_id();
                $advgradingdata = array_values($DB->get_records(
                    'gradingform_' . $gradingmethod . '_fillings',
                    [ 'instanceid' => $advgradinginstanceid ]
                ));
            } else {
                $advgradingdata = [];
            }
            $attributes['data-role'] = 'importfromsub';
            $attributes['data-grade'] = $grade;
            $attributes['data-comments'] = $comments;
            $attributes['data-advgrading'] = json_encode($advgradingdata);

            $subhref = vpl_mod_href('forms/gradesubmission.php', 'id', $id, 'userid', $userid, 'submissionid', $subinstance->id);
            $gradingdetails = new stdClass();
            $gradingdetails->date = userdate($subinstance->dategraded);
            $gradingdetails->gradername = fullname(mod_vpl_submission::get_grader($subinstance->grader));
            $subinfo = '<a href="' . $subhref . '">' . get_string('gradedonby', VPL, $gradingdetails) . '</a>';
        } else {
            $attributes['disabled'] = 'disabled';
            $subinfo = '(' . get_string('nosuchsubmission', VPL) . ')';
        }
        $group[] =& $mform->createElement('button', null, $label, $attributes);
        $group[] =& $mform->createElement('html', $subinfo);
        $mform->addGroup($group, $name, $title);
    }
}
