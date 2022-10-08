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
 * VPLT:: Tokenizer factory
 *
 * @package mod_vpl
 * @copyright 2022 David Parreño Barbuzano
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author David Parreño Barbuzano <losedavidpb@gmail.com>
 */
namespace mod_vpl\tokenizer;

use mod_vpl\tokenizer\tokenizer;
use mod_vpl\util\assertf;

class tokenizer_factory {
    private static array $tkloaded = array();

    /**
     * Get tokenizer for passed programming language
     *
     * @param string $namelang name of a programming language
     * @return ?tokenizer|?vpl_tokenizer
     */
    public static function get(string $namelang) {
        $tokenizer = self::get_object($namelang);

        if (!isset($tokenizer) || is_null($tokenizer)) {
            $tokenizer = self::get_require($namelang);
            assertf::assert(isset($tokenizer), $namelang, $namelang . ' is not available');
        }

        return $tokenizer;
    }

    private static function get_require(string $namelang) {
        $include = 'tokenizer_' . $namelang . '.class.php';
        $include = dirname(__FILE__) . '/../../similarity/' . $include;

        if (file_exists($include) === true) {
            if (!isset(self::$tkloaded[$namelang])) {
                require_once($include);
                $class = 'vpl_tokenizer_' . $namelang;
                self::$tkloaded[$namelang] = new $class();
            }

            return self::$tkloaded[$namelang];
        }

        return null;
    }

    private static function get_object(string $namelang) {
        $rulefilename = dirname(__FILE__) . '/../../similarity/tokenizer_rules/';
        $rulefilename .= $namelang . '_tokenizer_rules.json';

        if (file_exists($rulefilename) === true) {
            if (!isset(self::$tkloaded[$rulefilename])) {
                self::$tkloaded[$rulefilename] = new tokenizer($rulefilename);
            }

            return self::$tkloaded[$rulefilename];
        }

        return null;
    }
}
