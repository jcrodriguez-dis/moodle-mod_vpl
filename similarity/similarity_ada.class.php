<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// VPL for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with VPL for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Ada language similarity class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
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
