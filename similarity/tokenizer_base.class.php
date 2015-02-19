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
 * Programing language tokenizer base class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
class vpl_token_type{
    const reserved=1;
    const identifier=2;
    const operator=3;
    const literal=4;
    const other=5;
}

class vpl_token{
    public $type;
    public $value;
    //public $line;
    private static $hash_values=array();
    private static function get_hash($value){
        if(!isset($hash_values[$value])){
            $hash_values[$value]=mt_rand();
        }
        return $hash_values[$value];
    }
    public function __construct($type,$value,$line){
        $this->type=$type;
        $this->value=$value;
        //$this->line = $line;
    }
    public function hash(){
        return self::get_hash($this->value);
    }
    public function show(){
        echo /*$this->line.' '.*/$this->type.' '.$this->value.'<br />';
    }
}
class vpl_tokenizer_base{
    const CR ="\r";
    const LF ="\n";
    const TAB ="\t";
    public function __construct(){
    }
}
