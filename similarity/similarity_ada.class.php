<?php
/**
 * @version		$Id: similarity_ada.class.php,v 1.4 2013-06-11 18:28:29 juanca Exp $
 * @package		VPL. Ada language similarity class
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
require_once dirname(__FILE__).'/similarity_base.class.php';

class vpl_similarity_ada extends vpl_similarity_base{
	public function get_type(){
		return 4;
	}
	public function sintax_normalize(&$tokens){
		$identifier_list = false;
		$n_identifiers = 0;
		$identifier_def_pos = 0; 
		$bracket_level = 0;
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
						$bracket_level++;
						break;
					case '{':
						//only add }
						break;
					case ')':
						$bracket_level--;
						$ret[]=$token;
						break;
					case ';':
						$ret[]=$token;
						//End of identifier list declaration?
						if($identifier_list){
							if($identifier_def_pos>0){
								$rep = array_slice($ret,$identifier_def_pos);
								for($i=0; $i<$n_identifiers ; $i++){
									foreach($rep as $data){
										$ret[] = $data;
									}
								}
							}else{
								for($i=0; $i<$n_identifiers ; $i++){
									$ret[] = $token;
								}
							}
						}
						$identifier_list = false;
						break;
					case ',':
						//Posible identifier list
						if($bracket_level == 0){
							if($identifier_list){
							$identifier_list = true;
							$identifier_def_pos = 0; 
							$n_identifiers = 1;
							}else{
								$n_identifiers++;
							}
						}else{
							$ret[]=$token;
						}
						break;
					case ':':
						if($identifier_list){
							$identifier_def_pos = count($ret);
						}
						$ret[] = $token;
						break;
					default:
						$ret[]=$token;
				}
				$prev=$token;
			}
		}
		return $ret;
	}
		public function get_tokenizer(){
		return vpl_tokenizer_factory::get('ada');
	}
}

?>