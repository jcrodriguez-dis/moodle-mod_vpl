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

namespace mod_vpl\util;

/**
 * Bounded archive reader for submission uploads.
 *
 * @package mod_vpl
 * @copyright 2026 Juan Carlos Rodriguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_archive {
    /**
     * Read required files first, skipping excess additional files without expanding them.
     *
     * @param string $filename Temporary ZIP filename; removed on success or failure.
     * @param array $required Required filenames in submission order.
     * @param int $maxfiles Maximum number of retained files.
     * @param int $maxfilesize Maximum bytes per file, before and after conversion.
     * @param int $maxbytes Maximum aggregate bytes expanded and retained.
     * @param array $previous Files retained when replacing only archive contents.
     * @param int $ignored Number of additional entries skipped.
     * @return array Files indexed by logical filename.
     */
    public static function read(
        string $filename,
        array $required,
        int $maxfiles,
        int $maxfilesize,
        int $maxbytes,
        array $previous,
        int &$ignored
    ): array {
        $zip = new \ZipArchive();
        $opened = false;
        $ignored = 0;
        try {
            if ($zip->open($filename) !== true) {
                throw new \moodle_exception('cannotopenzip');
            }
            $opened = true;
            $entries = [];
            $retained = array_fill_keys(array_keys($previous), true);
            $size = 0;
            // Select only a bounded number of entries before opening any decompression streams.
            foreach ([true, false] as $requiredpass) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $stat = $zip->statIndex($i);
                    if ($stat === false) {
                        throw new \moodle_exception('error:invalidfile', 'mod_vpl', '', $filename);
                    }
                    $name = $stat['name'];
                    if (substr($name, -1) === '/') {
                        continue;
                    }
                    $isrequired = in_array($name, $required, true) || in_array($name . '.b64', $required, true);
                    if ($isrequired !== $requiredpass) {
                        continue;
                    }
                    $logicalname = in_array($name, $required, true) ? $name
                        : ($isrequired ? $name . '.b64' : $name);
                    if (!isset($retained[$logicalname]) && count($retained) >= $maxfiles) {
                        if ($isrequired) {
                            throw new \moodle_exception('maxfilesexceeded', 'mod_vpl');
                        }
                        $ignored++;
                        continue;
                    }
                    if (!vpl_is_valid_path_name($name)) {
                        throw new \moodle_exception('error:invalidfile', 'mod_vpl', '', $name);
                    }
                    if (isset($entries[$name])) {
                        throw new \moodle_exception('error:invalidfile', 'mod_vpl', '', $name);
                    }
                    if ($stat['size'] > $maxfilesize) {
                        throw new \moodle_exception('maxfilesizeexceeded', 'mod_vpl');
                    }
                    if ($stat['size'] > $maxbytes - $size) {
                        throw new \moodle_exception('maxpostsizeexceeded', 'mod_vpl');
                    }
                    $size += $stat['size'];
                    $entries[$name] = $stat;
                    $retained[$logicalname] = true;
                }
            }
            $files = $previous;
            $expanded = 0;
            $size = array_sum(array_map('strlen', $files));
            foreach ($entries as $name => $stat) {
                $stream = $zip->getStream($name);
                if ($stream === false) {
                    throw new \moodle_exception('error:invalidfile', 'mod_vpl', '', $name);
                }
                $content = '';
                try {
                    while (!feof($stream)) {
                        $remaining = min($maxfilesize - strlen($content), $maxbytes - $expanded);
                        $chunk = fread($stream, min(8192, max(1, $remaining + 1)));
                        if ($chunk === false || ($chunk === '' && !feof($stream))) {
                            throw new \moodle_exception('error:invalidfile', 'mod_vpl', '', $name);
                        }
                        $expanded += strlen($chunk);
                        if (strlen($content) + strlen($chunk) > $maxfilesize) {
                            throw new \moodle_exception('maxfilesizeexceeded', 'mod_vpl');
                        }
                        if ($expanded > $maxbytes) {
                            throw new \moodle_exception('maxpostsizeexceeded', 'mod_vpl');
                        }
                        $content .= $chunk;
                    }
                } finally {
                    fclose($stream);
                }
                if (!vpl_is_binary($name, $content)) {
                    $encoding = mb_detect_encoding($content, 'UNICODE, UTF-16, UTF-8, ISO-8859-1', true);
                    if ($encoding !== false) {
                        $converted = iconv($encoding, 'UTF-8', $content);
                        if ($converted === false) {
                            throw new \moodle_exception('error:invalidfile', 'mod_vpl', '', $name);
                        }
                        $content = $converted;
                    }
                } else if (in_array($name . '.b64', $required, true)) {
                    $name .= '.b64';
                    $content = base64_encode($content);
                }
                if (strlen($content) > $maxfilesize) {
                    throw new \moodle_exception('maxfilesizeexceeded', 'mod_vpl');
                }
                $size += strlen($content) - (isset($files[$name]) ? strlen($files[$name]) : 0);
                if ($size > $maxbytes) {
                    throw new \moodle_exception('maxpostsizeexceeded', 'mod_vpl');
                }
                $files[$name] = $content;
            }
            if (count($files) > $maxfiles) {
                throw new \moodle_exception('maxfilesexceeded', 'mod_vpl');
            }
            if ($size > $maxbytes) {
                throw new \moodle_exception('maxpostsizeexceeded', 'mod_vpl');
            }
            return array_replace(array_intersect_key(array_fill_keys($required, ''), $files), $files);
        } finally {
            if ($opened) {
                $zip->close();
            }
            unlink($filename);
        }
    }
}
