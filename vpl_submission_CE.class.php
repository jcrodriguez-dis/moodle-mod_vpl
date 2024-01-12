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
require_once(dirname(__FILE__).'/../../lib/gradelib.php');
require_once(dirname(__FILE__).'/vpl_submission.class.php');
require_once(dirname(__FILE__).'/jail/jailserver_manager.class.php');
require_once(dirname(__FILE__).'/jail/running_processes.class.php');

class mod_vpl_submission_CE extends mod_vpl_submission {
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
    private static $scriptname = [
        'vpl_run.sh' => 'run',
        'vpl_debug.sh' => 'debug',
        'vpl_evaluate.sh' => 'evaluate',
        'vpl_test_evaluate.sh' => 'test_evaluate',
    ];

    const TRUN = 0;
    const TDEBUG = 1;
    const TEVALUATE = 2;
    const TTESTEVALUATE = 3;
    const DIR_TEST_EVALUATION = 'vpl_evaluation_tests';

    private static $scripttype = [
        'vpl_run.sh' => self::TRUN,
        'vpl_debug.sh' => self::TDEBUG,
        'vpl_evaluate.sh' => self::TEVALUATE,
        'vpl_evaluate.cases' => self::TEVALUATE,
        'vpl_test_evaluate.sh' => self::TTESTEVALUATE,
    ];
    private static $scriptlist = [
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
            $ext = pathinfo( $checkfilename, PATHINFO_EXTENSION );
            if (isset(self::$languageext[$ext])) {
                return self::$languageext[$ext];
            }
        }
        return 'default';
    }

    /**
     * Return the script to manage the action and detected language
     *
     * @param string $scripttype 'run', 'debug', 'evaluate' or 'test_evaluate'
     * @param string $detectedpln Programming Language Name
     * @param object $data Execution data with script field if used
     *
     * @return string script contents
     */
    public static function get_script($scripttype, $detectedpln, $data) {
        $basepath = vpl_get_scripts_dir() . '/';
        $field = $scripttype . 'script';
        if ( isset($data->$field) &&  $data->$field > '' ) {
            $pln = $data->$field;
        } else {
            $pln = $detectedpln;
        }
        $filename = $basepath . $pln . '_' . $scripttype . '.sh';
        if (file_exists($filename)) {
            return file_get_contents( $filename );
        } else {
            $filename = $basepath . 'default' . '_' . $scripttype . '.sh';
            if (file_exists( $filename )) {
                return file_get_contents( $filename );
            } else {
                return file_get_contents( $basepath . 'default.sh' );
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
            $ret['vpl_evaluate.cpp'] = file_get_contents( $dirpath . '/vpl_evaluate.cpp' );
        }
        if ($type == 3) {
            $ret['vpl_test_evaluate.sh'] = self::get_script('test_evaluate', $detectedpln, $data);
        }
        if ($detectedpln == 'all' && $vpl->has_capability(VPL_MANAGE_CAPABILITY)) { // Test all scripts.
            $dirpath = vpl_get_scripts_dir();
            if (file_exists( $dirpath )) {
                $dirlst = opendir( $dirpath );
                while ( false !== ($filename = readdir( $dirlst )) ) {
                    if ($filename == "." || $filename == "..") {
                        continue;
                    }
                    if (substr( $filename, - 7 ) == '_run.sh' ||
                        substr( $filename, - 9 ) == '_hello.sh' ||
                        substr( $filename, - 9 ) == '_debug.sh' ) {
                        $ret[$filename] = file_get_contents( $dirpath . '/' .$filename );
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
     * @param mod_vpl $vpl. VPl instance to process.
     * @param int $type. Execution type code
     * @param array $vplused=[]. List of based on instances, usefull to avoid infinite recursion.
     * @return object with files, limits, interactive and other info
     */
    public static function prepare_execution_base($vpl, $type, &$vplused = []) {
        $plugincfg = get_config('mod_vpl');
        $vplinstance = $vpl->get_instance();
        if (isset($vplused[$vplinstance->id])) {
            throw new moodle_exception('error:recursivedefinition', 'mod_vpl');
        }
        $firstcall = count($vplused) == 0;
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
            if (isset(self::$scripttype[$filename]) && self::$scripttype[$filename] > $type) {
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
        if ($vplinstance->jailservers > '') {
            $data->jailservers = $vplinstance->jailservers . "\n" . $data->jailservers;
        }
        if ( $vplinstance->runscript > '' ) {
            $data->runscript = $vplinstance->runscript;
        }
        if ( $vplinstance->debugscript > '' ) {
            $data->debugscript = $vplinstance->debugscript;
        }
        if ($firstcall) { // No recursive call.
            $data->activityid = $vplinstance->id;
            $data->type = $type;
            // Limit resources to minimum.
            $data->maxtime = min($data->maxtime, (int) $plugincfg->maxexetime );
            $data->maxfilesize = min($data->maxfilesize, (int) $plugincfg->maxexefilesize );
            $data->maxmemory = min($data->maxmemory, (int) $plugincfg->maxexememory );
            $data->maxprocesses = min($data->maxprocesses, (int) $plugincfg->maxexeprocesses );
        }
        return $data;
    }

    /**
     * Adds to $data submission information data to be send to the jail
     *
     * @param object $data. Data to send to the jail.
     * @return object $data updated.
     */
    public function prepare_execution_submission($data) {
        // Submitted files.
        $sfg = $this->get_submitted_fgm();
        $submittedlist = $sfg->getFileList();
        // Add $submittedlist but removing the files overwrited by teacher's one.
        foreach ($submittedlist as $filename) {
            if (! isset( $data->files[$filename] )) {
                $data->files[$filename] = $sfg->getFileData( $filename );
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
        $data->groupid = $this->get_instance()->userid;
        $data->submittedlist = $submittedlist;
        return $data;
    }

    /**
     * Adds to $data submission information data to be send to the jail
     *
     * @param object $data. Data to send to the jail.
     * @return object $data object updated.
     */
    public static function prepare_execution_evaluation_tests($data) {
        // Changes resources limits to maximum, but not memory.
        $plugincfg = get_config('mod_vpl');
        $data->maxtime = (int) $plugincfg->maxexetime;
        $data->maxfilesize = (int) $plugincfg->maxexefilesize;
        $data->maxprocesses = (int) $plugincfg->maxexeprocesses;
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
     * Adds to $data execution info to be send to the jail.
     *
     * @param object $data. Data to send to the jail requiere: activityid, type
     * @return object $data updated.
     */
    public static function prepare_execution_info($data) {
        global $DB;
        $vpl = new mod_vpl(false, $data->activityid);
        // Info send with script.
        $info = "#!/bin/bash\n";
        $info .= vpl_bash_export('VPL_LANG', vpl_get_lang());
        if (isset($data->userid)) {
            $userid = $data->userid;
            $info .= vpl_bash_export('MOODLE_USER_ID',  $userid);
            if ($user = $DB->get_record('user', ['id' => $userid])) {
                $info .= vpl_bash_export('MOODLE_USER_NAME', $vpl->fullname($user, false ) );
                $info .= vpl_bash_export('MOODLE_USER_EMAIL', $user->email );
            }
        }
        if ($vpl->is_group_activity() && isset($data->groupid)) {
            $groupid = $data->groupid;
            if ($group = $DB->get_record('groups', ['id' => $groupid])) {
                $info .= vpl_bash_export('MOODLE_GROUP_ID',  $groupid);
                $info .= vpl_bash_export('MOODLE_GROUP_NAME', $group->name);
            }
        }
        if ($data->type >= self::TEVALUATE) { // If evaluation then add information.
            $info .= vpl_bash_export('VPL_MAXTIME', $data->maxtime);
            $info .= vpl_bash_export('VPL_MAXMEMORY', $data->maxmemory);
            $info .= vpl_bash_export('VPL_MAXFILESIZE', $data->maxfilesize);
            $info .= vpl_bash_export('VPL_MAXPROCESSES', $data->maxprocesses);
            $gradesetting = $vpl->get_grade_info();
            if ($gradesetting !== false) {
                $info .= vpl_bash_export('VPL_GRADEMIN', $gradesetting->grademin);
                $info .= vpl_bash_export('VPL_GRADEMAX', $gradesetting->grademax);
            }
            $info .= vpl_bash_export('VPL_PLN', $data->pln);
        }
        $info .= vpl_bash_export('VPL_COMPILATIONFAILED', get_string('VPL_COMPILATIONFAILED', VPL));
        if (isset($data->submittedlist)) {
            $info .= self::get_bash_export_for_subfiles($data->submittedlist);
        }
        // Add identifications of variations if exist.
        $info .= vpl_bash_export('VPL_VARIATION', '-');
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
                    $info .= vpl_bash_export('VPL_VARIATION' . $id, $varid);
                }
                $info .= vpl_bash_export('VPL_VARIATION', $varid);
            } else {
                $varidentificacions = [];
                foreach ($variations as $variation) {
                    $varidentificacions[] = $variation->identification;
                }
                $info .= vpl_bash_export('VPL_VARIATIONS', $varidentificacions);
            }
        }
        $premadescripts = self::get_scripts($vpl, $data);
        for ($i = 0; $i <= $data->type; $i ++) {
            $filename = self::$scriptlist[$i];
            if (isset($data->files[$filename]) && trim($data->files[$filename]) > '') { // Use custom script.
                if (substr($data->files[$filename], 0, 2) != '#!') { // Fixes script adding bash if no shebang.
                    $data->files[$filename] = "#!/bin/bash\n" . $data->files[$filename];
                }
                unset($premadescripts[$filename]);
            }
        }
        foreach ($premadescripts as $filename => $filedata) {
            if (trim($filedata) > '') {
                $data->files[$filename] = $filedata;
                $data->filestodelete[$filename] = 1;
            }
        }
        // Add script file with VPL environment information.
        $data->files['vpl_environment.sh'] = $info;
        $data->files['common_script.sh'] = file_get_contents( vpl_get_scripts_dir() . '/common_script.sh');
        // TODO change jail server to avoid this patch.
        if (count($data->filestodelete) == 0) { // If keeping all files => add dummy.
            $data->filestodelete['__vpl_to_delete__'] = 1;
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
            $num ++;
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

    public static function jailaction($vpl, $server, $action, $data) {
        $plugin = new stdClass();
        require(dirname( __FILE__ ) . '/version.php');
        $pluginversion = $plugin->version;
        $data->pluginversion = $pluginversion;
        $request = vpl_jailserver_manager::get_action_request( $action, $data);
        $error = '';
        $response = vpl_jailserver_manager::get_response( $server, $request, $error );
        if ($response === false) {
            $manager = $vpl->has_capability( VPL_MANAGE_CAPABILITY );
            if ($manager) {
                throw new Exception( get_string( 'serverexecutionerror', VPL ) . "\n" . $error );
            }
            throw new Exception( get_string( 'serverexecutionerror', VPL ) );
        }
        return $response;
    }
    public function jailrequestaction($data, $maxmemory, $localservers, &$server) {
        $error = '';
        $server = vpl_jailserver_manager::get_server( $maxmemory, $localservers, $error );
        if ($server == '') {
            $manager = $this->vpl->has_capability( VPL_MANAGE_CAPABILITY );
            $men = get_string( 'nojailavailable', VPL );
            if ($manager) {
                $men .= ": " . $error;
            }
            throw new Exception( $men );
        }
        return self::jailaction($this->vpl, $server, 'request', $data );
    }
    public function jailreaction($action, $processinfo = false) {
        if ($processinfo === false) {
            $vplid = $this->vpl->get_instance()->id;
            $processinfo = vpl_running_processes::get_run( $this->get_instance()->userid, $vplid);
        }
        if ($processinfo === false) {
            return;
        }
        $server = $processinfo->server;
        $data = new stdClass();
        $data->adminticket = $processinfo->adminticket;
        return self::jailaction($this->vpl, $server, $action, $data );
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
            if (vpl_is_binary( $filename )) {
                $encodefiles[$filename . '.b64'] = base64_encode( $filedata );
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
     * Run, debug, evaluate
     *
     * @param int $type (0=run, 1=debug, evaluate=2, test_evaluate=3)
     */
    public function run($type, $options = []) {
        // Stop current task if one.
        $this->cancelprocess();
        $options = ( array ) $options;
        $plugincfg = get_config('mod_vpl');
        $executescripts = [
                self::TRUN => 'vpl_run.sh',
                self::TDEBUG => 'vpl_debug.sh',
                self::TEVALUATE => 'vpl_evaluate.sh',
                self::TTESTEVALUATE => 'vpl_test_evaluate.sh',
        ];
        $data = $this->prepare_execution($type);
        $data->execute = $executescripts[$type];
        $data->interactive = $type < self::TEVALUATE ? 1 : 0;
        $data->lang = vpl_get_lang();
        $optionsvars = [
            'XGEOMETRY' => 'VPL_XGEOMETRY',
            'currentFileName' => 'VPL_CURRENTSUBFILE',
            'COMMANDARGS' => 'VPL_COMMANDARGS',
        ];
        foreach ($optionsvars as $option => $varname) {
            if (isset( $options[$option] )) {
                $envvar = vpl_bash_export($varname, $options[$option]);
                $data->files['vpl_environment.sh'] .= $envvar;
            }
        }
        $localservers = $data->jailservers;
        $maxmemory = $data->maxmemory;
        // Remove jailservers field.
        unset( $data->jailservers );
        self::adaptbinaryfiles($data, $data->files);
        $jailserver = '';
        $jailresponse = $this->jailrequestaction( $data, $maxmemory, $localservers, $jailserver );
        $parsed = parse_url( $jailserver );
        // Fix jail server port.
        $usinghttp = $parsed['scheme'] == 'http';
        $usinghttps = $parsed['scheme'] == 'https';
        if (! isset($parsed['port']) && $usinghttp) {
            $parsed['port'] = 80;
        }
        if (! isset($parsed['port']) && $usinghttps) {
            $parsed['port'] = 443;
        }
        if (! isset($jailresponse['port'] )) { // Try to fix old jail servers that don't return port.
            $jailresponse['port'] = $parsed['port'];
        }
        if (! isset($jailresponse['secureport'] )) { // Try to fix old jail servers that don't return port.
            $jailresponse['secureport'] = $parsed['port'];
        }
        $response = new stdClass();
        $response->server = $parsed['host'];
        $response->monitorPath = $jailresponse['monitorticket'] . '/monitor';
        $response->executionPath = $jailresponse['executionticket'] . '/execute';
        $response->port = $usinghttp ? $parsed['port'] : $jailresponse['port'];
        $response->securePort = $usinghttps ? $parsed['port'] : $jailresponse['secureport'];
        $response->wsProtocol = $plugincfg->websocket_protocol;
        $response->VNCpassword = substr( $jailresponse['executionticket'], 0, 8 );
        $instance = $this->get_instance();
        $process = new stdClass();
        $process->userid = $instance->userid;
        $process->vpl = $instance->vpl; // The vplid.
        $process->adminticket = $jailresponse['adminticket'];
        $process->server = $jailserver;
        $process->type = $type;
        $response->processid = vpl_running_processes::set($process);
        return $response;
    }

    /**
     * Updates files in running task
     *
     * @param mod_vpl $vpl VPL instance
     * @param int $userid
     * @param int $processid
     * @param string[string] $files internal format
     * @throws Exception
     * @return boolean True if updated
     */
    public static function update($vpl, $userid, $processid, $files,  $filestodelete = []) {
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
            $response = self::jailaction($vpl, $server, 'update', $data );
        } catch (\Throwable $e ) {
            return false;
        }
        return $response['update'] > 0;
    }

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
            $this->saveCE( $response );
            if ($response['executed'] > 0) {
                // If automatic grading.
                if ($this->vpl->get_instance()->automaticgrading) {
                    $data = new StdClass();
                    $data->grade = $this->proposedGrade( $response['execution'] );
                    $data->comments = $this->proposedComment( $response['execution'] );
                    $this->set_grade( $data, true );
                }
            }
        }
        return $this->get_CE_for_editor( $response );
    }
    public function isrunning() {
        try {
            $response = $this->jailreaction( 'running' );
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
            $processinfo = vpl_running_processes::get_run( $userid, $vplid);
        } else {
            $processinfo = vpl_running_processes::get_by_id( $vplid, $userid, $processid );
        }
        if ($processinfo == false) { // No process to cancel.
            return;
        }
        try {
            $this->jailreaction( 'stop', $processinfo );
        } catch (\Throwable $e ) {
            // No matter, consider that the process stopped.
            debugging("Process in execution server not stopped or not found", DEBUG_DEVELOPER );
        }
        vpl_running_processes::delete( $userid, $vplid, $processinfo->adminticket);
    }
}
