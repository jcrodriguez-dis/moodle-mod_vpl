var BTBUcases,//将案例拿到手
    BTBUi18n_title,//用于i18n的列表名称抬头
    BTBUaddcase,//添加测试用例的翻译
    BTBUpageid,
    BTBUCaseNameList = [];
function BTBUgetcases() {//获取最新的cases
    var xml = new XMLHttpRequest()
    xml.open("GET", "http://localhost/moodle/mod/vpl/forms/testcasesfile.json.php?id=" + BTBUpageid + "&action=load");
    xml.onreadystatechange = function () {
        if (xml.readyState == 4) {
            if (xml.status == 200) {
                try {
                    ans = JSON.parse(xml.responseText);
                }
                catch (err) {//json不对
                    alert(err.message)
                    return;
                }
                if (ans.success != true) {//如果回复有错误
                    alert(ans.error)
                    return;
                }
                BTBUgotcases(ans.response.files[0].contents);//渲染一次
            } else {
                alert("NetErr" + xml.status)
            }
        }
    }
    xml.send();
}
function BTBUparsecases(cases) {//解析
    BTBUCaseNameList = [];
    var ans = [];//回复
    var slist = cases.split('\n');//按行读取
    var tCaseName = undefined,//当前读取到的用例名称
        tFailMessage = undefined,//错误信息，写了这个会导致系统不再显示错误输出
        tGradeReduction = undefined,//评分占比
        tInput = undefined,//输入，评判
        tOutput = undefined;
    var isInput = false, isOutput = false;
    for (let index = 0; index < slist.length; index++) {//遍历每一行来解析
        const element = slist[index];
        if (element.startsWith('case=')) {//一个新的案例
            if (tCaseName != element.slice(5) && tCaseName != undefined) {//如果两个案例名称不同，说明是另一个案例。
                if (tGradeReduction == undefined) {
                    tGradeReduction = '<'+BTBUinfojson.default+'>'
                }
                if (tFailMessage == undefined) {
                    tFailMessage = '<'+BTBUinfojson.default+'>'
                }
                ans.push(
                    {
                        case: tCaseName,
                        input: tInput,
                        output: tOutput,
                        FailMessage: tFailMessage,
                        GradeReduction: tGradeReduction
                    }
                )
                BTBUCaseNameList.push(tCaseName)
            }
            tCaseName = element.slice(5)//案例名称更新
            tFailMessage = undefined
            tGradeReduction = undefined
            tInput = undefined
            tOutput = undefined
            isInput = false
            isOutput = false
        }
        else if (element.startsWith('Fail message=')) {
            tFailMessage = element.slice(13)
            isInput = false
            isOutput = false
        }
        else if (element.startsWith('grade reduction=')) {
            tGradeReduction = element.slice(16)
            isInput = false
            isOutput = false
        }
        else if (element.startsWith('input=')) {
            tInput = element.slice(6)
            isInput = true//开启下一行判断丢入input
            isOutput = false
        }
        else if (element.startsWith('output=')) {
            tOutput = element.slice(7)
            isInput = false
            isOutput = true//开启下一行判断丢入output
        }
        else {//可能是空行，也可能是input和output的信息
            if (isInput) {//input的
                tInput += '<br>' + element
            } else if (isOutput) {
                tOutput += '<br>' + element
            }
            //啥都不是就直接跳过就完了
        }
    }
    //最后应该还有一组没有被取出
    if (tGradeReduction == undefined) {
        tGradeReduction = '<默认>'
    }
    if (tFailMessage == undefined) {
        tFailMessage = '<默认>'
    }
    ans.push(
        {
            case: tCaseName,
            input: tInput,
            output: tOutput,
            FailMessage: tFailMessage,
            GradeReduction: tGradeReduction
        }
    )
    BTBUCaseNameList.push(tCaseName)
    return ans;
}
function BTBUgotcases(cases) {//渲染到解析器
    BTBUcases = cases
    document.getElementById("BTBUtable").innerHTML = BTBUi18n_title;
    array = BTBUparsecases(cases)
    for (let index = 0; index < array.length; index++) {
        const element = array[index];
        document.getElementById("BTBUtable").innerHTML +=
            '<tbody><tr class="lastrow"><td class="cell c0" style="">'
            + element.case + '</td><td class="cell c1" style="">'//名称
            + element.input + '</td><td class="cell c2" style="">'
            + element.output + '</td><td class="cell c3" style="">'
            + element.GradeReduction + '</td><td class="cell c4" style="">'
            + element.FailMessage + '</td><td class="cell c5 lastcol" style="">否</td></tr></tbody>'
    }
}

function BTBUi18n(ans) {//执行表头翻译
    BTBUi18n_title = '<thead><tr><th class="header c0" style="" scope="col">' + ans.testcase_name + '</th><th class="header c1" style="" scope="col">' + ans.testcase_input + '</th><th class="header c2" style="" scope="col">' + ans.testcase_output + '</th><th class="header c3" style="" scope="col">' + ans.grade_reduction + '</th><th class="header c4" style="" scope="col">' + ans.wrong_msg + '</th><th class="header c5 lastcol" style="" scope="col">' + ans.use_preset_code + '</th></tr></thead>';
    $JQVPL('#BTBUtestcase_name_label').text(ans.testcase_name);
    $JQVPL('#BTBUtestcase_input_label').text(ans.testcase_input)
    $JQVPL('#BTBUtestcase_output_label').text(ans.testcase_output)
    $JQVPL('#BTBUtestcase_grade_label').text(ans.grade_reduction)
    $JQVPL('#BTBUtestcase_wrong_label').text(ans.wrong_msg)
    BTBUaddcase = ans.testcases_add;
}
function BTBUinit() {//初始化
    BTBUi18n(BTBUinfojson);
    BTBUpageid=BTBUinfojson.pageid
    BTBUgetcases();
}
function BTBUmodalopen() {//打开拟态框
    var btbuDialog = $JQVPL('#vpl_ide_dialog_btbu');
    var OKButtons = {};
    OKButtons['ok'] = function () {//成功了
        if ($JQVPL('#BTBUtestcase_name').val() == "") {
            alert(BTBUinfojson.enter_testcase_name);
            return
        }
        if (BTBUCaseNameList.indexOf($JQVPL('#BTBUtestcase_name').val()) > -1) {
            alert(BTBUinfojson.testcase_name_dublicate);
            return
        }
        var newc = '\ncase=' + $JQVPL('#BTBUtestcase_name').val()
        if (!$JQVPL('#BTBUtestcase_wrong').prop('checked')) {
            newc += '\nFail message=' + BTBUinfojson.no_wrong_msg
        }
        if ($JQVPL('#BTBUtestcase_grade').val() == '') {//默认评分
            newc += '\ninput=' + $JQVPL('#BTBUtestcase_input').val() + '\noutput=' + $JQVPL('#BTBUtestcase_output').val() + '\n'
        } else {
            newc += '\ninput=' + $JQVPL('#BTBUtestcase_input').val() + '\noutput=' + $JQVPL('#BTBUtestcase_output').val() + '\ngrade reduction=' + $JQVPL('#BTBUtestcase_grade').val() + '%'
        }
        BTBUcases += newc;
        BTBUgotcases(BTBUcases);
        $JQVPL(this).dialog('close');
    };
    btbuDialog.dialog($JQVPL.extend({}, "dialogbase_options", {
        title: BTBUaddcase,
        width: 'auto',
        height: 'auto',
        buttons: OKButtons
    }));
}
function BTBUupload() {//提交信息到服务器
    var xmlh = new XMLHttpRequest();
    var data = {
        comments: '',
        files: [
            {
                contents: BTBUcases,
                encoding: 0,
                name: "vpl_evaluate.cases"
            }
        ]
    }
    xmlh.open('POST', 'http://localhost/moodle/mod/vpl/forms/testcasesfile.json.php?id=' + BTBUpageid + "&action=save")
    xmlh.onreadystatechange = function () {
        if (xmlh.readyState == 4) {
            if (xmlh.status == 200) {
                try {
                    ans = JSON.parse(xmlh.responseText);
                }
                catch (err) {//json不对
                    alert(err.message)
                    return;
                }
                if (ans.success != true) {//如果回复有错误
                    alert(ans.error)
                    return;
                }
                alert('OK')
                location.reload()
            } else {
                alert("NetErr" + xmlh.status)
            }
        }
    }
    xmlh.send(JSON.stringify(data))
}
document.ready = BTBUinit