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
 * Prolog programing language tokenizer class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname( __FILE__ ) . '/tokenizer_base.class.php');

class vpl_tokenizer_prolog extends vpl_tokenizer_base {
    protected $reserved = null;
    protected $linenumber;
    protected $tokens;
    protected function isnextopenparenthesis(& $s, $ini) {
        $l = strlen( $s );
        for ($i = $ini; $i < $l; $i ++) {
            $c = $s[$i];
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
    protected function add_pending(&$rest, &$s = null, $i = null) {
        $rest = trim( $rest );
        if (strlen( $rest ) == 0) {
            return;
        }
        $c = $rest[0];
        if ($this->isidentifierchar( $c )) {
            if (($c >= 'A' && $c <= 'Z') || $c == '_') { // Variable.
                $this->tokens[] = new vpl_token( vpl_token_type::OPERATOR, 'V', $this->linenumber );
            } else if (($c >= 'a' && $c <= 'z')) { // Literal.
                if ($s != null && $this->isnextopenparenthesis( $s, $i ) || $rest == 'is') {
                    $this->tokens[] = new vpl_token( vpl_token_type::OPERATOR, 'L', $this->linenumber );
                }
            }
        } else {
            $this->tokens[] = new vpl_token( vpl_token_type::OPERATOR, $rest, $this->linenumber );
        }
        $rest = '';
    }
    const IN_REGULAR = 0;
    const IN_STRING = 1;
    const IN_CHAR = 2;
    const IN_MACRO = 3;
    const IN_COMMENT = 4;
    const IN_LINECOMMENT = 5;
    const IN_IDENTIFIER = 6;
    public function parse($filedata) {
        $this->tokens = array ();
        $this->linenumber = 1;
        $state = self::IN_REGULAR;
        $pending = '';
        $l = strlen( $filedata );
        $current = '';
        for ($i = 0; $i < $l; $i ++) {
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
            switch ($state) {
                case self::IN_REGULAR :
                case self::IN_IDENTIFIER :
                    if ($current == '/') {
                        if ($next == '*') { // Begin block comments.
                            $state = self::IN_COMMENT;
                            $this->add_pending( $pending, $filedata, $i );
                            $i ++;
                            continue 2;
                        }
                        break;
                    } else if ($current == '%') { // Begin line comment.
                        $this->add_pending( $pending, $filedata, $i );
                        $state = self::IN_LINECOMMENT;
                        break;
                    } else if ($current == '"') {
                        $this->add_pending( $pending, $filedata, $i );
                        $state = self::IN_STRING;
                        break;
                    } else if ($current == "'") {
                        $this->add_pending( $pending, $filedata, $i );
                        $state = self::IN_CHAR;
                        break;
                    } else if ($this->isidentifierchar( $current )) {
                        if ($state == self::IN_REGULAR) {
                            $this->add_pending( $pending, $filedata, $i );
                            $state = self::IN_IDENTIFIER;
                        }
                    } else {
                        $this->add_pending( $pending, $filedata, $i );
                        if ($state == self::IN_IDENTIFIER) {
                            $state = self::IN_REGULAR;
                        }
                        if ($current == self::LF) {
                            continue 2;
                        }
                    }
                    break;
                case self::IN_COMMENT :
                    // Check end of block comment.
                    if ($current == '*') {
                        if ($next == '/') {
                            $state = self::IN_REGULAR;
                            $pending = '';
                            $i ++;
                            continue 2;
                        }
                    }
                    if ($current == self::LF) {
                        continue 2;
                    }
                    break;
                case self::IN_LINECOMMENT :
                    // Check end of comment.
                    if ($current == self::LF) {
                        $pending = '';
                        $state = self::IN_REGULAR;
                        continue 2;
                    }
                    break;
                case self::IN_STRING :
                case self::IN_CHAR :
                    // Check end of string.
                    if ($state == self::IN_STRING && $current == '"') {
                        $pending = '';
                        $state = self::IN_REGULAR;
                        continue 2;
                    }
                    // Check end of char.
                    if ($state == self::IN_CHAR && $current == '\'') {
                        $pending = '';
                        $state = self::IN_REGULAR;
                        continue 2;
                    }
                    if ($current == self::LF) {
                        $pending = '';
                        continue 2;
                    }
                    // Discard two backslash.
                    if ($current == '\\') {
                        $i ++; // Skip next char.
                        continue 2;
                    }
                    break;
            }
            $pending .= $current;
        }
        $this->compactoperators();
    }
    protected function compactoperators() {
        $correct = array ();
        $current = false;
        foreach ($this->tokens as &$next) {
            if ($current) {
                if ($current->type == vpl_token_type::OPERATOR && $next->type == vpl_token_type::OPERATOR
                    && strpos( 'LV()[]{},.;', $current->value ) === false
                    && strpos( 'LV()[]{},.;', $next->value ) === false) {
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
    public function get_tokens() {
        return $this->tokens;
    }
}
