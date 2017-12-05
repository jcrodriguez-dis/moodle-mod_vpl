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
 * Scala programing language tokenizer class
 *
 * @package mod_vpl
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Lang Michael <michael.lang.ima10@fh-joanneum.at>
 * @author Lückl Bernd <bernd.lueckl.ima10@fh-joanneum.at>
 * @author Lang Johannes <johannes.lang.ima10@fh-joanneum.at>
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname( __FILE__ ) . '/tokenizer_c.class.php');
class vpl_tokenizer_scala extends vpl_tokenizer_c {
    protected static $sreserved = null;
    public function __construct() {
        if (self::$sreserved === null) {
            self::$sreserved = array (
                    'abstract' => true,
                    'case' => true,
                    'catch' => true,
                    'class' => true,
                    'def' => true,
                    'do' => true,
                    'else' => true,
                    'extends' => true,
                    'false' => true,
                    'final' => true,
                    'finally' => true,
                    'for' => true,
                    'forSome' => true,
                    'if' => true,
                    'implicit' => true,
                    'import' => true,
                    'lazy' => true,
                    'match' => true,
                    'new' => true,
                    'null' => true,
                    'object' => true,
                    'override' => true,
                    'package' => true,
                    'private' => true,
                    'protected' => true,
                    'return' => true,
                    'sealed' => true,
                    'super' => true,
                    'this' => true,
                    'throw' => true,
                    'trait' => true,
                    'try' => true,
                    'true' => true,
                    'type' => true,
                    'val' => true,
                    'var' => true,
                    'while' => true,
                    'with' => true,
                    'yield' => true,
                    'Byte' => true,
                    'Short' => true,
                    'Char' => true,
                    'Int' => true,
                    'Long' => true,
                    'Float' => true,
                    'Double' => true,
                    'Boolean' => true,
                    'Unit' => true,
                    'String' => true
            );
        }
        $this->reserved = &self::$sreserved;
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
                            continue;
                        }
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
                    if (($current >= '0' && $current <= '9') || $current == '.' || $current == 'E' || $current == 'e') {
                        $pending .= $current;
                        continue;
                    }
                    if (($current == '-' || $current == '+') && ($previous == 'E' || $previous == 'e')) {
                        $pending .= $current;
                        continue;
                    }
                    $this->add_pending( $pending );
                    $state = self::REGULAR;
                    // Process current as regular.
                case self::regular :
                    if ($current == '/') {
                        if ($next == '*') { // Begin block comments.
                            $state = self::IN_COMMENT;
                            $this->add_pending( $pending );
                            $i ++;
                            continue;
                        }
                        if ($next == '/') { // Begin line comment.
                            $state = self::IN_LINECOMMENT;
                            $this->add_pending( $pending );
                            $i ++;
                            continue;
                        }
                    } else if ($current == '"') {
                        $state = self::IN_STRING;
                        $this->add_pending( $pending );
                        break;
                    } else if ($current == "'") {
                        $state = self::IN_CHAR;
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
