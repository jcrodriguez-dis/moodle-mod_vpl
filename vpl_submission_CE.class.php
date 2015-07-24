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
 * Submission Compilation Execution class definition
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
defined('MOODLE_INTERNAL') || die();
require_once dirname(__FILE__).'/../../lib/gradelib.php';
require_once dirname(__FILE__).'/vpl_submission.class.php';
require_once dirname(__FILE__).'/jail/jailserver_manager.class.php';
require_once dirname(__FILE__).'/jail/running_processes.class.php';
class mod_vpl_submission_CE extends mod_vpl_submission{
    private static $language_ext  = array(
                            'ada' => 'ada', 'adb' => 'ada', 'ads' => 'ada',
                            'asm' => 'asm',
                            'c' => 'c',
                            'cc'=>'cpp', 'cpp' => 'cpp', 'C' => 'cpp',
                            'clj' => 'clojure',
                            'cs' => 'csharp',
                            'd' => 'd',
                            'go' => 'go',
                            'java' => 'java',
                            'scala' => 'scala',
                            'sql' => 'sql',
                            'scm' => 'scheme','s' => 'scheme',
                            'lisp' => 'lisp','lsp' => 'lisp',
                            'lua' => 'lua',
                            'sh' => 'shell',
                            'pas' => 'pascal','p' => 'pascal',
                            'f77' => 'fortran', 'f' => 'fortran',
                            'pl' => 'prolog', 'pro' => 'prolog',
                            'htm' => 'html', 'html' => 'html',
                            'hs' => 'haskell',
                            'm' => 'matlab',
                            'perl' => 'perl', 'prl' => 'perl',
                            'php' => 'php',
                            'py' => 'python',
                            'vhd' => 'vhdl', 'vhdl' => 'vhdl',
                            'r' => 'r',
                            'rb' => 'ruby', 'ruby' => 'ruby');
    private static $script_name=array('vpl_run.sh'=>'run','vpl_debug.sh'=>'debug','vpl_evaluate.sh'=>'evaluate');

    private static $script_type=array('vpl_run.sh'=>0,'vpl_debug.sh'=>1,'vpl_evaluate.sh'=>2,'vpl_evaluate.cases'=>2);
    private static $script_list=array(0=>'vpl_run.sh',1=>'vpl_debug.sh',2=>'vpl_evaluate.sh');

    const trun=0;
    const tdebug=1;
    const tevaluate=2;

    /**
     * Constructor
     * @param $vpl object vpl instance
     * @param $instance vpl submission
     **/
    function __construct($vpl, $mix) {
        parent::__construct($vpl, $mix);
    }

    /**
     * Return the programming language name based on submitted files extensions
     * @param $filelist array of files submitted to check type
     * @return programming language name
     */
    function get_pln($filelist){
        foreach($filelist as $checkfilename){
            $ext = pathinfo($checkfilename,PATHINFO_EXTENSION);
            if(isset(mod_vpl_submission_CE::$language_ext[$ext])){
                return mod_vpl_submission_CE::$language_ext[$ext];
            }
        }
        return 'default';
    }

    /**
     * Return the default script to manage the action and detected language
     * @param $script 'vpl_run.sh','vpl_debug.sh' o 'vpl_evaluate.sh'
     * @param $pln Programming Language Name
     * @return array key=>filename value =>filedata
     */
    function get_default_script($script,$pln){
        $ret = array();
        $path = dirname(__FILE__).'/jail/default_scripts/';
        $script_type = mod_vpl_submission_CE::$script_name[$script];
        $filename = $path.$pln.'_'.$script_type.'.sh';
        if(file_exists($filename)){
            $ret[$script] = file_get_contents($filename);
        }else{
            $filename=$path.'default'.'_'.$script_type.'.sh';
            if(file_exists($filename)){
                $ret[$script] = file_get_contents($filename);
            }else{
                $ret[$script] = file_get_contents($path.'default.sh');
            }
        }
        if($script == 'vpl_evaluate.sh'){
            $ret['vpl_evaluate.cpp'] = file_get_contents($path.'vpl_evaluate.cpp');
        }
        return $ret;
    }

    /**
     * Recopile execution data to be send to the jail
     * @param $already=array(). List of based on instances, usefull to avoid infinite recursion
     * @param $vpl=null. Instance to process
     * @return object with files, limits, interactive and other info
     */
    function prepare_execution($type, &$already=array(), $vpl=null){
        global $CFG;
        if($vpl == null){
            $vpl = $this->vpl;
        }
        $vpl_instance = $vpl->get_instance();
        if(isset($already[$vpl_instance->id])){
            print_error('Recursive basedon vpl definition');
        }
        $call = count($already);
        $already[$vpl_instance->id]=true;
        $plugincfg = get_config('mod_vpl');
        //Load basedon files if needed
        if($vpl_instance->basedon){
            $basedon = new mod_vpl(null,$vpl_instance->basedon);
            $data = $this->prepare_execution($type,$already,$basedon);
        }else{
            $data=new stdClass();
            $data->files = array();
            $data->filestodelete = array();
            $data->maxtime = (int)$plugincfg->defaultexetime;
            $data->maxfilesize = (int)$plugincfg->defaultexefilesize;
            $data->maxmemory = (int)$plugincfg->defaultexememory;
            $data->maxprocesses = (int)$plugincfg->defaultexeprocesses;
            $data->jailservers = '';
        }
        //Execution files
        $sfg = $vpl->get_execution_fgm();
        $list = $sfg->getFileList();
        foreach ($list as $filename){
            //Skip unneeded script
            if(isset(self::$script_type[$filename]) &&
                self::$script_type[$filename]> $type){
                continue;
            }
            if(isset($data->files[$filename]) &&
            isset(mod_vpl_submission_CE::$script_name[$filename])){
                $data->files[$filename].= "\n".$sfg->getFileData($filename);
            }else{
                $data->files[$filename] = $sfg->getFileData($filename);
            }
            $data->filestodelete[$filename] = 1;
        }
        $deletelist = $sfg->getFileKeepList();
        foreach ($deletelist as $filename){
            unset($data->filestodelete[$filename]);
        }

        if($vpl_instance->maxexetime){
            $data->maxtime=(int)$vpl_instance->maxexetime;
        }
        if($vpl_instance->maxexememory){
            $data->maxmemory=(int)$vpl_instance->maxexememory;
        }
        if($vpl_instance->maxexefilesize){
            $data->maxfilesize=(int)$vpl_instance->maxexefilesize;
        }
        if($vpl_instance->maxexeprocesses){
            $data->maxprocesses=(int)$vpl_instance->maxexeprocesses;
        }
        //Add jailserver list
        if($vpl->get_instance()->jailservers>''){
            $data->jailservers .= "\n".$vpl->get_instance()->jailservers;
        }

        if($call >0 ){ //Stop if at recursive call
            return $data;
        }
        //Submitted files
        $sfg = $this->get_submitted_fgm();
        $list = $sfg->getFileList();
        //$submittedlist is $list but removing the files overwrited by teacher's one
        $submittedlist = array();
        foreach ($list as $filename){
            if(!isset($data->files[$filename])){
                $data->files[$filename] = $sfg->getFileData($filename);
            }
            $submittedlist[] = $filename;
        }
        //Get programming language
        $pln = $this->get_pln($list);
        //Adapt Java and HTML memory limit
        if($pln == 'java' || $pln == 'html'){
            $java_offset = 128*1024*1024; //Checked at Ubuntu 12.04 64 and CentOS 6.5 64
            if($data->maxmemory + $java_offset > $data->maxmemory){
                $data->maxmemory += $java_offset;
            }else{
                $data->maxmemory = (int)PHP_INT_MAX;
            }
        }
        //Limit resource to maximum
        $data->maxtime = min($data->maxtime,(int)$plugincfg->maxexetime);
        $data->maxfilesize = min($data->maxfilesize,(int)$plugincfg->maxexefilesize);
        $data->maxmemory = min($data->maxmemory,(int)$plugincfg->maxexememory);
        $data->maxprocesses = min($data->maxprocesses,(int)$plugincfg->maxexeprocesses);
        //Info send with script
        $info ="#!/bin/bash\n";
        $info .='export VPL_LANG='.vpl_get_lang(true)."\n";
        if($type == 2){ //If evaluation add information
            $info .='export VPL_MAXTIME='.$data->maxtime."\n";
            $info .='export VPL_MAXMEMORY='.$data->maxmemory."\n";
            $info .='export VPL_MAXFILESIZE='.$data->maxfilesize."\n";
            $info .='export VPL_MAXPROCESSES='.$data->maxprocesses."\n";
            $grade_setting=$vpl->get_grade_info();
            if($grade_setting !== false){
               $info .='export VPL_GRADEMIN='.$grade_setting->grademin."\n";
               $info .='export VPL_GRADEMAX='.$grade_setting->grademax."\n";
            }
            $info .='export VPL_COMPILATIONFAILED=\''.addslashes(get_string('VPL_COMPILATIONFAILED',VPL))."'\n";
        }
        $filenames = '';
        $num=0;
        foreach ($submittedlist as $filename){
            $filenames .= $filename.' ';
            $info .='export VPL_SUBFILE'.$num.'="'.$filename."\"\n";
            $num++;
        }
        $info .='export VPL_SUBFILES="'.$filenames."\"\n";
        //Add identifications of variations if exist
        $varids=$vpl->get_variation_identification($this->instance->userid);
        foreach ($varids as $id => $varid){
            $info .='export VPL_VARIATION'.$id.'='.$varid."\n";
        }
        for($i=0; $i<=$type; $i++){
            $script = mod_vpl_submission_CE::$script_list[$i];
            if(isset($data->files[$script]) && trim($data->files[$script])>''){
                if(substr($data->files[$script], 0, 2) != '#!'){
                    //No shebang => add bash
                    $data->files[$script]="#!/bin/bash\n".$data->files[$script];
                }
            }else{
                $files_Added = $this->get_default_script($script,$pln);
                foreach($files_Added as $filename => $filedata){
                    if(trim($filedata)>''){
                        $data->files[$filename] = $filedata;
                        $data->filestodelete[$filename] = 1;
                    }
                }
            }
        }
        //Add script file with VPL environment information
        $data->files['vpl_environment.sh']=$info;
        $data->files['common_script.sh'] = file_get_contents(dirname(__FILE__).'/jail/default_scripts/common_script.sh');

        //TODO change jail server to avoid this patch
        if(count($data->filestodelete)==0){ //If keeping all files => add dummy
            $data->filestodelete['__vpl_to_delete__']=1;
        }
        return $data;
    }

    function jailAction($server,$action,$data){
        if(!function_exists('xmlrpc_encode_request')){
            throw new Exception('Inernal server error: PHP XMLRPC requiered');
        }
        $request = xmlrpc_encode_request($action,$data,array('encoding'=>'UTF-8'));
        $response = vpl_jailserver_manager::get_response($server,$request,$error);
        if($response === false){
            $manager = $this->vpl->has_capability(VPL_MANAGE_CAPABILITY);
            if($manager){
                throw new Exception(get_string('serverexecutionerror',VPL)."\n".$error);
            }
            throw new Exception(get_string('serverexecutionerror',VPL));
        }
        return $response;
    }

    function jailRequestAction($data,$maxmemory,$localservers,&$server){
        $error='';
        $server = vpl_jailserver_manager::get_server($maxmemory, $localservers,$error);
        if($server == ''){
            $manager = $this->vpl->has_capability(VPL_MANAGE_CAPABILITY);
            $men=get_string('nojailavailable',VPL);
            if($manager){
                $men .=": ".$error;
            }
            throw new Exception($men);
        }
        return $this->jailAction($server,'request',$data);
    }

    function jailReaction($action, $process_info=false){
        if($process_info === false){
            $process_info=vpl_running_processes::get($this->get_instance()->userid);
        }
        if($process_info === false){
            throw new Exception('Process not found');
        }
        $server = $process_info->server;
        $data = new stdClass();
           $data->adminticket=$process_info->adminticket;
        return $this->jailAction($server,$action,$data);
    }

    /**
     * Run, debug, evaluate
     * @param int $type (0=run, 1=debug, evaluate=2)
     */
    function run($type){
        //Stop current task if one
        $this->cancelProcess();
        $plugincfg = get_config('mod_vpl');
        $execute_scripts= array(0=>'vpl_run.sh',1=>'vpl_debug.sh',2=>'vpl_evaluate.sh');
        $data = $this->prepare_execution($type);
        $data->execute =$execute_scripts[$type];
        $data->interactive=$type<2?1:0;
        $data->lang = vpl_get_lang(true);
        $localservers=$data->jailservers;
        $maxmemory=$data->maxmemory;
        //Remove jailservers field
        unset($data->jailservers);
        $server='';
        $jailResponse = $this->jailRequestAction($data,$maxmemory,$localservers,$server);
        $isHTTPS = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on";
        $parsed = parse_url($server);
        switch($plugincfg->websocket_protocol) {
             case 'always_use_wss': $use_wss =true; break;
             case 'always_use_ws': $use_wss =false; break;
             default: $use_wss= $isHTTPS;
        }
        $baseURL = $use_wss?'wss://':'ws://';
        $baseURL.=$parsed['host'];
        if($use_wss){
            $baseURL.=':'.$jailResponse['secureport'];
        }elseif(isset($parsed['port'])){
            $baseURL.=':'.$parsed['port'];
        }
        $baseURL.='/';
        $response = new stdClass();
        $response->monitorURL=$baseURL.$jailResponse['monitorticket'].'/monitor';
        $response->executionURL=$baseURL.$jailResponse['executionticket'].'/execute';
        $response->VNChost = $parsed['host'];
        $response->VNCpath = $jailResponse['executionticket'].'/execute';
        $response->VNCsecure = $isHTTPS;
        if($isHTTPS)
           $response->port = $jailResponse['secureport'];
        elseif(isset($parsed['port']))
           $response->port = $parsed['port'];
        else
           $response->port = 80;
        $response->VNCpassword = substr($jailResponse['executionticket'],0,8);
        $instance = $this->get_instance();
        vpl_running_processes::set($instance->userid,
                                  $server,
                                  $instance->vpl,
                                  $jailResponse['adminticket']);
        return $response;
    }

    function retrieveResult(){
        $response = $this->jailReaction('getresult');
        if($response === false){
            throw new Exception(get_string('serverexecutionerror',VPL));
        }
        if($response['interactive']==0){
            $this->saveCE($response);
            if($response['executed']>0){
                //If automatic grading
                if($this->vpl->get_instance()->automaticgrading){
                    $data = new StdClass();
                    $data->grade = $this->proposedGrade($response['execution']);
                    $data->comments = $this->proposedComment($response['execution']);;
                    $this->set_grade($data,true);
                }
            }
        }
        return $this->get_CE_for_editor($response);
    }

    function isRunning(){
        try{
            $response = $this->jailReaction('running');
        }catch(Exception $e){
            return false;
        }
        return $response['running']>0;
       }

    function cancelProcess(){
        $process_info=vpl_running_processes::get($this->get_instance()->userid);
        if($process_info == null) //No process to cancel
            return;
        try{
            $this->jailReaction('stop',$process_info);
        }catch(Exception $e){
            //No matter, consider that the process stopped
        }
        vpl_running_processes::delete($this->get_instance()->userid);
    }
}
