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
 * GIOTES strings for the English language.
 *
 * @package vplevaluator_giotes
 * @copyright 2025 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */

/**
 * @var array $string
 */
$string['child_continued'] = 'Child process was continued';
$string['child_terminated_by_signal'] = 'Child terminated by signal: {$a->signal} ({$a->signum})';
$string['command_line_too_long'] = 'Command line too long: cutted command line';
$string['error_parameter_unknow'] = 'Syntax error in the cases file (line:{$a}): unknow parameter';
$string['error_text_out'] = 'Syntax error in the cases file (line:{$a}): text out of parameter or comment';
$string['execution_file_not_found'] = 'Execution file not found: \'{$a}\'';
$string['fatal_errors'] = 'Fatal errors';
$string['forkpty_error'] = 'Internal error: forkpty error ({$a})';
$string['global_timeout'] = 'Global timeout';
$string['internal_error'] = 'Internal test error';
$string['no_test_cases'] = 'No test cases found in the cases file';
$string['output_too_large'] = 'Program output too large ({$a}Kb)';
$string['pluginname'] = 'GIOTES evaluator';
$string['privacy:metadata'] = 'GIOTES evaluator does not store any personal data.';
$string['program_terminated_by_signal'] = 'Program terminated by signal: {$a->signal} ({$a->signum})';
$string['program_terminated_by_unknown_reason'] = 'Program terminated by unknown reason: {$a}';
$string['stop_requested'] = 'Stop requested by the system';
$string['term_signal'] = 'Global test timeout (TERM signal received)';
$string['too_many_command_arguments'] = 'Too many command arguments: cutted parameters';
$string['waitpid_error'] = 'Internal error: waitpid error ({$a})';
