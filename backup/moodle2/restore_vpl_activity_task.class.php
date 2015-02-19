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
 * Provides support for restore VPL antivities in the moodle2 backup format
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
defined ( 'MOODLE_INTERNAL' ) || die ();
require_once(dirname( __FILE__ ) . '/restore_vpl_stepslib.php');
class restore_vpl_activity_task extends restore_activity_task {
    private $structurestep;
    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->structurestep = new restore_vpl_activity_structure_step ( 'vpl_structure', 'vpl.xml' );
        $this->add_step ( $this->structurestep );
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array ();

        $contents [] = new restore_decode_content ( 'vpl', array (
                'intro'
        ), 'vpl' );

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array ();

        $rules [] = new restore_decode_rule ( 'VPLVIEWBYID', '/mod/vpl/view.php?id=$1', 'course_module' );
        $rules [] = new restore_decode_rule ( 'VPLINDEX', '/mod/vpl/index.php?id=$1', 'course' );

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * choice logs.
     * It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array ();
        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs.
     * It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array ();
        return $rules;
    }
    public function after_restore() {
        global $DB;
        $id = $this->get_activityid ();
        $data = $DB->get_record ( 'vpl', array (
                'id' => $id
        ) );
        if ($data != false) {
            $data->basedon = $this->structurestep->get_mappingid ( 'vpl', $data->basedon );
            $DB->update_record ( 'vpl', $data );
        }
    }
}