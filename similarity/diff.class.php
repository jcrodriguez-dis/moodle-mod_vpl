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
 * Class to show two files diff
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
require_once dirname(__FILE__).'/../views/sh_base.class.php';
require_once dirname(__FILE__).'/similarity_factory.class.php';
require_once dirname(__FILE__).'/similarity_base.class.php';
class vpl_diff{
    /**
     * Remove chars and digits
     * @param $line string to process
     * @return string without chars and digits
     */
    static function removeAlphaNum($line){
        $ret='';
        $l= strlen($line);
        //Parse line to remove alphanum chars
        for($i=0; $i<$l; $i++){
            $c=$line[$i];
            if(!ctype_alnum($c) && $c != ' '){
                $ret.=$c;
            }
        }
        return $ret;
    }

    /**
     * Calculate the similarity of two lines
     * @param $line1
     * @param $line2
     * @return int (3 => trimmed equal, 2 =>removeAlphaNum , 1 => start of line , 0 => not equal)
     */
    static function diffLine($line1,$line2){
        //TODO Refactor.
        //This is a bad solution that must be rebuild to consider diferent languages
        //Compare trimed text
        $line1=trim($line1);
        $line2=trim($line2);
        if($line1==$line2){
            if(strlen($line1)>0){
                return 3;
            }else{
                return 1;
            }
        }
        //Compare filtered text (removing alphanum)
        $rAN1=self::removeAlphaNum($line1);
        $limit = strlen($rAN1);
        if($limit>0){
            if($limit>3){
                $limite = 3;
            }
            if(strncmp($rAN1,self::removeAlphaNum($line2),$limit) == 0){
                return 2;
            }
        }
        //Compare start of line
        $l=4;
        if($l>strlen($line1)){
            $l=strlen($line1);
        }
        if($l>strlen($line2)){
            $l=strlen($line2);
        }
        for($i=0; $i<$l; ++$i){
            if($line1[$i] !=$line2[$i]){
                break;
            }
        }
        return $i>0 ? 1:0;
    }

    static function newLineInfo($type,$ln1,$ln2=0){
        $ret = new StdClass();
        $ret->type = $type;
        $ret->ln1 = $ln1;
        $ret->ln2 = $ln2;
        return $ret;
    }

    /**
     * Initialize used matrix
     * @param $matrix matrix to initialize
     * @param $prev matrix to initialize
     * @param $nl1 number of rows
     * @param $nl2 number of columns
     * @return void
     */
    static function initAuxiliarMatrices(&$matrix,&$prev,$nl1,$nl2){
        // matrix[0..nl1+1][0..nl2+1]=0
        $row = array_pad(array(),$nl2+1,0);
        $matrix = array_pad(array(),$nl1+1,$row);
        // prev[0..nl1+1][0..nl2+1]=0
        $prev = $matrix;

        //update first column
        for($i=0; $i<=$nl1;$i++){
            $matriz[$i][0]=0;
            $prev[$i][0]=-1;
        }
        //update first row
        for($j=1; $j<=$nl2;$j++){
            $matriz[0][$j]=0;
            $prev[0][$j]=1;
        }
    }

    /**
     * Calculate diff for two array of lines
     * @param $lines1 array of string
     * @param $lines2 array of string
     * @return array of objects with info to show the two array of lines
     */
    static function calculateDiff($lines1,$lines2){
        $ret = array();
        $nl1=count($lines1);
        $nl2=count($lines2);
        if($nl1==0 && $nl2==0){
            return false;
        }
        if($nl1==0){ //There is no first file
            foreach($lines2 as $pos => $line){
                $ret[] = self::newLineInfo('>',0,$pos+1);
            }
            return $ret;
        }
        if($nl2==0){ //There is no second file
            foreach($lines1 as $pos => $line){
                $ret[] = self::newLineInfo('<',$pos+1);
            }
            return $ret;
        }
        self::initAuxiliarMatrices($matrix,$prev,$nl1,$nl2);

        //Matrix processing
        for($i=1; $i <= $nl1;$i++){
            $line=$lines1[$i-1];
            for($j=1; $j<=$nl2;$j++){
                if($matrix[$i][$j-1]>$matrix[$i-1][$j]) {
                    $max=$matrix[$i][$j-1];
                    $best = 1;
                }else{
                    $max=$matrix[$i-1][$j];
                    $best = -1;
                }
                $prize=self::diffLine($line,$lines2[$j-1]);
                if($matrix[$i-1][$j-1]+$prize>=$max){
                    $max=$matrix[$i-1][$j-1]+$prize;
                    $best = 0;
                }
                $matrix[$i][$j]=$max;
                $prev[$i][$j]=$best;
            }
        }

        //Calculate show info
        $limit=$nl1+$nl2;
        $pairs = array();
        $pi=$nl1;
        $pj=$nl2;
        while((!($pi == 0 && $pj == 0)) && $limit>0){
            $pair = new stdClass();
            $pair->i = $pi;
            $pair->j = $pj;
            $pairs[]=$pair;
            $p = $prev[$pi][$pj];
            if($p == 0){
                $pi--;
                $pj--;
            }elseif($p == -1){
                $pi--;
            }else{
                $pj--;
            }
            $limit--;
        }

        krsort($pairs);
        $prevpair = new stdClass();
        $prevpair->i=0;
        $prevpair->j=0;
        foreach($pairs as $pair){
            if($pair->i == $prevpair->i+1 && $pair->j == $prevpair->j+1){ //Regular advance
                if($lines1[$pair->i-1] == $lines2[$pair->j-1]){ //Equals
                    $ret[]=self::newLineInfo('=',$pair->i,$pair->j);
                }else{
                    $ret[]=self::newLineInfo('#',$pair->i,$pair->j);
                }
            }elseif($pair->i == $prevpair->i+1){ //Removed next line
                $ret[]=self::newLineInfo('<',$pair->i);
            }elseif($pair->j == $prevpair->j+1){ //Added one line
                $ret[]=self::newLineInfo('>',0,$pair->j);
            }else{
                debugging("Internal error ".print_r($pair,true)." ".print_r($prevpair,true));
            }
            $prevpair=$pair;
        }
        return $ret;
    }


    static function show($filename1, $data1, $HTMLheader1, $filename2, $data2, $HTMLheader2) {
        //Get file lines
        $nl = vpl_detect_newline($data1);
        $lines1 = explode($nl,$data1);
        $nl = vpl_detect_newline($data2);
        $lines2 = explode($nl,$data2);
        //Get dif as an array of info
        $diff = self::calculateDiff($lines1,$lines2);
        if($diff === false){
            return;
        }
        $separator= array('<'=>' <<< ', '>'=>' >>> ','='=>' === ','#'=>' ### ');
        $emptyline="&nbsp;\n";
        $data1='';
        $data2='';
        $datal1='';
        $datal2='';
        $diffl='';
        $lines1[-1]='';
        $lines2[-1]='';
        foreach($diff as $line){
            $diffl.= $separator[$line->type]."\n";
            if($line->ln1){
                $datal1.=$line->ln1." \n";
                $data1.=$lines1[$line->ln1-1]."\n";
            }else{
                $data1.=" \n";
                $datal1.=$emptyline;
            }
            if($line->ln2){
                $datal2.=$line->ln2." \n";
                $data2.=$lines2[$line->ln2-1]."\n";
            }else{
                $data2.=" \n";
                $datal2.=$emptyline;
            }
        }
        echo '<div style="width: 100%;min-width: 950px; overflow: auto">';
        //Header
        echo '<div style="float:left; width: 445px">';
        echo $HTMLheader1;
        echo '</div>';
        echo '<div style="float:left; width: 445px">';
        echo $HTMLheader2;
        echo '</div>';
        echo '<div style="clear:both;"></div>';
        //Files
        echo '<div style="float:left; text-align: right">';
        echo '<pre class="'.vpl_sh_base::c_general.'">';
        echo $datal1;
        echo '</pre>';
        echo '</div>';
        echo '<div style="float:left; width: 390px; overflow:auto">';
        $shower= vpl_sh_factory::get_sh($filename1);
        $shower->print_file($filename1,$data1,false);
        echo '</div>';
        echo '<div style="float:left">';
        echo '<pre class="'.vpl_sh_base::c_general.'">';
        echo $diffl;
        echo '</pre>';
        echo '</div>';
        echo '<div style="float:left; text-align: right;">';
        echo '<pre class="'.vpl_sh_base::c_general.'">';
        echo $datal2;
        echo '</pre>';
        echo '</div>';
        echo '<div style="float:left; width: 390px; overflow:auto">';
        $shower= vpl_sh_factory::get_sh($filename2);
        $shower->print_file($filename2,$data2,false);
        echo '</div>';
        echo '</div>';
        echo '<div style="clear:both;"></div>';

    }

    static function vpl_get_similfile($f,$vpl,&$HTMLheader,&$filename,&$data){
        global $DB;
        $HTMLheader='';
        $filename='';
        $data='';
        $type = required_param('type'.$f, PARAM_INT);
        if($type==1){
            $subid = required_param('subid'.$f, PARAM_INT);
            $filename = required_param('filename'.$f, PARAM_TEXT);
            $subinstance = $DB->get_record('vpl_submissions',array('id' => $subid));
            if($subinstance !== false){
                $vpl = new mod_vpl(false,$subinstance->vpl);
                $vpl->require_capability(VPL_SIMILARITY_CAPABILITY);
                $submission = new mod_vpl_submission($vpl,$subinstance);
                $user = $DB->get_record('user', array('id' => $subinstance->userid));
                if($user){
                    $HTMLheader .='<a href="'.vpl_mod_href('/forms/submissionview.php',
                                'id',$vpl->get_course_module()->id,'userid',$subinstance->userid).'">';
                }
                $HTMLheader .=s($filename).' ';
                if($user){
                    $HTMLheader .= '</a>';
                    $HTMLheader .= $vpl->user_fullname_picture($user);
                }
                $fg = $submission->get_submitted_fgm();
                $data = $fg->getFileData($filename);
                \mod_vpl\event\vpl_diff_viewed::log($submission);
            }
        }elseif($type == 2){
            //FIXME adapt to moodle 2.x
            /*
            global $CFG;
            $dirname = required_param('dirname'.$f,PARAM_RAW);
            $filename = required_param('filename'.$f,PARAM_RAW);
            $base=$CFG->dataroot.'/'.$vpl->get_course()->id.'/';
            $data = file_get_contents($base.$dirname.'/'.$filename);
            $HTMLheader .= $filename.' '.optional_param('username'.$f,'',PARAM_TEXT);
            */
        }elseif($type == 3){
            global $CFG;
            $data='';
            $zipname = required_param('zipfile'.$f,PARAM_RAW);
            $filename = required_param('filename'.$f,PARAM_RAW);
            $HTMLheader .= $filename.' '.optional_param('username'.$f,'',PARAM_TEXT);
            $ext = strtoupper(pathinfo($zipname,PATHINFO_EXTENSION));
            if($ext != 'ZIP'){
                print_error('nozipfile');
            }
            $zip = new ZipArchive();
            $zipfilename=vpl_similarity::get_zip_filepath($zipname);
            if($zip->open($zipfilename)){
                $data=$zip->getFromName($filename);
                $zip->close();
            }
        }else{
            print_error('type error');
        }
    }
}

