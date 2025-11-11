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
 * M (Octave) programing language tokenizer class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
class vpl_tokenizer_matlab extends vpl_tokenizer_base {
    /**
     * @var int State for regular parsing.
     * This state is used when the tokenizer is not currently processing a string, macro, comment,
     * line comment, or number. It is the default state for parsing.
     */
    const REGULAR = 0;

    /**
     * @var int State for parsing strings.
     */
    const IN_STRING = 1;

    /**
     * @var int State for parsing macros.
     */
    const IN_MACRO = 3;

    /**
     * @var int State for parsing block comments.
     */
    const IN_COMMENT = 4;

    /**
     * @var int State for parsing line comments.
     */
    const IN_LINECOMMENT = 5;

    /**
     * @var int State for parsing numbers.
     * This state is used when the tokenizer is currently processing a number.
     */
    const IN_NUMBER = 6;

    /**
     * @var array Reserved words for MATLAB/Octave.
     * This is a static variable to avoid re-initializing the reserved words
     */
    protected static $creserved = null;

    /**
     * @var int current Line number.
     */
    protected $linenumber;

    /**
     * @var array Reserved words for MATLAB/Octave.
     */
    protected $tokens;

    /**
     * @var string String delimiter used for string literals.
     * This can be either a single quote (') or a double quote (").
     * It is set when entering an IN_STRING state.
     */
    protected $stringdelimiter;

    /**
     * Check if the given text is a valid identifier.
     *
     * @param string $text The text to check.
     * @return bool True if the text is a valid identifier, false otherwise.
     */
    protected function is_indentifier($text) {
        if (strlen($text) == 0) {
            return false;
        }
        $first = $text[0];
        return ($first >= 'a' && $first <= 'z') || ($first >= 'A' && $first <= 'Z') || $first == '_';
    }

    /**
     * Check if the given text is a number.
     *
     * @param string $text The text to check.
     * @return bool True if the text is a number, false otherwise.
     */
    protected function is_number($text) {
        if (strlen($text) == 0) {
            return false;
        }
        $first = $text[0];
        if ($first == '.' && strlen($text) > 1) {
            $first = $text[1];
        }
        return $first >= '0' && $first <= '9';
    }

    /**
     * Add a pending token to the list of tokens.
     *
     * @param string $pending The pending token to add.
     */
    protected function add_pending(&$pending) {
        if ($pending <= ' ') {
            $pending = '';
            return;
        }
        if ($this->is_indentifier($pending)) {
            if (isset($this->reserved[$pending])) {
                $type = token_type::RESERVED;
            } else {
                $type = token_type::IDENTIFIER;
            }
        } else {
            if ($this->is_number($pending) || $pending == '""' || $pending == "''") {
                $type = token_type::LITERAL;
            } else if (strpos('()[]{};', $pending) === false) {
                $type = token_type::OPERATOR;
            } else {
                $type = token_type::OTHER;
            }
        }
        $this->tokens[] = new token($type, $pending, $this->linenumber);
        $pending = '';
    }

    /**
     * Constructor.
     *
     * Initializes the reserved words for MATLAB/Octave.
     */
    public function __construct() {
        if (self::$creserved === null) {
            self::$creserved = [ // Source MATLAB Quick Reference Author: Jialong He.
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
                    "and" => true,
            ];
        }
        $this->reserved = &self::$creserved;
    }

    /**
     * Parse the given file data and tokenize it.
     *
     * @param string $filedata The content of the file to parse.
     */
    public function parse($filedata) {
        $this->tokens = [];
        $state = self::REGULAR;
        $pending = '';
        $lastnospace = self::LF;
        $l = strlen($filedata);
        if ($l) {
            $this->linenumber++;
        }
        $current = self::LF;
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
            if (! ctype_space($previous) || $previous == self::LF) { // Keep last char.
                $lastnospace = $previous;
            }
            switch ($state) {
                case self::IN_LINECOMMENT:
                    // Check end of comment.
                    if ($current == self::LF) {
                        $pending = '';
                        $this->linenumber++;
                        $state = self::REGULAR;
                    }
                    break;
                case self::IN_COMMENT:
                    // Check end of comment.
                    if ($current == self::LF) {
                        $pending = '';
                        $this->linenumber++;
                    } else if ($current == '%' && $next == '}') {
                        $pending = '';
                        $i++;
                        $state = self::REGULAR;
                    }
                    break;
                case self::IN_STRING:
                    // Check end of string.
                    if ($current == $this->stringdelimiter && $next == $this->stringdelimiter) {
                        $i++;
                    } else if ($this->stringdelimiter == '"' && $current == '\\') {
                        $i++;
                    } else if ($this->stringdelimiter == $current) {
                        $lastnospace = $current;
                        $pending = '';
                        $state = self::REGULAR;
                    } else if ($current == self::LF) {
                        $pending = '';
                        $this->linenumber++;
                    }
                    break;
                case self::IN_NUMBER:
                    // Bug fixed 'e' => 'E'd by Lang Michael: michael.lang.ima10@fh-joanneum.at.
                    if (
                        ($current >= '0' && $current <= '9') ||
                        $current == '.' || $current == 'E' || $current == 'e'
                    ) {
                        $pending .= $current;
                        break;
                    }
                    if (($current == '-' || $current == '+') && ($previous == 'E' || $previous == 'e')) {
                        $pending .= $current;
                        break;
                    }
                    $this->add_pending($pending);
                    $state = self::REGULAR;
                    // Process current as REGULAR.
                case self::REGULAR:
                    if ($current == '%') {
                        if ($next == '{') {
                            $state = self::IN_COMMENT;
                        } else {
                            $state = self::IN_LINECOMMENT;
                        }
                        $this->add_pending($pending);
                        break 2;
                    } else if ($current == '"') {
                        $state = self::IN_STRING;
                        $this->stringdelimiter = '"';
                        $this->add_pending($pending);
                        $pending = '""';
                        $this->add_pending($pending);
                        break;
                    } else if (
                        $current == "'" && ($lastnospace == self::LF
                            || $lastnospace == '' || strpos("[,;'(=", $lastnospace) !== false)
                    ) {
                        $state = self::IN_STRING;
                        $this->stringdelimiter = "'";
                        $this->add_pending($pending);
                        $pending = "''";
                        $this->add_pending($pending);
                        break;
                    } else if ($current >= '0' && $current <= '9' && ! $this->is_indentifier($pending)) {
                        $state = self::IN_NUMBER;
                        $this->add_pending($pending);
                        $pending = $current;
                        break;
                    }
                    if (
                        ($current >= 'a' && $current <= 'z') || ($current >= 'A' && $current <= 'Z')
                            || $current == '_' || ord($current) > 127
                    ) {
                        $pending .= $current;
                    } else {
                        // TODO check level without { }.
                        $this->add_pending($pending);
                        if ($current == self::LF) {
                            $this->linenumber++;
                        } else {
                            $this->add_pending($current);
                        }
                    }
            }
        }

        $this->add_pending($pending);
        $this->compact_operators();
    }

    /**
     * Get the list of tokens.
     *
     * @return token[] List of tokens.
     */
    public function get_tokens() {
        return $this->tokens;
    }

    /**
     * Compact operators in the token list.
     */
    protected function compact_operators() {
        $correct = [];
        $current = false;
        foreach ($this->tokens as &$next) {
            if ($current) {
                if ($current->type == token_type::OPERATOR && $next->type == token_type::OPERATOR) {
                    $current->value .= $next->value;
                    $next = false;
                }
                if (strpos(')]}', $current->value) === false) {
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

    /**
     * Show the list of tokens.
     */
    public function show_tokens() {
        foreach ($this->tokens as $token) {
            $token->show();
        }
    }
}
