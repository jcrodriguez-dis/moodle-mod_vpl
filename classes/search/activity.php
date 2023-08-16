<?php
// This file is part of Moodle - http://moodle.org/
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
 * Class for support searching the VPL activity information (name and description).
 *
 * @package mod_vpl
 * @copyright 2023 onward Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl\search;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '../../../vpl.class.php');

/**
 * Class for support searching the VPL activity information (name and description).
 */
class activity extends \core_search\base_activity {

    /**
     * Returns false.
     *
     * At this moment VPL doesn't use Moodle files.
     *
     * @return bool
     */
    public function uses_file_indexing() {
        return false;
    }

    /**
     * Returns if the current user has access to $id VPL activity.
     *
     * VPL limits access to description also by password and network.
     *
     * @return bool
     */
    public function check_access($id) {
        try {
            $vpl = new \mod_vpl(null, $id);
        } catch (\dml_missing_record_exception $ex) {
            // If the record does not exist anymore.
            return \core_search\manager::ACCESS_DELETED;
        } catch (\dml_exception $ex) {
            // Skip results if there is any unexpected error.
            return \core_search\manager::ACCESS_DENIED;
        }

        if ($vpl->pass_network_check() &&
                $vpl->pass_password_check() &&
                $vpl->pass_seb_check() &&
                $vpl->is_visible()) {
            return \core_search\manager::ACCESS_GRANTED;
        }
        return \core_search\manager::ACCESS_DENIED;
    }
}
