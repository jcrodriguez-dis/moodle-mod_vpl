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
use mod_vpl\tokenizer\tokenizer;

class tokenizer_json extends tokenizer {
    private string $name = 'default';
    private array $extension = ['no-ext'];
    private bool $checkrules = true;
    private string $inheritrules;
    private bool $setcheckrules;

    private array $matchmappings = [];
    private array $regexprs = [];

    /**
     * Available data types for token's options,
     * which could be numbers, strings, arrays, and objects.
     *
     * Keys of this array are the token's names, and
     * values the list of all data types associated.
     */
    protected const TOKENTYPES = array(
        "token"                 => ["string", "array_string"],
        "regex"                 => ["string"],
        "next"                  => ["string", "array_object"],
        "default_token"         => ["string"]
    );

    /**
     * Group of rule's options which must be defined together.
     * This was defined in order to avoid no-sense definitions.
     */
    protected const REQUIREDGROUPRULEOPTIONS = array(
        "token"         => ["regex"],
        "regex"         => ["token"],
    );

    /**
     * Common tokens based on TextMate manual.
     * Tokenizer would only allow these tokens for "token" option
     * (more information at https://macromates.com/manual/en/language_grammars#naming-conventions)
     */
    protected const TEXTMATETOKENS = array(
        "comment" => [
            "line"  => [ "double-slash", "double-dash", "number-sign", "percentage", "character" ],
            "block" => [ "documentation" ]
        ],
        "constant" => [ "numeric", "character" => [ "escape" ], "language", "other" ],
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
        "paren" => [ "lparen", "rparen" ],
        "text"
    );

    /**
     * Creates a new instance of \mod_vpl\tokenizer\tokenizer class
     *
     * @param string $rulefilename JSON file with highlight rules
     * @param bool $setcheckrules true to set checkrules=true and false to define it based on $rulefilename
     */
    public function __construct(string $rulefilename, bool $setcheckrules=false) {
        assertf::assert(
            file_exists($rulefilename), $rulefilename,
            'file ' . $rulefilename . ' must exist'
        );

        assertf::assert(
            str_ends_with($rulefilename, '_highlight_rules.json'), $rulefilename,
            $rulefilename . ' must have suffix _highlight_rules.json'
        );

        $this->setcheckrules = $setcheckrules;
        $this->init_tokenizer($rulefilename);
    }

    /**
     * Prepare tokenizer with a file of rules
     *
     * It is recommended to use this method at __construct and
     * not as a independent method, since it will only be called
     * during the creation of the tokenizer
     *
     * @param string $rulefilename filename with all the rules
     * @return void
     */
    protected function init_tokenizer(string $rulefilename): void {
        $jsonobj = self::load_json($rulefilename);

        $this->init_check_rules($rulefilename, $jsonobj);
        $this->init_tokenizer_name($rulefilename, $jsonobj);
        $this->init_extension($rulefilename, $jsonobj);
        $this->init_inherit_rules($rulefilename, $jsonobj);
        $this->init_states($rulefilename, $jsonobj);

        $restoptions = get_object_vars($jsonobj);
        $areinvalidoptions = count($restoptions) != 0;

        if ($areinvalidoptions == true) {
            $errmssg = 'invalid options: ' . implode(',', array_keys($restoptions));
            assertf::assert($areinvalidoptions == false, $rulefilename, $errmssg);
        }

        self::prepare_tokenizer($rulefilename);
        $this->apply_inheritance();
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
            if (strcmp($ext, "no-ext") != 0) {
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

    // Preparation based on Ace Editor tokenizer.js
    // (https://github.com/ajaxorg/ace/blob/master/lib/ace/tokenizer.js).
    private function prepare_tokenizer(string $rulefilename): void {
        foreach ($this->states as $statename => $rules) {
            $ruleregexpr = $splitterrules = array();
            $matchtotal = 0;

            $this->matchmappings[$statename] = [ "default_token" => "text" ];
            $mapping = $this->matchmappings[$statename];

            for ($i = 0; $i < count($rules); $i++) {
                $rule = $rules[$i];

                if (isset($rule->default_token)) {
                    $mapping["default_token"] = $rule->default_token;
                }

                if (!isset($rule->regex)) {
                    continue;
                }

                $adjustedregex = $rule->regex;
                preg_match("/(?:(" . $adjustedregex . ")|(.))/", "a", $matches);
                $matchcount = count($matches) - 2;

                if (is_array($rule->token)) {
                    if (count($rule->token) == 1 && $matchcount == 1) {
                        $rule->token = $rule->token[0];
                    } else if ($matchcount - 1 != count($rule->token)) {
                        assertf::showerr($rulefilename, "number of classes and regexp groups doesn't match");
                        $rule->token = $rule->token[0];
                    } else {
                        $rule->tokenarray = $rule->token;
                        unset($rule->token);
                        //rule.onMatch = this.$arrayTokens;
                    }
                }

                if ($matchcount > 1) {
                    if (preg_match("/\\\d/", $rule->regex) === 1) {
                        $rule->regex = preg_replace_callback("/\\\([0-9]+)/", function($value) use ($matchtotal) {
                            return "\\" . (intval(substr($value[0], 1), 10) + $matchtotal + 1);
                        }, $rule->regex);
                    } else {
                        $matchcount = 1;
                        $adjustedregex = self::remove_capturing_groups($rule->regex);
                    }

                    if (isset($rule->token)) {
                        if (!isset($rule->split_regex) && !is_string($rule->token)) {
                            $splitterrules[] = $rule;
                        }
                    }
                }

                $mapping[$matchtotal] = $i;
                $matchtotal += $matchcount;
                $ruleregexpr[] = $adjustedregex;
            }

            if (count($ruleregexpr) == 0) {
                $mapping[0] = 0;
                $ruleregexpr[] = "$";
            }

            foreach ($splitterrules as $rule) {
                $rule->split_regex = $this->create_splitter_regexp($rule->regex);
            }

            $this->matchmappings[$statename] = $mapping;
            $this->regexprs[$statename] = "/(" . join(")|(", $ruleregexpr) . ")|($)/";
        }
    }

    private static function load_json(string $filename): object {
        $data = file_get_contents($filename);

        // Discard C-style comments and blank lines
        $content = preg_replace('#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#', '', $data);
        $content = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $content);

        $jsonobj = json_decode($content);
        assertf::assert(isset($jsonobj), $filename, 'file ' . $filename . ' is empty');
        return $jsonobj;
    }

    private function init_tokenizer_name(string $rulefilename, object $jsonobj) {
        if (isset($jsonobj->name)) {
            assertf::assert(
                is_string($jsonobj->name), $rulefilename,
                '"name" option must be a string'
            );

            $this->name = $jsonobj->name;
            unset($jsonobj->name);
        }
    }

    private function init_extension(string $rulefilename, object $jsonobj) {
        if (isset($jsonobj->extension)) {
            assertf::assert(
                is_string($jsonobj->extension) || tokenizer::check_type($jsonobj->extension, "array_string") === true,
                $rulefilename, '"extension" option must be a string or an array of strings'
            );

            if (is_string($jsonobj->extension)) {
                $this->extension = [$jsonobj->extension];
            } else {
                $this->extension = $jsonobj->extension;
            }

            foreach ($this->extension as $ext) {
                if (strcmp($ext, 'no-ext') != 0) {
                    $errmssg = 'extension ' . $ext . ' must start with .';
                    assertf::assert(str_starts_with($ext, '.'), $rulefilename, $errmssg);
                }
            }

            unset($jsonobj->extension);
        }
    }

    private function init_check_rules(string $rulefilename, object $jsonobj) {
        if (isset($jsonobj->check_rules)) {
            $optionval = $jsonobj->check_rules;

            assertf::assert(
                is_bool($optionval), $rulefilename,
                '"check_rules" option must be a boolean'
            );

            if (!$this->setcheckrules) {
                $this->checkrules = $optionval;
            }

            unset($jsonobj->check_rules);
        }
    }

    private function init_inherit_rules(string $rulefilename, object $jsonobj) {
        if (isset($jsonobj->inherit_rules)) {
            $optionval = $jsonobj->inherit_rules;

            if ($this->checkrules == true) {
                $errmssg = '"inherit_rules" option must be a string';
                assertf::assert(is_string($optionval), $rulefilename, $errmssg);
            }

            $this->inheritrules = dirname($rulefilename) . '/' . $optionval . '.json';

            if ($this->checkrules == true) {
                assertf::assert(
                    file_exists($this->inheritrules), $rulefilename,
                    "inherit JSON file " . $this->inheritrules . ' does not exist'
                );
            }

            unset($jsonobj->inherit_rules);
        }
    }

    private function init_states(string $rulefilename, object $jsonobj) {
        assertf::assert(isset($jsonobj->states), $rulefilename, '"states" option must be defined');

        if ($this->checkrules == true) {
            assertf::assert(
                is_object($jsonobj->states), $rulefilename,
                '"states" option must be an object'
            );

            $numstate = 0;

            foreach (array_keys(get_object_vars($jsonobj->states)) as $statename) {
                assertf::assert(is_string($statename), $rulefilename, 'name for state ' . $numstate . ' must be a string');
                assertf::assert(strcmp(trim($statename), "") != 0, $rulefilename, 'state ' . $numstate . ' must have a name');

                $state = $jsonobj->states->$statename;
                assertf::assert(is_array($state), $rulefilename, 'state ' . $numstate . ' must be an array');
                $this->check_rules($rulefilename, $statename, $state, $numstate, 0, -1);

                $numstate = $numstate + 1;
            }
        }

        $this->states = (array)$jsonobj->states;

        if ($this->checkrules == true) {
            assertf::assert(isset($this->states['start']), $rulefilename, '"start" state must exist');
        }

        unset($jsonobj->states);
    }

    private function check_rules(string $rulefilename, string $statename, array $state, int $nstate, int $nrule, int $nnext): void {
        foreach ($state as $rule) {
            $errmssg = "rule " . $nrule . " of state \"" . $statename . "\" nº" . $nstate . " must be an object";
            $errmssg = $nnext != -1 ? $errmssg . " (next: " . $nnext . ")" : $errmssg;
            assertf::assert(is_object($rule), $rulefilename, $errmssg);

            $optionsdefined = [];

            foreach (array_keys(get_object_vars($rule)) as $optionname) {
                $errmssg = "invalid option " . $optionname . " at rule " . $nrule . " of state \"";
                $errmssg .= $statename . "\" nº" . $nstate;
                $errmssg = $nnext != -1 ? $errmssg . " (next: " . $nnext . ")" : $errmssg;
                assertf::assert(array_key_exists($optionname, self::TOKENTYPES), $rulefilename, $errmssg);

                $optionsdefined[] = $optionname;
                $optionvalue = $rule->$optionname;
                $istypevalid = false;
                $typeoption = "";

                foreach (self::TOKENTYPES[$optionname] as $typevalue) {
                    $condtype = tokenizer::check_type($optionvalue, $typevalue);

                    if ($condtype === true) {
                        $istypevalid = true;
                        $typeoption = $typevalue;
                        break;
                    }

                    if (is_numeric($condtype)) {
                        if (strcmp($optionname, "next") == 0) {
                            $nrule = $condtype;
                        }
                    }
                }

                if (strcmp($optionname, "next") == 0) {
                    if (strcmp($typeoption, "array_object") == 0 || $nnext == -1) {
                        $nnext = $nnext + 1;
                    }
                }

                $errmssg = "invalid data type for " . $optionname . " at rule " . $nrule . " of state \"";
                $errmssg .= $statename . "\" nº" . $nstate;
                $errmssg = $nnext != -1 ? $errmssg . " (next: " . $nnext . ")" : $errmssg;
                assertf::assert($istypevalid, $rulefilename, $errmssg);

                if (strcmp($optionname, "token") == 0) {
                    $errmssg = "invalid token at rule " . $nrule . " of state \"";
                    $errmssg .= $statename . "\" nº" . $nstate;
                    $errmssg = $nnext != -1 ? $errmssg . " (next: " . $nnext . ")" : $errmssg;
                    assertf::assert(tokenizer::check_token($rule->$optionname, self::TEXTMATETOKENS), $rulefilename, $errmssg);
                }

                if (strcmp($optionname, "next") == 0) {
                    if (strcmp($typeoption, "array_object") == 0) {
                        $this->check_rules($rulefilename, $statename, $optionvalue, $nstate, 0, $nnext);
                        $nnext = $nnext >= 1 ? $nnext - 1 : $nnext;
                    }
                }
            }

            foreach (self::REQUIREDGROUPRULEOPTIONS as $optionrequired => $group) {
                if (in_array($optionrequired, $optionsdefined)) {
                    foreach ($group as $optiong) {
                        $errmssg = "option " . $optionrequired . " must be defined next to " . $optiong . " at rule ";
                        $errmssg .= $nrule . " of state \"" . $statename . "\" nº" . $nstate;
                        $errmssg = $nnext != -1 ? $errmssg . " (next: " . $nnext . ")" : $errmssg;
                        assertf::assert(in_array($optiong, $optionsdefined), $rulefilename, $errmssg);
                    }
                }
            }

            if (in_array("default_token", $optionsdefined)) {
                $errmssg = "option default_token must be alone at rule " . $nrule . " of state \"";
                $errmssg .= $statename . "\" nº" . $nstate;
                $errmssg = $nnext != -1 ? $errmssg . " (next: " . $nnext . ")" : $errmssg;
                assertf::assert(count($optionsdefined) == 1, $rulefilename, $errmssg);
            }

            $nrule = $nrule + 1;
        }
    }

    private function apply_inheritance(): void {
        if (!empty($this->inheritrules)) {
            $inherittokenizer = new tokenizer_json($this->inheritrules);
            $src = $inherittokenizer->get_states();

            foreach ($src as $srcname => $srcvalue) {
                if (!isset($this->states[$srcname])) {
                    $newstate = [$srcname => $srcvalue];
                    $this->states = array_merge($this->states, $newstate);
                } else {
                    foreach ($srcvalue as $rulesrc) {
                        if (!tokenizer::contains_rule($this->states[$srcname], $rulesrc)) {
                            $this->states[$srcname][] = $rulesrc;
                        }
                    }
                }
            }
        }
    }
}
