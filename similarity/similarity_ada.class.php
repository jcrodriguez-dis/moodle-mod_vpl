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
 * Ada language similarity class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/similarity_base.class.php');

class vpl_similarity_ada extends vpl_similarity_base {
    public function get_type() {
        return 4;
    }
    public function sintax_normalize(&$tokens) {
        $identifierlist = false;
        $nidentifiers = 0;
        $identifierdefpos = 0;
        $bracketlevel = 0;
        $ret = array ();
        foreach ($tokens as $token) {
            if ($token->type == vpl_token_type::OPERATOR) {
                switch ($token->value) {
                    case '[' :
                        // Only add ].
                        break;
                    case '(' :
                        // Only add ).
                        $bracketlevel ++;
                        break;
                    case '{' :
                        // Only add }.
                        break;
                    case ')' :
                        $bracketlevel --;
                        $ret[] = $token;
                        break;
                    case ';' :
                        $ret[] = $token;
                        // End of identifier list declaration?
                        if ($identifierlist) {
                            if ($identifierdefpos > 0) {
                                $rep = array_slice( $ret, $identifierdefpos );
                                for ($i = 0; $i < $nidentifiers; $i ++) {
                                    foreach ($rep as $data) {
                                        $ret[] = $data;
                                    }
                                }
                            } else {
                                for ($i = 0; $i < $nidentifiers; $i ++) {
                                    $ret[] = $token;
                                }
                            }
                        }
                        $identifierlist = false;
                        break;
                    case ',' :
                        // Posible identifier list.
                        if ($bracketlevel == 0) {
                            if ($identifierlist) {
                                $identifierlist = true;
                                $identifierdefpos = 0;
                                $nidentifiers = 1;
                            } else {
                                $nidentifiers ++;
                            }
                        } else {
                            $ret[] = $token;
                        }
                        break;
                    case ':' :
                        if ($identifierlist) {
                            $identifierdefpos = count( $ret );
                        }
                        $ret[] = $token;
                        break;
                    default :
                        $ret[] = $token;
                }
            }
        }
        return $ret;
    }
    public function get_tokenizer() {
        return vpl_tokenizer_factory::get( 'ada' );
    }
}
