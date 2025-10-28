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
 * Compilation and Execution of submission class definition
 *
 * @package mod_vpl
 * @copyright 2013 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../lib/gradelib.php');
require_once(dirname(__FILE__) . '/vpl_submission.class.php');
require_once(dirname(__FILE__) . '/jail/jailserver_manager.class.php');
require_once(dirname(__FILE__) . '/jail/running_processes.class.php');

/**
 * Class mod_vpl_submission_CE
 *
 * This class is used to manage the compilation and execution of submissions in VPL.
 * It extends the base mod_vpl_submission class and provides additional functionality
 * specific to compilation and execution tasks.
 */
class mod_vpl_submission_CE extends mod_vpl_submission {
    /**
     * Associative array for detecting the programming language based on a file's extension
     * @var array
     */
    private static $languageext = [
        'ada' => 'ada',
        'adb' => 'ada',
        'ads' => 'ada',
        'all' => 'all',
        'asm' => 'asm',
        'c' => 'c',
        'cc' => 'cpp',
        'cpp' => 'cpp',
        'C' => 'cpp',
        'c++' => 'cpp',
        'cbl' => 'cobol',
        'cob' => 'cobol',
        'clj' => 'clojure',
        'cs' => 'csharp',
        'd' => 'd',
        'erl' => 'erlang',
        'go' => 'go',
        'groovy' => 'groovy',
        'java' => 'java',
        'jl' => 'julia',
        'js' => 'javascript',
        'scala' => 'scala',
        'sql' => 'sql',
        'scm' => 'scheme',
        's' => 'mips',
        'kt' => 'kotlin',
        'lisp' => 'lisp',
        'lsp' => 'lisp',
        'lua' => 'lua',
        'sh' => 'shell',
        'pas' => 'pascal',
        'p' => 'pascal',
        'f77' => 'fortran',
        'f90' => 'fortran',
        'f' => 'fortran',
        'for' => 'fortran',
        'fs' => 'fsharp',
        'pl' => 'prolog',
        'pro' => 'prolog',
        'htm' => 'html',
        'html' => 'html',
        'hs' => 'haskell',
        'm' => 'matlab',
        'mzn' => 'minizinc',
        'perl' => 'perl',
        'prl' => 'perl',
        'php' => 'php',
        'py' => 'python',
        'psc' => 'pseint',
        'v' => 'verilog',
        'vb' => 'visualbasic',
        'vh' => 'verilog',
        'vhd' => 'vhdl',
        'vhdl' => 'vhdl',
        'r' => 'r',
        'R' => 'r',
        'rb' => 'ruby',
        'rs' => 'rust',
        'ruby' => 'ruby',
        'ts' => 'typescript',
    ];

    /**
     * Associative array for detecting the build system based on the configuration file
     * @var array
     */
    private static $languageconfig = [
        'Makefile' => 'make',
        'makefile' => 'make',
    ];
    /*
        Future config files.
        CMakeLists.txt => cmake
        build.ninja => ninja
        build.xml => ant
        build.gradle => gradle
        pom.xml => maven
    */

    /**
     * Associative array for detecting the action name based on the script name
     * @var array
     */
    private static $scriptname = [
        'vpl_run.sh' => 'run',
        'vpl_debug.sh' => 'debug',
        'vpl_evaluate.sh' => 'evaluate',
        'vpl_test_evaluate.sh' => 'test_evaluate',
        'vpl_evaluate.cases' => 'evaluate',
    ];

    /**
     * Identify the run execution type.
     */
    const TRUN = 0;
    /**
     * Identify the debug execution type.
     */
    const TDEBUG = 1;
    /**
     * Identify the evaluation execution type.
     */
    const TEVALUATE = 2;
    /**
     * Identify the test evaluation execution type.
     */
    const TTESTEVALUATE = 3;
    /**
     * Directory name for evaluation tests.
     * This directory must be in the execution files.
     * It is used to store the evaluation tests of evaluation.
     */
    const DIR_TEST_EVALUATION = 'vpl_evaluation_tests';

    /**
     * Mark to set the running mode to text.
     * This mark must appear in the first part of any code file.
     */
    const RUN_TEXT_MODE_MARK = '@vpl_run_text_mode';
    /**
     * Mark to set the running mode to GUI.
     * This mark must appear in the first part of any code file.
     */
    const RUN_GUI_MODE_MARK = '@vpl_run_gui_mode';
    /**
     * Mark to set the running mode to webapp.
     * This mark must appear in the first part of any code file.
     */
    const RUN_WEBAPP_MODE_MARK = '@vpl_run_webapp_mode';
    /**
     * Mark to set the running mode to text in GUI mode.
     * This mark must appear in the first part of any code file.
     */
    const RUN_TEXTINGUI_MODE_MARK = '@vpl_run_textingui_mode';

    /**
     * Associative array for getting the execution type based on the script name.
     * @var array
     */
    private static $script2type = [
        'vpl_run.sh' => self::TRUN,
        'vpl_debug.sh' => self::TDEBUG,
        'vpl_evaluate.sh' => self::TEVALUATE,
        'vpl_evaluate.cases' => self::TEVALUATE,
        'vpl_test_evaluate.sh' => self::TTESTEVALUATE,
    ];
    /**
     * Associative array for getting the script to use based on the execution type.
     * @var array
     */
    private static $type2script = [
        self::TRUN => 'vpl_run.sh',
        self::TDEBUG => 'vpl_debug.sh',
        self::TEVALUATE => 'vpl_evaluate.sh',
        self::TTESTEVALUATE => 'vpl_test_evaluate.sh',
    ];

    /**
     * Return the name of the programming language used.
     *
     * The name of the programming language is based on files extensions.
     *
     * @param array $filelist of submitted files
     * @return string programming language name
     */
    public static function get_pln($filelist) {
        foreach ($filelist as $checkfilename) {
            if (isset(self::$languageconfig[$checkfilename])) {
                return self::$languageconfig[$checkfilename];
            }
        }
        foreach ($filelist as $checkfilename) {
            $ext = pathinfo($checkfilename, PATHINFO_EXTENSION);
            if (isset(self::$languageext[$ext])) {
                return self::$languageext[$ext];
            }
        }
        return 'default';
    }

    /**
     * Return the script to manage the action and detected language
     *
     * @param string $action 'run', 'debug', 'evaluate' or 'test_evaluate'
     * @param string $detectedpln Programming Language Name
     * @param object $data Execution data with script field if used
     *
     * @return string script contents
     */
    public static function get_script($action, $detectedpln, $data) {
        $basepath = vpl_get_scripts_dir() . '/';
        $field = $action . 'script';
        if (isset($data->$field) &&  $data->$field > '') {
            $pln = $data->$field;
        } else {
            $pln = $detectedpln;
        }
        $filename = $basepath . $pln . '_' . $action . '.sh';
        if (file_exists($filename)) {
            return file_get_contents($filename);
        } else {
            $filename = $basepath . 'default' . '_' . $action . '.sh';
            if (file_exists($filename)) {
                return file_get_contents($filename);
            } else {
                return file_get_contents($basepath . 'default.sh');
            }
        }
    }

    /**
     * Return the scripts to manage the action and detected language
     *
     * @param object $vpl VPL activity
     * @param object $data Execution data with execution type and script fields if used
     *
     * @return array key=>filename value =>filedata
     */
    public static function get_scripts($vpl, $data) {
        $detectedpln = $data->pln;
        $type = $data->type;
        $ret = [];
        $ret['vpl_run.sh'] = self::get_script('run', $detectedpln, $data);
        if ($type == 1) {
            $ret['vpl_debug.sh'] = self::get_script('debug', $detectedpln, $data);
        }
        if ($type >= 2) {
            $ret['vpl_evaluate.sh'] = self::get_script('evaluate', $detectedpln, $data);
            $dirpath = vpl_get_scripts_dir();
            $ret['vpl_evaluate.cpp'] = file_get_contents($dirpath . '/vpl_evaluate.cpp');
        }
        if ($type == 3) {
            $ret['vpl_test_evaluate.sh'] = self::get_script('test_evaluate', $detectedpln, $data);
        }
        if ($detectedpln == 'all' && $vpl->has_capability(VPL_MANAGE_CAPABILITY)) { // Test all scripts.
            $dirpath = vpl_get_scripts_dir();
            if (file_exists($dirpath)) {
                $dirlst = opendir($dirpath);
                while (false !== ($filename = readdir($dirlst))) {
                    if ($filename == "." || $filename == "..") {
                        continue;
                    }
                    if (
                        substr($filename, - 7) == '_run.sh' ||
                        substr($filename, - 9) == '_hello.sh' ||
                        substr($filename, - 9) == '_debug.sh'
                    ) {
                        $ret[$filename] = file_get_contents($dirpath . '/' . $filename);
                    }
                }
                closedir($dirlst);
            }
        }
        return $ret;
    }

    /**
     * Recopile base execution data to be send to the jail
     *
     * @param mod_vpl $vpl VPl instance to process.
     * @param int $type Execution type code
     * @param array $vplused List of based on instances, usefull to avoid infinite recursion.
     * @return object with files, limits, interactive and other info
     */
    public static function prepare_execution_base($vpl, $type, &$vplused = []) {
        $plugincfg = get_config('mod_vpl');
        $vplinstance = $vpl->get_instance();
        if (isset($vplused[$vplinstance->id])) {
            throw new moodle_exception('error:recursivedefinition', 'mod_vpl');
        }
        $finalchecks = count($vplused) == 0;
        $vplused[$vplinstance->id] = true;
        // Load basedon files if needed.
        if ($vplinstance->basedon) {
            $basedon = new mod_vpl(null, $vplinstance->basedon);
            $data = self::prepare_execution_base($basedon, $type, $vplused);
        } else {
            $data = new stdClass();
            $data->files = [];
            $data->filestodelete = [];
            $data->maxtime = (int) $plugincfg->defaultexetime;
            $data->maxfilesize = (int) $plugincfg->defaultexefilesize;
            $data->maxmemory = (int) $plugincfg->defaultexememory;
            $data->maxprocesses = (int) $plugincfg->defaultexeprocesses;
            $data->jailservers = '';
            $data->runscript = $vplinstance->runscript;
            $data->debugscript = $vplinstance->debugscript;
            $data->evaluator = $vplinstance->evaluator;
            $data->run_mode = $vplinstance->run_mode;
            $data->evaluation_mode = $vplinstance->evaluation_mode;
            $data->execute = self::$type2script[$type];
        }
        // Execution files.
        $sfg = $vpl->get_execution_fgm();
        $list = $sfg->getFileList();
        // Locate testdir files.
        $regexptestfile = '/^' . self::DIR_TEST_EVALUATION . '\//';
        $testdir = [];
        foreach ($list as $filename) {
            $testdir[$filename] = preg_match($regexptestfile, $filename) == 1;
        }
        foreach ($list as $filename) {
            // Skip unneeded test files.
            if ($type < self::TTESTEVALUATE && $testdir[$filename]) {
                continue;
            }
            // Skip unneeded script.
            if (isset(self::$script2type[$filename]) && self::$script2type[$filename] > $type) {
                continue;
            }
            // Concatene or replace based-on files.
            if (isset($data->files[$filename]) && isset(self::$scriptname[$filename])) {
                $data->files[$filename] .= "\n" . $sfg->getFileData($filename);
            } else {
                $data->files[$filename] = $sfg->getFileData($filename);
            }
            if (! $testdir[$filename]) {
                $data->filestodelete[$filename] = 1;
            }
        }
        $deletelist = $sfg->getFileKeepList();
        foreach ($deletelist as $filename) {
            unset($data->filestodelete[$filename]);
        }
        if ($vplinstance->maxexetime) {
            $data->maxtime = (int) $vplinstance->maxexetime;
        }
        if ($vplinstance->maxexememory) {
            $data->maxmemory = (int) $vplinstance->maxexememory;
        }
        if ($vplinstance->maxexefilesize) {
            $data->maxfilesize = (int) $vplinstance->maxexefilesize;
        }
        if ($vplinstance->maxexeprocesses) {
            $data->maxprocesses = (int) $vplinstance->maxexeprocesses;
        }
        // Add jailserver list.
        if (! empty($vplinstance->jailservers)) {
            $data->jailservers = $vplinstance->jailservers . "\n" . $data->jailservers;
        }
        if (! empty($vplinstance->runscript)) {
            $data->runscript = $vplinstance->runscript;
        }
        if (! empty($vplinstance->debugscript)) {
            $data->debugscript = $vplinstance->debugscript;
        }
        if (! empty($vplinstance->evaluator)) {
            $data->evaluator = $vplinstance->evaluator;
        }
        if (! empty($vplinstance->run_mode)) {
            $data->run_mode = $vplinstance->run_mode;
        }
        if (! empty($vplinstance->evaluation_mode)) {
            $data->evaluation_mode = $vplinstance->evaluation_mode;
        }
        if ($finalchecks) { // Final checks.
            $data->activityid = $vplinstance->id;
            $data->type = $type;
            // Limit resources to minimum.
            $data->maxtime = min($data->maxtime, (int) $plugincfg->maxexetime);
            $data->maxfilesize = min($data->maxfilesize, (int) $plugincfg->maxexefilesize);
            $data->maxmemory = min($data->maxmemory, (int) $plugincfg->maxexememory);
            $data->maxprocesses = min($data->maxprocesses, (int) $plugincfg->maxexeprocesses);
        }
        return $data;
    }

    /**
     * Adds to $data submission information data to be send to the jail
     *
     * @param object $data Data to send to the jail.
     * @return object $data updated.
     */
    public function prepare_execution_submission($data) {
        // Submitted files.
        $sfg = $this->get_submitted_fgm();
        $submittedlist = $sfg->getFileList();
        // Add $submittedlist but removing the files overwrited by teacher's one.
        foreach ($submittedlist as $filename) {
            if (! isset($data->files[$filename])) {
                $data->files[$filename] = $sfg->getFileData($filename);
            }
        }
        // Get programming language.
        $data->pln = self::get_pln($submittedlist);
        // Adapts Java memory limit.
        if ($data->pln == 'java') {
            $javaoffset = 128 * 1024 * 1024; // Checked at Ubuntu 12.04 64 and CentOS 6.5 64.
            if ($data->maxmemory + $javaoffset > $data->maxmemory) {
                $data->maxmemory += $javaoffset;
            } else {
                $data->maxmemory = (int) PHP_INT_MAX;
            }
        }
        $data->userid = $this->get_instance()->userid;
        $data->groupid = $this->get_instance()->groupid;
        $data->submittedlist = $submittedlist;
        return $data;
    }

    /**
     * Adds to $data submission information data to be send to the jail
     *
     * @param object $data Data to send to the jail.
     * @return object $data object updated.
     */
    public static function prepare_execution_evaluation_tests($data) {
        // Changes resources limits to maximum.
        $plugincfg = get_config('mod_vpl');
        $data->maxtime = (int) $plugincfg->maxexetime;
        $data->maxfilesize = (int) $plugincfg->maxexefilesize;
        $data->maxprocesses = (int) $plugincfg->maxexeprocesses;
        $data->maxmemory = (int) $plugincfg->maxexememory;
        // Selects test files.
        $vpl = new mod_vpl(false, $data->activityid);
        $efg = $vpl->get_execution_fgm();
        $usevariations = $vpl->get_instance()->usevariations;
        $regexpfilestruct = '/^' . self::DIR_TEST_EVALUATION;
        if ($usevariations) {
            $regexpfilestruct .= '\/([^\/]*)\/([^\/]*)\/(.*)$/';
        } else {
            $regexpfilestruct .= '\/([^\/]*)\/(.*)$/';
        }
        $executionfiles = $efg->getFileList();
        $testfilelist = [];
        $casefilelist = [];
        foreach ($executionfiles as $filename) {
            $matches = [];
            if (preg_match($regexpfilestruct, $filename, $matches)) {
                $testfilelist[] = $filename;
                if ($usevariations) {
                    $casedir = $matches[1] . '/' . $matches[2];
                    $casefilename = $matches[3];
                } else {
                    $casedir = $matches[1];
                    $casefilename = $matches[2];
                }
                if (! isset($casefilelist[$casedir])) {
                    $casefilelist[$casedir] = [];
                }
                $casefilelist[$casedir][] = $casefilename;
            }
        }
        $data->submittedlist = [];
        $data->pln = self::get_pln($testfilelist);
        // Write local environment for each solution.
        foreach ($casefilelist as $casedir => $casefilenames) {
            $localenvironment = self::get_bash_export_for_subfiles($casefilenames);
            $localenvironmentfile = self::DIR_TEST_EVALUATION . '/' . $casedir . '/.localenvironment.sh';
            $data->files[$localenvironmentfile] = $localenvironment;
        }
        return $data;
    }

    /**
     * Get the run mode from the data object or from the files.
     *
     * @param object $data Data object containing run mode information.
     * @return string Run mode code.
     */
    public static function get_run_mode($data) {
        if (isset($data->pln) && $data->pln == 'all') {
            // If the programming language is 'all', return the default run mode.
            return '0';
        }
        if (! empty($data->run_mode) && $data->run_mode > '1') {
            return $data->run_mode;
        }
        $modes = [
            '2' => self::RUN_TEXT_MODE_MARK,
            '3' => self::RUN_GUI_MODE_MARK,
            '4' => self::RUN_WEBAPP_MODE_MARK,
            '5' => self::RUN_TEXTINGUI_MODE_MARK,
        ];
        $chunklentgh = 2 * 1024;
        if (! isset($data->submittedlist)) {
            $data->submittedlist = [];
        }
        // Search for marks in execution files.
        foreach ($data->files as $filename => $filedata) {
            if (in_array($filename, $data->submittedlist)) {
                continue; // Skip files submitted.
            }
            $startingchunk = substr($filedata, 0, $chunklentgh);
            foreach ($modes as $mode => $mark) {
                if (stripos($startingchunk, $mark) !== false) {
                    return $mode;
                }
            }
        }
        // Search for marks in user submitted files in order.
        foreach ($data->submittedlist as $filename) {
            if (!isset($data->files[$filename])) {
                continue; // Skip files not found.
            }
            $startingchunk = substr($data->files[$filename], 0, $chunklentgh);
            foreach ($modes as $mode => $mark) {
                if (stripos($startingchunk, $mark) !== false) {
                    return $mode;
                }
            }
        }
        return '0';
    }

    /**
     * Get the anrry of environment variables to be used in the jail.
     *
     * @param mod_vpl $vpl VPL activity instance.
     * @param object $data Data object containing execution information.
     * @return array Associative array of environment variables.
     */
    public static function get_environment_variables($vpl, $data) {
        global $DB;
        $variables = [];
        $variables['VPL_LANG'] = vpl_get_lang();
        $variables['MOODLE_COURSE_ID'] = $vpl->get_course()->id;
        $variables['MOODLE_ACTIVITY_ID'] = $vpl->get_course_module()->id;
        if (isset($data->userid)) {
            $userid = $data->userid;
            $variables['MOODLE_USER_ID'] = $userid;
            if ($user = $DB->get_record('user', ['id' => $userid])) {
                $variables['MOODLE_USER_NAME'] = $vpl->fullname($user, false);
                $variables['MOODLE_USER_EMAIL'] = $user->email;
            }
        }
        if ($vpl->is_group_activity() && isset($data->groupid)) {
            $groupid = $data->groupid;
            if ($group = $DB->get_record('groups', ['id' => $groupid])) {
                $variables['MOODLE_GROUP_ID'] = $groupid;
                $variables['MOODLE_GROUP_NAME'] = $group->name;
            }
        }
        if ($data->type == self::TRUN) {
            $variables['VPL_RUN_MODE'] = self::get_run_mode($data);
        }
        if ($data->type >= self::TEVALUATE) { // If evaluation then add information.
            $variables['VPL_EVALUATION_MODE'] = !empty($data->evaluation_mode) ? $data->evaluation_mode : '0';
            $variables['VPL_MAXTIME'] = $data->maxtime;
            $variables['VPL_MAXMEMORY'] = $data->maxmemory;
            $variables['VPL_MAXFILESIZE'] = $data->maxfilesize;
            $variables['VPL_MAXPROCESSES'] = $data->maxprocesses;
            $gradesetting = $vpl->get_grade_info();
            if ($gradesetting !== false) {
                $variables['VPL_GRADEMIN'] = $gradesetting->grademin;
                $variables['VPL_GRADEMAX'] = $gradesetting->grademax;
            }
            $variables['VPL_PLN'] = $data->pln;
        }
        $variables['VPL_COMPILATIONFAILED'] = get_string('VPL_COMPILATIONFAILED', VPL);
        // Add identifications of variations if exist.
        $variables['VPL_VARIATION'] = '-';
        $vplinstance = $vpl->get_instance();
        $usevariations = $vplinstance->usevariations;
        if ($usevariations) {
            $variations = $DB->get_records(VPL_VARIATIONS, ['vpl' => $vplinstance->id]);
            $usevariations = count($variations) > 0;
        }
        if ($usevariations) {
            if ($data->type < self::TTESTEVALUATE) {
                $varids = $vpl->get_variation_identification($data->userid);
                foreach ($varids as $id => $varid) {
                    $variables['VPL_VARIATION' . $id] = $varid;
                }
                $variables['VPL_VARIATION'] = $varid;
            } else {
                $varidentificacions = [];
                foreach ($variations as $variation) {
                    $varidentificacions[] = $variation->identification;
                }
                $variables['VPL_VARIATIONS'] = $varidentificacions;
            }
        }
        return $variables;
    }
    /**
     * Adds to $data execution info to be send to the jail.
     *
     * @param object $data Data to send to the jail requiere: activityid, type
     * @return object $data updated.
     */
    public static function prepare_execution_info($data) {
        global $DB;
        $vpl = new mod_vpl(false, $data->activityid);
        // Prepare vpl_environment.sh content.
        $enviromentcontent = "#!/bin/bash\n";
        if (isset($data->submittedlist)) {
            $enviromentcontent .= self::get_bash_export_for_subfiles($data->submittedlist);
        }
        $varsenv = self::get_environment_variables($vpl, $data);
        $premadescripts = self::get_scripts($vpl, $data);
        foreach ($premadescripts as $filename => $filedata) {
            if (isset($data->files[$filename]) && trim($data->files[$filename]) > '') { // Use custom script.
                if (vpl_fileextension($filename) == 'sh') {
                    $filecontent = $data->files[$filename];
                    if (substr($filecontent, 0, 2) != '#!') { // Fixes script adding bash if no shebang.
                        $data->files[$filename] = "#!/bin/bash\n" . $filecontent;
                    }
                }
            } else {
                $data->files[$filename] = $filedata;
                $data->filestodelete[$filename] = 1;
            }
        }
        if ($data->type == self::TTESTEVALUATE) {
            $varsenv['VPL_EVALUATION_SCRIPT'] = "vpl_evaluate.sh";
        }
        // If evaluating and evaluator => merge evaluator files and strings.
        if ($data->type >= self::TEVALUATE && !empty($data->evaluator)) {
            $evaluator = \mod_vpl\plugininfo\vplevaluator::get_evaluator($data->evaluator);
            foreach ($evaluator->get_execution_files() as $filename => $filedata) {
                $data->files[$filename] = $filedata;
                $data->filestodelete[$filename] = 1;
            }
            foreach ($evaluator->get_files_to_keep_when_running() as $filename) {
                unset($data->filestodelete[$filename]);
            }
            foreach ($evaluator->get_strings() as $varname => $value) {
                $varsenv['VPLEVALUATOR_STR_' . $varname] = $value;
            }
            if ($data->type == self::TTESTEVALUATE) {
                $varsenv['VPL_EVALUATION_SCRIPT'] = $evaluator->get_execution_script();
            } else {
                $data->execute = $evaluator->get_execution_script();
            }
        }
        // Add more environment variables to the environment script.
        foreach ($varsenv as $name => $value) {
            $enviromentcontent .= vpl_bash_export($name, $value);
        }
        $enviromentcontent .= <<<'SETLANG'
        for NEWLANG in $VPL_LANG en_US.UTF-8 C.utf8 POSIX C
        do
            export LC_ALL=$NEWLANG 2> .vpl_set_locale_error
            if [ -s .vpl_set_locale_error ]; then
                rm .vpl_set_locale_error
                continue
            else
                break
            fi
        done

        SETLANG;
        // Add script file with VPL environment information and set LC_ALL.
        $data->files['vpl_environment.sh'] = $enviromentcontent;
        // Add common script.
        $data->files['common_script.sh'] = file_get_contents(vpl_get_scripts_dir() . '/common_script.sh');
        // Add new script for evaluation mode test_in_gui if needed.
        if (isset($varsenv['VPL_EVALUATION_MODE']) &&  $varsenv['VPL_EVALUATION_MODE'] == '2') {
            $filename = vpl_get_scripts_dir() . '/default_evaluate_textingui.sh';
            $data->files['default_evaluate_textingui.sh'] = file_get_contents($filename);
        }
        return $data;
    }

    /**
     * Return environment variables for VPL_SUBFILES.
     *
     * @param array $filelist
     * @return string bash code to generate VPL_SUBFILE vars.
     */
    public static function get_bash_export_for_subfiles($filelist) {
        $bashcode = '';
        $filenames = '';
        $num = 0;
        foreach ($filelist as $filename) {
            $filenames .= $filename . "\n";
            $bashcode .= vpl_bash_export('VPL_SUBFILE' . $num, $filename);
            $num++;
        }
        $bashcode .= 'export VPL_SUBFILES="' . $filenames . "\"\n";
        return $bashcode;
    }
    /**
     * Recopile execution data to be send to the jail
     *
     * @param int $type 0 => run, 1 => debug, 2 => evaluate, 3 => test_evaluate
     * @return object with files, limits, interactive and other info
     */
    public function prepare_execution($type) {
        $data = self::prepare_execution_base($this->vpl, $type);
        if ($type < 3) {
            $data = $this->prepare_execution_submission($data);
        } else {
            self::prepare_execution_evaluation_tests($data);
        }
        $data = self::prepare_execution_info($data);
        return $data;
    }

    /**
     * Wrte a log file with the request and response of a jailserver action.
     *
     * @param string $action Action to be executed
     * @param string $request Request to be send
     * @param string $response Response from the jail server
     */
    public static function log_action($action, $request, $response) {
        global $CFG;
        $id = vpl_jailserver_manager::get_jsonrpcid();
        $filename = $CFG->dataroot . "/temp/vpl_{$action}_{$id}_log.json";
        $content = $request . "\n" . json_encode($response) . "\n";
        file_put_contents($filename, $content);
    }

    /**
     * Send a request to the jail server and return the response.
     *
     * @param object $vpl VPL instance
     * @param string $server Jail server
     * @param string $action Action to be executed
     * @param object $data Data to be sent
     * @return object Response from the jail server
     */
    public static function jailaction($vpl, $server, $action, $data) {
        $plugin = new stdClass();
        require(dirname(__FILE__) . '/version.php');
        $pluginversion = $plugin->version;
        $data->pluginversion = $pluginversion;
        $request = vpl_jailserver_manager::get_action_request($action, $data);
        $error = '';
        $response = vpl_jailserver_manager::get_response($server, $request, $error);
        // For logging you can call here log_action method.
        if ($response === false) {
            $manager = $vpl->has_capability(VPL_MANAGE_CAPABILITY);
            if ($manager) {
                throw new Exception(get_string('serverexecutionerror', VPL) . "\n" . $error);
            }
            throw new Exception(get_string('serverexecutionerror', VPL));
        }
        return $response;
    }
    /**
     * Request an action of run, debug, evaluate and test_evaluate on VPL instance and submission.
     *
     * @param object $data Data to be sent to the jail server
     * @param int $maxmemory Maximum memory to be used by the jail server
     * @param string $localservers List of local servers
     * @param string $server Jail server selected
     * @return object Response from the jail server with task information for the client.
     */
    public function jailrequestaction($data, $maxmemory, $localservers, &$server) {
        $error = '';
        $server = vpl_jailserver_manager::get_server($maxmemory, $localservers, $error);
        if ($server == '') {
            $manager = $this->vpl->has_capability(VPL_MANAGE_CAPABILITY);
            $men = get_string('nojailavailable', VPL);
            if ($manager) {
                $men .= ": " . $error;
            }
            throw new Exception($men);
        }
        return self::jailaction($this->vpl, $server, 'request', $data);
    }
    /**
     * Request an action of run, debug, evaluate and test_evaluate on VPL instance and submission.
     *
     * @param string $action Action to be executed
     * @param object|false $processinfo Process information, if false then get from vpl_running_processes.
     * @return object Response from the jail server with task information for the client.
     */
    public function jailreaction($action, $processinfo = false) {
        if ($processinfo === false) {
            $vplid = $this->vpl->get_instance()->id;
            $processinfo = vpl_running_processes::get_run($this->get_instance()->userid, $vplid);
        }
        if ($processinfo === false) {
            return;
        }
        $server = $processinfo->server;
        $data = new stdClass();
        $data->adminticket = $processinfo->adminticket;
        return self::jailaction($this->vpl, $server, $action, $data);
    }

    /**
     * Adapt files to send binary as base64.
     * Modify atributes of $data object: files and fileencoding, modify filestodelete (must exists array)
     *
     * @param object $data Set atribute files and fileencoding, modify filestodelete (must exists array)
     * @param array $files Array of files to adapt key=>filename value=>file data, remove values
     */
    public static function adaptbinaryfiles($data, &$files) {
        $fileencoding = [];
        $encodefiles = [];
        if (empty($data->filestodelete)) {
            $data->filestodelete = [];
        }
        foreach ($files as $filename => $filedata) {
            if (vpl_is_binary($filename)) {
                $encodefiles[$filename . '.b64'] = base64_encode($filedata);
                $fileencoding[$filename . '.b64'] = 1;
                $data->filestodelete[$filename . '.b64'] = 1;
            } else {
                $fileencoding[$filename] = 0;
                $encodefiles[$filename] = $filedata;
            }
            $files[$filename] = '';
        }
        $data->files = $encodefiles;
        $data->fileencoding = $fileencoding;
    }

    /**
     * Request an action of run, debug, evaluate and test_evaluate a VPL instance and submission.
     *
     * @param int $type (0=run, 1=debug, evaluate=2, test_evaluate=3)
     * @param array $options Options to be used in the execution.
     * @return object Response from the jail server with task information for the client.
     */
    public function run($type, $options = []) {
        // Stop current task if one.
        global $DB;
        $this->cancelprocess();
        $options = (array) $options;
        $plugincfg = get_config('mod_vpl');
        $data = $this->prepare_execution($type);
        $data->interactive = $type < self::TEVALUATE ? 1 : 0;
        $data->lang = vpl_get_lang();
        $optionsvars = [
            'XGEOMETRY' => 'VPL_XGEOMETRY',
            'currentFileName' => 'VPL_CURRENTSUBFILE',
            'COMMANDARGS' => 'VPL_COMMANDARGS',
        ];
        $enviromentvars = '';
        foreach ($optionsvars as $option => $varname) {
            if (isset($options[$option])) {
                $enviromentvars .= vpl_bash_export($varname, $options[$option]);
            }
        }
        $data->files['vpl_environment.sh'] .= $enviromentvars;
        $localservers = $data->jailservers;
        $maxmemory = $data->maxmemory;
        // Remove jailservers field.
        unset($data->jailservers);
        self::adaptbinaryfiles($data, $data->files);
        $jailserver = '';
        $jailresponse = $this->jailrequestaction($data, $maxmemory, $localservers, $jailserver);
        $parsed = parse_url($jailserver);
        // Fix jail server port.
        $usinghttp = $parsed['scheme'] == 'http';
        $usinghttps = $parsed['scheme'] == 'https';
        if (! isset($parsed['port']) && $usinghttp) {
            $parsed['port'] = 80;
        }
        if (! isset($parsed['port']) && $usinghttps) {
            $parsed['port'] = 443;
        }
        if (! isset($jailresponse['port'])) { // Try to fix old jail servers that don't return port.
            $jailresponse['port'] = $parsed['port'];
        }
        if (! isset($jailresponse['secureport'])) { // Try to fix old jail servers that don't return port.
            $jailresponse['secureport'] = $parsed['port'];
        }
        $response = new stdClass();
        $response->server = $parsed['host'];
        $response->monitorPath = $jailresponse['monitorticket'] . '/monitor';
        $response->executionPath = $jailresponse['executionticket'] . '/execute';
        $response->port = $usinghttp ? $parsed['port'] : $jailresponse['port'];
        $response->securePort = $usinghttps ? $parsed['port'] : $jailresponse['secureport'];
        $response->wsProtocol = $plugincfg->websocket_protocol;
        $response->VNCpassword = substr($jailresponse['executionticket'], 0, 8);
        $instance = $this->get_instance();
        $process = new stdClass();
        $process->userid = $instance->userid;
        $process->vpl = $instance->vpl; // The vplid.
        $process->adminticket = $jailresponse['adminticket'];
        $process->server = $jailserver;
        $process->type = $type;
        $response->processid = vpl_running_processes::set($process);
        if ($type < 2) {
            if ($type == 0) {
                $instance->run_count++;
            } else {
                $instance->debug_count++;
            }
            $DB->update_record(VPL_SUBMISSIONS, $instance);
        }
        return $response;
    }

    /**
     * Updates files in running task
     *
     * @param mod_vpl $vpl VPL instance
     * @param int $userid
     * @param int $processid
     * @param array $files internal format
     * @param array $filestodelete List of files to delete in the running task
     * @return boolean True if updated
     */
    public static function update($vpl, $userid, $processid, $files, $filestodelete = []) {
        $data = new stdClass();
        $data->files = $files;
        $vplid = $vpl->get_instance()->id;
        $processinfo = vpl_running_processes::get_by_id($vplid, $userid, $processid);
        if ($processinfo == false) { // No process => no update.
            return false;
        }
        $server = $processinfo->server;
        $data = new stdClass();
        $data->filestodelete = [];
        foreach ($filestodelete as $filename) {
            $data->filestodelete[$filename] = 1;
        }
        self::adaptbinaryfiles($data, $files);
        $data->adminticket = $processinfo->adminticket;
        try {
            $response = self::jailaction($vpl, $server, 'update', $data);
        } catch (\Throwable $e) {
            return false;
        }
        return $response['update'] > 0;
    }
    /**
     * Retrieve the result of a process.
     *
     * @param int $processid Process ID to retrieve the evaluation result.
     * @throws Exception If no process found or if there is an error retrieving the result.
     * @return string The evaluation result.
     */
    public function retrieveresult($processid) {
        $vplid = $this->vpl->get_instance()->id;
        $processinfo = vpl_running_processes::get_by_id($vplid, $this->instance->userid, $processid);
        if ($processinfo == false) { // No process found.
            throw new Exception(get_string('serverexecutionerror', VPL) . ' No process found');
        }
        $response = $this->jailreaction('getresult', $processinfo);
        if ($response === false) {
            throw new Exception(get_string('serverexecutionerror', VPL) . ' getresult no repsonse');
        }
        if ($response['interactive'] == 0 && $processinfo->type == 2) {
            $this->saveCE($response);
            if ($response['executed'] > 0) {
                // If automatic grading.
                if ($this->vpl->get_instance()->automaticgrading) {
                    $data = new StdClass();
                    $data->grade = $this->proposedGrade($response['execution']);
                    $data->comments = $this->proposedComment($response['execution']);
                    $this->set_grade($data, true);
                } else if ($this->get_instance()->dategraded > 0 && $this->get_instance()->grader == 0) {
                    $this->remove_grade();
                }
            }
        }
        return $this->get_CE_for_editor($response);
    }
    /**
     * Check if the process is running.
     *
     * @return bool True if running, false otherwise.
     */
    public function isrunning() {
        try {
            $response = $this->jailreaction('running');
        } catch (\Throwable $e) {
            return false;
        }
        return $response['running'] > 0;
    }
    /**
     * Cancel running process
     * @param int $processid
     */
    public function cancelprocess(int $processid = -1) {
        $vplid = $this->vpl->get_instance()->id;
        $userid = $this->get_instance()->userid;
        if ($processid == -1) {
            $processinfo = vpl_running_processes::get_run($userid, $vplid);
        } else {
            $processinfo = vpl_running_processes::get_by_id($vplid, $userid, $processid);
        }
        if ($processinfo == false) { // No process to cancel.
            return;
        }
        try {
            $this->jailreaction('stop', $processinfo);
        } catch (\Throwable $e) {
            // No matter, consider that the process stopped.
            debugging("Process in execution server not stopped or not found", DEBUG_DEVELOPER);
        }
        vpl_running_processes::delete($userid, $vplid, $processinfo->adminticket);
    }
}
