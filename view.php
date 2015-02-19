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
 * Show a VPL instance
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../config.php';
require_once dirname(__FILE__).'/locallib.php';
require_once dirname(__FILE__).'/vpl.class.php';
global $CFG;
require_login();
$id = optional_param('id', null    , PARAM_INT); // Course Module ID, or
//$a  = optional_param('a', null, PARAM_INT);  // vpl ID
$vpl = new mod_vpl($id);
$vpl->prepare_page('view.php', array('id' => $id));
$vpl->require_capability(VPL_VIEW_CAPABILITY);
$id = $vpl->get_course_module()->id;
$log_url=vpl_rel_url('view.php','id',$id);
if(!$vpl->is_visible()){
    notice(get_string('notavailable'));
}
if(!$vpl->has_capability(VPL_MANAGE_CAPABILITY) &&
   !$vpl->has_capability(VPL_GRADE_CAPABILITY)){
       $vpl->network_check();
    $vpl->password_check();
    $userid=$USER->id;
}else{
    $userid=optional_param('userid', $USER->id, PARAM_INT);
}

\mod_vpl\event\vpl_description_viewed::log($vpl);
// Print the page header
$PAGE->requires->css(new moodle_url('/mod/vpl/css/sh.css'));
$vpl->print_header(get_string('description',VPL));

/// Print the main part of the page
$vpl->print_view_tabs(basename(__FILE__));
$vpl->print_name();
echo $OUTPUT->box_start();
$vpl->print_submission_period();
$vpl->print_submission_restriction();
$vpl->print_variation($userid);
echo $OUTPUT->box_end();
$vpl->print_fulldescription();
$fr = $vpl->get_required_fgm();
if($fr->is_populated()){
    echo '<h2>'.get_string('requestedfiles',VPL)."</h2>\n";
    $fr->print_files(false);
}
if($vpl->has_capability(VPL_GRADE_CAPABILITY)){
    $fe = $vpl->get_execution_fgm();
    if($fe->is_populated()){
        echo '<h2>'.get_string('executionfiles',VPL)."</h2>\n";
        $fe->print_files(false);
    }
}
/// Finish the page
if(vpl_get_webservice_available()){
    echo "<a href='views/show_webservice.php?id=$id'>";
    echo get_string('webservice','core_webservice').'</a><br>';
}
$vpl->print_footer();

