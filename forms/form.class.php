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
 * @version        $Id: form.class.php,v 1.5 2012-06-05 23:22:16 juanca Exp $
 * @package mod_vpl. Form base class definition
 * @copyright    2012 Juan Carlos Rodríguez-del-Pino
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author        Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

class vpl_form {
    private $html_code;
    private $form_code;
    private $data;
    private $data_type;
    private function checkDataSet($name,&$value){
        if(isset($this->data->$name)){
            $value = $this->data->$name;
        }
        if(isset($this->data_type[$name])){
            switch($this->data_type[$name]){
                case PARAM_RAW:
                case PARAM_ALPHA:
                case PARAM_ALPHANUM:
                case PARAM_BOOL:
                case PARAM_CLEANFILE:
                case PARAM_INT:
                case PARAM_INTEGER:
                case PARAM_URL:
                case PARAM_TEXT:
                    //TODO implement filter
            }
        }
    }
    private function addInput($type,$name,$value,$size=false,$attributes=false){
        $this->checkDataSet($name,$value);
        $html='<input name="'.s($name).'" type="'.s($type).'" value="'.s($value).'"';
        $html.=' id="id_'.s($name).'"';
        if($size){
            $html.=' size="'.s($size).'"';
        }
        if($attributes){
            $html.=' '.$attributes;
        }
        $html.=' />';
        $this->html_code.=$html;
    }

    private function action($action,$id='mform1',$attributes=false){
        $html  = '<form action="'.$action.'" method="post" id="'.$id.'"';
        if($attributes !== false){
            $html .= ' '.$attributes;
        }
        $html .= '>';
        $this->form_code=$html;
    }

    function __construct( $page, $id='form1', $attributes = false){
        $this->html_code='';
        $this->form_code='';
        $this->data_type=array();
        $this->data = new StdClass();
        $this->action($page,$id,$attributes);
    }

    function setType( $name, $type){
        $this->data_type[$name]=$type;
    }

    function addHidden($name,$value=''){
        $this->checkDataSet($name,$value);
        $this->addInput('hidden',$name,$value);
    }

    function addPassword($name,$value=''){
        $this->checkDataSet($name,$value);
        $this->addInput('password',$name,$value);
    }

    function addText($name,$value='',$size=false){
        $this->checkDataSet($name,$value);
        $this->addInput('text',$name,$value,$size);
    }
    function addTextArea($name,$value='',$rows=50, $cols=10){
        $this->checkDataSet($name,$value);
        $html='<textarea name="'.s($name).'" rows="'.s($rows).'"';
        $html.=' cols="'.s($cols).'">';
        $html.=s($value);
        $html.='</textarea>';
        $this->html_code.=$html;
    }
    function addSubmitButton($name,$value,$attribute=''){
        $this->addInput('submit',$name,$value,null,$attribute);
    }
    function addCancelButton($value){
        $this->addInput('submit','cancel',$value);
    }
    function addSelect($name,$values,$selected=''){
        $this->checkDataSet($name,$selected);
        $html = '<select name="'.s($name).'">';
        foreach($values as $value => $text){
            $html .='<option value="'.s($value).'"';
            if($value==$selected){
                $html .= ' selected="selected"';
            }
            $html .= '>';
            $html .= s($text);
            $html .= '</option>';
        }
        $html .= '</select>';
        $this->html_code.=$html;
    }
    function addHTML($html){
        $this->html_code.=$html;
    }
    function definition(){
        $this->html_code='';
        $this->addHidden('sesskey',sesskey());
    }
    function display(){
        $this->definition();
        echo $this->form_code;
        echo '<div>';
        echo $this->html_code;
        echo '</div>';
        echo '</form>';
    }
    function is_cancelled(){
        return isset($_POST['cancel']);
    }
    function get_data(){
        if(count($_POST)){
            $ret = new StdClass();
            foreach($_POST as $key => $value){
                //TODO check type and range
                $this->checkDataSet($key,$value);
                $ret->$key = $value;
            }
            return $ret;
        }
        return false;
    }

    function set_data($data){
        $this->data=$data;
    }
}
