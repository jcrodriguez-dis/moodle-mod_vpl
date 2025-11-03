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

use mod_vpl\similarity\similarity_base;
use mod_vpl\tokenizer\token;
use mod_vpl\tokenizer\token_type;
use mod_vpl\tokenizer\tokenizer_factory;

/**
 * C language similarity class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
class vpl_similarity_c extends similarity_base {
    /**
     * Returns the type of similarity.
     *
     * @return int The type of similarity, which is 1 for C.
     */
    public function get_type() {
        return 1;
    }

    /**
     * Expands the operator in the array of tokens.
     *
     * @param array $array The array of tokens.
     * @param int $from The index from which to expand the operator.
     */
    public static function expand_operator(&$array, &$from) {
        $last = count($array) - 1; // Array alredy with equal =.
        for ($i = $from; $i < $last; $i++) { // Replicate from last instruction to =.
            $array[] = $array[$i];
        }
        $from = count($array) + 1;
    }

    /**
     * Normalizes the syntax of the given tokens.
     *
     * @param array $tokens The tokens to normalize.
     * @return array The normalized tokens.
     */
    public function sintax_normalize(&$tokens) {
        $posiniinst = 0;
        $openbrace = false;
        $nsemicolon = 0;
        $ret = [];
        $prev = new token(token_type::IDENTIFIER, '', 0);
        foreach ($tokens as $token) {
            if ($token->type == token_type::OPERATOR) {
                // Operator ++ and -- .
                // Operator ::
                // Changes (*p). and p-> .
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
                        $posiniinst = count($ret);
                        $nsemicolon = 0;
                        $openbrace = true;
                        break;
                    case '}':
                        // Remove unneeded {}.
                        if (! ($openbrace && $nsemicolon < 2)) {
                            $ret[] = $token;
                        }
                        $openbrace = false;
                        $posiniinst = count($ret);
                        break;
                    case ';':
                        // Count semicolon after a {.
                        $nsemicolon++;
                        $ret[] = $token;
                        $posiniinst = count($ret);
                        break;
                    case '++':
                        $ret[] = self::clone_token($token, '=');
                        self::expand_operator($ret, $posiniinst);
                        $token->value = '+';
                        $ret[] = $token;
                        break;
                    case '--':
                        $ret[] = self::clone_token($token, '=');
                        self::expand_operator($ret, $posiniinst);
                        $token->value = '-';
                        $ret[] = $token;
                        break;
                    case '+=':
                        $ret[] = self::clone_token($token, '=');
                        self::expand_operator($ret, $posiniinst);
                        $token->value = '+';
                        $ret[] = $token;
                        break;
                    case '-=':
                        $ret[] = self::clone_token($token, '=');
                        self::expand_operator($ret, $posiniinst);
                        $token->value = '-';
                        $ret[] = $token;
                        break;
                    case '*=':
                        $ret[] = self::clone_token($token, '=');
                        self::expand_operator($ret, $posiniinst);
                        $token->value = '*';
                        $ret[] = $token;
                        break;
                    case '/=':
                        $ret[] = self::clone_token($token, '=');
                        self::expand_operator($ret, $posiniinst);
                        $token->value = '/';
                        $ret[] = $token;
                        break;
                    case '%=':
                        $ret[] = self::clone_token($token, '=');
                        self::expand_operator($ret, $posiniinst);
                        $token->value = '%';
                        $ret[] = $token;
                        break;
                    case '->': // Replace "->" by "*( ).".
                        if ($prev->value == 'this') {
                            break;
                        }
                        $ret[] = self::clone_token($token, '(');
                        $ret[] = self::clone_token($token, '*');
                        $ret[] = self::clone_token($token, ')');
                        $token->value = '.';
                        $ret[] = $token;
                        break;
                    case '::':
                        break;
                    case ':':
                        $posiniinst = count($ret);
                        // No break.
                    default:
                        $ret[] = $token;
                }
                $prev = $token;
            }
            // TODO remove (p).
        }
        return $ret;
    }

    /**
     * Returns the tokenizer for the C language.
     *
     * @return vpl_tokenizer The tokenizer instance for C.
     */
    public function get_tokenizer() {
        return tokenizer_factory::get('c');
    }
}
