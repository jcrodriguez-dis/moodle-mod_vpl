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
 * VPLT:: Basic utilities for tokenizer
 *
 * @package mod_vpl
 * @copyright 2022 David Parreño Barbuzano
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author David Parreño Barbuzano <losedavidpb@gmail.com>
 */
namespace mod_vpl\tokenizer;

/**
 * This class was not designed to be used as a real tokenizer,
 * but it has many useful tools used by tokenizers.
 */
class tokenizer_base {
    protected array $states;
    protected array $matchmappings;
    protected array $regexprs;
    protected array $tokens;

    /**
     * @codeCoverageIgnore
     *
     * Initialize an empty tokenizer
     */
    public function __construct() {
        $this->tokens = [];
        $this->states = [];
        $this->matchmappings = [];
        $this->regexprs = [];
    }

    /**
     * @codeCoverageIgnore
     *
     * Get tokens for current tokenizer
     *
     * @return array
     */
    public function get_tokens(): array {
        return $this->tokens;
    }

    /**
     * @codeCoverageIgnore
     *
     * Get states for current tokenizer
     *
     * @return array
     */
    protected function get_states(): array {
        return $this->states;
    }

    /**
     * @codeCoverageIgnore
     *
     * Get matching map for current tokenizer
     *
     * @return array
     */
    protected function get_matchmappings(): array {
        return $this->matchmappings;
    }

    /**
     * @codeCoverageIgnore
     *
     * Get regex of each state for current tokenizer
     *
     * @return array
     */
    protected function get_regexprs(): array {
        return $this->regexprs;
    }

    /**
     * Check if passed value has an available data type
     *
     * @param $value value to check
     * @param string $typename data type which must have $value
     * @return bool|int
     */
    protected static function check_type($value, string $typename) {
        $condtypes = array(
            "number" => function ($val) {
                return is_numeric($val);
            },
            "string" => function ($val) {
                return is_string($val);
            },
            "object" => function ($val) {
                return is_object($val);
            },
            "bool"   => function ($val) {
                return is_bool($val);
            },
            "array"  => function ($val) {
                return is_array($val);
            }
        );

        if (str_starts_with($typename, "array_") && is_array($value)) {
            $typearray = substr($typename, 6);

            foreach ($value as $indexval => $val) {
                if (isset($condtypes[$typearray])) {
                    if ($condtypes[$typearray]($val) !== true) {
                        return $indexval;
                    }
                } else {
                    return $indexval;
                }
            }

            return true;
        } else if (!str_starts_with($typename, "array_")) {
            if (isset($condtypes[$typename])) {
                return $condtypes[$typename]($value);
            }
        }

        return false;
    }

    /**
     * Check if passed state contains passed rule
     *
     * @param array $state state that could have $rule
     * @param object $rule rule to search
     * @return bool
     */
    protected static function contains_rule(array $state, object $rule): bool {
        $result = false;

        foreach ($state as $ruleorig) {
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

    /**
     * Check data type of token
     *
     * @param string|array $token value for a token
     * @param array $availabletokens list of available tokens
     * @return bool
     */
    protected static function check_token($token, array $availabletokens): bool {
        $token = is_string($token) ? [$token] : $token;

        if (is_array($token) && count($token) == 0) {
            return false;
        }

        foreach ($token as $value) {
            if (!in_array($value, $availabletokens)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove Capturing Groups based on Ace Editor tokenizer.js
     * (https://github.com/ajaxorg/ace/blob/master/lib/ace/tokenizer.js).
     *
     * @param string $src source string with capturing groups
     * @return string
     */
    protected static function remove_capturing_groups(string $src): string {
        $regex = preg_replace_callback("/\\.|\[(?:\\.|[^\\\]])*|\(\?[:=!<]|(\()/", function ($value) {
            return strcmp($value[0], "(") == 0 ? "(?:" : $value[0];
        }, $src);

        return preg_replace("/\(\?:\)/", "()", $regex);
    }

    /**
     * Get an array of tokens extracted from capturing groups
     *
     * @param int    $numline number of line each token
     * @param array  $type list of types for each token
     * @param string $value expression with values for each group
     * @param string $regex regular expression with capturing groups
     * @return array
     */
    protected static function get_token_array(int $numline, array $type, string $value, string $regex): array {
        $tokenarray = array();

        if (preg_match_all("/\(\?:/", $regex, $matches, PREG_OFFSET_CAPTURE) >= 1) {
            if (count($type) === count($matches[0])) {
                $offset = $matches[0][0][1];
                $restvalue = $value;

                for ($i = 0; $i < count($matches[0]); $i++) {
                    if ($i != count($matches[0]) - 1) {
                        $length = $matches[0][$i + 1][1] - $offset;
                        $regexi = "/" . substr($regex, $offset, $length) . "/";
                        $offset = $matches[0][$i + 1][1];

                        preg_match($regexi, $restvalue, $matchesvalue);
                        $tokenarray[] = new token($type[$i], $matchesvalue[0], $numline);
                        $restvalue = substr($restvalue, strlen($matchesvalue[0]));
                    } else {
                        $length = strlen($value) - $offset;
                        $regexi = "/" . substr($regex, $offset, $length) . "/";
                        $tokenarray[] = new token($type[$i], $restvalue, $numline);
                    }
                }
            }
        }

        return $tokenarray;
    }
}
