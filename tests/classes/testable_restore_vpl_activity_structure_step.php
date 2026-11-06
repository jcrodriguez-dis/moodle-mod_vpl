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
 * Restore file handler access for tests.
 *
 * @package mod_vpl
 * @copyright 2026 Juan Carlos Rodriguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_vpl\tests;

/**
 * Expose the restore handlers without changing production visibility.
 */
class testable_restore_vpl_activity_structure_step extends \restore_vpl_activity_structure_step {
    /**
     * Restore a required file.
     *
     * @param array $data Backup file record.
     */
    public function restore_required_file(array $data): void {
        $this->process_required_file($data);
    }

    /**
     * Restore an execution file.
     *
     * @param array $data Backup file record.
     */
    public function restore_execution_file(array $data): void {
        $this->process_execution_file($data);
    }
}
