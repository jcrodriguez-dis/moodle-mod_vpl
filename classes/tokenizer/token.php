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
 * VPLT:: Token class for tokenizers
 *
 * @package mod_vpl
 * @copyright 2022 David Parreño Barbuzano
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author David Parreño Barbuzano <losedavidpb@gmail.com>
 */
namespace mod_vpl\tokenizer;

/**
 * @codeCoverageIgnore
 */
class token {
    private static array $hashvalues = [];

    /**
     * Type of current token
     */
    public $type;

    /**
     * @var ?string Specific value of current token
     */
    public ?string $value;

    /**
     * @var int Type of current token
     */
    public int $line;

    /**
     * Creates a new token with passed type and value
     *
     * @param $type type of current token
     * @param ?string $value value of current token
     * @param int $line number of line of current token
     */
    public function __construct($type, ?string $value, int $line) {
        $this->line = $line;
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * Check if passed token is equals to current one
     *
     * @param token $othertoken token to compare with current one
     * @return bool
     */
    public function equals_to(token $othertoken): bool {
        return (
            $othertoken->type === $this->type &&
            $othertoken->value === $this->value &&
            $othertoken->line === $this->line
        );
    }

    /**
     * Get hashcode for current token
     *
     * @return int
     */
    public function hash(): int {
        if (!isset(self::$hashvalues[$this->value])) {
            self::$hashvalues[$this->value] = mt_rand();
        }

        return self::$hashvalues[$this->value];
    }

    /**
     * Show token at current output channel
     */
    public function show(): void {
        echo $this->line . ' ' . $this->type . ' ' . $this->value . '<br>';
    }
}
