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
 * Internal utilities for tokenizer
 *
 * @package mod_vpl
 * @copyright 2022 David Parreño Barbuzano
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author David Parreño Barbuzano <losedavidpb@gmail.com>
 */
namespace mod_vpl\tokenizer;

use mod_vpl\util\assertf;

class utils {
    /**
     * @internal
     *
     * Available data types for token's options,
     * which could be numbers, strings, arrays, and objects.
     * Keys of this array are the token's names, and
     * values the list of all data types associated.
     */
    public const TOKENTYPES = array(
        "token"                 => ["string", "array_string"],
        "regex"                 => ["string"],
        "next"                  => ["string", "array_object"],
    );

    /**
     * @internal
     *
     * Group of rule's options which must be defined together.
     * This was defined in order to avoid no-sense definitions.
     */
    public const REQUIREDGROUPRULEOPTIONS = array(
        "token"         => ["regex"],
        "regex"         => ["token"],
    );

    /**
     * @internal
     *
     * Common tokens based on TextMate manual.
     * Tokenizer would only allow these tokens for "token" option
     * (more information at https://macromates.com/manual/en/language_grammars#naming-conventions)
     */
    public const TEXTMATETOKENS = array(
        "comment" => [
            "line"  => [ "double-slash", "double-dash", "number-sign", "percentage", "character" ],
            "block" => [ "documentation" ]
        ],
        "constant" => [
            "numeric",
            "character" => [ "escape" ],
            "language",
            "other"
        ],
        "entity" => [
            "name"  => [ "function", "type", "tag", "section" ],
            "other" => [ "intherited-class", "attribute-name" ]
        ],
        "invalid" => [ "illegal", "deprecated" ],
        "keyword" => [ "control", "operator", "other" ],
        "markup" => [
            "underline" => [ "link" ],
            "bold", "heading", "italic",
            "list" => [ "numbered", "unnumbered" ],
            "quote", "raw", "other"
        ],
        "meta",
        "storage" => [ "type", "modifier" ],
        "string" => [ "quoted", "single", "double", "triple", "other", "unquoted", "interpolated", "regexp", "other" ],
        "support" => [ "function", "class", "type", "typedef", "constant", "variable", "other" ],
        "variable" => [ "parameter", "language", "other" ],
        "paren" => [ "lparen", "rparen" ]
    );

    /**
     * Load JSON object without comments
     *
     * @internal
     * @param string $filename JSON file
     * @return object
     */
    public static function load_json(string $filename): object {
        $data = file_get_contents($filename);
        $content = self::discard_comments($data);

        $jsonobj = json_decode($content);
        assertf::assert(isset($jsonobj), $filename, 'file ' . $filename . ' is empty');
        return $jsonobj;
    }

    /**
     * Discard all comments from passed string
     *
     * @internal
     * @param string $data content with comments
     * @return string
     */
    public static function discard_comments(string $data): string {
        $pattern = '#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#';
        $content = preg_replace($pattern, '', $data);
        return $content;
    }

    /**
     * Get the associate array equivalence of states
     *
     * @internal
     * @param array $states list of all states
     * @return array
     */
    public static function get_assoc_states(array $states): array {
        $statesassoc = array();

        if (count($states) > 0) {
            foreach ($states as $state) {
                if (isset($state->name)) {
                    $indexval = $state->name;
                    $statesassoc[$indexval] = $state;
                }
            }
        }

        return $statesassoc;
    }

    /**
     * Check if passed value has an available data type
     *
     * @internal
     * @param $value value to check
     * @param string $typename data type which must have $value
     * @return bool|int
     */
    public static function check_type($value, string $typename) {
        if (str_starts_with($typename, "array_")) {
            if (is_array($value)) {
                $typearray = substr($typename, 6);

                foreach ($value as $indexvalue => $val) {
                    $cond = strcmp($typearray, "number") == 0 && is_numeric($val) == false;
                    $cond = $cond || strcmp($typearray, "string") == 0 && is_string($val) == false;
                    $cond = $cond || strcmp($typearray, "object") == 0 && is_object($val) == false;
                    $cond = $cond || strcmp($typearray, "array") == 0 && is_array($val) == false;

                    if ($cond == true) {
                        return $indexvalue;
                    }
                }

                return true;
            }
        } else {
            $condtypes = array(
                "number" => is_numeric($value),
                "string" => is_string($value),
                "object" => is_object($value),
                "array"  => is_array($value)
            );

            return $condtypes[$typename];
        }

        return false;
    }

    /**
     * Check data type of token based on TextMate
     *
     * @internal
     * @param string|array $token value for a token
     * @return bool
     */
    public static function check_token($token): bool {
        $token = is_string($token) ? [$token] : $token;

        foreach ($token as $value) {
            $splitvalues = explode(".", $value);
            $splitvalues = count($splitvalues) <= 0 ? $value : $splitvalues;
            $arraytoken = self::TEXTMATETOKENS;

            foreach ($splitvalues as $svalue) {
                if (isset($arraytoken) === true) {
                    if (isset($arraytoken[$svalue]) || in_array($svalue, $arraytoken)) {
                        if (isset($arraytoken[$svalue])) {
                            $arraytoken = $arraytoken[$svalue];
                        } else {
                            unset($arraytoken);
                        }

                        continue;
                    }
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Check if passed state contains passed rule
     *
     * @internal
     * @param object $state state that could have $rule
     * @param object $rule rule to search
     * @return bool
     */
    public static function contains_rule(object $state, object $rule): bool {
        $result = false;

        foreach ($state->data as $ruleorig) {
            $tokensorig = array_keys(get_object_vars($ruleorig));
            $tokensrule = array_keys(get_object_vars($rule));
            $result = false;

            if (count($tokensorig) == count($tokensrule)) {
                foreach ($tokensorig as $name) {
                    $result = false;

                    if (isset($rule->$name) && isset($ruleorig->$name)) {
                        if ($rule->$name === $ruleorig->$name) {
                            $result = true;
                        }
                    }
                }

                if ($result == true) {
                    break;
                }
            }
        }

        return $result;
    }
}
