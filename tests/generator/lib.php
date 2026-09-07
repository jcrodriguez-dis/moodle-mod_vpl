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
 * VPL module data generator class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

 /**
  * VPL module data generator class for testing.
  *
  * @codeCoverageIgnore
  */
class mod_vpl_generator extends testing_module_generator {
    /**
     * Array with default field setting for a VPL activity
     */
    protected const DEFAULTSETTING = [
        'shortdescription' => '',
        'intro' => '',
        'introformat' => 0,
        'startdate' => 0,
        'duedate' => 0,
        'maxfiles' => 1,
        'maxfilesize' => 0,
        'requirednet' => '',
        'password' => '',
        'grade' => 0,
        'visiblegrade' => 0,
        'usevariations' => 0,
        'variationtitle' => '',
        'basedon' => 0,
        'run' => 0,
        'debug' => 0,
        'evaluate' => 0,
        'evaluateonsubmission' => 0,
        'automaticgrading' => 0,
        'maxexetime' => 0,
        'restrictededitor' => 0,
        'activity_mode' => 0,
        'maxexememory' => 0,
        'maxexefilesize' => 0,
        'maxexeprocesses' => 0,
        'jailservers' => '',
        'worktype' => 0,
        'emailteachers' => 0,
        'timemodified' => 0,
        'freeevaluations' => 0,
        'reductionbyevaluation' => '',
        'sebrequired' => 0,
        'sebkeys' => '',
        'runscript' => '',
        'debugscript' => '',
        'evaluator' => '',
        'run_mode' => 0,
        'evaluation_mode' => 0,
        // SEB default values.
        'showsebdownloadlink' => 1,
        'showsebtaskbar' => 1,
        'showwificontrol' => 0,
        'showreloadbutton' => 1,
        'showtime' => 1,
        'showkeyboardlayout' => 1,
        'allowuserquitseb' => 1,
        'quitpassword' => '',
        'linkquitseb' => '',
        'userconfirmquit' => 1,
        'allowreloadinexam' => 1,
        'enableaudiocontrol' => 0,
        'muteonstartup' => 0,
        'allowcapturecamera' => 0,
        'allowcapturemicrophone' => 0,
        'allowspellchecking' => 0,
        'activateurlfiltering' => 0,
        'filterembeddedcontent' => 0,
        'expressionsallowed' => '',
        'regexallowed' => '',
        'expressionsblocked' => '',
        'regexblocked' => '',
    ];

    /**
     * Create a new instance of the VPL module.
     *
     * @param object|null $record The record to create the instance with.
     * @param ?array $options Additional options for creating the instance.
     * @return stdClass The created instance.
     */
    public function create_instance($record = null, ?array $options = null) {
        // Normalize parameter $record to object.
        $record = (object)(array)$record;

        // Set default value.
        foreach (self::DEFAULTSETTING as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }
        $instance = parent::create_instance($record, (array)$options);
        \mod_vpl::reset_db_cache();
        return $instance;
    }
}
