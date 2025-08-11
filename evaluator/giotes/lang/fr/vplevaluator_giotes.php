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
 * GIOTES strings for the French language.
 *
 * @package vplevaluator_giotes
 * @copyright 2025 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */

/**
 * @var array $string
 */
$string['child_continued'] = 'Le processus enfant a été repris';
$string['child_terminated_by_signal'] = 'Processus enfant terminé par un signal : {$a->signal} ({$a->signum})';
$string['command_line_too_long'] = 'Ligne de commande trop longue : ligne tronquée';
$string['error_parameter_unknow'] = 'Erreur de syntaxe dans le fichier de cas (ligne : {$a}) : paramètre inconnu';
$string['error_text_out'] = 'Erreur de syntaxe dans le fichier de cas (ligne : {$a}) : texte en dehors d\'un paramètre ou d\'un commentaire';
$string['execution_file_not_found'] = 'Fichier d\'exécution introuvable : \'{$a}\'';
$string['fatal_errors'] = 'Erreurs fatales';
$string['forkpty_error'] = 'Erreur interne : erreur forkpty ({$a})';
$string['global_timeout'] = 'Temps d\'exécution global dépassé';
$string['internal_error'] = 'Erreur interne du test';
$string['no_test_cases'] = 'Aucun cas de test trouvé dans le fichier de cas';
$string['output_too_large'] = 'Sortie du programme trop volumineuse ({$a}Ko)';
$string['pluginname'] = 'Évaluateur GIOTES';
$string['privacy:metadata'] = 'L\'évaluateur GIOTES ne stocke aucune donnée personnelle.';
$string['program_terminated_by_signal'] = 'Programme terminé par un signal : {$a->signal} ({$a->signum})';
$string['program_terminated_by_unknown_reason'] = 'Programme terminé pour une raison inconnue : {$a}';
$string['stop_requested'] = 'Arrêt demandé par le système';
$string['term_signal'] = 'Dépassement du temps global de test (signal TERM reçu)';
$string['too_many_command_arguments'] = 'Trop d\'arguments en ligne de commande : paramètres tronqués';
$string['waitpid_error'] = 'Erreur interne : erreur waitpid ({$a})';
