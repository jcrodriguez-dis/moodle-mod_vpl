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
 * English to Chinese(simplified) translation, 19/10/2020
 *
 * @author Computer Science Education group of Beijing Technology and Business University
 * @var array $string
 */

$string['about'] = '关于';
$string['acceptcertificates'] = '接受自签名证书';
$string['acceptcertificates_description'] = '如果execution服务器不使用自签名证书，请取消选中此选项';
$string['acceptcertificatesnote'] = "<p>您正在使用加密连接。<p/>
<p>要使用与execution服务器的加密连接，必须接受其证书。</p>
<p>如果这个过程有问题，可以尝试使用http（未加密）连接或其他浏览器。</p>
<p>请单击以下链接（服务器）并接受提供的证书。</p>";
$string['addfile'] = '添加文件';
$string['advanced'] = '高级';
$string['allfiles'] = '所有文件';
$string['allsubmissions'] = '所有提交的文件';
$string['anyfile'] = '任何文件';
$string['attemptnumber'] = '尝试次数 {$a}';
$string['autodetect'] = '自动检测';
$string['automaticevaluation'] = '自动评分';
$string['automaticgrading'] = '自动分级';
$string['averageperiods'] = '平均周期 {$a}';
$string['averagetime'] = '平均时间 {$a}';
$string['basedon'] = '基于';
$string['basic'] = '基本的';
$string['binaryfile'] = '二进制文件';
$string['browserupdate'] = '请将您的浏览器更新到最新版本<br />或者使用另一个支持Websocket的。';
$string['calculate'] = '计算成绩';
$string['changesNotSaved'] = '更改尚未保存';
$string['check_jail_servers'] = '检查 execution 服务器';
$string['clipboard'] = '剪贴板';
$string['closed'] = '关闭';
$string['comments'] = '评述';
$string['compilation'] = '编译';
$string['connected'] = '连接成功';
$string['connecting'] = '连接中';
$string['connection_closed'] = '连接关闭';
$string['connection_fail'] = '连接失败';
$string['console'] = '终端窗口';
$string['copy'] = '复制';
$string['create_new_file'] = '创建新文件';
$string['crontask'] = '显示VPL到达“可用起始”的活动时间';
$string['currentstatus'] = '当前状态';
$string['cut'] = '剪切';
$string['datesubmitted'] = '提交日期';
$string['debug'] = '调试';
$string['debugging'] = '调试中';
$string['debugscript'] = '调试脚本';
$string['debugscript_help'] = '选择要在此活动中使用的调试脚本。';
$string['defaultexefilesize'] = '默认最大可执行文件大小';
$string['defaultexememory'] = '默认最大内存使用量';
$string['defaultexeprocesses'] = '默认的最多进程数';
$string['defaultexetime'] = '默认的程序最长执行时间';
$string['defaultfilesize'] = '默认的上传文件最大字节数';
$string['defaultresourcelimits'] = '默认执行资源限制';
$string['delete'] = '删除';
$string['delete_file_fq'] = "删除 '{\$a}' 文件？";
$string['delete_file_q'] = '删除文件？';
$string['deleteallsubmissions'] = '删除所有提交的文件';
$string['description'] = '描述';
$string['diff'] = '差异';
$string['discard_submission_period'] = '放弃提交期';
$string['discard_submission_period_description'] = '对于每个学生和其分配的任务，系统放弃所有提交的内容，只保留最后一个周期提交的任务。';
$string['download'] = '下载';
$string['downloadallsubmissions'] = '下载所有提交的程序';
$string['downloadsubmissions'] = '下载提交文件';
$string['duedate'] = '到期日';
$string['edit'] = '编辑';
$string['editing'] = '编辑中';
$string['editortheme'] = '编辑器主题';
$string['evaluate'] = '评分';
$string['evaluateonsubmission'] = '仅评分已提交的程序';
$string['evaluating'] = '评分进行中';
$string['evaluation'] = '评分值';
$string['examples'] = '示例';
$string['execution'] = '运行程序';
$string['executionfiles'] = '运行所需文件';
$string['executionoptions'] = '运行选项';
$string['file'] = '文件';
$string['file_name'] = '文件名';
$string['fileadded'] = "文件 '{\$a}' 已添加";
$string['filedeleted'] = "文件 '{\$a}' 已删除";
$string['filelist'] = "文件列表";
$string['filenotadded'] = '文件没有被添加';
$string['fileNotChanged'] = '文件没有变化';
$string['filenotdeleted'] = '文件 \'{$a}\' 没有被删除。';
$string['filenotrenamed'] = '文件 \'{$a}\' 没有被重命名。';
$string['filerenamed'] = "文件 '{\$a->from}' 已经被更名为 '{\$a->to}'。";
$string['filesChangedNotSaved'] = '文件已更改，但尚未保存。';
$string['filesNotChanged'] = '文件没有改变。';
$string['filestoscan'] = '文件扫描';
$string['fileupdated'] = "文件 '{\$a}' 已被更新。";
$string['finalreduction'] = '最终还原';
$string['finalreduction_help'] = '<b>FR [NE/FE R]</b><br>
<b>FR</b> 最终成绩减少。<br>
<b>NE</b> 学生要求自动评分。<br>
<b>FE</b> 允许自由评分。<br>
<b>R</b> 通过评分降低分数等级。如果它是一个百分比，它将覆盖以前的结果。<br>';
$string['find'] = "查找";
$string['find_replace'] = '查找/替换';
$string['freeevaluations'] = '自由评分';
$string['freeevaluations_help'] = '不降低最终分数的自动评分数';
$string['fulldescription'] = '详细描述';
$string['fullscreen'] = '全屏';
$string['getjails'] = '获取 execution 服务器';
$string['gradeandnext'] = '分数段和下一级';
$string['graded'] = '分级的分数';
$string['gradedbyuser'] = '分级的用户';
$string['gradedon'] = "已评分";
$string['gradedonby'] = '由 {$a->gradername} 审核的日期 {$a->date} ';
$string['gradenotremoved'] = '分级尚未删除。检查成绩册中的活动配置。';
$string['gradenotsaved'] = '成绩尚未保存。检查成绩册中的活动配置。';
$string['gradeoptions'] = '分级选项';
$string['grader'] = "评分器";
$string['gradercomments'] = '评估报告';
$string['graderemoved'] = '分数已删除';
$string['groupwork'] = '工作组';
$string['inconsistentgroup'] = '您不是一个组的唯一成员（0 o>1）';
$string['incorrect_file_name'] = '错误的文件名';
$string['individualwork'] = '个人工作';
$string['instanceselection'] = 'VPL 选择的';
$string['isexample'] = '本程序为示例';
$string['jail_servers'] = 'Execution 服务器列表';
$string['jail_servers_config'] = 'Execution 服务器配置';
$string['jail_servers_description'] = '为每台服务器写一行';
$string['joinedfiles'] = '加入选定的文件';
$string['keepfiles'] = '运行时要保留的文件';
$string['keyboard'] = '键盘';
$string['lasterror'] = '最近一次错误的信息';
$string['lasterrordate'] = '最近一次错误的日期';
$string['listofcomments'] = '评论列表';
$string['listsimilarity'] = '相似处列表';
$string['listwatermarks'] = '水印列表';
$string['load'] = '载入';
$string['loading'] = '载入中';
$string['local_jail_servers'] = '本地 execution 服务器';
$string['manualgrading'] = '手工分级';
$string['maxexefilesize'] = '最大可执行文件的长度';
$string['maxexememory'] = '最大使用内存';
$string['maxexeprocesses'] = '最多进程数';
$string['maxexetime'] = '最长执行时间';
$string['maxfiles'] = '最多文件数';
$string['maxfilesexceeded'] = '超过允许最多文件数了';
$string['maxfilesize'] = '最大上传文件尺寸';
$string['maxfilesizeexceeded'] = '超过最大文件长度允许值了';
$string['maximumperiod'] = '最长周期 {$a}';
$string['maxresourcelimits'] = '程序的运行资源限制';
$string['maxsimilarityoutput'] = '最大相似输出';
$string['menucheck_jail_servers'] = '检查 execution 服务器';
$string['menuexecutionfiles'] = '附加文件';
$string['menuexecutionoptions'] = '选项';
$string['menukeepfiles'] = '保留的文件';
$string['menulocal_jail_servers'] = '本地 execution 服务器';
$string['menuresourcelimits'] = '资源限制';
$string['minsimlevel'] = '显示最小相似度';
$string['moduleconfigtitle'] = '配置VPL模式';
$string['modulename'] = '虚拟编程实验室（Virtual programming lab）';
$string['modulenameplural'] = '虚拟编程实验室群（Virtual programming labs）';
$string['multidelete'] = '多次删除';
$string['nevaluations'] = '{$a} 自动评分完成';
$string['new'] = '新建';
$string['new_file_name'] = '新建文件的文件名';
$string['next'] = '下一个';
$string['nojailavailable'] = '没有可用的 execution 服务器';
$string['noright'] = '您没有访问权限';
$string['nosubmission'] = '没有有效提交';
$string['notexecuted'] = '未执行';
$string['notgraded'] = '未评分';
$string['notsaved'] = '未保存';
$string['novpls'] = '未定义虚拟编程实验室';
$string['nowatermark'] = '拥有水印数 {$a}';
$string['nsubmissions'] = '提交文件数 {$a}';
$string['numcluster'] = '集群数 {$a}';
$string['open'] = '打开';
$string['opnotallowfromclient'] = '不允许在这台机器上操作';
$string['options'] = '选项';
$string['optionsnotsaved'] = '选项设置没被保存';
$string['optionssaved'] = '选项设置已被保存';
$string['origin'] = '来源';
$string['othersources'] = '扫描其他来源的数据';
$string['outofmemory'] = '内存溢出';
$string['paste'] = '粘贴';
$string['pluginadministration'] = 'VPL管理';
$string['pluginname'] = '虚拟编程实验室';
$string['previoussubmissionslist'] = '已提交清单';
$string['print'] = '打印';
$string['proposedgrade'] = '建议分数：{$a}';
$string['proxy'] = '代理';
$string['proxy_description'] = '从Moodle平台访问 execution 服务器的代理。';
$string['redo'] = '重做';
$string['reductionbyevaluation'] = "重新自动评分";
$string['reductionbyevaluation_help'] = "对学生的每一个自动评分，将最终分数降低一个值或百分比";
$string['regularscreen'] = '还原屏幕';
$string['removegrade'] = '删除评分';
$string['rename'] = '重命名';
$string['rename_file'] = '重命名文件';
$string['replace_find'] = '替换/查找';
$string['requestedfiles'] = '附加文件';
$string['requirednet'] = '允许从网上提交附加文件';
$string['requiredpassword'] = '需要密码';
$string['resetfiles'] = '重置文件';
$string['resetvpl'] = '重置文件 {$a}';
$string['resourcelimits'] = '资源限制';
$string['restrictededitor'] = '禁用上传，粘贴和拖放外部文件';
$string['retrieve'] = '检索结果';
$string['run'] = '运行';
$string['running'] = '运行中';
$string['runscript'] = '运行脚本';
$string['runscript_help'] = '选择要在此编程中使用的运行脚本';
$string['save'] = '保存';
$string['save'] = '保存';
$string['savecontinue'] = '保存并且继续';
$string['saved'] = '完成保存';
$string['savedfile'] = "文件 '{\$a}' 已被保存";
$string['saveoptions'] = '保存选项';
$string['saving'] = '保存中';
$string['scanactivity'] = '编程活动';
$string['scandirectory'] = '目录';
$string['scanningdir'] = '扫描目录 ...';
$string['scanoptions'] = '扫描选项';
$string['scanother'] = '扫描添加源文件中的相似性';
$string['scanzipfile'] = 'Zip文件';
$string['sebkeys'] = 'SEB 测试词';
$string['sebkeys_help'] = '从.seb文件中获取的 SEB 测试词 (max 3)<br>它比浏览器检查更可靠。<br>https://safeexambrowser.org';
$string['sebrequired'] = '需要SEB浏览器';
$string['sebrequired_help'] = '需要使用正确配置的SEB浏览器';
$string['select_all'] = '选择所有';
$string['server'] = '服务器';
$string['serverexecutionerror'] = '服务器执行错误';
$string['shortdescription'] = '简要说明';
$string['shortcuts'] = '键盘快捷键';
$string['similarity'] = '相似性';
$string['similarto'] = '类似于';
$string['startdate'] = '可从';
$string['submission'] = '提交';
$string['submissionperiod'] = '提交期限';
$string['submissionrestrictions'] = '提交限制';
$string['submissions'] = '提交数';
$string['submissionselection'] = '提交选择';
$string['submissionslist'] = '提交清单';
$string['submissionview'] = '提交浏览';
$string['submittedby'] = ' {$a} 已经提交';
$string['submittedon'] = '提交日期';
$string['submittedonp'] = '提交日期 {$a}';
$string['sureresetfiles'] = '是否要丢失所有工作并将文件重置为其原始状态？';
$string['test'] = '编程测试';
$string['testcases'] = '测试用例';
$string['timelimited'] = '时间限制';
$string['timeleft'] = '剩下的时间';
$string['timeout'] = '超时';
$string['timeunlimited'] = '不限时间';
$string['totalnumberoferrors'] = "错误";
$string['undo'] = '撤销';
$string['unexpected_file_name'] = "文件名不正确：预期 '{\$a->expected}' 且找到 '{\$a->found}'";
$string['unzipping'] = '解压缩中 ...';
$string['uploadfile'] = '上传文件';
$string['usevariations'] = '使用变量';
$string['usewatermarks'] = '使用水印';
$string['usewatermarks_description'] = '向学生的文件添加水印（仅限于支持的语言）';
$string['variation'] = '变更 {$a}';
$string['variation_options'] = '变更选项';
$string['variations'] = '变更';
$string['variations_unused'] = '此编程活动有变更，但已禁用';
$string['variationtitle'] = '变更标题';
$string['varidentification'] = '识别';
$string['visiblegrade'] = '可见的';
$string['vpl:addinstance'] = '添加新的VPL实例';
$string['vpl:grade'] = 'VPL分数分配';
$string['vpl:manage'] = '管理VPL分配';
$string['vpl:setjails'] = '为特定VPL实例设置 execution 服务器';
$string['vpl:similarity'] = '搜索VPL分配相似性';
$string['vpl:submit'] = '提交VPL作业';
$string['vpl:view'] = '查看完整的VPL分配说明';
$string['vpl'] = '虚拟编程实验室';
$string['VPL_COMPILATIONFAILED'] = '编译或准备执行失败';
$string['vpl_debug.sh'] = '此脚本准备调试';
$string['vpl_evaluate.cases'] = '用于评估的测试用例';
$string['vpl_evaluate.sh'] = '此脚本准备评估';
$string['vpl_run.sh'] = '这个脚本准备执行';
$string['workingperiods'] = '工作周期';
$string['worktype'] = '工作类型';
$string['websocket_protocol'] = 'WebSocket协议';
$string['websocket_protocol_description'] = 'WebSocket协议类型 (ws:// or wss://) 由浏览器用于连接到执行服务器。';
$string['always_use_wss'] = '始终使用加密（wss）websocket协议';
$string['always_use_ws'] = '始终使用未加密（ws）websocket协议';
$string['depends_on_https'] = '使用ws或wss取决于是否使用http或https';

$string['check_jail_servers_help'] = "<p>此页面检查并显示用于此活动的执行服务器的状态。</p>";
$string['executionfiles_help'] = '<p>简介</p>
<p>你需要在这里设置一些文件用于运行、调试和自动评估,
 This includes scripting files包括脚本文件、程序测试文件和数据文件。</p>
<p>运行和调试的默认脚本</p>
<p>如果您没有设置运行或调试配置文件，系统将默认依据文件扩展名来猜测编码类型并使用内置在服务器里面的脚本
</p>';
$string['executionoptions_help'] = '<p>Various execution options are set in this page</p>
<ul>
<li><b>Based on</b>: sets other VPL instance from which some features are imported:
<ul><li>Execution files (concatenating the predefined scripting files)</li>
<li>Limits for the execution resources.</li>
<li>Variations, that are concatenating to generate multivariations.</li>
<li>Maximun length for each file to be uploaded with the submission</li>
</ul>
</li>
<li><b>Run</b>, <b>Debug</b> and <b>Evalaute</b>: must be set to \'Yes\' if the corresponding action can be executed when editing the submission. This affects to the students only, users with  capability of grading can always execute these actions.</li>
<li><b>Evaluate just on submission</b>: the submission is evaluated automatically when it is uploaded.</li>
<li><b>Automatic grading</b>: if the evaluation result includes grading codes, they are used to set the grade automatically.</li>
</ul>';
$string['fulldescription_help'] = '<p>You must write here a full description for the activity.</p>
<p>If you don\'t write anything here, the short description is shown instead.</p>
<p>If you want to evaluate automatically, the interfaces for the assignments must be detailed and non-ambiguous.</p>';
$string['keepfiles_help'] = '<p>Due to security issues, the files added as &quot;Execution files&quot; are deleted before running the file vpl_execution.</p>
If any of those files is needed during the execution (by example, to be used as test data), it must be marked here.';
$string['local_jail_servers_help'] = '<p>Here you can set the local execution servers added for this activity and those
that are based on it.</p>
<p>Enter the full URL of a server on each line. You can use blank lines
and comments starting the line with "#".</p>
<p>This activity will use as execution server list: the servers sets here
plus the server list set in the "based on" activity
plus the list of common execution servers.
If you want to prevent this activity and derived ones
from using other servers, then you have to add a line
containing "end_of_jails" at the end of the server list.
</p>';
$string['modulename_help'] = '<p>VPL is a activity module for Moodle that manage programming assignments and whose salient features are:
</p>
<ul>
<li>Enable to edit the programs source code in the browser</li>
<li>Students can run interactively programs in the browser</li>
<li>You can run tests to review the programs.</li>
<li>Allows searching for similarity between files.</li>
<li>Allows setting editing restrictions and avoiding external text pasting.</li>
</ul>
<p><a href="http://vpl.dis.ulpgc.es">Virtual Programming lab Home Page</a></p>';
$string['modulename_link'] = 'mod/vpl/view';
$string['requestedfiles_help'] = '<p>Here you set names and its initial content up for the requested files to the max number of files that was set in the basic description of the activity.</p>
<p>If you don\'t set names for whole number of files, the unnamed files are optional and can have any name.</p>
<p>You also can add contents to the requested files, so these contents will be available the first time that they will be opened with the editor, if no previous submission exists.</p>';
$string['resourcelimits_help'] = '<p>You can set limits for the execution time, the memory used, the execution files sizes and the number of processes to be executed simultaneously.</p>
<p>These limits are used when running the scripting files vpl_run.sh, vpl_debug.sh and vpl_evaluate.sh and the file vpl_execution built by them.</p>
<p>If this activity is based on other activity, the limits can be affected by those set in the base activity and its ancestors or in the global configuration of the module.</p>';
$string['testcases_adv_help'] = 'This feature allows to run the student program and check its output for a given input. To set up the evaluation cases you must populate the file &quot;vpl_evaluate.cases&quot;.<br>
The file "vpl_evaluate.cases" has the following format:<br>
<ul>
<li> "<b>case </b>= Description of case": Set an start of test case definition.</li>
<li> "<b>input </b>= text": can use several lines. Ends with other instruction.</li>
<li> "<b>output </b>= text": can use several lines. Ends with other instruction. A case can have differents correct output. There are three types of output: numbers, text and exact test:
<ul>
<li> <b>number</b>: defined as sequence of numbers (integers and floats). Only numbers in the output are checked, other text are ignored. Floats are checked with tolerance</li>
<li> <b>text</b>: defined as text without double quote. Only words are checked and the rest of chars are ignored, the comparation is case-insensitive </li>
<li> <b>exact text</b>: defined as text into double quote. The exact match is used to test the output.</li>
</ul>
</li>
<li> "<b>grade reduction</b> = [value|percentage%]" : By default an error reduces student\'s grade (starts with maxgrade) by (grade_range/number of cases) but with this instruction
you can change the reduction value or percentage.</li>
</ul>';
// BTBU edited; old=testcases_help.
$string['variations_help'] = '<p>A set of variations can be defined for an activity. These variations are randomly assigned to the students.</p>
<p>Here you can indicate if this activity has variations, put a title for the set of variations, and to add the desired variations.</p>
<p>Each variation has an identification code and a description. The identification code is used by the <b>vpl_enviroment.sh</b> file to pass
the variation assigned to each student to the script files. The description, formatted in HTML, is shown to the students that have assigned
the corresponding variation.</p>';

// BTBU.
$string['testcases_adv'] = '高级测试样例编辑器';
$string['testcases_help'] = 'no help yet';
$string['testcase_name'] = '测试用例名称';
$string['testcase_input'] = '测试输入';
$string['testcase_output'] = '测试输出';
$string['grade_reduction'] = '评分权重';
$string['wrong_msg'] = '错误输出';
$string['use_preset_code'] = '是否使用预制代码';
$string['testcases_add'] = '添加测试用例';
$string['enter_testcase_name'] = '请输入测试样例名称';
$string['testcase_name_dublicate'] = '测试用例名称重复';
$string['no_wrong_msg'] = '教师要求不显示错误信息';
$string['default'] = '默认';
