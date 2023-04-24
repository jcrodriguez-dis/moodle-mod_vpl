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
 * VPL Syntaxhighlighters adapter for Ace editor
 *
 * @package mod_vpl
 * @copyright 2017 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/sh_base.class.php');
require_once(dirname(__FILE__).'/sh_factory.class.php');

class vpl_sh_ace extends vpl_sh_base {
    protected static $fid = 0;
    protected static $executionfiles = array('vpl_run.sh', 'vpl_debug.sh',
                                              'vpl_evaluate.sh', 'vpl_evaluate.cases');
    protected static function getid() {
        self::$fid ++;
        return 'fileid' . self::$fid;
    }
    public function print_file($filename, $filedata, $showln = true, $nl = 3000, $title = true) {
        global $PAGE;
        if ( array_search($filename, self::$executionfiles) !== false &&
             $filedata == '') {
            return;
        }
        $tid = self::getid();
        $plugincfg = get_config('mod_vpl');
        if ( isset($plugincfg->editor_theme) ) {
            $theme = $plugincfg->editor_theme;
        } else {
            $theme = 'chrome';
        }
        if ( $title ) {
            echo "<h4 id='$tid'>" . s( $filename ) . '</h4>';
        }
        if ( $filedata > '' ) {

            $code = '<pre ';
            $code .= " id='code$tid' style='display:none' >";
            $code .= htmlentities( $filedata, ENT_NOQUOTES );
            $code .= '</pre>';
            echo $code;
            $code = '<h4 ';
            $code .= " id='code{$tid}load' style='text-align:center'>";
            $code .= vpl_get_awesome_icon('loading') . get_string('loading', VPL);
            $code .= '</h4>';
            echo $code;
            $PAGE->requires->js_call_amd('mod_vpl/vplutil', 'syntaxHighlightFile', array($tid, $filename, $theme, $showln, $nl));

        }
    }
}
