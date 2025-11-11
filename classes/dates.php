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
 * Class for VPL dates integration in overview feature.
 *
 * @package mod_vpl
 * @copyright 2025 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */

namespace mod_vpl;

use core\activity_dates;

/**
 * Class getting VPL activities dates for a given course module and a user.
 *
 * @copyright 2025 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */
class dates extends activity_dates {
    /** @var int $duedate the cached activity due date */
    private int $duedate;

    /**
     * Returns a list with the startdate and duedate in mod_vpl if availables.
     *
     * @return array
     */
    protected function get_dates(): array {
        require_once(dirname(__FILE__) . '/../locallib.php');

        $this->duedate = 0;
        $vpl = new \mod_vpl($this->cm->id);

        $startdate = $vpl->get_effective_setting('startdate');
        $duedate = $vpl->get_effective_setting('duedate');
        $dates = [];
        if ($startdate) {
            $date = [
                'dataid' => 'startdate',
                'label' => get_string('startdate', 'mod_vpl') . ':',
                'timestamp' => (int) $startdate,
            ];
            $dates[] = $date;
        }
        if ($duedate) {
            $this->duedate = (int) $duedate;
            $date = [
                'dataid' => 'duedate',
                'label' => get_string('duedate', 'mod_vpl') . ':',
                'timestamp' => $duedate,
            ];
            $dates[] = $date;
        }
        return $dates;
    }

    /**
     * Returns the duedate, if any.
     * @return int the due date timestamp or null if not set.
     */
    public function get_due_date(): ?int {
        if (!isset($this->duedate)) {
            $this->get_dates();
        }
        return empty($this->duedate) ? null : $this->duedate;
    }
}
