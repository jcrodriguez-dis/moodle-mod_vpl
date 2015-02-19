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
 * Syntaxhighlighters object factory class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

class vpl_sh_factory{
    static $base =null;
    static $c = null;
    static $cpp = null;
    static $java = null;
    static $scheme = null;
    static $ada = null;
    static $sql = null;
    static $sh = null;
    static $pascal = null;
    static $fortran77 = null;
    static $prolog = null;
    static $matlab = null;
    static $python = null;
    static $scala = null;
    static function get_object(&$ref, $type){
        if($ref == null){
            require_once dirname(__FILE__).'/sh_'.$type.'.class.php';
            $class = 'vpl_sh_'.$type;
            $ref = new $class();
        }
        return $ref;
    }

    static function get_sh($filename){
        $ext = pathinfo($filename,PATHINFO_EXTENSION);
        if($ext == 'c'){
            return self::get_object(self::$c,'c');
        }elseif($ext == 'cpp' || $ext == 'h'){
            return self::get_object(self::$cpp,'cpp');
        }elseif($ext == 'java'){
            return self::get_object(self::$java,'java');
        }elseif($ext == 'ada' || $ext == 'adb' || $ext == 'ads'){
            return self::get_object(self::$ada,'ada');
        }elseif($ext == 'sql'){
            return self::get_object(self::$sql,'sql');
        }elseif($ext == 'scm'){
            return self::get_object(self::$scheme,'scheme');
        }elseif($ext == 'sh'){
            return self::get_object(self::$sh,'sh');
        }elseif($ext == 'pas'){
            return self::get_object(self::$sh,'pascal');
        }elseif($ext == 'f77' || $ext == 'f' ){
            return self::get_object(self::$fortran77,'fortran77');
        }elseif($ext == 'pl' ){
            return self::get_object(self::$prolog,'prolog');
        }elseif($ext == 'm' ){
            return self::get_object(self::$matlab,'matlab');
        }elseif($ext == 'py' ){
            return self::get_object(self::$python,'python');
        }elseif($ext == 'scala' ){
            return self::get_object(self::$scala,'scala');
        }else{
            return self::get_object(self::$base,'base');
        }
    }
}
