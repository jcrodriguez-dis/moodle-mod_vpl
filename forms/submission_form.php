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
 * Submission form definition
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../locallib.php');

/**
 * Class to define the submission form for VPL
 *
 * This form allows users to submit their work, including file uploads and comments.
 */
class mod_vpl_submission_form extends moodleform {
    /**
     * @var mod_vpl $vpl The VPL instance for which the submission is being made.
     */
    protected $vpl;

    /**
     * @var int $userid The user ID for whom the submission is being made.
     */
    protected $userid;

    /**
     * Returns the internal form object.
     *
     * @return moodleform The internal form object.
     */
    protected function getinternalform() {
        return $this->_form;
    }

    /**
     * Constructor
     *
     * @param moodle_page $page The page where the form will be displayed.
     * @param mod_vpl $vpl The VPL instance.
     * @param int $userid The user ID for whom the submission is being made.
     */
    public function __construct($page, $vpl, $userid) {
        $this->vpl = $vpl;
        $this->userid = $userid;
        parent::__construct($page);
    }

    /**
     * Defines the form elements
     */
    protected function definition() {
        global $CFG, $OUTPUT, $PAGE;
        $mform = & $this->_form;
        // Identification info.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid', 0);
        $mform->setType('userid', PARAM_INT);
        // Comments.
        $mform->addElement('textarea', 'comments', get_string('comments', VPL), [
                'cols' => '40',
                'rows' => 2,
        ]);
        $mform->setType('comments', PARAM_TEXT);

        $submission = $this->vpl->last_user_submission($this->userid);
        $firstsub = ($submission === false);
        $instance = $this->vpl->get_instance();
        $reqfiles = $this->vpl->get_required_files();

        $mform->addElement(
            'select',
            'submitmethod',
            get_string('submitmethod', VPL),
            [ 'archive' => get_string('archive', VPL), 'files' => get_string('files') ]
        );
        $mform->setDefault('submitmethod', count($reqfiles) == 1 ? 'files' : 'archive');

        $mform->addElement('header', 'headersubmitarchive', get_string('submitarchive', VPL));

        $filepickertitle = get_string('submitarchive', VPL);
        if (!$firstsub) {
            $mform->addElement(
                'radio',
                'archiveaction',
                $filepickertitle,
                get_string('archivereplacedelete', VPL),
                'replacedelete'
            );
            $mform->addElement(
                'radio',
                'archiveaction',
                '',
                get_string('archivereplace', VPL),
                'replace'
            );
            $mform->disabledIf('archiveaction', 'submitmethod', 'neq', 'archive');
            $filepickertitle = null;
        }
        $mform->addElement('filepicker', 'archive', $filepickertitle, null, [ 'accepted_types' => '.zip' ]);
        $mform->disabledIf('archive', 'submitmethod', 'neq', 'archive');

        $mform->addElement('header', 'headersubmitfiles', get_string('submitfiles', VPL));

        // Files upload.
        $i = 0;
        $requiredicon = $OUTPUT->pix_icon('requestedfiles', get_string('required'), 'mod_vpl', [ 'class' => 'text-info' ]);
        foreach ($reqfiles as $reqfile) {
            $field = 'file' . $i;
            $filepickertitle = $requiredicon . $reqfile;
            if (!$firstsub) {
                $mform->addElement(
                    'radio',
                    $field . 'action',
                    $filepickertitle,
                    get_string('keepcurrentfile', VPL),
                    'keep'
                );
                $mform->addElement(
                    'radio',
                    $field . 'action',
                    '',
                    get_string('replacefile', VPL),
                    'replace'
                );
                $mform->disabledIf($field . 'action', 'submitmethod', 'neq', 'files');
                $mform->addElement('hidden', $field . 'name', $reqfile);
                $mform->setType($field . 'name', PARAM_RAW);
                $filepickertitle = null;
            }
            $mform->addElement('filepicker', $field, $filepickertitle);
            $mform->disabledIf($field, 'submitmethod', 'neq', 'files');
            if (!$firstsub) {
                $mform->disabledIf($field, $field . 'action', 'neq', 'replace');
            }
            $i++;
        }
        if (!$firstsub) {
            $subfiles = (new mod_vpl_submission($this->vpl, $submission))->get_submitted_fgm()->getFileList();
            foreach ($subfiles as $subfile) {
                if (!in_array($subfile, $reqfiles)) {
                    $field = 'file' . $i;
                    $mform->addElement('radio', $field . 'action', $subfile, get_string('keepcurrentfile', VPL), 'keep');
                    $mform->addElement('radio', $field . 'action', '', get_string('deletefile', VPL), 'delete');
                    $mform->addElement('radio', $field . 'action', '', get_string('replacefile', VPL), 'replace');
                    $mform->disabledIf($field . 'action', 'submitmethod', 'neq', 'files');
                    $mform->addElement('hidden', $field . 'name', $subfile);
                    $mform->setType($field . 'name', PARAM_PATH);
                    $mform->addElement('filepicker', $field);
                    $mform->disabledIf($field, 'submitmethod', 'neq', 'files');
                    $mform->disabledIf($field, $field . 'action', 'neq', 'replace');
                    $i++;
                }
            }
        }

        while ($i < $instance->maxfiles) {
            $field = 'file' . $i;
            $mform->addElement('filepicker', $field, get_string('anyfile', VPL));
            $mform->disabledIf($field, 'submitmethod', 'neq', 'files');
            $mform->addGroup([
                    $mform->createElement('advcheckbox', $field . 'rename', get_string('renameuploadedfile', VPL)),
                    $mform->createElement(
                        'text',
                        $field . 'name',
                        get_string('new_file_name', VPL),
                        [ 'size' => 32, 'placeholder' => get_string('new_file_name', VPL) ]
                    ),
            ]);
            $mform->setType($field . 'name', PARAM_PATH);
            $mform->setDefault($field . 'rename', 0);
            $mform->disabledIf($field . 'name', $field . 'rename');
            $mform->disabledIf($field . 'name', $field . 'name', 'neq', 'files');
            $i++;
        }
        $this->add_action_buttons(true, get_string('submit'));

        $PAGE->requires->js_call_amd('mod_vpl/submissionform', 'setup');
    }

    /**
     * Set the data for the form.
     *
     * @param stdClass $data The data to set in the form.
     */
    public function set_data($data) {
        for ($i = 0; $i < $this->vpl->get_instance()->maxfiles; $i++) {
            $data->{'file' . $i . 'action'} = 'keep';
            $data->{'archiveaction'} = 'replacedelete';
        }
        parent::set_data($data);
    }
}
