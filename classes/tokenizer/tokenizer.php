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
 * Tokenizer for highlight rules JSON files
 *
 * @package mod_vpl
 * @copyright 2022 David Parreño Barbuzano
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author David Parreño Barbuzano <losedavidpb@gmail.com>
 */
namespace mod_vpl\tokenizer;

use mod_vpl\util\assertf;

defined('MOODLE_INTERNAL') || die();

class tokenizer {
    // Maximum number of tokens than tokenizer could process.
    // Adjust this to avoid slow tokenizers.
    private const max_token_count = 2000;

    // Available data types for token's options,
    // which could be numbers, strings, arrays, and objects.
    // Keys of this array are the token's names, and
    // values the list of all data types associated
    private const TOKEN_TYPES = array(
        "token"                 => ["string", "array_string"],
        "regex"                 => ["string"],
        "next"                  => ["string", "array_object"],
        "default_token"         => ["string"],
    );

    // Group of token's options which must be defined together.
    // This was defined in order to avoid no-sense definitions.
    private const REQUIRED_GROUP_OF_TOKENS = array(
        "token"         => ["regex"],
        "regex"         => ["token"],
    );

    // Group of token's options which could be defined together
    // but are not required to be all defined. This is used to
    // assure that non-valid tokens are together.
    private const POSSIBLE_GROUPS_OF_TOKENS = array(
        0 => ["token", "regex", "next"],
        1 => ["default_token"]
    );

    private string $filename;
    private ?object $json_obj;
    private array $states;
    private array $match_mappings;
    private array $reg_exprs;
    private bool $check_rules;
    private bool $override_check_rules;

    /**
     * Creates a new instance of \mod_vpl\tokenizer\tokenizer class
     *
     * @param string $filename JSON file with highlight rules
     * @param bool $override_check_rules set this to true to avoid syntax check
     */
    public function __construct(string $filename, bool $override_check_rules=false) {
        $this->override_check_rules = $override_check_rules;
        $this->check_rules = true;

        $this->preprocess($filename);
      //  $this->postprocess();
    }

    /**
     * Parse all lines of passed file
     *
     * @param string $filename file to parse
     * @return array
     */
    public function parse(string $filename): array {
        assertf::assert(file_exists($filename), null, "file " . $filename . " does not exist");
        $info_lines = array();

        if ($file = fopen($filename, "r")) {
            while(!feof($file)) {
                $text_per_line = fgets($file);
                $info_line = $this->get_line_tokens($text_per_line, "start");
                array_push($info_lines, $info_line);
            }

            fclose($file);
        }

        return $info_lines;
    }

    /**
     * Get all tokens for passed line
     *
     * @param string $line content of the line
     * @param string $start_state state on which stack would start
     * @return array
     */
    public function get_line_tokens(string $line, string $start_state=""): array {
        $stack = [];

        $current_state = strcmp($start_state, "") == 0? $start_state : "start";
        if (isset($this->states[$current_state])) $current_state = "start";

        //$state = $this->search_state($this->json_obj, $current_state);
        $state = $this->states[$current_state];
        $mapping = $this->match_mappings[$current_state];
        $regex = $this->reg_exprs[$current_state];

        $token = new token(null, "");

        $match_attempts = 0;
        $last_index = 0;
        $tokens = [];

        while (preg_match($regex, $line, $match, PREG_OFFSET_CAPTURE, $last_index) != 0) {
            $type = $mapping["default_token"];
            $index = $last_index;
            $value = $match[0];
            $match = $match[0];
            $rule = null;

            if ($index - strlen($value) > $last_index) {
                $skipped = substr($line, $last_index, $index - strlen($value));

                if ($token->type == $type) {
                    $token->value .= $skipped;
                } else {
                    if (isset($token->type)) array_push($tokens, $token);
                    $token = new token($type, $skipped);
                }
            }

            for ($i = 0; $i < strlen($match) - 2; $i++) {
                if (!isset($match[$i + 1])) continue;
                $rule = $state[$mapping[$i]];

                if (isset($rule->next)) {
                    $current_state = $rule->next;
                    if (!isset($this->states[$current_state])) $current_state = "start";

                    $state = $this->states[$current_state];
                    $mapping = $this->match_mappings[$current_state];
                    $regex = $this->reg_exprs[$current_state];
                    $last_index = $index;
                }

                break;
            }

            if (isset($value)) {
                if (is_string($value)) {
                    if (!isset($rule) && $token->type === $type) {
                        $token->value .= $value;
                    } else {
                        if (isset($token->type)) array_push($tokens, $token->type);
                        $token = new token($type, $value);
                    }
                } else if(isset($type)) {
                    if (isset($token->type)) array_push($tokens, $token->type);
                    $token = new token(null, "");

                    for ($i = 0; $i < count($type); $i++)
                        array_push($tokens, $type[$i]);
                }
            }

            if ($last_index == strlen($line)) break;

            if ($match_attempts++ > $this->max_token_count) {
                assertf::assert($match_attempts > 2 * strlen($line), null, "infinite loop found at tokenizer");

                while ($last_index < strlen($line)) {
                    if ($token->type) array_push($tokens, $token);
                    $token = new token(substr($line, $last_index, $last_index += 500), "overflow");
                }

                $current_state = "start"; $stack = []; break;
            }
        }

        if (isset($token->type)) array_push($tokens, $token);
        return array("tokens" => $tokens, "state" => count($stack) > 0? $stack : $current_state);
    }

    /**
     * Get states for current tokenizer
     *
     * This function is just defined for tokenizer tests,
     * but you can use it to check if all states of the
     * highlight rules JSON file have been detected.
     *
     * @return array
     */
    public function get_states(): array {
        $states_copy = $this->states;
        return $states_copy;
    }

    // -----------------------
    // Private
    // -----------------------

    private function preprocess(string $filename) {
        assertf::assert(file_exists($filename), $filename, "filename " . $filename . " must exist");
        assertf::assert(str_ends_with($filename, "_highlight_rules.json"), $filename, $filename . " must have the suffix _highlight_rules.json");
        $this->filename = $filename;

        $this->load_json($filename);
        $this->check_syntax_and_set_states();
        $this->apply_inheritance();
    }

    private function postprocess(): void {
        for ($i = 0; $i < count($this->states); $i++) {
            $state = $this->states[$i]->data;
            $key = $this->states[$i]->name;
            $rule_reg_exprs = [];
            $match_total = 0;

            $this->match_mappings[$key] = ["default_token" => "text"];
            $mapping = $this->match_mappings[$key];

            for ($j = 0; $j < count($state); $j++) {
                $rule = $state[$j];

                if (isset($rule->default_token))
                    $mapping["default_token"] = $rule->default_token;

                if (!isset($rule->regex)) continue;

                $adjusted_regex = $rule->regex;
                $match_count = preg_match_all("(.)", $adjusted_regex);

                if (isset($rule->token)) {
                    if (is_array($rule->token)) {
                        if (count($rule->token) == 1 || $match_count == 1) {
                            $rule->token = $rule->token[0];
                        } else {
                            $cond = $match_count - 1 != count($rule->token);
                            assertf::assert($cond, $this->filename, "number of classes and regex groups doesn't match");
                            $rule->token = null;
                            $rule->token_array = $rule->token;
                        }
                    }
                }

                $mapping[$match_total] = $j;
                $match_total += $match_count;
                array_push($rule_reg_exprs, $adjusted_regex);
            }

            if (count($rule_reg_exprs) == 0) {
                $mapping[0] = 0;
                array_push($rule_reg_exprs, "$");
            }

            $this->match_mappings[$key] = $mapping;
            $rule_reg_exprs = preg_quote(join($rule_reg_exprs), '/');
            $this->reg_exprs[$key] = "/(" . $rule_reg_exprs . ")|($)/";
        }
    }

    private static function discard_comments(string $data): string {
        $pattern = "#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#";
        $content = preg_replace($pattern, '', $data);
        return $content;
    }

    private function load_json(string $filename): void {
        $data = file_get_contents($filename);
        $content = $this->discard_comments($data);

        $this->json_obj = json_decode($content);
        assertf::assert(isset($this->json_obj), $filename, "file " . $filename . " is empty");
    }

    private static function prepare_states(array $states): array {
        $states_indexable = array();

        foreach ($states as $key => $state)
            $states_indexable[$state->name] = $state;

        return $states_indexable;
    }

    private function check_syntax_and_set_states(): void {
        foreach ($this->json_obj as $key => $val) {
            // Check rules option
            if (strcmp($key, "check_rules") == 0) {
                assertf::assert(is_bool($val), $this->filename, "check_rules option must be boolean");

                if (!$this->override_check_rules) {
                    $this->check_rules = $val;
                }
            }

            // Check check_rules value
            if ($this->check_rules) {
                // Check general option
                $cond_key = strcmp($key, "inherit_rules") == 0 || strcmp($key, "states") == 0;
                assertf::assert($cond_key, $this->filename, "option " . $key . " not found");

                // Check inherit rules
                if (strcmp($key, "inherit_rules") == 0) {
                    assertf::assert(is_string($val), $this->filename, "inherit_rules option must be a string");

                    if (!empty($val)) {
                        $val = dirname($this->filename) . '/' . $val;

                        assertf::assert(
                            file_exists($val . ".json"), $this->filename,
                            "inherit JSON file " . $val . ".json does not exist"
                        );
                    }
                }

                // Check states
                else {
                    assertf::assert(is_array($val), $this->filename, "states must be an array");
                    $list_names = []; $num_state = 0;

                    foreach(((array)$this->json_obj)[$key] as $state) {
                        assertf::assert(is_object($state), $this->filename, "state " . $num_state . " must be an object");
                        assertf::assert(isset($state->name), $this->filename, "state " . $num_state . " must have a name");
                        assertf::assert(is_string($state->name), $this->filename, "name for state " . $num_state . " must be a string");
                        assertf::assert(!in_array($state->name, $list_names), $this->filename, "name \"" . $state->name . "\" of state " . $num_state . " is duplicated");
                        assertf::assert(isset($state->data), $this->filename, "state \"" . $state->name . "\" nº" . $num_state . " must have a data section");
                        assertf::assert(is_array($state->data), $this->filename, "data section for state \"" . $state->name . "\" nº" . $num_state . " must be an array");
                        array_push($list_names, $state->name);

                        $this->check_rules($state->data, $state->name, $num_state, 0, -1);
                        $num_state = $num_state + 1;
                    }

                    $this->states = self::prepare_states($val);
                }
            } else if (strcmp($key, "states") == 0) {
                $this->states = self::prepare_states($val);
                break;
            }
        }
    }

    private static function check_token_type($value, string $type_name) {
        if (str_starts_with($type_name, "array_")) {
            if (is_array($value)) {
                $type_array = substr($type_name, 6);

                foreach($value as $key => $val) {
                    switch ($type_array) {
                        case "number": if (!is_numeric($val)) return $key; break;
                        case "string": if (!is_string($val)) return $key; break;
                        case "object": if (!is_object($val)) return $key; break;
                        case "array": if (!is_array($val)) return $key; break;
                        default: return $key;
                    }
                }

                return true;
            }
        } else {
            switch ($type_name) {
                case "number": return is_numeric($value); break;
                case "string": return is_string($value); break;
                case "object": return is_object($value); break;
                case "array": return is_array($value); break;
            }
        }

        return false;
    }

    private function check_rules(array $state, string $state_name, int $num_state, int $num_rule, int $num_next): void {
        foreach($state as $rule) {
            $options = array();

            // Check rule
            $err_mssg = "rule " . $num_rule . " of state \"" . $state_name . "\" nº" . $num_state . " must be an object";
            if ($num_next != -1) $err_mssg .= " (next: " . $num_next . ")";
            assertf::assert(is_object($rule), $this->filename, $err_mssg);

            // Check options for current rule
            foreach(array_keys(get_object_vars($rule)) as $option_n) {
                $err_mssg = "invalid option " . $option_n . " at rule " . $num_rule . " of state \"" . $state_name . "\" nº" . $num_state;
                if ($num_next != -1) $err_mssg .= " (next: " . $num_next . ")";
                assertf::assert(array_key_exists($option_n, self::TOKEN_TYPES), $this->filename, $err_mssg);

                $option_v = $rule->$option_n;
                $is_type_valid = false;
                $type_option = null;

                // Check data type for current option
                foreach (self::TOKEN_TYPES[$option_n] as $type_v) {
                    $cond_type = self::check_token_type($option_v, $type_v);
                    $type_option = "";

                    // General case
                    if (is_bool($cond_type)) {
                        if ($cond_type == true) {
                            $is_type_valid = true;
                            $type_option = $type_v;
                            break;
                        }
                    }

                    //Special case for "next" option
                    else if (is_numeric($cond_type)) {
                        if (strcmp($option_n, "next") == 0) {
                            $num_rule = $cond_type;
                        }
                    }
                }

                // Check "next" token
                if (strcmp($option_n, "next") == 0) {
                    $num_next = $num_next + 1;
                }

                // Check data type for current option
                $err_mssg = "invalid data type for " . $option_n . " at rule " . $num_rule . " of state \"" . $state_name . "\" nº" . $num_state;
                if ($num_next != -1) $err_mssg .= " (next: " . $num_next . ")";
                assertf::assert($is_type_valid, $this->filename, $err_mssg);

                // Check "next" token
                if (strcmp($option_n, "next") == 0) {
                    if (strcmp($type_option, "array_object") == 0) {
                        $this->check_rules($option_v, $state_name, $num_state, 0, $num_next);
                    }
                }

                array_push($options, $option_n);
                $groups_checked = false;
            }

            // Check groups for current options
            foreach(self::POSSIBLE_GROUPS_OF_TOKENS as $i => $group) {
                if ($groups_checked) break;

                foreach($group as $j => $group_option) {
                    if (in_array($group_option, $options)) {
                        $groups_checked = true;

                        foreach ($options as $k => $option_d) {
                            $err_mssg = "option " . $option_d . " could not be defined with the rest of options at rule ";
                            $err_mssg .= $num_rule . " of state \"" . $state_name . "\" nº" . $num_state;
                            if ($num_next != -1) $err_mssg .= " (next: " . $num_next . ")";
                            assertf::assert(in_array($option_d, $group), $this->filename, $err_mssg);
                        }
                    }
                }
            }

            // Check required groups
            foreach(self::REQUIRED_GROUP_OF_TOKENS as $option_required => $group) {
                if (in_array($option_required, $options)) {
                    foreach($group as $i => $option_g) {
                        $err_mssg = "option " . $option_required . " must be defined next to " . $option_g . " at rule ";
                        $err_mssg .= $num_rule . " of state \"" . $state_name . "\" nº" . $num_state;
                        if ($num_next != -1) $err_mssg .= " (next: " . $num_next . ")";
                        assertf::assert(in_array($option_g, $options), $this->filename, $err_mssg);
                    }
                }
            }

            $num_rule = $num_rule + 1;
        }
    }

    private function apply_inheritance(): void {
        if (!isset($this->json_obj->inherit_rules)) $inherit_rules = "";
        else $inherit_rules = $this->json_obj->inherit_rules;

        if (!empty($inherit_rules)) {
            $inherit_rules = dirname($this->filename) . '/' . $inherit_rules . '.json';
            $inherit_tokenizer = new tokenizer($inherit_rules);
            $src = $inherit_tokenizer->get_states();

            foreach($src as $src_name => $state_src) {
                if (!isset($this->states[$src_name])) {
                    $this->states = array_merge($this->states, [$src_name => $state_src]);
                } else {
                    foreach($state_src->data as $rule_src) {
                        if (!self::search_rule($this->states[$src_name], $rule_src)) {
                            array_push($this->states[$src_name], $rule_src);
                        }
                    }
                }
            }
        }
    }

    private static function search_rule($state, $rule) {
        $result = false;

        foreach($state->data as $rule_obj) {
            if ($result == true) break;
            $result = true;

            foreach(array_keys(get_object_vars($rule_obj)) as $option_n) {
                if (!isset($rule->$option_n)) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }
}