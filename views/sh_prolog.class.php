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
 * Syntaxhighlighter for Prolog language
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/sh_base.class.php';

class vpl_sh_prolog extends vpl_sh_base{
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

    protected function show_pending(&$rest, &$s = null, $i=null){
        if(strlen($rest) == 0){
            return;
        }
        $c = $rest[0];
        if($this->isIdentifierChar($c)){
            $needEnd = true;
            if(($c >= 'A' && $c <= 'Z') || $c == '_'){
                $this->initTag(self::c_variable);
            }elseif(($c >= 'a' && $c <= 'z') ){
                if($s != null && $this->isNextOpenParenthesis($s,$i) || $rest == 'is'){
                    $this->initTag(self::c_reserved);
                }else{
                    $this->initTag(self::c_macro);
                }
            }else{
                $needEnd=false;
            }
            parent::show_pending($rest);
            if($needEnd){
                echo self::endTag;
            }
        }else{
            parent::show_pending($rest);
        }
    }
    const in_regular=0;
    const in_string=1;
    const in_char=2;
    const in_macro=3;
    const in_comment=4;
    const in_linecomment=5;
    const in_identifier=6;

    function show_line_number(){
        echo "\n";
        parent::show_line_number();
    }


    function print_file($filename, $filedata, $showln=true){
        $this->begin($filename,$showln);
        $state = self::in_regular;
        $pending='';
        $l = strlen($filedata);
        if($l){
            $this->show_line_number();
        }
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
                            $this->show_pending($pending,$filedata,$i);
                            $this->initTag(self::c_comment);
                            $this->show_text('/*');
                            $i++;
                            continue 2;
                        }
                        break;
                    }elseif($current == '%'){ // Begin line comment
                        $this->show_pending($pending,$filedata,$i);
                        $state = self::in_linecomment;
                        $this->initTag(self::c_comment);
                        break;
                    }elseif($current == '"')    {
                        $this->show_pending($pending,$filedata,$i);
                        $state = self::in_string;
                        $this->initTag(self::c_string);
                        break;
                    }elseif($current == "'"){
                        $this->show_pending($pending,$filedata,$i);
                        $state = self::in_char;
                        $this->initTag(self::c_string);
                        break;
                    } elseif ($this->isIdentifierChar($current)) {
                        if ($state==self::in_regular){
                            $this->show_pending($pending,$filedata,$i);
                            $state = self::in_identifier;
                        }
                    } else {
                        if ($state==self::in_identifier){
                            $this->show_pending($pending,$filedata,$i);
                            $state = self::in_regular;
                        }
                        if($current == self::LF){
                            $this->show_pending($pending,$filedata,$i);
                            $this->show_line_number();
                            continue 2;
                        }
                    }
                    break;
                case self::in_comment:
                    // Check end of block comment
                    if($current=='*') {
                        if($next=='/') {
                            $state = self::in_regular;
                            $pending .= '*/';
                            $this->show_text($pending);
                            $pending='';
                            $this->endTag();
                            $i++;
                            continue 2;
                        }
                    }
                    if($current == self::LF){
                        $this->show_text($pending);
                        $pending='';
                        $this->endTag();
                        $this->show_line_number();
                        $this->initTag(self::c_comment);
                        continue 2;
                    }
                    break;
                case self::in_linecomment:
                    // Check end of comment
                    if($current==self::LF){
                        $this->show_text($pending);
                        $pending='';
                        $this->endTag();
                        $this->show_line_number();
                        $state=self::in_regular;
                        continue 2;
                    }
                    break;
                case self::in_string:
                case self::in_char:
                    // Check end of string
                    if($state == self::in_string && $current=='"') {
                        $pending .= $current;
                        $this->show_text($pending);
                        $pending='';
                        $this->endTag();
                        $state = self::in_regular;
                        continue 2;
                    }
                    // Check end of char
                    if($state == self::in_char && $current=='\'') {
                        $pending .= $current;
                        $this->show_text($pending);
                        $pending='';
                        $this->endTag();
                        $state = self::in_regular;
                        continue 2;
                    }
                    if($current==self::LF){
                        $this->show_text($pending);
                        $pending='';
                        $this->endTag();
                        $this->show_line_number();
                        $this->initTag(self::c_string);
                        continue 2;
                    }
                    //discard two backslash
                    if($current=='\\'){
                        $pending .= $current.$next;
                        $i++; //Skip next char
                        continue 2;
                    }
                    break;
            }
            $pending .= $current;
        }

        $this->show_pending($pending);
        if($state != self::in_regular && $state!=self::in_identifier){
            $this->endTag();
        }
        $this->end();
    }
}

