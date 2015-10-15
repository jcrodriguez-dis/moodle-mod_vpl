<?php
/**
 * @version		$Id: tokenizer_java.class.php,v 1.2 2012-06-05 23:22:11 juanca Exp $
 * @package		VPL. Java programing language tokenizer class
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/tokenizer_c.class.php';

class vpl_tokenizer_java extends vpl_tokenizer_c{
	static $reserved_java=null;
	function __construct(){
		parent::__construct();
		if(self::$reserved_java === null){
			self::$reserved_java = array('abstract' => true, 'continue' => true, 'for' => true, 'new' => true, 'switch' => true,
				'assert' => true, 'default' => true, 'goto' => true, 'package' => true, 'synchronized' => true,
				'boolean' => true, 'do' => true, 'if' => true, 'private' => true, 'this' => true,
				'break' => true, 'double' => true, 'implements' => true, 'protected' => true, 'throw' => true,
				'byte' => true, 'else' => true, 'import' => true, 'public' => true, 'throws' => true,
				'case' => true, 'enum' => true, 'instanceof' => true, 'return' => true, 'transient' => true,
				'catch' => true, 'extends' => true, 'int' => true, 'short' => true, 'try' => true,
				'char' => true, 'final' => true, 'interface' => true, 'static' => true, 'void' => true,
				'class' => true, 'finally' => true, 'long' => true, 'strictfp' => true, 'volatile' => true,
				'const' => true, 'float' => true, 'native' => true, 'super' => true, 'while' => true
			);
			$this->reserved=self::$reserved_java;
		}
	}
}

?>