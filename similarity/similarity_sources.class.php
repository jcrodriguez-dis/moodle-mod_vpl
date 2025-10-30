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
 * Classes to manage file from difrerent soruces
 *
 * @package mod_vpl
 * @copyright 2015 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class to represent files from any source.
 */
class vpl_file_from_base {
    /**
     * Returns the file information.
     * @return string HTML string with the file information.
     */
    public function show_info() {
    }

    /**
     * Returns if the file can be accessed.
     * @return bool True if the file can be accessed, false otherwise.
     */
    public function can_access() {
        return false;
    }

    /**
     * Returns the user ID of the file.
     * @return string The user ID associated with the file.
     */
    public function get_userid() {
        return '';
    }
}

/**
 * Information of a file from a directory
 */
class vpl_file_from_dir extends vpl_file_from_base {
    /**
     * @var array $usersname Static array to store user names indexed by their IDs.
     * This is static to avoid loading the same user multiple times.
     */
    protected static $usersname = [];

    /**
     * @var string $dirname Directory name where the file is located.
     */
    protected $dirname;

    /**
     * @var string $filename Name of the file.
     */
    protected $filename;

    /**
     * @var string $userid User ID associated with the file.
     */
    protected $userid;

    /**
     * @var int $vplid ID of the VPL activity.
     */
    protected $vplid;

    /**
     * Processes a GAP user file to extract user information.
     * This method reads the contents of a file named 'datospersonales.gap'
     * and extracts the user's NIF, name, and surname.
     * It then stores the user's full name in the static $usersname array.
     * This is for compatibility with GAP 2.x application.
     * @param string $filepath The path to the file to process.
     */
    public static function process_gap_userfile($filepath) {
        if (strtolower(basename($filepath)) == 'datospersonales.gap') {
            $nif = '';
            $nombre = '';
            $apellidos = '';
            $lines = explode("\n", file_get_contents($filepath));
            if (count($lines) > 3) {
                if (strpos($lines[1], 'NIF=') !== false) {
                    $nif = substr($lines[1], 4);
                }
                if (strpos($lines[2], 'Nombre=') !== false) {
                    $nombre = substr($lines[2], 7);
                }
                if (strpos($lines[3], 'Apellidos=') !== false) {
                    $apellidos = substr($lines[3], 10);
                }
            }
            if ($nif > '' && $nombre > '' && $apellidos > '') {
                global $CFG;
                if ($CFG->fullnamedisplay == 'lastname firstname') {
                    self::$usersname[$nif] = mb_convert_encoding($apellidos . ', ' . $nombre, 'utf-8');
                } else {
                    self::$usersname[$nif] = mb_convert_encoding($nombre . ' ' . $apellidos, 'utf-8');
                }
            }
        }
    }

    /**
     * Returns the user ID from the filename.
     * This is for compatibility with GAP 2.x application.
     * @param string $filename The name of the file.
     * @return string The user ID if found, otherwise an empty string.
     */
    public static function get_user_id_from_file($filename) {
        if (count(self::$usersname)) {
            $filename = strtolower($filename);
            foreach (array_keys(self::$usersname) as $userid) {
                if (strpos($filename, $userid) !== false) {
                    return $userid;
                }
            }
        }
        return '';
    }

    /**
     * Constructor for the vpl_file_from_dir class.
     *
     * @param string $filename The name of the file.
     * @param int $vplid The ID of the VPL activity.
     * @param string $dirname The directory name where the file is located.
     * @param string $userid The user ID associated with the file (optional).
     */
    public function __construct(&$filename, $vplid, $dirname, $userid = '') {
        $this->filename = $filename;
        $this->dirname = $dirname;
        $this->vplid = $vplid;
        $this->userid = self::get_user_id_from_file($filename);
    }

    /**
     * Returns the user ID of the file.
     * @return string The user ID associated with the file.
     */
    public function get_userid() {
        return $this->userid;
    }

    /**
     * Returns the file information.
     * @return string HTML string with the file information
     */
    public function show_info() {
        $ret = '';
        $ret .= '<a href="' . 'file.php' . '">';
        $ret .= s($this->filename) . ' ';
        $ret .= '</a>';
        if ($this->userid != '') {
            $ret .= ' ' . self::$usersname[$this->userid];
        }
        return $ret;
    }

    /**
     * Returns if the file can be accessed.
     * @return bool True if the file can be accessed, false otherwise
     */
    public function can_access() {
        return $this->filename != vpl_similarity_preprocess::JOINEDFILENAME;
    }

    /**
     * Returns the parameters to link to this file.
     *
     * @param int $t Type of link (1 for directory, 2 for activity, 3 for zip)
     * @return array Associative array with parameters for the link
     */
    public function link_parms($t) {
        $res = [
                'type' . $t => 2,
                'dirname' . $t => $this->dirname,
                'filename' . $t => $this->filename,
        ];
        if ($this->userid != '') {
            $res['username' . $t] = self::$usersname[$this->userid];
        }
        return $res;
    }
}

/**
 * Information of a file from a zip file
 */
class vpl_file_from_zipfile extends vpl_file_from_dir {
    /**
     * Returns the file information.
     * @return string HTML string with the file information
     */
    public function show_info() {
        $ret = '';
        $ret .= s($this->filename);
        if ($this->userid != '') {
            $ret .= ' ' . self::$usersname[$this->userid];
        }
        return $ret;
    }

    /**
     * Returns if the file can be accessed.
     */
    public function can_access() {
        return true;
    }

    /**
     * Returns the parameters to link to this file.
     *
     * @param int $t Type of link (1 for directory, 2 for activity, 3 for zip)
     * @return array Associative array with parameters for the link
     */
    public function link_parms($t) {
        $res = [
                'type' . $t => 3,
                'vplid' . $t => $this->vplid,
                'zipfile' . $t => $this->dirname,
                'filename' . $t => $this->filename,
        ];
        if ($this->userid != '') {
            $res['username' . $t] = self::$usersname[$this->userid];
        }
        return $res;
    }
}

/**
 * Information of a file from other vpl activity
 */
class vpl_file_from_activity extends vpl_file_from_base {
    /**
     * @var array $vpls Array to store VPL activities.
     * This is static to avoid loading the same activity multiple times.
     */
    protected static $vpls = [];

    /**
     * @var int $vplid ID of the VPL activity.
     */
    protected $vplid;

    /**
     * @var string $filename Name of the file.
     */
    protected $filename;

    /**
     * @var string $subid Submission ID of the file.
     */
    protected $subid;

    /**
     * @var string $userid User ID of the file.
     */
    protected $userid;

    /**
     * Constructor for the vpl_file_from_activity class.
     *
     * @param string $filename The name of the file.
     * @param mod_vpl $vpl The VPL activity object.
     * @param object $subinstance The subinstance object containing user ID and submission ID.
     */
    public function __construct(&$filename, &$vpl, $subinstance) {
        $id = $vpl->get_instance()->id;
        if (! isset(self::$vpls[$id])) {
            self::$vpls[$id] = $vpl;
        }
        $this->vplid = $id;
        $this->filename = $filename;
        $this->userid = $subinstance->userid;
        $this->subid = $subinstance->id;
    }

    /**
     * Shows the file information.
     *
     * @return string HTML string with the file information
     */
    public function show_info() {
        global $DB;
        $vpl = self::$vpls[$this->vplid];
        $cmid = $vpl->get_course_module()->id;
        $ret = '';
        if ($this->userid >= 0) {
            $user = $DB->get_record('user', [
                    'id' => $this->userid,
            ]);
        } else {
            $user = false;
        }
        if ($user) {
            $ret .= '<a href="' . vpl_mod_href('/forms/submissionview.php', 'id', $cmid, 'userid', $user->id) . '">';
        }
        $ret .= s($this->filename);
        if ($user) {
            $ret .= '</a> ';
            $sub = new mod_vpl_submission($vpl, $this->subid);
            $ret .= $sub->get_grade_core() . '<br>';
            $ret .= $vpl->user_fullname_picture($user);
            $link = vpl_mod_href('/similarity/user_similarity.php', 'id', $vpl->get_course()->id, 'userid', $user->id);
            $ret .= ' (<a href="' . $link . '">';
            $ret .= '*</a>)';
        }
        return $ret;
    }

    /**
     * Returns the user ID of the file.
     * @return string The user ID associated with the file.
     */
    public function get_userid() {
        return $this->userid;
    }

    /**
     * Returns if the file can be accessed.
     * @return bool True if the file can be accessed, false otherwise
     */
    public function can_access() {
        return $this->filename != vpl_similarity_preprocess::JOINEDFILENAME;
    }

    /**
     * Returns the parameters to link to this file.
     *
     * @param int $t Type of link (1 for directory, 2 for activity, 3 for zip)
     * @return array Associative array with parameters for the link
     */
    public function link_parms($t) {
        return [
                'type' . $t => 1,
                'subid' . $t => $this->subid,
                'filename' . $t => $this->filename,
        ];
    }
}

/**
 * Utility class to get list of preprocessed files
 */
class vpl_similarity_preprocess {
    /**
     *
     * @var const fake file name for files joined
     */
    const JOINEDFILENAME = '_joined_files_';
    /**
     *
     * @var minimum number of tokens needed to accept a file to be compared
     */
    const MINTOKENS = 10;

    /**
     * Preprocesses a submission, returning processed files
     *
     * @param object $fgm Object to manage the file of a submission
     * @param array $selectedfiles List of files to processes
     * @param boolean $allfiles If true preprocess all files
     * @param boolean $joinedfiles If true join files as one
     * @param object $vpl Activity to process (optional)
     * @param object $subinstance (optional)
     * @param array $toremove Array with filenames as keys to remove from comparation (optional)
     * @return array Asociative array with file name as key and simil object as value
     */
    public static function proccess_files(
        $fgm,
        $selectedfiles,
        $allfiles,
        $joinedfiles,
        $vpl = false,
        $subinstance = false,
        $toremove = []
    ) {
        $files = [];
        $filelist = $fgm->getFileList();
        $simjf = false;
        $from = null;
        $joinedfilesdata = '';
        foreach ($filelist as $filename) {
            if (! isset($selectedfiles[basename($filename)]) && ! $allfiles) {
                continue;
            }
            $sim = vpl_similarity_factory::get($filename);
            if ($sim) {
                $data = $fgm->getFileData($filename);
                if ($joinedfiles) {
                    if (! $simjf) {
                        $simjf = $sim;
                    }
                    $joinedfilesdata .= $data . "\n";
                } else {
                    if ($vpl) {
                        $from = new vpl_file_from_activity($filename, $vpl, $subinstance);
                    }
                    if (isset($toremove[$filename])) {
                        $sim->init($data, $from, $toremove[$filename]);
                    } else {
                        $sim->init($data, $from);
                    }
                    if ($sim->get_size() > self::MINTOKENS) {
                        $files[$filename] = $sim;
                    }
                }
            }
        }
        if ($simjf) {
            $filename = self::JOINEDFILENAME;
            if ($vpl) {
                $from = new vpl_file_from_activity($filename, $vpl, $subinstance);
            }
            if (isset($toremove[$filename])) {
                $simjf->init($joinedfilesdata, $from, $toremove[$filename]);
            } else {
                $simjf->init($joinedfilesdata, $from);
            }
            if ($simjf->get_size() > self::MINTOKENS) {
                $files[self::JOINEDFILENAME] = $simjf;
            }
        }
        return $files;
    }
    /**
     * Preprocesses an activity loading and preprocessing the students' files
     *
     * @param array& $simil $simil Input/output array that saves the new files found to process
     * @param object $vpl Activity to process
     * @param array $selectedfiles Files to process
     * @param bool $allfiles If true process all files
     * @param bool $joinedfiles If true process files as a joined file
     * @param object $spb Progress bar to show activity load
     * @return void
     */
    public static function activity(&$simil, $vpl, $selectedfiles, $allfiles, $joinedfiles, $spb) {
        $vpl->require_capability(VPL_SIMILARITY_CAPABILITY);
        $cm = $vpl->get_course_module();
        $groupmode = groups_get_activity_groupmode($cm);
        if (! $groupmode) {
            $groupmode = groups_get_course_groupmode($vpl->get_course());
        }
        $currentgroup = groups_get_activity_group($cm, true);
        if (! $currentgroup) {
            $currentgroup = '';
        }
        if ($vpl->is_group_activity()) {
            $list = groups_get_all_groups($vpl->get_course()->id, 0, $cm->groupingid);
        } else {
            $list = $vpl->get_students($currentgroup);
        }
        if (count($list) == 0) {
            return;
        }
        $submissions = $vpl->all_last_user_submission();
        // Get initial content files.
        $reqf = $vpl->get_required_fgm();
        $toremove = self::proccess_files($reqf, $selectedfiles, $allfiles, $joinedfiles);

        $spb->set_max(count($list));
        $i = 0;
        foreach ($list as $user) {
            $i++;
            $spb->set_value($i);
            if (isset($submissions[$user->id])) {
                $subinstance = $submissions[$user->id];
                $submission = new mod_vpl_submission($vpl, $subinstance);
                $subf = $submission->get_submitted_fgm();
                $files = self::proccess_files($subf, $selectedfiles, $allfiles, $joinedfiles, $vpl, $subinstance, $toremove);
                foreach ($files as $file) {
                    $simil[] = $file;
                }
            }
        }
    }

    /**
     * Preprocesses user activity, loading user activity files into $simil array
     *
     * @param array $simil of file processed objects
     * @param object $vpl activity to process
     * @param int $userid id of the user to preprocess
     * @return void
     */
    public static function user_activity(&$simil, $vpl, $userid) {
        $subinstance = $vpl->last_user_submission($userid);
        if (! $subinstance) {
            return;
        }
        $vpl->require_capability(VPL_SIMILARITY_CAPABILITY);
        // Get initial content files.
        $reqf = $vpl->get_required_fgm();
        $filelist = $reqf->getFileList();
        $toremove = [];
        foreach ($filelist as $filename) {
            $sim = vpl_similarity_factory::get($filename);
            if ($sim) {
                $data = $reqf->getFileData($filename);
                $sim->init($data, null);
                $toremove[$filename] = $sim;
            }
        }
        $submission = new mod_vpl_submission($vpl, $subinstance);
        $subf = $submission->get_submitted_fgm();
        $filelist = $subf->getFileList();
        foreach ($filelist as $filename) {
            $sim = vpl_similarity_factory::get($filename);
            if ($sim) {
                $data = $subf->getFileData($filename);
                $from = new vpl_file_from_activity($filename, $vpl, $subinstance);
                if (isset($toremove[$filename])) {
                    $sim->init($data, $from, $toremove[$filename]);
                } else {
                    $sim->init($data, $from);
                }
                if ($sim->get_size() > self::MINTOKENS) {
                    $simil[] = $sim;
                }
            }
        }
    }

    /**
     * Returns the file path for a ZIP file.
     *
     * @param int $vplid ID of the VPL activity.
     * @param string $zipname Name of the ZIP file.
     * @return string Full path to the ZIP file.
     */
    public static function get_zip_filepath($vplid, $zipname) {
        global $CFG;
        $zipname = basename($zipname);
        return $CFG->dataroot . '/temp/vpl_zip/' . $vplid . '_' . $zipname;
    }

    /**
     * Creates a ZIP file with the given data.
     *
     * @param int $vplid ID of the VPL activity.
     * @param string $zipname Name of the ZIP file to create.
     * @param string $zipdata Content of the ZIP file.
     */
    public static function create_zip_file($vplid, $zipname, $zipdata) {
        $filename = self::get_zip_filepath($vplid, $zipname);
        vpl_fwrite($filename, $zipdata);
    }

    /**
     * Preprocesses ZIP file, loading processesed files into $simil array
     *
     * @param array $simil Input/output array that saves the new files found to process
     * @param string $zipname Zip file name
     * @param string $zipdata Zip file content
     * @param object $vpl Current VPL activity
     * @param array $selectedfiles Files to process
     * @param bool $allfiles If true process all files.
     * @param bool $joinedfiles If true process files as a joined file
     * @param object $spb Progress bar to show Zip file load
     * @return void
     */
    public static function zip(&$simil, $zipname, $zipdata, $vpl, $selectedfiles, $allfiles, $joinedfiles, $spb) {
        $ext = strtoupper(pathinfo($zipname, PATHINFO_EXTENSION));
        if ($ext != 'ZIP') {
            throw new moodle_exception('wrongzipfilename');
        }
        $vplid = $vpl->get_instance()->id;
        self::create_zip_file($vplid, $zipname, $zipdata);
        $zip = new ZipArchive();
        $zipfilename = self::get_zip_filepath($vplid, $zipname);
        $spb->set_value(get_string('unzipping', VPL));
        if ($zip->open($zipfilename) === true) {
            $spb->set_max($zip->numFiles);
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $spb->set_value($i + 1);
                $filename = $zip->getNameIndex($i);
                if ($filename == false) {
                    break;
                }
                $data = $zip->getFromIndex($i);
                if ($data) {
                    // TODO remove if no GAP file.
                    vpl_file_from_zipfile::process_gap_userfile($filename);
                    if (! isset($selectedfiles[basename($filename)]) && ! $allfiles) {
                        continue;
                    }
                    $sim = vpl_similarity_factory::get($filename);
                    if ($sim) {
                        $from = new vpl_file_from_zipfile($filename, $vplid, $zipname);
                        $sim->init($data, $from);
                        if ($sim->get_size() > self::MINTOKENS) {
                            $simil[] = $sim;
                        }
                    }
                }
            }
        }
        $spb->set_value($zip->numFiles);
    }
}
