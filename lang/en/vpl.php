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
 * @var array $string
 */
$string['about'] = 'About';
$string['acceptcertificates'] = 'Accept self signed certificates';
$string['acceptcertificates_description'] = 'If the execution servers are not using self signed certificates uncheck this option';
$string['acceptcertificatesnote'] = "<p>You are using an encrypted connection.<p/>
<p>To use an encrypted connection with the execution servers it is required you accept its certificates.</p>
<p>If you have problems with this process, you can try to use a http (unencrypted) connection or other browser.</p>
<p>Please, click on the following links (server #) and accept the offered certificate.</p>";
$string['addfile'] = 'Add file';
$string['addoverride'] = 'Add an override';
$string['advanced'] = 'Advanced';
$string['allfiles'] = 'All files';
$string['allsubmissions'] = 'All submissions';
$string['anyfile'] = 'Any file';
$string['attemptnumber'] = 'Attempt number {$a}';
$string['autodetect'] = 'Autodetect';
$string['automaticevaluation'] = 'Automatic evaluation';
$string['automaticgrading'] = 'Automatic grade';
$string['averageperiods'] = 'Average periods {$a}';
$string['averagetime'] = 'Average time {$a}';
$string['basedon'] = 'Based on';
$string['basic'] = 'Basic';
$string['binaryfile'] = 'Binary File';
$string['breakpoint'] = 'Breakpoint';
$string['browserupdate'] = 'Please update your browser to the last version<br />or use another that supports Websocket.';
$string['calculate'] = 'Calculate';
$string['calendardue'] = 'VPL submission is due';
$string['calendarexpectedon'] = 'VPL submission expected';
$string['changesNotSaved'] = 'Changes have not been saved';
$string['check_jail_servers'] = 'Check execution servers';
$string['clipboard'] = 'Clipboard';
$string['closed'] = 'Closed';
$string['comments'] = 'Comments';
$string['compilation'] = 'Compilation';
$string['confirmoverridedeletion'] = 'Are you sure you want to delete this override set?';
$string['connected'] = 'connected';
$string['connecting'] = 'connecting';
$string['connection_closed'] = 'connection closed';
$string['connection_fail'] = 'connection fail';
$string['console'] = 'Console';
$string['copy'] = 'Copy';
$string['create_new_file'] = 'Create a new file';
$string['crontask'] = 'Background processing for Virtual Programming Lab module';
$string['currentstatus'] = 'Current status';
$string['cut'] = 'Cut';
$string['datesubmitted'] = 'Date submitted';
$string['debug'] = 'Debug';
$string['debugging'] = 'Debugging';
$string['debugscript'] = 'Debug script';
$string['debugscript_help'] = 'Select the debug script to use in this activity';
$string['defaultexefilesize'] = 'Maximum default execution file size';
$string['defaultexememory'] = 'Maximum default memory used';
$string['defaultexeprocesses'] = 'Maximum default number of processes';
$string['defaultexetime'] = 'Maximum default execution time';
$string['defaultfilesize'] = 'Default maximum upload file size';
$string['defaultresourcelimits'] = 'Default execution resources limits';
$string['delete'] = 'Delete';
$string['delete_file_fq'] = "delete '{\$a}' file?";
$string['delete_file_q'] = 'Delete file?';
$string['deleteallsubmissions'] = 'Delete all submissions';
$string['description'] = 'Description';
$string['diff'] = 'diff';
$string['disabled'] = 'Disabled';
$string['discard_submission_period'] = 'Discard submission period';
$string['discard_submission_period_description'] = 'For each student and assignment, the system tries to discard submissions. The system keep the last one and at least a submission for every period';
$string['download'] = 'Download';
$string['downloadallsubmissions'] = 'Download all submissions';
$string['downloadsubmissions'] = 'Download submissions';
$string['duedate'] = 'Due date';
$string['dueevent'] = '{$a} submission is due';
$string['dueeventaction'] = 'Develop/submit';
$string['edit'] = 'Edit';
$string['editing'] = 'Editing';
$string['editortheme'] = 'Editor theme';
$string['error:inconsistency'] = "Inconsistency found '{\$a}'";
$string['error:recordnotdeleted'] = "Record not deleted '{\$a}'";
$string['error:recordnotinserted'] = "Record not inserted '{\$a}'";
$string['error:recordnotupdated'] = "Record not updated '{\$a}'";
$string['error:recursivedefinition'] = "Recursive basedon VPL definition";
$string['error:uninstalling'] = 'Error uninstalling VPL. All data may have not been deleted';
$string['error:zipnotfound'] = 'ZIP file not found';
$string['evaluate'] = 'Evaluate';
$string['evaluateonsubmission'] = 'Evaluate just on submission';
$string['evaluating'] = 'Evaluating';
$string['evaluation'] = 'Evaluation';
$string['examples'] = 'Examples';
$string['execution'] = 'Execution';
$string['executionfiles'] = 'Execution files';
$string['executionoptions'] = 'Execution options';
$string['file'] = 'File';
$string['file_name'] = 'File name';
$string['fileadded'] = "The '{\$a}' file has been added";
$string['filedeleted'] = "The '{\$a}' file has been deleted";
$string['filelist'] = "File list";
$string['filenotadded'] = 'File has not been added';
$string['fileNotChanged'] = 'File has not changed';
$string['filenotdeleted'] = 'The \'{$a}\' file has NOT been deleted';
$string['filenotrenamed'] = 'The \'{$a}\' file has NOT been renamed';
$string['filerenamed'] = "The '{\$a->from}' file has been renamed to '{\$a->to}'";
$string['filesChangedNotSaved'] = 'Files have changed but they have not been saved';
$string['filesNotChanged'] = 'Files have not changed';
$string['filestoscan'] = 'Files to scan';
$string['fileupdated'] = "The '{\$a}' file has been updated";
$string['finalreduction'] = 'Final reduction';
$string['finalreduction_help'] = '<b>FR [NE/FE R]</b><br>
<b>FR</b> Final grade reduction.<br>
<b>NE</b> Automatic evaluations requested by the student.<br>
<b>FE</b> Free evaluations allowed.<br>
<b>R</b> Grade reduction by evaluation. If it is a percent, it is apply over previous result.<br>';
$string['find'] = "Find";
$string['find_replace'] = 'Find/Replace';
$string['freeevaluations'] = 'Free evaluations';
$string['freeevaluations_help'] = 'Number of automatic evaluations that do not reduce final score';
$string['fulldescription'] = 'Full description';
$string['fullscreen'] = 'Fullscreen';
$string['getjails'] = 'Get execution servers';
$string['gradeandnext'] = 'Grade & next';
$string['graded'] = 'Graded';
$string['gradedbyuser'] = 'Graded by user';
$string['gradedon'] = "Evaluated on";
$string['gradedonby'] = 'Reviewed on {$a->date} by {$a->gradername}';
$string['gradenotremoved'] = 'The grade has NOT been removed. Check activity config in the gradebook.';
$string['gradenotsaved'] = 'The grade has NOT been saved. Check activity config in the gradebook.';
$string['gradeoptions'] = 'Grade options';
$string['grader'] = "Evaluator";
$string['gradercomments'] = 'Assessment report';
$string['graderemoved'] = 'The grade has been removed';
$string['groupwork'] = 'Group work';
$string['inconsistentgroup'] = 'You are not member of only one group (0 o >1)';
$string['incorrect_file_name'] = 'Incorrect file name';
$string['indicator:cognitivedepth'] = 'VPL cognitive';
$string['indicator:cognitivedepth_help'] = 'This indicator is based on the cognitive depth reached by the student in an VPL activity.';
$string['indicator:socialbreadth'] = 'VPL social';
$string['indicator:socialbreadth_help'] = 'This indicator is based on the social breadth reached by the student in an VPL activity.';
$string['individualwork'] = 'Individual work';
$string['instanceselection'] = 'VPL selection';
$string['isexample'] = 'This activity acts as example';
$string['jail_servers'] = 'Execution servers list';
$string['jail_servers_config'] = 'Execution servers config';
$string['jail_servers_description'] = 'Write a line for each server';
$string['joinedfiles'] = 'Joined selected files';
$string['keepfiles'] = 'Files to keep when running';
$string['keyboard'] = 'Keyboard';
$string['lasterror'] = 'Last error info';
$string['lasterrordate'] = 'Last error date';
$string['listofcomments'] = 'List of comments';
$string['listsimilarity'] = 'List of similarities found';
$string['listwatermarks'] = 'Water marks list';
$string['load'] = 'Load';
$string['loading'] = 'Loading';
$string['local_jail_servers'] = 'Local execution servers';
$string['manualgrading'] = 'Manual grading';
$string['maxexefilesize'] = 'Maximum execution file size';
$string['maxexememory'] = 'Maximum memory used';
$string['maxexeprocesses'] = 'Maximum number of processes';
$string['maxexetime'] = 'Maximum execution time';
$string['maxfiles'] = 'Maximum number of files';
$string['maxfilesexceeded'] = 'Maximum number of files exceeded';
$string['maxfilesize'] = 'Maximum upload file size';
$string['maxfilesizeexceeded'] = 'Maximum file size exceeded';
$string['maxpostsizeexceeded'] = 'Maximum server post size exceeded. Please, remove files or reduce files size';
$string['maximumperiod'] = 'Maximum period {$a}';
$string['maxresourcelimits'] = 'Maximum execution resources limits';
$string['maxsimilarityoutput'] = 'Maximum output by similarity';
$string['menucheck_jail_servers'] = 'Check execution servers';
$string['menuexecutionfiles'] = 'Execution files';
$string['menuexecutionoptions'] = 'Options';
$string['menukeepfiles'] = 'Files to keep';
$string['menulocal_jail_servers'] = 'Local execution servers';
$string['menuresourcelimits'] = 'Resources limits';
$string['minsimlevel'] = 'Minimum similarity level to show';
$string['moduleconfigtitle'] = 'VPL Module Config';
$string['modulename'] = 'Virtual programming lab';
$string['modulenameplural'] = 'Virtual programming labs';
$string['multidelete'] = 'Multiple delete';
$string['nevaluations'] = '{$a} automatic evaluations done';
$string['new'] = 'New';
$string['new_file_name'] = 'New file name';
$string['next'] = 'Next';
$string['nojailavailable'] = 'No execution server available';
$string['noright'] = 'You don\'t have right to access';
$string['nosubmission'] = 'No submission available';
$string['notexecuted'] = 'Not executed';
$string['notgraded'] = 'Not graded';
$string['notsaved'] = 'Not saved';
$string['novpls'] = 'No virtual programming lab defined';
$string['nowatermark'] = 'Own water marks {$a}';
$string['nsubmissions'] = '{$a} submissions';
$string['numcluster'] = 'Cluster {$a}';
$string['open'] = 'Open';
$string['opnotallowfromclient'] = 'Action not allowed from this machine';
$string['options'] = 'Options';
$string['optionsnotsaved'] = 'Options have not been saved';
$string['optionssaved'] = 'Options have been saved';
$string['origin'] = 'Origin';
$string['othersources'] = 'Other sources to add to the scan';
$string['outofmemory'] = 'Out of memory';
$string['override'] = 'Override';
$string['overridefor'] = '{$a->base} is due for {$a->for}';
$string['overrideforgroup'] = '{$a->base} is due for members of {$a->for}';
$string['overrides'] = 'Overrides';
$string['override_options'] = 'Override options';
$string['override_users'] = 'Affected users';
$string['paste'] = 'Paste';
$string['pluginadministration'] = 'VPL administration';
$string['pluginname'] = 'Virtual programming lab';
$string['previoussubmissionslist'] = 'Previous submissions list';
$string['print'] = 'Print';
$string['privacy:metadata:vpl'] = 'Information of the activity';
$string['privacy:metadata:vpl_submissions'] = 'Information on the attempts/submissions and on its evaluation';
$string['privacy:metadata:vpl_editor_fontsize'] = 'The user preference for the font size of the IDE';
$string['privacy:metadata:vpl_acetheme'] = 'The user preference for the editor theme of the IDE';
$string['privacy:metadata:vpl_terminaltheme'] = 'The user preference for the terminal color combination';
$string['privacy:metadata:vpl:id'] = 'Activity identification number';
$string['privacy:metadata:vpl:name'] = 'Activity name';
$string['privacy:metadata:vpl:course'] = 'Course id';
$string['privacy:metadata:vpl:shortdescription'] = 'Activity short description';
$string['privacy:metadata:vpl:startdate'] = 'Start date of the activity';
$string['privacy:metadata:vpl:duedate'] = 'Due date of the activity';
$string['privacy:metadata:vpl:grade'] = 'Activity grade';
$string['privacy:metadata:vpl:reductionbyevaluation'] = 'Penalization on the mark for each student request of automatic evaluation';
$string['privacy:metadata:vpl:freeevaluations'] = 'Number of free automatic evaluations (without penalization)';
$string['privacy:metadata:vpl_submissions:userid'] = 'User DB id';
$string['privacy:metadata:vpl_submissions:groupid'] = 'Group DB id';
$string['privacy:metadata:vpl_submissions:datesubmitted'] = 'Date and time of submission';
$string['privacy:metadata:vpl_submissions:studentcomments'] = 'Comments written by the student about the submission';
$string['privacy:metadata:vpl_submissions:nevaluations'] = 'Number of requested automatic evaluation by the student until this submission';
$string['privacy:metadata:vpl_submissions:dategraded'] = 'Date and time of the evaluation of the submission';
$string['privacy:metadata:vpl_submissions:grade'] = 'The mark for this submission. This value may no match the value in the grade book.';
$string['privacy:metadata:vpl_submissions:graderid'] = 'grader user DB id';
$string['privacy:metadata:vpl_submissions:gradercomments'] = 'Comments of the grader about this submission';
$string['privacy:metadata:vpl_assigned_variations'] = 'Information of the activity variation assigned, if any';
$string['privacy:metadata:vpl_assigned_variations:userid'] = 'User DB id.';
$string['privacy:metadata:vpl_assigned_variations:vplid'] = 'VPL DB id';
$string['privacy:metadata:vpl_assigned_variations:description'] = 'Description of the assigned variation';
$string['privacy:metadata:vpl_assigned_overrides'] = 'Information of the activity settings overrides assigned, if any';
$string['privacy:metadata:vpl_assigned_overrides:vplid'] = 'VPL DB id';
$string['privacy:metadata:vpl_assigned_overrides:userid'] = 'User DB id';
$string['privacy:metadata:vpl_assigned_overrides:overrideid'] = 'Assigned override id';
$string['privacy:metadata:vpl_running_processes'] = 'Information of user\'s running processes on this activity ';
$string['privacy:metadata:vpl_running_processes:userid'] = 'User DB id.';
$string['privacy:metadata:vpl_running_processes:vplid'] = 'VPL DB id';
$string['privacy:metadata:vpl_running_processes:server'] = 'Server that runs the task';
$string['privacy:metadata:vpl_running_processes:starttime'] = 'Date the task starts running';
$string['privacy:overridepath'] = 'assigned_override';
$string['privacy:submissionpath'] = 'submission_{$a}';
$string['privacy:variationpath'] = 'assigned_variation';
$string['privacy:runningprocesspath'] = 'running_process_{$a}';
$string['proposedgrade'] = 'Proposed grade: {$a}';
$string['proxy'] = 'proxy';
$string['proxy_description'] = 'Proxy from Moodle to execution servers';
$string['redo'] = 'Redo';
$string['reductionbyevaluation'] = "Reduction by automatic evaluation";
$string['reductionbyevaluation_help'] = "Reduce final score by a value or percentage for each automatic evaluation requested by the student";
$string['regularscreen'] = 'Regular screen';
$string['removegrade'] = 'Remove grade';
$string['removebreakpoint'] = 'Remove breakpoint';
$string['rename'] = 'Rename';
$string['rename_file'] = 'Rename file';
$string['replace_find'] = 'Replace/Find';
$string['replacenewer'] = "A newer version was already saved.\nDo you want to replace the newer version with this one?";
$string['requestedfiles'] = 'Requested files';
$string['requirednet'] = 'Allowed submission from net';
$string['requiredpassword'] = 'A password is required';
$string['resetfiles'] = 'Reset files';
$string['resetvpl'] = 'Reset {$a}';
$string['resourcelimits'] = 'Resources limits';
$string['restrictededitor'] = 'Disable external file upload, paste and drop external content';
$string['retrieve'] = 'Retrieve results';
$string['run'] = 'Run';
$string['running'] = 'Running';
$string['runscript'] = 'Run script';
$string['runscript_help'] = 'Select the run script to use in this activity';
$string['save'] = 'Save';
$string['save'] = 'Save';
$string['savecontinue'] = 'Save and continue';
$string['saved'] = 'Saved';
$string['savedfile'] = "The '{\$a}' file has been saved";
$string['saveforotheruser'] = "You are saving a submission for other user, are you sure?";
$string['saveoptions'] = 'save options';
$string['saving'] = 'Saving';
$string['scanactivity'] = 'Activity';
$string['scandirectory'] = 'Directory';
$string['scanningdir'] = 'Scanning directory ...';
$string['scanoptions'] = 'Scan options';
$string['scanother'] = 'Scan similarities in added sources';
$string['scanzipfile'] = 'Zip file';
$string['sebkeys'] = 'SEB exam Key/s';
$string['sebkeys_help'] = 'SEB exam key(s) (max 3) obtained from .seb file<br>It is more reliable than only browser check.<br>https://safeexambrowser.org';
$string['sebrequired'] = 'SEB browser required';
$string['sebrequired_help'] = 'Using SEB browser properly configured is required';
$string['select_all'] = 'Select all';
$string['selectbreakpoint'] = 'Select breakpoint';
$string['server'] = 'Server';
$string['serverexecutionerror'] = 'Server execution error';
$string['shortdescription'] = 'Short description';
$string['shortcuts'] = 'Keyboard shortcuts';
$string['similarity'] = 'Similarity';
$string['similarto'] = 'Similar to';
$string['startdate'] = 'Available from';
$string['starting'] = 'Starting';
$string['submission'] = 'Submission';
$string['submissionperiod'] = 'Submission period';
$string['submissionrestrictions'] = 'Submission restrictions';
$string['submissions'] = 'Submissions';
$string['submissionselection'] = 'Submission selection';
$string['submissionslist'] = 'Submissions list';
$string['submissionview'] = 'Submission view';
$string['submittedby'] = 'Submitted by {$a}';
$string['submittedon'] = 'Submitted on';
$string['submittedonp'] = 'Submitted on {$a}';
$string['sureresetfiles'] = 'Do you want to lost all your work and reset the files to its original state?';
$string['test'] = 'Test activity';
$string['testcases'] = 'Test cases';
$string['timelimited'] = 'Time limited';
$string['timeleft'] = 'Time left';
$string['timeout'] = 'Timeout';
$string['timespent'] = 'Time spent';
$string['timespent_help'] = 'Time spent in this activity based on the saved versions<br>The bar graph shows the number of students per time range.';
$string['timeunlimited'] = 'Time unlimited';
$string['totalnumberoferrors'] = "Errors";
$string['undo'] = 'Undo';
$string['unexpected_file_name'] = "Incorrect file name: expected '{\$a->expected}' and found '{\$a->found}'";
$string['unzipping'] = 'Unzipping ...';
$string['update'] = 'Update';
$string['updating'] = 'Updating';
$string['uploadfile'] = 'Upload file';
$string['use_xmlrpc'] = 'Use XML-RPC';
$string['use_xmlrpc_description'] = 'If set, the system will use the old XML-RPC protocol instead of JSON-RPC to communicate with the vpl-jail-servers. Set this option if you are using a vpl-jail-servers with a version previous to V3.0.0.';
$string['usevariations'] = 'Use variations';
$string['usewatermarks'] = 'Use watermarks';
$string['usewatermarks_description'] = 'Adds watermarks to student\'s files (only to supported languages)';
$string['variation'] = 'Variation {$a}';
$string['variation_options'] = 'Variation options';
$string['variations'] = 'Variations';
$string['variations_unused'] = 'This activity has variations, but are disabled';
$string['variationtitle'] = 'Variation title';
$string['varidentification'] = 'Identification';
$string['visiblegrade'] = 'Visible';
$string['vpl:addinstance'] = 'Add new vpl instances';
$string['vpl:grade'] = 'Grade VPL assignment';
$string['vpl:manage'] = 'Manage VPL assignment';
$string['vpl:setjails'] = 'Set execution servers for particular VPL instances';
$string['vpl:similarity'] = 'Search VPL assignment similarity';
$string['vpl:submit'] = 'Submit VPL assignment';
$string['vpl:view'] = 'View full VPL assignment description';
$string['vpl'] = 'Virtual Programming Lab';
$string['VPL_COMPILATIONFAILED'] = 'The compilation or preparation of execution has failed';
$string['vpl_debug.sh'] = 'This script prepares the debugging';
$string['vpl_evaluate.cases'] = 'Test cases for evaluation';
$string['vpl_evaluate.sh'] = 'This script prepares the evaluation';
$string['vpl_run.sh'] = 'This script prepares the execution';
$string['workingperiods'] = 'Working periods';
$string['worktype'] = 'Type of work';
$string['websocket_protocol'] = 'WebSocket protocol';
$string['websocket_protocol_description'] = 'Type of WebSocket protocol (ws:// or wss://) used by the browser to connect to execution servers.';
$string['always_use_wss'] = 'Always use encrypted (wss) websocket protocol';
$string['always_use_ws'] = 'Always use unencrypted (ws) websocket protocol';
$string['depends_on_https'] = 'Use ws or wss depending on if using http or https';

$string['basic'] = 'Basic';
$string['intermediate'] = 'Intermediate';
$string['advanced'] = 'Advanced';
$string['variables'] = 'Variables';
$string['operatorsvalues'] = 'Operators/Values';
$string['control'] = 'Control';
$string['inputoutput'] = 'Input/Output';
$string['functions'] = 'Functions';
$string['lists'] = 'Lists';
$string['math'] = 'Math';
$string['text'] = 'Text';
$string['start'] = 'Start';
$string['startanimate'] = 'Start animate';
$string['stop'] = 'Stop';
$string['pause'] = 'Pause';
$string['resume'] = 'Resume';
$string['step'] = 'Step';

$string['check_jail_servers_help'] = "<p>This page check and show the status of execution servers used
for this activity.</p>";
$string['executionfiles_help'] = '<p>Here you set the files that are needed to prepare the execution,
debug or assessment of a submission. This includes scripting files, program test files and data files.</p>
<p>If you don\'t set script files for run or debug submissions, the system
will resolve the language you use (based on file name extensions) and use a
predefined script.';
$string['executionoptions_help'] = '<p>Various execution options are set in this page</p>
<ul>
<li><b>Based on</b>: sets other VPL instance from which some features are imported:
<ul><li>Execution files (concatenating the predefined scripting files)</li>
<li>Limits for the execution resources.</li>
<li>Variations, that are concatenating to generate multivariations.</li>
<li>Maximun length for each file to be uploaded with the submission</li>
</ul>
</li>
<li><b>Run</b>, <b>Debug</b> and <b>Evalaute</b>: must be set to \'Yes\' if the corresponding action can be executed when editing the submission. This affects to the students only, users with  capability of grading can always execute these actions.</li>
<li><b>Evaluate just on submission</b>: the submission is evaluated automatically when it is uploaded.</li>
<li><b>Automatic grading</b>: if the evaluation result includes grading codes, they are used to set the grade automatically.</li>
</ul>';
$string['fulldescription_help'] = '<p>You must write here a full description for the activity.</p>
<p>If you don\'t write anything here, the short description is shown instead.</p>
<p>If you want to evaluate automatically, the interfaces for the assignments must be detailed and non-ambiguous.</p>';
$string['keepfiles_help'] = '<p>Due to security issues, the files added as &quot;Execution files&quot; are deleted before running the file vpl_execution.</p>
If any of those files is needed during the execution (by example, to be used as test data), it must be marked here.';
$string['local_jail_servers_help'] = '<p>Here you can set the local execution servers added for this activity and those
that are based on it.</p>
<p>Enter the full URL of a server on each line. You can use blank lines
and comments starting the line with "#".</p>
<p>This activity will use as execution server list: the servers sets here
plus the server list set in the "based on" activity
plus the list of common execution servers.
If you want to prevent this activity and derived ones
from using other servers, then you have to add a line
containing "end_of_jails" at the end of the server list.
</p>';
$string['modulename_help'] = '<p>VPL is a activity module for Moodle that manage programming assignments and whose salient features are:
</p>
<ul>
<li>Enable to edit the programs source code in the browser</li>
<li>Students can run interactively programs in the browser</li>
<li>You can run tests to review the programs.</li>
<li>Allows searching for similarity between files.</li>
<li>Allows setting editing restrictions and avoiding external text pasting.</li>
</ul>
<p><a href="http://vpl.dis.ulpgc.es">Virtual Programming lab Home Page</a></p>';
$string['modulename_link'] = 'mod/vpl/view';
$string['override_help'] = 'If "Override" is checked, this setting will be overriden with selected value for affected users.';
$string['override_users_help'] = 'One user/group can only be affected to one override set.<br>
If a user is affected to one set and one group he is a member of is affected to another, then by-user affectation prevails.<br>
If a user is a member of several groups affected to several sets, the first one in the table prevails.';
$string['overrides_help'] = 'A set of settings can be overriden for an activity. These settings will override activity settings for affected users and groups.';
$string['requestedfiles_help'] = '<p>Here you set names and its initial content up for the requested files to the max number of files that was set in the basic description of the activity.</p>
<p>If you don\'t set names for whole number of files, the unnamed files are optional and can have any name.</p>
<p>You also can add contents to the requested files, so these contents will be available the first time that they will be opened with the editor, if no previous submission exists.</p>';
$string['resourcelimits_help'] = '<p>You can set limits for the execution time, the memory used, the execution files sizes and the number of processes to be executed simultaneously.</p>
<p>These limits are used when running the scripting files vpl_run.sh, vpl_debug.sh and vpl_evaluate.sh and the file vpl_execution built by them.</p>
<p>If this activity is based on other activity, the limits can be affected by those set in the base activity and its ancestors or in the global configuration of the module.</p>';
$string['testcases_help'] = 'This feature allows to run the student program and check its output for a given input. To set up the evaluation cases you must populate the file &quot;vpl_evaluate.cases&quot;.<br>
The file "vpl_evaluate.cases" has the following format:<br>
<ul>
<li> "<b>case </b>= Description of case": Set an start of test case definition.</li>
<li> "<b>input </b>= text": can use several lines. Ends with other instruction.</li>
<li> "<b>output </b>= text": can use several lines. Ends with other instruction. A case can have differents correct output. There are three types of output: numbers, text and exact test:
<ul>
<li> <b>number</b>: defined as sequence of numbers (integers and floats). Only numbers in the output are checked, other text are ignored. Floats are checked with tolerance</li>
<li> <b>text</b>: defined as text without double quote. Only words are checked and the rest of chars are ignored, the comparation is case-insensitive </li>
<li> <b>exact text</b>: defined as text into double quote. The exact match is used to test the output.</li>
</ul>
</li>
<li> "<b>grade reduction</b> = [value|percentage%]" : By default an error reduces student\'s grade (starts with maxgrade) by (grade_range/number of cases) but with this instruction
you can change the reduction value or percentage.</li>
</ul>';
$string['variations_help'] = '<p>A set of variations can be defined for an activity. These variations are randomly assigned to the students.</p>
<p>Here you can indicate if this activity has variations, put a title for the set of variations, and to add the desired variations.</p>
<p>Each variation has an identification code and a description. The identification code is used by the <b>vpl_enviroment.sh</b> file to pass
the variation assigned to each student to the script files. The description, formatted in HTML, is shown to the students that have assigned
the corresponding variation.</p>';
