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
 * Languages utility functions for VPL.
 *
 * @package mod_vpl
 * @copyright 2026 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */
namespace mod_vpl\util;

/**
 * Languages utility class for VPL.
 *
 * @copyright 2026 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */
class languages {
    /**
     * Maps language identifiers to their file extensions.
     */
    public const LANGUAGES2EXTENSION = [
        'ada' => ['ads', 'adb', 'ada'],
        'all' => ['all'],
        'asm' => ['asm', 'nasm', 'inc'],
        'cases' => ['cases'],
        'c' => ['c'],
        'clojure' => ['clj'],
        'cobol' => ['cob', 'cbl'],
        'cpp' => ['h', 'hxx', 'cc', 'C', 'cpp', 'c++'],
        'csharp' => ['cs'],
        'd' => ['d'],
        'dart' => ['dart'],
        'eiffel' => ['e'],
        'erlang' => ['erl', 'hrl'],
        'fortran' => ['f77', 'f90', 'f', 'for'],
        'fsharp' => ['fs'],
        'go' => ['go'],
        'groovy' => ['groovy'],
        'haskell' => ['hs'],
        'html' => ['htm', 'html'],
        'java' => ['java'],
        'javascript' => ['js'],
        'julia' => ['jl'],
        'kotlin' => ['kt'],
        'lisp' => ['lisp', 'lsp'],
        'lua' => ['lua'],
        'matlab' => ['m'],
        'minizinc' => ['mzn'],
        'mips' => ['s', 'mips'],
        'pascal' => ['pas', 'p'],
        'perl' => ['perl', 'prl'],
        'php' => ['php'],
        'prolog' => ['pl', 'pro'],
        'pseint' => ['psc'],
        'python' => ['py'],
        'r' => ['r', 'R'],
        'ruby' => ['rb', 'ruby'],
        'rust' => ['rs', 'rust'],
        'scala' => ['scala'],
        'scheme' => ['scm'],
        'shell' => ['sh'],
        'sql' => ['sql'],
        'typescript' => ['ts', 'tsx'],
        'verilog' => ['v', 'vh'],
        'vhdl' => ['vhd', 'vhdl'],
        'visualbasic' => ['vb'],
    ];
    /**
     * Associative array for detecting the programming language
     * based on a file's extension.
     * @var array
     */
    public const EXTENSION2LANGUAGE = [
        'ada' => 'ada',
        'adb' => 'ada',
        'ads' => 'ada',
        'all' => 'all',
        'asm' => 'asm',
        'nasm' => 'asm',
        'inc' => 'asm',
        'cases' => 'cases',
        'c' => 'c',
        'cc' => 'cpp',
        'cpp' => 'cpp',
        'C' => 'cpp',
        'c++' => 'cpp',
        'h' => 'cpp',
        'hxx' => 'cpp',
        'cbl' => 'cobol',
        'cob' => 'cobol',
        'clj' => 'clojure',
        'cs' => 'csharp',
        'd' => 'd',
        'dart' => 'dart',
        'e' => 'eiffel',
        'erl' => 'erlang',
        'hrl' => 'erlang',
        'f77' => 'fortran',
        'f90' => 'fortran',
        'f' => 'fortran',
        'for' => 'fortran',
        'fs' => 'fsharp',
        'go' => 'go',
        'groovy' => 'groovy',
        'hs' => 'haskell',
        'htm' => 'html',
        'html' => 'html',
        'java' => 'java',
        'js' => 'javascript',
        'jl' => 'julia',
        'kt' => 'kotlin',
        'lisp' => 'lisp',
        'lsp' => 'lisp',
        'lua' => 'lua',
        'm' => 'matlab',
        'mzn' => 'minizinc',
        's' => 'mips',
        'mips' => 'mips',
        'pas' => 'pascal',
        'p' => 'pascal',
        'perl' => 'perl',
        'prl' => 'perl',
        'php' => 'php',
        'pl' => 'prolog',
        'pro' => 'prolog',
        'psc' => 'pseint',
        'py' => 'python',
        'r' => 'r',
        'R' => 'r',
        'rb' => 'ruby',
        'ruby' => 'ruby',
        'rs' => 'rust',
        'rust' => 'rust',
        'scala' => 'scala',
        'scm' => 'scheme',
        'sh' => 'shell',
        'sql' => 'sql',
        'ts' => 'typescript',
        'tsx' => 'typescript',
        'v' => 'verilog',
        'vh' => 'verilog',
        'vb' => 'visualbasic',
        'vhd' => 'vhdl',
        'vhdl' => 'vhdl',
    ];
    /**
     * Associative array for detecting the build system
     * based on the configuration file name.
     * @var array
     */
    public const CONFIG2LANGUAGE = [
        'Makefile' => 'make',
        'makefile' => 'make',
    ];
    /*
        TODO:
        Future config files to support.
        CMakeLists.txt => cmake
        build.ninja => ninja
        build.xml => ant
        build.gradle => gradle
        pom.xml => maven
    */
    /**
     * Return the name of the programming language used.
     * The name of the programming language
     * is based on finding a build config file
     * or, if not found, on the first known file extension found.
     *
     * @param array $filelist of files
     * @return string programming language name
     */
    public static function get_language_from_file_list($filelist) {
        foreach ($filelist as $checkfilename) {
            if (isset(self::CONFIG2LANGUAGE[$checkfilename])) {
                return self::CONFIG2LANGUAGE[$checkfilename];
            }
        }
        foreach ($filelist as $checkfilename) {
            $ext = pathinfo($checkfilename, PATHINFO_EXTENSION);
            if (isset(self::EXTENSION2LANGUAGE[$ext])) {
                return self::EXTENSION2LANGUAGE[$ext];
            }
        }
        return '';
    }
    /**
     * Return the name of the programming language used.
     * The name of the programming language
     * is based on finding a build config file
     * or, if not found, on the first known file extension found.
     *
     * @param string $filename
     * @return string programming language name
     */
    public static function get_language_from_filename($filename) {
        if (isset(self::CONFIG2LANGUAGE[$filename])) {
            return self::CONFIG2LANGUAGE[$filename];
        }

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (isset(self::EXTENSION2LANGUAGE[$ext])) {
            return self::EXTENSION2LANGUAGE[$ext];
        }
        return '';
    }
}
