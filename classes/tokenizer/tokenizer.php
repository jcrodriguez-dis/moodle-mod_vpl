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
 * VPLT:: Tokenizer for tokenizer rules JSON files
 *
 * @package mod_vpl
 * @copyright 2022 David Parreño Barbuzano
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author David Parreño Barbuzano <losedavidpb@gmail.com>
 */
namespace mod_vpl\tokenizer;

use mod_vpl\util\assertf;
use mod_vpl\tokenizer\tokenizer_base;

// @codeCoverageIgnoreStart
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;
    }
}

// Adjust this flag in order to avoid showing error messages
// which are not catched as exceptions. On production, this
// should be commnented or set to false.
define('TOKENIZER_ON_TEST', true);
// @codeCoverageIgnoreEnd

class tokenizer extends tokenizer_base {
    protected string $name = 'default';
    protected array $extension = ['no-ext'];
    protected bool $checkrules = true;
    protected string $inheritrules;
    protected bool $setcheckrules;
    protected array $rawoverridetokens = [];

    /**
     * Maximum number of tokens that tokenizer allow
     * before performance gets worse
     */
    protected int $maxtokencount = 2000;

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
        "next"                  => ["string"],
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
     * List of all VPL token types used at override_tokens
     */
    protected const VPLTOKENTYPES = array(
        "vpl_identifier", "vpl_literal", "vpl_operator",
        "vpl_reserved", "vpl_other", "vpl_null"
    );

    /**
     * Available names for tokens based on TextMate and ACE editor.
     *
     * Each token must be declared as one of the vpl_token_type avaiable types
     * in order to be compatible for similarity classes.
     *
     * It is important to notice that if one token's name has not a valid type,
     * tokenizer would delete it for similarity tests.
     *
     * For more information about the meaning of some names, see
     * https://macromates.com/manual/en/language_grammars at Naming Conventions section
     */
    protected array $availabletokens = array(
        "comment" => null,
        "comment.line" => null,
        "comment.line.double-slash" => null,
        "comment.line.double-dash" => null,
        "comment.line.number-sign" => null,
        "comment.line.percentage" => null,
        "comment.line.character" => null,
        "comment.block" => null,
        "comment.block.documentation" => null,
        "constant" => token_type::LITERAL,
        "constant.numeric" => token_type::LITERAL,
        "constant.character" => token_type::LITERAL,
        "constant.character.escape" => token_type::LITERAL,
        "constant.language" => token_type::LITERAL,
        "constant.language.escape" => token_type::LITERAL,
        "constant.other" => token_type::LITERAL,
        "entity" => null,
        "entity.name" => token_type::IDENTIFIER,
        "entity.name.function" => token_type::IDENTIFIER,
        "entity.name.type" => token_type::IDENTIFIER,
        "entity.name.tag" => token_type::RESERVED,
        "entity.name.section" => token_type::IDENTIFIER,
        "entity.other" => token_type::IDENTIFIER,
        "entity.other.inherited-class" => token_type::IDENTIFIER,
        "entity.other.attribute-name" => token_type::RESERVED,
        "keyword" => token_type::RESERVED,
        "keyword.control" => token_type::RESERVED,
        "keyword.operator" => token_type::OPERATOR,
        "keyword.other" => token_type::RESERVED,
        "markup" => null,
        "markup.underline" => token_type::OTHER,
        "markup.underline.link" => token_type::OTHER,
        "markup.bold" => token_type::OTHER,
        "markup.heading" => token_type::OTHER,
        "markup.italic" => token_type::OTHER,
        "markup.list" => token_type::OTHER,
        "markup.list.numbered" => token_type::OTHER,
        "markup.list.unnumbered" => token_type::OTHER,
        "markup.quote" => token_type::OTHER,
        "markup.raw" => token_type::OTHER,
        "markup.other" => token_type::OTHER,
        "meta" => null,
        "storage" => null,
        "storage.type" => token_type::RESERVED,
        "storage.modifier" => token_type::RESERVED,
        "string" => token_type::LITERAL,
        "string.quoted" => token_type::LITERAL,
        "string.quoted.single" => token_type::LITERAL,
        "string.quoted.double" => token_type::LITERAL,
        "string.quoted.triple" => token_type::LITERAL,
        "string.quoted.other" => token_type::LITERAL,
        "string.unquoted" => token_type::LITERAL,
        "string.interpolated" => token_type::LITERAL,
        "string.regexp" => token_type::LITERAL,
        "string.other" => token_type::LITERAL,
        "support" => null,
        "support.function" => token_type::RESERVED,
        "support.class" => token_type::RESERVED,
        "support.type" => token_type::RESERVED,
        "support.constant" => token_type::LITERAL,
        "support.variable" => token_type::IDENTIFIER,
        "support.other" => token_type::OTHER,
        "identifier" => token_type::IDENTIFIER,
        "variable" => token_type::IDENTIFIER,
        "variable.parameter" => token_type::IDENTIFIER,
        "variable.language" => token_type::RESERVED,
        "variable.other" => token_type::IDENTIFIER,
        "text" => null,
        "punctuation" => null,
        "punctuation.separator" => token_type::OPERATOR,
        "paren" => token_type::OPERATOR,
        "paren.lparen" => token_type::OPERATOR,
        "paren.rparen" => token_type::OPERATOR,

        // VPL types.
        "vpl_identifier" => token_type::IDENTIFIER,
        "vpl_literal" => token_type::LITERAL,
        "vpl_operator" => token_type::OPERATOR,
        "vpl_reserved" => token_type::RESERVED,
        "vpl_other" => token_type::OTHER,
        "vpl_null" => null, // Same as "" at JSON files.
    );

    /**
     * @codeCoverageIgnore
     *
     * Get availabletokens for current tokenizer
     *
     * @return array
     */
    protected function get_override_tokens(): array {
        return $this->availabletokens;
    }

    /**
     * @codeCoverageIgnore
     *
     * Get rawoverridetokens for current tokenizer
     *
     * @return array
     */
    protected function get_raw_override_tokens(): array {
        return $this->rawoverridetokens;
    }

    /**
     * @codeCoverageIgnore
     *
     * Get maxtokencount for current tokenizer
     *
     * @return int
     */
    protected function get_max_token_count(): int {
        return $this->maxtokencount;
    }

    /**
     * Creates a new instance of \mod_vpl\tokenizer\tokenizer class
     *
     * @param string $rulefilename JSON file with highlight rules
     * @param bool $setcheckrules true to set checkrules=true and false to define it based on $rulefilename
     */
    public function __construct(string $rulefilename, bool $setcheckrules=false) {
        parent::__construct();

        assertf::assert(
            file_exists($rulefilename), $rulefilename,
            'file ' . $rulefilename . ' must exist'
        );

        assertf::assert(
            str_ends_with($rulefilename, '_tokenizer_rules.json'), $rulefilename,
            $rulefilename . ' must have suffix _tokenizer_rules.json'
        );

        $this->setcheckrules = $setcheckrules;
        $jsonobj = self::load_json($rulefilename);

        $this->init_check_rules($rulefilename, $jsonobj);
        $this->init_inherit_rules($rulefilename, $jsonobj);
        $this->init_override_tokens($rulefilename, $jsonobj);
        $this->init_max_token_count($rulefilename, $jsonobj);
        $this->init_tokenizer_name($rulefilename, $jsonobj);
        $this->init_extension($rulefilename, $jsonobj);
        $this->init_states($rulefilename, $jsonobj);

        $restoptions = get_object_vars($jsonobj);
        $areinvalidoptions = count($restoptions) != 0;

        if ($areinvalidoptions == true) {
            $errmssg = 'invalid options: ' . implode(',', array_keys($restoptions));
            assertf::assert($areinvalidoptions == false, $rulefilename, $errmssg);
        }

        $this->apply_inheritance();
        self::prepare_tokenizer($rulefilename);
    }

    /**
     * @codeCoverageIgnore
     *
     * Set tokenizer::$maxtokencount whether $maxtokencount is natural
     *
     * @param int $maxtokencount natural number to set to $maxtokencount
     */
    public function set_max_token_count(int $maxtokencount=0): void {
        if ($maxtokencount >= 0) {
            $this->maxtokencount = $maxtokencount;
        }
    }

    /**
     * Get all tokens for passed filename for similarity
     *
     * @param string $data content or file to tokenize
     * @param bool $isfile check if $data is filename
     * @return array
     */
    public function parse(string $data, bool $isfile=true): array {
        if ($isfile === true) {
            $tokens = $this->get_all_tokens($data);
        } else {
            // @codeCoverageIgnoreStart
            $tokens = [$this->get_line_tokens($data, "start", 0)];
            // @codeCoverageIgnoreEnd
        }

        $tokensprepared = array();

        foreach ($tokens as $dataofline) {
            foreach ($dataofline['tokens'] as $token) {
                $cond = in_array($token->type, array_keys($this->availabletokens));
                assertf::assert($cond, null, 'token ' . $token->type . ' is not valid');
                $type = $this->availabletokens[$token->type];

                if (is_null($type) === false) {
                    if (strlen(trim($token->value)) > 0) {
                        $tokensprepared[] = new token(
                            $type, trim($token->value), $token->line
                        );
                    }
                }
            }
        }

        $this->tokens = $tokensprepared;
        return $tokensprepared;
    }

    /**
     * Get tokens for each line of $filename
     *
     * @param string $filename file to tokenize
     * @return array
     */
    public function get_all_tokens(string $filename): array {
        assertf::assert(file_exists($filename), $this->name, 'file ' . $filename . ' does not exist');
        $hasvalidext = false;

        foreach ($this->extension as $ext) {
            if (strcmp($ext, "no-ext") != 0) {
                $hasvalidext = str_ends_with($filename, $ext) ? true : $hasvalidext;
            }
        }

        $extensionsstr = implode(',', $this->extension);
        assertf::assert($hasvalidext, $this->name, $filename . ' must end with one of the extensions ' . $extensionsstr);

        $infolines = array();
        $state = 'start';
        $numline = 0;

        if ($file = fopen($filename, 'r')) {
            if (filesize($filename) != 0) {
                while (!feof($file)) {
                    $textperline = fgets($file);
                    $infoline = $this->get_line_tokens($textperline, $state, $numline++);

                    $state = $infoline["state"];
                    $infolines[] = $infoline;
                }
            }

            fclose($file);
        }

        return $infolines;
    }

    /**
     * Get all tokens for passed line based on Ace Editor
     * (https://github.com/ajaxorg/ace/blob/master/lib/ace/tokenizer.js).
     *
     * @param string $line content of the line
     * @param string $startstate state on which stack would start
     * @param int    $numline number of line
     * @return array
     */
    public function get_line_tokens(string $line, string $startstate, int $numline): array {
        $startstate = !isset($startstate) ? "" : $startstate;
        $currentstate = strcmp($startstate, "") === 0 ? "start" : $startstate;
        $tokens = array();

        if (!isset($this->states[$currentstate])) {
            $currentstate = "start";
            $state = $this->states[$currentstate];
        } else {
            $state = $this->states[$currentstate];
        }

        $mapping = $this->matchmappings[$currentstate];
        $regex = $this->regexprs[$currentstate];
        $offset = $lastindex = $matchattempts = $numchars = 0;
        $token = new token(null, "", -1);

        while (preg_match($regex, substr($line, $offset), $matches, PREG_OFFSET_CAPTURE) === 1) {
            if ($matchattempts++ >= $this->maxtokencount) {
                // @codeCoverageIgnoreStart
                if ($matchattempts > 2 * strlen($line)) {
                    if (!defined('TOKENIZER_ON_TEST') || constant('TOKENIZER_ON_TEST') === false) {
                        assertf::showerr(null, "infinite loop with " . $startstate . " in tokenizer");
                    }
                }
                // @codeCoverageIgnoreEnd

                while ($numchars < strlen($line)) {
                    self::add_token($tokens, $token, $numchars, $line);
                    $overflowval = substr($line, $lastindex, 500);
                    $token = new token("overflow", $overflowval, $numline);
                    $lastindex += 500;
                }

                $currentstate = "start";
                break;
            }

            $type = $mapping["default_token"];
            $value = $matches[0][0];

            $offset += strlen($value);

            if (strlen($value) === 0 && $matches[0][1] === 0) {
                $offset = $offset + 1;
                $numchars = $numchars + 1;
            }

            $index = $offset;

            if ($matches[0][1] > 0) {
                $skipped = substr($line, $lastindex, $matches[0][1]);
                $offset += strlen($skipped);

                if ($token->type === $type) {
                    $token->value .= $skipped;
                } else {
                    self::add_token($tokens, $token, $numchars, $line);
                    $token = new token($type, $skipped, $numline);
                }
            }

            for ($i = 0; $i < count($matches) - 1; $i++) {
                if ($matches[$i + 1][1] != -1) {
                    if (isset($mapping[$i])) {
                        $rule = $state[$mapping[$i]];
                        $type = isset($rule->token) ? $rule->token : $rule->token_array;

                        if (isset($rule->next)) {
                            $currentstate = $rule->next;

                            if (!isset($this->states[$currentstate])) {
                                // @codeCoverageIgnoreStart
                                if (!defined('TOKENIZER_ON_TEST') || constant('TOKENIZER_ON_TEST') === false) {
                                    assertf::showerr(null, "state " . $currentstate . " doesn't exist");
                                }
                                // @codeCoverageIgnoreEnd

                                $currentstate = "start";
                                $state = $this->states[$currentstate];
                            } else {
                                $state = $this->states[$currentstate];
                            }

                            $mapping = $this->matchmappings[$currentstate];
                            $regex = $this->regexprs[$currentstate];
                            $lastindex = $index;
                        }
                    }

                    break;
                }
            }

            if (isset($value)) {
                if (isset($type) && is_string($type)) {
                    if (!isset($rule) && $token->type === $type) {
                        $token->value .= $value;
                    } else {
                        self::add_token($tokens, $token, $numchars, $line);
                        $token = new token($type, $value, $numline);
                    }
                } else {
                    self::add_token($tokens, $token, $numchars, $line);
                    $token = new token(null, "", -1);
                    $tokenarray = tokenizer_base::get_token_array($numline, $type, $value, $regex);

                    foreach ($tokenarray as $tokensplit) {
                        $tokens[] = $tokensplit;
                        $numchars += strlen($tokensplit->value);
                    }
                }
            }

            $condexit = $lastindex >= strlen($line) || $offset >= strlen($line);
            $condexit = $condexit || $numchars >= strlen($line);

            if ($condexit === true) {
                break;
            }

            $lastindex = $index;
        }

        self::add_token($tokens, $token, $numchars, $line, true);
        return [ "state"  => $currentstate, "tokens" => $tokens ];
    }

    // Preparation based on Ace Editor tokenizer.js
    // (https://github.com/ajaxorg/ace/blob/master/lib/ace/tokenizer.js).
    private function prepare_tokenizer(string $rulefilename): void {
        foreach ($this->states as $statename => $rules) {
            $ruleregexpr = array();
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
                $matchcount = count($matches) >= 3 ? count($matches) - 2 : 1;

                if (is_array($rule->token)) {
                    if (count($rule->token) == 1 || $matchcount == 1) {
                        $rule->token = $rule->token[0];
                    } else if ($matchcount - 1 != count($rule->token)) {
                        // @codeCoverageIgnoreStart
                        if (!defined('TOKENIZER_ON_TEST') || constant('TOKENIZER_ON_TEST') === false) {
                            $errmssg = "number of classes and regexp groups doesn't match ";
                            $errmssg .= ($matchcount - 1) . " != " . count($rule->token);
                            assertf::showerr($rulefilename, $errmssg);
                        }
                        // @codeCoverageIgnoreEnd

                        $rule->token = $rule->token[0];
                    } else {
                        $rule->token_array = $rule->token;
                        unset($rule->token);
                    }
                }

                if ($matchcount > 1) {
                    if (preg_match("/\\\(\d)/", $rule->regex) === 1) {
                        $adjustedregex = preg_replace_callback("/\\\([0-9]+)/", function($value) use ($matchtotal) {
                            return "\\" . (intval(substr($value[0], 1), 10) + $matchtotal + 1);
                        }, $rule->regex);
                    } else {
                        $matchcount = 1;
                        $adjustedregex = self::remove_capturing_groups($rule->regex);
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

            $this->matchmappings[$statename] = $mapping;
            $this->regexprs[$statename] = "/(" . join(")|(", $ruleregexpr) . ")|($)/";
        }
    }

    private static function add_token(array &$tokens, token $token, int &$numchars, string $line, bool $settrim=false): void {
        if (isset($token->type) && $numchars < strlen($line)) {
            $cond = !$settrim ? strlen($token->value) >= 1 : strlen(trim($token->value)) >= 1;

            if ($cond === true) {
                $tokens[] = $token;
                $numchars += strlen($token->value);
            }
        }
    }

    private static function load_json(string $filename): object {
        $data = file_get_contents($filename);

        // Discard C-style comments and blank lines.
        $content = preg_replace('#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#', '', $data);
        $content = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $content);

        $jsonobj = json_decode($content, null, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        assertf::assert(isset($jsonobj), $filename, 'file ' . $filename . ' is empty');
        return $jsonobj;
    }

    private function init_max_token_count(string $rulefilename, object $jsonobj) {
        if (isset($jsonobj->max_token_count)) {
            if ($this->checkrules === true) {
                assertf::assert(
                    is_numeric($jsonobj->max_token_count), $rulefilename,
                    '"max_token_count" option must be numeric'
                );

                assertf::assert(
                    $jsonobj->max_token_count >= 0, $rulefilename,
                    '"max_token_count" option must be a positive integer'
                );
            }

            $this->set_max_token_count($jsonobj->max_token_count);
            unset($jsonobj->max_token_count);
        }
    }

    private function init_override_tokens(string $rulefilename, object $jsonobj) {
        if (isset($jsonobj->override_tokens)) {
            if ($this->checkrules === true) {
                assertf::assert(
                    is_object($jsonobj->override_tokens), $rulefilename,
                    '"override_tokens" option must be an object'
                );
            }

            $overridetokens = (array)$jsonobj->override_tokens;
            $this->rawoverridetokens = $overridetokens;

            foreach ($overridetokens as $tokename => $strtokentype) {
                if ($this->checkrules === true) {
                    assertf::assert(
                        !in_array($tokename, self::VPLTOKENTYPES),
                        $rulefilename, $tokename . ' could not be overrided'
                    );

                    assertf::assert(
                        isset($this->availabletokens[$strtokentype]) || strcmp($strtokentype, 'vpl_null') == 0,
                        $rulefilename, $strtokentype . ' does not exist'
                    );
                }

                $tokentype = $this->availabletokens[$strtokentype];
                $this->availabletokens[$tokename] = $tokentype;
            }

            unset($jsonobj->override_tokens);
        }

        // Inherit override_tokens before checking rules.
        if (!empty($this->inheritrules)) {
            $inherittokenizer = new tokenizer($this->inheritrules);
            $src = $inherittokenizer->get_override_tokens();
            $rawsrc = $inherittokenizer->get_raw_override_tokens();

            foreach (array_keys($rawsrc) as $tokename) {
                $tokentype = $src[$tokename];
                $this->availabletokens[$tokename] = $tokentype;
            }
        }
    }

    private function init_tokenizer_name(string $rulefilename, object $jsonobj) {
        if (isset($jsonobj->name)) {
            if ($this->checkrules === true) {
                assertf::assert(
                    is_string($jsonobj->name), $rulefilename,
                    '"name" option must be a string'
                );
            }

            $this->name = $jsonobj->name;
            unset($jsonobj->name);
        }
    }

    private function init_extension(string $rulefilename, object $jsonobj) {
        if (isset($jsonobj->extension)) {
            if ($this->checkrules === true) {
                assertf::assert(
                    is_string($jsonobj->extension) || tokenizer_base::check_type($jsonobj->extension, "array_string") === true,
                    $rulefilename, '"extension" option must be a string or an array of strings'
                );
            }

            if (is_string($jsonobj->extension)) {
                $this->extension = [$jsonobj->extension];
            } else {
                $this->extension = $jsonobj->extension;
            }

            if ($this->checkrules === true) {
                foreach ($this->extension as $ext) {
                    if (strcmp($ext, 'no-ext') != 0) {
                        $errmssg = 'extension ' . $ext . ' must start with .';
                        assertf::assert(str_starts_with($ext, '.'), $rulefilename, $errmssg);
                    }
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
                $this->check_rules($rulefilename, $statename, $state, $numstate, 0);

                $numstate = $numstate + 1;
            }
        }

        $this->states = (array)$jsonobj->states;

        if ($this->checkrules == true) {
            assertf::assert(isset($this->states['start']), $rulefilename, '"start" state must exist');
        }

        unset($jsonobj->states);
    }

    private function check_rules(string $rulefilename, string $statename, array $state, int $nstate, int $nrule): void {
        foreach ($state as $rule) {
            $errmssg = "rule " . $nrule . " of state \"" . $statename . "\" nº" . $nstate . " must be an object";
            assertf::assert(is_object($rule), $rulefilename, $errmssg);

            $optionsdefined = [];

            foreach (array_keys(get_object_vars($rule)) as $optionname) {
                $errmssg = "invalid option " . $optionname . " at rule " . $nrule . " of state \"";
                $errmssg .= $statename . "\" nº" . $nstate;
                assertf::assert(array_key_exists($optionname, self::TOKENTYPES), $rulefilename, $errmssg);

                $optionsdefined[] = $optionname;
                $optionvalue = $rule->$optionname;
                $istypevalid = false;

                foreach (self::TOKENTYPES[$optionname] as $typevalue) {
                    $condtype = tokenizer_base::check_type($optionvalue, $typevalue);

                    if ($condtype === true) {
                        $istypevalid = true;
                        break;
                    }
                }

                $errmssg = "invalid data type for " . $optionname . " at rule " . $nrule . " of state \"";
                $errmssg .= $statename . "\" nº" . $nstate;
                assertf::assert($istypevalid, $rulefilename, $errmssg);

                if (strcmp($optionname, "token") == 0) {
                    $errmssg = "invalid token at rule " . $nrule . " of state \"" . $statename . "\" nº" . $nstate;
                    $cond = tokenizer_base::check_token($rule->$optionname, array_keys($this->availabletokens));
                    assertf::assert($cond, $rulefilename, $errmssg);
                }
            }

            foreach (self::REQUIREDGROUPRULEOPTIONS as $optionrequired => $group) {
                if (in_array($optionrequired, $optionsdefined)) {
                    foreach ($group as $optiong) {
                        $errmssg = "option " . $optionrequired . " must be defined next to " . $optiong . " at rule ";
                        $errmssg .= $nrule . " of state \"" . $statename . "\" nº" . $nstate;
                        assertf::assert(in_array($optiong, $optionsdefined), $rulefilename, $errmssg);
                    }
                }
            }

            if (in_array("default_token", $optionsdefined)) {
                $errmssg = "option default_token must be alone at rule " . $nrule . " of state \"";
                $errmssg .= $statename . "\" nº" . $nstate;
                assertf::assert(count($optionsdefined) == 1, $rulefilename, $errmssg);
            }

            $nrule = $nrule + 1;
        }
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
                    foreach ($srcvalue as $rulesrc) {
                        if (!tokenizer_base::contains_rule($this->states[$srcname], $rulesrc)) {
                            $this->states[$srcname][] = $rulesrc;
                        }
                    }
                }
            }
        }
    }
}
