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
 * VPL Syntaxhighlighter for Scheme language
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/sh_text.class.php');

class vpl_sh_scheme extends vpl_sh_text {
    public function __construct() {
        parent::__construct();
        // TODO need more reserved words and functions.
        $list = array (
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
                'not'
        );
        $this->reserved = array ();
        foreach ($list as $word) {
            $this->reserved [$word] = 1;
        }
    }
    public function is_previous_open_parenthesis(& $string, $pos) {
        for (; $pos >= 0; $pos --) {
            $char = $string [$pos];
            if ($char == '(') {
                return true;
            }
            if ($char != ' ' && $char != self::TAB && $char != self::LF && $char != self::CR) {
                return false;
            }
        }
        return false;
    }
    const IN_REGULAR = 0;
    const IN_STRING = 1;
    const IN_CHAR = 2;
    const IN_COMMENT = 4;
    public function print_file($filename, $filedata, $showln = true) {
        $this->begin( $filename, $showln );
        $state = self::IN_REGULAR;
        $pending = '';
        $previousisopenparenthesis = false;
        $l = strlen( $filedata );
        if ($l) {
            $this->show_line_number();
        }
        $current = '';
        $pospendig = 0;
        for ($i = 0; $i < $l; $i ++) {
            $previous = $current;
            $current = $filedata [$i];
            if ($i < ($l - 1)) {
                $next = $filedata [$i + 1];
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

                case self::IN_COMMENT :
                    if ($current == self::LF) {
                        $this->show_pending( $pending );
                        $this->endTag();
                        $state = self::IN_REGULAR;
                        $this->show_text( $current );
                        $this->show_line_number();
                    } else {
                        $pending .= $current;
                    }

                    break;
                case self::IN_STRING :
                    $pending .= $current;
                    if ($current == '"' && $previous != "\\") {
                        $this->show_pending( $pending );
                        $this->endTag();
                        $state = self::IN_REGULAR;
                    }
                    if ($current == self::LF) {
                        $this->show_line_number();
                    }
                    break;
                case self::IN_CHAR :
                    $pending .= $current;
                    if (! ctype_alpha( $current ) && $current != '-') {
                        $this->show_pending( $pending );
                        $this->endTag();
                        $state = self::IN_REGULAR;
                        $i --;
                        continue; // Reprocess current char.
                    }
                    break;
                case self::IN_REGULAR :

                    if (($current != ' ') && ($current != '(') && ($current != ')')
                        && ($current != ';') && ($current != '"')
                        && ($current != self::LF) && ($current != self::TAB)) {
                        if ($pending == '') {
                            $pospendig = $i;
                        }
                        $pending .= $current;
                    } else {
                        if (strlen( $pending )) {
                            if ($this->is_previous_open_parenthesis( $filedata, $pospendig - 1 )) {
                                if (array_key_exists( $pending, $this->reserved )) {
                                    $class = self::c_reserved;
                                } else {
                                    $class = self::c_variable;
                                }
                                $this->initTag( $class );
                                $this->show_pending( $pending );
                                $this->endTag();
                            } else {
                                if ($pending == "#t" || $pending == "#f") {
                                    $this->initTag( self::c_reserved );
                                    $this->show_pending( $pending );
                                    $this->endTag();
                                } else {
                                    $this->show_pending( $pending );
                                }
                            }
                        }
                        if ($current == ';') {
                            $state = self::IN_COMMENT;
                            $this->initTag( self::c_comment );
                        } else if ($current == '"') {
                            $state = self::IN_STRING;
                            $this->initTag( self::c_string );
                        } else if ($current == '#' && $next == '\\') {
                            $state = self::IN_CHAR;
                            $this->initTag( self::c_string );
                        }
                        if ($current == '(') {
                            $this->initHover();
                        }
                        $this->show_text( $current );
                        if ($current == ')') {
                            $this->endHover();
                        }
                        if ($current == self::LF) {
                            $this->show_line_number();
                        }
                    }
                    break;
            }
        }
        if (strlen( $pending )) {
            $this->show_pending( $pending );
        }
        if ($state != self::IN_REGULAR) {
            $this->endTag();
        }
        $this->end();
    }
}
