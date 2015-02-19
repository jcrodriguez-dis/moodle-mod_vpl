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
 * Scheme programing language tokenizer class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/tokenizer_base.class.php';

class vpl_tokenizer_scheme extends vpl_tokenizer_base{
    protected $reserved=null;
    protected $line_number;
    protected $tokens;
    function __construct(){
        parent::__construct();
        //TODO need more reserved and functions
        $list = array('define', 'if', 'cond', 'else',
                                 'let', 'eq?', 'eqv?', 'equal?',
                                 'and', 'or', 'letrec', 'let-syntax',
                                 'letrec-sintax', 'begin', 'do',
                                'quote', '+', '-', '*', '/',
                                 'sqrt', 'eval', 'car', 'cdr', 'list',
                                 'cons', 'null?', 'list?', '=', '<>',
                                 '<=', '>=', '<', '>', 'lambda',
                                 'not');
        $this->reserved= array();
        foreach ($list as $word) {
            $this->reserved[$word]=1;
        }
    }
    function is_previous_open_parenthesis(& $string, $pos){
        for( ;$pos >= 0;$pos--){
            $char = $string[$pos];
            if($char=='('){
                return true;
            }
            if($char != ' ' && $char != self::TAB && $char != self::LF && $char != self::CR){
                return false;
            }
        }
        return false;
    }
    protected function is_indentifier($text){
        if(strlen($text)==0){
            return false;
        }
        $first=$text{0};
        return ($first >= 'a' && $first <= 'z') ||
                    ($first >= 'A' && $first <= 'Z') ||
                    $first=='_';
    }
    protected function is_number($text){
        if(strlen($text)==0){
            return false;
        }
        $first=$text{0};
        return $first >= '0' && $first <= '9';
    }
    protected function add_parenthesis(){
        $this->tokens[] = new vpl_token(vpl_token_type::operator,'(',$this->line_number);
    }

    protected function add_parameter_pending(&$pending){
        if($pending <= ' '){
            $pending = '';
            return;
        }
        $this->tokens[] = new vpl_token(vpl_token_type::literal,$pending,$this->line_number);
        $pending='';
    }

    protected function add_function_pending(&$pending){
        if($pending <= ' '){
            $pending = '';
            return;
        }
        if(isset($this->reserved[$pending])){
            $type=vpl_token_type::operator;
        }else{
            $type=vpl_token_type::identifier;
        }
        $this->tokens[] = new vpl_token($type,$pending,$this->line_number);
        $pending='';
    }
    const in_regular=0;
    const in_string=1;
    const in_char=2;
    const in_comment=4;

    function parse($filedata){
        $this->tokens=array();
        $this->line_number=1;
        $state = self::in_regular;
        $pending='';
        $previous_is_open_parenthesis = false;
        $l = strlen($filedata);
        $current='';
        $pospendig=0;
        for($i=0;$i<$l;$i++){
            $previous=$current;
            $current=$filedata[$i];
            if($i < ($l-1)) {
                $next = $filedata[$i+1];
            }else{
                $next ='';
            }
            if($current == self::CR){
                if($next == self::LF) {
                    continue;
                }else{
                    $current = self::LF;
                }
            }
            switch($state){

                case self::in_comment:{
                    if($current==self::LF) {
                        $state = self::in_regular;
                    }
                    break;
                }
                case self::in_string:{
                    if($current=='"' && $previous!="\\") {
                        $state = self::in_regular;
                    }
                    break;
                }
                case self::in_char:{
                    if(! ctype_alpha($current) && $current!='-') {
                        $state = self::in_regular;
                        $i--;
                        continue; //Reprocess current char
                    }
                    break;
                }
                case self::in_regular:{
                    if(($current != ' ') && ($current != '(')&& ($current!=')')
                    && ($current != ';')&& ($current != '"') && ($current!=self::LF) && ($current!=self::TAB)) {
                        if($pending == ''){
                            $pospendig=$i;
                        }
                        $pending .= $current;
                    }else{
                        if(strlen($pending)){
                            if($this->is_previous_open_parenthesis($filedata, $pospendig-1)){
                                $this->add_function_pending($pending);
                            }else{
                                $this->add_parameter_pending($pending);
                            }
                        }
                        if($current == '('){
                            $this->add_parenthesis();
                        }
                        if($current == ';'){
                            $state = self::in_comment;
                        } elseif($current == '"')    {
                            $state = self::in_string;
                        } elseif($current == '#' && $next =='\\') {
                            $state = self::in_char;
                        }
                    }
                    break;
                }
            }
        }
    }
    function get_tokens(){
        return $this->tokens;
    }
}
