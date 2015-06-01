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
 * Similarity base and utility class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/tokenizer_factory.class.php';
require_once dirname(__FILE__).'/../views/status_box.class.php';

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
        return true;
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
        }
        return $ret;
    }
    function get_userid(){
        return $this->userid;
    }
    public function can_access(){
        return true;
    }
    public function link_parms($t){
        return array('type'.$t=>1,'subid'.$t=>$this->subid,'filename'.$t => $this->filename);
    }
}


/**
 * Similarity preprocesing information of a file
 * from any source (directory, zip file or vpl activity)
 *
 */
class vpl_similarity_base{
    var $from;
    var $size;
    static $value_converter=array(); //array to convert string operators to numbers
    var $vecfrec;
    var $hashses;
    protected static function get_value_id($value){
        if(!isset(self::$value_converter[$value])){
            self::$value_converter[$value]=count(self::$value_converter);
        }
        return self::$value_converter[$value];
    }
    public function get_type(){
        return 0;
    }
    public function get_tokenizer(){
        return vpl_tokenizer_factory::get('base');
    }
    public function sintax_normalize(&$tokens){
        return $tokens;
    }
    public function init(&$data,&$from){
        $HASH_SIZE = 4;
        $HASH_REDUCTION = 1000;
        $this->from=$from;
        $this->size=0;
        $this->vecfrec = array();
        $this->hashes = array();
        //Get tokenizer for this language
        $tok = $this->get_tokenizer();
        //Parse code
        $tok->parse($data);
        //Normalize parsed data transforming or removing code
        $tokens = $tok->get_tokens();
        $tokens = $this->sintax_normalize($tokens);
        //Process tokens to get vector of frecuencies and size
        //Calculate hashes
        $last = array();
        for($i=0; $i<$HASH_SIZE ; $i++){
            $last[$i] = '';
        }
        foreach($tokens as $token){
            if($token->type == vpl_token_type::operator){
                //Calculate hashes table
                for($i=0; $i<$HASH_SIZE-1 ; $i++){
                    $last[$i] = $last[$i+1];
                }
                $last[$HASH_SIZE-1] = $token->value;
                $item = '';
                for($i=0; $i<$HASH_SIZE ; $i++){
                    $item .= $last[$i];
                }
                $hash = crc32($item)%$HASH_REDUCTION;
                if(isset($this->hashes[$hash])){
                    $this->hashes[$hash]++;
                }else{
                    $this->hashes[$hash] = 1;
                }
                $this->size++;
                //Get operator id
                $vid = self::get_value_id($token->value);
                if(isset($this->vecfrec[$vid])){
                    $this->vecfrec[$vid]++;
                }else{
                    $this->vecfrec[$vid]=1;
                }
            }
        }
    }
    public function show_info($ext=false){
        $ret = $this->from->show_info();
        if($ext){
            $ret .= 'valueconverter='.htmlspecialchars(print_r(self::$value_converter,true),ENT_COMPAT | ENT_HTML401,'UTF-8').'<br />';
            $ret .= 'vecfrec='.htmlspecialchars(print_r($this->vecfrec,true),ENT_COMPAT | ENT_HTML401,'UTF-8').'<br />';
            $ret .= 'hashses='.htmlspecialchars(print_r($this->hashes,true),ENT_COMPAT | ENT_HTML401,'UTF-8').'<br />';
        }
        return $ret;
    }
    public function can_access(){
        return $this->from->can_access();
    }
    public function get_userid(){
        return $this->from->get_userid();
    }
    public function link_parms($t){
        return $this->from->link_parms($t);
    }


    /**
     * Get similarity 1 among this file and other
     * @param $other the other file info object
     * @return number 0-100 %
     */
    public function similarity1(&$other){
        $dif1=0;
        $taken=0;
        foreach($this->vecfrec as $op => $frec){
            if(isset($other->vecfrec[$op])){
                if($frec != $other->vecfrec[$op]){
                    $dif1++;
                }
                $taken++;
            }else{
                $dif1++;
            }
        }
        $dif2 = count($other->vecfrec)-$taken;
        return 100*(1-(($dif1+$dif2)/(count($this->vecfrec)+count($other->vecfrec))));
    }

    /**
     * Get similarity 2 among this file and other
     * @param $other the other file info object
     * @return number 0-100 %
     */
    public function similarity2(&$other){
        $dif=0;
        $taken=0;
        foreach($this->vecfrec as $op => $frec){
            if(isset($other->vecfrec[$op])){
                $dif += abs($other->vecfrec[$op]-$frec);
                $taken +=$other->vecfrec[$op];
            }else{
                $dif+=$frec;
            }
        }
        $dif += $other->size - $taken;
        return 100*(1-($dif/($this->size+$other->size)));
    }

    /**
     * Get similarity 3 among this file and other
     * @param $other the other file info object
     * @return number 0-100 %
     */
    public function similarity3(&$other){
        $dif=0;
        $taken=0;
        foreach($this->hashes as $hash => $frec){
            if(isset($other->hashes[$hash])){
                $dif += abs($other->hashes[$hash]-$frec);
                $taken +=$other->hashes[$hash];
            }else{
                $dif+=$frec;
            }
        }
        $dif += $other->size - $taken;
        return 100*(1-($dif/($this->size+$other->size)));
    }
}

//TODO refactor to a protected class
class vpl_files_pair{
    static $id_counter=0;
    static $min_s1=100;
    static $min_s2=100;
    static $min_s3=100;
    static $max_s1=100;
    static $max_s2=100;
    static $max_s3=100;
    public $first;
    public $second;
    public $selected;
    public $s1;
    public $s2;
    public $s3;
    public $id;
    private $cluster_number;
    public function __construct($first=null,$second=null,$s1=0,$s2=0,$s3=0){
        $this->first = $first;
        $this->second = $second;
        $this->selected = false;
        $this->s1 = $s1;
        $this->s2 = $s2;
        $this->s3 = $s3;
        $this->id = self::$id_counter++;
        $this->cluster_number=0;
    }

    static public function set_mins($s1,$s2,$s3){
        self::$min_s1 = $s1;
        self::$min_s2 = $s2;
        self::$min_s3 = $s3;
    }
    static public function set_maxs($s1,$s2,$s3){
        self::$max_s1 = $s1;
        self::$max_s2 = $s2;
        self::$max_s3 = $s3;
    }

    static public function cmp($a,$b){
        $al = $a->get_level();
        $bl = $b->get_level();
        if($al == $bl){
            return 0;
        }
        return $al > $bl ? 1 : -1;
    }

    public function get_link(){
        global $OUTPUT;
        $text = '<spam class="vpl_sim'.(int)$this->get_level1().'">';
        $text .= (int)$this->s1;
        $text .= '</spam>';
        $text .= '|';
        $text .= '<spam class="vpl_sim'.(int)$this->get_level2().'">';
        $text .= (int)$this->s2;
        $text .= '</spam>';
        $text .= '|';
        $text .= '<spam class="vpl_sim'.(int)$this->get_level3().'">';
        $text .= (int)$this->s3;
        $text .= '</spam>';
        if($this->first->can_access() && $this->second->can_access()){
            $url = vpl_mod_href('similarity/diff.php','id',required_param('id', PARAM_INT));
            foreach($this->first->link_parms('1') as $parm => $value){
                $url = vpl_url_add_param($url,$parm,$value);
            }
            foreach($this->second->link_parms('2') as $parm => $value){
                $url = vpl_url_add_param($url,$parm,$value);
            }
            $options = array('height' => 800, 'width' => 900, 'directories' =>0, 'location' =>0, 'menubar'=>0,
                        'personalbar'=>0,'status'=>0,'toolbar'=>0);
            $action = new popup_action('click', $url,'viewdiff'.$this->id,$options);
            $HTML = $OUTPUT->action_link($url, $text,$action);
        }else{
            $HTML = $text;
        }
        $HTML .= $this->s1>=self::$min_s1?'*':'';
        $HTML .= $this->s2>=self::$min_s2?'*':'';
        $HTML .= $this->s3>=self::$min_s3?'*':'';
        $HTML = '<div class="vpl_sim'.(int)$this->get_level().'">'.$HTML.'</div>';
        return $HTML;
    }
    //Return normalize levels to 0-11
    public static function normalize_level($value,$min,$max){
        if(abs($max-$min)< 0.001) return 0;
        return min((1.0-(($value-$min)/($max-$min)))*11,11);
    }
    public function get_level1(){
        if(!isset($this->level1)){
            $this->level1 = (int) self::normalize_level($this->s1,self::$min_s1,self::$max_s1);
        }
        return $this->level1;
    }
    public function get_level2(){
        if(!isset($this->level2)){
            $this->level2 = (int) self::normalize_level($this->s2,self::$min_s2,self::$max_s2);
        }
        return $this->level2;
    }
    public function get_level3(){
        if(!isset($this->level3)){
            $this->level3 = (int) self::normalize_level($this->s3,self::$min_s3,self::$max_s3);
        }
        return $this->level3;
    }
    public function get_level(){
        if(!isset($this->level)){
            $level1 = $this->get_level1();
            $level2 = $this->get_level2();
            $level3 = $this->get_level3();
            $this->level = min($level1,$level2,$level3,11);
        }
        return $this->level;
    }

    public function set_cluster($value){
        $this->cluster_number=$value;
    }

    public function get_cluster(){
        if($this->cluster_number>0){
            return '<a href="#clu'.$this->cluster_number.'">'.$this->cluster_number.'</a>';
        }else{
            return '';
        }
    }
}

/**
 * Utility class to get list of preprocessed files
 *
 */
class vpl_similarity{
    /**
     * scan_activity load activity files into $simil array
     * @param $simil array of file processed objects
     * @param $vpl activity to process
     * @param $filesselected array if set only files selected or unselected will be processed
     * @param $SPB Box used to show load process
     * @param $selected boolean switch to get selected or no selected files (default true)
     * @return void
     */
    static public function scan_activity(&$simil, $vpl, $filesselected=array(), $SPB, $selected=true){
        global $CFG;
        $vpl->require_capability(VPL_SIMILARITY_CAPABILITY);
        $list = $vpl->get_students();
        if(count($list) == 0){
            return;
        }
        $submissions = $vpl->all_last_user_submission();
        $SPB->set_max(count($list));
        //debugging("Submissions ".count($submissions), DEBUG_DEVELOPER);
        $i = 0;
        foreach ($list as $user) {
            $i++;
            $SPB->set_value($i);
            if(isset($submissions[$user->id])){
                $subinstance = $submissions[$user->id];
                $origin = '';
                $submission = new mod_vpl_submission($vpl,$subinstance);
                $subf = $submission->get_submitted_fgm();
                $filelist = $subf->getFileList();
                foreach($filelist as $filename){
                    //debugging("Filename ".$filename, DEBUG_DEVELOPER);
                    if($selected){
                        if(count($filesselected)>0 && !isset($filesselected[$filename])){
                            continue;
                        }
                    }else{
                        if(isset($filesselected[$filename])){
                            continue;
                        }
                    }
                    //debugging("Added ".$filename, DEBUG_DEVELOPER);
                    $sim = vpl_similarity_factory::get($filename);
                    if($sim){
                        $data = $subf->getFileData($filename);
                        $from =new vpl_file_from_activity($filename,$vpl,$subinstance);
                        $sim->init($data,$from);
                        if($sim->size>10){
                            $simil[]=$sim;
                        }
                    }
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

    static public function scan_zip(&$simil, $zipname, $zipdata, $vpl, $filesselected=array(), $SPB){
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
                    if(count($filesselected)>0 && !isset($filesselected[basename($filename)])){
                        continue;
                    }
                    $sim = vpl_similarity_factory::get($filename);
                    if($sim){
                        //TODO locate userid
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
    static function get_dir_filelist($basepath,$dir){
        $filelist = array();
        $dirlist = array();
        if($dir > ''){
            $basedir = $basepath.'/'.$dir;
        }else{
            $basedir = $basepath;
        }
        if($dd = opendir($basedir)){
            while ($file=readdir($dd)) {
                if ($file[0] == '.'){
                    continue;
                }
                $relpath =$dir.'/'.$file;
                $fullpath=$basedir.'/'.$file;
                if(is_file($fullpath)){
                    $filelist[] = $relpath;
                }elseif(is_dir($fullpath)){
                    $dirlist[] = $relpath;
                }
            }
            closedir($dd);
        }
        foreach($dirlist as $adir){
            $otherlist = self::get_dir_filelist($basepath,$adir);
            $filelist = array_merge($filelist,$otherlist);
        }
        return $filelist;
    }
    static public function scan_directory(&$simil, $dir, $vpl, $filesselected=array(), $SPB){
        global $CFG;
        $basedir = $CFG->dataroot.'/'.$vpl->get_course()->id;
        $SPB->set_value(get_string('scanningdir',VPL));
        $filelist = self::get_dir_filelist($basedir.'/'.$dir,'');
        $SPB->set_value((string)count($filelist));
        $SPB->set_max(count($filelist));
        foreach($filelist as $pos => $file){
            $SPB->set_value($pos+1);
            $base = basename($file);
            //TODO remove if no GAP file
            $fullpath = $basedir.'/'.$dir.'/'.$file;
            vpl_file_from_dir::process_gap_userfile($fullpath);
            if(count($filesselected)>0 && !isset($filesselected[$base])){
                continue;
            }
            $sim = vpl_similarity_factory::get($file);
            if($sim){
                $data = file_get_contents($fullpath);
                //TODO locate userid
                $from =new vpl_file_from_dir($file,$dir);
                $sim->init($data,$from);
                if($sim->size>10){
                    $simil[]=$sim;
                }
            }
        }
    }

    static public function get_selected(&$files, $maxselected, $slimit, $SPB){
        $vs1=array();
        $vs2=array();
        $vs3=array();
        $minlevel1=0;
        $minlevel2=0;
        $minlevel3=0;
        $maxlevel1=100;
        $maxlevel2=100;
        $maxlevel3=100;
        $selected = array();
        $jlimit=count($files);
        if($jlimit<$slimit){
            $slimit=$jlimit;
        }
        $SPB->set_max($slimit);
        for($i=0; $i<$slimit; $i++){ //Search similarity with
            $SPB->set_value($i+1);
            $current=$files[$i];
            $current_type=$current->get_type();
            $userid = $current->get_userid();
            for($j=$i+1;$j<$jlimit; $j++){ //Compare with all others
                $other=$files[$j];
                //If not the same language then skip
                if($current_type != $other->get_type() ||
                ($userid!='' && $userid == $other->get_userid())){
                    continue;
                }
                //Calculate metrics
                $s1=$current->similarity1($other);
                $s2=$current->similarity2($other);
                $s3=$current->similarity3($other);
                if($s1>=$minlevel1 || $s2>=$minlevel2 || $s3 >= $minlevel3){
                    $case = new vpl_files_pair($files[$i],$files[$j],$s1,$s2,$s3);
                    $maxlevel1=max($s1,$maxlevel1);
                    $maxlevel2=max($s2,$maxlevel2);
                    $maxlevel3=max($s3,$maxlevel3);
                    if($s1>=$minlevel1){
                        $vs1[] = $case;
                        if(count($vs1) > 2*$maxselected){
                            self::filter_selected($vs1,$maxselected,$minlevel1,1);
                        }
                    }
                    if($s2>=$minlevel2){
                        $vs2[] = $case;
                        if(count($vs2) > 2*$maxselected){
                            self::filter_selected($vs2,$maxselected,$minlevel2,2);
                        }
                    }
                    if($s3>=$minlevel3){
                        $vs3[] = $case;
                        if(count($vs3) > 2*$maxselected){
                            self::filter_selected($vs3,$maxselected,$minlevel3,3);
                        }
                    }
                }
            }
        }
        self::filter_selected($vs1,$maxselected,$minlevel1,1,true);
        self::filter_selected($vs2,$maxselected,$minlevel2,2,true);
        self::filter_selected($vs3,$maxselected,$minlevel3,3,true);
        vpl_files_pair::set_mins($minlevel1,$minlevel2,$minlevel3);
        vpl_files_pair::set_maxs($maxlevel1,$maxlevel2,$maxlevel3);
        //Merge vs1, vs2 and vs3
        $max = count($vs1);
        for($i=0; $i< $max ; $i++){
            if(! $vs1[$i]->selected){
                $selected[]=$vs1[$i];
                $vs1[$i]->selected=true;
            }
            if(! $vs2[$i]->selected){
                $selected[]=$vs2[$i];
                $vs2[$i]->selected=true;
            }
            if(! $vs3[$i]->selected){
                $selected[]=$vs3[$i];
                $vs3[$i]->selected=true;
            }
        }
        //usort of old PHP versions don't call static class functions
//        $corder = new vpl_files_pair;
//        usort($selected,array($corder,'cmp'));
        return $selected;
    }
    static $corder=null;
    static public function filter_selected(&$vec, $maxselected, &$minlevel,$sid, $last=false){
        if(count($vec)>$maxselected || ($last && count($vec)>0)){
            if(self::$corder === null){
                self::$corder = new vpl_similarity;
            }
            //usort of old PHP versions don't call static class functions
            if(!usort($vec,array(self::$corder,'cmp_selected'.$sid))){
                debugging('usort error');
            }
            $field = 's'.$sid;
            $vec = array_slice($vec,0,$maxselected);
            $minlevel=$vec[count($vec)-1]->$field;
        }
    }
    static public function cmp_selected1($a, $b){
        if ($a->s1 == $b->s1) {
            if ($a->s3 == $b->s3) {
                if ($a->s2 == $b->s2) {
                    return 0;
                }
                return ($a->s2 > $b->s2) ? -1 : 1;
            }
            return ($a->s3 > $b->s3) ? -1 : 1;
        }
        return ($a->s1 > $b->s1) ? -1 : 1;
    }

    static public function cmp_selected2($a, $b){
        if ($a->s2 == $b->s2) {
            if ($a->s1 == $b->s1) {
                if ($a->s3 == $b->s3) {
                    return 0;
                }
                return ($a->s3 > $b->s3) ? -1 : 1;
            }
            return ($a->s1 > $b->s1) ? -1 : 1;
        }
        return ($a->s2 > $b->s2) ? -1 : 1;
    }

    static public function cmp_selected3($a, $b){
        if ($a->s3 == $b->s3) {
            if ($a->s1 == $b->s1) {
                if ($a->s2 == $b->s2) {
                    return 0;
                }
                return ($a->s2 > $b->s2) ? -1 : 1;
            }
            return ($a->s1 > $b->s1) ? -1 : 1;
        }
        return ($a->s3 > $b->s3) ? -1 : 1;
    }
}
