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
 * GIOTES strings for the German language.
 *
 * @package vplevaluator_giotes
 * @copyright 2025 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */

/**
 * @var array $string
 */
$string['child_continued'] = 'Kindprozess wurde fortgesetzt';
$string['child_terminated_by_signal'] = 'Kindprozess durch Signal beendet: {$a->signal} ({$a->signum})';
$string['command_line_too_long'] = 'Befehlszeile zu lang: gekürzte Befehlszeile';
$string['error_parameter_unknow'] = 'Syntaxfehler in der Fallakte (Zeile:{$a}): unbekannter Parameter';
$string['error_text_out'] = 'Syntaxfehler in der Fallakte (Zeile:{$a}): Text außerhalb von Parameter oder Kommentar';
$string['execution_file_not_found'] = 'Ausführungsdatei nicht gefunden: \'{$a}\'';
$string['fatal_errors'] = 'Schwerwiegende Fehler';
$string['forkpty_error'] = 'Interner Fehler: forkpty-Fehler ({$a})';
$string['global_timeout'] = 'Globale Zeitüberschreitung';
$string['internal_error'] = 'Interner Testfehler';
$string['no_test_cases'] = 'Keine Testfälle in der Fallakte gefunden';
$string['output_too_large'] = 'Programmausgabe zu groß ({$a}Kb)';
$string['pluginname'] = 'GIOTES Bewerter';
$string['privacy:metadata'] = 'GIOTES Bewerter speichert keine personenbezogenen Daten.';
$string['program_terminated_by_signal'] = 'Programm durch Signal beendet: {$a->signal} ({$a->signum})';
$string['program_terminated_by_unknown_reason'] = 'Programm aus unbekanntem Grund beendet: {$a}';
$string['stop_requested'] = 'Stopp vom System angefordert';
$string['term_signal'] = 'Globale Testzeit überschritten (TERM-Signal empfangen)';
$string['too_many_command_arguments'] = 'Zu viele Befehlsargumente: gekürzte Parameter';
$string['waitpid_error'] = 'Interner Fehler: waitpid-Fehler ({$a})';
