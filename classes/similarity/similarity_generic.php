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
 * Generic similarity class based on tokenizer
 *
 * @package mod_vpl
 * @copyright 2022 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\similarity;

use \mod_vpl\tokenizer\tokenizer_factory;

/**
 * @codeCoverageIgnore
 */
class similarity_generic extends similarity_base {
    private static int $lasttypenumber = 50;
    private static array $typenumbers = [];

    private string $tokenizerclass;
    private int $typenumber;

    public function __construct(string $tokenizerclass) {
        $this->tokenizerclass = $tokenizerclass;
        $this->typenumber = self::get_type_number($tokenizerclass);
    }

    public function get_type() {
        return $this->typenumber;
    }

    public function get_tokenizer() {
        return tokenizer_factory::get($this->tokenizerclass);
    }

    private static function get_type_number($tokenizerclass) {
        if (!isset(self::$typenumbers[$tokenizerclass])) {
            self::$typenumbers[$tokenizerclass] = self::$lasttypenumber++;
        }

        return self::$typenumbers[$tokenizerclass];
    }
}
