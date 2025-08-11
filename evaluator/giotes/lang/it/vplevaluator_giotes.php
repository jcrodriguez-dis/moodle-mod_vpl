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
 * GIOTES strings for the Italian language.
 *
 * @package vplevaluator_giotes
 * @copyright 2025 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */

/**
 * @var array $string
 */
$string['child_continued'] = 'Il processo figlio è stato ripreso';
$string['child_terminated_by_signal'] = 'Processo figlio terminato dal segnale: {$a->signal} ({$a->signum})';
$string['command_line_too_long'] = 'Riga di comando troppo lunga: riga di comando troncata';
$string['error_parameter_unknow'] = 'Errore di sintassi nel file dei casi (linea:{$a}): parametro sconosciuto';
$string['error_text_out'] = 'Errore di sintassi nel file dei casi (linea:{$a}): testo fuori dal parametro o commento';
$string['execution_file_not_found'] = 'File di esecuzione non trovato: \'{$a}\'';
$string['fatal_errors'] = 'Errori fatali';
$string['forkpty_error'] = 'Errore interno: errore forkpty ({$a})';
$string['global_timeout'] = 'Timeout globale';
$string['internal_error'] = 'Errore interno del test';
$string['no_test_cases'] = 'Nessun caso di test trovato nel file dei casi';
$string['output_too_large'] = 'Output del programma troppo grande ({$a}Kb)';
$string['pluginname'] = 'Valutatore GIOTES';
$string['privacy:metadata'] = 'Il valutatore GIOTES non memorizza alcun dato personale.';
$string['program_terminated_by_signal'] = 'Programma terminato dal segnale: {$a->signal} ({$a->signum})';
$string['program_terminated_by_unknown_reason'] = 'Programma terminato per motivo sconosciuto: {$a}';
$string['stop_requested'] = 'Arresto richiesto dal sistema';
$string['term_signal'] = 'Timeout globale del test (segnale TERM ricevuto)';
$string['too_many_command_arguments'] = 'Troppi argomenti di comando: parametri troncati';
$string['waitpid_error'] = 'Errore interno: errore waitpid ({$a})';
