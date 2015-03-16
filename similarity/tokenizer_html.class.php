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
 * HTML language tokenizer class
 *
 * @package mod_vpl
 * @copyright 2015 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/tokenizer_base.class.php';

//TODO Implement script parse
class vpl_tokenizer_html extends vpl_tokenizer_base{
    const regular=0;
    const in_string=1;
    const in_comment=2;
    const in_tagname=3;
    const in_tagend=4;
    const in_tagattrname=5;
    const in_tagattrvalue=6;

    protected $line_number;
    protected $tokens;

    protected function add_pending(&$rawpending,$state){
        $pending = strtolower($rawpending);
        $rawpending='';
        if($state == self::in_tagattrvalue){
            return;
        }
        if($state == self::in_tagend){
            $pending.='/';
        }
        $this->tokens[] = new vpl_token(vpl_token_type::operator,$pending,$this->line_number);
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
                case self::in_comment:
                    // Check end of comment
                    if($current=='>' && $i>6 && substr($filedata,$i-2,2) == '--'){
                        $state=self::regular;
                    }
                    break;
                case self::in_string:
                    // Check end of string
                    if($current==$endString){
                        $state = self::in_tagattrname;
                    }
                    break;
                case self::regular:
                    if($current=='<'){
                        if($next == '!' && $i+3< $l && substr($filedata,$i+2,2)=='--'){
                            $state = self::in_comment;
                            $i+=3;
                        }else{
                            $state = self::in_tagname;
                        }
                    }
                    break;
                    case self::in_tagend:
                    case self::in_tagname:
                    if($current=='/'){
                        $state = self::in_tagend;
                        break;
                    }
                    if(ctype_alpha($current)){
                        $pending .= $current;
                    }elseif($pending>''){
                        $this->add_pending($pending,$state);
                        $state = self::in_tagattrname;
                        $i--;
                    }
                    break;
                case self::in_tagattrname:
                case self::in_tagattrvalue:
                    if(ctype_alnum($current) || strpos('-_$',$current) !== false){
                        $pending .= $current;
                    }elseif($pending>''){
                        $this->add_pending($pending,$state);
                        $state = self::in_tagattrname;
                        $i--;
                    }
                    if($current=='"' || $current == "'"){
                        $state = self::in_string;
                        $endString = $current;
                    }
                    if($current == '='){
                        $state = self::in_tagattrvalue;
                    }
                    if($current == '>'){
                        $state = self::regular;
                    }
                    break;
            }
        }
    }
    function get_tokens(){
        return $this->tokens;
    }
    function show_tokens(){
        foreach($this->tokens as $token){
            $token->show();
        }
    }
}
