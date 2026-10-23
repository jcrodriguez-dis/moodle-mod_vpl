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
 * Class to manage file icons for different file types.
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl\util;

/**
 * Class to manage file icons for different file types.
 */
class file_icon {
    /**
     * Shell scripts with a dedicated VPL execution icon.
     */
    private const EXECUTION_SCRIPTS = [
        'vpl_run.sh',
        'vpl_debug.sh',
        'vpl_evaluate.sh',
        'vpl_test_evaluate.sh',
        'pre_vpl_run.sh',
    ];

    /**
     * Get the shared filename mappings for the IDE.
     *
     * @return array
     */
    public static function get_client_config(): array {
        return [
            'extensions' => languages::EXTENSION2LANGUAGE,
            'filenames' => languages::CONFIG2LANGUAGE,
            'scripts' => self::EXECUTION_SCRIPTS,
        ];
    }

    /**
     * Get the icon identifier for a file.
     *
     * @param string $filename
     * @param string|null $data Optional file contents for binary detection.
     * @return string
     */
    public static function get_name(string $filename, ?string $data = null): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vpl/locallib.php');

        if (\vpl_is_image($filename)) {
            return 'image';
        }
        if (\vpl_is_audio($filename)) {
            return 'audio';
        }
        if (\vpl_is_video($filename)) {
            return 'video';
        }
        if (\vpl_is_binary($filename, $data)) {
            return 'binary';
        }
        if (\vpl_is_blockly($filename)) {
            return 'blockly';
        }
        if (in_array(basename($filename), self::EXECUTION_SCRIPTS, true)) {
            return 'vplscript';
        }
        $language = languages::get_language_from_filename(basename($filename));
        if ($language == '') {
            $language = 'text';
        }
        return $language;
    }

    /**
     * Get the SVG icon URL for a icon name.
     *
     * @param string $icon
     * @return \moodle_url
     */
    public static function get_url(string $icon): \moodle_url {
        return new \moodle_url('/mod/vpl/pix/fileicons/' . $icon . '.svg');
    }

    /**
     * Get the HTML img tag for a icon.
     *
     * @param string $icon
     * @param string $classes Optional CSS classes for the img tag.
     * @return string
     */
    public static function get_img(string $icon, string $classes = ""): string {
        $iconurl = self::get_url($icon);
        $alttext = 'alt="' . $icon . '" title="' . $icon . '"';
        $src = 'src="' . $iconurl->out() . '"';
        $classes = 'class="vpl_file_header_icon ' . $classes . '"';
        return "<img $src $alttext $classes>";
    }

    /**
     * Get simple filename with icon in HTML.
     * @param string $filename
     * @param string|null $data Optional file contents for binary detection.
     * @return string
     */
    public static function get_filename_with_icon(string $filename, ?string $data = null): string {
        $icon = self::get_name($filename, $data);
        $img = self::get_img($icon);
        return $img . '&nbsp;' . s($filename);
    }
}
