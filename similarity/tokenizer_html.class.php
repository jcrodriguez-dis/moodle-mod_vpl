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
 * HTML language tokenizer class
 *
 * @package mod_vpl
 * @copyright 2015 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/tokenizer_base.class.php');

// TODO Implement javascript parser.

class vpl_tokenizer_html extends vpl_tokenizer_base {
    const REGULAR = 0;
    const IN_STRING = 1;
    const IN_COMMENT = 2;
    const IN_TAGNAME = 3;
    const IN_TAGEND = 4;
    const IN_TAGATTRNAME = 5;
    const IN_TAGATTRVALUE = 6;
    protected $tokens;
    protected function add_pending(&$rawpending, $state) {
        $pending = strtolower( $rawpending );
        $rawpending = '';
        if ($state == self::IN_TAGATTRVALUE) {
            return;
        }
        if ($state == self::IN_TAGEND) {
            $pending .= '/';
        }
        $this->tokens [] = new vpl_token( vpl_token_type::OPERATOR, $pending, $this->line_number );
    }
    public function parse($filedata) {
        $this->tokens = array ();
        $this->line_number = 1;
        $state = self::REGULAR;
        $pending = '';
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
                $this->line_number ++;
            }
            if ($current == self::CR) {
                if ($next == self::LF) {
                    continue;
                } else {
                    $this->line_number ++;
                    $current = self::LF;
                }
            }
            switch ($state) {
                case self::IN_COMMENT :
                    // Check end of comment.
                    if ($current == '>' && $i > 6 && substr( $filedata, $i - 2, 2 ) == '--') {
                        $state = self::REGULAR;
                    }
                    break;
                case self::IN_STRING :
                    // Check end of string.
                    if ($current == $endstring) {
                        $state = self::IN_TAGATTRNAME;
                    }
                    break;
                case self::REGULAR :
                    if ($current == '<') {
                        if ($next == '!' && $i + 3 < $l && substr( $filedata, $i + 2, 2 ) == '--') {
                            $state = self::IN_COMMENT;
                            $i += 3;
                        } else {
                            $state = self::IN_TAGNAME;
                        }
                    }
                    break;
                case self::IN_TAGEND :
                case self::IN_TAGNAME :
                    if ($current == '/') {
                        $state = self::IN_TAGEND;
                        break;
                    }
                    if (ctype_alpha( $current )) {
                        $pending .= $current;
                    } else if ($pending > '') {
                        $this->add_pending( $pending, $state );
                        $state = self::IN_TAGATTRNAME;
                        $i --;
                    }
                    break;
                case self::IN_TAGATTRNAME :
                case self::IN_TAGATTRVALUE :
                    if (ctype_alnum( $current ) || strpos( '-_$', $current ) !== false) {
                        $pending .= $current;
                    } else if ($pending > '') {
                        $this->add_pending( $pending, $state );
                        $state = self::IN_TAGATTRNAME;
                        $i --;
                    }
                    if ($current == '"' || $current == "'") {
                        $state = self::IN_STRING;
                        $endstring = $current;
                    }
                    if ($current == '=') {
                        $state = self::IN_TAGATTRVALUE;
                    }
                    if ($current == '>') {
                        $state = self::REGULAR;
                    }
                    break;
            }
        }
    }
    public function get_tokens() {
        return $this->tokens;
    }
    public function show_tokens() {
        foreach ($this->tokens as $token) {
            $token->show();
        }
    }
}
