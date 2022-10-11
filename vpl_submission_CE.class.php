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
    private static $languageext = array (
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
            'v' => 'verilog',
            'vh' => 'verilog',
            'vhd' => 'vhdl',
            'vhdl' => 'vhdl',
            'r' => 'r',
            'R' => 'r',
            'rb' => 'ruby',
            'ruby' => 'ruby',
            'ts' => 'typescript'
    );
    private static $scriptname = array (
            'vpl_run.sh' => 'run',
            'vpl_debug.sh' => 'debug',
            'vpl_evaluate.sh' => 'evaluate'
    );
    private static $scripttype = array (
            'vpl_run.sh' => 0,
            'vpl_debug.sh' => 1,
            'vpl_evaluate.sh' => 2,
            'vpl_evaluate.cases' => 2
    );
    private static $scriptlist = array (
            0 => 'vpl_run.sh',
            1 => 'vpl_debug.sh',
            2 => 'vpl_evaluate.sh'
    );
    const TRUN = 0;
    const TDEBUG = 1;
    const TEVALUATE = 2;

    /**
     * Return the programming language name based on submitted files extensions
     *
     * @param $filelist array
     *            of files submitted to check type
     * @return string programming language name
     */
    public function get_pln($filelist) {
        foreach ($filelist as $checkfilename) {
            $ext = pathinfo( $checkfilename, PATHINFO_EXTENSION );
            if (isset( self::$languageext[$ext] )) {
                return self::$languageext[$ext];
            }
        }
        return 'default';
    }

    /**
     * Return the default script to manage the action and detected language
     *
     * @param $script string 'vpl_run.sh','vpl_debug.sh' o 'vpl_evaluate.sh'
     * @param $pln string Programming Language Name
     * @param $data object execution data
     *
     * @return array key=>filename value =>filedata
     */
    public function get_default_script($script, $pln, $data) {
        $vplinstance = $this->vpl->get_instance();
        $ret = array ();
        $path = dirname( __FILE__ ) . '/jail/default_scripts/';
        $scripttype = self::$scriptname[$script];
        $field = $scripttype . 'script';
        if ( isset($data->$field) &&  $data->$field > '' ) {
            $pln = $vplinstance->$field;
        }
        $filename = $path . $pln . '_' . $scripttype . '.sh';
        if (file_exists( $filename )) {
            $ret[$script] = file_get_contents( $filename );
        } else {
            $filename = $path . 'default' . '_' . $scripttype . '.sh';
            if (file_exists( $filename )) {
                $ret[$script] = file_get_contents( $filename );
            } else {
                $ret[$script] = file_get_contents( $path . 'default.sh' );
            }
        }
        if ($script == 'vpl_evaluate.sh') {
            $ret['vpl_evaluate.cpp'] = file_get_contents( $path . 'vpl_evaluate.cpp' );
        }
        if ($pln == 'all' && $this->vpl->has_capability( VPL_MANAGE_CAPABILITY )) { // Test all scripts.
            $dirpath = dirname( __FILE__ ) . '/jail/default_scripts';
            if (file_exists( $dirpath )) {
                $dirlst = opendir( $dirpath );
                while ( false !== ($filename = readdir( $dirlst )) ) {
                    if ($filename == "." || $filename == "..") {
                        continue;
                    }
                    if (substr( $filename, - 7 ) == '_run.sh' ||
                        substr( $filename, - 9 ) == '_hello.sh' ||
                        substr( $filename, - 9 ) == '_debug.sh' ) {
                        $ret[$filename] = file_get_contents( $path . $filename );
                    }
                }
                closedir( $dirlst );
            }
        }
        return $ret;
    }

    /**
     * Recopile execution data to be send to the jail
     *
     * @param array $already=array().
     *            List of based on instances, usefull to avoid infinite recursion
     * @param mod_vpl $vpl. VPl instance to process. Default = null
     * @return object with files, limits, interactive and other info
     */
    public function prepare_execution($type, &$already = array(), $vpl = null) {
        global $DB;
        $plugincfg = get_config('mod_vpl');
        if ($vpl == null) {
            $vpl = $this->vpl;
        }
        $vplinstance = $vpl->get_instance();
        if (isset( $already[$vplinstance->id] )) {
            throw new moodle_exception('error:recursivedefinition', 'mod_vpl');
        }
        $call = count( $already );
        $already[$vplinstance->id] = true;
        // Load basedon files if needed.
        if ($vplinstance->basedon) {
            $basedon = new mod_vpl( null, $vplinstance->basedon );
            $data = $this->prepare_execution( $type, $already, $basedon );
        } else {
            $data = new stdClass();
            $data->files = array ();
            $data->filestodelete = array ();
            $data->maxtime = ( int ) $plugincfg->defaultexetime;
            $data->maxfilesize = ( int ) $plugincfg->defaultexefilesize;
            $data->maxmemory = ( int ) $plugincfg->defaultexememory;
            $data->maxprocesses = ( int ) $plugincfg->defaultexeprocesses;
            $data->jailservers = '';
            $data->runscript = $vplinstance->runscript;
            $data->debugscript = $vplinstance->debugscript;
        }
        // Execution files.
        $sfg = $vpl->get_execution_fgm();
        $list = $sfg->getFileList();
        foreach ($list as $filename) {
            // Skip unneeded script.
            if (isset( self::$scripttype[$filename] ) && self::$scripttype[$filename] > $type) {
                continue;
            }
            if (isset( $data->files[$filename] ) && isset( self::$scriptname[$filename] )) {
                $data->files[$filename] .= "\n" . $sfg->getFileData( $filename );
            } else {
                $data->files[$filename] = $sfg->getFileData( $filename );
            }
            $data->filestodelete[$filename] = 1;
        }
        $deletelist = $sfg->getFileKeepList();
        foreach ($deletelist as $filename) {
            unset( $data->filestodelete[$filename] );
        }

        if ($vplinstance->maxexetime) {
            $data->maxtime = ( int ) $vplinstance->maxexetime;
        }
        if ($vplinstance->maxexememory) {
            $data->maxmemory = ( int ) $vplinstance->maxexememory;
        }
        if ($vplinstance->maxexefilesize) {
            $data->maxfilesize = ( int ) $vplinstance->maxexefilesize;
        }
        if ($vplinstance->maxexeprocesses) {
            $data->maxprocesses = ( int ) $vplinstance->maxexeprocesses;
        }
        // Add jailserver list.
        if ($vpl->get_instance()->jailservers > '') {
            $data->jailservers = $vpl->get_instance()->jailservers . "\n" . $data->jailservers;
        }
        if ( $vplinstance->runscript > '' ) {
            $data->runscript = $vplinstance->runscript;
        }
        if ( $vplinstance->debugscript > '' ) {
            $data->debugscript = $vplinstance->debugscript;
        }

        if ($call > 0) { // Stop if at recursive call.
            return $data;
        }
        // Submitted files.
        $sfg = $this->get_submitted_fgm();
        $list = $sfg->getFileList();
        // Var $submittedlist is $list but removing the files overwrited by teacher's one.
        $submittedlist = array ();
        foreach ($list as $filename) {
            if (! isset( $data->files[$filename] )) {
                $data->files[$filename] = $sfg->getFileData( $filename );
            }
            $submittedlist[] = $filename;
        }
        // Get programming language.
        $pln = $this->get_pln( $list );
        // Adapt Java and HTML memory limit.
        if ($pln == 'java' || $pln == 'html') {
            $javaoffset = 128 * 1024 * 1024; // Checked at Ubuntu 12.04 64 and CentOS 6.5 64.
            if ($data->maxmemory + $javaoffset > $data->maxmemory) {
                $data->maxmemory += $javaoffset;
            } else {
                $data->maxmemory = ( int ) PHP_INT_MAX;
            }
        }
        // Limit resource to maximum.
        $data->maxtime = min( $data->maxtime, ( int ) $plugincfg->maxexetime );
        $data->maxfilesize = min( $data->maxfilesize, ( int ) $plugincfg->maxexefilesize );
        $data->maxmemory = min( $data->maxmemory, ( int ) $plugincfg->maxexememory );
        $data->maxprocesses = min( $data->maxprocesses, ( int ) $plugincfg->maxexeprocesses );
        $subinstance = $this->get_instance();
        // Info send with script.
        $info = "#!/bin/bash\n";
        $info .= vpl_bash_export( 'VPL_LANG', vpl_get_lang( true ) );
        $info .= vpl_bash_export( 'MOODLE_USER_ID',  $subinstance->userid );
        if ($user = $DB->get_record( 'user', array ( 'id' => $subinstance->userid ) )) {
            $info .= vpl_bash_export( 'MOODLE_USER_NAME', $vpl->fullname( $user, false ) );
            $info .= vpl_bash_export( 'MOODLE_USER_EMAIL', $user->email );
        }
        if ($vpl->is_group_activity()) {
            if ($group = $DB->get_record( 'groups', array ( 'id' => $subinstance->groupid ) )) {
                $info .= vpl_bash_export( 'MOODLE_GROUP_ID',  $subinstance->groupid );
                $info .= vpl_bash_export( 'MOODLE_GROUP_NAME', $group->name );
            }
        }
        if ($type == 2) { // If evaluation add information.
            $info .= vpl_bash_export( 'VPL_MAXTIME', $data->maxtime );
            $info .= vpl_bash_export( 'VPL_MAXMEMORY',  $data->maxmemory );
            $info .= vpl_bash_export( 'VPL_MAXFILESIZE',  $data->maxfilesize );
            $info .= vpl_bash_export( 'VPL_MAXPROCESSES',  $data->maxprocesses );
            $gradesetting = $vpl->get_grade_info();
            if ($gradesetting !== false) {
                $info .= vpl_bash_export( 'VPL_GRADEMIN',  $gradesetting->grademin );
                $info .= vpl_bash_export( 'VPL_GRADEMAX',  $gradesetting->grademax );
            }
            $info .= vpl_bash_export( 'VPL_COMPILATIONFAILED', get_string( 'VPL_COMPILATIONFAILED', VPL ) );
        }
        $filenames = '';
        $num = 0;
        foreach ($submittedlist as $filename) {
            $filenames .= $filename . "\n";
            $info .= vpl_bash_export( 'VPL_SUBFILE' . $num, $filename );
            $num ++;
        }
        $info .= 'export VPL_SUBFILES="' . $filenames . "\"\n";
        // Add identifications of variations if exist.
        $info .= vpl_bash_export( 'VPL_VARIATION', '' );
        $varids = $vpl->get_variation_identification( $this->instance->userid );
        foreach ($varids as $id => $varid) {
            $info .= vpl_bash_export( 'VPL_VARIATION' . $id, $varid );
            $info .= vpl_bash_export( 'VPL_VARIATION', $varid );
        }
        for ($i = 0; $i <= $type; $i ++) {
            $script = self::$scriptlist[$i];
            if (isset( $data->files[$script] ) && trim( $data->files[$script] ) > '') {
                if (substr( $data->files[$script], 0, 2 ) != '#!') {
                    // No shebang => add bash.
                    $data->files[$script] = "#!/bin/bash\n" . $data->files[$script];
                }
            } else {
                $filesadded = $this->get_default_script( $script, $pln, $data );
                foreach ($filesadded as $filename => $filedata) {
                    if (trim( $filedata ) > '') {
                        $data->files[$filename] = $filedata;
                        $data->filestodelete[$filename] = 1;
                    }
                }
            }
        }
        // Add script file with VPL environment information.
        $data->files['vpl_environment.sh'] = $info;
        $data->files['common_script.sh'] = file_get_contents( dirname( __FILE__ ) . '/jail/default_scripts/common_script.sh' );

        // TODO change jail server to avoid this patch.
        if (count( $data->filestodelete ) == 0) { // If keeping all files => add dummy.
            $data->filestodelete['__vpl_to_delete__'] = 1;
        }
        // Info to log who/what.
        $data->userid = $this->instance->userid;
        $data->activityid = $this->vpl->get_instance()->id;
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
            throw new Exception( 'Process not found' );
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
     * @param int $type
     *            (0=run, 1=debug, evaluate=2)
     */
    public function run($type, $options = array()) {
        // Stop current task if one.
        $this->cancelprocess();
        $options = ( array ) $options;
        $plugincfg = get_config('mod_vpl');
        $executescripts = array (
                0 => 'vpl_run.sh',
                1 => 'vpl_debug.sh',
                2 => 'vpl_evaluate.sh'
        );
        $data = $this->prepare_execution( $type );
        $data->execute = $executescripts[$type];
        $data->interactive = $type < 2 ? 1 : 0;
        $data->lang = vpl_get_lang( true );
        if (isset( $options['XGEOMETRY'] )) { // TODO refactor to a better solution.
            $data->files['vpl_environment.sh'] .= "\n".vpl_bash_export( 'VPL_XGEOMETRY', $options['XGEOMETRY'] );
        }
        if (isset( $options['COMMANDARGS'] )) {
            $data->commandargs = $options['COMMANDARGS'];
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
        if (! isset( $parsed['port'] ) && $parsed['scheme'] == 'http') {
            $parsed['port'] = 80;
        }
        if (! isset( $jailresponse['port'] )) { // Try to fix old jail servers that don't return port.
            $jailresponse['port'] = $parsed['port'];
        }

        $response = new stdClass();
        $response->server = $parsed['host'];
        $response->monitorPath = $jailresponse['monitorticket'] . '/monitor';
        $response->executionPath = $jailresponse['executionticket'] . '/execute';
        $response->port = $jailresponse['port'];
        $response->securePort = $jailresponse['secureport'];
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
        } catch ( Exception $e ) {
            return false;
        }
        return $response['update'] > 0;
    }

    public function retrieveresult($processid) {
        $vplid = $this->vpl->get_instance()->id;
        $processinfo = vpl_running_processes::get_by_id($vplid, $this->instance->userid, $processid);
        if ($processinfo == false) { // No process found.
            throw new Exception( get_string( 'serverexecutionerror', VPL ) );
        }
        $response = $this->jailreaction( 'getresult', $processinfo );
        if ($response === false) {
            throw new Exception( get_string( 'serverexecutionerror', VPL ) );
        }
        if ($response['interactive'] == 0) {
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
        } catch ( Exception $e ) {
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
        } catch ( Exception $e ) {
            // No matter, consider that the process stopped.
            debugging( "Process in execution server not sttoped or not found", DEBUG_DEVELOPER );
        }
        vpl_running_processes::delete( $userid, $vplid, $processinfo->adminticket);
    }
}
