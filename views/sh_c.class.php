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
 * VPL Syntaxhighlighter for C language
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/sh_text.class.php');

class vpl_sh_c extends vpl_sh_text {
    protected function show_pending(&$rest) {
        if (array_key_exists( $rest, $this->reserved )) {
            $this->initTag( self::c_reserved );
            parent::show_pending( $rest );
            echo self::endTag;
        } else {
            parent::show_pending( $rest );
        }
    }

    const REGULAR = 0;
    const IN_STRING = 1;
    const IN_CHAR = 2;
    const IN_MACRO = 3;
    const IN_COMMENT = 4;
    const IN_LINECOMMENT = 5;

    public function __construct() {
        $this->reserved = array (
                "auto" => true,
                "break" => true,
                "case" => true,
                "char" => true,
                "const" => true,
                "continue" => true,
                "default" => true,
                "do" => true,
                "double" => true,
                "else" => true,
                "enum" => true,
                "extern" => true,
                "float" => true,
                "for" => true,
                "goto" => true,
                "if" => true,
                "inline" => true,
                "int" => true,
                "long" => true,
                "register" => true,
                "restrict" => true,
                "return" => true,
                "short" => true,
                "signed" => true,
                "sizeof" => true,
                "static" => true,
                "struct" => true,
                "switch" => true,
                "typedef" => true,
                "union" => true,
                "unsigned" => true,
                "void" => true,
                "volatile" => true,
                "while" => true,
                "_Bool" => true,
                "_Complex" => true,
                "_Imaginary" => true
        );
        parent::__construct();
    }
    public function show_line_number() {
        echo "\n";
        parent::show_line_number();
    }
    public function print_file($filename, $filedata, $showln = true) {
        $this->begin( $filename, $showln );
        $state = self::REGULAR;
        $pending = '';
        $firstnospace = '';
        $lastnospace = '';
        $l = strlen( $filedata );
        if ($l) {
            $this->show_line_number();
        }
        $current = '';
        $previous = '';
        for ($i = 0; $i < $l; $i ++) {
            $previous = $current;
            $current = $filedata [$i];
            if ($i < ($l - 1)) {
                $next = $filedata [$i + 1];
            } else {
                $next = '';
            }
            if ($previous == self::LF) {
                $lastnospace = '';
                $firstnospace = '';
            }
            if ($current == self::CR) {
                if ($next == self::LF) {
                    continue;
                } else {
                    $current = self::LF;
                }
            }
            if ($current != ' ' && $current != "\t") { // Keep first and last char.
                if ($current != self::LF) {
                    $lastnospace = $current;
                }
                if ($firstnospace == '') {
                    $firstnospace = $current;
                }
            }
            switch ($state) {
                case self::IN_COMMENT :
                    // Check end of block comment.
                    if ($current == '*') {
                        if ($next == '/') {
                            $state = self::REGULAR;
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
                        if ($this->showln) { // Check to send endtag.
                            $this->endTag();
                        }
                        $this->show_line_number();
                        if ($this->showln) { // Check to send initTagtag.
                            $this->initTag( self::c_comment );
                        }
                    } else {
                        $pending .= $current;
                    }
                    break;
                case self::IN_LINECOMMENT :
                    // Check end of comment.
                    if ($current == self::LF) {
                        $this->show_text( $pending );
                        $pending = '';
                        $this->endTag();
                        $this->show_line_number();
                        $state = self::REGULAR;
                    } else {
                        $pending .= $current;
                    }
                    break;
                case self::IN_MACRO :
                    // Check end of macro.
                    if ($current == self::LF) {
                        if ($lastnospace != '\\') {
                            $this->show_text( $pending );
                            $pending = '';
                            $this->endTag();
                            $this->show_line_number();
                            $state = self::REGULAR;
                        } else {
                            $this->show_text( $pending );
                            $pending = '';
                            $this->endTag();
                            $this->show_line_number();
                            $this->initTag( self::c_macro );
                        }
                    } else {
                        $pending .= $current;
                    }
                    break;
                case self::IN_STRING :
                    // Check end of string.
                    if ($current == '"' && $previous != '\\') {
                        $pending .= '"';
                        $this->show_text( $pending );
                        $pending = '';
                        $this->endTag();
                        $state = self::REGULAR;
                        break;
                    }
                    if ($current == self::LF) {
                        $this->show_text( $pending );
                        $pending = '';
                        $this->endTag();
                        $this->show_line_number();
                        $this->initTag( self::c_string );
                    } else {
                        $pending .= $current;
                    }
                    // Discard two backslash.
                    if ($current == '\\' && $previous == '\\') {
                        $current = ' ';
                    }
                    break;
                case self::IN_CHAR :
                    // Check end of char.
                    if ($current == '\'' && $previous != '\\') {
                        $pending .= '\'';
                        $this->show_text( $pending );
                        $pending = '';
                        $this->endTag();
                        $state = self::REGULAR;
                        break;
                    }
                    if ($current == self::LF) {
                        $this->show_text( $pending );
                        $pending = '';
                        $this->endTag();
                        $this->show_line_number();
                        $this->initTag( self::c_string );
                    } else {
                        $pending .= $current;
                    }
                    // Discard two backslash.
                    if ($current == '\\' && $previous == '\\') {
                        $current = ' ';
                    }
                    break;
                case self::REGULAR :
                    if ($current == '/') {
                        if ($next == '*') { // Begin block comments.
                            $state = self::IN_COMMENT;
                            $this->show_pending( $pending );
                            $this->initTag( self::c_comment );
                            $this->show_text( '/*' );
                            $i ++;
                            continue 2;
                        }
                        if ($next == '/') { // Begin line comment.
                            $state = self::IN_LINECOMMENT;
                            $this->show_pending( $pending );
                            $this->initTag( self::c_comment );
                            $this->show_text( '//' );
                            $i ++;
                            continue 2;
                        }
                    } else if ($current == '"') {
                        $state = self::IN_STRING;
                        $this->show_pending( $pending );
                        $this->initTag( self::c_string );
                        $this->show_text( '"' );
                        break;
                    } else if ($current == "'") {
                        $state = self::IN_CHAR;
                        $this->show_pending( $pending );
                        $this->initTag( self::c_string );
                        $this->show_text( '\'' );
                        break;
                    } else if ($current == '#' && $firstnospace == $current) {
                        $state = self::IN_MACRO;
                        $this->show_pending( $pending );
                        $this->initTag( self::c_macro );
                        $this->show_text( '#' );
                        break;
                    }
                    if (($current >= 'a' && $current <= 'z') || ($current >= 'A' && $current <= 'Z')
                        || ($current >= '0' && $current <= '9') || $current == '_' || ord( $current ) > 127) {
                        $pending .= $current;
                    } else {
                        $this->show_pending( $pending );
                        if ($current == '{' || $current == '(' || $current == '[') {
                            $this->initHover();
                        }
                        if ($current == self::LF) {
                            $this->show_line_number();
                        } else {
                            $aux = $current;
                            $this->show_pending( $aux );
                        }
                        if ($current == ')' || $current == '}' || $current == ']') {
                            $this->endHover();
                        }
                    }
            }
        }

        $this->show_pending( $pending );
        if ($state != self::REGULAR) {
            $this->endTag();
        }
        $this->end();
    }
}
