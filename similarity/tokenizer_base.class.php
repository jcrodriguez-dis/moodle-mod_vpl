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

defined('MOODLE_INTERNAL') || die();

class vpl_token_type {
    const RESERVED = 1;
    const IDENTIFIER = 2;
    const OPERATOR = 3;
    const LITERAL = 4;
    const OTHER = 5;
}
class vpl_token {
    public $type;
    public $value;
    public $line;
    private static $hashvalues = array ();
    private static function get_hash($value) {
        if (! isset( self::$hashvalues [$value] )) {
            self::$hashvalues [$value] = mt_rand();
        }
        return self::$hashvalues [$value];
    }
    public function __construct($type, $value, $line) {
        $this->type = $type;
        $this->value = $value;
        $this->line = $line;
    }
    public function hash() {
        return self::get_hash( $this->value );
    }
    public function show() {
        echo $this->line . ' ' . $this->type . ' ' . $this->value . '<br />';
    }
}
class vpl_tokenizer_base {
    const CR = "\r";
    const LF = "\n";
    const TAB = "\t";
}
