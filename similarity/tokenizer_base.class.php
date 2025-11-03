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
 * Programing language tokenizer base class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

use mod_vpl\tokenizer\token;
use mod_vpl\tokenizer\token_type;

/**
 * Base class for tokenizers in VPL.
 */
class vpl_tokenizer_base {
    /**
     * @var string CR The carriage return character used for new lines in the source code.
     */
    const CR = "\r";

    /**
     * @var string LF The line feed character used for new lines in the source code.
     */
    const LF = "\n";

    /**
     * @var string TAB The tab character used for indentation in the source code.
     */
    const TAB = "\t";

    /**
     * @var array $reserved Reserved keywords for the programming language.
     */
    protected $reserved = [];

    /**
     * @var int $linenumber The current line number in the source code being tokenized.
     */
    protected $linenumber;
}
