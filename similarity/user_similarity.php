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
 * @package mod_vpl. List most similar submission files
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
die();
require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../vpl_submission.class.php';
require_once dirname(__FILE__).'/similarity_factory.class.php';
require_once dirname(__FILE__).'/similarity_base.class.php';
require_once dirname(__FILE__).'/similarity_form.class.php';
require_once dirname(__FILE__).'/clusters.class.php';
require_once dirname(__FILE__).'/../views/status_box.class.php';
ini_set('memory_limit','256M');

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$time_limit = 600; // 10 minutes
$vpl = new mod_vpl($id);
$vpl->prepare_page('similarity/user_similarity.php', array('id' => $id,'userid' => $userid));

$vpl->require_capability(VPL_SIMILARITY_CAPABILITY);
\mod_vpl\event\vpl_user_similarity_report_viewed::log(array(
        'objectid' => $vpl->get_instance()->id,
        'context' => context_module::instance($id),
        'relateduserid' => $userid
));
//Print header
$PAGE->requires->css(new moodle_url('/mod/vpl/css/similarity.css'));
$vpl->print_header(get_string('listsimilarity',VPL));
$subinstance=$vpl->last_user_submission($userid);
if($subinstance === false){ //No user submission
    $vpl->print_footer();
    die;
}
$submission = new mod_vpl_submission($vpl,$subinstance);
//Get required filenames
$filelist = $vpl->get_required_fgm()->getFileList();
foreach($filelist as $filename){
    $filesselected[$filename]=true;
}
//prepare table head
$firstname = get_string('firstname');
$lastname  = get_string('lastname');
if ($CFG->fullnamedisplay == 'lastname firstname') {
    $name = $lastname.' / '.$firstname;
} else {
    $name = $firstname.' / '.$lastname;
}
$with    = get_string('similarto',VPL);
$head  = array ('#',$name, '',$with);
$align = array ('right','left', 'center', 'left');
$size = array ('','60','60','');

//Preprocess current VPL instance
$subfgm = $submission->get_submitted_fgm();
foreach($subfgm->getFileList() as $filename){
    if(isset($filesselected[$filename])){ //Check if this is a required filename
        echo '<h3>'.s($filename).'</h3>';
        @set_time_limit($time_limit);
        $simil = array();
        $fselected = array();
        $activity_load_box = new vpl_progress_bar(s($vpl->get_printable_name()).":".s($filename));
        $fselected[$filename]=true;
        vpl_similarity::scan_activity($simil,$vpl,$fselected,$activity_load_box);
        //TODO Find and put first user file
        $l=count($simil);
        for($i=0; $i<$l;$i++){
            if($simil[$i]->from->userid == $userid){
                $obj= $simil[0];
                $simil[0] = $simil[$i];
                $simil[$i] = $obj;
                break;
            }
        }
        $search_progression = new vpl_progress_bar(get_string('similarity',VPL));
        $selected = vpl_similarity::get_selected($simil,4,1,$search_progression);
        $table = new html_table();
        $table->head  = $head;
        $table->align = $align;
        $table->size = $size;
        $usernumber=0;
        foreach($selected as $case){
            $table->data[] = array (++$usernumber,
                                $case->first->show_info(),
                                $case->get_link(),
                                $case->second->show_info(),
                                $case->get_cluster());
        }
        echo html_writer::table($table);
        $activity_load_box->hide();
        $search_progression->hide();
    }
}
@set_time_limit($time_limit);
$simil = array();
$activity_load_box = new vpl_progress_bar(s($vpl->get_printable_name()).":".s($filename));
vpl_similarity::scan_activity($simil,$vpl,$filesselected,$activity_load_box,false);
//TODO Find and put first user file
$l=count($simil);
$others=0;
for($i=0; $i<$l;$i++){
    if($simil[$i]->from->userid == $userid){
        $obj= $simil[$others];
        $simil[$others] = $simil[$i];
        $simil[$i] = $obj;
        $others++;
    }
}
if($others>0){
    $search_progression = new vpl_progress_bar(get_string('similarity',VPL));
    $selected = vpl_similarity::get_selected($simil,2*$others,$others,$search_progression);
    $table = new html_table();
    $table->head  = $head;
    $table->align = $align;
    $table->size = $size;
    $usernumber=0;
    foreach($selected as $case){
        $table->data[] = array (++$usernumber,
                            $case->first->show_info(),
                            $case->get_link(),
                            $case->second->show_info());
    }
    echo html_writer::table($table);
    $search_progression->hide();
}
$activity_load_box->hide();

$vpl->print_footer();
