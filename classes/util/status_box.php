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
 * Class to show a process status in a box
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
 * Class to show a status box
 *
 * This class is used to show a status box with a text.
 * It can be used to show the status of a process.
 */
class status_box {
    /**
     * @var int last id used for the box
     * This is used to generate unique ids for each box.
     */
    protected static $lastid = 0;
    /**
     * @var string id of the box
     */
    protected $id;
    /**
     * @var int time when the box was created
     */
    protected $starttime;

    /**
     * Constructor
     * @param string $text text to show in the box
     */
    public function __construct($text = '') {
        global $OUTPUT;
        $this->id = 'vpl_sb_' . (self::$lastid + 1);
        $this->starttime = time();
        self::$lastid++;
        echo $OUTPUT->box($text, '', $this->id);
    }

    /**
     * Print text
     *
     * @param string $text text to show in the box
     */
    public function print_text($text) {
        $javascript = 'window.document.getElementById(\'';
        $javascript .= $this->id;
        $javascript .= '\').innerHTML =\'';
        $javascript .= addslashes($text);
        $javascript .= '\';';
        echo vpl_include_js($javascript);
        @ob_flush();
        flush();
    }

    /**
     * Hide box
     */
    public function hide() {
        $javascript = 'window.document.getElementById(\'';
        $javascript .= $this->id;
        $javascript .= '\').style.display=\'none\';';
        echo vpl_include_js($javascript);
        @ob_flush();
        flush();
    }
}
