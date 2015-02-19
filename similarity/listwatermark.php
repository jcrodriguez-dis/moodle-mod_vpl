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
 * List water marks in vpl submission
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
require_once dirname(__FILE__).'/watermark.php';

require_login();

$id = required_param('id', PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('similarity/listwatermark.php', array('id' => $id));

$course = $vpl->get_course();
$vpl->require_capability(VPL_SIMILARITY_CAPABILITY);
\mod_vpl\event\vpl_watermark_report_viewed::log($vpl);
//Print header
$vpl->print_header(get_string('listwatermarks',VPL));
$vpl->print_view_tabs(basename(__FILE__));
$list = $vpl->get_students();
$firstname = get_string('firstname');
$lastname  = get_string('lastname');
if ($CFG->fullnamedisplay == 'lastname firstname') { // for better view (dlnsk)
    $name = $lastname.' / '.$firstname;
} else {
    $name = $firstname.' / '.$lastname;
}

//Load strings
$origin    = get_string('origin',VPL);
$table = new html_table();
$table->head  = array ('#',$name, $origin);
$table->align = array ('right','left', 'left');
$table->size = array ('','60','60');
$submissions = $vpl->all_last_user_submission();
$usernumber=0;
$nwm=0;
foreach ($list as $userinfo) {
    if(isset($submissions[$userinfo->id])){
        $origin = '';
        $subinstance = $submissions[$userinfo->id];
        $submission = new mod_vpl_submission($vpl,$subinstance);
        $subf = $submission->get_submitted_fgm();
        $filelist = $subf->getFileList();
        foreach($filelist as $filename){
            $data = $subf->getFileData($filename);
            $wm = vpl_watermark::getwm($data);
            if($wm){
                if($wm != $userinfo->id){
                    $user_origin = $DB->get_record('user',array('id' => $wm));
                    if($user_origin){
                        $origin .='<a href="'.vpl_mod_href('/forms/submissionview.php','id',$id,'userid',$wm).'">';
                        $origin .=s($filename).' ';
                        $origin .= '</a>';
                        $origin .=' <a href="'.vpl_abs_href('/user/view.php','id',$wm,'course',$course->id).'">';
                        $origin .= $vpl->fullname($user_origin).'</a>';
                    }
                }else{
                    $nwm++;
                }
            }
        }
        if($origin==''){
            continue;
        }
        $usernumber++;
        $table->data[] = array ($usernumber,$vpl->user_fullname_picture($userinfo),
            $origin);
    }
}
if($usernumber>0){
    echo html_writer::table($table);
}
echo $OUTPUT->box(get_string('nowatermark',VPL,$nwm));
$vpl->print_footer();
