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
 * Assert with customized error messages
 *
 * @package mod_vpl
 * @copyright 2022 David Parreño Barbuzano
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author David Parreño Barbuzano <losedavidpb@gmail.com>
 */
namespace mod_vpl\util;

use Exception;

class assertf {
    /**
     * Flag to enable or disable the colors at error messages.
     * This option must be false at production.
     */
    public const SET_MESSAGES_WITH_COLORS = true;

    /**
     * Get error message for passed filename and message
     *
     * @param string $filename location on which error has been thrown
     * @param string $message customized error message
     * @return string
     */
    static function get_error(string $filename, string $message): string {
        if (self::SET_MESSAGES_WITH_COLORS) {
            $message_ = isset($filename)? "\e[1m" . $filename . "\e[0m" : "";
            $message_ .= "\e[1m:\e[0m \e[0;31merror:\e[0m " . $message;
        } else {
            $message_ = isset($filename)? $filename : "";
            $message_ .= "error: " . $message;
        }

        return $message_;
    }

    /**
     * Check a condition and throw an exception if it fails
     *
     * @param bool $cond condition that would be checked
     * @param ?string $filename location on which error has been thrown
     * @param string $message customized error message
     * @return void
     */
    static function assert(bool $cond, ?string $filename, string $message): void {
        if ($cond != true) {
            $message_ = self::get_error($filename, $message);
            throw new Exception($message_);
        }
    }

}