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
 * Python programing language tokenizer class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Lang Michael <michael.lang.ima10@fh-joanneum.at>
 * @author Lückl Bernd <bernd.lueckl.ima10@fh-joanneum.at>
 * @author Lang Johannes <johannes.lang.ima10@fh-joanneum.at>
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname( __FILE__ ) . '/tokenizer_c.class.php');
class vpl_tokenizer_python extends vpl_tokenizer_c {

    protected static $pythonreserved = null;

    protected function is_text($text) {
        if (strlen( $text ) == 0) {
            return false;
        }
        $first = $text [0];
        return $first == '"' || $first == "'";
    }

    protected function add_pending(&$pending) {
        if ($pending <= ' ') {
            $pending = '';
            return;
        }
        if ($this->is_indentifier( $pending )) {
            if (isset( $this->reserved [$pending] )) {
                $type = vpl_token_type::RESERVED;
            } else {
                $type = vpl_token_type::IDENTIFIER;
            }
        } else {
            if ($this->is_number( $pending ) || $this->is_text( $pending )) {
                $type = vpl_token_type::LITERAL;
            } else {
                $type = vpl_token_type::OPERATOR;
            }
        }
        $this->tokens [] = new vpl_token( $type, $pending, $this->linenumber );
        $pending = '';
    }

    public function __construct() {
        if (self::$pythonreserved === null) {
            self::$pythonreserved = array (
                    'False' => true,
                    'class' => true,
                    'finally' => true,
                    'is' => true,
                    'return' => true,
                    'None' => true,
                    'continue' => true,
                    'for' => true,
                    'lambda' => true,
                    'try' => true,
                    'True' => true,
                    'def' => true,
                    'from' => true,
                    'nonlocal' => true,
                    'while' => true,
                    'and' => true,
                    'del' => true,
                    'global' => true,
                    'not' => true,
                    'with' => true,
                    'as' => true,
                    'elif' => true,
                    'if' => true,
                    'or' => true,
                    'yield' => true,
                    'assert' => true,
                    'else' => true,
                    'import' => true,
                    'pass' => true,
                    'break' => true,
                    'except' => true,
                    'in' => true,
                    'raise' => true
            );
        }
        $this->reserved = &self::$pythonreserved;
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
            $current = $filedata [$i];
            if ($i < ($l - 1)) {
                $next = $filedata [$i + 1];
            } else {
                $next = '';
            }
            if ($i < ($l - 2)) {
                $nextnext = $filedata [$i + 2];
            } else {
                $nextnext = '';
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
                    if ($current == '"' && $next == '"' && $nextnext == '"') {
                        $i += 2;
                        $state = self::REGULAR;
                        continue;
                    }
                    break;
                case self::IN_LINECOMMENT :
                    // Check end of comment.
                    if ($current == self::LF) {
                        $state = self::REGULAR;
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
                case self::IN_NUMBER :
                    if (($current >= '0' && $current <= '9') || $current == '.'
                        || $current == 'E' || $current == 'e') {
                        $pending .= $current;
                        continue;
                    }
                    if (($current == '-' || $current == '+') && ($previous == 'E' || $previous == 'e')) {
                        $pending .= $current;
                        continue;
                    }
                    $this->add_pending( $pending );
                    $state = self::REGULAR;
                    // Process current as REGULAR.
                case self::REGULAR :
                    if ($current == '"' && $next == '"' && $nextnext == '"') {
                        // Begin block comments.
                        $state = self::IN_COMMENT;
                        $this->add_pending( $pending );
                        $i += 2;
                        continue;
                    } else if ($current == '#') {
                        // Begin line comment.
                        $state = self::IN_LINECOMMENT;
                        $this->add_pending( $pending );
                        continue;
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
}
