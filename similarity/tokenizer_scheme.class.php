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
 * Scheme programing language tokenizer class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/tokenizer_base.class.php');
use mod_vpl\tokenizer\token;
use mod_vpl\tokenizer\token_type;

/**
 * Class to tokenize Scheme programs
 *
 * This class extends the vpl_tokenizer_base class to provide specific
 * tokenization for Scheme programming language.
 */
class vpl_tokenizer_scheme extends vpl_tokenizer_base {
    /**
     * @var array list of reserved words
     */
    protected $reserved = null;

    /**
     * @var int current line number
     * This is used to report the line number of the token found.
     */
    protected $linenumber;

    /**
     * @var array tokens found
     */
    protected $tokens;

    /**
     * Constructor
     * Initialize the reserved words for Scheme
     */
    public function __construct() {
        // TODO need more reserved and functions.
        $list = [
                'define',
                'if',
                'cond',
                'else',
                'let',
                'eq?',
                'eqv?',
                'equal?',
                'and',
                'or',
                'letrec',
                'let-syntax',
                'letrec-sintax',
                'begin',
                'do',
                'quote',
                '+',
                '-',
                '*',
                '/',
                'sqrt',
                'eval',
                'car',
                'cdr',
                'list',
                'cons',
                'null?',
                'list?',
                '=',
                '<>',
                '<=',
                '>=',
                '<',
                '>',
                'lambda',
                'not',
        ];
        $this->reserved = [];
        foreach ($list as $word) {
            $this->reserved[$word] = 1;
        }
    }

    /**
     * Check if the previous character is an open parenthesis
     *
     * @param string $string the string to check
     * @param int $pos position in the string to check
     * @return bool true if the previous character is an open parenthesis, false otherwise
     */
    protected function is_previous_open_parenthesis(&$string, $pos) {
        for (; $pos >= 0; $pos--) {
            $char = $string[$pos];
            if ($char == '(') {
                return true;
            }
            if ($char != ' ' && $char != self::TAB && $char != self::LF && $char != self::CR) {
                return false;
            }
        }
        return false;
    }

    /**
     * Check if the text is an identifier
     *
     * @param string $text the text to check
     * @return bool true if it is an identifier, false otherwise
     */
    protected function is_indentifier($text) {
        if (strlen($text) == 0) {
            return false;
        }
        $first = $text[0];
        return ($first >= 'a' && $first <= 'z') || ($first >= 'A' && $first <= 'Z') || $first == '_';
    }

    /**
     * Check if the text is a number
     *
     * @param string $text the text to check
     * @return bool true if it is a number, false otherwise
     */
    protected function is_number($text) {
        if (strlen($text) == 0) {
            return false;
        }
        $first = $text[0];
        return $first >= '0' && $first <= '9';
    }

    /**
     * Add a parenthesis to the list of tokens
     */
    protected function add_parenthesis() {
        $this->tokens[] = new token(token_type::OPERATOR, '(', $this->linenumber);
    }

    /**
     * Add a parameter to the list of tokens
     *
     * @param string $pending the parameter
     */
    protected function add_parameter_pending(&$pending) {
        if ($pending <= ' ') {
            $pending = '';
            return;
        }
        $this->tokens[] = new token(token_type::LITERAL, $pending, $this->linenumber);
        $pending = '';
    }

    /**
     * Add a function to the list of tokens
     *
     * @param string $pending function name
     */
    protected function add_function_pending(&$pending) {
        if ($pending <= ' ') {
            $pending = '';
            return;
        }
        if (isset($this->reserved[$pending])) {
            $type = token_type::OPERATOR;
        } else {
            $type = token_type::IDENTIFIER;
        }
        $this->tokens[] = new token($type, $pending, $this->linenumber);
        $pending = '';
    }

    /**
     * @var int parser in regular state
     */
    const IN_REGULAR = 0;

    /**
     * @var int parser state for be inside of a string
     */
    const IN_STRING = 1;

    /**
     * @var int parser state for be inside of a character
     */
    const IN_CHAR = 2;

    /**
     * @var int parser state for be inside of a comment
     */
    const IN_COMMENT = 4;

    /**
     * Parse a Scheme file and return the tokens found
     *
     * @param string $filedata content of the file to parse
     */
    public function parse($filedata) {
        $this->tokens = [];
        $this->linenumber = 1;
        $state = self::IN_REGULAR;
        $pending = '';
        $l = strlen($filedata);
        $current = '';
        $pospendig = 0;
        for ($i = 0; $i < $l; $i++) {
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
            switch ($state) {
                case self::IN_COMMENT:
                    if ($current == self::LF) {
                        $state = self::IN_REGULAR;
                    }
                    break;
                case self::IN_STRING:
                    if ($current == '"' && $previous != "\\") {
                        $state = self::IN_REGULAR;
                    }
                    break;
                case self::IN_CHAR:
                    if (! ctype_alpha($current) && $current != '-') {
                        $state = self::IN_REGULAR;
                        $i--;
                        break; // Reprocess current char.
                    }
                    break;
                case self::IN_REGULAR:
                    if (
                        ($current != ' ') && ($current != '(') && ($current != ')') && ($current != ';')
                        && ($current != '"') && ($current != self::LF) && ($current != self::TAB)
                    ) {
                        if ($pending == '') {
                            $pospendig = $i;
                        }
                        $pending .= $current;
                    } else {
                        if (strlen($pending)) {
                            if ($this->is_previous_open_parenthesis($filedata, $pospendig - 1)) {
                                $this->add_function_pending($pending);
                            } else {
                                $this->add_parameter_pending($pending);
                            }
                        }
                        if ($current == '(') {
                            $this->add_parenthesis();
                        }
                        if ($current == ';') {
                            $state = self::IN_COMMENT;
                        } else if ($current == '"') {
                            $state = self::IN_STRING;
                        } else if ($current == '#' && $next == '\\') {
                            $state = self::IN_CHAR;
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Get the tokens found in the file
     *
     * @return array of token objects
     */
    public function get_tokens() {
        return $this->tokens;
    }
}
