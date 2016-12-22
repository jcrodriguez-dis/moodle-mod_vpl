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
 * VPL Syntaxhighlighter for Fortran77 language
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/sh_text.class.php');

class vpl_sh_fortran77 extends vpl_sh_text {
    protected $previouspending;
    protected function show_pending(&$rest) {
        $lower = strtolower( $rest );
        if (array_key_exists( $lower, $this->reserved )) {
            $this->initTag( self::C_RESERVED );
            parent::show_pending( $rest );
            echo self::ENDTAG;
        } else {
            parent::show_pending( $rest );
        }
        $this->previouspending = $lower;
        $rest = '';
    }
    protected function is_begin_identifier($c) {
        return ($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || ($c >= '0' && $c <= '9') || ($c == '$') || ($c == '_');
    }
    public function __construct() {
        $this->reserved = array (
                'accept' => 1,
                'assign' => 1,
                'backspace' => 1,
                'call' => 1,
                'close' => 1,
                'continue' => 1,
                'decode' => 1,
                'do' => 1,
                'dowhile' => 1,
                'else' => 1,
                'elseif' => 1,
                'encode' => 1,
                'enddo' => 1,
                'endfile' => 1,
                'endif' => 1,
                'goto' => 1,
                'if' => 1,
                'include' => 1,
                'inquire' => 1,
                'open' => 1,
                'pause' => 1,
                'print' => 1,
                'return' => 1,
                'rewind' => 1,
                'save' => 1,
                'static' => 1,
                'stop' => 1,
                'write' => 1,
                /* From here declarators. */
                'automatic' => 1,
                'blockdata' => 1,
                'byte' => 1,
                'character' => 1,
                'common' => 1,
                'complex' => 1,
                'data' => 1,
                'dimension' => 1,
                'doublecomplex' => 1,
                'doubleprecision' => 1,
                'end' => 1,
                'endmap' => 1,
                'endstructure' => 1,
                'endunion' => 1,
                'equivalence' => 1,
                'external' => 1,
                'format' => 1,
                'function' => 1,
                'implicit' => 1,
                'integer' => 1,
                'intrinsic' => 1,
                'logical' => 1,
                'map' => 1,
                'namelist' => 1,
                'options' => 1,
                'parameter' => 1,
                'pointer' => 1,
                'pragma' => 1,
                'program' => 1,
                'real' => 1,
                'record' => 1,
                'static' => 1,
                'structure' => 1,
                'subroutine' => 1,
                'type' => 1,
                'union' => 1,
                'virtual' => 1,
                'volatile' => 1
        );
        parent::__construct();
    }
    const IN_REGULAR = 0;
    const IN_STRING = 1;
    const IN_CSTRING = 2;
    const IN_DSTRING = 3;
    const IN_LINECOMMENT = 4;
    const IN_IDENTIFIER = 5;
    public function print_file($filename, $filedata, $showln = true) {
        $this->begin( $filename, $showln );
        $state = self::IN_REGULAR;
        $pending = '';
        $l = strlen( $filedata );
        if ($l) {
            $this->show_line_number();
        }
        $current = '';
        for ($i = 0; $i < $l; $i ++) {
            $current = $filedata [$i];
            if ($i < ($l - 1)) {
                $next = $filedata [$i + 1];
            } else {
                $next = '';
            }
            if ($current == self::CR) {
                if ($next == self::LF) {
                    continue;
                } else {
                    $current = self::LF;
                }
            }

            switch ($state) {
                case self::IN_REGULAR :
                case self::IN_IDENTIFIER :
                    if ($current == '#') {
                        $this->show_pending( $pending );
                        $state = self::IN_LINECOMMENT;
                        $this->initTag( self::C_COMMENT );
                    } else if ($current == '\'') {
                        $this->show_pending( $pending );
                        $this->initTag( self::C_STRING );
                        $state = self::IN_STRING;
                    } else if ($current == '"') {
                        $this->show_pending( $pending );
                        $state = self::IN_DSTRING;
                        $this->initTag( self::C_STRING );
                    } else if ($current == '$' && $next == '\'') {
                        $this->show_pending( $pending );
                        $pending = '$\'';
                        $i ++;
                        $this->initTag( self::C_STRING );
                        $state = self::IN_CSTRING;
                        continue 2;
                    } else if ($this->is_begin_identifier( $current )) {
                        if ($state == self::IN_REGULAR) {
                            $this->show_pending( $pending );
                            $state = self::IN_IDENTIFIER;
                        }
                    } else if ($state == self::IN_IDENTIFIER) {
                        $this->show_pending( $pending );
                        $state = self::IN_REGULAR;
                    }
                    break;
                case self::IN_LINECOMMENT :
                    if ($current == self::LF) {
                        $this->show_pending( $pending );
                        echo "\n";
                        $this->endTag();
                        $this->show_line_number();
                        $state = self::IN_REGULAR;
                        continue 2;
                    }
                case self::IN_STRING :
                    if ($current == '\'') {
                        $pending .= '\'';
                        $this->show_pending( $pending );
                        $this->endTag();
                        $state = self::IN_REGULAR;
                        continue 2;
                    }
                    break;
                case self::IN_CSTRING :
                    if ($current == '\'') {
                        $pending .= '\'';
                        $this->show_pending( $pending );
                        $this->endTag();
                        $state = self::IN_REGULAR;
                        continue 2;
                    }
                    if ($current == '\\') { // Jump next.
                        $pending .= '\\' . $next;
                        $i ++;
                        continue 2;
                    }
                    break;
                case self::IN_DSTRING :
                    if ($current == '"') {
                        $pending .= '"';
                        $this->show_pending( $pending );
                        $this->endTag();
                        $state = self::IN_REGULAR;
                        continue 2;
                    }
                    if ($current == '\\') { // Jump next.
                        $pending .= '\\' . $next;
                        $i ++;
                        continue 2;
                    }
                    break;
            }
            $pending .= $current;
            if ($current == self::LF) {
                $this->show_pending( $pending );
                $this->show_line_number();
            }
        }

        $this->show_pending( $pending );
        if ($state != self::IN_REGULAR) {
            $this->endTag();
        }
        $this->end();
    }
}
