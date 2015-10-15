<?php
/**
 * @version		$Id: tokenizer_factory.class.php,v 1.4 2012-06-05 23:22:10 juanca Exp $
 * @package		VPL. Tokenizer factory class
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

class vpl_tokenizer_factory{
	static private $classloaded=array();
	static function get($type){
		if(!isset(self::$classloaded[$type])){
			$include = 'tokenizer_'.$type.'.class.php';
			require_once dirname(__FILE__).'/'.$include;
			$class = 'vpl_tokenizer_'.$type;
			self::$classloaded[$type] = new $class();
		}
		return self::$classloaded[$type];
	}
}
?>