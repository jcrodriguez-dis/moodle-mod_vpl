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
 * Class to manage a group of files
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\util;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/locallib.php');
require_once($CFG->dirroot . '/mod/vpl/views/sh_factory.class.php');
require_once($CFG->dirroot . '/mod/vpl/similarity/watermark.class.php');

/**
 * Class file_group
 * Manage a group of files for VPL activities.
 * Limits minimum and maximum number of files and keeps file order.
 */
class file_group {
    /**
     * @var string $filelistname Name of file list
     */
    protected $filelistname;

    /**
     * @var string $dir Path to directory where files are saved
     */
    protected $dir;

    /**
     * @var int $maxnumfiles Maximum number of files
     */
    protected $maxnumfiles;

    /**
     * @var int $numstaticfiles Number of file names not changeables 0..($numstaticfiles-1)
     */
    protected $numstaticfiles;

    /**
     * Save an array of strings in a file
     *
     * @param string $filename Name of the file to save the list.
     * @param array $list Array of strings to save in the file.
     * @param string $otherfln If not false, use this file name to save the list.
     *
     * @return void
     */
    public static function write_list($filename, $list, $otherfln = false) {
        if (!function_exists('link')) {
            $otherfln = false; // If link() is not available, do not use other file.
        }
        $data = '';
        foreach ($list as $info) {
            if ($info > '') {
                if ($data > '') {
                    $data .= "\n";
                }
                $data .= $info;
            }
        }
        // Try to reuse other file.
        if (
            $otherfln != false && is_file($otherfln)
            && file_get_contents($otherfln) === $data
            && link($otherfln, $filename)
        ) {
            return;
        }
        vpl_fwrite($filename, $data);
    }

    /**
     * Get parsed lines of a file
     *
     * @param string $filename string
     * @return array of lines of the file
     */
    public static function read_list($filename) {
        $ret = [];
        if (is_file($filename)) {
            $data = file_get_contents($filename);
            if ($data > '') {
                $nl = vpl_detect_newline($data);
                $ret = explode($nl, $data);
            }
        }
        return $ret;
    }

    /**
     * Constructor
     *
     * @param string $dir Path to the directory where files are saved.
     * @param int $maxnumfiles Maximum number of files in the group.
     * @param int $numstaticfiles Number of files that must exist.
     */
    public function __construct($dir, $maxnumfiles = 10000, $numstaticfiles = 0) {
        $this->dir = dirname($dir) . "/" . basename($dir);
        $this->filelistname = $this->dir . ".lst";
        $this->maxnumfiles = $maxnumfiles;
        $this->numstaticfiles = $numstaticfiles;
    }

    /**
     * Get max number of files.
     * @return int
     */
    public function get_maxnumfiles() {
        return $this->maxnumfiles;
    }

    /**
     * Get number of static files.
     * @return int
     */
    public function get_numstaticfiles() {
        return $this->numstaticfiles;
    }

    /**
     * Encode file path to be a file name
     * @param string $path file path
     * @return string
     */
    public static function encodefilename($path) {
        return str_replace('/', '=', $path);
    }

    /**
     * Add a new file to the group/Modify the data file
     *
     * @param string $filename Name of the file to add or modify.
     * @param string $data Data to write in the file. If null, the file is deleted.
     * @return bool true if the file was added or modified, false if the file could not be added
     */
    public function addfile($filename, $data = null) {
        if (! vpl_is_valid_path_name($filename)) {
            return false;
        }
        ignore_user_abort(true);
        $filelist = $this->getFileList();
        $path = $this->dir . '/' . self::encodeFileName($filename);
        if (array_search($filename, $filelist) !== false) {
            if ($data !== null) {
                vpl_fwrite($path, $data);
            } else {
                if (is_file($path)) {
                    unlink($path);
                }
            }
            return true;
        }
        if (count($filelist) >= $this->maxnumfiles) {
            return false;
        }
        $filelist[] = $filename;
        $this->setFileList($filelist);
        if ($data !== null) {
            vpl_fwrite($path, $data);
        }
        return true;
    }

    /**
     * Add new files to the group/Modify the data file
     *
     * @param array $files
     * @param string $otherdir If not false, use this directory to check if the file exists.
     * @param string $otherfln If not false, use this file name to save the list.
     */
    public function addallfiles($files, $otherdir = false, $otherfln = false) {
        ignore_user_abort(true);
        if (!function_exists('link')) {
            $otherdir = false; // If link() is not available, do not use other dir.
        }
        $filelist = $this->getFileList();
        $filehash = [];
        foreach ($filelist as $f) {
            $filehash[$f] = 1;
        }
        vpl_create_dir($this->dir);
        foreach ($files as $filename => $data) {
            if (!isset($filehash[$filename])) {
                $filelist[] = $filename;
            }
            if ($data === null) {
                $data = '';
            }
            $fnencode = self::encodeFileName($filename);
            $path = $this->dir . '/' . $fnencode;
            if ($otherdir != false) {
                $otherpath = $otherdir . $fnencode;
                if (
                    file_exists($otherpath)
                        && $data == file_get_contents($otherpath)
                        && link($otherpath, $path)
                ) {
                    continue;
                }
            }
            vpl_fwrite($path, $data);
        }
        $this->setFileList($filelist, $otherfln);
    }

    /**
     * Delete all files from groupfile
     *
     * @return void
     */
    public function deleteallfiles() {
        ignore_user_abort(true);
        $filelist = $this->getFileList();
        foreach ($filelist as $filename) {
            $fullname = $this->dir . '/' . self::encodeFileName($filename);
            if (is_file($fullname)) {
                unlink($fullname);
            }
        }
        $this->setFileList([]);
    }

    /**
     * Get the file list name used by default
     *
     * @return string
     */
    public function getfilelistname() {
        return $this->filelistname;
    }

    /**
     * Cache for file list
     * @var array|null
     */
    protected $cachefilelist = null;

    /**
     * Get list of files
     *
     * @return array List of file names
     */
    public function getfilelist() {
        if ($this->cachefilelist !== null) {
            return $this->cachefilelist;
        }
        $this->cachefilelist = self::read_list($this->filelistname);
        return $this->cachefilelist;
    }

    /**
     * Get all files from group
     *
     * @return array $files
     */
    public function getallfiles() {
        $files = [];
        $filelist = $this->getFileList();
        foreach ($filelist as $filename) {
            $fullname = $this->dir . '/' . self::encodeFileName($filename);
            if (is_file($fullname)) {
                 $files[$filename] = file_get_contents($fullname);
            } else {
                 $files[$filename] = '';
            }
        }
        return $files;
    }

    /**
     * Set the file list.
     *
     * @param array $filelist List of file names to save.
     * @param bool $otherfln If true, use the other file name to save the list.
     */
    public function setfilelist($filelist, $otherfln = false) {
        self::write_list($this->filelistname, $filelist, $otherfln);
        $this->cachefilelist = $filelist;
    }

    /**
     * Get the file comment by number
     *
     * @param int $num
     * @return string comment for the file
     */
    public function getfilecomment($num) {
        return get_string('file') . ' ' . ($num + 1);
    }

    /**
     * Get the file data by number or name
     *
     * @param int/string $mix
     * @return string file data or empty string if file does not exist
     */
    public function getfiledata($mix) {
        if (is_int($mix)) {
            $num = $mix;
            $filelist = $this->getFileList();
            if ($num >= 0 && $num < count($filelist)) {
                $filename = $this->dir . '/' . self::encodeFileName($filelist[$num]);
                if (is_file($filename)) {
                    return file_get_contents($filename);
                } else {
                    return '';
                }
            }
        } else if (is_string($mix)) {
            $filelist = $this->getFileList();
            if (array_search($mix, $filelist) !== false) {
                $fullfilename = $this->dir . '/' . self::encodeFileName($mix);
                if (is_file($fullfilename)) {
                    return file_get_contents($fullfilename);
                } else {
                    return '';
                }
            }
        }
        debugging("File not found $mix", DEBUG_DEVELOPER);
        return '';
    }

    /**
     * Return is there is some file with data
     *
     * @return boolean true if there is some file with data, false otherwise
     */
    public function is_populated() {
        $filelist = $this->getFileList();
        foreach ($filelist as $filename) {
            $fullname = $this->dir . '/' . self::encodeFileName($filename);
            if (is_file($fullname)) {
                $info = stat($fullname);
                if ($info['size'] > 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Return a version number for the group file
     *
     * @return int Version number based on the last modification time of the directory
     */
    public function getversion() {
        if (file_exists($this->dir)) {
            $info = stat($this->dir);
            if ($info !== false) {
                return $info['mtime'];
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    /**
     * @var int $outputtextsize Total size of text files shown.
     */
    protected static $outputtextsize = 0;

    /**
     * @var int $outputbinarysize Total size of binary files shown.
     */
    protected static $outputbinarysize = 0;

    /**
     * @var int $outputtextlimit Limit of total size of text files shown.
     */
    protected static $outputtextlimit = 100000;

    /**
     * @var int $outputbinarylimit Limit of total size of binary files shown.
     */
    protected static $outputbinarylimit = 10000000;
    /**
     * Print the files in the group.
     *
     * @param bool $ifnoexist If true, prints the file name even if the file does not exist.
     */
    public function print_files($ifnoexist = true) {
        $filenames = $this->getFileList();
        $showbinary = self::$outputbinarysize < self::$outputbinarylimit;
        $showcode = self::$outputtextsize < self::$outputtextlimit;
        foreach ($filenames as $name) {
            if (is_file($this->dir . '/' . self::encodeFileName($name))) {
                if (vpl_is_binary($name)) {
                    if ($showbinary) {
                        $printer = \vpl_sh_factory::get_sh($name);
                        $data = $this->getFileData($name);
                        $printer->print_file($name, $data);
                        self::$outputbinarysize += strlen($data);
                    } else {
                        echo '<h4>' . s($name) . '</h4>';
                        echo "[...]";
                    }
                } else {
                    if ($showcode) {
                        $printer = \vpl_sh_factory::get_sh($name);
                    } else {
                        $printer = \vpl_sh_factory::get_object('text_nsh');
                    }
                    $data = $this->getFileData($name);
                    $printer->print_file($name, $data);
                    self::$outputtextsize += strlen($data);
                }
            } else if ($ifnoexist) {
                echo '<h4>' . s($name) . '</h4>';
            }
        }
        \vpl_sh_factory::syntaxhighlight();
    }

    /**
     * Generate temporal zip file
     *
     * @param bool $watermark bool Adds watermark to files
     */
    public function generate_zip_file(bool $watermark = false) {
        global $CFG;
        global $USER;
        $zip = new \ZipArchive();
        $dir = $CFG->dataroot . '/temp/vpl';
        if (! file_exists($dir)) {
            mkdir($dir, $CFG->directorypermissions, true);
        }
        $zipfilename = tempnam($dir, 'zip');
        $filelist = $this->getFileList();
        if (count($filelist) > 0) {
            if ($zip->open($zipfilename, \ZipArchive::OVERWRITE) === true) {
                foreach ($filelist as $filename) {
                    $data = $this->getFileData($filename);
                    if ($watermark) {
                        $data = \vpl_watermark::addwm($data, $filename, $USER->id);
                    }
                    $zip->addFromString($filename, $data);
                }
                $zip->close();
            } else {
                return false;
            }
        } else {
            vpl_fwrite($zipfilename, base64_decode("UEsFBgAAAAAAAAAAAAAAAAAAAAAAAA=="));
        }
        return $zipfilename;
    }

    /**
     * Download files as zip
     *
     * @param string $name Name of the zip file to download
     * @param bool $watermark Add watermark to files in the zip
     */
    public function download_files($name, $watermark = false) {
        $zipfilename = $this->generate_zip_file($watermark);
        if ($zipfilename !== false) {
            vpl_output_zip($zipfilename, $name);
            die();
        }
    }
}
