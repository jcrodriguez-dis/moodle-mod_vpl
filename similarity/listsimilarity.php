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
 * List most similar submission files
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

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

require_login();

$id = required_param('id', PARAM_INT);
$time_limit = 600; // 10 minutes
$vpl = new mod_vpl($id);
$vpl->prepare_page('similarity/listsimilarity.php', array('id' => $id));

$vpl->require_capability(VPL_SIMILARITY_CAPABILITY);
\mod_vpl\event\vpl_similarity_report_viewed::log($vpl);
//Print header
$PAGE->requires->css(new moodle_url('/mod/vpl/css/similarity.css'));
$vpl->print_header(get_string('listsimilarity',VPL));
$vpl->print_view_tabs(basename(__FILE__));
$form = new vpl_similarity_form('listsimilarity.php',$vpl);
if(!($fromform = $form->get_data())){
    print_error('readingdata',VPL);
}
//Get filelist names to scan
$filesselected=array();
$filelist = $vpl->get_required_fgm()->getFileList();
$num=0;
foreach($filelist as $filename){
    if(isset($fromform->{'file'.$num})){
        $filesselected[$filename]=true;
    }
    $num++;
}

$usernumber=0;
$nwm=0;
$simil = array();
//Preprocess current VPL instance
@set_time_limit($time_limit);
$activity_load_box = new vpl_progress_bar(s($vpl->get_printable_name()));
//debugging("Adding activity files", DEBUG_DEVELOPER);
vpl_similarity::scan_activity($simil,$vpl,$filesselected,$activity_load_box);
//debugging("Files to check ".count($simil), DEBUG_DEVELOPER);
$il = count($simil);
//Preprocess other VPL instance
if(isset($fromform->scanactivity) && $fromform->scanactivity>0){
    @set_time_limit($time_limit);
    $othervpl=new mod_vpl($fromform->scanactivity);
    $other_activity_load_box = new vpl_progress_bar(s($othervpl->get_printable_name()));
    //debugging("Adding other activity files", DEBUG_DEVELOPER);
    vpl_similarity::scan_activity($simil,$othervpl,$filesselected,$other_activity_load_box);
    //debugging("Files to check ".count($simil), DEBUG_DEVELOPER);
}
//Preprocess files in a ZIP file
$name = $form->get_new_filename('scanzipfile0');
$data = $form->get_file_content('scanzipfile0');
if($data !== false && $name !== false ){
    @set_time_limit($time_limit);
    $zip_load_box0 = new vpl_progress_bar(s($name));
    //debugging("Adding files in zip file", DEBUG_DEVELOPER);
    vpl_similarity::scan_zip($simil,$name,$data,$vpl,$filesselected,$zip_load_box0);
    //debugging("Files to check ".count($simil), DEBUG_DEVELOPER);
}
/* remove when 1.9 => 2.x
//Preprocess files in a ZIP file
$name = $form->get_new_filename('scanzipfile1');
$data = $form->get_file_content('scanzipfile1');
if($data !== false && $name !== false ){
    @set_time_limit($time_limit);
    $zip_load_box1 = new vpl_progress_bar(s($fromform->scanzipfile1));
//    debugging("Adding files in zip file", DEBUG_DEVELOPER);
    vpl_similarity::scan_zip($simil,$fromform->scanzipfile1,$vpl,$filesselected,$zip_load_box1);
//    debugging("Files to check ".count($simil), DEBUG_DEVELOPER);
}
//Preprocess files in a directory
if(isset($fromform->scandirectory) && $fromform->scandirectory>''){
    @set_time_limit($time_limit);
    $dir_load_box = new vpl_progress_bar(s($fromform->scandirectory));
    $basedir = $CFG->dataroot.'/'.$vpl->get_course()->id;
    debugging("Adding files in directory", DEBUG_DEVELOPER);
    vpl_similarity::scan_directory($simil,$fromform->scandirectory,$vpl,$filesselected,$dir_load_box);
    debugging("Files to check ".count($simil), DEBUG_DEVELOPER);
}
*/
//Search similarity in other files after current VPL instance
if(isset($fromform->searchotherfiles)){
    $il = count($simil);
}
@set_time_limit($time_limit);
$search_progression = new vpl_progress_bar(get_string('similarity',VPL));
$selected = vpl_similarity::get_selected($simil,$fromform->maxoutput,$il,$search_progression);
//$extinfo=$USER->id==2;
$extinfo=false;

if(count($selected)){
    $firstname = get_string('firstname');
    $lastname  = get_string('lastname');
    if ($CFG->fullnamedisplay == 'lastname firstname') {
        $name = $lastname.' / '.$firstname;
    } else {
        $name = $firstname.' / '.$lastname;
    }
    $with    = get_string('similarto',VPL);
    $table = new html_table();
    $table->head  = array ('#',$name, '',$with,get_string('numcluster',VPL,'#'));
    $table->align = array ('right','left', 'center', 'left','right');
    $table->size = array ('','60','60');
    $clusters = new vpl_clusters($selected);
    foreach($selected as $case){
        //Cluster assigning
        $clusters->process($case);
    }
    $clusters->assign_number();
    $usernumber=0;
    foreach($selected as $case){
        $table->data[] = array (++$usernumber,
                            $case->first->show_info($extinfo),
                            $case->get_link(),
                            $case->second->show_info($extinfo),
                            $case->get_cluster());
    }
    echo html_writer::table($table);
    $clusters->print_clusters();
}else{
    echo $OUTPUT->box(get_string('noresults'));
}
//Hide progress bars
$activity_load_box->hide();
if(isset($other_activity_load_box)){
    $other_activity_load_box->hide();
}
if(isset($zip_load_box0)){
    $zip_load_box0->hide();
}
$search_progression->hide();
$vpl->print_footer();
