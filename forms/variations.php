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

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/../vpl.class.php');
global $CFG, $DB;
require_once($CFG->libdir.'/formslib.php');

/**
 * Class to define variation activation and variation title form
 */
class mod_vpl_variation_option_form extends moodleform {
    protected function definition() {
        $mform = & $this->_form;
        $mform->addElement( 'header', 'variation_options', get_string( 'variation_options', VPL ) );
        $mform->addElement( 'selectyesno', 'usevariations', get_string( 'usevariations', VPL ) );
        $mform->addElement( 'text', 'variationtitle', get_string( 'variationtitle', VPL ), [
                'size' => 60,
        ] );
        $mform->setType( 'variationtitle', PARAM_TEXT );
        $buttongroup = [];
        $buttongroup[] = $mform->createElement( 'submit', 'save', get_string( 'save', VPL ) );
        $buttongroup[] = $mform->createElement( 'submit', 'cancel', get_string( 'cancel' ) );
        $mform->addGroup( $buttongroup );
    }
}

/**
 * Class to define variation add and edit form
 */
class mod_vpl_variation_form extends moodleform {
    protected $varid;
    protected $number;
    // Parm $varid = -1 new variation.
    public function __construct($page, $number = 0, $varid = 0) {
        $this->number = $number;
        $this->varid = $varid;
        parent::__construct( $page );
    }
    protected function definition() {
        global $CFG;
        $mform = & $this->_form;
        if ($this->number > 0) {
            $title = get_string( 'variation_n', VPL, "{$this->number}" );
        } else {
            $title = get_string( 'add' );
        }
        $mform->addElement( 'header', 'variation', $title );
        $mform->addElement( 'hidden', 'varid', $this->varid );
        $mform->setType( 'varid', PARAM_INT );

        $mform->addElement( 'text', 'identification', get_string( 'varidentification', VPL ), [
                'size' => '20',
        ] );
        $mform->setDefault( 'identification', '' );
        $mform->setType( 'identification', PARAM_RAW );
        $fieldname = 'description'; // Allows multile editors in page.
        $mform->addElement('editor', $fieldname, get_string('description', VPL));
        $mform->setType($fieldname, PARAM_RAW);
        $mform->setDefault( $fieldname, '' );

        $buttongroup = [];
        $buttongroup[] = $mform->createElement( 'submit', 'save', get_string( 'save', VPL ) );
        $buttongroup[] = $mform->createElement( 'submit', 'cancel', get_string( 'cancel' ) );
        if ($this->number > 0) {
            $menssage = addslashes( get_string( 'delete' ) );
            $onclick = 'onclick="return confirm(\'' . $menssage . '\')"';
            $buttongroup[] = $mform->createElement( 'submit', 'delete', get_string( 'delete' ), $onclick );
        }
        $mform->addGroup( $buttongroup );
    }
}
/**
 * Returns HTML of a variation in the variation page
 *
 * @param object $variation DB register.
 * @param object $cmid of the VPL activity.
 * @param int $number secuential number of the variation in the page
 * @return string HTML
 */
function get_variation_with_edit_html($variation, $cmid, $number) {
    global $OUTPUT;
    $anchor = "vpl_variation_{$cmid}_{$number}";
    $parms = ['number' => $number, 'identification' => s($variation->identification)];
    $html = "<hr id='$anchor'><b>" . get_string( 'variation_n_i', VPL, $parms ) . '</b> ';
    $parms = ['id' => $cmid, 'varid' => $variation->id, 'number' => $number];
    $url = new moodle_url( '/mod/vpl/forms/variations.php', $parms);
    $btext = get_string('edit');
    $html .= ' ' . html_writer::link($url, $btext, ['class' => 'btn btn-primary']) . '<br>';
    $html .= $OUTPUT->box( $variation->description );
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
    global $OUTPUT;
    $parms = ['id' => $cmid];
    $anchor = "vpl_variation_{$cmid}_{$number}";
    $url = new moodle_url( '/mod/vpl/forms/variations.php', $parms, $anchor);
    $parms = ['number' => $number, 'identification' => s($variation->identification)];
    $btext = get_string( 'variation_n_i', VPL, $parms );
    return html_writer::link($url, $btext, ['class' => 'btn btn-secondary']);
}
/**
 * Returns HTML link to add a new variation for this activity
 *
 * @param object $cmid of the VPL activity.
 * @return string HTML
 */
function get_add_variation_html($cmid) {
    $parms = ['id' => $cmid, 'varid' => -1, 'number' => 0];
    $url = new moodle_url( '/mod/vpl/forms/variations.php', $parms);
    $btext = get_string('add');
    $html = html_writer::link($url, $btext, ['class' => 'btn btn-primary']);
    return $html;
}

/**
 * Prints HTML showing the link to add a new variation for this activity
 *
 * @param object $form form to show
 * @param object $vpl current VPL activity
 */
function print_basic_html($form, $vpl) {
    global $DB, $OUTPUT;
    $form->set_data($vpl->get_instance());
    $form->display();
    $list = $DB->get_records('vpl_variations', ['vpl' => $vpl->get_instance()->id]);
    // Show variations.
    $id = $vpl->get_course_module()->id;
    echo get_add_variation_html($id);
    $number = 1;
    foreach ($list as $variation) {
        echo ' ' . get_link_variation_html($variation, $id, $number);
        $number ++;
    }
    $number = 1;
    foreach ($list as $variation) {
        echo get_variation_with_edit_html($variation, $id, $number);
        $number ++;
    }
}

require_login();

$id = required_param('id', PARAM_INT);
$varid = optional_param('varid', -13, PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('forms/variations.php', ['id' => $id]);
vpl_include_jsfile('hideshow.js');
$vplid = $vpl->get_instance()->id;
$vpl->require_capability(VPL_MANAGE_CAPABILITY);
$href = vpl_mod_href('forms/variations.php', 'id', $id);
$vpl->print_header(get_string('variations', VPL));
$vpl->print_heading_with_help('variations');
$form = new mod_vpl_variation_option_form($href);
// Generate default form and check for action.
if ($varid == -13) { // No variation, basic form.
    if (isset($_POST['cancel'])) {
        vpl_notice(get_string('cancelled'));
        print_basic_html($form, $vpl);
    } else if ($fromform = $form->get_data()) {
        vpl_truncate_string( $fromform->variationtitle, 255 );
        $instance = $vpl->get_instance();
        $instance->usevariations = $fromform->usevariations;
        $instance->variationtitle = $fromform->variationtitle;
        $vpl->update();
        \mod_vpl\event\vpl_variation_updated::log($vpl);
        vpl_notice(get_string('updated', '', $instance->variationtitle));
        print_basic_html($form, $vpl);
    } else {
        print_basic_html($form, $vpl);
    }
} else if ($varid == -1) { // Add new variation.
    $mform = new mod_vpl_variation_form($href, 0, -1);
    $fromform = $mform->get_data();
    if ($fromform) {
        if (isset($_POST['cancel'])) {
            vpl_notice(get_string('cancelled'));
            print_basic_html($form, $vpl);
        } else {
            $fromform->vpl = $vplid;
            unset( $fromform->id );
            $fromform->description = $fromform->description['text'];
            vpl_truncate_variations( $fromform );
            if ($vid = $DB->insert_record(VPL_VARIATIONS, $fromform )) {
                \mod_vpl\event\variation_added::logvpl($vpl, $vid );
            } else {
                throw new moodle_exception('error:recordnotinserted', 'mod_vpl', '', VPL_VARIATIONS);
            }
            vpl_notice(get_string('saved', VPL));
            print_basic_html($form, $vpl);
        }
    } else {
        $mform->display();
    }
} else {
    $number = optional_param('number', 0, PARAM_INT );
    $mform = new mod_vpl_variation_form($href, $number, $varid);
    $fromform = $mform->get_data();
    if ($fromform) {
        if (isset($_POST['cancel'])) {
            vpl_notice(get_string('cancelled'));
            print_basic_html($form, $vpl);
        } else if ( isset($_POST['delete']) ) { // Deletes variation and its assignned variations.
            if ($DB->delete_records(VPL_VARIATIONS, ['id' => $fromform->varid, 'vpl' => $vplid])) {
                \mod_vpl\event\variation_deleted::logvpl( $vpl, $fromform->varid );
                $DB->delete_records(VPL_ASSIGNED_VARIATIONS, ['variation' => $fromform->varid]);
                vpl_notice(get_string('deleted'));
                print_basic_html($form, $vpl);
            } else {
                throw new moodle_exception('error:recordnotdeleted', 'mod_vpl', '', VPL_VARIATIONS);
            }
        } else { // Update record.
            if ($DB->get_record(VPL_VARIATIONS, ['id' => $fromform->varid, 'vpl' => $vplid])) { // Check consistence.
                $fromform->vpl = $vplid;
                $fromform->id = $fromform->varid;
                $fromform->description = $fromform->description['text'];
                vpl_truncate_variations( $fromform );
                $DB->update_record( VPL_VARIATIONS, $fromform );
                \mod_vpl\event\variation_updated::logvpl( $vpl, $fromform->varid );
                vpl_notice(get_string('updated', '', $fromform->identification));
                print_basic_html($form, $vpl);
            } else {
                throw new moodle_exception('error:inconsistency', 'mod_vpl', '', VPL_VARIATIONS);
            }
        }
    } else {
        $variation = $DB->get_record(VPL_VARIATIONS, ['id' => $varid, 'vpl' => $vplid]);
        if ($variation) {
            $variation->varid = $variation->id;
            $variation->id = $id;
            $variation->description = ['text' => $variation->description];
            $mform->set_data( $variation );
            $mform->display();
        } else {
            throw new moodle_exception('error:inconsistency', 'mod_vpl', '', VPL_VARIATIONS);
        }
    }
}

$vpl->print_footer();
