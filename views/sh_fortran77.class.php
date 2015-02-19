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
 * Syntaxhighlighter for Fortran77 language
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/sh_base.class.php';

class vpl_sh_fortran77 extends vpl_sh_base{
    var $previous_pending;
    protected function show_pending(&$rest){
        $lower=strtolower($rest);
        if(array_key_exists($lower , $this->reserved)){
            $this->initTag(self::c_reserved);
            parent::show_pending($rest);
            echo self::endTag;
        }else{
            parent::show_pending($rest);
        }
        $this->previous_pending=$lower;
        $rest ='';
    }
    protected function is_begin_identifier($c){
        return ($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z')
        || ($c >= '0' && $c <= '9') || ($c == '$') || ($c == '_');
    }
    function __construct(){
        $this->reserved= array('accept' => 1, 'assign' => 1,    'backspace' => 1,
                    'call' => 1,    'close' => 1, 'continue' => 1,
                    'decode' => 1, 'do' => 1,    'dowhile' => 1,
                    'else' => 1, 'elseif' => 1, 'encode' => 1,
                    'enddo' => 1, 'endfile' => 1,    'endif' => 1,
                    'goto' => 1,    'if' => 1, 'include' => 1,
                    'inquire' => 1, 'open' => 1, 'pause' => 1,
                    'print' => 1, 'return' => 1, 'rewind' => 1,
                    'save' => 1, 'static' => 1, 'stop' => 1,
                    'write' => 1,
                    //From here declarators
                    'automatic' => 1, 'blockdata' => 1, 'byte' => 1, 'character' => 1,
                    'common' => 1, 'complex' => 1, 'data' => 1, 'dimension' => 1, 'doublecomplex' => 1,
                    'doubleprecision' => 1, 'end' => 1, 'endmap' => 1, 'endstructure' => 1,    'endunion' => 1,
                    'equivalence' => 1, 'external' => 1, 'format' => 1, 'function' => 1,
                    'implicit' => 1,    'integer' => 1, 'intrinsic' => 1, 'logical' => 1,
                    'map' => 1, 'namelist' => 1, 'options' => 1, 'parameter' => 1,
                    'pointer' => 1, 'pragma' => 1, 'program' => 1, 'real' => 1,
                    'record' => 1, 'static' => 1, 'structure' => 1, 'subroutine' => 1,
                    'type' => 1, 'union' => 1, 'virtual' => 1, 'volatile' => 1
        );
        parent::__construct();
    }
    const in_regular = 0;
    const in_string = 1;
    const in_cstring = 2;
    const in_dstring = 3;
    const in_linecomment = 4;
    const in_identifier = 5;
    function print_file($filename, $filedata, $showln=true){
        $this->begin($filename,$showln);
        $state=self::in_regular;
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
                    if ($current == '#') {
                        $this->show_pending($pending);
                        $state = self::in_linecomment;
                        $this->initTag(self::c_comment);
                    }  elseif ($current == '\'') {
                        $this->show_pending($pending);
                        $this->initTag(self::c_string);
                        $state=self::in_string;
                    } elseif ($current == '"') {
                        $this->show_pending($pending);
                        $state=self::in_dstring;
                        $this->initTag(self::c_string);
                    } elseif ($current == '$' && $next == '\'') {
                        $this->show_pending($pending);
                        $pending = '$\'';
                        $i++;
                        $this->initTag(self::c_string);
                        $state=self::in_cstring;
                        continue 2;
                    } elseif ($this->is_begin_identifier($current)) {
                        if ($state==self::in_regular){
                            $this->show_pending($pending);
                            $state = self::in_identifier;
                        }
                    } elseif($state==self::in_identifier){
                        $this->show_pending($pending);
                        $state=self::in_regular;
                    }
                    break;
                case self::in_linecomment:
                    if ($current == self::LF){
                        $this->show_pending($pending);
                        echo "\n";
                        $this->endTag();
                        $this->show_line_number();
                        $state=self::in_regular;
                        continue 2;
                    }
                case self::in_string:
                    if ($current == '\'') {
                        $pending .= '\'';
                        $this->show_pending($pending);
                        $this->endTag();
                        $state = self::in_regular;
                        continue 2;
                    }
                break;
                case self::in_cstring:
                    if ($current == '\'') {
                        $pending .= '\'';
                        $this->show_pending($pending);
                        $this->endTag();
                        $state = self::in_regular;
                        continue 2;
                    }
                    if ($current == '\\') { //Jump next
                        $pending .= '\\'.$next;
                        $i++;
                        continue 2;
                    }
                break;
                case self::in_dstring:
                    if ($current == '"') {
                        $pending .= '"';
                        $this->show_pending($pending);
                        $this->endTag();
                        $state = self::in_regular;
                        continue 2;
                    }
                    if ($current == '\\') { //Jump next
                        $pending .= '\\'.$next;
                        $i++;
                        continue 2;
                    }
                break;
            }
            $pending .= $current;
            if($current == self::LF){
                $this->show_pending($pending);
                $this->show_line_number();
            }
        }

        $this->show_pending($pending);
        if($state != self::in_regular){
            $this->endTag();
        }
        $this->end();
    }
}
