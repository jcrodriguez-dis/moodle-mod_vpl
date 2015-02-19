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
 * Java programing language tokenizer class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/tokenizer_c.class.php';

class vpl_tokenizer_java extends vpl_tokenizer_c{
    static $reserved_java=null;
    function __construct(){
        parent::__construct();
        if(self::$reserved_java === null){
            self::$reserved_java = array('abstract' => true, 'continue' => true, 'for' => true, 'new' => true, 'switch' => true,
                'assert' => true, 'default' => true, 'goto' => true, 'package' => true, 'synchronized' => true,
                'boolean' => true, 'do' => true, 'if' => true, 'private' => true, 'this' => true,
                'break' => true, 'double' => true, 'implements' => true, 'protected' => true, 'throw' => true,
                'byte' => true, 'else' => true, 'import' => true, 'public' => true, 'throws' => true,
                'case' => true, 'enum' => true, 'instanceof' => true, 'return' => true, 'transient' => true,
                'catch' => true, 'extends' => true, 'int' => true, 'short' => true, 'try' => true,
                'char' => true, 'final' => true, 'interface' => true, 'static' => true, 'void' => true,
                'class' => true, 'finally' => true, 'long' => true, 'strictfp' => true, 'volatile' => true,
                'const' => true, 'float' => true, 'native' => true, 'super' => true, 'while' => true
            );
            $this->reserved=self::$reserved_java;
        }
    }
}
