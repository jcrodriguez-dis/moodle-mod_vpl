<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once(dirname(__FILE__).'/diff.class.php');

require_course_login( $COURSE );

require_login();
$id = $_POST['id']; // Course Module ID.
$vpl = new mod_vpl( $id );

$explode_code_in_editor = explode("\n", $_POST['val']);

$fr = $vpl->get_fgm('required');

        $filenames = $fr->getFileList();
        foreach ($filenames as $name) {
            
            if (file_exists( $fr->get_dir() . $fr::encodeFileName( $name ) ) && $name == $_POST['name']) {
                    $printer = vpl_sh_factory::get_sh( $name );
                    $data = $fr->getFileData( $name );
                    $explode_requested_file = explode("\n", $data);
            } 
        }
        
        $diff = vpl_diff::calculatediff( $explode_requested_file , $explode_code_in_editor );
        header('Content-type: application/json');
        echo json_encode($diff);
    