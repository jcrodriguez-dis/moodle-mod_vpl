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
 * Base class for logging submission related events
 *
 * @package mod_vpl
 * @copyright 2014 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\event;

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../locallib.php');

/**
 * Base event class for VPL submission events.
 * This class is used to log events related to VPL submissions, such as creation, editing, and viewing.
 */
class submission_base extends base {
    /**
     * Returns the mapping for object IDs.
     * This method is used to map the object IDs in the database to the event.
     *
     * @return array An associative array with 'db' and 'restore' keys for object ID mapping.
     */
    public static function get_objectid_mapping() {
        return ['db' => 'vpl_submissions', 'restore' => 'vpl_submissions'];
    }

    /**
     * Returns the mapping for other related data.
     * This method is used to map any other related data that is not directly associated with the submission.
     *
     * @return bool|false Returns false if there is no other mapping to be done.
     */
    public static function get_other_mapping() {
        // Nothing to map.
        return false;
    }

    /**
     * Initializes the event.
     * This method is called when the event is created.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = VPL_SUBMISSIONS;
    }

    /**
     * Returns the URL for the event.
     * This method is used to provide a URL that can be used to view the submission.
     *
     * @return string The URL for viewing the submission.
     */
    public function get_url() {
        return $this->get_url_base('forms/submissionview.php');
    }

    /**
     * Returns the description of the event.
     * This method is used to provide a human-readable description of the event.
     *
     * @param string $mod The type of modification (e.g., 'updated', 'deleted').
     * @return string Description of the event.
     */
    protected function get_description_mod($mod) {
        $desc = 'The user with id ' . $this->userid . ' ' . $this->action;
        $desc .= ' ' . $mod . ' VPL submission with id ' . $this->objectid;
        if (isset($this->relateduserid) && $this->relateduserid > 0 && $this->relateduserid != $this->userid) {
            $desc .= ' of user with id ' . $this->relateduserid;
        }
        return $desc;
    }

    /**
     * Logs the event for a submission.
     * This method is used to log the event when a submission is created, edited, or viewed.
     *
     * @param mixed $submission The submission object or an array of submission data.
     */
    public static function log($submission) {
        if (is_array($submission)) {
            parent::log($submission);
        } else {
            global $USER;
            $subinstance = $submission->get_instance();
            $vpl = $submission->get_vpl();
            $einfo = [
                    'objectid' => $subinstance->id,
                    'context' => $vpl->get_context(),
                    'relateduserid' => ($USER->id != $subinstance->userid ? $subinstance->userid : null),
            ];
            parent::log($einfo);
        }
    }

    /**
     * Returns the description of the event.
     * This method is used to provide a human-readable description of the event.
     *
     * @return string Description of the event.
     */
    public function get_description() {
        return $this->get_description_mod('');
    }
}
