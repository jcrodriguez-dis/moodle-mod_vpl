<?php
/**
 * @version		$Id: similarity_factory.class.php,v 1.8 2013-06-11 18:28:29 juanca Exp $
 * @package		VPL. Similarity object factory classes
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

class vpl_filetype{
	//TODO implement new types
	static private $sstr = array(
						'h'=>'cpp',
						'hxx'=>'cpp',
						'c'=>'c',
						'js' => 'c', //JavaScript as C
						'C'=>'cpp',
						'cpp'=>'cpp',
						'ads'=>'ada',
						'adb'=>'ada',
						'ada'=>'ada',
						'java'=>'java',
						'Java'=>'java',
						'scm'=>'scheme',
						'pl' =>'prolog',
						'scala' => 'scala',
						'py' => 'python',
						'm' => 'matlab'
	);
	static public function str($ext){
		if(isset(self::$sstr[$ext])){
			return self::$sstr[$ext];
		}else{
			return false;
		}
	}
}

class vpl_similarity_factory{
	static private $classloaded=array();
	static private function get_object($type){
		if(!isset($classloaded[$type])){
			$include = 'similarity_'.$type.'.class.php';
			require_once($include);
			$classloaded[$type]=true;
		}
		$class = 'vpl_similarity_'.$type;
		return new $class();
	}

	static public function get($filename){
		$ext = pathinfo($filename,PATHINFO_EXTENSION);
		if($type=vpl_filetype::str($ext)){
			return self::get_object($type);
		}
		else{
			return null;
		}
	}
}
?>