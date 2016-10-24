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
 * Download an output file
 *
 * @package mod_vpl
 * @copyright 2016 Frantisek Galcik
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Frantisek Galcik <frantisek.galcik@upjs.sk>
 */
require_once dirname(__FILE__) . '/../../../config.php';
global $CFG, $USER;
require_once dirname(__FILE__) . '/../locallib.php';
require_once dirname(__FILE__) . '/../vpl.class.php';
require_once dirname(__FILE__) . '/../vpl_submission.class.php';
try{
    require_login();
    $id = required_param('id',PARAM_INT);
    $file = required_param('file', PARAM_FILE);

    $vpl = new mod_vpl($id);
    $userid = optional_param('userid',FALSE,PARAM_INT);
    $submissionid =  optional_param('submissionid',FALSE,PARAM_INT);

    if(!$vpl->has_capability(VPL_GRADE_CAPABILITY)){
        $userid = FALSE;
        $submissionid = FALSE;
    }
    //Read record
    if($userid && $userid != $USER->id){
        //Grader
        $vpl->require_capability(VPL_GRADE_CAPABILITY);
        $grader =TRUE;
        if($submissionid){
            $subinstance = $DB->get_record('vpl_submissions',array('id' => $submissionid));
        }else{
            $subinstance = $vpl->last_user_submission($userid);
        }
    }
    else{
        //Download own submission
        $vpl->require_capability(VPL_VIEW_CAPABILITY);
        $userid = $USER->id;
        $grader = FALSE;
        if($submissionid && $vpl->has_capability(VPL_GRADE_CAPABILITY)){
            $subinstance = $DB->get_record('vpl_submissions',array('id' => $submissionid));
        }else{
            $subinstance = $vpl->last_user_submission($userid);
        }
        $vpl->password_check();
    }

    //Check consistence
    if(!$subinstance){
        throw new Exception(get_string('nosubmission',VPL));
    }
    if($subinstance->vpl != $vpl->get_instance()->id){
        throw new Exception(get_string('invalidcourseid'));
    }
    $submissionid = $subinstance->id;

    if($vpl->is_inconsistent_user($subinstance->userid,$userid)){
        throw new Exception('vpl submission user inconsistence');
    }
    if($vpl->get_instance()->id != $subinstance->vpl){
        throw new Exception('vpl submission vpl inconsistence');
    }
    $submission = new mod_vpl_submission($vpl,$subinstance);
    $fgm = $submission->get_output_files_fgm();
    $is_hidden = (substr(basename($file), 0, 1) == '.');
        if (!$vpl->has_capability(VPL_GRADE_CAPABILITY) && $is_hidden) {
        throw new Exception('file does not exists');
    }
    if (!$fgm->download_file($file)) {
        throw new Exception('file does not exists');
    }
}catch (Exception $e){
    $vpl->prepare_page('views/downloadoutputfile.php', array('id' => $id, 'file'=>$file));
    $vpl->print_header(get_string('download',VPL));
    echo $OUTPUT->box($e->getMessage());
    $vpl->print_footer();
}
