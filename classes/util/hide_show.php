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
 * Show/hide HTML div [+]/[-]
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl\util;

defined('MOODLE_INTERNAL') || die;
require_once(dirname(__FILE__).'/../../locallib.php');

/**
 * Class to hide & show div/span contents.
 */
class hide_show {
    private static $globalid = 0;
    protected $id;
    protected $show;

    /**
     * Create a new object whit defined state.
     * @param bool $show Set the initial state shown or hidden contents (default hidden)
     */
    public function __construct($show = false) {
        if (self::$globalid == 0) {
            echo vpl_include_jsfile( 'hideshow.js' );
        }
        $this->id = self::$globalid;
        $this->show = $show;
        self::$globalid ++;
    }
    /**
     * Generate the hide/show button.
     * @return string HTML
     */
    public function generate(): string {
        $html = ' <a id="sht' . $this->id . '" class=""';
        $html .= ' onclick="VPL.showHideDiv(' . $this->id . ');">';
        $id = "id='vpl_shb{$this->id}h'";
        $title = get_string('hide');
        $icon = "class='icon fa fa-eye-slash'";
        $sign = '[-]';
        $style = "style='cursor:pointer";
        $style .= $this->show ? "'" : ";display:none'";
        $html .= "<span $id $style><i $icon aria-hidden='true' title='$title'></i>$sign</span>";
        $id = "id='vpl_shb{$this->id}s'";
        $title = get_string('show');
        $icon = "class='icon fa fa-eye'";
        $sign = '[+]';
        $style = "style='cursor:pointer";
        $style .= $this->show ? ";display:none'" : "'";
        $html .= "<span $id $style><i $id $icon aria-hidden='true' title='$title'></i>$sign</span>";
        $html .= '</a> ';
        return $html;
    }

    /**
     * Generate begin of div for contents.
     * @return string HTML or empty
     */
    public function begin_div(): string {
        $html = '<div id="vpl_shc' . $this->id . '" class="vpl_show_hide_content"';
        if (! ($this->show)) {
            $html .= ' style="display:none"';
        }
        $html .= '>';
        return $html;
    }

    /**
     * Generate end of div for contents.
     * @return string HTML
     */
    public function end_div(): string {
        return '</div>';
    }

    /**
     * Generate div with contents.
     * @param string $content Contents to use, must be escaped.
     * @return string HTML
     */
    public function content_in_div($content): string {
        return $this->begin_div() . $content . $this->end_div();
    }

    /**
     * Generate begin of span for contents.
     * @return string HTML
     */
    public function begin_span(): string {
        $html = '<span id="vpl_shc' . $this->id . '" class="vpl_show_hide_content"';
        if (! ($this->show)) {
            $html .= ' style="display:none"';
        }
        $html .= '>';
        return $html;
    }

    /**
     * Generate end of span for contents.
     * @return string HTML
     */
    public function end_span() {
        return '</span>';
    }

    /**
     * Generate span with contents.
     * @param string $content Contents to use, must be escaped.
     * @return string HTML
     */
    public function content_in_span($content) {
        return $this->begin_span() . $content . $this->end_span();
    }

    public function get_tag_id() {
        return 'shd' . $this->id;
    }

}
