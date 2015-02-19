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
 * Class to edit a group of files
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/locallib.php';
require_once dirname(__FILE__).'/views/sh_factory.class.php';

class file_group_process{
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
     * Constructor
     *
     * @param string $filelistname
     * @param string $dir
     * @param int $maxnumfiles
     * @param int $numstaticfiles
     */
    function __construct($filelistname,$dir,$maxnumfiles=10000,$numstaticfiles=0){
        $this->filelistname = $filelistname;
        $this->dir = $dir;
        if(strlen($dir) == 0 || $dir[strlen($dir)-1] != '/'){
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
    function get_maxnumfiles(){
        return $this->maxnumfiles;
    }

    /**
     * Get number of static files.
     *
     * @return int
     */
    function get_numstaticfiles(){
        return $this->numstaticfiles;
    }

    /**
     * encode file path to be a file name
     * @parm path file path
     * @return string
     */
    static function encodeFileName($path){
        return str_replace('/','=',$path);
    }

    /**
     * Add a new file to the group/Modify the data file
     *
     * @param string $filename
     * @param string $data
     * @return bool (added==true)
     */
    function addFile($filename,$data=null){
        if(!vpl_is_valid_path_name($filename)){
            return false;
        }
        ignore_user_abort (true);
        $filelist = $this->getFileList();
        foreach($filelist as $f){
            if($filename == $f){
                if($data !== null){
                    $path=$this->dir.self::encodeFileName($filename);
                    $fd = vpl_fopen($path);
                    fwrite($fd,$data);
                    fclose($fd);
                }
                return true;
            }
        }
        if(count($filelist)>= $this->maxnumfiles){
            return false;
        }
        $filelist[] = $filename;
        $this->setFileList($filelist);
        if($data){
            $path=$this->dir.self::encodeFileName($filename);
            $fd = vpl_fopen($path);
            fwrite($fd,$data);
            fclose($fd);
        }
        return true;
    }

    /**
     * Delete a file from groupfile
     *
     * @param int $num file position
     * @return bool
     */
    function deleteFile($num){
        if($num < $this->numstaticfiles){
            return false;
        }
        ignore_user_abort (true);
        $filelist = $this->getFileList();
        $l = count($filelist);
        $ret = false;
        $filelistmod = array();
        for($i = 0 ;$i <$l; $i++){
            if($num== $i){
                $fullname = $this->dir.self::encodeFileName($filelist[$num]);
                $ret = true;
                if(file_exists($fullname)){
                    unlink($fullname);
                }
            }
            else{
                $filelistmod[]=$filelist[$i];
            }
        }
        if($ret){
            $this->setFileList($filelistmod);
        }
        return $ret;
    }

    /**
     * Rename a file
     *
     * @param int $num
     * @param string $filename new filename
     * @return bool (renamed==true)
     */
    function renameFile($num,$filename){
        if($num<$this->numstaticfiles || !vpl_is_valid_path_name($filename)){
            return false;
        }
        ignore_user_abort (true);
        $filelist = $this->getFileList();
        if(array_search($filename,$filelist) !== false){
            return false;
        }
        if($num >= 0 && $num<count($filelist)){
            $path1=$this->dir.self::encodeFileName($filelist[$num]);
            $path2=$this->dir.self::encodeFileName($filename);
            if(file_exists($path1)){
                rename($path1,$path2);
                $filelist[$num] =$filename;
                $this->setFileList($filelist);
                return true;
            }
        }
        return false;
    }

    /**
     * Get list of files
     *
     * @return string[]
     */
    function getFileList(){
        return vpl_read_list_from_file($this->filelistname);
    }

    /**
     * Set the file list.
     *
     * @param string[] $filelist
     */
    function setFileList($filelist){
        vpl_write_list_to_file($this->filelistname,$filelist);
    }

    /**
     * Get the file comment by number
     *
     * @param int $num
     * @return string
     */
    function getFileComment($num){
        return get_string('file').' '.($num+1);
    }

    /**
     * Get the file data by number or name
     *
     * @param int/string $mix
     * @return string
     */
    function getFileData($mix){
        if(is_int($mix)){
            $num=$mix;
            $filelist = $this->getFileList();
            if($num>=0 && $num<count($filelist)){
                $filename =$this->dir.self::encodeFileName($filelist[$num]);
                if(file_exists($filename)){
                    return file_get_contents($filename);
                }else{
                    return '';
                }
            }
        }
        elseif(is_string($mix)){
            $filelist = $this->getFileList();
            if(array_search($mix,$filelist)!== false){
                $fullfilename =$this->dir.self::encodeFileName($mix);
                if(file_exists($fullfilename)){
                    return file_get_contents($fullfilename);
                }else{
                    return '';
                }
            }
        }
        debugging("File not found $mix",DEBUG_DEVELOPER);
        return '';
    }

    /**
     * Return is there is some file with data
     * @return boolean
     */
    function is_populated(){
        $filelist = $this->getFileList();
        foreach($filelist as $filename){
            $fullname = $this->dir.self::encodeFileName($filename);
            if(file_exists($fullname)){
                $info = stat($fullname);
                 if($info['size']>0){
                     return true;
                 }
            }
        }
        return false;
    }


    /**
     * Print file group
     **/
    function print_files($if_no_exist=true){
        global $OUTPUT;
        $filenames = $this->getFileList();
        foreach ($filenames as $name) {
            if(file_exists($this->dir.self::encodeFileName($name))){
                echo '<h3>'.s($name).'</h3>';
                $printer= vpl_sh_factory::get_sh($name);
                echo $OUTPUT->box_start();
                $data = $this->getFileData($name);
                $printer->print_file($name,$data);
                echo $OUTPUT->box_end();
            }elseif($if_no_exist){
                echo '<h3>'.s($name).'</h3>';
            }
        }
    }

    /**
     * Download files
     * @parm $name name of zip file generated
     **/
    function download_files($name){
        $cname = rawurlencode($name.'.zip');
        global $CFG;
        $zip = new ZipArchive();
        $zipfilename=tempnam($CFG->dataroot . '/temp/'  , 'vpl_zipdownload' );
        if($zip->open($zipfilename,ZIPARCHIVE::CREATE)){
            foreach ($this->getFileList() as $filename) {
                $zip->addFromString($filename, $this->getFileData($filename));
            }
            $zip->close();
            //Get zip data
            $data=file_get_contents($zipfilename);
            //remove zip file
            unlink($zipfilename);
            //Send zipdata
            @header('Content-Length: '.strlen($data));
            @header('Content-Type: application/zip; charset=utf-8');
            @header('Content-Disposition: attachment; filename="'.$name.'.zip"; filename*=utf-8\'\''.$cname);
            @header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
            @header('Content-Transfer-Encoding: binary');
            @header('Expires: 0');
            @header('Pragma: no-cache');
            @header('Accept-Ranges: none');
            echo $data;
            die;
        }
    }
}

