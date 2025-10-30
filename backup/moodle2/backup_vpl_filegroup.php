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
 * Provides support for backup VPL file groups in the moodle2 backup format
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl\backup;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../../vpl.class.php');
/**
 * Provide backup of group of files
 *
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 * @see backup_nested_element
 */
class backup_vpl_filegroup extends \backup_nested_element {
    /**
     * Read a file and return it as Object encoded if needed
     * @param string $base directory
     * @param string $filename name of file
     * @return \stdClass file info object
     */
    private function load_file($base, $filename) {
        $data = file_get_contents($base . $filename);
        $info = new \stdClass();
        if (vpl_is_binary($filename, $data)) {
            $info->name = $filename . '.b64'; // For backward compatibility.
            $info->content = base64_encode($data);
            $info->encoding = 1;
        } else {
            $info->name = $filename;
            $info->content = $data;
            $info->encoding = 0;
        }
        return $info;
    }

    /**
     * Read files in a directory
     * @param string $base directory
     * @param string $dirname containing files to backup
     * @return backup_array_iterator
     */
    private function get_files($base, $dirname) {
        $files = [];
        $filelst = $dirname . '.lst';
        $extrafiles = [
                $filelst,
                $filelst . '.keep',
                'compilation.txt',
                'execution.txt',
                'grade_comments.txt',
        ];
        foreach ($extrafiles as $file) {
            if (file_exists($base . $file)) {
                $files[] = $this->load_file($base, $file);
            }
        }
        $dirpath = $base . $dirname;
        if (file_exists($dirpath)) {
            $dirlst = opendir($dirpath);
            while (false !== ($filename = readdir($dirlst))) {
                if ($filename == "." || $filename == "..") {
                    continue;
                }
                $files[] = $this->load_file($base, $dirname . '/' . $filename);
            }
            closedir($dirlst);
        }
        return new \backup_array_iterator($files);
    }

    /**
     * Returns list of backup files as iterator
     *
     * @param object $processor unused
     * @return object backup array iterator
     */
    protected function get_iterator($processor) {
        global $CFG;

        switch ($this->get_name()) {
            case 'required_file':
                $vplid = $this->find_first_parent_by_name('id')->get_value();
                $path = $CFG->dataroot . '/vpl_data/' . $vplid . '/';
                return $this->get_files($path, 'required_files');
            case 'execution_file':
                $vplid = $this->find_first_parent_by_name('id')->get_value();
                $path = $CFG->dataroot . '/vpl_data/' . $vplid . '/';
                return $this->get_files($path, 'execution_files');
                break;
            case 'submission_file':
                $vplid = $this->find_first_parent_by_name('vpl')->get_value();
                $subid = $this->find_first_parent_by_name('id')->get_value();
                $userid = $this->find_first_parent_by_name('userid')->get_value();
                $path = $CFG->dataroot . '/vpl_data/' . $vplid . '/usersdata/' . $userid . '/' . $subid . '/';
                return $this->get_files($path, 'submittedfiles');
                break;
            default:
                throw new \Exception('Type of element error for backup_nested_group');
        }
    }
}
