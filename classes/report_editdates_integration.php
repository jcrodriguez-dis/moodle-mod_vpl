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
 * Class to lock based on directory path
 *
 * @package mod_vpl
 * @copyright 2022 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/../locallib.php');

class mod_vpl_report_editdates_integration extends \report_editdates_mod_date_extractor {
    public function __construct($course) {
        parent::__construct($course, VPL);
        parent::load_data();
    }

    public function get_settings(\cm_info $cm) {
        $vplinstance = $this->mods[$cm->instance];

        return [
                'startdate' => new \report_editdates_date_setting(
                        get_string('startdate', VPL),
                        $vplinstance->startdate,
                        self::DATETIME, true),
                'duedate' => new \report_editdates_date_setting(
                        get_string('duedate', VPL),
                        $vplinstance->duedate,
                        self::DATETIME, true)
        ];
    }

    public function validate_dates(\cm_info $cm, array $dates) {
        if ($dates['startdate'] && $dates['duedate']
                && $dates['duedate'] < $dates['startdate']) {
            return get_string('duedatevalidation', 'assign');
        }
    }

    public function save_dates(\cm_info $cm, array $dates) {
        global $DB;

        $vpl = new \mod_vpl($cm->id);
        $instance = $vpl->get_instance();
        $instance->startdate = $dates['startdate'];
        $instance->duedate = $dates['duedate'];
        $instance->timemodified = time();
        $DB->update_record(VPL, $instance);
        vpl_update_instance_event($instance);
    }
}
