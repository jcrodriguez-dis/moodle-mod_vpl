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
 * Classes to manage file from difrerent soruces
 *
 * @package mod_vpl
 * @copyright 2015 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/**
 * @abstract class to represent files from any source
 */
class vpl_file_from_base{
    public function show_info(){
    }
    public function can_access(){
        return false;
    }
    public function get_userid(){
        return '';
    }
}

/**
 * Information of a file from a directory
 */
class vpl_file_from_dir extends vpl_file_from_base{
    static $usersname=array();
    var $dirname;
    var $filename;
    var $userid;
    //This is for compatibility with GAP 2.x application
    static public function process_gap_userfile($filepath){
        if(strtolower(basename($filepath)) == 'datospersonales.gap'){
            $NIF = '';
            $nombre='';
            $apellidos='';
            $lines = explode("\n",file_get_contents($filepath));
            if(count($lines)>3){
                if(strpos($lines[1],'NIF=')!==false){
                    $NIF = substr($lines[1],4);
                }
                if(strpos($lines[2],'Nombre=')!==false){
                    $nombre = substr($lines[2],7);
                }
                if(strpos($lines[3],'Apellidos=')!==false){
                    $apellidos = substr($lines[3],10);
                }
            }
            if($NIF>'' && $nombre>'' && $apellidos>''){
                global $CFG;
                if ($CFG->fullnamedisplay == 'lastname firstname') {
                    self::$usersname[$NIF]=mb_convert_encoding($apellidos.', '.$nombre,'utf-8');
                } else {
                    self::$usersname[$NIF]=mb_convert_encoding($nombre.' '.$apellidos,'utf-8');
                }
            }
        }
    }

    //This is for compatibility with GAP 2.x application
    static public function get_user_id_from_file($filename){
        if(count(self::$usersname)){
            $filename = strtolower($filename);
            foreach(self::$usersname as $userid => $userdata){
                if(strpos($filename,$userid) !== false){
                    return $userid;
                }
            }
        }
        return '';
    }

    function __construct(&$filename,$dirname,$userid=''){
        $this->filename=$filename;
        $this->dirname=$dirname;
        $this->userid=self::get_user_id_from_file($filename);
    }

    function get_userid(){
        return $this->userid;
    }
    public function show_info(){
        $ret='';
        $ret .='<a href="'.'file.php'.'">';
        $ret .=s($this->filename).' ';
        $ret .= '</a>';
        if($this->userid!=''){
            $ret .= ' '.self::$usersname[$this->userid];
        }
        return $ret;
    }
    public function can_access(){
        return $this->filename != vpl_similarity_preprocess::joinedfilename;
    }
    public function link_parms($t){
        $res = array('type'.$t=>2,'dirname'.$t => $this->dirname,'filename'.$t => $this->filename);
        if($this->userid!=''){
            $res['username'.$t]=self::$usersname[$this->userid];;
        }
        return $res;
    }
}

/**
 * Information of a file from a zip file
 *
 */
class vpl_file_from_zipfile extends vpl_file_from_dir{
    function __construct(&$filename,$zipname,$userid=''){
        parent::__construct($filename,$zipname,$userid);
    }
    public function show_info(){
        $ret='';
        $ret .=s($this->filename);
        if($this->userid!=''){
            $ret .= ' '.self::$usersname[$this->userid];
        }
        return $ret;
    }
    public function can_access(){
        return true;
    }
    public function link_parms($t){
        $res = array('type'.$t=>3,'zipfile'.$t=>$this->dirname,'filename'.$t => $this->filename);
        if($this->userid!=''){
            $res['username'.$t]=self::$usersname[$this->userid];;
        }
        return $res;
    }
}

/**
 * Information of a file from other vpl activity
 *
 */
class vpl_file_from_activity extends vpl_file_from_base{
    static $vpls=array();
    var $vplid;
    var $filename;
    var $subid;
    var $userid;
    function __construct(&$filename,&$vpl,$subinstance){
        $id = $vpl->get_instance()->id;
        if(!isset(self::$vpls[$id])){
            self::$vpls[$id]=$vpl;
        }
        $this->vplid = $id;
        $this->filename=$filename;
        $this->userid=$subinstance->userid;
        $this->subid=$subinstance->id;
    }
    public function show_info(){
        global $DB;
        $vpl = self::$vpls[$this->vplid];
        $cmid = $vpl->get_course_module()->id;
        $ret='';
        if($this->userid>=0){
            $user = $DB->get_record('user',array('id' => $this->userid));
        }else{
            $user = false;
        }
        if($user){
            $ret .='<a href="'.vpl_mod_href('/forms/submissionview.php','id',$cmid,'userid',$user->id).'">';
        }
        $ret .=s($this->filename);
        if($user){
            $ret .= '</a> ';
            $sub = new mod_vpl_submission($vpl,$this->subid);
            $ret .=    $sub->print_grade_core().'<br />';
            $ret .= $vpl->user_fullname_picture($user);
            $ret .=' (<a href="'.vpl_mod_href('/similarity/user_similarity.php','id',$vpl->get_course()->id,'userid',$user->id).'">';
            $ret .= '*</a>)';
        }
        return $ret;
    }
    function get_userid(){
        return $this->userid;
    }
    public function can_access(){
        return $this->filename != vpl_similarity_preprocess::joinedfilename;
    }
    public function link_parms($t){
        return array('type'.$t=>1,'subid'.$t=>$this->subid,'filename'.$t => $this->filename);
    }
}

/**
 * Utility class to get list of preprocessed files
 *
 */
class vpl_similarity_preprocess{
    /**
     * @var const fake file name for files joined
     */
    const joinedfilename='_joined_files_';
    /**
     * @var minimum number of tokens needed to accept a file to be compared
     */
    const mintokens=10;
    /**
     * Preprocesses activity, loading activity files into $simil array
     * @param $simil array of file processed objects
     * @param $vpl activity to process
     * @param $filesselected array if set only files selected
     * @param $allfiles preprocess sll files
     * @param $joinedfiles join files as one
     * @param $SPB Box used to show load process
     * @return void
     */
    static public function proccess_files($fgm, $filesselected,$allfiles,$joinedfiles,
        $vpl=false,$subinstance=false,$toRemove=array()){
        $files = array();
        $filelist = $fgm->getFileList();
        $simjf=false;
        $from=null;
        $joinedfilesdata='';
        foreach($filelist as $filename){
            //debugging("Filename ".$filename, DEBUG_DEVELOPER);
            if( !isset($filesselected[$filename]) && !$allfiles){
                continue;
            }
            $sim = vpl_similarity_factory::get($filename);
            if($sim){
                $data = $fgm->getFileData($filename);
                if($joinedfiles){
                    if(!$simjf){
                        $simjf = $sim;
                    }
                    $joinedfilesdata.= $data."\n";
                }else{
                    if($vpl){
                        $from =new vpl_file_from_activity($filename,$vpl,$subinstance);
                    }
                    if(isset($toRemove[$filename])){
                        $sim->init($data,$from,$toRemove[$filename]);
                    }else{
                        $sim->init($data,$from);
                    }
                    if($sim->size>self::mintokens){
                        $files[$filename]=$sim;
                    }
                }
            }
        }
        if($simjf){
            $filename=self::joinedfilename;
            if($vpl){
                $from =new vpl_file_from_activity($filename,$vpl,$subinstance);
            }
            if(isset($toRemove[$filename])){
                $simjf->init($joinedfilesdata,$from,$toRemove[$filename]);
            }else{
                $simjf->init($joinedfilesdata,$from);
            }
            if($simjf->size>self::mintokens){
                $files[self::joinedfilename]=$simjf;
            }
        }
        return $files;
    }

    static public function activity(&$simil, $vpl, $filesselected=array(),$allfiles,$joinedfiles,$SPB){
        $vpl->require_capability(VPL_SIMILARITY_CAPABILITY);
        $list = $vpl->get_students();
        if(count($list) == 0){
            return;
        }
        $submissions = $vpl->all_last_user_submission();
        //Get initial content files
        $reqf = $vpl->get_required_fgm();
        $toRemove = self::proccess_files($reqf, $filesselected, $allfiles, $joinedfiles);

        $SPB->set_max(count($list));
        //debugging("Submissions ".count($submissions), DEBUG_DEVELOPER);
        $i = 0;
        foreach ($list as $user) {
            $i++;
            $SPB->set_value($i);
            if(isset($submissions[$user->id])){
                $subinstance = $submissions[$user->id];
                $submission = new mod_vpl_submission($vpl,$subinstance);
                $subf = $submission->get_submitted_fgm();
                $files = self::proccess_files($subf, $filesselected, $allfiles, $joinedfiles,
                            $vpl,$subinstance,$toRemove);
                foreach($files as $file){
                    $simil[]=$file;
                }
            }
        }
    }

    /**
     * Preprocesses user activity, loading user activity files into $simil array
     * @param $simil array of file processed objects
     * @param $vpl activity to process
     * @param $userid id of the user to preprocess
     * @return void
     */
    static public function user_activity(&$simil, $vpl, $userid){
        $subinstance=$vpl->last_user_submission($userid);
        if(!$subinstance){
            return;
        }
        $vpl->require_capability(VPL_SIMILARITY_CAPABILITY);
        //Get initial content files
        $reqf = $vpl->get_required_fgm();
        $filelist = $reqf->getFileList();
        $toRemove = array();
        foreach($filelist as $filename){
            //debugging("Filename ".$filename, DEBUG_DEVELOPER);
            $sim = vpl_similarity_factory::get($filename);
            if($sim){
                $data = $reqf->getFileData($filename);
                $sim->init($data,null);
                $toRemove[$filename]=$sim;
            }
        }
        $origin = '';
        $submission = new mod_vpl_submission($vpl,$subinstance);
        $subf = $submission->get_submitted_fgm();
        $filelist = $subf->getFileList();
        foreach($filelist as $filename){
            //debugging("Filename ".$filename, DEBUG_DEVELOPER);
            $sim = vpl_similarity_factory::get($filename);
            if($sim){
                $data = $subf->getFileData($filename);
                $from =new vpl_file_from_activity($filename,$vpl,$subinstance);
                if(isset($toRemove[$filename])){
                    $sim->init($data,$from,$toRemove[$filename]);
                }else{
                    $sim->init($data,$from);
                }
                if($sim->size>10){
                    $simil[]=$sim;
                }
            }
        }
    }

    static public function get_zip_filepath($zipname){
        global $CFG,$COURSE;
        $zipname=basename($zipname);
        return $CFG->dataroot . '/temp/vpl_zip/'.$COURSE->id.'_'.$zipname;
    }

    static public function create_zip_file($zipname, $zipdata){
        $filename = self::get_zip_filepath($zipname);
        $fp = vpl_fopen($filename);
        fwrite($fp,$zipdata);
        fclose($fp);
    }

    /**
     * Preprocesses ZIP file, loading processesed files into $simil array
     * @param $simil array of file processed objects
     * @param $vpl activity to process
     * @param $filesselected array if set only files selected
     * @param $allfiles preprocess sll files
     * @param $joinedfiles join files as one
     * @param $SPB
     * @return void
     */
    static public function zip(&$simil, $zipname, $zipdata, $vpl, $filesselected=array(), $allfiles, $joinedfiles,$SPB){
        global $CFG;
        $ext = strtoupper(pathinfo($zipname,PATHINFO_EXTENSION));
        if($ext != 'ZIP'){
            print_error('nozipfile');
        }
        self::create_zip_file($zipname, $zipdata);
        $zip = new ZipArchive();
        $zipfilename=self::get_zip_filepath($zipname);
        //debugging("Unzip file ".$zipfilename, DEBUG_DEVELOPER);
        $SPB->set_value(get_string('unzipping',VPL));
        if($zip->open($zipfilename)){
            $SPB->set_max($zip->numFiles);
            $i=1;
            for($i=0; $i < $zip->numFiles ;$i++){
                $SPB->set_value($i+1);
                $filename = $zip->getNameIndex($i);
                if($filename==false) break;
                $data = $zip->getFromIndex($i);
                if($data){
                    //debugging("Examining file ".$filename, DEBUG_DEVELOPER);
                    //TODO remove if no GAP file
                    vpl_file_from_zipfile::process_gap_userfile($filename);
                    if( !isset($filesselected[basename($filename)]) && !$allfiles){
                        continue;
                    }
                    $sim = vpl_similarity_factory::get($filename);
                    if($sim){
                        $from =new vpl_file_from_zipfile($filename,$zipname);
                        $sim->init($data,$from);
                        if($sim->size>10){
                            $simil[]=$sim;
                        }
                    }
                }
            }
        }
        $SPB->set_value($zip->numFiles);
    }
}
