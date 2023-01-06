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
  * @codeCoverageIgnore
  */
class mod_vpl_generator extends testing_module_generator {
    public function create_instance($record = null, array $options = null) {
        // Normalize parameter $record to object.
        $record = (object)(array)$record;

        $defaultsettings = array(
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
            'example' => 0,
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
        );

        // Set default value.
        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }
}
