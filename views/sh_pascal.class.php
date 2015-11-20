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
 * VPL Syntaxhighlighter for Pascal language
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/sh_text.class.php');

class vpl_sh_pascal extends vpl_sh_text {
    protected $previouspending;
    protected function show_pending(&$rest) {
        $lower = strtolower( $rest );
        if (array_key_exists( $lower, $this->reserved )) {
            $this->initTag( self::C_RESERVED );
            parent::show_pending( $rest );
            echo self::endTag;
        } else {
            parent::show_pending( $rest );
        }
        $this->previouspending = $lower;
        $rest = '';
    }
    protected function is_begin_identifier($c) {
        return ($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || ($c >= '0' && $c <= '9') || ($c == '_');
    }
    public function __construct() {
        $this->reserved = array (
                'and' => true,
                'end' => true,
                'label' => true,
                'repeat' => true,
                'while' => true,
                'asm' => true,
                'exports' => true,
                'library' => true,
                'set' => true,
                'with' => true,
                'array' => true,
                'file' => true,
                'mod' => true,
                'shl' => true,
                'xor' => true,
                'begin' => true,
                'for' => true,
                'nil' => true,
                'shr' => true,
                'case' => true,
                'function' => true,
                'not' => true,
                'string' => true,
                'const' => true,
                'goto' => true,
                'object' => true,
                'then' => true,
                'constructor' => true,
                'if' => true,
                'of' => true,
                'to' => true,
                'destructor' => true,
                'implementation' => true,
                'or' => true,
                'type' => true,
                'div' => true,
                'in' => true,
                'packed' => true,
                'unit' => true,
                'do' => true,
                'inherited' => true,
                'procedure' => true,
                'until' => true,
                'downto' => true,
                'inline' => true,
                'program' => true,
                'uses' => true,
                'else' => true,
                'interface' => true,
                'record' => true,
                'var' => true
        );
        parent::__construct();
    }
    const IN_REGULAR = 0;
    const IN_IDENTIFIER = 1;
    const IN_STRING = 2;
    const IN_COMMENT_C = 3;
    const IN_COMMENT_P = 4;
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
                    if ($current == '(') {
                        if ($next == '*') { // Block comment begin.
                            $this->show_pending( $pending );
                            $state = self::IN_COMMENT_P;
                            $this->initTag( self::C_COMMENT );
                            $i ++;
                            $pending = '(*';
                            continue 2;
                        }
                    } else if ($current == '{') {
                        $this->show_pending( $pending );
                        $state = self::IN_COMMENT_C;
                        $this->initTag( self::C_COMMENT );
                    } else if ($current == '\'') {
                        $this->show_pending( $pending );
                        $this->initTag( self::C_STRING );
                        $state = self::IN_STRING;
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
                case self::IN_COMMENT_P :
                    if ($current == self::LF) {
                        $this->show_pending( $pending );
                        echo "\n";
                        $this->endTag();
                        $this->show_line_number();
                        $this->initTag( self::C_COMMENT );
                        continue 2;
                    } else if ($current == '*') {
                        if ($next == ')') {
                            $pending .= '*)';
                            $this->show_pending( $pending );
                            $this->endTag();
                            $state = self::IN_REGULAR;
                            $i ++;
                            continue 2;
                        }
                    }
                    break;
                case self::IN_COMMENT_C :
                    if ($current == self::LF) {
                        $this->show_pending( $pending );
                        $this->endTag();
                        echo "\n";
                        $this->show_line_number();
                        $this->initTag( self::C_COMMENT );
                        continue 2;
                    } else if ($current == '}') {
                        $pending .= '}';
                        $this->show_pending( $pending );
                        $this->endTag();
                        $state = self::IN_REGULAR;
                        continue 2;
                    }
                    break;
                case self::IN_STRING :
                    if ($current == '\'') {
                        if ($next != '\'') {
                            $this->show_pending( $pending );
                            $this->endTag();
                            $state = self::IN_REGULAR;
                        } else {
                            $pending .= '\'';
                            $i ++; // Remove next ' from scan.
                        }
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
