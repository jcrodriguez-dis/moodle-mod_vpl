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

/**
 * @codeCoverageIgnore
 */
class assertf {
    /**
     * Flag to enable or disable the colors at error messages.
     * This option must be false at production.
     */
    public static bool $messagewithcolors = true;

    /**
     * Get error message for passed filename and message
     *
     * @param ?string $filename location on which error has been thrown
     * @param string $message customized error message
     * @return string
     */
    public static function get_error(?string $filename, string $message): string {
        if (self::$messagewithcolors) {
            $messagecustomized = isset($filename) ? "\e[1m" . basename($filename) . "\e[0m\e[1m:\e[0m " : "";
            $messagecustomized .= "\e[0;31merror:\e[0m " . $message;
        } else {
            $messagecustomized = isset($filename) ? basename($filename) . ": " : "";
            $messagecustomized .= "error: " . $message;
        }

        return $messagecustomized;
    }

    /**
     * Check a condition and throw an exception if it fails
     *
     * @param bool $cond condition that would be checked
     * @param ?string $filename location on which error has been thrown
     * @param string $message customized error message
     * @return void
     */
    public static function assert(bool $cond, ?string $filename, string $message): void {
        if ($cond != true) {
            $messagecustomized = self::get_error($filename, $message);
            throw new Exception($messagecustomized);
        }
    }

    /**
     * Show an error with passed filename and message
     *
     * @param ?string $filename location on which error has been thrown
     * @param string $message customized error message
     * @return void
     */
    public static function showerr(?string $filename, string $message): void {
        $messagecustomized = self::get_error($filename, $message);
        fwrite(STDERR, $messagecustomized);
    }
}
