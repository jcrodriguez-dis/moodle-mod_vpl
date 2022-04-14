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
 * C programing language tokenizer class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/tokenizer_base.class.php');

class vpl_tokenizer_c extends vpl_tokenizer_base {
    const REGULAR = 0;
    const IN_STRING = 1;
    const IN_CHAR = 2;
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
            if ($this->is_number( $pending )) {
                $type = vpl_token_type::LITERAL;
            } else {
                $type = vpl_token_type::OPERATOR;
            }
        }
        $this->tokens[] = new vpl_token( $type, $pending, $this->linenumber );
        $pending = '';
    }
    public function __construct() {
        if (self::$creserved === null) {
            self::$creserved = array (
                    'auto' => true,
                    'break' => true,
                    'case' => true,
                    'char' => true,
                    'const' => true,
                    'continue' => true,
                    'default' => true,
                    'do' => true,
                    'double' => true,
                    'else' => true,
                    'enum' => true,
                    'extern' => true,
                    'float' => true,
                    'for' => true,
                    'goto' => true,
                    'if' => true,
                    'inline' => true,
                    'int' => true,
                    'long' => true,
                    'register' => true,
                    'restrict' => true,
                    'return' => true,
                    'short' => true,
                    'signed' => true,
                    'sizeof' => true,
                    'static' => true,
                    'struct' => true,
                    'switch' => true,
                    'typedef' => true,
                    'union' => true,
                    'unsigned' => true,
                    'void' => true,
                    'volatile' => true,
                    'while' => true,
                    '_Bool' => true,
                    '_Complex' => true,
                    '_Imaginary' => true
            );
        }
        $this->reserved = &self::$creserved;
    }
    public function parse($filedata) {
        $this->tokens = array ();
        $this->linenumber = 1;
        $state = self::REGULAR;
        $pending = '';
        $firstnospace = '';
        $lastnospace = '';
        $l = strlen( $filedata );
        $current = '';
        $previous = '';
        for ($i = 0; $i < $l; $i ++) {
            $previous = $current;
            $current = $filedata[$i];
            if ($i < ($l - 1)) {
                $next = $filedata[$i + 1];
            } else {
                $next = '';
            }
            if ($previous == self::LF) {
                $lastnospace = '';
                $firstnospace = '';
                $this->linenumber ++;
            }
            if ($current == self::CR) {
                if ($next == self::LF) {
                    continue;
                } else {
                    $this->linenumber ++;
                    $current = self::LF;
                }
            }
            if ($current != ' ' && $current != "\t") { // Keep first and last no space char.
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
                            $i ++;
                            $state = self::REGULAR;
                            break;
                        }
                    }
                    break;
                case self::IN_LINECOMMENT :
                    // Check end of comment.
                    if ($current == self::LF) {
                        $state = self::REGULAR;
                    }
                    break;
                case self::IN_MACRO :
                    // Check end of macro.
                    if ($current == self::LF) {
                        if ($lastnospace != '\\') {
                            $state = self::REGULAR;
                        }
                    }
                    break;
                case self::IN_STRING :
                    // Check end of string.
                    if ($current == '"' && $previous != '\\') {
                        $state = self::REGULAR;
                        break;
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
                        $state = self::REGULAR;
                        break;
                    }
                    // Discard two backslash.
                    if ($current == '\\' && $previous == '\\') {
                        $current = ' ';
                    }
                    break;
                case self::IN_NUMBER : // Bug fixed by Lang Michael: michael.lang.ima10@fh-joanneum.at 'e' => 'E'.
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
                    if ($current == '/') {
                        if ($next == '*') { // Begin block comments.
                            $state = self::IN_COMMENT;
                            $this->add_pending( $pending );
                            $i ++;
                            break;
                        }
                        if ($next == '/') { // Begin line comment.
                            $state = self::IN_LINECOMMENT;
                            $this->add_pending( $pending );
                            $i ++;
                            break;
                        }
                    } else if ($current == '"') {
                        $state = self::IN_STRING;
                        $this->add_pending( $pending );
                        break;
                    } else if ($current == "'") {
                        $state = self::IN_CHAR;
                        $this->add_pending( $pending );
                        break;
                    } else if ($current == '#' && $firstnospace == $current) {
                        $state = self::IN_MACRO;
                        $this->add_pending( $pending );
                        break;
                    } else if ($current >= '0' && $current <= '9') {
                        $state = self::IN_NUMBER;
                        $this->add_pending( $pending );
                        $pending = $current;
                        break;
                    }
                    if (($current >= 'a' && $current <= 'z') || ($current >= 'A' && $current <= 'Z')
                         || $current == '_' || ord( $current ) > 127) {
                        $pending .= $current;
                    } else {
                        $this->add_pending( $pending );
                        if ($current > ' ') {
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
    public function compact_operators() {
        $correct = array ();
        $current = false;
        foreach ($this->tokens as &$next) {
            if ($current) {
                if ($current->type == vpl_token_type::OPERATOR && $next->type == vpl_token_type::OPERATOR
                        && strpos( '()[]{};', $current->value ) === false && strpos( '()[]{};', $next->value ) === false) {
                            $current->value .= $next->value;
                            $next = false;
                }
                $correct[] = $current;
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
