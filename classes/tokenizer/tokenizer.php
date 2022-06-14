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

class tokenizer {
    // Available data types for token's options,
    // which could be numbers, strings, arrays, and objects.
    // Keys of this array are the token's names, and
    // values the list of all data types associated.
    private const TOKENTYPES = array(
        "token"                 => ["string", "array_string"],
        "regex"                 => ["string"],
        "next"                  => ["string", "array_object"],
    );

    // Group of token's options which must be defined together.
    // This was defined in order to avoid no-sense definitions.
    private const REQUIREDGROUPOFTOKENS = array(
        0 => ["token", "regex"]
    );

    private string $name;
    private array $extension;
    private string $inheritrules;
    private bool $overridecheckrules;
    private bool $checkrules;
    private array $states;

    private array $matchmappings;
    private array $regexprs;

    /**
     * Creates a new instance of \mod_vpl\tokenizer\tokenizer class
     *
     * @param string $filename JSON file with highlight rules
     * @param bool $overridecheckrules set this to true to avoid syntax check
     */
    public function __construct(string $filename, bool $overridecheckrules=false) {
        assertf::assert(file_exists($filename), $filename, 'file ' . $filename . ' must exist');

        $errmssg = $filename . ' must have suffix _highlight_rules.json';
        assertf::assert(str_ends_with($filename, '_highlight_rules.json'), $filename, $errmssg);

        $this->overridecheckrules = $overridecheckrules;
        $this->checkrules = true;

        $this->name = 'default';
        $this->extension = ['plaintext'];

        $jsonobj = $this->load_json($filename);
        $this->init_tokenizer($filename, $jsonobj);
        $this->apply_inheritance();
        // $this->prepare_tokenizer($filename);
    }

    /**
     * Parse all lines of passed file
     *
     * @param string $filename file to parse
     * @return array
     */
    public function parse(string $filename): array {
        assertf::assert(file_exists($filename), $this->name, 'file ' . $filename . ' does not exist');

        foreach ($this->extension as $ext) {
            if (strcmp($ext, "plaintext") != 0) {
                $hasvalidext = str_ends_with($filename, $ext);
                assertf::assert($hasvalidext, $this->name, $filename . ' must end with ' . $ext);
            }
        }

        $infolines = array();

        if ($file = fopen($filename, 'r')) {
            while (!feof($file)) {
                $textperline = fgets($file);
                $infoline = $this->get_line_tokens($textperline, 'start');
                $infolines[] = $infoline;
            }

            fclose($file);
        }

        return $infolines;
    }

    /**
     * Get all tokens for passed line
     *
     * @param string $line content of the line
     * @param string $startstate state on which stack would start
     * @return array
     */
    public function get_line_tokens(string $line, string $startstate=""): array {
        $stack = [];

        $currentstate = strcmp($startstate, "") == 0 ? $startstate : "start";

        if (isset($this->states[$currentstate])) {
            $currentstate = "start";
        }

        // $state = $this->search_state($this->jsonobj, $currentstate);
        $state = $this->states[$currentstate];
        $mapping = $this->matchmappings[$currentstate];
        $regex = $this->regexprs[$currentstate];

        $token = new token(null, "");

        $matchattempts = 0;
        $lastindex = 0;
        $tokens = [];

        while (preg_match($regex, $line, $match, PREG_OFFSET_CAPTURE, $lastindex) != 0) {
            $type = $mapping["default_token"];
            $index = $lastindex;
            $value = $match[0];
            $match = $match[0];
            $rule = null;

            if ($index - strlen($value) > $lastindex) {
                $skipped = substr($line, $lastindex, $index - strlen($value));

                if ($token->type == $type) {
                    $token->value .= $skipped;
                } else {
                    if (isset($token->type)) {
                        array_push($tokens, $token);
                    }

                    $token = new token($type, $skipped);
                }
            }

            for ($i = 0; $i < strlen($match) - 2; $i++) {
                if (!isset($match[$i + 1])) {
                    continue;
                }

                $rule = $state[$mapping[$i]];

                if (isset($rule->next)) {
                    $currentstate = !isset($this->states[$rule->next]) ? "start" : $rule->next;

                    $state = $this->states[$currentstate];
                    $mapping = $this->matchmappings[$currentstate];
                    $regex = $this->regexprs[$currentstate];
                    $lastindex = $index;
                }

                break;
            }

            if (isset($value)) {
                if (is_string($value)) {
                    if (!isset($rule) && $token->type === $type) {
                        $token->value .= $value;
                    } else {
                        if (isset($token->type)) {
                            array_push($tokens, $token->type);
                        }

                        $token = new token($type, $value);
                    }
                } else if (isset($type)) {
                    if (isset($token->type)) {
                        array_push($tokens, $token->type);
                    }

                    $token = new token(null, "");

                    for ($i = 0; $i < count($type); $i++) {
                        array_push($tokens, $type[$i]);
                    }
                }
            }

            if ($lastindex == strlen($line)) {
                break;
            }

            if ($matchattempts++ > $this->max_token_count) {
                assertf::assert($matchattempts > 2 * strlen($line), null, "infinite loop found at tokenizer");

                while ($lastindex < strlen($line)) {
                    if ($token->type) {
                        array_push($tokens, $token);
                    }

                    $token = new token(substr($line, $lastindex, $lastindex += 500), "overflow");
                }

                $currentstate = "start"; $stack = []; break;
            }
        }

        if (isset($token->type)) {
            array_push($tokens, $token);
        }

        return array("tokens" => $tokens, "state" => count($stack) > 0 ? $stack : $currentstate);
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
        return $this->states;
    }

    private function prepare_tokenizer(string $filename): void {
        for ($i = 0; $i < count($this->states); $i++) {
            $state = $this->states[$i]->data;
            $key = $this->states[$i]->name;
            $ruleregexprs = [];
            $matchtotal = 0;

            $this->matchmappings[$key] = ["default_token" => "text"];
            $mapping = $this->matchmappings[$key];

            for ($j = 0; $j < count($state); $j++) {
                $rule = $state[$j];

                if (isset($rule->default_token)) {
                    $mapping["default_token"] = $rule->default_token;
                }

                if (!isset($rule->regex)) {
                    continue;
                }

                $adjustedregex = $rule->regex;
                $matchcount = preg_match_all("(.)", $adjustedregex);

                if (isset($rule->token)) {
                    if (is_array($rule->token)) {
                        if (count($rule->token) == 1 || $matchcount == 1) {
                            $rule->token = $rule->token[0];
                        } else {
                            $cond = $matchcount - 1 != count($rule->token);
                            assertf::assert($cond, $filename, "number of classes and regex groups doesn't match");
                            $rule->token = null;
                            $rule->token_array = $rule->token;
                        }
                    }
                }

                $mapping[$matchtotal] = $j;
                $matchtotal += $matchcount;
                array_push($ruleregexprs, $adjustedregex);
            }

            if (count($ruleregexprs) == 0) {
                $mapping[0] = 0;
                array_push($ruleregexprs, "$");
            }

            $this->matchmappings[$key] = $mapping;
            $ruleregexprs = preg_quote(join($ruleregexprs), '/');
            $this->regexprs[$key] = "/(" . $ruleregexprs . ")|($)/";
        }
    }

    private static function load_json(string $filename): object {
        $data = file_get_contents($filename);
        $content = self::discard_comments($data);

        $jsonobj = json_decode($content);
        assertf::assert(isset($jsonobj), $filename, 'file ' . $filename . ' is empty');
        return $jsonobj;
    }

    private static function discard_comments(string $data): string {
        $pattern = '#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#';
        $content = preg_replace($pattern, '', $data);
        return $content;
    }

    private function init_tokenizer(string $filename, object $jsonobj): void {
        $this->init_check_rules($filename, $jsonobj);
        $this->init_tokenizer_name($filename, $jsonobj);
        $this->init_extension($filename, $jsonobj);
        $this->init_inherit_rules($filename, $jsonobj);
        $this->init_states($filename, $jsonobj);

        $restoptions = get_object_vars($jsonobj);
        $areinvalidoptions = count($restoptions) != 0;

        if ($areinvalidoptions == true) {
            $errmssg = 'invalid options: ' . implode(',', array_keys($restoptions));
            assertf::assert($areinvalidoptions == false, $filename, $errmssg);
        }
    }

    private function init_tokenizer_name(string $filename, object $jsonobj) {
        if (isset($jsonobj->name)) {
            assertf::assert(
                is_string($jsonobj->name), $filename,
                '"name" option must be a string'
            );

            $this->name = $jsonobj->name;
            unset($jsonobj->name);
        }
    }

    private function init_extension(string $filename, object $jsonobj) {
        if (isset($jsonobj->extension)) {
            assertf::assert(
                is_string($jsonobj->extension) || self::check_token_type($jsonobj->extension, "array_string") === true,
                $filename, '"extension" option must be a string or an array of strings'
            );

            if (is_string($jsonobj->extension)) {
                $this->extension = [$jsonobj->extension];
            } else {
                $this->extension = $jsonobj->extension;
            }

            foreach ($this->extension as $ext) {
                $errmssg = 'extension ' . $ext . ' must start with .';
                assertf::assert(str_starts_with('.', $ext), $filename, $errmssg);
            }

            unset($jsonobj['name']);
        }
    }

    private function init_check_rules(string $filename, object $jsonobj) {
        if (isset($jsonobj->check_rules)) {
            $optionval = $jsonobj->check_rules;

            assertf::assert(
                is_bool($optionval), $filename,
                '"check_rules" option must be a boolean'
            );

            if (!$this->overridecheckrules) {
                $this->checkrules = $optionval;
            }

            unset($jsonobj->check_rules);
        }
    }

    private function init_inherit_rules(string $filename, object $jsonobj) {
        if (isset($jsonobj->inherit_rules)) {
            $optionval = $jsonobj->inherit_rules;

            if ($this->checkrules == true) {
                $errmssg = '"inherit_rules" option must be a string';
                assertf::assert(is_string($optionval), $filename, $errmssg);
            }

            $this->inheritrules = dirname($filename) . '/' . $optionval . '.json';

            if ($this->checkrules == true) {
                assertf::assert(
                    file_exists($this->inheritrules), $filename,
                    "inherit JSON file " . $this->inheritrules . ' does not exist'
                );
            }

            unset($jsonobj->inherit_rules);
        }
    }

    private function init_states(string $filename, object $jsonobj) {
        assertf::assert(isset($jsonobj->states), $filename, '"states" option must be defined');

        if ($this->checkrules == true) {
            assertf::assert(
                is_array($jsonobj->states), $filename,
                '"states" option must be an array'
            );

            $liststatenames = [];
            $numstate = 0;

            foreach ($jsonobj->states as $state) {
                assertf::assert(is_object($state), $filename, 'state ' . $numstate . ' must be an object');
                assertf::assert(isset($state->name), $filename, 'state ' . $numstate . ' must have a name');
                assertf::assert(is_string($state->name), $filename, 'name for state ' . $numstate . ' must be a string');

                $errmssg = 'name "' . $state->name . '" of state ' . $numstate . ' is duplicated';
                assertf::assert(!in_array($state->name, $liststatenames), $filename, $errmssg);

                $errmssg = 'state "' . $state->name . '" nº' . $numstate . ' must have a data section';
                assertf::assert(isset($state->data), $filename, $errmssg);

                $errmssg = 'data section for state "' . $state->name . '" nº' . $numstate . ' must be an array';
                assertf::assert(is_array($state->data), $filename, $errmssg);
                $this->check_rules($filename, $state, $numstate, 0, -1);

                $liststatenames[] = $state->name;
                $numstate = $numstate + 1;
            }
        }

        $this->states = self::convert_to_assoc_states($jsonobj->states);
        unset($jsonobj->states);
    }

    private static function convert_to_assoc_states(array $states): array {
        $stassoc = array();

        if (count($states) > 0) {
            foreach ($states as $state) {
                $indexvalue = $state->name;
                $stassoc[$indexvalue] = $state;
            }
        }

        return $stassoc;
    }

    private function check_rules(string $filename, object $state, int $numstate, int $numrule, int $numnext): void {
        foreach ($state->data as $rule) {
            $errmssg = "rule " . $numrule . " of state \"" . $state->name . "\" nº" . $numstate . " must be an object";
            $errmssg = $numnext != -1 ? $errmssg . " (next: " . $numnext . ")" : $errmssg;
            assertf::assert(is_object($rule), $filename, $errmssg);

            $optionsdefined = [];

            foreach (array_keys(get_object_vars($rule)) as $optionname) {
                $errmssg = "invalid option " . $optionname . " at rule " . $numrule . " of state \"";
                $errmssg .= $state->name . "\" nº" . $numstate;
                $errmssg = $numnext != -1 ? $errmssg . " (next: " . $numnext . ")" : $errmssg;
                assertf::assert(array_key_exists($optionname, self::TOKENTYPES), $filename, $errmssg);

                $optionsdefined[] = $optionname;
                $optionvalue = $rule->$optionname;
                $istypevalid = false;
                $typeoption = "";

                foreach (self::TOKENTYPES[$optionname] as $typevalue) {
                    $condtype = self::check_token_type($optionvalue, $typevalue);

                    if ($condtype === true) {
                        $istypevalid = true;
                        $typeoption = $typevalue;
                        break;
                    }

                    if (is_numeric($condtype)) {
                        if (strcmp($optionname, "next") == 0) {
                            $numrule = $condtype;
                        }
                    }
                }

                if (strcmp($optionname, "next") == 0) {
                    $numnext = $numnext + 1;
                }

                $errmssg = "invalid data type for " . $optionname . " at rule " . $numrule . " of state \"";
                $errmssg .= $state->name . "\" nº" . $numstate;
                $errmssg = $numnext != -1 ? $errmssg . " (next: " . $numnext . ")" : $errmssg;
                assertf::assert($istypevalid, $filename, $errmssg);

                if (strcmp($optionname, "next") == 0) {
                    if (strcmp($typeoption, "array_object") == 0) {
                        $substate = (object)array("name" => $state->name, "data" => $optionvalue);
                        $this->check_rules($filename, $substate, $numstate, 0, $numnext);
                    }
                }
            }

            foreach (self::REQUIREDGROUPOFTOKENS as $group) {
                foreach ($group as $optionname) {
                    if (in_array($optionname, $optionsdefined)) {
                        $intersectoptions = array_intersect($group, $optionsdefined);
                        $areallelementsdefined = count($intersectoptions) == count($group);

                        $errmssg = 'missing options ' . implode(',', array_diff($group, $intersectoptions));
                        $errmssg .= ' at rule ' . $numrule . ' of state "' . $state->name . '" nº' . $numstate;
                        $errmssg = $numnext != -1 ? $errmssg . ' (next: ' . $numnext . ')' : $errmssg;
                        assertf::assert($areallelementsdefined, $filename, $errmssg);
                    }
                }
            }

            $numrule = $numrule + 1;
        }
    }

    private static function check_token_type($value, string $typename) {
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

    private function apply_inheritance(): void {
        if (!empty($this->inheritrules)) {
            $inherittokenizer = new tokenizer($this->inheritrules);
            $src = $inherittokenizer->get_states();

            foreach ($src as $srcname => $srcvalue) {
                if (!isset($this->states[$srcname])) {
                    $newstate = [$srcname => $srcvalue];
                    $this->states = array_merge($this->states, $newstate);
                } else {
                    foreach ($srcvalue->data as $rulesrc) {
                        if (!self::contains_rule($this->states[$srcname], $rulesrc)) {
                            $this->states[$srcname][] = $rulesrc;
                        }
                    }
                }
            }
        }
    }

    private static function contains_rule(object $state, object $rule): bool {
        $result = false;

        foreach ($state->data as $ruleobj) {
            if ($result == true) {
                return $result;
            }

            $result = true;

            foreach (array_keys(get_object_vars($ruleobj)) as $optionn) {
                if (!isset($rule->$optionn)) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }
}
