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

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../locallib.php');

/**
 * Class to show a progress bar in a box
 */
class progress_bar extends status_box {
    /**
     * @var int minimum value
     */
    protected $min;
    /**
     * @var int maximum value
     */
    protected $max;
    /**
     * @var int last time the progress bar was updated
     */
    protected $lasttime;
    /**
     * @var string text to show in the progress bar
     */
    protected $text;

    /**
     * Constructor
     *
     * @param string $text text to show in the progress bar
     * @param int $min minimum value (default 0)
     * @param int $max maximum value (default 100)
     */
    public function __construct($text = '', $min = 0, $max = 100) {
        parent::__construct($text);
        $this->text = $text;
        $this->min = $min;
        $this->max = $max;
        $this->lasttime = 0;
    }

    /**
     * Set the value of the progress bar
     *
     * @param int $value current value
     */
    public function set_value($value) {
        if (is_string($value)) {
            $this->print_text($this->text . ' (' . $value . ')');
            return;
        }
        $currenttime = time();
        $percent = ((($value - $this->min) * 100) / ($this->max - $this->min));
        if ($this->lasttime != $currenttime || $percent >= 100) {
            if ($percent > 100) {
                $percent = 100;
            }
            $this->lasttime = $currenttime;
            if ($percent == 100) {
                $text = $this->text . ' (' . sprintf("%5.1f", $percent) . '%)';
                $text .= ' ' . get_string('numseconds', '', $currenttime - $this->starttime);
                $text .= sprintf(" %5.1fMB", memory_get_usage() / 1024000);
                $this->print_text($text);
            } else {
                $this->print_text($this->text . ' (' . sprintf("%5.1f", $percent) . '%)');
            }
        }
    }

    /**
     * Set the maximum value
     *
     * @param int $max maximum value
     */
    public function set_max($max) {
        $this->max = $max;
    }
}
