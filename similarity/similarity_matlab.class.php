<?php
/**
 * @version		$Id: similarity_matlab.class.php,v 1.2 2013-06-11 18:28:29 juanca Exp $
 * @package		VPL. C language similarity class
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
require_once dirname(__FILE__).'/similarity_base.class.php';

class vpl_similarity_matlab extends vpl_similarity_base{
	public function get_type(){
		return 9;
	}

	public function sintax_normalize(&$tokens){
		$pos_ini_inst = 0;
		$open_brace =false;
		$nsemicolon=0;
		$ret = array();
		$prev = new vpl_token(vpl_token_type::identifier,'',0);
		foreach($tokens as $token){
			if($token->type == vpl_token_type::operator){
				switch($token->value){
					case '[':
						//only add ]
						break;
					case '(':
						//only add )
						break;
					case '{':
						break;
					case '<': //Replace < by >.
						$token->value='>';
						$ret[]=$token;
						break;
					case '<=': //Replace < by >.
						$token->value='>=';
						$ret[]=$token;
						break;
					default:
						$ret[]=$token;
				}
				$prev=$token;
			}
			//TODO remove (p)
		}
		return $ret;
	}

	public function get_tokenizer(){
		return vpl_tokenizer_factory::get('matlab');
	}
}

?>