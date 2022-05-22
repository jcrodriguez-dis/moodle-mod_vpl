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
 * M (Octave) programing language tokenizer class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/tokenizer_base.class.php');

class vpl_tokenizer_matlab extends vpl_tokenizer_base {
    const REGULAR = 0;
    const IN_STRING = 1;
    const IN_MACRO = 3;
    const IN_COMMENT = 4;
    const IN_LINECOMMENT = 5;
    const IN_NUMBER = 6;
    protected static $creserved = null;
    protected $linenumber;
    protected $tokens;
    protected function is_indentifier($text) {
        if (strlen( $text ) == 0) {
            return false;
        }
        $first = $text[0];
        return ($first >= 'a' && $first <= 'z') || ($first >= 'A' && $first <= 'Z') || $first == '_';
    }
    protected function is_number($text) {
        if (strlen( $text ) == 0) {
            return false;
        }
        $first = $text[0];
        if ($first == '.' && strlen( $text ) > 1) {
            $first = $text[1];
        }
        return $first >= '0' && $first <= '9';
    }
    protected function add_pending(&$pending) {
        if ($pending <= ' ') {
            $pending = '';
            return;
        }
        if ($this->is_indentifier( $pending )) {
            if (isset( $this->reserved[$pending] )) {
                $type = vpl_token_type::RESERVED;
            } else {
                $type = vpl_token_type::IDENTIFIER;
            }
        } else {
            if ($this->is_number( $pending ) || $pending == '""' || $pending == "''") {
                $type = vpl_token_type::LITERAL;
            } else if (strpos( '()[]{};', $pending ) === false) {
                $type = vpl_token_type::OPERATOR;
            } else {
                $type = vpl_token_type::OTHER;
            }
        }
        $this->tokens[] = new vpl_token( $type, $pending, $this->linenumber );
        $pending = '';
    }
    public function __construct() {
        if (self::$creserved === null) {
            self::$creserved = array ( // Source MATLAB Quick Reference Author: Jialong He.
                    /* Managing Commands and Functions. */
                    "addpath" => true,
                    "doc" => true,
                    "docopt" => true,
                    "genpath" => true,
                    "help" => true,
                    "helpbrowser" => true,
                    "helpdesk" => true,
                    "helpwin" => true,
                    "lasterr" => true,
                    "lastwarn" => true,
                    "license" => true,
                    "lookfor" => true,
                    "partialpath" => true,
                    "path" => true,
                    "pathtool" => true,
                    "profile" => true,
                    "profreport" => true,
                    "rehash" => true,
                    "rmpath" => true,
                    "support" => true,
                    "type" => true,
                    "ver" => true,
                    "version" => true,
                    "web" => true,
                    "what" => true,
                    "whatsnew" => true,
                    "which" => true,
                    /* Managing Variables and the Workspace. */
                    "clear" => true,
                    "disp" => true,
                    "length" => true,
                    "load" => true,
                    "memory" => true,
                    "mlock" => true,
                    "munlock" => true,
                    "openvar" => true,
                    "Open" => true,
                    "pack" => true,
                    "save" => true,
                    "saveas" => true,
                    "size" => true,
                    "who" => true,
                    "whos" => true,
                    "workspace" => true,
                    /* Starting and Quitting MATLAB. */
                    "finish" => true,
                    "exit" => true,
                    "matlab" => true,
                    "matlabrc" => true,
                    "quit" => true,
                    "startup" => true,
                    /* As a Programming Language. */
                    "builtin" => true,
                    "eval" => true,
                    "evalc" => true,
                    "evalin" => true,
                    "feval" => true,
                    "function" => true,
                    "global" => true,
                    "nargchk" => true,
                    "persistent" => true,
                    "script" => true,
                    /* Control Flow. */
                    "break" => true,
                    "case" => true,
                    "catch" => true,
                    "continue" => true,
                    "else" => true,
                    "elseif" => true,
                    "end" => true,
                    "error" => true,
                    "for" => true,
                    "if" => true,
                    "otherwise" => true,
                    "return" => true,
                    "switch" => true,
                    "try" => true,
                    "warning" => true,
                    "while" => true,
                    /* Interactive Input. */
                    "input" => true,
                    "keyboard" => true,
                    "menu" => true,
                    "pause" => true,
                    /* Object-Oriented Programming. */
                    "class" => true,
                    "double" => true,
                    "inferiorto" => true,
                    "inline" => true,
                    "int8" => true,
                    "int16" => true,
                    "int32" => true,
                    "isa" => true,
                    "loadobj" => true,
                    "saveobj" => true,
                    "single" => true,
                    "superiorto" => true,
                    "uint8" => true,
                    "uint16" => true,
                    "uint32" => true,
                    /* Operators. */
                    "kron" => true,
                    "xor" => true,
                    "and" => true
            );
        }
        $this->reserved = &self::$creserved;
    }
    public function parse($filedata) {
        $this->tokens = array ();
        $state = self::REGULAR;
        $pending = '';
        $lastnospace = self::LF;
        $l = strlen( $filedata );
        if ($l) {
            $this->linenumber ++;
        }
        $current = self::LF;
        for ($i = 0; $i < $l; $i ++) {
            $previous = $current;
            $current = $filedata[$i];
            if ($i < ($l - 1)) {
                $next = $filedata[$i + 1];
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
            if (! ctype_space( $previous ) || $previous == self::LF) { // Keep last char.
                $lastnospace = $previous;
            }
            switch ($state) {
                case self::IN_LINECOMMENT :
                    // Check end of comment.
                    if ($current == self::LF) {
                        $pending = '';
                        $this->linenumber ++;
                        $state = self::REGULAR;
                    }
                    break;
                case self::IN_COMMENT :
                    // Check end of comment.
                    if ($current == self::LF) {
                        $pending = '';
                        $this->linenumber ++;
                    } else if ($current == '%' && $next == '}') {
                        $pending = '';
                        $i ++;
                        $state = self::REGULAR;
                    }
                    break;
                case self::IN_STRING :
                    // Check end of string.
                    if ($current == $this->string_delimiter && $next == $this->string_delimiter) {
                        $i ++;
                    } else if ($this->string_delimiter == '"' && $current == '\\') {
                        $i ++;
                    } else if ($this->string_delimiter == $current) {
                        $lastnospace = $current;
                        $pending = '';
                        $state = self::REGULAR;
                    } else if ($current == self::LF) {
                        $pending = '';
                        $this->linenumber ++;
                    }
                    break;
                case self::IN_NUMBER :
                    // Bug fixed 'e' => 'E'd by Lang Michael: michael.lang.ima10@fh-joanneum.at.
                    if (($current >= '0' && $current <= '9') ||
                    $current == '.' || $current == 'E' || $current == 'e') {
                        $pending .= $current;
                        break;
                    }
                    if (($current == '-' || $current == '+') && ($previous == 'E' || $previous == 'e')) {
                        $pending .= $current;
                        break;
                    }
                    $this->add_pending( $pending );
                    $state = self::REGULAR;
                    // Process current as REGULAR.
                case self::REGULAR :
                    if ($current == '%') {
                        if ($next == '{') {
                            $state = self::IN_COMMENT;
                        } else {
                            $state = self::IN_LINECOMMENT;
                        }
                        $this->add_pending( $pending );
                        break 2;
                    } else if ($current == '"') {
                        $state = self::IN_STRING;
                        $this->string_delimiter = '"';
                        $this->add_pending( $pending );
                        $pending = '""';
                        $this->add_pending( $pending );
                        break;
                    } else if ($current == "'" && ($lastnospace == self::LF
                            || $lastnospace == '' || strpos( "[,;'(=", $lastnospace ) !== false)) {
                        $state = self::IN_STRING;
                        $this->string_delimiter = "'";
                        $this->add_pending( $pending );
                        $pending = "''";
                        $this->add_pending( $pending );
                        break;
                    } else if ($current >= '0' && $current <= '9' && ! $this->is_indentifier( $pending )) {
                        $state = self::IN_NUMBER;
                        $this->add_pending( $pending );
                        $pending = $current;
                        break;
                    }
                    if (($current >= 'a' && $current <= 'z') || ($current >= 'A' && $current <= 'Z')
                            || $current == '_' || ord( $current ) > 127) {
                        $pending .= $current;
                    } else {
                        // TODO check level without { }.
                        $this->add_pending( $pending );
                        if ($current == self::LF) {
                            $this->linenumber ++;
                        } else {
                            $this->add_pending( $current );
                        }
                    }
            }
        }

        $this->add_pending( $pending );
        $this->compact_operators();
    }
    public function get_tokens() {
        return $this->tokens;
    }
    protected function compact_operators() {
        $correct = array ();
        $current = false;
        foreach ($this->tokens as &$next) {
            if ($current) {
                if ($current->type == vpl_token_type::OPERATOR && $next->type == vpl_token_type::OPERATOR) {
                    $current->value .= $next->value;
                    $next = false;
                }
                if (strpos( ')]}', $current->value ) === false) {
                    $correct[] = $current;
                }
            }
            $current = $next;
        }
        if ($current) {
            $correct[] = $current;
        }
        $this->tokens = $correct;
    }
    public function show_tokens() {
        foreach ($this->tokens as $token) {
            $token->show();
        }
    }
}
