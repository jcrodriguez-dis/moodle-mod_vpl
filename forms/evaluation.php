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
 * Atomatic evaluation from link
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../vpl_submission_CE.class.php';
require_once dirname(__FILE__).'/../editor/editor_utility.php';
require_login();
vpl_editor_util::generate_requires_evaluation();

$id = required_param('id',PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('forms/evaluation.php', array('id' => $id));
$userid = optional_param('userid',FALSE,PARAM_INT);
if((!$userid || $userid == $USER->id)
    && $vpl->get_instance()->evaluate){//Evaluate own submission
    $userid = $USER->id;
    $vpl->require_capability(VPL_SUBMIT_CAPABILITY);
}else { //Evaluate other user submission
    $vpl->prepare_page('forms/evaluation.php', array('id' => $id, 'userid' => $userid));
    $vpl->require_capability(VPL_GRADE_CAPABILITY);
}
if($USER->id == $userid){
    $vpl->network_check();
    $vpl->password_check();
}
//Display page
$vpl->print_header(get_string('evaluation',VPL));
flush();
$course = $vpl->get_course();
$instance = $vpl->get_instance();
echo '<h2>'.s(get_string('evaluating',VPL)).'</h2>';
$userinfo=$DB->get_record('user',array('id' => $userid));
$text = ' '.$vpl->user_picture($userinfo);
$text .= ' '.fullname($userinfo);
echo $OUTPUT->box($text);
$ajaxurl="edit.json.php?id={$id}&userid={$userid}&action=";
if(optional_param('grading',0,PARAM_INT)){
    $inpopup = optional_param('inpopup',0,PARAM_INT);
    $nexturl="../forms/gradesubmission.php?id={$id}&userid={$userid}&inpopup={$inpopup}";
}else{
    $nexturl="../forms/submissionview.php?id={$id}&userid={$userid}";
}
vpl_editor_util::generateEvaluateScript($ajaxurl,$nexturl);
$vpl->print_footer();

