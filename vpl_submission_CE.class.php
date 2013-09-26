<?php
/**
 * @version		$Id: vpl_submission_CE.class.php,v 1.35 2013-07-09 13:32:41 juanca Exp $
 * @package		VPL. submission Compilation Execution class definition
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
defined('MOODLE_INTERNAL') || die();
require_once dirname(__FILE__).'/../../lib/gradelib.php';
require_once dirname(__FILE__).'/vpl_submission.class.php';
require_once dirname(__FILE__).'/jail/HTTP_request.class.php';
require_once dirname(__FILE__).'/jail/jailserver_manager.class.php';
require_once dirname(__FILE__).'/jail/proxy.class.php';
class mod_vpl_submission_CE extends mod_vpl_submission{
	private static $language_ext  = array('c' => 'c',
							'cpp' => 'cpp', 'C' => 'cpp',
							'java' => 'java',
							'ada' => 'ada', 'adb' => 'ada', 'ads' => 'ada',
							'scala' => 'scala',
							'sql' => 'sql',
							'scm' => 'scheme','s' => 'scheme',
							'sh' => 'shell',
							'pas' => 'pascal','p' => 'pascal',
							'f77' => 'fortran', 'f' => 'fortran',
							'pl' => 'prolog', 'pro' => 'prolog',
							'hs' => 'haskell',
							'cs' => 'csharp',
							'm' => 'matlab',
							'perl' => 'perl', 'prl' => 'perl',
							'php' => 'php',
							'py' => 'python',
							'vhd' => 'vhdl', 'vhdl' => 'vhdl',
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
		//Load basedon files if needed
		if($vpl_instance->basedon){
			$basedon = new mod_vpl(null,$vpl_instance->basedon);
			$data = $this->prepare_execution($type,$already,$basedon);
		}else{
			$data=new stdClass();
			$data->files = array();
			$data->filestodelete = array();
			$data->maxtime = (int)$CFG->vpl_defaultexetime;
			$data->maxfilesize = (int)$CFG->vpl_defaultexefilesize;
			$data->maxmemory = (int)$CFG->vpl_defaultexememory;
			$data->maxprocesses = (int)$CFG->vpl_defaultexeprocesses;
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
		if($call >0 ){ //Stop if at recursive call
			return $data;
		}
		//Limit resource to maximum
		$data->maxtime = min($data->maxtime,(int)$CFG->vpl_maxexetime);
		$data->maxfilesize = min($data->maxfilesize,(int)$CFG->vpl_maxexefilesize);
		$data->maxmemory = min($data->maxmemory,(int)$CFG->vpl_maxexememory);
		$data->maxprocesses = min($data->maxprocesses,(int)$CFG->vpl_maxexeprocesses);
				
		//Submitted files
		$sfg = $this->get_submitted_fgm();
		$list = $sfg->getFileList();
		//$submittedlist is $list but removing the files overwrited by teacher's one
		$submittedlist = array();
		foreach ($list as $filename){
			if(!isset($data->files[$filename])){
				$data->files[$filename] = $sfg->getFileData($filename);
				$submittedlist[] = $filename;
			}
		}
		//Info send with script
		$info ="#!/bin/bash\n";
		$info .='export VPL_LANG='.vpl_get_lang(true)."\n";
		if($type == 2){ //If evaluation add information
			$info .='export VPL_MAXTIME='.$data->maxtime."\n";
			$info .='export VPL_MAXMEMORY='.$data->maxmemory."\n";
			$info .='export VPL_MAXFILESIZE='.$data->maxfilesize."\n";
			$info .='export VPL_MAXPROCESSES='.$data->maxprocesses."\n";
			$info .='export VPL_FILEBASEURL='.$CFG->wwwroot.'file.php/'.$vpl->get_course_module()->id."/\n";
			$grade_setting=$vpl->get_grade_info();
			if($grade_setting !== false){
			   $info .='export VPL_GRADEMIN='.$grade_setting->grademin."\n";
			   $info .='export VPL_GRADEMAX='.$grade_setting->grademax."\n";
			}
			$info .='export VPL_COMPILATIONFAILED=\''.get_string('VPL_COMPILATIONFAILED',VPL)."'\n";
		}
		$filenames = '';
		$num=0;
		foreach ($submittedlist as $filename){
			$filenames .= str_replace(' ','\\ ',$filename).' ';
			$info .='export VPL_SUBFILE'.$num.'='.$filename."\n";
			$num++;
		}
		$info .='export VPL_SUBFILES=\''.$filenames."'\n";
		//Add identifications of variations if exist
		$varids=$vpl->get_variation_identification($this->instance->userid);
		foreach ($varids as $id => $varid){
			$info .='export VPL_VARIATION'.$id.'='.$varid."\n";
		}
		$pln = $this->get_pln($list);
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
		
		//Add jailserver list
		if($vpl->get_instance()->jailservers>''){
			$data->jailservers .= "\n".$vpl->get_instance()->jailservers;
		}
		//TODO change jail server to avoid this patch
		if(count($data->filestodelete)==0){ //If keeping all files => add dummy
			$data->filestodelete['__vpl_to_delete__']=1;
		}
		return $data;
	}
	
	static private $send_alive_count=0;
	
	/**
	 * Send text to browser and update the applet editor status bar
	 * @param $to_editor if true update applet status bar process
	 * @return void
	 */
	static function send_alive($to_editor=true){
		if($to_editor){
			echo vpl_include_js('VPL.updateStatusBarProcess(window.parent);');
		}
		echo str_pad('', 4100); //4K+4
		echo "+\n";
		self::$send_alive_count++;
		@ob_flush();
		flush();
	}

	/**
	 * Send startStatusBarProcess to the applet editor status bar
	 * @param $text the action to show
	 * @param $window default window.parent
	 * @return void
	 */
	static function send_start_process($text,$window='window.parent'){
		echo vpl_include_js('VPL.startStatusBarProcess('.$window.',\''.addslashes($text).'\');');
		@ob_flush();
		flush();
	}
	
	/**
	 * Send endStatusBarProcess to the applet editor status bar
	 * @param $text message to show, default nothing
	 * @param $window
	 * @return void
	 */
	static function send_end_process($text='', $window='window.parent'){
		if($text==''){
			echo vpl_include_js('VPL.endStatusBarProcess('.$window.');');
		}else{
			echo vpl_include_js('VPL.endStatusBarProcess('.$window.',"'.$text.'");');
		}
		@ob_flush();
		flush();
	}
	
	/**
	 * Run, debug
	 * @param int $type (0=run, 1=debug)
	 */
	function run($type){
		//Caller Check security (who and config)
		global $CFG;
		$execute_scripts= array(0=>'vpl_run.sh',1=>'vpl_debug.sh',2=>'vpl_evaluate.sh');
		$data = $this->prepare_execution($type);
		$data->execute =$execute_scripts[$type];
		$this->send_alive();
		@ob_flush();
		$proxy = new vpl_doubleproxy($CFG->vpl_proxy_port_from,$CFG->vpl_proxy_port_to);
		$proxy->get_jail_info($jport,$jpass,$cport,$cpass);
		$data->interactive=1;
		$data->ip = $_SERVER['SERVER_ADDR'];
		$data->port=(int)$jport;
		$data->password=$jpass;
		$this->send_alive();
		$limitTime = $data->maxtime; // save maxtime before manipulate $data
		set_time_limit(2*$limitTime);
		//Select server
		$feedback = '';
		$server = vpl_jailserver_manager::get_server($data->maxmemory, $data->jailservers,$feedback);
		if($server == ''){
			$this->send_end_process();
			$men=get_string('nojailavailable',VPL);
			if($this->vpl->has_capability(VPL_MANAGE_CAPABILITY)){
				$men .="\n".$feedback;
			}
			throw new Exception($men);
		}
		//Remove jailservers field
		unset($data->jailservers);
		if(!function_exists('xmlrpc_encode_request')){
			$this->send_end_process();
			throw new Exception('PHP XMLRPC requiered');
		}
		$request = xmlrpc_encode_request('execute',$data,array('encoding'=>'UTF-8'));
		$http=new vpl_HTTP_request($request);
		$this->send_alive();
		if(!$http->try_server($server)){
			$this->send_end_process();
			throw new Exception(get_string('serverexecutionerror',VPL)."\n".$http->get_error());
		}
		$go_on=true;
		$timeUpdateBar=time();
		$limitTime = 2*$limitTime + time(); //Limit time is 2(compilation+execution) * $data->maxtime
		set_time_limit(2*$limitTime+1);
		while($http->is_connected() && time()<$limitTime){
			usleep(50000);
			if($timeUpdateBar!=time()){ //Every second
				$timeUpdateBar=time();
				$this->send_alive();
			}
			$http->advance();
			$proxy->advance();
			if(!$http->is_connected()){
				if($http->get_state() == vpl_HTTP_request::error){
					$this->send_end_process();
					debugging('http error '.$http->get_error());
					$proxy->close();
					throw new Exception('http error '.$http->get_error());
				} 
				$xml_response=$http->get_response();
				//Debug write XML to file
				//file_put_contents('/tmp/reponse_xmlrpc.txt',$xml_response);
				$response=xmlrpc_decode($xml_response,'UTF-8');
				if($response == null){
					$this->send_end_process();
					debugging('xmlrpc_decode error');
					$proxy->close();
					throw new Exception('xmlrpc_decode error');
				}elseif(xmlrpc_is_fault($response)){
					$this->send_end_process();
					debugging('xmlrpc error: '.$response['faultString']);
					$proxy->close();
					throw new Exception('xmlrpc error: '.$response['faultString']);
				}
				//debug
				//p($xml_response);
				//p(print_r($response));
			}
		}
		if($http->is_connected()){
			$this->send_end_process();
			$http->close_handles();
			$proxy->close();
			throw new Exception('Http with jail timeout');
		}
		$this->send_CE_to_editor($response);
		@ob_flush();
		flush();
		if(! $response['executed']){
			$proxy->close();
			$this->send_end_process();
			$proxy->close();
			return;
		}
		//All OK then
		//Open console
		$script="window.parent.document.getElementById('appleteditorid').initConsole($cport,'$cpass');";
		echo vpl_include_js($script);
		@ob_flush();
		flush();
		$jailConnectTimeOut = time()+10; //After 10 seg. without connection timeout
		while($proxy->is_running() && time()<$limitTime){
			usleep(50000);
			if($timeUpdateBar!=time()){ //Every second
				$timeUpdateBar=time();
				$this->send_alive();
			}
			$proxy->advance();
			//Check for jail and console conection timeout
			if( (!$proxy->was_jailconected() || !$proxy->was_clientconected())
				&& $jailConnectTimeOut< time()){
				$this->send_end_process();
				$men = '';
				if(!$proxy->was_jailconected()){
					$men .= "Jail connection timeout\n";
				}
				if(!$proxy->was_clientconected()){
					$text ='Console connection timeout';
					$jpending = $proxy->get_clientpending();
					if($jpending>''){
						$text .= "\n-----------------------\n";
						$text .= "Console output pending:\n";
						//Clean joending
						$parts = explode("\17",$jpending);
						for($i=0;$i < count($parts); $i++)
							if($i % 2 == 0)
								$text .= $parts[$i];
					}
					if(strlen($text)>500){
						$text=substr($text,0,500);
					}
					$men .= $text;
				}
				$proxy->close();
				throw new Exception($men);
			}
			if( $proxy->was_clientconected() && !$proxy->is_clientconected()){
				$this->send_end_process();
				$proxy->close();
				return;
			}
			if($proxy->was_jailconected() && !$proxy->is_jailconected() 
			    && !$proxy->is_jailpending() && !$proxy->is_clientpending()){
				$this->send_end_process();
				$proxy->close();
				return;
			}		
		}
		if($proxy->is_running()){
			$this->send_end_process();
			$proxy->close();
			if(time()>=$limitTime){
				throw new Exception('Proxy global timeout');
			}
		}
	}
	
	/**
	 * Evaluate submission.
	 * @parm $transfer if true send result to applet (default value)
	 * Save evaluation result and send information to the applet editor.
	 * If configured send grade and comments to the gradebook.
	 */
	function evaluate($transfer=true){
		//Caller Check security (who and config)
		global $CFG;
		$data = $this->prepare_execution(self::tevaluate);
		$data->execute ='vpl_evaluate.sh';
		$data->interactive=0;
		$data->port=0;
		$data->password=0;
		$this->send_alive($transfer);
		@ob_flush();
		$limitTime = $data->maxtime; // save maxtime before manipulate $data
		//Select server
		$feedback='';
		$server = vpl_jailserver_manager::get_server($data->maxmemory,$data->jailservers,$feedback);
		if($server == ''){
			if($transfer){
				$this->send_end_process();
			}
			$men=get_string('nojailavailable',VPL);
			if($this->vpl->has_capability(VPL_MANAGE_CAPABILITY)){
				$men .="\n".$feedback;
			}
			throw new Exception($men);
		}
		$request = xmlrpc_encode_request('execute',$data,array('encoding'=>'UTF-8'));
		$http=new vpl_HTTP_request($request);
		$this->send_alive($transfer);
		if(!$http->try_server($server)){
			if($transfer){
				$this->send_end_process();
			}
			throw new Exception(get_string('serverexecutionerror',VPL)."\n".$http->get_error());
		}
		$timeUpdateBar=time();
		$limitTime = 2*$limitTime + time(); //Limit time is 2(compilation+execution) * $data->maxtime
		set_time_limit(2*$limitTime+1);
		while(time()<$limitTime){
			usleep(50000);
			if($timeUpdateBar!=time()){
				$timeUpdateBar=time();
				$this->send_alive($transfer);
			}
			$http->advance();
			if(!$http->is_connected()){
				if($http->get_state() == vpl_HTTP_request::error){
					if($transfer){
						$this->send_end_process();
					}
					debugging('http error '.$http->get_error());
					throw new Exception('http error:'.$http->get_error());
				} 
				$xml_response=$http->get_response();
				//Debug write XML to file
				//file_put_contents('/tmp/reponse_xmlrpc.txt',$xml_response);
				$response=xmlrpc_decode($xml_response,'UTF-8');
				if($response == null){
					if($transfer){
						$this->send_end_process();
					}
					//p($xml_response);
					debugging('xmlrpc_decode error');
					throw new Exception('xmlrpc_decode error');
				}elseif(xmlrpc_is_fault($response)){
					if($transfer){
						$this->send_end_process();
					}
					debugging('xmlrpc error: '.$response['faultString']);
					throw new Exception('xmlrpc error: '.$response['faultString']);
				}
				//debug
				//p($xml_response);
				//p(print_r($response));
				if($transfer){
					$this->send_CE_to_editor($response);
				}
				$this->saveCE($response);
				
				if($response['executed']>0){
					//If automatic grading
					if($this->vpl->get_instance()->automaticgrading){
						$data = new StdClass();
						$data->grade = $this->proposedGrade($response['execution']);
						$data->comments = $this->proposedComment($response['execution']);;
						$this->set_grade($data,true);
					}
				}else{
					if($transfer){
						$this->send_end_process();
					}
				}
				break;
			}
		}
		if($http->is_connected()){
			if($transfer){
				$this->send_end_process();
			}
			$http->close_handles();
			throw new Exception(get_string('httptimeout',VPL));
		}
	}
}
?>