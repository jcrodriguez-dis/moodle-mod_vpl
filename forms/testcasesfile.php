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
 * Edit test cases' file
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
require_once(dirname(__FILE__).'/../editor/editor_utility.php');

require_login();
$id = required_param( 'id', PARAM_INT );

$vpl = new mod_vpl( $id );

$vpl->prepare_page( 'forms/testcasesfile.php', [
        'id' => $id,
] );

$vpl->require_capability( VPL_MANAGE_CAPABILITY );

$options = [];
$options['restrictededitor'] = false;
$options['save'] = true;
$options['run'] = false;
$options['debug'] = false;
$options['evaluate'] = false;
$options['ajaxurl'] = "testcasesfile.json.php?id={$id}&action=";
$options['download'] = "../views/downloadexecutionfiles.php?id={$id}";
$options['resetfiles'] = false;
$options['showparentfiles'] = false;
$options['showparentfilesurl'] = null;
$options['minfiles'] = 1;
$options['maxfiles'] = 1;
$options['saved'] = true;
$options['readOnlyFiles'] = [];

$evaluatorname = $vpl->get_effective_setting('evaluator');
if (!empty($evaluatorname)) {
    $evaluator = \mod_vpl\plugininfo\vplevaluator::get_evaluator($evaluatorname);
    $options['maxfiles'] = count($evaluator->get_test_files());
}

vpl_editor_util::generate_requires($vpl, $options);

$vpl->print_header( get_string( 'testcases', VPL ) );
$vpl->print_heading_with_help( 'testcases' );

echo \mod_vpl\plugininfo\vplevaluator::get_printable_evaluator_help_link($vpl);
vpl_editor_util::print_tag();
vpl_editor_util::print_js_i18n();
vpl_editor_util::print_js_description($vpl, $USER->id);

$vpl->print_footer_simple();
