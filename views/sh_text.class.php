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
 * VPL Syntaxhighlighter text base class
 *
 * @package mod_vpl
 * @copyright 2012 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/sh_base.class.php');

class vpl_sh_text extends vpl_sh_base {
    const CR = "\r";
    const LF = "\n";
    const TAB = "\t";
    protected $reserved;
    protected $showln;
    protected $linenumber;
    protected $filename;
    protected $actionline;
    protected $hoverlevel;
    const C_FUNCTION = 'vpl_f';
    const C_VARIABLE = 'vpl_v';
    const C_STRING = 'vpl_s';
    const C_COMMENT = 'vpl_c';
    const C_MACRO = 'vpl_m';
    const C_RESERVED = 'vpl_r';
    const C_GENERAL = 'vpl_g';
    const C_HOVER = 'vpl_h';
    const C_LINENUMBER = 'vpl_ln';
    const ENDTAG = '</span>';
    protected function show_line_number() {
        if ($this->showln) {
            echo '<span class="' . self::c_linenumber . '">';
            $name = $this->filename . '.' . $this->linenumber;
            echo '<a name="' . $name . '"></a>';
            $text = sprintf( '%5d', $this->linenumber );
            if ($this->actionline) {
                echo '<a href="javascript:actionLine(\'' . $name . '\')")>' . $text . '</a>';
            } else {
                echo $text;
            }
            echo ' </span>';
        }
        $this->linenumber ++;
    }
    protected function show_text($text) {
        p( $text );
    }
    protected function show_pending(&$rest) {
        $this->show_text( $rest );
        $rest = '';
    }
    protected function inittag($class) {
        echo '<span class="' . $class . '">';
    }
    protected function endtag() {
        echo '</span>';
    }
    protected function begin($filename, $showln = true) {
        $this->hoverlevel = 0;
        $this->showln = $showln;
        $this->filename = $filename;
        $this->linenumber = 1;
        echo '<pre class="vpl_sh ' . self::C_GENERAL . '">';
    }
    protected function end() {
        while ( $this->hoverlevel > 0 ) {
            $this->endhover();
        }
        echo '</pre>';
    }
    protected function inithover() {
        echo '<span class="' . self::c_hover . ($this->hoverlevel < 12 ? $this->hoverlevel : 11) . '">';
        $this->hoverlevel ++;
    }
    protected function endhover() {
        $this->hoverlevel --;
        echo '</span>';
    }
    public function __construct() {
    }
    public function print_file($filename, $filedata, $showln = true) {
        $this->begin( $filename, $showln );
        $pending = '';
        $l = strlen( $filedata );
        if ($l) {
            $this->show_line_number();
        }
        for ($i = 0; $i < $l; $i ++) {
            $current = $filedata [$i];
            if ($i < ($l - 1)) {
                $next = $filedata [$i + 1];
            } else {
                $next = '';
            }
            if ($current == self::CR) {
                if ($next == self::LF) {
                    continue;
                } else {
                    $current = self::LF;
                }
            }
            $pending .= $current;
            if ($current == self::LF) {
                $this->show_pending( $pending );
                $this->show_line_number();
            }
        }
        $this->show_pending( $pending );
        $this->end();
    }
}
