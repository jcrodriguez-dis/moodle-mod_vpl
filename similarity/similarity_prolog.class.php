<?php
/**
 * @version		$Id: similarity_prolog.class.php,v 1.3 2013-06-11 18:28:29 juanca Exp $
 * @package		VPL. Prolog language similarity class
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
require_once dirname(__FILE__).'/similarity_base.class.php';

class vpl_similarity_prolog extends vpl_similarity_base{
	public function get_type(){
		return 6;
	}
	public function sintax_normalize(&$tokens){
		$open_brace =false;
		$nsemicolon=0;
		$ret = array();
		$prev = new vpl_token(vpl_token_type::identifier,'',0);
		foreach($tokens as $token){
			if($token->type == vpl_token_type::operator){
				$ret[]=$token;
			}
		}
		return $ret;
	}
	
	public function get_tokenizer(){
		return vpl_tokenizer_factory::get('prolog');
	}
}

?>