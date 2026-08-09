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
 * Class to manage edition/execution operations
 *
 * @package mod_vpl
 * @copyright 2014 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/../vpl.class.php');
require_once(dirname(__FILE__) . '/../vpl_submission_CE.class.php');
require_once(dirname(__FILE__) . '/../vpl_example_CE.class.php');

/**
 * Class to manage edition/execution operations
 *
 * @package mod_vpl
 * @copyright 2014 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
class mod_vpl_edit {
    /**
     * Translates files from IDE to internal format
     *
     * @param array $postfiles atributes encoding, name and contents
     * @return array contents indexed by filenames
     */
    public static function filesfromide(&$postfiles) {
        $files = [];
        foreach ($postfiles as $file) {
            if ($file->encoding == 1) {
                $files[$file->name] = base64_decode($file->contents);
            } else {
                $files[$file->name] = $file->contents;
            }
        }
        return $files;
    }

    /**
     * Translates files from internal format to IDE format
     *
     * @param string[string] $from contents indexed by filenames
     * @return array of stdClass
     */
    public static function filestoide(&$from) {
        $files = [];
        foreach ($from as $name => $data) {
            $file = new stdClass();
            $file->name = $name;
            if (vpl_is_binary($name, $data)) {
                $file->contents = base64_encode($data);
                $file->encoding = 1;
            } else {
                $file->contents = $data;
                $file->encoding = 0;
            }
            $files[] = $file;
        }
        return $files;
    }

    /**
     * Converts from file internal format to old array of array format
     * @param string[string] $arrayfiles files internal format
     * @return string[][]
     */
    public static function files2object(&$arrayfiles) {
        $files = [];
        foreach ($arrayfiles as $name => $data) {
            $file = [
                    'name' => $name,
                    'data' => $data,
            ];
            $files[] = $file;
        }
        return $files;
    }

    /**
     * Save a submission version
     *
     * @param mod_vpl $vpl VPL instance
     * @param int $userid User id of the submission
     * @param array $files internal format
     * @param string $comments User comments for the submission
     * @param int $originalversion -1 for no previous version or the id of version to update
     * @throws Exception
     * @return int saved record id
     */
    public static function save(mod_vpl $vpl, int $userid, array &$files, string $comments = '', int $originalversion = -1) {
        global $USER;
        $response = new stdClass();
        $response->requestsconfirmation = false;
        $response->saved = false;
        if ($originalversion != -1) {
            $lastsub = $vpl->last_user_submission($userid);
            if ($lastsub && $lastsub->id != $originalversion) {
                $response->requestsconfirmation = true;
                $response->question = get_string('replacenewer', VPL);
                $response->version = $lastsub->id;
                return $response;
            }
            if ($userid != $USER->id) {
                $response->requestsconfirmation = true;
                $response->question = get_string('saveforotheruser', VPL);
                $response->version = -1;
                return $response;
            }
        }
        $errormessage = '';
        if ($subid = $vpl->add_submission($userid, $files, $comments, $errormessage)) {
            \mod_vpl\event\submission_uploaded::log([
                    'objectid' => $subid,
                    'context' => $vpl->get_context(),
                    'relateduserid' => ($USER->id != $userid ? $userid : null),
            ]);
            $response->version = $subid;
            $response->saved = true;
            return $response;
        } else {
            throw new Exception(get_string('notsaved', VPL) . ': ' . $errormessage);
        }
    }
    /**
     * Updates files in running task
     *
     * @param mod_vpl $vpl VPL instance
     * @param int $userid
     * @param int $processid
     * @param array $files internal format
     * @param array $filestodelete files to delete in the running task
     * @throws Exception
     * @return boolean True if updated
     */
    public static function update(mod_vpl $vpl, int $userid, int $processid, array &$files, $filestodelete = []) {
        return mod_vpl_submission_CE::update($vpl, $userid, $processid, $files, $filestodelete);
    }

    /**
     * Returns initial/requested files of $vpl
     * @param mod_vpl $vpl
     * @return string[string] files internal format
     */
    public static function get_requested_files($vpl) {
        $reqfgm = $vpl->get_required_fgm();
        return $reqfgm->getallfiles();
    }

    /**
     * Returns last submitted files of $vpl and userid.
     * If available $compilationexecution will return compilation and execution information.
     * @param mod_vpl $vpl
     * @param int $userid
     * @param Object $compilationexecution
     * @return string[string]
     */
    public static function get_submitted_files($vpl, $userid, &$compilationexecution) {
        $compilationexecution = false;
        $lastsub = $vpl->last_user_submission($userid);
        if ($lastsub) {
            $submission = new mod_vpl_submission($vpl, $lastsub);
            $fgp = $submission->get_submitted_fgm();
            $files = $fgp->getallfiles();
            $compilationexecution = $submission->get_CE_for_editor();
            $compilationexecution->comments = $submission->get_instance()->comments;
        } else {
            $files = self::get_requested_files($vpl);
            $compilationexecution = new stdClass();
            $compilationexecution->comments = '';
            $compilationexecution->nevaluations = 0;
            $compilationexecution->freeevaluations = $vpl->get_effective_setting('freeevaluations', $userid);
            $compilationexecution->reductionbyevaluation = $vpl->get_effective_setting('reductionbyevaluation', $userid);
        }
        return $files;
    }

    /**
     * Returns the last or other submission and compilation execution information
     * @param mod_vpl $vpl
     * @param int $userid
     * @param int|boolean $submissionid
     * @return Object
     */
    public static function load($vpl, $userid, $submissionid = false) {
        global $DB;
        $response = new stdClass();
        $response->version = 0;
        $response->comments = '';
        $response->compilationexecution = false;
        $vplinstance = $vpl->get_instance();
        if ($submissionid !== false) {
            // Security checks.
            $parms = ['id' => $submissionid, 'vpl' => $vplinstance->id];
            $vpl->require_capability(VPL_GRADE_CAPABILITY);
            $res = $DB->get_records('vpl_submissions', $parms);
            if (count($res) == 1) {
                 $subreg = $res[$submissionid];
            } else {
                 $subreg = false;
            }
        } else {
            $subreg = $vpl->last_user_submission($userid);
        }
        $response->files = self::get_requested_files($vpl);
        if ($subreg) {
            $submission = new mod_vpl_submission($vpl, $subreg);
            $fgp = $submission->get_submitted_fgm();
            $response->version = $subreg->id;
            $response->comments = $subreg->comments;
            $response->files = array_merge($response->files, $fgp->getallfiles());
            $response->compilationexecution = $submission->get_CE_for_editor();
        } else {
            $compilationexecution = new stdClass();
            $compilationexecution->grade = '';
            $compilationexecution->nevaluations = 0;
            $compilationexecution->freeevaluations = $vpl->get_effective_setting('freeevaluations', $userid);
            $compilationexecution->reductionbyevaluation = $vpl->get_effective_setting('reductionbyevaluation', $userid);
            $response->compilationexecution = $compilationexecution;
        }
        return $response;
    }

    /**
     * Request the execution (run|debug|evaluate|test_evaluate) of a user's submission or test_evaluate
     * @param mod_vpl $vpl
     * @param int $userid
     * @param string $action
     * @param array $options for the execution
     * @throws Exception
     * @return Object with execution information
     */
    public static function execute($vpl, $userid, $action, $options = []) {
        global $USER;
        $example = $vpl->is_example();
        $lastsub = $vpl->last_user_submission($userid);
        if (! $lastsub && ! $example && $action != 'test_evaluate') {
            throw new Exception(get_string('nosubmission', VPL));
        }
        if ($example || ! $lastsub) {
            $submission = new mod_vpl_example_CE($vpl);
        } else {
            $submission = new mod_vpl_submission_CE($vpl, $lastsub);
        }
        $code = ['run' => 0, 'debug' => 1, 'evaluate' => 2, 'test_evaluate' => 3];
        $traslate = ['run' => 'run', 'debug' => 'debugged',
                     'evaluate' => 'evaluated', 'test_evaluate' => 'evaluated', ];
        $eventclass = '\mod_vpl\event\submission_' . $traslate[$action];
        $eventclass::log($submission);
        return $submission->run($code[$action], $options);
    }

    /**
     * Request the retrieve of the evaluation result of a running process.
     * @param mod_vpl $vpl VPL activity object.
     * @param int $userid User id.
     * @param int $processid Process id. If not provided backwards compatibility is applied
     *                       and the last process for this user and VPL activity is used.
     * @throws Exception
     * @return stdClass Object with compilation, execution, evaluation, grade and nevaluations as attributes.
     */
    public static function retrieve_result(mod_vpl $vpl, int $userid, $processid = -1) {
        if ($processid == -1) { // Backware compatibility => process id not provided.
            // Get the process id of the last running process for this user and VPL activity.
            $processinfo = vpl_running_processes::get_run($userid, $vpl->get_instance()->id);
            if ($processinfo == false) { // No process found.
                throw new Exception(get_string('serverexecutionerror', VPL) . ' Process not found');
            } else {
                $processid = $processinfo->id;
            }
        } else {
            // Security checks.
            $vplid = $vpl->get_instance()->id;
            $processinfo = vpl_running_processes::get_by_id($vplid, $userid, $processid);
            if ($processinfo == false) { // No process found.
                throw new Exception(get_string('serverexecutionerror', VPL) . ' Process not found');
            }
        }
        if ($processinfo->submissionid == 0) {
            throw new Exception(get_string('serverexecutionerror', VPL) . ' Process without submission');
        }
        if ($vpl->is_example()) {
            $submission = new mod_vpl_example_CE($vpl);
        } else {
            $submission = new mod_vpl_submission_CE($vpl, $processinfo->submissionid);
        }
        return $submission->retrieveresult($processid);
    }

    /**
     * Request the cancel of a evaluation/execution in progress.
     * @param mod_vpl $vpl
     * @param int $userid
     * @param int $processid
     * @return string The message of not canceled or empty string
     */
    public static function cancel($vpl, $userid, int $processid) {
        $example = $vpl->is_example();
        $lastsub = $vpl->last_user_submission($userid);
        try {
            if ($example || ! $lastsub) {
                $submission = new mod_vpl_example_CE($vpl);
            } else {
                $submission = new mod_vpl_submission_CE($vpl, $lastsub);
            }
            $submission->cancelProcess($processid);
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
        return '';
    }

    /**
     * Check and stop the direct runs that pass a limit for this user and vpl activity if any
     * @param int $vplid
     * @param int $userid
     * @param int $maxrunning
     */
    public static function limit_directruns($vplid, $userid, $maxrunning) {
        $allprocesses = vpl_running_processes::get_directrun($userid);
        if (count($allprocesses) >= $maxrunning) {
            // Sort the processes so that the oldest ones are first and the newest ones are last.
            // The processes that are not for this VPL activity are sorted first, so that they are stopped first.
            usort($allprocesses, function ($a, $b) use ($vplid) {
                if ($a->vpl == $vplid && $b->vpl == $vplid) {
                    return $a->id <=> $b->id;
                }
                if ($a->vpl == $vplid) {
                    return 1;
                }
                if ($b->vpl == $vplid) {
                    return -1;
                }
                return $a->id <=> $b->id;
            });
            $processestostop = array_slice($allprocesses, 0, count($allprocesses) - $maxrunning + 1);
            foreach ($processestostop as $process) {
                try {
                    $data = new \stdClass();
                    $data->adminticket = $process->adminticket;
                    $request = vpl_jailserver_manager::get_action_request('stop', $data);
                    vpl_jailserver_manager::get_response($process->server, $request, $error);
                } catch (\Throwable $e) {
                    debugging("Process directrun in execution server not stopped or not found", DEBUG_DEVELOPER);
                }
                vpl_running_processes::delete($userid, $vplid, $process->adminticket);
            }
        }
    }

    /**
     * Request the direct run code in an execution server
     *
     * @param mod_vpl $vpl VPL instance
     * @param int $userid User id of the run request
     * @param stdClass $actiondata of the command to execute
     * @return stdClass with the response information
     * @throws Exception
     */
    public static function directrun($vpl, $userid, $actiondata) {
        $vplid = $vpl->get_instance()->id;
        // TODO: This is a temporary solution to limit the number of direct runs for non-managers and non-graders.
        if ($vpl->has_capability(VPL_MANAGE_CAPABILITY) || $vpl->has_capability(VPL_GRADE_CAPABILITY)) {
            $maxrunning = 1000;
        } else {
            $maxrunning = 3;
        }
        self::limit_directruns($vplid, $userid, $maxrunning);
        $command = $actiondata->command;
        if ($command == '') {
            throw new Exception(get_string('nojailavailable', VPL));
        }
        $language = null;
        if ($command[0] == '$') {
            $language = preg_replace('/[^a-z_]/', '', substr($command, 1));
            $command = '/usr/local/bin/vpl/' . $language . '_ls';
        }
        $maxmemory = 2000 * 1000 * 1000;
        $error = '';
        $server = vpl_jailserver_manager::get_server($vpl, $maxmemory, $error, $language);
        if ($server == '') {
            $manager = $vpl->has_capability(VPL_MANAGE_CAPABILITY);
            $men = get_string('nojailavailable', VPL);
            if ($manager) {
                $men .= ": " . $error;
            }
            throw new Exception($men);
        }
        $data = new stdClass();
        mod_vpl_submission_CE::adaptbinaryfiles($data, $actiondata->files);
        $vplang = vpl_get_lang();
        $executefilename = '.vpl_directrun.sh';
        $data->files[$executefilename] = <<<DIRECTRUNCODE
#!/bin/bash
cat > vpl_execution <<CONTENTS
#!/bin/bash
for NEWLANG in $vplang en_US.UTF-8 C.utf8 POSIX C
do
    export LC_ALL=\$NEWLANG 2> .vpl_set_locale_error
    if [ -s .vpl_set_locale_error ]; then
        rm -f .vpl_set_locale_error
        continue
    else
        break
    fi
done
rm -f .vpl_set_locale_error
stty raw -echo
$command

CONTENTS
chmod +x vpl_execution
DIRECTRUNCODE;
        $data->filestodelete[$executefilename] = 1;
        $data->fileencoding[$executefilename] = 0;
        $data->execute = $executefilename;
        $plugin = new stdClass();
        require(__DIR__ . '/../version.php');
        $pluginversion = $plugin->version;
        $data->pluginversion = $pluginversion;
        $data->interactive = 1;
        $data->lang = vpl_get_lang();
        $data->maxtime = 1000000;
        $data->maxfilesize = $maxmemory;
        $data->maxmemory = $maxmemory;
        $data->maxprocesses = 10000;
        $request = vpl_jailserver_manager::get_action_request('directrun', $data);
        $error = '';
        $jailresponse = vpl_jailserver_manager::get_response($server, $request, $error);
        if ($jailresponse === false) {
            $manager = $vpl->has_capability(VPL_MANAGE_CAPABILITY);
            if ($manager) {
                throw new Exception(get_string('serverexecutionerror', VPL) . "\n" . $error . ' ' . $server . ' ' . $request);
            }
            throw new Exception(get_string('serverexecutionerror', VPL));
        }
        $parsed = parse_url($server);
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
        $plugincfg = get_config('mod_vpl');
        $response = new stdClass();
        $response->server = $parsed['host'];
        $response->executionPath = $jailresponse['executionticket'] . '/execute';
        $response->port = $usinghttp ? $parsed['port'] : $jailresponse['port'];
        $response->securePort = $usinghttps ? $parsed['port'] : $jailresponse['secureport'];
        $response->wsProtocol = $plugincfg->websocket_protocol;
        $response->homepath = $jailresponse['homepath'];
        $process = new stdClass();
        $process->userid = $userid;
        $process->vpl = $vplid;
        $process->adminticket = $jailresponse['adminticket'];
        $process->server = $server;
        $process->type = 4;
        $process->submissionid = 0;
        $process->time_limit = time() + $data->maxtime;
        $response->processid = vpl_running_processes::set($process);
        return $response;
    }
}
