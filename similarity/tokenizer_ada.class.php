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
 * ADA programing language tokenizer class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/tokenizer_base.class.php';

class vpl_tokenizer_ada extends vpl_tokenizer_base{
    const regular=0;
    const in_string=1;
    const in_linecomment=2;
    const in_number=3;
    protected static $ada_reserved=null;
    protected static $operators;
    protected $line_number;
    protected $tokens;
    protected function is_number($text){
        if(strlen($text)==0){
            return false;
        }
        $first=$text{0};
        return $first >= '0' && $first <= '9';
    }

    protected function add_pending(&$rawpending){
        $pending = strtolower($rawpending);
        if(isset(self::$operators[$pending])){
            $type=vpl_token_type::operator;
        }elseif(isset($this->reserved[$pending])){
            $type=vpl_token_type::reserved;
        }elseif($this->is_number($pending)){
            $type=vpl_token_type::literal;
        }else{
            $type=vpl_token_type::identifier;
        }
        $this->tokens[] = new vpl_token($type,$pending,$this->line_number);
        $rawpending='';
    }
    function __construct(){
        if(self::$ada_reserved === null){
            self::$ada_reserved= array('abort' => true, 'else' => true, 'new' => true, 'return' => true,
                'abs' => true, 'elsif' => true, 'not' => true, 'reverse' => true,
                'abstract' => true, 'end' => true, 'null' => true,
                'accept' => true, 'entry' => true,  'select' => true,
                'access' => true, 'exception' => true, 'of' => true, 'separate' => true,
                'aliased' => true, 'exit' => true, 'or' => true, 'subtype' => true,
                'all' => true,  'others' => true, 'synchronized' => true,
                'and' => true, 'for' => true, 'out' => true,
                'array' => true, 'function' => true, 'overriding' => true, 'tagged' => true,
                'at' => true,   'task' => true,
                'generic' => true, 'package' => true, 'terminate' => true,
                'begin' => true, 'goto' => true, 'pragma' => true, 'then' => true,
                'body' => true,  'private' => true, 'type' => true,
                'if' => true, 'procedure' => true,
                'case' => true, 'in' => true, 'protected' => true, 'until' => true,
                'constant' => true, 'interface' => true, 'use' => true,
                'is' => true, 'raise' => true,
                'declare' => true,  'range' => true, 'when' => true,
                'delay' => true, 'limited' => true, 'record' => true, 'while' => true,
                'delta' => true, 'loop' => true, 'rem' => true, 'with' => true,
                'digits' => true,  'renames' => true,
                'do' => true, 'mod' => true, 'requeue' => true, 'xor' => true);
            self::$operators= array('abs' => true, 'not' => true, 'in' => true,
                'or' => true, 'and' => true, 'rem' => true, 'mod' => true, 'xor' => true,
                '&' => true, '\'' => true, '(' => true, ')' => true, '*' => true,
                '+' => true, ',' => true, '–' => true, '.' => true, '/' => true,
                ':' => true, ';' => true, '<' => true, '=' => true, '>' => true, '|' => true,
                '=>' => true, '..' => true, '**' => true, ':=' => true, '/=' => true,
                '>=' => true, '<=' => true, '<<' => true, '>>' => true, '<>' => true);
        }
        $this->reserved = &self::$ada_reserved;
        parent::__construct();
    }


    function parse($filedata){
        $this->tokens=array();
        $this->line_number=1;
        $state = self::regular;
        $pending='';
        $l = strlen($filedata);
        $current='';
        $previous='';
        for($i=0;$i<$l;$i++){
            $previous=$current;
            $current=$filedata[$i];
            if($i < ($l-1)) {
                $next = $filedata[$i+1];
            }else{
                $next ='';
            }
            if($previous == self::LF){
                $this->line_number++;
            }
            if($current == self::CR){
                if($next == self::LF) {
                    continue;
                }else{
                    $this->line_number++;
                    $current = self::LF;
                }
            }
            switch($state){
                case self::in_linecomment:
                    // Check end of comment
                    if($current==self::LF){
                        $state=self::regular;
                    }
                    break;
                case self::in_string:
                    // Check end of string
                    if($current=='"'){
                        if($next!='"') {
                            $state = self::regular;
                            break;
                        }else{
                            $i++;
                            $current=' ';
                            break;
                        }
                    }
                    break;
                case self::in_number:
                    if(($current >= '0' && $current <= '9') ||
                    $current == '.' || $current == 'e' || $current == 'e'){
                        $pending .= $current;
                        continue;
                    }
                    if(($current == '-' || $current == '+') && ($previous == 'E' || $previous == 'e')){
                        $pending .= $current;
                        continue;
                    }
                    $this->add_pending($pending);
                    $state = self::regular;
                    //Process current as regular
                case self::regular:
                    if(strpos(" \n\r\t\v\f",$current)!== false){ //A separator
                        $this->add_pending($pending);
                        break;
                    }elseif($current == '-') {
                        if($next == '-'){ // Begin line comment
                            $state = self::in_linecomment;
                            $this->add_pending($pending);
                            $i++;
                            continue;
                        }
                    }elseif($current == '"')    {
                        $state = self::in_string;
                        $this->add_pending($pending);
                        break;
                    }elseif($current == "'"){
                        $this->add_pending($pending);
                        if($i < ($l-2) && $filedata[$i+2]=== "'"){ //Char literal coding problem
                            $i+=2;
                            break;
                        } //Not char literal then operator
                    }elseif(strpos("&'()*+,–./:;<=>|",$current)!== false){ //A delimiter
                        $this->add_pending($pending);
                        $this->add_pending($current);
                        break;
                    }
                    elseif($current >= '0' && $current <= '9'){ //Start of number
                        $state = self::in_number;
                        $this->add_pending($pending);
                        $pending .= $current;
                        break;
                    }
                    $this->add_pending($pending);
            }
        }
        $this->add_pending($pending);
        $this->compact_operators();
    }
    function get_tokens(){
        return $this->tokens;
    }
    function compact_operators(){
        $correct = array();
        $current = false;
        foreach($this->tokens as &$next){
            if($current){
                if($current->type == vpl_token_type::operator
                && $next->type == vpl_token_type::operator
                && isset($this->operators[$current->value.$next->value])){
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
    function show_tokens(){
        foreach($this->tokens as $token){
            $token->show();
        }
    }
}
