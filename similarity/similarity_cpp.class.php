<?php
/**
 * @version		$Id: similarity_cpp.class.php,v 1.7 2013-06-11 18:28:29 juanca Exp $
 * @package		VPL. C++ language similarity class
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/similarity_c.class.php';

class vpl_similarity_cpp extends vpl_similarity_c{
	public function get_type(){
		return 2;
	}
	public function get_tokenizer(){
		return vpl_tokenizer_factory::get('cpp');
	}
}

?>