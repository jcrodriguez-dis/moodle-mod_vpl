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
 * @version        $Id: outputfiles.php,v 1.0 2016-09-07 20:19:00 fero Exp $
 * @package mod_vpl
 * @copyright    2016 Frantisek Galcik
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author        Frantisek Galcik <frantisek.galcik@upjs.sk>
 */

require_once dirname(__FILE__) . '/../../../config.php';
require_once dirname(__FILE__) . '/../locallib.php';
require_once dirname(__FILE__) . '/../vpl.class.php';
require_once $CFG->libdir . '/formslib.php';

class mod_vpl_outputfiles_form extends moodleform {
    public $clear_selection = false;
    public $clear_newfile = false;

    protected $filenames;

    function __construct($page, $filenames) {
        $this->filenames = $filenames;
        parent::__construct($page);
    }

    protected function definition() {
        $mform = &$this->_form;
        $mform->addElement('hidden', 'id', required_param('id', PARAM_INT));
        $mform->setType('id', PARAM_INT);
        $mform->addElement('header', 'header_outputfiles', get_string('outputfiles', VPL));

        $mform->addElement('text', 'newfile', get_string('newoutputfile', VPL));
        $mform->setType('newfile', PARAM_NOTAGS);
        $mform->addHelpButton('newfile', 'newoutputfile', VPL);
        $mform->addElement('submit', 'addoutputfile', get_string('addoutputfile', VPL));

        if (count($this->filenames) > 0) {
            $num = 0;
            foreach ($this->filenames as $filename) {
                $mform->addElement('checkbox', 'outputfile' . $num, s($filename));
                $mform->setDefault('outputfile' . $num, false);
                $num++;
            }
            $mform->addElement('submit', 'removeoutputfiles', get_string('removeoutputfiles', VPL));
        }
    }

    public function validation($data, $files) {
        $errors = array();
        if (!empty($data['addoutputfile'])) {
            $filename = trim($data['newfile']);
            if (($filename == '') || !vpl_is_valid_path_name($filename)) {
                $errors['newfile'] = get_string('invalidfilename', VPL);
            }

            if (in_array($filename, $this->filenames)) {
                $errors['newfile'] = get_string('duplicatedfilename', VPL);
            }
        }

        return $errors;
    }

    function definition_after_data() {
        parent::definition_after_data();
        $mform = &$this->_form;

        if ((count($this->filenames) > 0) && ($this->clear_selection)) {
            $num = 0;
            foreach ($this->filenames as $filename) {
                $mform->getElement('outputfile' . $num)->setValue(false);
                $num++;
            }
        }

        if ($this->clear_newfile) {
            $mform->getElement('newfile')->setValue('');
        }
    }
}

require_login();

$id = required_param('id', PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('forms/outputfiles.php', array(
    'id' => $id
));
vpl_include_jsfile('hideshow.js');
$vpl->require_capability(VPL_MANAGE_CAPABILITY);

//Display page
$vpl->print_header(get_string('outputfiles', VPL));
$vpl->print_heading_with_help('outputfiles');

$filenames = $vpl->get_output_files();
$mform = new mod_vpl_outputfiles_form('outputfiles.php', $filenames);
if ($fromform = $mform->get_data()) {
    $list_changed = false;
    $clear_newfile = false;
    $clear_selection = false;
    if (!empty($fromform->removeoutputfiles)) {
        $nlist = count($filenames);
        $new_filenames = array();
        for ($i = 0; $i < $nlist; $i++) {
            $name = 'outputfile' . $i;
            if (empty($fromform->$name)) {
                $new_filenames[] = $filenames[$i];
            }
        }
        $filenames = $new_filenames;
        $list_changed = true;
        $clear_selection = true;
    }

    if (!empty($fromform->addoutputfile)) {
        array_unshift($filenames, trim($fromform->newfile));
        $list_changed = true;
        $clear_newfile = true;
        $clear_selection = true;
    }

    if ($list_changed) {
        $vpl->set_output_files($filenames);
        \mod_vpl\event\vpl_outputlist_updated::log($vpl);
        vpl_notice(get_string('outputlistupdated', VPL));
        $mform = new mod_vpl_outputfiles_form('outputfiles.php', $filenames);
        $mform->clear_newfile = $clear_newfile;
        $mform->clear_selection = $clear_selection;
    }
}

\mod_vpl\event\vpl_outputlist_viewed::log($vpl);
$mform->display();
$vpl->print_footer();