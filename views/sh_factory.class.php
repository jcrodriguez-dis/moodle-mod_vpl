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
 * VPL Syntaxhighlighter object factory class
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

class vpl_sh_factory {
    protected static $cache = array ();
    public static function get_object($type) {
        if (! isset( self::$cache [$type] )) {
            require_once(dirname( __FILE__ ) . '/sh_' . $type . '.class.php');
            $class = 'vpl_sh_' . $type;
            self::$cache [$type] = new $class();
        }
        return self::$cache [$type];
    }
    public static function get_sh($filename) {
        $ext = vpl_fileextension( $filename );
        if (vpl_is_binary( $filename )) {
            if (vpl_is_image( $filename )) {
                return self::get_object( 'image' );
            } else {
                return self::get_object( 'binary' );
            }
        }
        switch ($ext) {
            case 'c' :
                return self::get_object( 'c' );
            case 'cpp' :
            case 'h' :
                return self::get_object( 'cpp' );
            case 'java' :
                return self::get_object( 'java' );
            case 'ada' :
            case 'adb' :
            case 'ads' :
                return self::get_object( 'ada' );
            case 'sql' :
                return self::get_object( 'sql' );
            case 'scm' :
                return self::get_object( 'scheme' );
            case 'sh' :
                return self::get_object( 'sh' );
            case 'pas' :
                return self::get_object( 'pascal' );
            case 'f77' :
            case 'f' :
                return self::get_object( 'fortran77' );
            case 'pl' :
                return self::get_object( 'prolog' );
            case 'm' :
                return self::get_object( 'matlab' );
            case 'py' :
                return self::get_object( 'python' );
            case 'scala' :
                return self::get_object( 'scala' );
            default :
                return self::get_object( 'geshi' );
        }
    }
}
