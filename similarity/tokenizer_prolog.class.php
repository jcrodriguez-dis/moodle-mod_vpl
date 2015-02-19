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
 * Prolog programing language tokenizer class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/tokenizer_base.class.php';

class vpl_tokenizer_prolog extends vpl_tokenizer_base{
    protected $reserved=null;
    protected $line_number;
    protected $tokens;
    protected function isNextOpenParenthesis(& $s, $ini){
        $l = strlen($s);
        for($i=$ini;$i< $l; $i++){
            $c=$s[$i];
            if($c=='(') {
                return true;
            }
            if($c !=' ' && $c != self::CR && $c != self::LF && $c != '\t') {
                return false;
            }
        }
        return false;
    }

    protected function isIdentifierChar($c) {
        return ($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z')
                || ($c >= '0' && $c <= '9') || ($c == '_');
    }

    protected function add_pending(&$rest, &$s = null, $i=null){
        $rest=trim($rest);
        if(strlen($rest) == 0){
            return;
        }
        $c = $rest[0];
        if($this->isIdentifierChar($c)){
            if(($c >= 'A' && $c <= 'Z') || $c == '_'){//Variable
                $this->tokens[] = new vpl_token(vpl_token_type::operator,'V',$this->line_number);
            }elseif(($c >= 'a' && $c <= 'z') ){ //Literal
                if($s != null && $this->isNextOpenParenthesis($s,$i) || $rest == 'is'){
                    $this->tokens[] = new vpl_token(vpl_token_type::operator,'L',$this->line_number);
                }
            }
        }else{
            $this->tokens[] = new vpl_token(vpl_token_type::operator,$rest,$this->line_number);
        }
        $rest='';
    }
    const in_regular=0;
    const in_string=1;
    const in_char=2;
    const in_macro=3;
    const in_comment=4;
    const in_linecomment=5;
    const in_identifier=6;

    function parse($filedata){
        $this->tokens=array();
        $this->line_number=1;
        $state = self::in_regular;
        $pending='';
        $l = strlen($filedata);
        $current='';
        for($i=0;$i<$l;$i++){
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
                case self::in_regular:
                case self::in_identifier:
                    if($current == '/') {
                        if($next == '*') { // Begin block comments
                            $state = self::in_comment;
                            $this->add_pending($pending,$filedata,$i);
                            $i++;
                            continue 2;
                        }
                        break;
                    }elseif($current == '%'){ // Begin line comment
                        $this->add_pending($pending,$filedata,$i);
                        $state = self::in_linecomment;
                        break;
                    }elseif($current == '"')    {
                        $this->add_pending($pending,$filedata,$i);
                        $state = self::in_string;
                        break;
                    }elseif($current == "'"){
                        $this->add_pending($pending,$filedata,$i);
                        $state = self::in_char;
                        break;
                    } elseif ($this->isIdentifierChar($current)) {
                        if ($state==self::in_regular){
                            $this->add_pending($pending,$filedata,$i);
                            $state = self::in_identifier;
                        }
                    } else {
                        $this->add_pending($pending,$filedata,$i);
                        if ($state==self::in_identifier){
                            $state = self::in_regular;
                        }
                        if($current == self::LF){
                            continue 2;
                        }
                    }
                    break;
                case self::in_comment:
                    // Check end of block comment
                    if($current=='*') {
                        if($next=='/') {
                            $state = self::in_regular;
                            $pending = '';
                            $i++;
                            continue 2;
                        }
                    }
                    if($current == self::LF){
                        continue 2;
                    }
                    break;
                case self::in_linecomment:
                    // Check end of comment
                    if($current==self::LF){
                        $pending='';
                        $state=self::in_regular;
                        continue 2;
                    }
                    break;
                case self::in_string:
                case self::in_char:
                    // Check end of string
                    if($state == self::in_string && $current=='"') {
                        $pending='';
                        $state = self::in_regular;
                        continue 2;
                    }
                    // Check end of char
                    if($state == self::in_char && $current=='\'') {
                        $pending='';
                        $state = self::in_regular;
                        continue 2;
                    }
                    if($current==self::LF){
                        $pending='';
                        continue 2;
                    }
                    //discard two backslash
                    if($current=='\\'){
                        $i++; //Skip next char
                        continue 2;
                    }
                    break;
            }
            $pending .= $current;
        }
        $this->compact_operators();
    }
    function compact_operators(){
        $correct = array();
        $current = false;
        foreach($this->tokens as &$next){
            if($current){
                if($current->type == vpl_token_type::operator
                        && $next->type == vpl_token_type::operator
                        && strpos('()[]{},.;',$current->value) === false){
                    $current->value .= $next->value;
                    $next=false;
                }
                $correct[] = $current;
            }
            $current = $next;
        }
        if($current){
            $correct[] = $current;
        }
        $this->tokens = $correct;
    }

    function get_tokens(){
        return $this->tokens;
    }
}
