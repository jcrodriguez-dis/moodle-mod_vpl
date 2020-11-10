<?php
class BTBUmodal_util
{

    public static function print_tag()
    {

?>
        <div id="vpl_ide_dialog_btbu" class="vpl_ide_dialog" style="display: none;" >
            <div class="vpl_ide_dialog_content" id='btbu_dialog'>
                <h3>请在此输入信息</h3>
                <label id="BTBUtestcase_name_label">测试用例名8称</label><input id="BTBUtestcase_name" placeholder="测试用例名称"><br>
                <label id="BTBUtestcase_input_label" >测试输8入</label><textarea id="BTBUtestcase_input" placeholder="测试输入"></textarea><br>
                <label id="BTBUtestcase_output_label">测试输8出</label><textarea id="BTBUtestcase_output" placeholder="测试输出"></textarea><br>
                <label id="BTBUtestcase_grade_label" >评分权8重</label><input type="number" max="100" min='0' id="BTBUtestcase_grade" placeholder="评分权重">%<br>
                <label id="BTBUtestcase_wrong_label">是否显示8错误输出</label><input id="BTBUtestcase_wrong" type="checkbox" checked="1"><br>
            </div>
        </div>
<?php
    }
}
