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
 * Form base class definition
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined( 'MOODLE_INTERNAL' ) || die();

class vpl_form {
    private $htmlcode;
    private $formcode;
    private $data;
    private $datatype;
    private function checkdataset($name, &$value) {
        if (isset( $this->data->$name )) {
            $value = $this->data->$name;
        }
        if (isset( $this->datatype [$name] )) {
            switch ($this->datatype [$name]) {
                case PARAM_RAW :
                case PARAM_ALPHA :
                case PARAM_ALPHANUM :
                case PARAM_BOOL :
                case PARAM_CLEANFILE :
                case PARAM_INT :
                case PARAM_INTEGER :
                case PARAM_URL :
                case PARAM_TEXT :
                    // TODO implement filter.
            }
        }
    }
    private function addinput($type, $name, $value, $size = false, $attributes = false) {
        $this->checkDataSet( $name, $value );
        $html = '<input name="' . s( $name ) . '" type="' . s( $type ) . '" value="' . s( $value ) . '"';
        $html .= ' id="id_' . s( $name ) . '"';
        if ($size) {
            $html .= ' size="' . s( $size ) . '"';
        }
        if ($attributes) {
            $html .= ' ' . $attributes;
        }
        $html .= ' />';
        $this->htmlcode .= $html;
    }
    private function action($action, $id = 'mform1', $attributes = false) {
        $html = '<form action="' . $action . '" method="post" id="' . $id . '"';
        if ($attributes !== false) {
            $html .= ' ' . $attributes;
        }
        $html .= '>';
        $this->formcode = $html;
    }
    public function __construct($page, $id = 'form1', $attributes = false) {
        $this->htmlcode = '';
        $this->formcode = '';
        $this->datatype = array ();
        $this->data = new StdClass();
        $this->action( $page, $id, $attributes );
    }
    public function settype($name, $type) {
        $this->datatype [$name] = $type;
    }
    public function addhidden($name, $value = '') {
        $this->checkDataSet( $name, $value );
        $this->addInput( 'hidden', $name, $value );
    }
    public function addpassword($name, $value = '') {
        $this->checkDataSet( $name, $value );
        $this->addInput( 'password', $name, $value );
    }
    public function addtext($name, $value = '', $size = false) {
        $this->checkDataSet( $name, $value );
        $this->addInput( 'text', $name, $value, $size );
    }
    public function addtextarea($name, $value = '', $rows = 50, $cols = 10) {
        $this->checkDataSet( $name, $value );
        $html = '<textarea name="' . s( $name ) . '" rows="' . s( $rows ) . '"';
        $html .= ' cols="' . s( $cols ) . '">';
        $html .= s( $value );
        $html .= '</textarea>';
        $this->htmlcode .= $html;
    }
    public function addsubmitbutton($name, $value, $attribute = '') {
        $this->addInput( 'submit', $name, $value, null, $attribute );
    }
    public function addcancelbutton($value) {
        $this->addInput( 'submit', 'cancel', $value );
    }
    public function addselect($name, $values, $selected = '') {
        $this->checkDataSet( $name, $selected );
        $html = '<select name="' . s( $name ) . '">';
        foreach ($values as $value => $text) {
            $html .= '<option value="' . s( $value ) . '"';
            if ($value == $selected) {
                $html .= ' selected="selected"';
            }
            $html .= '>';
            $html .= s( $text );
            $html .= '</option>';
        }
        $html .= '</select>';
        $this->htmlcode .= $html;
    }
    public function addhtml($html) {
        $this->htmlcode .= $html;
    }
    protected function definition() {
        $this->htmlcode = '';
        $this->addHidden( 'sesskey', sesskey() );
    }
    public function display() {
        $this->definition();
        echo $this->formcode;
        echo '<div>';
        echo $this->htmlcode;
        echo '</div>';
        echo '</form>';
    }
    public function is_cancelled() {
        return isset( $_POST ['cancel'] );
    }
    public function get_data() {
        if (count( $_POST )) {
            $ret = new StdClass();
            foreach ($_POST as $key => $value) {
                // TODO check type and range.
                $this->checkDataSet( $key, $value );
                $ret->$key = $value;
            }
            return $ret;
        }
        return false;
    }
    public function set_data($data) {
        $this->data = $data;
    }
}
