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
 * Concatenated execution files for a VPL
 *
 * @package mod_vpl
 * @copyright 2024 Astor Bizard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Astor Blizard
 */

use core\output\html_writer;

require_once(__DIR__ . '/../../../config.php');

require_login();

global $CFG, $PAGE, $OUTPUT;
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
require_once($CFG->dirroot . '/mod/vpl/vpl_submission_CE.class.php');
require_once($CFG->dirroot . '/mod/vpl/views/sh_factory.class.php');

$id = required_param('id', PARAM_INT);

$vpl = new mod_vpl($id);
$vplinstance = $vpl->get_instance();
$context = context_module::instance($id);

$PAGE->set_url('/mod/vpl/views/concatexecfiles.php', [ 'id' => $id ]);
$PAGE->set_context($context);
$PAGE->set_cm($vpl->get_course_module());
$PAGE->set_pagelayout('popup');
$pagetitle = $vpl->get_printable_name() . ' - ' . get_string('concatenatedscripts', 'mod_vpl');
$PAGE->set_title($pagetitle);

require_capability(VPL_MANAGE_CAPABILITY, $context);

// Build arrays of defined scripts in each VPL in the basedon chain.
$fgm = $vpl->get_execution_fgm();
$definedscripts = [
        'vpl_run.sh' => [ [ $id => $fgm->getfiledata('vpl_run.sh') ] ],
        'vpl_debug.sh' => [ [ $id => $fgm->getfiledata('vpl_debug.sh') ] ],
        'vpl_evaluate.sh' => [ [ $id => $fgm->getfiledata('vpl_evaluate.sh') ] ],
        'vpl_evaluate.cases' => [ [ $id => $fgm->getfiledata('vpl_evaluate.cases') ] ],
];

$currentinstance = $vplinstance;
$basedons = [ $currentinstance->id => $vpl ];
while ($currentinstance->basedon) {
    if (isset($basedons[$currentinstance->basedon])) {
        throw new moodle_exception('error:recursivedefinition', 'mod_vpl');
    }
    $currentvpl = new mod_vpl(null, $currentinstance->basedon);
    $basedons[$currentinstance->basedon] = $currentvpl;
    $currentinstance = $currentvpl->get_instance();
    $currentfgm = $currentvpl->get_execution_fgm();
    foreach (array_keys($definedscripts) as $scriptname) {
        $definedscripts[$scriptname][] = [ $currentvpl->get_course_module()->id => $currentfgm->getfiledata($scriptname) ];
    }
}

// Retrieve default scripts.
$data = new stdClass();
$data->runscript = $vpl->get_closest_set_field_in_base_chain('runscript', '');
$data->debugscript = $vpl->get_closest_set_field_in_base_chain('debugscript', '');
$pln = mod_vpl_submission_CE::get_pln($vpl->get_required_fgm()->getfilelist());
$defaultscripts = [
        'vpl_run.sh' => mod_vpl_submission_CE::get_script('run', $pln, $data),
        'vpl_debug.sh' => mod_vpl_submission_CE::get_script('debug', $pln, $data),
        'vpl_evaluate.sh' => mod_vpl_submission_CE::get_script('evaluate', $pln, $data),
        'vpl_evaluate.cases' => '',
];

$finalscripts = [
        'vpl_run.sh' => [],
        'vpl_debug.sh' => [],
        'vpl_evaluate.sh' => [],
        'vpl_evaluate.cases' => [],
];

foreach ($defaultscripts as $filename => $defaultscript) {
    $concatenated = trim(implode('', array_column(array_map('array_values', array_reverse($definedscripts[$filename])), 0)));
    if ($concatenated > '') {
        // Use user-defined script (because user-defined script is not empty).
        if ($filename != 'vpl_evaluate.cases' && substr($concatenated, 0, 2) != '#!') { // Fixes script adding bash if no shebang.
            $finalscripts[$filename][] = [ -1 => "#!/bin/bash" ];
        }
        foreach (array_reverse($definedscripts[$filename]) as $scriptfragment) {
            foreach ($scriptfragment as $vplid => $filecontents) {
                if (trim($filecontents) > '') {
                    $finalscripts[$filename][] = [ $vplid => $filecontents ];
                }
            }
        }
    } else {
        if ($filename == 'vpl_evaluate.cases') {
            continue; // Do not add empty evaluate cases.
        }
        // User-defined script is empty, so use the default one.
        $finalscripts[$filename][] = [ 0 => $defaultscript ];
    }
}

vpl_sh_factory::include_js();
echo $OUTPUT->header();
echo $OUTPUT->heading_with_help($pagetitle, 'concatenatedscripts', 'mod_vpl');
if (count($basedons) > 1) {
    echo '<div class="text-center mx-2" style="width:max-content">
              <div>' . get_string('inheritancechain', 'mod_vpl') . '</div>' .
              implode('<i class="fa fa-caret-up d-block"></i>', array_map(function ($chainvpl) {
                  return html_writer::link(
                      new moodle_url('/mod/vpl/view.php?id=' . $chainvpl->get_course_module()->id),
                      $chainvpl->get_printable_name()
                  );
              }, array_reverse($basedons))) .
          '</div>';
}

foreach ($finalscripts as $filename => $files) {
    if (empty($files)) {
        continue;
    }
    $printer = vpl_sh_factory::get_object('ace');
    echo '<details open class="my-2"><summary>';
    echo html_writer::tag('h4', s($filename), [ 'class' => 'my-2 d-inline-block' ]);
    echo '</summary>';
    foreach ($files as $scriptfragment) {
        foreach ($scriptfragment as $vplid => $filecontents) {
            if ($vplid > 0) {
                if ($vplid != $id) {
                    $filevpl = new mod_vpl($vplid);
                    $text = get_string('fromvpl', 'mod_vpl', $filevpl->get_printable_name());
                    if ($filevpl->has_capability(VPL_VIEW_CAPABILITY)) {
                        $urlview = new moodle_url('/mod/vpl/view.php?id=' . $filevpl->get_course_module()->id);
                        $text .= ' (<a href="' . $urlview . '">' . get_string('view') . '</a>)';
                    }
                    if ($filevpl->has_capability(VPL_MANAGE_CAPABILITY)) {
                        $urledit = new moodle_url('/mod/vpl/forms/executionfiles.php?id=' . $vplid);
                        $text .= ' (<a href="' . $urledit . '">' . get_string('edit') . '</a>)';
                    }
                } else {
                    $text = get_string('fromthisvpl', 'mod_vpl', $vpl->get_printable_name());
                }
            } else if ($vplid == -1) {
                $text = get_string('generatedshebang', 'mod_vpl');
            } else { // Then $vplid == 0.
                $type = substr($filename, 4, -3); // Decompose into vpl_{$type}.sh.
                if ($type === 'evaluate') {
                    $text = get_string('defaultevaluatescript', 'mod_vpl');
                } else {
                    if (empty($data->{$type . 'script'})) {
                        $details = [
                            'pln' => $pln,
                            'origin' => get_string('determinedfromrequiredfiles', 'mod_vpl'),
                        ];
                    } else {
                        $details = [
                            'pln' => $data->{$type . 'script'},
                            'origin' => get_string('setbyexecutionoptions', 'mod_vpl'),
                        ];
                    }
                    $text = get_string('defaultscriptforlang', 'mod_vpl', $details);
                }
            }
            echo '<div class="border" style="margin-bottom:1px">
                      <div class="mx-1 mb-1 small">' . $text . '</div>
                      <div class="nomargin">';
            $printer->print_file($filename, $filecontents, true, count(explode("\n", $filecontents)), false);
            echo '    </div>
                  </div>';
        }
    }
    echo '</details>';
}
vpl_sh_factory::syntaxhighlight();

echo $OUTPUT->footer();
