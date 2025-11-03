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
use mod_vpl\tokenizer\tokenizer_factory;

/**
 * Scala language similarity class
 *
 * @package mod_vpl
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Lang Michael <michael.lang.ima10@fh-joanneum.at>
 * @author Lückl Bernd <bernd.lueckl.ima10@fh-joanneum.at>
 * @author Lang Johannes <johannes.lang.ima10@fh-joanneum.at>
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 * @copyright all authors
 */
class vpl_similarity_scala extends vpl_similarity_c {
    /**
     * Returns the type of similarity.
     *
     * @return int The type of similarity, which is 7 for Scala.
     */
    public function get_type() {
        return 7;
    }

    /**
     * Returns the tokenizer for the Scala language.
     */
    public function get_tokenizer() {
        return tokenizer_factory::get('scala');
    }
}
