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

namespace mod_vpl\util;

/**
 * Class to lock based on directory path
 *
 * @package mod_vpl
 * @copyright 2017 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
class lock {
    /**
     * @var string The path to the lock file.
     * This is the full path where the lock file will be created.
     */
    protected $lockfile;

    /**
     * Get the filename for the lock file.
     * This is a static method that returns the name of the lock file.
     *
     * @return string The name of the lock file.
     */
    public static function filename() {
        return '/vpl.lock';
    }

    /**
     * Constructor for the lock class.
     * This will create a lock file in the specified directory.
     * If the lock file already exists, it will wait until it can create it.
     *
     * @param string $dir The directory where the lock file will be created.
     * @param int $timeout The maximum time to wait for the lock in seconds.
     */
    public function __construct($dir, $timeout = 5) {
        global $CFG;
        if (! file_exists($dir)) {
            mkdir($dir, $CFG->directorypermissions, true);
        }
        $this->lockfile = $dir . self::filename();
        $ctime = 0;
        $start = time();
        $ntries = 0;
        while ($ntries < 10) {
            $fp = false;
            if (! file_exists($this->lockfile)) {
                $fp = fopen($this->lockfile, 'x');
                if ($fp !== false) { // Locked by me.
                    fclose($fp);
                    break;
                }
            }
            if ($fp === false) { // Locked by other.
                $time = filectime($this->lockfile);
                if ($time !== false && $time != $ctime) { // First time or locker changed.
                    $ctime = $time;
                    $start = time();
                }
                usleep(100000);
                if ($start + $timeout < time()) { // Lock timeout => removed.
                    if (file_exists($this->lockfile)) {
                        unlink($this->lockfile);
                        $ntries++;
                    } else {
                        continue;
                    }
                }
            }
        }
    }

    /**
     * Remove the lock file when the object is destroyed.
     * This is called automatically when the script ends or the object goes out of scope.
     */
    public function __destruct() {
        if (file_exists($this->lockfile)) {
            unlink($this->lockfile);
        }
    }
}
