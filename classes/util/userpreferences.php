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
 * User preferences utility functions for VPL.
 *
 * @package mod_vpl
 * @copyright 2026 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */
namespace mod_vpl\util;

/**
 * User preferences utility class for VPL.
 *
 * @copyright 2026 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */
class userpreferences {
    /** @var string Preference name for user preferences */
    public const PREFERENCE_NAME = 'vpl_preferences';
    /** @var array FIELDS and their types */
    public const FIELDS = [
        'editorTheme' => 'string',
        'editorFontSize' => 'integer',
        'editorKeyBinding' => 'string',
        'editorShowInvisibles' => 'boolean',
        'editorLiveAutocompletion' => 'boolean',
        'terminalTheme' => 'string',
        'terminalFontSize' => 'integer',
    ];
    /** @var array Default values for FIELDS */
    public const FIELDSDEFAULTS = [
        'editorTheme' => 'chrome',
        'editorFontSize' => 12,
        'editorKeyBinding' => 'Ace',
        'editorShowInvisibles' => false,
        'editorLiveAutocompletion' => false,
        'terminalTheme' => 'Default',
        'terminalFontSize' => 12,
    ];

    /**
     * Clean preferences removing unknown FIELDS and converting to correct types.
     *
     * @param object $preferences preferences object
     * @return object cleaned preferences object
     */
    public static function clean($preferences) {
        $cleaned = (object)[];
        foreach (self::FIELDS as $field => $type) {
            if (isset($preferences->$field)) {
                switch ($type) {
                    case 'string':
                        $cleaned->$field = @(string)$preferences->$field;
                        break;
                    case 'integer':
                        $cleaned->$field = (int)$preferences->$field;
                        break;
                    case 'boolean':
                        $cleaned->$field = (bool)$preferences->$field;
                        break;
                }
            }
        }
        return $cleaned;
    }

    /**
     * Update user preferences with new values.
     *
     * @param object $newpreferences new preferences object
     * @param int|null $userid user ID (null for current user)
     * @return object updated userpreferences
     */
    public static function update($newpreferences, $userid = null) {
        $save = false;
        if ($newpreferences !== null) {
            if (isset($newpreferences->reset)) {
                \unset_user_preference(self::PREFERENCE_NAME, $userid);
                unset($newpreferences->reset);
                $save = true;
            }
        } else {
            $save = true;
            $newpreferences = (object)[];
        }
        $newpreferences = self::clean($newpreferences);
        $preferences = self::get($userid);
        // Merge new preferences with old ones.
        foreach ($newpreferences as $key => $value) {
            if (!isset($preferences->$key) || $preferences->$key !== $value) {
                $preferences->$key = $value;
                $save = true;
            }
        }
        // Remove default values to save only differences.
        $fieldsused = 0;
        foreach (self::FIELDSDEFAULTS as $field => $default) {
            if (isset($preferences->$field)) {
                if ($preferences->$field === $default) {
                    unset($preferences->$field);
                    $save = true;
                } else {
                    $fieldsused++;
                }
            }
        }
        if ($fieldsused === 0) {
            \unset_user_preference(self::PREFERENCE_NAME, $userid);
        } else if ($save) {
            \set_user_preference(self::PREFERENCE_NAME, json_encode($preferences), $userid);
        }
        return $preferences;
    }

    /**
     * Get user preferences as object with attributes and values.
     *
     * @param int|null $userid user ID
     * @return object user preferences
     */
    public static function get($userid = null) {
        $jsonpreferences = \get_user_preferences(self::PREFERENCE_NAME, '{}', $userid);
        $preferences = json_decode($jsonpreferences);
        if (!is_object($preferences)) {
            $preferences = (object)[];
        }
        $preferences = self::clean($preferences);
        return $preferences;
    }
}
