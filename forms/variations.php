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
 * Variation definitions form
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

use mod_vpl\variation\form;
use mod_vpl\variation\option_form;

require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/../vpl.class.php');
global $CFG, $DB;
require_once($CFG->libdir . '/formslib.php');

/**
 * Returns actions for a variation in HTML
 *
 * @param object $variation DB register.
 * @param object $cmid of the VPL activity.
 * @param int $number secuential number of the variation in the page
 * @return string HTML
 */
function get_variation_actions_html($variation, $cmid, $number) {
    global $OUTPUT;
    $variationclass = "vpl_variation_{$number}";
    $anchor = "vpl_variation_{$cmid}_{$number}";
    $separator = "<hr id='$anchor'>";
    $parms = ['number' => $number, 'identification' => s($variation->identification)];
    $variationidentification = get_string('variation_n_i', VPL, $parms);

    $parms = ['id' => $cmid, 'varid' => $variation->id, 'number' => $number, 'action' => 'edit'];
    $url = new moodle_url('/mod/vpl/forms/variations.php', $parms);
    $btext = get_string('edit');
    $editbutton = html_writer::link($url, $btext, ['class' => 'btn btn-primary']);

    $parms = ['id' => $cmid, 'varid' => $variation->id, 'number' => $number, 'action' => 'delete'];
    $url = new moodle_url('/mod/vpl/forms/variations.php', $parms);
    $btext = get_string('delete');
    $deletebutton = html_writer::link($url, $btext, ['class' => 'btn btn-danger']);

    $variationcontent = $OUTPUT->box($variation->description);
    $html = "$separator\n";
    $variationheader = "<b>$variationidentification</b> $editbutton $deletebutton\n";
    $html .= html_writer::div($variationheader, $variationclass);
    $html .= $variationcontent;
    return $html;
}

/**
 * Returns HTML link to show a variation in the variation page
 *
 * @param object $variation DB register.
 * @param object $cmid of the VPL activity.
 * @param int $number secuential number of the variation in the page
 * @return string HTML
 */
function get_link_variation_html($variation, $cmid, $number) {
    $parms = ['id' => $cmid];
    $anchor = "vpl_variation_{$cmid}_{$number}";
    $url = new moodle_url('/mod/vpl/forms/variations.php', $parms, $anchor);
    $parms = ['number' => $number, 'identification' => s($variation->identification)];
    $btext = get_string('variation_n_i', VPL, $parms);
    return html_writer::link($url, $btext, ['class' => 'btn btn-secondary']);
}
/**
 * Returns HTML link to add a new variation for this activity
 *
 * @param object $cmid of the VPL activity.
 * @return string HTML
 */
function get_add_variation_html($cmid) {
    $parms = ['id' => $cmid, 'varid' => -1, 'number' => 0, 'action' => 'add'];
    $url = new moodle_url('/mod/vpl/forms/variations.php', $parms);
    $btext = get_string('add');
    $html = html_writer::link($url, $btext, ['class' => 'btn btn-primary']);
    return $html;
}

/**
 * Sets the variation data in the given form.
 *
 * @param object $mform form to set the data in.
 * @param int $vplid ID of the VPL activity.
 * @param int $varid ID of the variation.
 * @return void
 */
function set_variation_data($mform, $vplid, $varid) {
    global $DB;
    $variation = $DB->get_record(VPL_VARIATIONS, ['id' => $varid, 'vpl' => $vplid]);
    if ($variation) {
        $variation->varid = $variation->id;
        $variation->description = ['text' => $variation->description];
        $mform->set_data($variation);
    } else {
        throw new moodle_exception('error:inconsistency', 'mod_vpl', '', VPL_VARIATIONS);
    }
}


/**
 * Prints HTML showing the link to add a new variation for this activity
 *
 * @param object $form form to show
 * @param object $vpl current VPL activity
 */
function print_basic_html($form, $vpl) {
    global $DB;
    $form->set_data($vpl->get_instance());
    $form->display();
    $list = $DB->get_records('vpl_variations', ['vpl' => $vpl->get_instance()->id]);
    // Show variations.
    $cmid = $vpl->get_course_module()->id;
    echo get_add_variation_html($cmid);
    $number = 1;
    foreach ($list as $variation) {
        echo ' ' . get_link_variation_html($variation, $cmid, $number);
        $number++;
    }
    $number = 1;
    foreach ($list as $variation) {
        echo get_variation_actions_html($variation, $cmid, $number);
        $number++;
    }
}

$cmid = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'vpl');
require_login($course, true, $cm);

$vpl = new mod_vpl($cmid);
$vpl->prepare_page('forms/variations.php', ['id' => $cmid]);

$varid = optional_param('varid', -13, PARAM_INT);
$canceled = optional_param('cancel', '', PARAM_TEXT) !== '';
$variationaction = optional_param('action', '', PARAM_TEXT);

vpl_include_jsfile('hideshow.js');
$vplid = $vpl->get_instance()->id;
$vpl->require_capability(VPL_MANAGE_CAPABILITY);
$href = vpl_mod_href('forms/variations.php', 'id', $cmid);
$vpl->print_header(get_string('variations', VPL));
$vpl->print_heading_with_help('variations');

if ($canceled) {
    vpl_notice(get_string('cancelled'));
    $form = new option_form($href);
    if ($form->is_cancelled()) {
        $href = vpl_mod_href('forms/variations.php', 'id', $cmid, 'cancel', 1);
        vpl_inmediate_redirect($href);
    }
    print_basic_html($form, $vpl);
} else if ($variationaction === 'add') {
    $mform = new form($href, 'add', -1);
    $fromform = $mform->get_data();
    if ($fromform) {
        $fromform->vpl = $vplid;
        unset($fromform->id);
        $fromform->description = $fromform->description['text'];
        vpl_truncate_variations($fromform);
        if ($vid = $DB->insert_record(VPL_VARIATIONS, $fromform)) {
            \mod_vpl\event\variation_added::logvpl($vpl, $vid);
        } else {
            throw new moodle_exception('error:recordnotinserted', 'mod_vpl', '', VPL_VARIATIONS);
        }
        vpl_notice(get_string('saved', VPL));
        $form = new option_form($href);
        print_basic_html($form, $vpl);
    } else {
        $mform->display();
    }
} else if ($variationaction === 'edit') {
    $mform = new form($href, 'edit', $varid);
    $fromform = $mform->get_data();
    if ($fromform) {
        if ($DB->get_record(VPL_VARIATIONS, ['id' => $fromform->varid, 'vpl' => $vplid])) { // Check consistence.
            $fromform->vpl = $vplid;
            $fromform->id = $fromform->varid;
            $fromform->description = $fromform->description['text'];
            vpl_truncate_variations($fromform);
            $DB->update_record(VPL_VARIATIONS, $fromform);
            \mod_vpl\event\variation_updated::logvpl($vpl, $fromform->varid);
            vpl_notice(get_string('updated', '', $fromform->identification));
            $form = new option_form($href);
            print_basic_html($form, $vpl);
        } else {
            throw new moodle_exception('error:inconsistency', 'mod_vpl', '', VPL_VARIATIONS);
        }
    } else {
        set_variation_data($mform, $vplid, $varid);
        $mform->display();
    }
} else if ($variationaction === 'delete') {
    $mform = new form($href, 'delete', $varid);
    $fromform = $mform->get_data();
    if ($fromform) {
        if (isset($_POST['delete'])) { // Deletes variation and its assignned variations.
            if ($DB->delete_records(VPL_VARIATIONS, ['id' => $fromform->varid, 'vpl' => $vplid])) {
                \mod_vpl\event\variation_deleted::logvpl($vpl, $fromform->varid);
                $DB->delete_records(VPL_ASSIGNED_VARIATIONS, ['variation' => $fromform->varid]);
                vpl_notice(get_string('deleted'));
                $form = new option_form($href);
                print_basic_html($form, $vpl);
            } else {
                throw new moodle_exception('error:recordnotdeleted', 'mod_vpl', '', VPL_VARIATIONS);
            }
        } // Handle deletion of a variation.
    } else {
        set_variation_data($mform, $vplid, $varid);
        $mform->display();
    }
} else {
    // Default action: display the options form.
    $form = new option_form($href);
    if ($varid == -13) { // No variation, basic form.
        if ($fromform = $form->get_data()) {
            vpl_truncate_string($fromform->variationtitle, 255);
            $instance = $vpl->get_instance();
            $instance->usevariations = $fromform->usevariations;
            $instance->variationtitle = $fromform->variationtitle;
            $vpl->update();
            \mod_vpl\event\vpl_variation_updated::log($vpl);
            vpl_notice(get_string('updated', '', $instance->variationtitle));
        }
    }
    print_basic_html($form, $vpl);
}

$vpl->print_footer();
