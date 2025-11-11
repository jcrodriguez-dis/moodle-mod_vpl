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
namespace mod_vpl\similarity;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/vpl/vpl_submission.class.php');

/**
 * Utility class to get list of preprocessed files
 */
class preprocess {
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
            $sim = similarity_factory::get($filename);
            if ($sim) {
                $data = $fgm->getFileData($filename);
                if ($joinedfiles) {
                    if (! $simjf) {
                        $simjf = $sim;
                    }
                    $joinedfilesdata .= $data . "\n";
                } else {
                    if ($vpl) {
                        $from = new file_from_activity($filename, $vpl, $subinstance);
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
                $from = new file_from_activity($filename, $vpl, $subinstance);
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
                $submission = new \mod_vpl_submission($vpl, $subinstance);
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
            $sim = similarity_factory::get($filename);
            if ($sim) {
                $data = $reqf->getFileData($filename);
                $sim->init($data, null);
                $toremove[$filename] = $sim;
            }
        }
        $submission = new \mod_vpl_submission($vpl, $subinstance);
        $subf = $submission->get_submitted_fgm();
        $filelist = $subf->getFileList();
        foreach ($filelist as $filename) {
            $sim = similarity_factory::get($filename);
            if ($sim) {
                $data = $subf->getFileData($filename);
                $from = new file_from_activity($filename, $vpl, $subinstance);
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
            throw new \moodle_exception('wrongzipfilename');
        }
        $vplid = $vpl->get_instance()->id;
        self::create_zip_file($vplid, $zipname, $zipdata);
        $zip = new \ZipArchive();
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
                    file_from_zip::process_gap_userfile($filename);
                    if (! isset($selectedfiles[basename($filename)]) && ! $allfiles) {
                        continue;
                    }
                    $sim = similarity_factory::get($filename);
                    if ($sim) {
                        $from = new file_from_zip($filename, $vplid, $zipname);
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
