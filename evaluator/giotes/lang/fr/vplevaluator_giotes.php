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
$string['pluginname'] = 'Evaluador GIOTES';
$string['error_parameter_unknow'] = 'Error de sintaxis en el archivo de casos (línea:{$a}): parámetro desconocido';
$string['no_test_cases'] = 'No se encontraron casos de prueba en el archivo de casos';
$string['error_text_out'] = 'Error de sintaxis en el archivo de casos (línea:{$a}): texto fuera de parámetro o comentario';
$string['global_timeout'] = 'Tiempo de espera global excedido';
$string['stop_requested'] = 'Parada solicitada por el sistema';
$string['fatal_errors'] = 'Errores fatales';
$string['output_too_large'] = 'Salida del programa demasiado grande ({$a}Kb)';
$string['command_line_too_long'] = 'Línea de comandos demasiado larga: línea de comandos truncada';
$string['too_many_command_arguments'] = 'Demasiados argumentos de comando: parámetros truncados';
$string['execution_file_not_found'] = 'Archivo de ejecución no encontrado: \'{$a}\'';
$string['forkpty_error'] = 'Error interno: error en forkpty ({$a})';
$string['program_terminated_by_signal'] = 'Programa terminado por señal: {$a->signal} ({$a->signum})';
$string['child_terminated_by_signal'] = 'Proceso hijo terminado por señal: {$a->signal} ({$a->signum})';
$string['child_continued'] = 'Proceso hijo continuado';
$string['program_terminated_by_unknown_reason'] = 'Programa terminado por razón desconocida: {$a}';
$string['waitpid_error'] = 'Error interno: error en waitpid ({$a})';
$string['term_signal'] = 'Tiempo de espera global de la prueba excedido (señal TERM recibida)';
$string['internal_error'] = 'Error interno en la prueba';
