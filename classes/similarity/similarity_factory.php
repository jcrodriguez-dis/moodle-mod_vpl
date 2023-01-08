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
 * Factory class for getting the proper similarity processor based on filename extension.
 *
 * @package mod_vpl
 * @copyright 2022 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\similarity;

use Exception;
use mod_vpl\similarity\similarity_generic;

class similarity_factory {
    /**
     * @var string[] $ext2typearray Relates file extension to file type.
     */
    private static array $ext2typearray = [
        'h' => 'cpp',
        'hxx' => 'cpp',
        'c' => 'c',
        'js' => 'c', // JavaScript as C.
        'cc' => 'cpp',
        'C' => 'cpp',
        'cpp' => 'cpp',
        'cs' => 'cpp', // C# as C++.
        'ads' => 'ada',
        'adb' => 'ada',
        'ada' => 'ada',
        'java' => 'java',
        'Java' => 'java',
        'scm' => 'scheme',
        'pl' => 'prolog',
        'scala' => 'scala',
        'py' => 'python',
        'm' => 'matlab',
        'html' => 'html',
        'htm' => 'html'
    ];

    /**
     * @codeCoverageIgnore
     *
     * Get all available languages for similarity
     *
     * @return array
     */
    protected static function get_available_languages(): array {
        return array_unique(array_values(self::$ext2typearray));
    }

    /**
     * Returns the file type of a file extension.
     *
     * @param string $ext File extesion
     * @return string|false File type or false if not found
     */
    public static function ext2type(string $ext) {
        if (isset(self::$ext2typearray[$ext])) {
            return self::$ext2typearray[$ext];
        } else {
            return false;
        }
    }

    /**
     * @var string[] $classloaded Saves legaced classes loaded.
     */
    private static array $classloaded = [];

    /**
     * Returns an object of a class derived from similarity_base to process a file of a type.
     *
     * @param string $type File type
     * @return object Object of a class derived from similarity_base
     */
    private static function get_object(string $type) {
        $similarityclass = self::get_with_similarity_class($type);

        if (!isset($similarityclass)) {
            $similarityclass = self::get_with_generic($type);

            if (!isset($similarityclass)) {
                $similarityclass = self::get_with_old_similarity_class($type);
            }
        }

        return $similarityclass;
    }

    private static function get_with_similarity_class(string $type) {
        $similarityclass = '\mod_vpl\similarity\similarity_' . $type;

        if (class_exists($similarityclass) === true) {
            return new $similarityclass();
        } else {
            return null;
        }
    }

    private static function get_with_generic(string $type) {
        $tokenizerrule = dirname(__FILE__) . '/../../similarity/tokenizer_rules/';
        $tokenizerrule .= $type . '_tokenizer_rules.json';

        if (file_exists($tokenizerrule) === true) {
            return new similarity_generic($type);
        } else {
            return null;
        }
    }

    private static function get_with_old_similarity_class(string $type) {
        if (!isset(self::$classloaded[$type])) {
            $include = dirname(__FILE__) . '/../../similarity/similarity_';
            $include .= $type . '.class.php';

            try {
                require_once($include);
                self::$classloaded[$type] = true;
                // @codeCoverageIgnoreStart
            } catch (Exception $exe) {
                return null;
            }
            // @codeCoverageIgnoreEnd
        }

        $similarityclass = '\vpl_similarity_' . $type;
        return new $similarityclass();
    }

    /**
     * Get similarity class for passed programming language
     *
     * @param string $namelang name of a programming language
     * @return ?similariy|?vpl_similarity|?similarity_generic
     */
    public static function get(string $filename) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $type = self::ext2type($ext);

        if ($type != false) {
            return self::get_object($type);
        } else {
            return null;
        }
    }
}
