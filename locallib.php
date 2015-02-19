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
 * Common functions of VPL
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
defined('MOODLE_INTERNAL') || die();

define('VPL','vpl');
define('VPL_SUBMISSIONS','vpl_submissions');
define('VPL_JAILSERVERS','vpl_jailservers');
define('VPL_RUNNING_PROCESSES','vpl_running_processes');
define('VPL_VARIATIONS','vpl_variations');
define('VPL_ASSIGNED_VARIATIONS','vpl_assigned_variations');
define('VPL_GRADE_CAPABILITY','mod/vpl:grade');
define('VPL_VIEW_CAPABILITY','mod/vpl:view');
define('VPL_SUBMIT_CAPABILITY','mod/vpl:submit');
define('VPL_SIMILARITY_CAPABILITY','mod/vpl:similarity');
define('VPL_ADDINSTANCE_CAPABILITY','mod/vpl:addinstance');
define('VPL_SETJAILS_CAPABILITY','mod/vpl:setjails');
define('VPL_MANAGE_CAPABILITY','mod/vpl:manage');

require_once dirname(__FILE__).'/vpl.class.php';

/**
 * Set get vpl session var
 *
 * @param string $varname name of the session var without 'vpl_'
 * @param string $default default value
 * @param string $parname optional parameter name
 * @return $varname/param value
 **/
function vpl_get_set_session_var($varname,$default,$parname=null){
    global $SESSION;
    if($parname==null){
        $parname=$varname;
    }
    $res=$default;
    $fullname='vpl_'.$varname;
    if(isset($SESSION->$fullname)){ // exists var?
        $res = $SESSION->$fullname;
    }
    $res = optional_param($parname,$res, PARAM_ALPHA);
    $SESSION->$fullname = $res;
    return $res;
}

/**
 * Open/create a file and its dir
 *
 * @param $filename path to file
 * @return file descriptor
 **/
function vpl_fopen($filename){
    global $CFG;

    if(!file_exists($filename)){ // exists file?
        $dir = dirname($filename);
        if(!file_exists($dir)){ // create dir?
            mkdir($dir,$CFG->directorypermissions,true);
        }
    }
    $fp= fopen($filename,'wb+');
    return $fp;
}

/**
 * Recursively delete a directory
 *
 * @return bool All delete
 **/
function vpl_delete_dir($dirname){
    $ret = false;
    if(file_exists($dirname)){
        $ret = true;
        if(is_dir($dirname)){
            $dd = opendir($dirname);
            if(!$dd){
                return false;
            }
            $list = array();
            while($name=readdir($dd)){
                if($name != '.' && $name != '..'){
                    $list[]=$name;
                }
            }
            closedir($dd);
            $ret = true;
            foreach ($list as $name) {
                $ret = vpl_delete_dir($dirname.'/'.$name) and $ret;
            }
            $ret = rmdir($dirname)and $ret;
        }
        else{
            $ret = unlink($dirname);
        }
    }
    return $ret;
}

/**
 * get parsed lines of a file
 *
 * @param $filename string
 * @return array of lines of the file
 **/
function vpl_read_list_from_file($filename){
    $ret = array();
    if(file_exists($filename)){
        $data = file_get_contents($filename);
        if($data>''){
            $nl = vpl_detect_newline($data);
            $ret = explode($nl,$data);
        }
    }
    return $ret;
}

/**
 * Save an array in a file
 * @param $filename string
 **/
function vpl_write_list_to_file($filename, $list){
    $data='';
    foreach ($list as $info) {
        if($info > ''){
            if($data > ''){
                $data .= "\n";
            }
            $data .= $info;
        }
    }
    $fp = vpl_fopen($filename);
    fwrite($fp,$data);
    fclose($fp);
}

/**
 * Get lang code
 * @parm $bash_adapt true adapt lang to bash LANG (default false)
 * @return string
 */
function vpl_get_lang($bash_adapt=false){
    global $SESSION,$USER,$CFG;
    $common_langs = array (
            'aa' => 'DJ',
            'af' => 'ZA',
            'am' => 'ET',
            'an' => 'ES',
            'az' => 'AZ',
            'ber' => 'DZ',
            'bg' => 'BG',
            'ca' => 'ES',
            'cs' => 'CZ',
            'da' => 'DK',
            'de' => 'DE',
            'dz' => 'BT',
            'en' => 'US',
            'es' => 'ES',
            'et' => 'EE',
            'fa' => 'IR',
            'fi' => 'FI',
            'fr' => 'FR',
            'hu' => 'HU',
            'ig' => 'NG',
            'it' => 'IT',
            'is' => 'IS',
            'ja' => 'JP',
            'km' => 'KH',
            'ko' => 'KR',
            'lo' => 'LA',
            'lv' => 'LV',
            'pt' => 'PT',
            'ro' => 'RO',
            'ru' => 'RU',
            'se' => 'NO',
            'sk' => 'sk',
            'so' => 'SO',
            'sv' => 'SE',
            'or' => 'IN',
            'th' => 'th',
            'ti' => 'ET',
            'tk' => 'TM',
            'tr' => 'TR',
            'uk' => 'UA',
            'yo' => 'NG'
    );
    if(isset($SESSION->lang)){
        $lang=$SESSION->lang;
    }elseif(isset($USER->lang)){
        $lang=$USER->lang;
    }elseif(isset($CFG->lang)){
        $lang=$CFG->lang;
    }else{
        $lang='en';
    }
    if($bash_adapt){
        $parts=explode('_',$lang);
        if(count($parts) == 2){
            $lang=$parts[0];
        }
        if(isset($common_langs[$lang])){
            $lang=$lang.'_'.$common_langs[$lang];
        }
        $lang.='.UTF-8';
    }
    return $lang;
}

/**
 * generate URL to page with params
 * @param $page string page from wwwroot
 * @param $var1 string var1 name optional
 * @param $value1 string value of var1 optional
 * @param $var2 string var2 name optional
 * @param $value2 string value of var2 optional
 * @param ...
 **/
function vpl_abs_href(){
    global $CFG;
    $parms = func_get_args();
    $l=count($parms);
    $href = $CFG->wwwroot.$parms[0];
    for( $p=1;$p<$l-1; $p+=2 ) {
        $href .= ($p>1?'&amp;':'?')
        .urlencode($parms[$p]).'='
        .urlencode($parms[$p+1]);
    }
    return $href;
}

/**
 * generate URL to page with params
 * @param $page string page from wwwroot/mod/vpl/
 * @param $var1 string var1 name optional
 * @param $value1 string value of var1 optional
 * @param $var2 string var2 name optional
 * @param $value2 string value of var2 optional
 * @param ...
 **/
function vpl_mod_href(){
    global $CFG;
    $parms = func_get_args();
    $l=count($parms);
    $href = $CFG->wwwroot.'/mod/vpl/'.$parms[0];
    for( $p=1;$p<$l-1; $p+=2 ) {
        $href .= ($p>1?'&amp;':'?')
        .urlencode($parms[$p]).'='
        .urlencode($parms[$p+1]);
    }
    return $href;
}

/**
 * generate URL relative page with params
 * @param $page string page relative
 * @param $var1 string var1 name optional
 * @param $value1 string value of var1 optional
 * @param $var2 string var2 name optional
 * @param $value2 string value of var2 optional
 * @param ...
 **/
function vpl_rel_url(){
    $parms = func_get_args();
    $l=count($parms);
    $url = $parms[0];
    for( $p=1;$p<$l-1; $p+=2 ) {
        $url .= ($p>1?'&amp;':'?')
        .urlencode($parms[$p]).'='
        .urlencode($parms[$p+1]);
    }
    return $url;
}
/**
 * Add a parm to a url
 * @param $url string
 * @param $parm string name
 * @param $value string value of parm
 **/
function vpl_url_add_param($url,$parm,$value){
    if(strpos($url,'?')){
        return $url . '&amp;'.urlencode($parm).'='.urlencode($value);
    }else{
        return $url . '?'.urlencode($parm).'='.urlencode($value);
    }
}

/**
 * Print a message and redirect
 * @param $message string to be print
 * @param $link URL to redirect to
 * @param $wait int time to wait in seconds
 * @return void
 */
function vpl_redirect($link,$message,$wait=4){
    global $OUTPUT, $VPL_OUTPUTHEADER;
    if(!(isset($VPL_OUTPUTHEADER) && $VPL_OUTPUTHEADER===true)){
        echo $OUTPUT->header();
    }
    static $idcount=0;
    $idcount++;
    $text='<div class="redirectmessage">'.s($message).'<br/></div>';
    $text.='<div class="continuebutton"><a id="vpl_red'.$idcount.'" href="'.$link.'">'.get_string('continue').'</a></div>';
    $deco = urldecode($link);
    $deco = html_entity_decode($deco);
    if($wait == 0){
        echo vpl_include_js('window.location.replace("'.$deco.'");');
    }else{
        $js = 'var vpl_jump=function (){window.location.replace("'.$deco.'");};';
        echo vpl_include_js($js."setTimeout('vpl_jump()',$wait*1000);");
    }
    echo $text;
    echo $OUTPUT->footer();
    die;
}


/**
 * Inmediate redirect
 * @param $link URL to redirect to
 * @return void
 */
function vpl_inmediate_redirect($url){
    vpl_redirect($url,'',0);
}

function vpl_include_jsfile($file,$defer=true){
    global $PAGE;
    $PAGE->requires->js(new moodle_url('/mod/vpl/jscript/'.$file),!$defer);
}

function vpl_include_js($jscript){
    if($jscript==''){
        return '';
    }
    $ret='<script type="text/javascript">';
    $ret .="\n//<![CDATA[\n";
    $ret .= $jscript;
    $ret .="\n//]]>\n</script>\n";
    return $ret;
}

/**
 * Popup message box to show text
 * @param $text text to show. It use s() to sanitize text
 * @param $print
 * @return void
 */
function vpl_js_alert($text,$print=true){
    $aux = addslashes($text); //Sanitize text
    $aux = str_replace("\n","\\n",$aux); //Add \n to show multiline text
    $aux = str_replace("\r","",$aux); //Remove \r
    $ret=vpl_include_js('alert("'.$aux.'");');
    if($print){
        echo $ret;
        @ob_flush();
        flush();
    }else{
        return $ret;
    }
}

function vpl_get_select_time($maximum=null){
    $minute=60;
    if($maximum === null){ //Default value
        $maximum = 35*$minute;
    }
    $ret = array(0 => get_string('select'));
    if($maximum <= 0){
        return $ret;
    }
    $value=4;
    if($maximum < $value){
        $value=$maximum;
    }
    while($value <= $maximum){
        if($value < $minute){
            $ret[$value] = get_string('numseconds','',$value);
        }else{
            $num = (int)($value / $minute);
            $ret[$num*$minute] = get_string('numminutes','',$num);
            $value = $num*$minute;
        }
        $value *=2;
    }
    return $ret;
}

/**
 * Return the post_max_size PHP config option in bytes
 * @return int max size in bytes
 */
function vpl_get_max_post_size(){
    $maxs = trim(ini_get('post_max_size'));
    $len = strlen($maxs);
    $last = strtolower($maxs[$len-1]);
    $max = (int) substr($maxs,0,$len-1);
    if($last == 'k'){
        $max *= 1024;
    }elseif ($last == 'm'){
        $max *= 1024*1024;
    }elseif ($last == 'g'){
        $max *= 1024*1024*1000;
    }
    return $max;
}

/**
 * Convert a size in byte to string in Kb, Mb, Gb and Tb
 * Following IEC "Prefixes for binary multiples"
 * @param $size int size in bytes
 * @return string
 */
function vpl_conv_size_to_string($size){
    static $measure = array(1024,1048576,1073741824,1099511627776,PHP_INT_MAX);
    static $measure_name = array('KiB','MiB','GiB','TiB');
    for($i=0; $i<count($measure)-1; $i++){
        if($measure[$i]<=0){ //Check for int overflow
            $num = $size / $measure[$i-1];
            return sprintf('%.2f %s',$num,$measure_name[$i-1]);
        }
        if($size < $measure[$i+1]){
            $num = $size / $measure[$i];
            if($num >= 3 || $size % $measure[$i] == 0){
                return sprintf('%4d %s',$num,$measure_name[$i]);
            }else{
                return sprintf('%.2f %s',$num,$measure_name[$i]);
            }
        }
    }
}

/**
 * Return the array key after or equal to value
 * @param $array
 * @param $value of key to search
 * @return key found
 */
function vpl_get_array_key($array,$value){
    foreach($array as $key => $nothing){
        if($key >= $value){
            return $key;
        }
    }
    return $key;
}

/**
 * Return un array with the format [size in bytes]=> size in text
 * The first element is [0] => select
 * @param $minimum the initial value
 * @param $maximum the limit of values generates
 * @return array
 */
function vpl_get_select_sizes($minimum=0,$maximum=PHP_INT_MAX){
    $maximum = (int) $maximum;
    if($maximum < 0){
        $maximum = PHP_INT_MAX;
    }
    if($maximum > 17.0e9){
        $maximum = 16*1073741824;
    }
    $ret = array(0 => get_string('select'));
    if($minimum>0){
        $value=$minimum;
    }else{
        $value=256*1024;
    }
    $pre=0;
    $increment = $value/4;
    while($value <= $maximum && $value >0){ //Avoid int overflow
        $ret[$value] = vpl_conv_size_to_string($value);
        $pre=$value;
        $value +=$increment;
        $value = (int) $value;
        if($pre >= $value){ //Check for loop end
            break;
        }
        if($value>= 8*$increment){
            $increment=$value/4;
        }
    }
    if($pre < $maximum){ //Show limit value
        $ret[$maximum] = vpl_conv_size_to_string($maximum);
    }
    return $ret;
}

/**
 * @param string $data
 * @return string newline separator "\r\n", "\n", "\r"
 */
function vpl_detect_newline(&$data){
    //Detect text newline chars
    if(strrpos($data,"\r\n") !== false){
        return "\r\n"; //Windows
    }else if(strrpos($data,"\n") !== false){
        return "\n"; //UNIX
    }else if(strrpos($data,"\r") !== false){
        return "\r"; //Mac
    }else{
        return "\n"; //Default Unix
    }
}

function vpl_notice($text,$classes='generalbox'){
    global $OUTPUT;
    echo $OUTPUT->box($text,$classes,'vpl.hide');
}

function vpl_error($text,$classes=''){
    vpl_notice($text,'errorbox');
}

/**
 * Remove trailing zeros from a float as string
 * @param $value
 * @return string
 */
function vpl_rtzeros($value){
    if(strpos($value,'.') || strpos($value,',')){
        return rtrim(rtrim($value,'0'),'.,');
    }
    return $value;
}

/**
 * Generate an array with index an values $url.index
 * @param $url url base
 * @param $array array of index
 * @return array with index as key and url as value
 */
function vpl_select_index($url,$array){
    $ret =array();
    foreach( $array as $value){
        $ret[$value]= $url.$value;
    }
    return $ret;
}

/**
 * Generate an array ready to be use in $OUTPUT->select_url
 * @param $url url base
 * @param $array array of values
 * @return array with url as key and text as value
 */
function vpl_select_array($url,$array){
    $ret =array();
    foreach($array as $value){
        $ret[$url.$value]= get_string($value, VPL);
    }
    return $ret;
}

function vpl_is_valid_path_name($path) {
    if (strlen($path) > 256)
        return false;
    $dirs = explode('/',$path);
    for ($i = 0; $i < count($dirs); $i++){
        if (!vpl_is_valid_file_name($dirs[$i]))
            return false;
    }
    return true;
}

function vpl_is_valid_file_name($fileName){
    $regexp = '/[\x00-\x1f]|[:-@]|[{-~]|\\|\[|\]|[\/\^`´]|^\-|^ | $|\.\./';
    if (strlen($fileName) < 1) return false;
    if (strlen($fileName) > 128) return false;
    return preg_match($regexp,$fileName) === 0;
}

function vpl_truncate_string(&$string, $limit){
    $limit -= 3; //Add space for ...
    if(strlen($string) > $limit )
        $string = substr($string,0,$limit).'...';
}

function vpl_truncate_VPL($instance){
    vpl_truncate_string($instance->name,255);
    vpl_truncate_string($instance->requirednet,255);
    vpl_truncate_string($instance->password,255);
    vpl_truncate_string($instance->variationtitle,255);
}

function vpl_truncate_VARIATIONS($instance){
    vpl_truncate_string($instance->identification,40);
}

function vpl_truncate_RUNNING_PROCESSES($instance){
    vpl_truncate_string($instance->server,255);
}

function vpl_truncate_JAILSERVERS($instance){
    vpl_truncate_string($instance->server,255);
}

function vpl_get_webservice_available(){
    global $DB,$USER,$CFG;
    if($USER->id<=2)
        return false;
    if(! $CFG->enablewebservices)
        return false;
    $service = $DB->get_record('external_services', array('shortname' => 'mod_vpl_edit', 'enabled' => 1));
    return !empty($service);
}

function vpl_get_webservice_token($vpl){
    global $DB,$SESSION,$USER,$CFG;
    $now = time();
    if($USER->id<=2)
        return '';
    if(! $CFG->enablewebservices)
        return '';
    $service = $DB->get_record('external_services',
            array(
                    'shortname' => 'mod_vpl_edit',
                    'enabled' => 1
            )
    );
    if(empty($service))
        return '';
    $token_record = $DB->get_record('external_tokens',
            array(
                    'sid' => session_id(),
                    'userid' => $USER->id,
                    'externalserviceid' => $service->id
            )
    );
    if(!empty($token_record) and $token_record->validuntil < $now){
        unset($token_record); //Will be delete before creating a new one
    }
    if(empty($token_record)){
        //Remove old tokens from DB
        $select = 'validuntil > 0 AND  validuntil < ?';
        $DB->delete_records_select('external_tokens',$select,array($now));
        //Select unique token
        for($i=0 ; $i<100; $i++){
            $token = md5(uniqid(mt_rand(), true));
            $token_record = $DB->get_record('external_tokens',
                    array('token' => $token)
            );
            if(empty($token_record)) break;
        }
        if($i >= 100) return '';
        $token_record = new stdClass;
        $token_record->token = $token;
        $token_record->sid = session_id();
        $token_record->userid = $USER->id;
        $token_record->creatorid = $USER->id;
        $token_record->tokentype = EXTERNAL_TOKEN_EMBEDDED;
        $token_record->timecreated = $now;
        $token_record->validuntil = $now+DAYSECS;
        $token_record->iprestriction = getremoteaddr();
        $token_record->contextid = $vpl->get_context()->id;
        $token_record->externalserviceid = $service->id;
        $DB->insert_record('external_tokens', $token_record);
    }
    return $token_record->token;
}


function vpl_get_webservice_urlbase($vpl){
    global $CFG;
    $token = vpl_get_webservice_token($vpl);
    if($token=='') return '';
    return $CFG->wwwroot
    .'/mod/vpl/webservice.php?moodlewsrestformat=json'
            .'&wstoken='.$token
            .'&id='.$vpl->get_course_module()->id
            .'&wsfunction=';
}
