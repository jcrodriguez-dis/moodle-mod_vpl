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
 * C language similarity class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
require_once dirname(__FILE__).'/similarity_base.class.php';

class vpl_similarity_c extends vpl_similarity_base{
    public function get_type(){
        return 1;
    }
    static public function expand_operator(&$array, &$from){
        $last = count($array)-1; //array alredy with equal =
        for($i=$from; $i < $last; $i++){ //Replicate from las instruction to =
            $array[]=$array[$i];
        }
        $from = count($array)+1;
    }
    public function sintax_normalize(&$tokens){
        $pos_ini_inst = 0;
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
                        $pos_ini_inst = count($ret);
                        $nsemicolon=0;
                        $open_brace =true;
                        break;
                    case '}':
                        //Remove unneeded {}
                        if(!($open_brace && $nsemicolon<2)){
                            $ret[]=$token;
                        }
                        $open_brace =false;
                        $pos_ini_inst = count($ret);
                        break;
                    case ';':
                        //count semicolon after a {
                        $nsemicolon++;
                        $ret[]=$token;
                        $pos_ini_inst = count($ret);
                        break;
                    case '++':
                        $token->value='=';
                        $ret[]=$token;
                        self::expand_operator($ret, $pos_ini_inst);
                        $token->value='+';
                        $ret[]=$token;
                        break;
                    case '--':
                        $token->value='=';
                        $ret[]=$token;
                        self::expand_operator($ret, $pos_ini_inst);
                        $token->value='-';
                        $ret[]=$token;
                        break;
                    case '+=':
                        $token->value='=';
                        $ret[]=$token;
                        self::expand_operator($ret, $pos_ini_inst);
                        $token->value='+';
                        $ret[]=$token;
                        break;
                    case '-=':
                        $token->value='=';
                        $ret[]=$token;
                        self::expand_operator($ret, $pos_ini_inst);
                        $token->value='-';
                        $ret[]=$token;
                        break;
                    case '*=':
                        $token->value='=';
                        $ret[]=$token;
                        self::expand_operator($ret, $pos_ini_inst);
                        $token->value='*';
                        $ret[]=$token;
                        break;
                    case '/=':
                        $token->value='=';
                        $ret[]=$token;
                        self::expand_operator($ret, $pos_ini_inst);
                        $token->value='/';
                        $ret[]=$token;
                        break;
                    case '%=':
                        $token->value='=';
                        $ret[]=$token;
                        self::expand_operator($ret, $pos_ini_inst);
                        $token->value='%';
                        $ret[]=$token;
                        break;
                    case '->': //Replace -> by *( ).
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
                    case ':':
                        $pos_ini_inst = count($ret);
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
        return vpl_tokenizer_factory::get('c');
    }
}
