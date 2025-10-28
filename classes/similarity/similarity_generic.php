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
 * Generic similarity class based on a tokenizer
 *
 * @package mod_vpl
 * @copyright 2022 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\similarity;

use mod_vpl\tokenizer\tokenizer_factory;

/**
 * Generic similarity class based on a tokenizer.
 * @codeCoverageIgnore
 */
class similarity_generic extends similarity_base {
    /**
     * @var int $lasttypenumber Last type number assigned to a tokenizer dynamicaly.
     * This is used to ensure that each tokenizer class has a unique type number.
     */
    private static int $lasttypenumber = 50;

    /**
     * @var array $typenumbers Cache for type numbers of tokenizers
     */
    private static array $typenumbers = [];

    /**
     * @var string Class name of the tokenizer to be used for this similarity class.
     */
    private string $tokenizerclass;

    /**
     * @var int $typenumber Type number for this similarity class.
     * This is used to identify the similarity type.
     */
    private int $typenumber;

    /**
     * Constructor for the similarity_generic class.
     * It initializes the tokenizer class and assigns a type number.
     * @param string $tokenizerclass The class name of the tokenizer to be used.
     */
    public function __construct(string $tokenizerclass) {
        $this->tokenizerclass = $tokenizerclass;
        $this->typenumber = self::get_type_number($tokenizerclass);
    }

    /**
     * Get the type number for this similarity class.
     * This number is used to identify the similarity type.
     */
    public function get_type() {
        return $this->typenumber;
    }

    /**
     * Get the tokenizer instance for this similarity class.
     * This method uses the tokenizer factory to create
     * an instance of the specified tokenizer class.
     *
     * @return \mod_vpl\tokenizer\tokenizer_base
     */
    public function get_tokenizer() {
        return tokenizer_factory::get($this->tokenizerclass);
    }

    /**
     * Get the type number for a given tokenizer class.
     * This method ensures that each tokenizer class
     * has a unique type number.
     * @param string $tokenizerclass The class name of the tokenizer.
     * @return int The type number for the tokenizer class.
     */
    private static function get_type_number($tokenizerclass) {
        if (!isset(self::$typenumbers[$tokenizerclass])) {
            self::$typenumbers[$tokenizerclass] = self::$lasttypenumber++;
        }

        return self::$typenumbers[$tokenizerclass];
    }
}
