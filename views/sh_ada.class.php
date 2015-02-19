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
 * Syntaxhighlighter for ADA language
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/sh_base.class.php';

class vpl_sh_ada extends vpl_sh_base{
    var $previous_pending;
    protected function show_pending(&$rest){
        //TODO FIX hover highlight
        $lower=strtolower($rest);
        if(array_key_exists($lower , $this->reserved)){
/*            if($lower == 'else' || $lower == 'end'){
                $this->endHover();
            }*/
            $this->initTag(self::c_reserved);
            parent::show_pending($rest);
            echo self::endTag;
/*            if(($lower == 'begin' || $lower == 'loop' || $lower == 'else' || $lower == 'is') &&
               ($this->previous_pending != 'end')){
                $this->initHover();
            }*/
        }else{
            parent::show_pending($rest);
        }
        $this->previous_pending=$lower;
        $rest ='';
    }
    protected function is_begin_identifier($c){
        return ($c >= 'A' && $c <= 'Z') || ($c >= 'a' && $c <= 'z') || ($c >= '0' && $c <= '9') ||
        ($c == '_') || (ord($c) >= 128);
    }
    function __construct(){
        $this->reserved= array('abort' => 1, 'else' => 1, 'new' => 1, 'return' => 1
        , 'abs' => 1, 'elsif' => 1, 'not' => 1, 'reverse' => 1
        , 'abstract' => 1, 'end' => 1, 'null' => 1
        , 'accept' => 1, 'entry' => 1, 'select' => 1
        , 'access' => 1, 'exception' => 1, 'separate' => 1
        , 'aliased' => 1, 'exit' => 1, 'of' => 1, 'subtype' => 1
        , 'all' => 1, 'or' => 1
        , 'and' => 1, 'for' => 1, 'others' => 1, 'tagged' => 1
        , 'array' => 1, 'function' => 1, 'out' => 1, 'task' => 1
        , 'at' => 1, 'terminate' => 1, 'generic' => 1, 'package' => 1, 'then' => 1
        , 'begin' => 1, 'goto' => 1, 'pragma' => 1, 'type' => 1
        , 'body' => 1, 'private' => 1
        , 'if' => 1, 'procedure' => 1
        , 'case' => 1, 'in' => 1, 'protected' => 1, 'until' => 1
        , 'constant' => 1, 'is' => 1, 'use' => 1
        , 'raise' => 1
        , 'declare' => 1, 'range' => 1, 'when' => 1
        , 'delay' => 1, 'limited' => 1, 'record' => 1, 'while' => 1
        , 'delta' => 1, 'loop' => 1, 'rem' => 1, 'with' => 1
        , 'digits' => 1, 'renames' => 1
        , 'do' => 1, 'mod' => 1, 'requeue' => 1, 'xor' =>1);
        parent::__construct();
    }

    function print_file($filename, $filedata, $showln=true){
        $this->begin($filename, $showln);
        $normal=true;
        $in_char=false;
        $in_comment=false;
        $in_string=false;
        $pending='';
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
            if($current == self::CR){
                if($next == self::LF) {
                    continue;
                }else{
                    $current = self::LF;
                }
            }
            if(!$normal)
            {
                if($current != self::LF){
                $this->show_text($current);
                }
                if($in_comment) {
                    if($current==self::LF)
                    {
                        $this->endTag();
                        $in_comment=false;
                        $normal=true;
                    }
                }
                if($in_string){
                    if($current=='"'){
                        if($next=='"') {
                            $this->show_text($current);
                            $i++;
                        }
                        else
                        {
                            $this->endTag();
                            $in_string=false;
                            $normal=true;
                        }
                    }
                }
                if($current==self::LF){
                    $this->show_text($current);
                    $this->show_line_number();
                }
            }else{
                if($this->is_begin_identifier($current)){
                    $pending .= $current;
                }else{
                    if(strlen($pending)){
                        $this->show_pending($pending);
                    }
                    if($current == '-' && $next == '-'){
                        $in_comment=true;
                        $normal=false;
                        $this->initTag(self::c_comment);
                    }
                    if($current == '"')    {
                        $in_string=true;
                        $normal=false;
                        $this->initTag(self::c_string);
                    }
                    if($current == "'" && $i+2 <$l)    {
                        if($filedata[$i+2]=="'"){
                            $this->initTag(self::c_string);
                            $this->show_text($current.$next.$current);
                            $this->endTag();
                            $i += 2;
                        }
                        else{
                            $this->show_text($current);
                        }
                    }
                    else{
                        $this->show_text($current);
                    }
                    if($current == self::LF){
                        $this->show_line_number();
                    }
                }
            }
        }
        $this->show_pending($pending);
        if(!$normal){
            $this->endTag();
        }
        $this->end();
    }
}
