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
 * VPLT:: Token types for tokenizers
 *
 * @package mod_vpl
 * @copyright 2022 David Parreño Barbuzano
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author David Parreño Barbuzano <losedavidpb@gmail.com>
 */
namespace mod_vpl\tokenizer;

/**
 * @codeCoverageIgnore
 *
 * List of available tokens used as a fixture to define
 * compatible tokens for similarity
 */
class token_type {
    /**
     * @var int Reserved words that only has one meaning
     */
    public const RESERVED = 1;

    /**
     * @var int Unique name that identify a token
     */
    public const IDENTIFIER = 2;

    /**
     * @var int Symbols of operators used at calculations
     */
    public const OPERATOR = 3;

    /**
     * @var int Constants for values
     */
    public const LITERAL = 4;

    /**
     * @var int Other types of tokens
     */
    public const OTHER = 5;
}
