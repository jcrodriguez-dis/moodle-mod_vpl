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
 * Scheme language similarity class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
require_once dirname(__FILE__).'/similarity_base.class.php';

class vpl_similarity_scheme extends vpl_similarity_base{
    public function get_type(){
        return 5;
    }
    public function sintax_normalize(&$tokens){
        $open_brace =false;
        $nsemicolon=0;
        $ret = array();
        $prev = new vpl_token(vpl_token_type::identifier,'',0);
        foreach($tokens as $token){
            if($token->type == vpl_token_type::operator){
                $ret[]=$token;
            }
        }
        return $ret;
    }

    public function get_tokenizer(){
        return vpl_tokenizer_factory::get('scheme');
    }
}
