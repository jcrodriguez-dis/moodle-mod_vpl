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
 * Class to hide & show any tag contents (div, span, pre, etc.).
 */
class hide_show {

    /**
     * @var int Global counter of ids to get a unique id for each tag.
     */
    private static $globalid = 0;

    /**
     * @var int Current instance id.
     */
    protected $id;

    /**
     * @var bool Indicate if the first state of the tag (show or hide).
     */
    protected $show;

    /**
     * @var string Tag used, needed for closing the tag.
     */
    protected $tag;

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
     * Generate begin of tag for contents.
     * @return string HTML or empty
     */
    public function begin($tag): string {
        $this->tag = $tag;
        $html = "<$tag id='vpl_shc{$this->id}' class='vpl_show_hide_content'";
        if (! ($this->show)) {
            $html .= " style='display:none'";
        }
        $html .= '>';
        return $html;
    }

    /**
     * Generate end of tag for contents.
     * @return string HTML
     */
    public function end(): string {
        return "</{$this->tag}>";
    }

    /**
     * Generate tag with contents.
     * @param string $content Contents to use, must be escaped.
     * @return string HTML
     */
    public function content_in_tag($tag, $content): string {
        return $this->begin($tag) . $content . $this->end();
    }

    /**
     * Return tag ID.
     * @return int
     */
    public function get_tag_id() {
        return 'vpl_shc' . $this->id;
    }
}
