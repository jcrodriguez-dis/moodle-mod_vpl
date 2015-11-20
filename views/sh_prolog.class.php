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
 * VPL Syntaxhighlighter for Prolog language
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/sh_text.class.php');

class vpl_sh_prolog extends vpl_sh_text {
    protected function isnextopenparenthesis(& $s, $ini) {
        $l = strlen( $s );
        for ($i = $ini; $i < $l; $i ++) {
            $c = $s [$i];
            if ($c == '(') {
                return true;
            }
            if ($c != ' ' && $c != self::CR && $c != self::LF && $c != '\t') {
                return false;
            }
        }
        return false;
    }
    protected function isidentifierchar($c) {
        return ($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || ($c >= '0' && $c <= '9') || ($c == '_');
    }
    protected function show_pending(&$rest, &$s = null, $i = null) {
        if (strlen( $rest ) == 0) {
            return;
        }
        $c = $rest [0];
        if ($this->isidentifierchar( $c )) {
            $needend = true;
            if (($c >= 'A' && $c <= 'Z') || $c == '_') {
                $this->initTag( self::C_VARIABLE );
            } else if (($c >= 'a' && $c <= 'z')) {
                if ($s != null && $this->isnextopenparenthesis( $s, $i ) || $rest == 'is') {
                    $this->initTag( self::C_RESERVED );
                } else {
                    $this->initTag( self::C_MACRO );
                }
            } else {
                $needend = false;
            }
            parent::show_pending( $rest );
            if ($needend) {
                echo self::ENDTAG;
            }
        } else {
            parent::show_pending( $rest );
        }
    }
    const IN_REGULAR = 0;
    const IN_STRING = 1;
    const IN_CHAR = 2;
    const IN_MACRO = 3;
    const IN_COMMENT = 4;
    const IN_LINECOMMENT = 5;
    const IN_IDENTIFIER = 6;
    public function show_line_number() {
        echo "\n";
        parent::show_line_number();
    }
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
                    if ($current == '/') {
                        if ($next == '*') { // Begin block comments.
                            $state = self::IN_COMMENT;
                            $this->show_pending( $pending, $filedata, $i );
                            $this->initTag( self::C_COMMENT );
                            $this->show_text( '/*' );
                            $i ++;
                            continue 2;
                        }
                        break;
                    } else if ($current == '%') { // Begin line comment.
                        $this->show_pending( $pending, $filedata, $i );
                        $state = self::IN_LINECOMMENT;
                        $this->initTag( self::C_COMMENT );
                        break;
                    } else if ($current == '"') {
                        $this->show_pending( $pending, $filedata, $i );
                        $state = self::IN_STRING;
                        $this->initTag( self::C_STRING );
                        break;
                    } else if ($current == "'") {
                        $this->show_pending( $pending, $filedata, $i );
                        $state = self::IN_CHAR;
                        $this->initTag( self::C_STRING );
                        break;
                    } else if ($this->isidentifierchar( $current )) {
                        if ($state == self::IN_REGULAR) {
                            $this->show_pending( $pending, $filedata, $i );
                            $state = self::IN_IDENTIFIER;
                        }
                    } else {
                        if ($state == self::IN_IDENTIFIER) {
                            $this->show_pending( $pending, $filedata, $i );
                            $state = self::IN_REGULAR;
                        }
                        if ($current == self::LF) {
                            $this->show_pending( $pending, $filedata, $i );
                            $this->show_line_number();
                            continue 2;
                        }
                    }
                    break;
                case self::IN_COMMENT :
                    // Check end of block comment.
                    if ($current == '*') {
                        if ($next == '/') {
                            $state = self::IN_REGULAR;
                            $pending .= '*/';
                            $this->show_text( $pending );
                            $pending = '';
                            $this->endTag();
                            $i ++;
                            continue 2;
                        }
                    }
                    if ($current == self::LF) {
                        $this->show_text( $pending );
                        $pending = '';
                        $this->endTag();
                        $this->show_line_number();
                        $this->initTag( self::C_COMMENT );
                        continue 2;
                    }
                    break;
                case self::IN_LINECOMMENT :
                    // Check end of comment.
                    if ($current == self::LF) {
                        $this->show_text( $pending );
                        $pending = '';
                        $this->endTag();
                        $this->show_line_number();
                        $state = self::IN_REGULAR;
                        continue 2;
                    }
                    break;
                case self::IN_STRING :
                case self::IN_CHAR :
                    // Check end of string.
                    if ($state == self::IN_STRING && $current == '"') {
                        $pending .= $current;
                        $this->show_text( $pending );
                        $pending = '';
                        $this->endTag();
                        $state = self::IN_REGULAR;
                        continue 2;
                    }
                    // Check end of char.
                    if ($state == self::IN_CHAR && $current == '\'') {
                        $pending .= $current;
                        $this->show_text( $pending );
                        $pending = '';
                        $this->endTag();
                        $state = self::IN_REGULAR;
                        continue 2;
                    }
                    if ($current == self::LF) {
                        $this->show_text( $pending );
                        $pending = '';
                        $this->endTag();
                        $this->show_line_number();
                        $this->initTag( self::C_STRING );
                        continue 2;
                    }
                    // Discard two backslash.
                    if ($current == '\\') {
                        $pending .= $current . $next;
                        $i ++; // Skip next char.
                        continue 2;
                    }
                    break;
            }
            $pending .= $current;
        }

        $this->show_pending( $pending );
        if ($state != self::IN_REGULAR && $state != self::IN_IDENTIFIER) {
            $this->endTag();
        }
        $this->end();
    }
}
