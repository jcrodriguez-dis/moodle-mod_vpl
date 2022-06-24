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
 * Generic definition for tokenizers
 *
 * @package mod_vpl
 * @copyright 2022 David Parreño Barbuzano
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author David Parreño Barbuzano <losedavidpb@gmail.com>
 */
namespace mod_vpl\tokenizer;

/**
 * Tokenizer must always implement the methods
 * defined at this interface.
 */
interface tokenizer {
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
    public function init(string $rulefilename): void;

    /**
     * Parse all lines of passed file
     *
     * @param string $filename file to parse
     * @return array
     */
    public function parse(string $filename): array;

    /**
     * Get all tokens for passed line based on Ace Editor
     * (https://github.com/ajaxorg/ace/blob/master/lib/ace/tokenizer.js).
     *
     * @param string $line content of the line
     * @param string $startstate state on which stack would start
     * @return array
     */
    public function get_line_tokens(string $line, string $startstate=""): array;
}
