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
 * VPL Syntaxhighlighter for python language
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/sh_text.class.php');

class vpl_sh_python extends vpl_sh_text {
    protected function show_pending(&$rest) {
        if (array_key_exists( $rest, $this->reserved )) {
            $this->initTag( self::c_reserved );
            parent::show_pending( $rest );
            echo self::endTag;
        } else if (strlen( $rest ) > 0 && $rest [0] == '_') {
            $this->initTag( self::c_variable );
            parent::show_pending( $rest );
            echo self::endTag;
        } else {
            parent::show_pending( $rest );
        }
    }
    const IN_REGULAR = 0;
    const IN_IDENTIFIER = 1;
    const IN_STRING = 2;
    const IN_DECORATOR = 3;
    const IN_COMMENT = 4;
    const IN_LINECOMMENT = 5;
    public function __construct() {
        $this->reserved = array (
                "False" => true,
                "class" => true,
                "finally" => true,
                "is" => true,
                "return" => true,
                "None" => true,
                "continue" => true,
                "for" => true,
                "lambda" => true,
                "try" => true,
                "True" => true,
                "def" => true,
                "from" => true,
                "nonlocal" => true,
                "while" => true,
                "and" => true,
                "del" => true,
                "global" => true,
                "not" => true,
                "with" => true,
                "as" => true,
                "elif" => true,
                "if" => true,
                "or" => true,
                "yield" => true,
                "assert" => true,
                "else" => true,
                "import" => true,
                "pass" => true,
                "break" => true,
                "except" => true,
                "in" => true,
                "raise" => true
        );
        parent::__construct();
    }
    public function show_line_number() {
        echo "\n";
        parent::show_line_number();
    }
    protected function isidentifierchar($c) {
        return ($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || ($c >= '0' && $c <= '9') || ($c == '_') || ($c >= 128);
    }
    public function print_file($filename, $filedata, $showln = true) {
        $this->begin( $filename, $showln );
        $state = self::IN_REGULAR;
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
                case self::IN_REGULAR :
                case self::IN_IDENTIFIER :
                    if ($current == '#') {
                        $this->show_pending( $pending );
                        $state = self::IN_LINECOMMENT;
                        $pending = $current;
                        continue 2;
                    } else if ($current == '"') {
                        $this->show_pending( $pending );
                        if (substr( $filedata, $i, 3 ) == '"""') {
                            if ($firstnospace == '"') {
                                $state = self::IN_COMMENT;
                                $pending = '"';
                                continue 2;
                            }
                            $stringlimit = '"""';
                        } else {
                            $stringlimit = '"';
                        }
                        $pending = $current;
                        $state = self::IN_STRING;
                        $rawstring = strtolower( $previous ) == 'r';
                        continue 2;
                    } else if ($current == '\'') {
                        $this->show_pending( $pending );
                        $state = self::IN_STRING;
                        $rawstring = strtolower( $previous ) == 'r';
                        if (substr( $filedata, $i, 3 ) == "'''") {
                            $stringlimit = "'''";
                        } else {
                            $stringlimit = "'";
                        }
                        $pending = $stringlimit;
                        $i += strlen( $stringlimit ) - 1;
                        continue 2;
                    } else if ($current == '@') {
                        $this->show_pending( $pending );
                        $state = self::IN_DECORATOR;
                        $pending = $current;
                        continue 2;
                    } else if ($this->isidentifierchar( $current )) {
                        if ($state == self::IN_REGULAR) {
                            $this->show_text( $pending );
                            $pending = '';
                            $state = self::IN_IDENTIFIER;
                        }
                    } else if ($state == self::IN_IDENTIFIER) {
                        $this->show_pending( $pending );
                        $state = self::IN_REGULAR;
                    }
                    break;
                case self::IN_COMMENT :
                    if (substr( $filedata, $i, 3 ) == '"""') {
                        $state = self::IN_REGULAR;
                        $this->initTag( self::c_comment );
                        $this->show_text( $pending . '"""' );
                        $pending = '';
                        $this->endTag();
                        $i += 2;
                        continue 2;
                    } else if ($current == self::LF) {
                        $this->initTag( self::c_comment );
                        $this->show_text( $pending );
                        $pending = '';
                        $this->endTag();
                        $this->show_line_number();
                        continue 2;
                    }
                    break;
                case self::IN_LINECOMMENT :
                    if ($current == self::LF) {
                        $this->initTag( self::c_comment );
                        $this->show_text( $pending );
                        $pending = '';
                        $this->endTag();
                        $this->show_line_number();
                        $state = self::IN_REGULAR;
                        continue 2;
                    }
                    break;
                case self::IN_STRING :
                    if (substr( $filedata, $i, strlen( $stringlimit ) ) == $stringlimit) {
                        if ($rawstring || $previous != '\\') {
                            $state = self::IN_REGULAR;
                            $this->initTag( self::c_string );
                            $this->show_text( $pending . $stringlimit );
                            $pending = '';
                            $this->endTag();
                            $i += strlen( $stringlimit ) - 1;
                            continue 2;
                        }
                    }
                    if ($current == self::LF) {
                        $this->initTag( self::c_string );
                        $this->show_text( $pending );
                        $pending = '';
                        $this->endTag();
                        $this->show_line_number();
                        continue 2;
                    }
                    $pending .= $current;
                    if ($previous == '\\') {
                        $current = '\0';
                    }
                    continue 2;
                case self::IN_DECORATOR :
                    if (! $this->isidentifierchar( $next ) && $next != '.' && $next != ' ') {
                        $state = self::IN_REGULAR;
                        $this->initTag( self::c_macro );
                        $this->show_text( $pending );
                        $this->endTag();
                        if ($current == self::LF) {
                            $this->show_line_number();
                            $pending = '';
                        } else {
                            $pending = $current;
                        }
                        continue 2;
                    }
                    break;
            }
            if ($current == self::LF) {
                $this->show_pending( $pending );
                $this->show_line_number();
            } else {
                $pending .= $current;
            }
        }

        $this->show_pending( $pending );
        if ($state != self::IN_REGULAR) {
            $this->endTag();
        }
        $this->end();
    }
}
