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
 * Class to manage PHP configuration
 *
 * @package   mod_vpl
 * @copyright 2020 onwards Juan Carlos Rodríguez-del-Pino
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\util;

/**
 * Class to manage PHP config
 */
class phpconfig {
    /**
     * Keeps conversions from Kb, Mb or Gb to bytes.
     *
     * @var array Key 'k', 'm', 'g' => value
     */
    const BYTECONVERTER = array( '' => 1,
                                 'k' => 1024,
                                 'm' => 1024 * 1024,
                                 'g' => 1024 * 1024 * 1024 );
    /**
     * Returns number of bytes from string values in Kb, Mb or Gb
     *
     * @param string $value Value to convert e.g 2M, 1.5Gb, 32K
     *
     * @return int Nummer of bytes
     */
    public static function get_bytes(string $value): int {
        $value = strtolower(trim($value));
        $regexp = '/^[\s]*([0-9]+)[\s]*(:?k|m|g|)b?[ \t]*$/';
        if (preg_match($regexp, $value, $matches) == 1) {
            $number = (float) $matches[1];
            $unityvalue = self::BYTECONVERTER[$matches[2]];
            if ( $number < PHP_INT_MAX / $unityvalue ) {
                $bytes = (int) ($number * $unityvalue);
            } else {
                $bytes = PHP_INT_MAX;
            }
        } else {
            $bytes = PHP_INT_MAX;
        }
        return $bytes;
    }

    /**
     * Return the value of a ini paramater
     *
     * @param string $param Name of the parameter
     * @return int Number of bytes
     */
    public static function get_ini_value($param): int {
        return self::get_bytes(ini_get($param));
    }

    /**
     * Return the post maximum size in bytes
     *
     * @return int Number of bytes
     */
    public static function get_post_max_size_internal($value): int {
        $number = self::get_bytes($value);
        if ($number <= 0) {
            $number = PHP_INT_MAX;
        }
        return $number;
    }

    /**
     * Return the post maximum size in bytes
     *
     * @return int Number of bytes
     */
    public static function get_post_max_size(): int {
        return self::get_post_max_size_internal(ini_get('post_max_size'));
    }

    /**
     * Increase PHP memory limit to post_max_size * 3
     *
     * @return void
     */
    public static function increase_memory_limit(): void {
        gc_enable();
        $maxpost = self::get_post_max_size();
        if ( $maxpost < PHP_INT_MAX / 3 ) {
            $bytes = $maxpost * 3;
        } else {
            $bytes = PHP_INT_MAX;
        }
        if ($bytes > self::get_ini_value('memory_limit') && $bytes > memory_get_usage()) {
            $newmemorylimit = (int) ($bytes / self::BYTECONVERTER['k']);
            ini_set('memory_limit', $newmemorylimit . 'K');
        }
    }

    /**
     * Throws an exception if the PHP free memory is less than needed
     *
     * @param int $memoryneeded Memory needed
     *
     * @return void
     */
    public static function checks_free_memory(int $memoryneeded): void {
        $memoryused = memory_get_usage();
        $memorylimit = self::get_bytes(ini_get('memory_limit'));
        if ($memorylimit - $memoryused < $memoryneeded) {
            self::increase_memory_limit();
            $memorylimit = self::get_bytes(ini_get('memory_limit'));
            if ($memorylimit - $memoryused < $memoryneeded) {
                throw new \Exception(get_string('outofmemory', 'vpl'));
            }
        }
    }
}
