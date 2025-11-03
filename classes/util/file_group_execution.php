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
 * Class file_group_execution
 *
 * @package mod_vpl
 * @copyright 2013 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */
namespace mod_vpl\util;

/**
 * Class file_group_execution
 *
 * Manage the execution file groups for VPL activities.
 * Includes fixed files required for execution.
 */
class file_group_execution extends file_group {
    /**
     * Name of fixed file names
     *
     * @var string[]
     */
    protected static $basefiles = [
            'vpl_run.sh',
            'vpl_debug.sh',
            'vpl_evaluate.sh',
            'vpl_evaluate.cases',
    ];

    /**
     * Number of $basefiles elements
     *
     * @var int
     */
    protected static $numbasefiles;

    /**
     * Constructor
     *
     * @param string $dir
     */
    public function __construct($dir) {
        self::$numbasefiles = count(self::$basefiles);
        parent::__construct($dir, 1000, self::$numbasefiles);
    }

    /**
     * Get list of files
     *
     * @return string[]
     */
    public function getfilelist() {
        return array_values(array_unique(array_merge(self::$basefiles, parent::getfilelist())));
    }

    /**
     * Get the file comment by number
     *
     * @param int $num
     * @return string
     */
    public function getfilecomment($num) {
        if ($num < self::$numbasefiles) {
            return get_string(self::$basefiles[$num], VPL);
        } else {
            return get_string('file') . ' ' . ($num + 1 - self::$numbasefiles);
        }
    }

    /**
     * Get list of files to keep when running
     *
     * @return string[]
     */
    public function getfilekeeplist() {
        return self::read_list($this->filelistname . '.keep');
    }

    /**
     * Set the file list to keep when running
     *
     * @param string[] $filelist
     */
    public function setfilekeeplist($filelist) {
        self::write_list($this->filelistname . '.keep', $filelist);
    }
}
