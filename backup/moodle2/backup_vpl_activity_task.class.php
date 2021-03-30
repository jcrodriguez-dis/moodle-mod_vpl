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
 * This file contains the backup activity for the VPL module
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined ( 'MOODLE_INTERNAL' ) || die ();
require_once(dirname ( __FILE__ ) . '/backup_vpl_stepslib.php');

/**
 * VPL backup task class that provides all the settings and steps to perform one
 * complete backup of the activity
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
class backup_vpl_activity_task extends backup_activity_task {

    /**
     * No particular settings for this activity.
     */
    protected function define_my_settings() {
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step ( new backup_vpl_activity_structure_step ( 'vpl_structure', 'vpl.xml' ) );
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     * @param string $content to encode
     * @return string encode result
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote ( $CFG->wwwroot, "/" );

        // Link to the list of VPL instances.
        $search = "/(" . $base . "\/mod\/vpl\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace ( $search, '$@VPLINDEX*$2@$', $content );

        // Link to VPL view by moduleid.
        $search = "/(" . $base . "\/mod\/vpl\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace ( $search, '$@VPLVIEWBYID*$2@$', $content );

        return $content;
    }
}
