<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Observer for user merging.
 * @package    mod_vpl
 * @copyright  2026 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_vpl\observer;

use logstore_standard;

/**
 * Observer definition.
 * @copyright  2026 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mergeusers {
    /**
     * Observer called every time an user is merged.
     * @param \tool_mergeusers\event\user_merged_success $event
     */
    public static function user_merged(\tool_mergeusers\event\user_merged_success $event) {
        global $CFG;
        if (!is_dir($CFG->dataroot . '/vpl_data')) {
            return;
        }
        $fromid = $event->other['usersinvolved']['fromid'];
        $toid = $event->other['usersinvolved']['toid'];
        foreach (array_diff(scandir($CFG->dataroot . '/vpl_data'), [ '.', '..' ]) as $vplid) {
            $basedir = $CFG->dataroot . '/vpl_data/' . $vplid . '/usersdata';
            if (is_dir($basedir . '/' . $fromid)) {
                // Merged user has data in this VPL.
                foreach (array_diff(scandir($basedir . '/'  . $fromid), [ '.', '..' ]) as $subid) {
                    if (!is_dir($basedir . '/' . $toid)) {
                        mkdir($basedir . '/' . $toid);
                    }
                    rename($basedir . '/' . $fromid . '/' . $subid, $basedir . '/' . $toid . '/' . $subid);
                }
                rmdir($basedir . '/' . $fromid);
            }
        }
    }
}
