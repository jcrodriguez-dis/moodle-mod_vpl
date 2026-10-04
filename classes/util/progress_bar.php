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
 * Class to show a process bar status in a box
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl\util;

/**
 * Class to show a progress bar using the Moodle core progress bar.
 */
class progress_bar extends \core\output\progress_bar {
    /**
     * @var bool this class flushes the output buffers itself
     */
    protected static $supportsoutputbuffering = true;
    /**
     * @var int minimum value
     */
    protected $min;
    /**
     * @var int maximum value
     */
    protected $max;
    /**
     * @var string text to show in the progress bar
     */
    protected $text;
    /**
     * @var int time when the progress bar was created
     */
    protected $starttime;

    /**
     * Constructor
     *
     * @param string $text text to show in the progress bar
     * @param int $min minimum value (default 0)
     * @param int $max maximum value (default 100)
     */
    public function __construct($text = '', $min = 0, $max = 100) {
        parent::__construct('vpl_pb_' . uniqid(), 500, true);
        $this->text = $text;
        $this->min = $min;
        $this->max = $max;
        $this->starttime = time();
        $this->update_full(0, $text);
        $this->scroll_into_view();
    }

    /**
     * Scroll the page to make the progress bar visible
     */
    protected function scroll_into_view() {
        echo \html_writer::script(
            'var e = document.getElementById(' . json_encode($this->get_id()) . ');' .
            'if (e) { e.scrollIntoView({block: "nearest"}); }'
        );
        @ob_flush();
        flush();
    }

    /**
     * Set the value of the progress bar
     *
     * @param int|string $value current value, a string is shown as text
     */
    public function set_value($value) {
        if (is_string($value)) {
            $this->print_text($this->text . ' (' . $value . ')');
            return;
        }
        $range = $this->max - $this->min;
        $percent = $range > 0 ? (($value - $this->min) * 100) / $range : 100;
        $percent = max(0, min(100, $percent));
        $text = $this->text;
        if ($percent >= 100) {
            $text .= ' ' . get_string('numseconds', '', time() - $this->starttime);
            $text .= sprintf(" %5.1fMB", memory_get_usage() / 1024000);
        }
        $this->update_full($percent, $text);
        @ob_flush();
        flush();
    }

    /**
     * Update the bar and flush the output buffers so it is shown at once.
     *
     * @param float $percent progress percentage
     * @param string $msg status message
     */
    protected function update_raw($percent, $msg) {
        parent::update_raw($percent, $msg);
        @ob_flush();
        flush();
    }

    /**
     * Set the maximum value
     *
     * @param int $max maximum value
     */
    public function set_max($max) {
        $this->max = $max;
    }

    /**
     * Print text in the status message of the progress bar
     *
     * @param string $text text to show
     */
    public function print_text($text) {
        $this->update_full($this->get_percent(), $text);
        @ob_flush();
        flush();
    }

    /**
     * Hide the progress bar
     */
    public function hide() {
        echo \html_writer::script(
            'var e = document.getElementById(' . json_encode($this->get_id()) . ');' .
            'if (e) { e.style.display = "none"; }'
        );
        @ob_flush();
        flush();
    }
}
