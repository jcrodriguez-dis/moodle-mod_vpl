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

require_once(dirname(__FILE__) . '/similarity_c.class.php');
use mod_vpl\tokenizer\token;
use mod_vpl\tokenizer\token_type;
use mod_vpl\tokenizer\tokenizer_factory;

/**
 * Java language similarity class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
class vpl_similarity_java extends vpl_similarity_c {
    /**
     * Returns the type of similarity.
     *
     * @return int The type of similarity, which is 3 for Java.
     */
    public function get_type() {
        return 3;
    }

    /**
     * Normalizes the syntax of the given tokens.
     *
     * @param array $tokens The tokens to normalize.
     * @return array The normalized tokens.
     */
    public function sintax_normalize(&$tokens) {
        $openbrace = false;
        $nsemicolon = 0;
        $ret = [];
        $prev = new token(token_type::IDENTIFIER, '', 0);
        foreach ($tokens as $token) {
            if ($token->type == token_type::OPERATOR) {
                // Operators "++" and "--" .
                // Operator "::" .
                // Expresion "(*p)." and "p->" .
                // Operators +=, -=, *=, etc.
                switch ($token->value) {
                    case '[':
                        // Only add ].
                        break;
                    case '(':
                        // Only add ).
                        break;
                    case '{':
                        // Only add }.
                        $nsemicolon = 0;
                        $openbrace = true;
                        break;
                    case '}':
                        // Remove unneeded {}.
                        if (! ($openbrace && $nsemicolon < 2)) {
                            $ret[] = $token;
                        }
                        $openbrace = false;
                        break;
                    case ';':
                        // Count semicolon after a {.
                        $nsemicolon++;
                        $ret[] = $token;
                        break;
                    case '++':
                        $ret[] = self::clone_token($token, '=');
                        $token->value = '+';
                        $ret[] = $token;
                        break;
                    case '--':
                        $ret[] = self::clone_token($token, '=');
                        $token->value = '-';
                        $ret[] = $token;
                        break;
                    case '+=':
                        $ret[] = self::clone_token($token, '=');
                        $token->value = '+';
                        $ret[] = $token;
                        break;
                    case '-=':
                        $ret[] = self::clone_token($token, '=');
                        $token->value = '-';
                        $ret[] = $token;
                        break;
                    case '*=':
                        $ret[] = self::clone_token($token, '=');
                        $token->value = '*';
                        $ret[] = $token;
                        break;
                    case '/=':
                        $ret[] = self::clone_token($token, '=');
                        $token->value = '/';
                        $ret[] = $token;
                        break;
                    case '%=':
                        $ret[] = self::clone_token($token, '=');
                        $token->value = '%';
                        $ret[] = $token;
                        break;
                    case '.':
                        if ($prev->value == 'this') {
                            break;
                        }
                        // No break.
                    case '::':
                        break;
                    default:
                        $ret[] = $token;
                }
                $prev = $token;
            }
            // TODO remove "(p)" .
        }
        return $ret;
    }

    /**
     * Returns the tokenizer for the Java language.
     *
     * @return vpl_tokenizer The tokenizer instance for Java.
     */
    public function get_tokenizer() {
        return tokenizer_factory::get('java');
    }
}
