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
 * SPRESAI strings for the Spanish language.
 *
 * @package vplevaluator_spresai
 * @copyright 2025 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */

/**
 * @var array $string
 */

$string['error_import_config'] = 'Cargando archivo config.py: {$a->error}';
$string['error_import_litellm'] = 'Cargando biblioteca LiteLLM: {$a->error}';
$string['error_invalid_mode'] = 'Modo inválido \'{$a->mode}\'. Debe ser uno de \'evaluate\', \'explain\', \'fix\', \'tip\'.';
$string['error_prompt_file_not_found'] = 'Archivo de prompt {$a->file} no encontrado y no se proporcionó prompt predeterminado.';
$string['error_contact_model'] = 'Contactando con el modelo de IA: {$a->error}';
$string['error_response_model'] = 'Respuesta del modelo: {$a->error}';
$string['error_unknown'] = 'Ocurrió un error desconocido e inesperado.';
$string['pluginname'] = 'Evaluador SPRESAI';
$string['privacy:metadata'] = 'El evaluador SPRESAI no almacena ningún dato personal.';
