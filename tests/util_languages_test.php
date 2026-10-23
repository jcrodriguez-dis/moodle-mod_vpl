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
 * Unit tests for the mod_vpl\util\languages utility class.
 *
 * Covers:
 *  - LANGUAGES2EXTENSION and EXTENSION2LANGUAGE constant integrity
 *  - Bidirectional consistency between the two maps
 *  - get_language_from_filename()
 *  - get_language_from_file_list()
 *
 * @package mod_vpl
 * @copyright 2026 Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_vpl;

use mod_vpl\util\languages;

/**
 * Unit tests for \mod_vpl\util\languages.
 *
 * @group mod_vpl
 * @group mod_vpl_util
 * @covers \mod_vpl\util\languages
 */
final class util_languages_test extends \advanced_testcase {
    /**
     * LANGUAGES2EXTENSION must be a non-empty associative array whose values are
     * non-empty arrays of extension strings.
     *
     * @covers \mod_vpl\util\languages::LANGUAGES2EXTENSION
     */
    public function test_languages2extension_is_valid(): void {
        $map = languages::LANGUAGES2EXTENSION;

        $this->assertIsArray($map, 'LANGUAGES2EXTENSION should be an array');
        $this->assertNotEmpty($map, 'LANGUAGES2EXTENSION should not be empty');

        foreach ($map as $lang => $exts) {
            $this->assertIsString($lang, 'Language key should be a string');
            $this->assertNotEmpty($lang, 'Language key should not be empty');
            $this->assertIsArray($exts, "Extensions for '{$lang}' should be an array");
            $this->assertNotEmpty($exts, "Extensions for '{$lang}' should not be empty");
            foreach ($exts as $ext) {
                $this->assertIsString($ext, "Each extension for '{$lang}' should be a string");
                $this->assertNotEmpty($ext, "Each extension for '{$lang}' should not be empty");
            }
        }
    }

    /**
     * EXTENSION2LANGUAGE must be a non-empty associative array whose values are
     * non-empty language-name strings.
     *
     * @covers \mod_vpl\util\languages::EXTENSION2LANGUAGE
     */
    public function test_extension2language_is_valid(): void {
        $map = languages::EXTENSION2LANGUAGE;

        $this->assertIsArray($map, 'EXTENSION2LANGUAGE should be an array');
        $this->assertNotEmpty($map, 'EXTENSION2LANGUAGE should not be empty');

        foreach ($map as $ext => $lang) {
            $this->assertIsString($ext, 'Extension key should be a string');
            $this->assertNotEmpty($ext, 'Extension key should not be empty');
            $this->assertIsString($lang, "Language for extension '{$ext}' should be a string");
            $this->assertNotEmpty($lang, "Language for extension '{$ext}' should not be empty");
        }
    }

    /**
     * CONFIG2LANGUAGE must be a non-empty associative array whose values are
     * non-empty language-name strings.
     *
     * @covers \mod_vpl\util\languages::CONFIG2LANGUAGE
     */
    public function test_config2language_is_valid(): void {
        $map = languages::CONFIG2LANGUAGE;

        $this->assertIsArray($map, 'CONFIG2LANGUAGE should be an array');
        $this->assertNotEmpty($map, 'CONFIG2LANGUAGE should not be empty');

        foreach ($map as $configfile => $lang) {
            $this->assertIsString($configfile, 'Config file key should be a string');
            $this->assertNotEmpty($configfile, 'Config file key should not be empty');
            $this->assertIsString($lang, "Language for config file '{$configfile}' should be a string");
            $this->assertNotEmpty($lang, "Language for config file '{$configfile}' should not be empty");
        }
    }

    /**
     * Every extension listed in LANGUAGES2EXTENSION must appear in EXTENSION2LANGUAGE
     * and map back to the same (or an equivalent) language.
     *
     * @covers \mod_vpl\util\languages::LANGUAGES2EXTENSION
     * @covers \mod_vpl\util\languages::EXTENSION2LANGUAGE
     */
    public function test_languages2extension_covered_by_extension2language(): void {
        $l2e = languages::LANGUAGES2EXTENSION;
        $e2l = languages::EXTENSION2LANGUAGE;

        foreach ($l2e as $lang => $exts) {
            foreach ($exts as $ext) {
                $this->assertArrayHasKey(
                    $ext,
                    $e2l,
                    "Extension '{$ext}' (from language '{$lang}') is missing in EXTENSION2LANGUAGE"
                );
                $this->assertSame(
                    $lang,
                    $e2l[$ext],
                    "Extension '{$ext}' maps to '{$e2l[$ext]}' but is listed under '{$lang}'"
                );
            }
        }
    }

    /**
     * Every extension in EXTENSION2LANGUAGE must appear in at least one entry of
     * LANGUAGES2EXTENSION under the language it maps to.
     *
     * @covers \mod_vpl\util\languages::EXTENSION2LANGUAGE
     * @covers \mod_vpl\util\languages::LANGUAGES2EXTENSION
     */
    public function test_extension2language_covered_by_languages2extension(): void {
        $l2e = languages::LANGUAGES2EXTENSION;
        $e2l = languages::EXTENSION2LANGUAGE;

        // Build a flat set of all extensions declared in LANGUAGES2EXTENSION.
        $declaredexts = [];
        foreach ($l2e as $exts) {
            foreach ($exts as $ext) {
                $declaredexts[$ext] = true;
            }
        }

        foreach ($e2l as $ext => $lang) {
            $this->assertArrayHasKey(
                $ext,
                $declaredexts,
                "Extension '{$ext}' (mapped to '{$lang}') is missing from LANGUAGES2EXTENSION"
            );
            $this->assertArrayHasKey(
                $lang,
                $l2e,
                "Language '{$lang}' for extension '{$ext}' is not in LANGUAGES2EXTENSION"
            );
            $this->assertContains(
                $ext,
                $l2e[$lang],
                "Extension '{$ext}' is not listed under '{$lang}' in LANGUAGES2EXTENSION"
            );
        }
    }

    /**
     * Known extensions should resolve to the expected language.
     *
     * @covers \mod_vpl\util\languages::get_language_from_filename
     */
    public function test_get_language_from_filename_known_extensions(): void {
        $cases = [
            'hello.c'      => 'c',
            'hello.cpp'    => 'cpp',
            'hello.h'      => 'cpp',
            'hello.hpp'    => 'cpp',
            'hello.java'   => 'java',
            'hello.py'     => 'python',
            'hello.js'     => 'javascript',
            'hello.ts'     => 'typescript',
            'hello.html'   => 'html',
            'hello.htm'    => 'html',
            'hello.cs'     => 'csharp',
            'hello.rb'     => 'ruby',
            'hello.go'     => 'go',
            'hello.rs'     => 'rust',
            'hello.php'    => 'php',
            'hello.pl'     => 'prolog',
            'hello.scm'    => 'scheme',
            'hello.hs'     => 'haskell',
            'hello.vhd'    => 'vhdl',
            'hello.vhdl'   => 'vhdl',
            'hello.v'      => 'verilog',
            'hello.vh'     => 'verilog',
        ];

        foreach ($cases as $filename => $expectedlang) {
            $result = languages::get_language_from_filename($filename);
            $this->assertSame(
                $expectedlang,
                $result,
                "get_language_from_filename('{$filename}') should return '{$expectedlang}'"
            );
        }
    }

    /**
     * Config filenames (Makefile / makefile) should resolve to 'make'.
     *
     * @covers \mod_vpl\util\languages::get_language_from_filename
     */
    public function test_get_language_from_filename_config_files(): void {
        $this->assertSame('make', languages::get_language_from_filename('Makefile'));
        $this->assertSame('make', languages::get_language_from_filename('makefile'));
    }

    /**
     * Unknown extensions and empty strings should return an empty string.
     *
     * @covers \mod_vpl\util\languages::get_language_from_filename
     */
    public function test_get_language_from_filename_unknown(): void {
        $this->assertSame('', languages::get_language_from_filename('file.xyz123'));
        $this->assertSame('', languages::get_language_from_filename('file.unknown'));
        $this->assertSame('', languages::get_language_from_filename('nodotfile'));
        $this->assertSame('', languages::get_language_from_filename(''));
    }

    /**
     * A filename with multiple dots should use only the last extension.
     *
     * @covers \mod_vpl\util\languages::get_language_from_filename
     */
    public function test_get_language_from_filename_multiple_dots(): void {
        $this->assertSame('java', languages::get_language_from_filename('com.example.Main.java'));
        $this->assertSame('c', languages::get_language_from_filename('lib.extra.c'));
    }

    /**
     * The first recognisable extension in the list determines the language.
     *
     * @covers \mod_vpl\util\languages::get_language_from_file_list
     */
    public function test_get_language_from_file_list_first_match(): void {
        $list = ['readme.txt', 'main.java', 'helper.java'];
        $this->assertSame('java', languages::get_language_from_file_list($list));

        $list2 = ['main.py', 'utils.py'];
        $this->assertSame('python', languages::get_language_from_file_list($list2));

        // Pairwise language priority: first recognised extension wins.
        $cases = [
            'c'          => ['program.c', 'program.cpp'],
            'java'       => ['program.java', 'program.py'],
            'python'     => ['program.py', 'program.js'],
            'javascript' => ['program.js', 'program.rb'],
            'ruby'       => ['program.rb', 'program.go'],
            'go'         => ['program.go', 'program.rs'],
            'rust'       => ['program.rs', 'program.php'],
            'php'        => ['program.php', 'program.pl'],
            'prolog'     => ['program.pl', 'program.hs'],
            'haskell'    => ['program.hs', 'program.vhd'],
            'vhdl'       => ['program.vhd', 'program.vhdl'],
            'verilog'    => ['program.v', 'program.vh'],
        ];
        foreach ($cases as $expected => $files) {
            $this->assertSame(
                $expected,
                languages::get_language_from_file_list($files),
                "File list ['" . implode("', '", $files) . "'] should resolve to '{$expected}'"
            );
        }
    }

    /**
     * A config filename (Makefile) must take priority over source-file extensions.
     *
     * @covers \mod_vpl\util\languages::get_language_from_file_list
     */
    public function test_get_language_from_file_list_config_priority(): void {
        $list = ['main.cpp', 'Makefile', 'README'];
        $this->assertSame('make', languages::get_language_from_file_list($list));

        $list2 = ['src/main.c', 'makefile'];
        $this->assertSame('make', languages::get_language_from_file_list($list2));
    }

    /**
     * An empty file list or a list with no recognisable files must return an empty string.
     *
     * @covers \mod_vpl\util\languages::get_language_from_file_list
     */
    public function test_get_language_from_file_list_no_match(): void {
        $this->assertSame('', languages::get_language_from_file_list([]));
        $this->assertSame('', languages::get_language_from_file_list(['readme.txt', 'data.csv']));
    }

    /**
     * A list containing only files with unknown extensions must return an empty string.
     *
     * @covers \mod_vpl\util\languages::get_language_from_file_list
     */
    public function test_get_language_from_file_list_all_unknown(): void {
        $list = ['file.xyz', 'file.abc', 'file.qqq'];
        $this->assertSame('', languages::get_language_from_file_list($list));
    }
}
