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
 * A schedule task for VPL cron.
 *
 * @package mod_vpl
 * @copyright 2020 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\task;

/**
 * Class cron_task to be used by the task system.
 * The task switch from hidden to show the activities that reach the startdate.
 *
 */
class cron_task extends \core\task\scheduled_task {
    /**
     * @var bool $verbose The state of verbosity of the task.
     */
    private $verbose = true;

    /**
     * @var integer const. Range of time around the start date
     */
    const STARTDATE_RANGE = 300;

    /**
     * Get the range of time around the start date.
     * @return int The range of time in seconds.
     */
    public function get_time_range(): int {
        $lastrun = $this->get_last_run_time();
        if ($lastrun !== false) {
            $elapsed = intval((time() - $lastrun) * 1.2);
            return min(self::STARTDATE_RANGE, $elapsed);
        } else {
            return self::STARTDATE_RANGE;
        }
    }

    /**
     * Set the verbose state.
     * @param bool $state Setting the value to true shows the name of the activity
     * and false no shows.
     */
    public function set_verbose(bool $state): void {
        $this->verbose = $state;
    }

    /**
     * Get a descriptive name for this task shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'mod_vpl');
    }

    /**
     * Makes visible VPL activities that are starting now.
     */
    public function make_visible() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
        require_once($CFG->dirroot . '/mod/vpl/locallib.php');
        $rebuilds = [];
        $now = time();
        $sql = 'SELECT id, startdate, duedate, course, name FROM {vpl}
                    WHERE startdate < ?
                          and startdate >= ?
                          and (duedate > startdate or duedate = 0)
                          and activity_mode = ?';
        $notbefore = $now - $this->get_time_range() / 2;
        $notafter = $now + $this->get_time_range() / 2;
        $parms = [$notafter, $notbefore, 0]; // NORMAL => activity_mode = 0.
        $vpls = $DB->get_records_sql($sql, $parms);
        $checked = 0;
        $donevisible = 0;
        foreach ($vpls as $instance) {
            $checked++;
            if (! instance_is_visible(VPL, $instance)) {
                $vpl = new \mod_vpl(null, $instance->id);
                if ($this->verbose) {
                    echo 'Setting visible "' . s($vpl->get_printable_name()) . '"' . "\n";
                }
                $cm = $vpl->get_course_module();
                set_coursemodule_visible($cm->id, true);
                $rebuilds[$cm->course] = $cm->course;
                $donevisible++;
            }
        }
        echo "Checked $checked VPL instances.\n";
        echo "Made visible $donevisible VPL instances.\n";
        foreach ($rebuilds as $courseid) {
            rebuild_course_cache($courseid);
        }
    }

    /**
     * Removes processes started 1 day ago
     */
    public function remove_old_processes() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vpl/jail/running_processes.class.php');
        \vpl_running_processes::remove_old_processes(60 * 60 * 24);
        return true;
    }

    /**
     * Check and remove stopped processes.
     * Check only non short-lived processes.
     * @param int $maxtime Maximum time in seconds to be checking.
     */
    public function remove_stopped_processes($maxtime) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vpl/jail/running_processes.class.php');
        $shortlive = 60 * 5;
        $startedbefore = time() - $shortlive;
        $timeout = time() + $maxtime;
        $processes = \vpl_running_processes::lanched_processes();
        shuffle($processes);
        $checked = 0;
        foreach ($processes as $process) {
            if ($process->start_time < $startedbefore) {
                \vpl_running_processes::is_running($process);
                $checked++;
            }
            if (time() > $timeout) {
                break;
            }
        }
        echo "Checked $checked task processes.\n";
    }

    /**
     * Run VPL cron.
     */
    public function execute() {
        $this->make_visible();
        $this->remove_old_processes();
        $this->remove_stopped_processes($this->get_time_range() / 2);
        return true;
    }
}
