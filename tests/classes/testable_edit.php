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

namespace mod_vpl\tests;

/**
 * Editor operations with injectable transport and observable job dispatch.
 *
 * @package mod_vpl
 * @copyright 2026 Juan Carlos Rodriguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_edit extends \mod_vpl_edit {
    /** @var array Calls delegated to the job backend. */
    public static $calls = [];

    /**
     * Supply the existing injectable transport while retaining real jail selection.
     *
     * @return class-string<testable_jailserver_manager> Jail manager class.
     */
    protected static function get_jailserver_manager(): string {
        return testable_jailserver_manager::class;
    }

    /**
     * Record retrieval without contacting an execution server.
     *
     * @param \mod_vpl $vpl VPL activity.
     * @param int $userid Process owner.
     * @param int $processid Process ID.
     * @return \stdClass Editor response.
     */
    public static function retrieve_result(\mod_vpl $vpl, int $userid, $processid = -1) {
        self::$calls[] = ['retrieve', $vpl->get_instance()->id, $userid, $processid];
        return (object) ['execution' => 'completed', 'grade' => '7'];
    }

    /**
     * Record cancellation without contacting an execution server.
     *
     * @param \mod_vpl $vpl VPL activity.
     * @param int $userid Process owner.
     * @param int $processid Process ID.
     * @return string Empty error on success.
     */
    public static function cancel($vpl, $userid, int $processid) {
        self::$calls[] = ['cancel', $vpl->get_instance()->id, $userid, $processid];
        return '';
    }
}
