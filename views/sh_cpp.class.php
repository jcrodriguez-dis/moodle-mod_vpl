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
 * Syntaxhighlighter for C++ language
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/sh_c.class.php';

class vpl_sh_cpp extends vpl_sh_c{
    function __construct(){
        parent::__construct();
        $added = array( 'and' => true, 'and_eq' => true, 'bitand' => true, 'bitor' => true,
                     'bool' => true, 'catch' => true, 'class' => true, 'compl' => true,
                     'const_cast' => true, 'delete' => true, 'dynamic_cast' => true, 'explicit' => true,
                      'export' => true, 'false' => true, 'friend' => true, 'inline' => true,
                      'namespace' => true, 'new' => true, 'not' => true, 'not_eq' => true, 'operator' => true,
                      'or' => true, 'or_eq' => true, 'private' => true, 'protected' => true, 'public' => true,
                      'reinterpret_cast' => true, 'static_cast' => true, 'template' => true, 'this' => true,
                      'throw' => true, 'true' => true, 'try' => true, 'typeid' => true, 'typename' => true,
                     'using' => true, 'virtual' => true, 'xor' => true, 'xor_eq' => true);
        $this->reserved= array_merge($this->reserved, $added);
    }
}
