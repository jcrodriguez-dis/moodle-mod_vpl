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
 * Classes to manage file from difrerent soruces
 *
 * @package mod_vpl
 * @copyright 2015 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\similarity;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/vpl_submission.class.php');

/**
 * Information of a file from other vpl activity
 */
class file_from_activity extends file_from_base {
    /**
     * @var array $vpls Array to store VPL activities.
     * This is static to avoid loading the same activity multiple times.
     */
    protected static $vpls = [];

    /**
     * @var int $vplid ID of the VPL activity.
     */
    protected $vplid;

    /**
     * @var string $filename Name of the file.
     */
    protected $filename;

    /**
     * @var string $subid Submission ID of the file.
     */
    protected $subid;

    /**
     * @var string $userid User ID of the file.
     */
    protected $userid;

    /**
     * Constructor for the file_from_activity class.
     *
     * @param string $filename The name of the file.
     * @param mod_vpl $vpl The VPL activity object.
     * @param object $subinstance The subinstance object containing user ID and submission ID.
     */
    public function __construct(&$filename, &$vpl, $subinstance) {
        $id = $vpl->get_instance()->id;
        if (! isset(self::$vpls[$id])) {
            self::$vpls[$id] = $vpl;
        }
        $this->vplid = $id;
        $this->filename = $filename;
        $this->userid = $subinstance->userid;
        $this->subid = $subinstance->id;
    }

    /**
     * Shows the file information.
     *
     * @return string HTML string with the file information
     */
    public function show_info() {
        global $DB;
        $vpl = self::$vpls[$this->vplid];
        $cmid = $vpl->get_course_module()->id;
        $ret = '';
        if ($this->userid >= 0) {
            $user = $DB->get_record('user', [
                    'id' => $this->userid,
            ]);
        } else {
            $user = false;
        }
        if ($user) {
            $ret .= '<a href="' . vpl_mod_href('/forms/submissionview.php', 'id', $cmid, 'userid', $user->id) . '">';
        }
        $ret .= s($this->filename);
        if ($user) {
            $ret .= '</a> ';
            $sub = new \mod_vpl_submission($vpl, $this->subid);
            $ret .= $sub->get_grade_core() . '<br>';
            $ret .= $vpl->user_fullname_picture($user);
            $link = vpl_mod_href('/similarity/user_similarity.php', 'id', $vpl->get_course()->id, 'userid', $user->id);
            $ret .= ' (<a href="' . $link . '">';
            $ret .= '*</a>)';
        }
        return $ret;
    }

    /**
     * Returns the user ID of the file.
     * @return string The user ID associated with the file.
     */
    public function get_userid() {
        return $this->userid;
    }

    /**
     * Returns if the file can be accessed.
     * @return bool True if the file can be accessed, false otherwise
     */
    public function can_access() {
        return $this->filename != preprocess::JOINEDFILENAME;
    }

    /**
     * Returns the parameters to link to this file.
     *
     * @param int $t Type of link (1 for directory, 2 for activity, 3 for zip)
     * @return array Associative array with parameters for the link
     */
    public function link_parms($t) {
        return [
                'type' . $t => 1,
                'subid' . $t => $this->subid,
                'filename' . $t => $this->filename,
        ];
    }
}
