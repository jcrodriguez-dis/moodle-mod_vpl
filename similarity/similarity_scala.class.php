<?php
/**
 * @version		$Id: similarity_scala.class.php,v 1.2 2013-06-11 18:31:10 juanca Exp $
 * @package		VPL. Scala language similarity class
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Lang Michael <michael.lang.ima10@fh-joanneum.at>
 * @author		Lückl Bernd <bernd.lueckl.ima10@fh-joanneum.at>
 * @author		Lang Johannes <johannes.lang.ima10@fh-joanneum.at>
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
require_once dirname(__FILE__).'/similarity_base.class.php';

class vpl_similarity_scala extends vpl_similarity_base{
	public function get_type(){
		return 7;
	}
	public function sintax_normalize(&$tokens){
		$open_brace =false;
		$nsemicolon=0;
		$ret = array();
		$prev = new vpl_token(vpl_token_type::identifier,'',0);
		foreach($tokens as $token){
			if($token->type == vpl_token_type::operator){
				//++ and -- operator
				//:: operator
				//(*p). and p->
				//+=, -=, *=, etc
				switch($token->value){
					case '[':
						//only add ]
						break;
					case '(':
						//only add )
						break;
					case '{':
						//only add }
						$nsemicolon=0;
						$open_brace =true;
						break;
					case '}':
						//Remove unneeded {}
						if(!($open_brace && $nsemicolon<2)){
							$ret[]=$token;
						}
						$open_brace =false;
						break;
					case ';':
						//count semicolon after a {
						$nsemicolon++;
						$ret[]=$token;
						break;
					case '++':
						$token->value='=';
						$ret[]=$token;
						$token->value='+';
						$ret[]=$token;
						break;
					case '--':
						$token->value='=';
						$ret[]=$token;
						$token->value='-';
						$ret[]=$token;
						break;
					case '+=':
						$token->value='=';
						$ret[]=$token;
						$token->value='+';
						$ret[]=$token;
						break;
					case '-=':
						$token->value='=';
						$ret[]=$token;
						$token->value='-';
						$ret[]=$token;
						break;
					case '*=':
						$token->value='=';
						$ret[]=$token;
						$token->value='*';
						$ret[]=$token;
						break;
					case '/=':
						$token->value='=';
						$ret[]=$token;
						$token->value='/';
						$ret[]=$token;
						break;
					case '%=':
						$token->value='=';
						$ret[]=$token;
						$token->value='%';
						$ret[]=$token;
						break;
					case '->':
						if($prev->value == 'this'){
							break;
						}
						$token->value='(';
						$ret[]=$token;
						$token->value='*';
						$ret[]=$token;
						$token->value=')';
						$ret[]=$token;
						$token->value='.';
						$ret[]=$token;
						break;
					case '::':
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
		return vpl_tokenizer_factory::get('scala');
	}
}

?>