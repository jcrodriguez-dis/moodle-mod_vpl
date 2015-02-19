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
 * Example Compilation Execution class definition
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
defined('MOODLE_INTERNAL') || die();
require_once dirname(__FILE__).'/vpl_submission_CE.class.php';
class mod_vpl_example_CE extends mod_vpl_submission_CE{
    /**
     * Constructor
     * @param $vpl. vpl object instance
     **/
    function __construct($vpl) {
        global $USER;
        $fake = new stdClass();
        $fake->userid = $USER->id;
        $fake->vpl = $vpl->get_instance()->id;
        parent::__construct($vpl, $fake);
    }

    /**
     * @return object file group manager for example files
     **/
    function get_submitted_fgm(){
        if(!$this->submitted_fgm){
            $this->submitted_fgm = $this->vpl->get_required_fgm();
        }
        return $this->submitted_fgm;
    }

    /**
     * Save Compilation Execution result. Removed
     * @param $result array response from server
     * @return uvoid
     */
    function saveCE($result){
        //Paranoic removed
    }
}
