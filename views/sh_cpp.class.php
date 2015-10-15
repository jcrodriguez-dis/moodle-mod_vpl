<?php
/**
 * @version		$Id: sh_cpp.class.php,v 1.4 2012-06-05 23:22:10 juanca Exp $
 * @package		VPL. Syntaxhighlighter for C++ language
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/sh_c.class.php';

class vpl_sh_cpp extends vpl_sh_c{
	function __construct(){
		parent::__construct();
		$added = array( 'and' => true, 'and_eq' => true, 'bitand' => true, 'bitor' => true,
					 'bool' => true, 'catch' => true, 'class' => true, 'compl' => true,
					 'const_cast' => true, 'delete' => true, 'dynamic_cast' => true, 'explicit' => true,
 					 'export' => true, 'false' => true, 'friend' => true, 'inline' => true,
 					 'namespace' => true, 'new' => true, 'not' => true, 'not_eq' => true, 'operator' => true,
 					 'or' => true, 'or_eq' => true, 'private' => true, 'protected' => true, 'public' => true,
 					 'reinterpret_cast' => true, 'static_cast' => true, 'template' => true, 'this' => true,
 					 'throw' => true, 'true' => true, 'try' => true, 'typeid' => true, 'typename' => true,
					 'using' => true, 'virtual' => true, 'xor' => true, 'xor_eq' => true);
		$this->reserved= array_merge($this->reserved, $added);
	}
}

?>