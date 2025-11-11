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
use mod_vpl\tokenizer\token_type;
use mod_vpl\tokenizer\tokenizer_factory;

/**
 * Prolog language similarity class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
class vpl_similarity_prolog extends similarity_base {
    /**
     * Returns the type of similarity.
     *
     * @return int The type of similarity, which is 6 for Prolog.
     */
    public function get_type() {
        return 6;
    }

    /**
     * Normalizes the syntax of the given tokens.
     *
     * @param array $tokens The tokens to normalize.
     * @return array The normalized tokens.
     */
    public function sintax_normalize(&$tokens) {
        $ret = [];
        foreach ($tokens as $token) {
            if ($token->type == token_type::OPERATOR) {
                $ret[] = $token;
            }
        }
        return $ret;
    }

    /**
     * Returns the tokenizer for the Prolog language.
     *
     * @return vpl_tokenizer The tokenizer instance for Prolog.
     */
    public function get_tokenizer() {
        return tokenizer_factory::get('prolog');
    }
}
