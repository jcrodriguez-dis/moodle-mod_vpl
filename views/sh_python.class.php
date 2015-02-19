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
 * Syntaxhighlighter for python language
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/sh_base.class.php';

class vpl_sh_python extends vpl_sh_base{
    protected function show_pending(&$rest){
        if(array_key_exists($rest  , $this->reserved)){
            $this->initTag(self::c_reserved);
            parent::show_pending($rest);
            echo self::endTag;
        }elseif(strlen($rest)>0 && $rest[0] == '_'){
            $this->initTag(self::c_variable);
            parent::show_pending($rest);
            echo self::endTag;
        }else{
            parent::show_pending($rest);
        }
    }
    const regular=0;
    const in_identifier=1;
    const in_string=2;
    const in_decorator=3;
    const in_comment=4;
    const in_linecomment=5;
    function __construct(){
        $this->reserved= array("False" => true, "class" => true, "finally" => true, "is" => true, "return" => true,
                    "None" => true, "continue" => true, "for" => true, "lambda" => true, "try" => true,
                    "True" => true, "def" => true, "from" => true, "nonlocal" => true, "while" => true,
                    "and" => true, "del" => true, "global" => true, "not" => true, "with" => true,
                    "as" => true, "elif" => true, "if" => true, "or" => true, "yield" => true,
                    "assert" => true, "else" => true, "import" => true, "pass" => true,
                    "break" => true, "except" => true, "in" => true, "raise" => true);
        parent::__construct();
    }
    function show_line_number(){
        echo "\n";
        parent::show_line_number();
    }

    protected function isIdentifierChar($c) {
        return ($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z')
                || ($c >= '0' && $c <= '9') || ($c == '_') || ($c >= 128);
    }

    function print_file($filename, $filedata, $showln=true){
        $this->begin($filename,$showln);
        $state = self::regular;
        $pending='';
        $first_no_space = '';
        $last_no_space = '';
        $l = strlen($filedata);
        if($l){
            $this->show_line_number();
        }
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
                $last_no_space='';
                $first_no_space = '';
            }
            if($current == self::CR){
                if($next == self::LF) {
                    continue;
                }else{
                    $current = self::LF;
                }
            }
            if($current != ' ' && $current != "\t") {//Keep first and last char
                if($current != self::LF){
                    $last_no_space=$current;
                }
                if($first_no_space == ''){
                    $first_no_space = $current;
                }
            }
            switch($state){
            case self::regular:
            case self::in_identifier:
                if ($current == '#') {
                    $this->show_pending($pending);
                    $state=self::in_linecomment;
                    $pending=$current;
                    continue 2;
                } else if ($current == '"') {
                    $this->show_pending($pending);
                    if(substr($filedata,$i,3) == '"""'){
                        if($first_no_space=='"'){
                            $state=self::in_comment;
                            $pending = '"';
                            continue 2;
                        }
                        $stringLimit = '"""';
                    }else{
                        $stringLimit = '"';
                    }
                    $pending = $current;
                    $state=self::in_string;
                    $rawString = strtolower($previous)=='r';
                    continue 2;
                } else if ($current == '\'') {
                    $this->show_pending($pending);
                    $state=self::in_string;
                    $rawString = strtolower($previous)=='r';
                    if(substr($filedata,$i,3) == "'''"){
                        $stringLimit = "'''";
                    }else{
                        $stringLimit = "'";
                    }
                    $pending = $stringLimit;
                    $i += strlen($stringLimit)-1;
                    continue 2;
                }else if ($current == '@') {
                    $this->show_pending($pending);
                    $state=self::in_decorator;
                    $pending = $current;
                    continue 2;
                } else if ($this->isIdentifierChar($current)) {
                    if ($state==self::regular){
                        $this->show_text($pending);
                        $pending='';
                        $state=self::in_identifier;
                    }
                } else if($state==self::in_identifier){
                    $this->show_pending($pending);
                    $state=self::regular;
                }
                break;
            case self::in_comment:
                if (substr($filedata,$i,3) == '"""') {
                    $state= self::regular;
                    $this->initTag(self::c_comment);
                    $this->show_text($pending.'"""');
                    $pending='';
                    $this->endTag();
                    $i += 2;
                    continue 2;
                }else if ($current == self::LF){
                    $this->initTag(self::c_comment);
                    $this->show_text($pending);
                    $pending='';
                    $this->endTag();
                    $this->show_line_number();
                    continue 2;
                }
                break;
            case self::in_linecomment:
                if ($current == self::LF) {
                    $this->initTag(self::c_comment);
                    $this->show_text($pending);
                    $pending='';
                    $this->endTag();
                    $this->show_line_number();
                    $state= self::regular;
                    continue 2;
                }
                break;
            case self::in_string:
                if (substr($filedata,$i,strlen($stringLimit)) == $stringLimit){
                    if( $rawString || $previous != '\\') {
                        $state= self::regular;
                        $this->initTag(self::c_string);
                        $this->show_text($pending.$stringLimit);
                        $pending='';
                        $this->endTag();
                        //highlight(blockStart, i+stringLimit.length(),font.getString());
                        $i+=strlen($stringLimit)-1;
                        continue 2;
                    }
                }
                if ($current == self::LF){
                    $this->initTag(self::c_string);
                    $this->show_text($pending);
                    $pending='';
                    $this->endTag();
                    $this->show_line_number();
                    continue 2;
                }
                $pending .= $current;
                if($previous=='\\'){
                    $current ='\0';
                }
                continue 2;
            case self::in_decorator:
                if (! $this->isIdentifierChar($next) && $next != '.' && $next != ' '){
                    $state= self::regular;
                    $this->initTag(self::c_macro);
                    $this->show_text($pending);
                    $this->endTag();
                    if($current == self::LF){
                        $this->show_line_number();
                        $pending='';
                    }else{
                        $pending=$current;
                    }
                    continue 2;
                }
                break;
            }
            if ($current == self::LF){
                $this->show_pending($pending);
                $this->show_line_number();
            }
            else $pending .=$current;
        }

        $this->show_pending($pending);
        if($state != self::regular){
            $this->endTag();
        }
        $this->end();
    }
}
