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

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/tokenizer_base.class.php');
use mod_vpl\tokenizer\token;
use mod_vpl\tokenizer\token_type;

/**
 * Prolog programing language tokenizer class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
class vpl_tokenizer_prolog extends vpl_tokenizer_base {
    /**
     * @var array of reserved words in Prolog.
     */
    protected $reserved = null;

    /**
     * @var int the current line number in the file.
     */
    protected $linenumber;

    /**
     * @var array of tokens found in the file.
     */
    protected $tokens;

    /**
     * Check if there is a next open parenthesis in the string.
     *
     * @param string $s the string to check
     * @param int $ini the initial index to start checking
     * @return bool true if there is a next open parenthesis, false otherwise
     */
    protected function isnextopenparenthesis(&$s, $ini) {
        $l = strlen($s);
        for ($i = $ini; $i < $l; $i++) {
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

    /**
     * Check if the character is an identifier.
     *
     * @param string $c the character to check
     * @return bool true if it is an identifier, false otherwise
     */
    protected function isidentifierchar($c) {
        return ($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || ($c >= '0' && $c <= '9') || ($c == '_');
    }

    /**
     * Add a pending token to the list of tokens.
     *
     * @param string $rest the pending string to add
     * @param string|null $s the source string (optional)
     * @param int|null $i the index in the source string (optional)
     */
    protected function add_pending(&$rest, &$s = null, $i = null) {
        $rest = trim($rest);
        if (strlen($rest) == 0) {
            return;
        }
        $c = $rest[0];
        if ($this->isidentifierchar($c)) {
            if (($c >= 'A' && $c <= 'Z') || $c == '_') { // Variable.
                $this->tokens[] = new token(token_type::OPERATOR, 'V', $this->linenumber);
            } else if (($c >= 'a' && $c <= 'z')) { // Literal.
                if ($s != null && $this->isnextopenparenthesis($s, $i) || $rest == 'is') {
                    $this->tokens[] = new token(token_type::OPERATOR, 'L', $this->linenumber);
                }
            }
        } else {
            $this->tokens[] = new token(token_type::OPERATOR, $rest, $this->linenumber);
        }
        $rest = '';
    }

    /**
     * @var int regular state of the tokenizer.
     */
    const IN_REGULAR = 0;

    /**
     * @var int state of the tokenizer in string parsing.
     */
    const IN_STRING = 1;

    /**
     * @var int state of the tokenizer in char parsing.
     */
    const IN_CHAR = 2;

    /**
     * @var int state of the tokenizer in macro parsing.
     */
    const IN_MACRO = 3;

    /**
     * @var int state of the tokenizer in block comment parsing.
     */
    const IN_COMMENT = 4;

    /**
     * @var int state of the tokenizer in line comment parsing.
     */
    const IN_LINECOMMENT = 5;

    /**
     * @var int state of the tokenizer in identifier parsing.
     */
    const IN_IDENTIFIER = 6;

    /**
     * Parse the file data and extract tokens.
     * @param string $filedata the file data to parse
     */
    public function parse($filedata) {
        $this->tokens = [];
        $this->linenumber = 1;
        $state = self::IN_REGULAR;
        $pending = '';
        $l = strlen($filedata);
        $current = '';
        for ($i = 0; $i < $l; $i++) {
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
                case self::IN_REGULAR:
                case self::IN_IDENTIFIER:
                    if ($current == '/') {
                        if ($next == '*') { // Begin block comments.
                            $state = self::IN_COMMENT;
                            $this->add_pending($pending, $filedata, $i);
                            $i++;
                            continue 2;
                        }
                        break;
                    } else if ($current == '%') { // Begin line comment.
                        $this->add_pending($pending, $filedata, $i);
                        $state = self::IN_LINECOMMENT;
                        break;
                    } else if ($current == '"') {
                        $this->add_pending($pending, $filedata, $i);
                        $state = self::IN_STRING;
                        break;
                    } else if ($current == "'") {
                        $this->add_pending($pending, $filedata, $i);
                        $state = self::IN_CHAR;
                        break;
                    } else if ($this->isidentifierchar($current)) {
                        if ($state == self::IN_REGULAR) {
                            $this->add_pending($pending, $filedata, $i);
                            $state = self::IN_IDENTIFIER;
                        }
                    } else {
                        $this->add_pending($pending, $filedata, $i);
                        if ($state == self::IN_IDENTIFIER) {
                            $state = self::IN_REGULAR;
                        }
                        if ($current == self::LF) {
                            continue 2;
                        }
                    }
                    break;
                case self::IN_COMMENT:
                    // Check end of block comment.
                    if ($current == '*') {
                        if ($next == '/') {
                            $state = self::IN_REGULAR;
                            $pending = '';
                            $i++;
                            continue 2;
                        }
                    }
                    if ($current == self::LF) {
                        continue 2;
                    }
                    break;
                case self::IN_LINECOMMENT:
                    // Check end of comment.
                    if ($current == self::LF) {
                        $pending = '';
                        $state = self::IN_REGULAR;
                        continue 2;
                    }
                    break;
                case self::IN_STRING:
                case self::IN_CHAR:
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
                        $i++; // Skip next char.
                        continue 2;
                    }
                    break;
            }
            $pending .= $current;
        }
        $this->compactoperators();
    }

    /**
     * Compact operators in the token list.
     *
     * This method merges consecutive operator tokens into a single token
     * if they are not special characters like parentheses, brackets, etc.
     */
    protected function compactoperators() {
        $correct = [];
        $current = false;
        foreach ($this->tokens as &$next) {
            if ($current) {
                if (
                    $current->type == token_type::OPERATOR && $next->type == token_type::OPERATOR
                    && strpos('LV()[]{},.;', $current->value) === false
                    && strpos('LV()[]{},.;', $next->value) === false
                ) {
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

    /**
     * Get the list of tokens found in the file.
     *
     * @return token[] the list of tokens
     */
    public function get_tokens() {
        return $this->tokens;
    }
}
