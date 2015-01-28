<?php
/**
 * @version		$Id: sh_factory.class.php,v 1.12 2013-04-22 14:13:38 juanca Exp $
 * @package		VPL. Syntaxhighlighters object factory class
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

class vpl_sh_factory{
	static $cache = array();
	static function get_object($type){
		if(!isset(self::$cache[$type])){
			require_once dirname(__FILE__).'/sh_'.$type.'.class.php';
			$class = 'vpl_sh_'.$type;
			self::$cache[$type] = new $class();
		}
		return self::$cache[$type];
	}

	static function get_sh($filename){
		$ext = vpl_fileExtension($filename);
		if(vpl_is_binary($filename)){
			if(vpl_is_image($filename)){
				return self::get_object('image');
			}else{
				return self::get_object('binary');
			}
		}
		if($ext == 'c'){
			return self::get_object('c');
		}elseif($ext == 'cpp' || $ext == 'h'){
			return self::get_object('cpp');
		}elseif($ext == 'java'){
			return self::get_object('java');
		}elseif($ext == 'ada' || $ext == 'adb' || $ext == 'ads'){
			return self::get_object('ada');
		}elseif($ext == 'sql'){
			return self::get_object('sql');
		}elseif($ext == 'scm'){
			return self::get_object('scheme');
		}elseif($ext == 'sh'){
			return self::get_object('sh');
		}elseif($ext == 'pas'){
			return self::get_object('pascal');
		}elseif($ext == 'f77' || $ext == 'f' ){
			return self::get_object('fortran77');
		}elseif($ext == 'pl' ){
			return self::get_object('prolog');
		}elseif($ext == 'm' ){
			return self::get_object('matlab');
		}elseif($ext == 'py' ){
			return self::get_object('python');
		}elseif($ext == 'scala' ){
			return self::get_object('scala');
		}else{
			return self::get_object('geshi');
		}
	}
}
?>