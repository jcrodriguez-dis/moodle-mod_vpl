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
 * Edit  file
 *
 * @package mod_vpl
 * @copyright    2012 Juan Carlos Rodríguez-del-Pino
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author        Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__). '/../../../config.php');
require_once(dirname(__FILE__). '/../locallib.php');
require_once(dirname(__FILE__). '/../vpl.class.php');
//require_once(dirname( __FILE__ ). '/edit.class.php');
require_once(dirname(__FILE__). '/../editor/editor_utility.php');

vpl_editor_util::generate_requires();

require_login();
$id = required_param( 'id', PARAM_INT );
$type = required_param( 'type', PARAM_ALPHANUMEXT );
    
$vpl = new mod_vpl( $id );
$instance = $vpl->get_instance();
$vpl->prepare_page( 'forms/files.php', array ( 'id' => $id ,'type' =>$type ) );

$vpl->require_capability( VPL_MANAGE_CAPABILITY );
$vpl->print_header( get_string( $type.'files', VPL ) );
$vpl->print_heading_with_help( $type.'files' );

$options = Array ();
$options ['restrictededitor'] = false;
$options ['save'] = true;
$options ['run'] = ($type=='execution');
$options ['debug'] = ($type=='execution');
$options ['evaluate'] = ($type=='execution');
$options ['ajaxurl'] = "files.json.php?id={$id}&type={$type}&action=";
$options ['download'] = "../views/downloadfiles.php?id={$id}&type={$type}";
$options ['resetfiles'] = ($type=='corrected');
$options ['correctedfiles'] = false;
$options ['minfiles'] = 0;
$options ['maxfiles'] = ($type=='execution')?1000:$instance->maxfiles;
$options ['saved'] = true;





// Get files.
if ($type=='testcases'){
    $options ['minfiles'] = 1;
    $options ['maxfiles'] = 1;
    $fgm = $vpl->get_fgm('execution');
    $filename='vpl_evaluate.cases';
}else{
    $fgm = $vpl->get_fgm($type);
}
if ($type=='execution'){
    $options ['minfiles'] = $fgp->get_numstaticfiles();
}

$files = Array();
if (isset($filename)){
    $files[$filename]=$fgm->getFileData($filename);
}else{
    $filelist =$fgm->getFileList();
    $nf = count($filelist);
    for( $i = 0; $i < $nf; $i++){
        $filename=$filelist[$i];
        $filedata=$fgm->getFileData($filelist[$i]);
        $files[$filename]=$filedata;
    }
}
session_write_close();

echo $OUTPUT->box_start();
vpl_editor_util::print_tag( $options,$files );
echo $OUTPUT->box_end();
$vpl->print_footer_simple();
