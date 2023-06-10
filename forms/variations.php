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


class mod_vpl_variation_option_form extends moodleform {
    protected function definition() {
        $mform = & $this->_form;
        $mform->addElement( 'header', 'variation_options', get_string( 'variation_options', VPL ) );
        $mform->addElement( 'hidden', 'id', required_param( 'id', PARAM_INT ) );
        $mform->setType( 'id', PARAM_INT );
        $mform->addElement( 'selectyesno', 'usevariations', get_string( 'usevariations', VPL ) );
        $mform->addElement( 'text', 'variationtitle', get_string( 'variationtitle', VPL ), array (
                'size' => 60
        ) );
        $mform->setType( 'variationtitle', PARAM_TEXT );
        $buttongroup = array ();
        $buttongroup[] = $mform->createElement( 'submit', 'save', get_string( 'save', VPL ) );
        $buttongroup[] = $mform->createElement( 'submit', 'cancel', get_string( 'cancel' ) );
        $mform->addGroup( $buttongroup );
    }
}

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
            $title = get_string( 'variation', VPL, "{$this->number}" );
        } else {
            $title = get_string( 'add' );
        }
        $mform->addElement( 'header', 'variation' . ($this->number + 100), $title );
        $mform->addElement( 'hidden', 'id', required_param( 'id', PARAM_INT ) );
        $mform->setType( 'id', PARAM_INT );
        $mform->addElement( 'hidden', 'varid', $this->varid );
        $mform->setType( 'varid', PARAM_INT );

        $mform->addElement( 'text', 'identification', get_string( 'varidentification', VPL ), array (
                'size' => '20'
        ) );
        $mform->setDefault( 'identification', '' );
        $mform->setType( 'identification', PARAM_RAW );
        $fieldname = 'description' . $this->varid; // Allows multile editors in page.
        $mform->addElement('editor', $fieldname, get_string('description', VPL));
        $mform->setType($fieldname, PARAM_RAW);
        $mform->setDefault( $fieldname, '' );

        $buttongroup = array ();
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

require_login();

$id = required_param( 'id', PARAM_INT );
$vpl = new mod_vpl( $id );
$vpl->prepare_page( 'forms/variations.php', array ( 'id' => $id ) );
vpl_include_jsfile( 'hideshow.js' );
$vplid = $vpl->get_instance()->id;
$vpl->require_capability( VPL_MANAGE_CAPABILITY );
$href = vpl_mod_href( 'forms/variations.php', 'id', $id );
$vpl->print_header( get_string( 'variations', VPL ) );
$vpl->print_heading_with_help( 'variations' );
// Generate default form and check for action.
if (optional_param( 'varid', -13, PARAM_INT ) == -13) { // No variation saved.
    $oform = new mod_vpl_variation_option_form( $href, $vpl );
    if (isset($_POST['cancel']) ) {
        vpl_redirect( $href, get_string('cancelled'));
    } else if ($fromform = $oform->get_data()) {
        vpl_truncate_string( $fromform->variationtitle, 255 );
        $instance = $vpl->get_instance();
        $instance->usevariations = $fromform->usevariations;
        $instance->variationtitle = $fromform->variationtitle;
        $vpl->update();
        \mod_vpl\event\vpl_variation_updated::log( $vpl );
        vpl_redirect( $href, get_string('updated', '', $instance->variationtitle) );
    }
    $vplinstmod = clone $vpl->get_instance();
    $vplinstmod->id = $id;
    $oform->set_data( $vplinstmod );
}
$varid = optional_param( 'varid', 0, PARAM_INT );
$mform = new mod_vpl_variation_form( $href, 0, $varid );
if ( isset($_POST['cancel']) ) {
    vpl_redirect( $href, get_string('cancelled') ); // Reload page.
} else if ($fromform = $mform->get_data()) {
    if ( isset($_POST['delete']) ) { // Deletes variation and its assignned variations.
        if ($DB->delete_records( VPL_VARIATIONS, array (
                'id' => $fromform->varid,
                'vpl' => $vplid
        ) )) {
            \mod_vpl\event\variation_deleted::logvpl( $vpl, $fromform->varid );
            $DB->delete_records( VPL_ASSIGNED_VARIATIONS, array (
                    'variation' => $fromform->varid
            ) );
        } else {
            throw new moodle_exception('error:recordnotdeleted', 'mod_vpl', VPL_VARIATIONS);
        }
        vpl_redirect( $href, get_string('deleted') );
    } else {
        if ($fromform->varid <= 0) { // New record.
            $fromform->vpl = $vplid;
            unset( $fromform->id );
            $fromform->description = $fromform->description0['text'];
            vpl_truncate_variations( $fromform );
            if ($vid = $DB->insert_record( VPL_VARIATIONS, $fromform )) {
                \mod_vpl\event\variation_added::logvpl( $vpl, $vid );
            } else {
                throw new moodle_exception('error:recordnotinserted', 'mod_vpl', VPL_VARIATIONS);
            }
            vpl_redirect( $href, get_string('saved', VPL) );
        } else { // Update record.
            if ($DB->get_record( VPL_VARIATIONS, array (
                    'id' => $fromform->varid,
                    'vpl' => $vplid
            ) )) { // Check consistence.
                $fromform->vpl = $vplid;
                $fromform->id = $fromform->varid;
                $fieldname = 'description' . $fromform->varid;
                $fromform->description = $fromform->{$fieldname}['text'];
                vpl_truncate_variations( $fromform );
                $DB->update_record( VPL_VARIATIONS, $fromform );
                \mod_vpl\event\variation_updated::logvpl( $vpl, $fromform->varid );
                vpl_redirect( $href, get_string('updated', '', $fromform->identification) );
            } else {
                throw new moodle_exception('error:inconsistency', 'mod_vpl', VPL_VARIATIONS);
            }
        }
    }
}
// Display page.
if (isset( $oform )) {
    $oform->display();
}

// Get list of variations.
$list = $DB->get_records( 'vpl_variations', array (
        'vpl' => $vplid
) );

// Generate and show forms.
$number = 1;
foreach ($list as $variation) {
    $aform = new mod_vpl_variation_form( $href, $number, $variation->id );
    $variation->varid = $variation->id;
    $variation->id = $id;
    $fieldname = 'description' . $variation->varid;
    $variation->$fieldname = array('text' => $variation->description);
    $aform->set_data( $variation );
    $aform->display();
    $number ++;
}
$lastform = new mod_vpl_variation_form( $href );
$lastform->display();
$vpl->print_footer();
