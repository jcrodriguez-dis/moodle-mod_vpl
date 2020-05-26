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

defined( 'MOODLE_INTERNAL' ) || die();
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/views/sh_factory.class.php');
require_once(dirname(__FILE__).'/similarity/watermark.class.php');


class file_group_process {
    /**
     * Name of file list
     *
     * @var string
     */
    protected $filelistname;

    /**
     * Path to directory where files are saved
     *
     * @var string
     */
    protected $dir;

    /**
     * Maximum number of files
     *
     * @var int
     */
    protected $maxnumfiles;

    /**
     * Number of files not changeables 0..($numstaticfiles-1)
     *
     * @var int
     */
    protected $numstaticfiles;

    /**
     * Save an array of strings in a file
     *
     * @param $filename string
     * @param $list array of strings
     *
     * @return void
     */
    public static function write_list($filename, $list, $otherfln = false) {
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
        if ($otherfln != false && file_exists( $otherfln )
            && file_get_contents( $otherfln ) === $data
            && link( $otherfln, $filename) ) {
            return;
        }
        $fp = vpl_fopen( $filename );
        fwrite( $fp, $data );
        fclose( $fp );
    }

    /**
     * get parsed lines of a file
     *
     * @param $filename string
     * @return array of lines of the file
     */
    public static function read_list($filename) {
        $ret = array ();
        if (file_exists( $filename )) {
            $data = file_get_contents( $filename );
            if ($data > '') {
                $nl = vpl_detect_newline( $data );
                $ret = explode( $nl, $data );
            }
        }
        return $ret;
    }
    /**
     * Constructor
     *
     * @param string $filelistname
     * @param string $dir
     * @param int $maxnumfiles
     * @param int $numstaticfiles
     */
    public function __construct($filelistname, $dir, $maxnumfiles = 10000, $numstaticfiles = 0) {
        $this->filelistname = $filelistname;
        $this->dir = $dir;
        if (strlen( $dir ) == 0 || $dir [strlen( $dir ) - 1] != '/') {
            $this->dir .= '/';
        }
        $this->maxnumfiles = $maxnumfiles;
        $this->numstaticfiles = $numstaticfiles;
    }

    /**
     * Get max number of files.
     *
     * @return int
     */
    public function get_maxnumfiles() {
        return $this->maxnumfiles;
    }

    /**
     * Get number of static files.
     *
     * @return int
     */
    public function get_numstaticfiles() {
        return $this->numstaticfiles;
    }

    /**
     * encode file path to be a file name
     * @parm path file path
     * @return string
     */
    public static function encodefilename($path) {
        return str_replace('/', '=', $path);
    }

    /**
     * Add a new file to the group/Modify the data file
     *
     * @param string $filename
     * @param string $data
     * @return bool (added==true)
     */
    public function addfile($filename, $data = null) {
        if (! vpl_is_valid_path_name( $filename )) {
            return false;
        }
        ignore_user_abort( true );
        $filelist = $this->getFileList();
        foreach ($filelist as $f) {
            if ($filename == $f) {
                if ($data !== null) {
                    $path = $this->dir . self::encodeFileName( $filename );
                    $fd = vpl_fopen( $path );
                    fwrite( $fd, $data );
                    fclose( $fd );
                }
                return true;
            }
        }
        if (count( $filelist ) >= $this->maxnumfiles) {
            return false;
        }
        $filelist [] = $filename;
        $this->setFileList( $filelist );
        if ($data) {
            $path = $this->dir . self::encodeFileName( $filename );
            $fd = vpl_fopen( $path );
            fwrite( $fd, $data );
            fclose( $fd );
        }
        return true;
    }

    /**
     * Add new files to the group/Modify the data file
     *
     * @param array $files
     * @return bool (added==true)
     */
    public function addallfiles($files, $otherdir = false, $otherfln = false) {
        ignore_user_abort( true );
        $filelist = $this->getFileList();
        $filehash = array();
        foreach ($filelist as $f) {
            $filehash [$f] = 1;
        }
        foreach ($files as $filename => $data) {
            if ( !isset($filehash[$filename]) ) {
                $filelist[] = $filename;
            }
            if ($data === null) {
                $data = '';
            }
            $fnencode = self::encodeFileName( $filename );
            $path = $this->dir . $fnencode;
            if ( $otherdir != false ) {
                $otherpath = $otherdir . $fnencode;
                if (file_exists( $otherpath)
                        && $data == file_get_contents( $otherpath )
                        && link($otherpath, $path) ) {
                    continue;
                }
            }
            $fp = vpl_fopen( $path );
            fwrite( $fp, $data );
            fclose( $fp );
        }
        $this->setFileList( $filelist, $otherfln);
    }

    /**
     * Delete all files from groupfile
     *
     * @return void
     */
    public function deleteallfiles() {
        ignore_user_abort( true );
        $filelist = $this->getFileList();
        foreach ($filelist as $filename) {
            $fullname = $this->dir . self::encodeFileName( $filename );
            if (file_exists( $fullname )) {
                unlink( $fullname );
            }
        }
        $this->setFileList( array() );
    }

    /**
     * Get list of files
     *
     * @return string[]
     */
    public function getfilelist() {
        return self::read_list($this->filelistname);
    }

    /**
     * Get all files from group
     *
     * @return array $files
     */
    public function getallfiles() {
        $files = array();
        $filelist = $this->getFileList();
        foreach ($filelist as $filename) {
            $fullname = $this->dir . self::encodeFileName( $filename );
            if (file_exists( $fullname )) {
                 $files [$filename] = file_get_contents( $fullname );
            } else {
                 $files [$filename] = '';
            }
        }
        return $files;
    }

    /**
     * Set the file list.
     *
     * @param string[] $filelist
     */
    public function setfilelist($filelist, $otherfln = false) {
        self::write_list($this->filelistname, $filelist, $otherfln );
    }

    /**
     * Get the file comment by number
     *
     * @param int $num
     * @return string
     */
    public function getfilecomment($num) {
        return get_string( 'file' ) . ' ' . ($num + 1);
    }

    /**
     * Get the file data by number or name
     *
     * @param int/string $mix
     * @return string
     */
    public function getfiledata($mix) {
        if (is_int( $mix )) {
            $num = $mix;
            $filelist = $this->getFileList();
            if ($num >= 0 && $num < count( $filelist )) {
                $filename = $this->dir . self::encodeFileName( $filelist [$num] );
                if (file_exists( $filename )) {
                    return file_get_contents( $filename );
                } else {
                    return '';
                }
            }
        } else if (is_string( $mix )) {
            $filelist = $this->getFileList();
            if (array_search( $mix, $filelist ) !== false) {
                $fullfilename = $this->dir . self::encodeFileName( $mix );
                if (file_exists( $fullfilename )) {
                    return file_get_contents( $fullfilename );
                } else {
                    return '';
                }
            }
        }
        debugging( "File not found $mix", DEBUG_DEVELOPER );
        return '';
    }

    /**
     * Return is there is some file with data
     *
     * @return boolean
     */
    public function is_populated() {
        $filelist = $this->getFileList();
        foreach ($filelist as $filename) {
            $fullname = $this->dir . self::encodeFileName( $filename );
            if (file_exists( $fullname )) {
                $info = stat( $fullname );
                if ($info ['size'] > 0) {
                    return true;
                }
            }
        }
        return false;
    }

    static protected $outputtextsize = 0; // Total size of text files shown.
    static protected $outputbinarysize = 0; // Total size of binary files shown.
    static protected $outputtextlimit = 100000; // Limit of total size of text files shown.
    static protected $outputbinarylimit = 10000000; // Limit of total size of binary files shown.
    /**
     * Print file group
     */
    public function print_files($ifnoexist = true) {
        $filenames = $this->getFileList();
        $showbinary = self::$outputbinarysize < self::$outputbinarylimit;
        $showcode = self::$outputtextsize < self::$outputtextlimit;
        foreach ($filenames as $name) {
            if (file_exists( $this->dir . self::encodeFileName( $name ) )) {
                if ( vpl_is_binary($name) ) {
                    if ($showbinary) {
                        $printer = vpl_sh_factory::get_sh( $name );
                        $data = $this->getFileData( $name );
                        $printer->print_file( $name, $data );
                        self::$outputbinarysize += strlen($data);
                    } else {
                        echo '<h4>' . s( $name ) . '</h4>';
                        echo "[...]";
                    }
                } else {
                    if ($showcode) {
                        $printer = vpl_sh_factory::get_sh( $name );
                    } else {
                        $printer = vpl_sh_factory::get_object('text_nsh');
                    }
                    $data = $this->getFileData( $name );
                    $printer->print_file( $name, $data );
                    self::$outputtextsize += strlen($data);
                }
            } else if ($ifnoexist) {
                echo '<h4>' . s( $name ) . '</h4>';
            }
        }
        vpl_sh_factory::syntaxhighlight();
    }

    /**
     * Download files
     *
     * @parm $name name of zip file generated
     */
    public function download_files($name, $watermark = false) {
        global $CFG;
        global $USER;
        $zip = new ZipArchive();
        $dir = $CFG->dataroot . '/temp/vpl';
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        $zipfilename = tempnam( $dir, 'zip' );
        if ($zip->open( $zipfilename, ZipArchive::CREATE | ZipArchive::OVERWRITE )) {
            foreach ($this->getFileList() as $filename) {
                $data = $this->getFileData( $filename );
                if ($watermark) {
                    $data = vpl_watermark::addwm( $data, $filename, $USER->id );
                }
                $zip->addFromString( $filename, $data );
            }
            $zip->close();
            vpl_output_zip($zipfilename, $name);
            die();
        }
    }
}

