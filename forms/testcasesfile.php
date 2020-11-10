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
require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/../vpl.class.php');
require_once(dirname(__FILE__) . '/../editor/editor_utility.php');
require_once(dirname(__FILE__) . '/../BTBUmodal/BTBUmodal_utility.php');

vpl_editor_util::generate_requires();

require_login();
$id = required_param('id', PARAM_INT);

$vpl = new mod_vpl($id);
$instance = $vpl->get_instance();
$vpl->prepare_page('forms/testcasesfile.php', array(
        'id' => $id
));

$vpl->require_capability(VPL_MANAGE_CAPABILITY);
$fgp = $vpl->get_required_fgm();
$vpl->print_header(get_string('testcases', VPL));
$vpl->print_heading_with_help('testcases');

//btbu，这块目前只是展示，之后要纳入翻译系统！好像具体实现起来还是个问题？
$vpl_btbu_VsheetEdit = new html_table();
$vpl_btbu_VsheetEdit->id="BTBUtable";
$vpl_btbu_VsheetEdit->attributes['class'] = 'generaltable mod_index';
$vpl_btbu_VsheetTitle=array(
        'testcase_name',
        'testcase_input',
        'testcase_output',
        'grade_reduction',
        'wrong_msg',
        'use_preset_code',
        'testcases_add',
        'default',
        'enter_testcase_name',
        'testcase_name_dublicate',
);
$infolist=array();
foreach ($vpl_btbu_VsheetTitle as $word) {
        $infolist [$word] = get_string( $word, VPL );
    }
$vpl_btbu_VsheetEdit->head = $list;
$vpl_btbu_VsheetEdit->data=array(array(
        '未就绪',
        '<空白>',
        'Hello world1',
        '0.5',
        '显示',
        '否')
);
echo html_writer::table( $vpl_btbu_VsheetEdit );
echo html_writer::link($url, '添加案例', array('class' => 'btn btn-secondary','onclick' => 'BTBUmodalopen()'));
echo '<br><br>';
echo html_writer::link($url, '确认', array('class' => 'btn btn-primary','style' => 'color:white','onclick' => 'BTBUupload()'));
//bootstrap

vpl_include_jsfile( 'BTBUtestcase.js' );
BTBUmodal_util::print_tag();
$infolist['pageid']=$_GET['id'];
echo '<script>var BTBUinfojson=' . json_encode($infolist) . ';</script>';


echo "<br>现在得想想办法让这个编辑器能ajax到executionfiles.json.php?id={$id}&action=;;;算了，我们上bootstrap或者框架吧";
$vpl->print_heading_with_help('testcases_adv');
echo "<span style='color:red'>请注意！如设置了预制代码，则请到高级设置-运行所需文件进行更改！</span>";
//btbu

$options = array();
$options['restrictededitor'] = false;
$options['save'] = true;
$options['run'] = false;
$options['debug'] = false;
$options['evaluate'] = false;
$options['ajaxurl'] = "testcasesfile.json.php?id={$id}&action=";
$options['download'] = "../views/downloadexecutionfiles.php?id={$id}";
$options['resetfiles'] = false;
$options['minfiles'] = 1;
$options['maxfiles'] = 1;
$options['saved'] = true;

session_write_close();
vpl_editor_util::print_tag($options);
$vpl->print_footer_simple();
?>

