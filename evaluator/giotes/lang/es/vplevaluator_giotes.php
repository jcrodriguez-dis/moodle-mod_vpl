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
 * GIOTES strings for the Spanish language.
 *
 * @package vplevaluator_giotes
 * @copyright 2025 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */

/**
 * @var array $string
 */
$string['child_continued'] = 'Proceso hijo continuado';
$string['child_terminated_by_signal'] = 'Proceso hijo terminado por señal: {$a->signal} ({$a->signum})';
$string['command_line_too_long'] = 'Línea de comandos demasiado larga: línea de comandos truncada';
$string['error_parameter_unknow'] = 'Error de sintaxis en el archivo de casos (línea:{$a}): parámetro desconocido';
$string['error_text_out'] = 'Error de sintaxis en el archivo de casos (línea:{$a}): texto fuera de parámetro o comentario';
$string['execution_file_not_found'] = 'Archivo de ejecución no encontrado: \'{$a}\'';
$string['fatal_errors'] = 'Errores fatales';
$string['forkpty_error'] = 'Error interno: error en forkpty ({$a})';
$string['global_timeout'] = 'Tiempo de espera global excedido';
$string['internal_error'] = 'Error interno en la prueba';
$string['no_test_cases'] = 'No se encontraron casos de prueba en el archivo de casos';
$string['output_too_large'] = 'Salida del programa demasiado grande ({$a}Kb)';
$string['pluginname'] = 'Evaluador GIOTES';
$string['privacy:metadata'] = 'El evaluador GIOTES no almacena datos personales.';
$string['program_terminated_by_signal'] = 'Programa terminado por señal: {$a->signal} ({$a->signum})';
$string['program_terminated_by_unknown_reason'] = 'Programa terminado por razón desconocida: {$a}';
$string['stop_requested'] = 'Parada solicitada por el sistema';
$string['term_signal'] = 'Tiempo de espera global de la prueba excedido (señal TERM recibida)';
$string['too_many_command_arguments'] = 'Demasiados argumentos de comando: parámetros truncados';
$string['waitpid_error'] = 'Error interno: error en waitpid ({$a})';
