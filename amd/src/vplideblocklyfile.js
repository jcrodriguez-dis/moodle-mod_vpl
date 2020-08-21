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
 * File management
 *
 * @package mod_vpl
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/* globals Interpreter */
/* globals Blockly */

define(
    [
        'jquery',
        'mod_vpl/vplutil',
    ],
    function($, VPLUtil) {
        return function() {
            var self = this;
            var vplIdeInstance = this.getVPLIDE();
            this.firstFocus = true;
            this.workspacePlayground = false;
            var adaptBlockly = function() {
                if (typeof Blockly.PHP.workspaceToCodeOld == 'undefined') {
                    Blockly.PHP.workspaceToCodeOld = Blockly.PHP.workspaceToCode;
                    Blockly.PHP.workspaceToCode = function(workspace) {
                        return "<?\n" + Blockly.PHP.workspaceToCodeOld(workspace);
                    };
                }
                if (typeof Blockly.Python.workspaceToCodeOld == 'undefined') {
                    Blockly.Python.workspaceToCodeOld = Blockly.Python.workspaceToCode;
                    Blockly.Python.workspaceToCode = function(workspace) {
                        return "# -*- coding: utf-8 -*-\n" + Blockly.Python.workspaceToCodeOld(workspace);
                    };
                }
            };
            this.focus = function() {
                if (self.firstFocus) {
                    if (self.workspacePlayground) {
                        self.firstFocus = false;
                        Blockly.Events.disable();
                        self.setContent(getContentOld.call(self));
                        VPLUtil.adjustBlockly(self.workspacePlayground, 10, 10);
                        self.workspacePlayground.scrollX = 0;
                        self.workspacePlayground.scrollY = 0;
                        Blockly.svgResize(self.workspacePlayground);
                        Blockly.resizeSvgContents(self.workspacePlayground);
                        self.adjustSize();
                        Blockly.Events.enable();
                    } else {
                        VPLUtil.longDelay('focus', self.focus);
                    }
                }
            };
            this.adjustSize = function() {
                if (!this.isOpen() || !this.workspacePlayground) {
                    return false;
                }
                var editTag = $(this.getTId());
                if (editTag.length === 0) {
                    return false;
                }
                var tabs = editTag.parent();
                var newHeight = tabs.height();
                newHeight -= editTag.position().top;
                editTag.height(newHeight);
                $('#' + this.bdiv).height(newHeight);
                $('#' + this.bdiv).width(editTag.width());
                Blockly.svgResize(this.workspacePlayground);
                return false;
            };
            this.undo = function() {
                if (this.isOpen()) {
                    this.workspacePlayground.undo(false);
                }
            };
            this.redo = function() {
                if (this.isOpen()) {
                    this.workspacePlayground.undo(true);
                }
            };
            this.interpreter = false;
            this.animateRun = false;
            this.RUNSTATE = 1;
            this.STEPSTATE = 2;
            this.STOPSTATE = 3;
            this.executionState = this.STOPSTATE;
            this.goNext = false;
            this.initRun = function(animate) {
                var ter = vplIdeInstance.getTerminal();
                if (ter.isConnected()) {
                    ter.closeLocal();
                }
                this.animateRun = animate;
                Blockly.JavaScript.STATEMENT_PREFIX = 'highlightBlock(%1);\n';
                Blockly.JavaScript.addReservedWords('highlightBlock');
                var code = Blockly.JavaScript.workspaceToCode(self.workspacePlayground);
                var initApi = function(interpreter, scope) {
                    // Add an API function for the alert() block.
                    var wrapper = function(text) {
                        text = text ? text.toString() + '\r\n' : text + '\r\n';
                        return interpreter.createPrimitive(ter.writeLocal(text));
                    };
                    interpreter.setProperty(scope, 'alert',
                            interpreter.createNativeFunction(wrapper));

                    // Add an API function for the prompt() block.
                    wrapper = function(text, callback) {
                        text = text ? text.toString() : '' + text;
                        ter.writeLocal(text);
                        ter.setDataCallback(function(t) {
                                               ter.writeLocal('\n');
                                               callback(t);
                                            });
                    };
                    interpreter.setProperty(scope, 'prompt',
                        interpreter.createAsyncFunction(wrapper));
                    wrapper = function(id) {
                        if (id == self.breakpoint) {
                            self.executionState = self.STEPSTATE;
                            self.updateRunButtons();
                            vplIdeInstance.getTerminal().setMessage(VPLUtil.str('breakpoint'));
                        }
                        if (self.animateRun || self.executionState == self.STEPSTATE) {
                            self.workspacePlayground.highlightBlock(id);
                        }
                        self.goNext = false;
                    };
                    interpreter.setProperty(scope, 'highlightBlock',
                            interpreter.createNativeFunction(wrapper));
                };
                self.interpreter = new Interpreter(code, initApi);
                ter.connectLocal(self.stop, VPLUtil.doNothing);
            };
            this.reservedWords = {
                'Infinity': true, 'Array': true, 'Boolean': true,
                'Date': true, 'Error': true, 'EvalError': true,
                'Function': true, 'JSON': true, 'Math': true,
                'NaN': true, 'Number': true, 'Object': true, 'RangeError': true,
                'ReferenceError': true, 'RegExp': true, 'String': true,
                'SyntaxError': true, 'TypeError': true, 'URIError': true,
                'alert': true, 'arguments': true, 'constructor': true, 'eval': true,
                'highlightBlock': true, 'isFinite': true,
                'isNaN': true, 'parseFloat': true, 'parseInt': true, 'prompt': true,
                'self': true, 'this': true, 'window': true,
            };
            this.breakpoint = '';
            this.getVarValue = function(val) {
                var HTML = '';
                if (val === null) {
                    HTML = "<b>null</b>";
                } else if (val != undefined) {
                    var type = typeof val;
                    if (type == 'string') {
                        HTML = '"' + VPLUtil.sanitizeText(val) + '"';
                    } else if (type == 'boolean') {
                        HTML = "<b>" + val + "</b>";
                    } else if (type == 'object' && val.class === "Array") {
                        HTML = '[';
                        var ar = val.properties;
                        for (var i = 0; i < ar.length; i++) {
                            HTML += self.getVarValue(ar[i]);
                            if (i != ar.length - 1) {
                                HTML += ', ';
                            }
                        }
                        HTML += ']';
                    } else if (type == 'object') {
                        HTML = "<b>" + val.toString() + "</b>";
                    } else {
                        HTML += '' + val;
                    }
                }
                return HTML;
            };
            this.getVariables = function(properties) {
                var HTML = '';
                for (var proname in properties) {
                    if (this.reservedWords[proname] === true) {
                        continue;
                    }
                    var pro = properties[proname];
                    if (pro != undefined && !(pro.class === "Function")) {
                        HTML += '<b>' + proname + "</b>:&nbsp;" + self.getVarValue(pro) + "<br>\n";
                    }
                }
                return HTML;
            };
            this.getParameters = function(args) {
                var HTML = '(';
                for (var i = 0; i < args.length; i++) {
                    HTML += '' + args[i];
                    if (i < args.length - 1) {
                        HTML += ', ';
                    }
                }
                return HTML + ')';
            };
            this.showStack = function(interpreter) {
                var sn = 0;
                var HTML = '<table class="generaltable">';
                var stack = interpreter.stateStack;
                var lastFunc = '<tr><td>0</td><td><b>Globals</b></td>';
                for (var i = 0; i < stack.length; i++) {
                    var level = stack[i];
                    if (lastFunc > '' && (level.node.type == 'CallExpression' || i == stack.length - 1)) {
                        HTML += lastFunc + '<td>' + self.getVariables(level.scope.properties);
                        HTML += '</td></tr>';
                    }
                    if (level.node.type == 'CallExpression') {
                        if (self.reservedWords[level.node.callee.name] !== true
                        && level.node.callee.name != undefined) {
                            sn++;
                            lastFunc = '<tr><td>' + sn + '</td>';
                            lastFunc += '<td>' + level.node.callee.name + self.getParameters(level.arguments_) + '</td>';
                        } else {
                            lastFunc = '';
                        }
                    }
                }
                HTML += '</table>';
                vplIdeInstance.setResult({variables: HTML});
            };
            this.runLoop = function() {
                if (!self.interpreter) {
                    return;
                }
                self.goNext = true;
                for (var i = 0; i < 30000 && self.goNext; i++) {
                    if (self.executionState == self.STOPSTATE) {
                        break;
                    }
                    if (!self.interpreter || !self.interpreter.step()) {
                        self.executionState = self.STOPSTATE;
                        self.updateRunButtons();
                        break;
                    }
                }
                if (self.executionState == self.STOPSTATE) {
                    self.workspacePlayground.highlightBlock(-1);
                    vplIdeInstance.getTerminal().closeLocal();
                    vplIdeInstance.setResult({variables: ''});
                    return;
                }
                if (self.executionState == self.RUNSTATE) {
                    if (self.animateRun) {
                        setTimeout(self.runLoop, 1000);
                        self.showStack(self.interpreter);
                    } else {
                        setTimeout(self.runLoop, 0);
                    }
                } else {
                    self.showStack(self.interpreter);
                }
            };
            this.start = function() {
                self.initRun(false);
                self.executionState = self.RUNSTATE;
                self.updateRunButtons();
                vplIdeInstance.getTerminal().setMessage(VPLUtil.str('start'));
                self.runLoop();
            };
            this.startAnimate = function() {
                self.initRun(true);
                self.executionState = self.RUNSTATE;
                self.updateRunButtons();
                vplIdeInstance.getTerminal().setMessage(VPLUtil.str('startanimate'));
                self.runLoop();
            };
            this.stop = function() {
                self.executionState = self.STOPSTATE;
                self.updateRunButtons();
                vplIdeInstance.getTerminal().setMessage(VPLUtil.str('stop'));
                vplIdeInstance.getTerminal().closeLocal();
                self.interpreter = false;
                vplIdeInstance.setResult({variables: ''});
            };
            this.pause = function() {
                self.executionState = self.STEPSTATE;
                vplIdeInstance.getTerminal().setMessage(VPLUtil.str('pause'));
                self.updateRunButtons();
            };
            this.resume = function() {
                self.executionState = self.RUNSTATE;
                vplIdeInstance.getTerminal().setMessage(VPLUtil.str('resume'));
                self.updateRunButtons();
                self.runLoop();
            };
            this.step = function() {
                if (self.executionState == self.STOPSTATE) {
                    self.initRun(true);
                }
                self.executionState = self.STEPSTATE;
                self.updateRunButtons();
                vplIdeInstance.getTerminal().setMessage(VPLUtil.str('step'));
                self.runLoop();
            };
            this.hasUndo = function() {
                return true;
            };
            this.hasRedo = function() {
                return true;
            };
            this.oldSetFileName = this.setFileName;
            this.generatorMap = {
                py: 'Python',
                dart: 'Dart',
                js: 'JavaScript',
                lua: 'Lua',
                php: 'PHP'
            };
            this.generator = '';
            this.setFileName = function(name) {
                var regExt2 = /\.([^.]+)\.blockly[123]?$/;
                var regFn2 = /(.+)\.blockly[123]?$/;
                var fileName = this.getFileName();
                var oldExt = VPLUtil.fileExtension(fileName);
                self.oldSetFileName(name);
                var ext2 = regExt2.exec(fileName);
                var fn2 = regFn2.exec(fileName);
                if (ext2 !== null && fn2 !== null && typeof this.generatorMap[ext2[1]] == 'string') {
                    this.generator = this.generatorMap[ext2[1]];
                    this.generatedFilename = fn2[1];
                } else {
                    this.generator = '';
                }
                if (fn2 !== null && oldExt != VPLUtil.fileExtension(fileName)) {
                    this.setToolbox();
                }
            };

            this.changeCode = function(event) {
                if (event.type == 'ui' && event.element == 'selected') {
                    self.breakpoint = event.newValue;
                    return;
                }
                if (event.type == 'ui'
                        && event.element == 'category'
                        && event.newValue == VPLUtil.str('run')) {
                    self.updateRunButtons();
                    return;
                }
                if (!event.recordUndo) {
                    return;
                }
                self.change();
                var fileManager = this.getFileManager();
                if (this.generator != '') {
                    var code = Blockly[this.generator].workspaceToCode(self.workspacePlayground);
                    var fid = fileManager.fileNameExists(this.generatedFilename);
                    // Try to create generated code file.
                    if (fid == -1) {
                        fileManager.addFile({
                            name: this.generatedFilename,
                            contents: ''},
                            false, VPLUtil.doNothing, VPLUtil.doNothing);
                        fid = fileManager.fileNameExists(this.generatedFilename);
                    }
                    if (fid != -1) {
                        var fc = fileManager.getFile(fid);
                        fc.setContent(code);
                        fc.change();
                        fc.gotoLine(1);
                        fc.setReadOnly(true);
                    }
                }
            };
            this.updateRunButtons = function() {
                switch (self.executionState) {
                    case self.RUNSTATE: {
                        $('.blocklyStartC').hide();
                        $('.blocklyStartAnimateC').hide();
                        $('.blocklyStopC').show();
                        $('.blocklyPauseC').show();
                        $('.blocklyResumeC').hide();
                        $('.blocklyStepC').hide();
                        break;
                    }
                    case self.STEPSTATE: {
                        $('.blocklyStartC').hide();
                        $('.blocklyStartAnimateC').hide();
                        $('.blocklyStopC').show();
                        $('.blocklyPauseC').hide();
                        $('.blocklyResumeC').show();
                        $('.blocklyStepC').show();
                        break;
                    }
                    case self.STOPSTATE: {
                        $('.blocklyStartC').show();
                        $('.blocklyStartAnimateC').show();
                        $('.blocklyStopC').hide();
                        $('.blocklyPauseC').hide();
                        $('.blocklyResumeC').hide();
                        $('.blocklyStepC').show();
                        break;
                    }
                }
            };
            this.setToolbox = function() {
                var ext = VPLUtil.fileExtension(this.getFileName());
                var toolboxname = ext + 'Toolbox';
                if (self[toolboxname] === false) {
                    $.ajax({
                        url: '../editor/blocklytoolboxes/' + toolboxname + '.xml',
                        dataType: 'text',
                        success: function(data) {
                            self[toolboxname] = self.blocklyIn18(data);
                            self.setToolbox();
                        },
                    });
                    return;
                }
                this.workspacePlayground.updateToolbox(this[toolboxname]);
                this.workspacePlayground.registerButtonCallback('blocklyStartButton', this.start);
                this.workspacePlayground.registerButtonCallback('blocklyStartAnimateButton', this.startAnimate);
                this.workspacePlayground.registerButtonCallback('blocklyStopButton', this.stop);
                this.workspacePlayground.registerButtonCallback('blocklyPauseButton', this.pause);
                this.workspacePlayground.registerButtonCallback('blocklyResumeButton', this.resume);
                this.workspacePlayground.registerButtonCallback('blocklyStepButton', this.step);
                this.adjustSize();
            };
            this.open = function() {
                if (self.blocklyNotLoaded) {
                    VPLUtil.loadScript(
                        [
                            '../editor/blockly/blockly_compressed.js',
                            '../editor/blockly/msg/js/en.js',
                            '../editor/blockly/blocks_compressed.js',
                            '../editor/blockly/python_compressed.js',
                            '../editor/blockly/javascript_compressed.js',
                            '../editor/blockly/php_compressed.js',
                            '../editor/blockly/lua_compressed.js',
                            '../editor/blockly/dart_compressed.js',
                            '../editor/acorn/acorn.js',
                            '../editor/acorn/interpreter.js',
                        ],
                        function() {
                            adaptBlockly();
                            self.blocklyNotLoaded = false;
                            self.open();
                        }
                    );
                    return false;
                }
                var fileName = this.getFileName();
                this.setOpen(true);
                this.setFileName(fileName);
                var horizontalMenu = false;
                if (/.*[0-9]$/.test(VPLUtil.fileExtension(fileName))) {
                    horizontalMenu = true;
                }
                var tid = this.getTId();
                // Workaround to remove jquery-ui theme background color.
                $(tid).removeClass('ui-widget-content ui-tabs-panel');
                $(tid).addClass('ui-corner-bottom');
                this.bdiv = 'bkdiv' + this.getId();
                $(tid).html('<div id="' + this.bdiv + '" style="height: 480px; width: 600px;"></div>');
                var options = {
                    toolbox: '<xml><category name=""><block type="math_number"></block></category></xml>',
                    media: '../editor/blockly/media/',
                    horizontalLayout: horizontalMenu,
                    zoom: {
                        controls: true,
                        wheel: true,
                        startScale: 1.0,
                        maxScale: 3,
                        minScale: 0.2,
                        scaleSpeed: 1.15
                    }
                };
                this.workspacePlayground = Blockly.inject(this.bdiv, options);
                this.workspacePlayground.addChangeListener(function(event) {
                    self.changeCode(event);
                });
                this.setToolbox();
                return false;
            };
            var getContentOld = this.getContent;
            this.getContent = function() {
                if (!this.isOpen()) {
                    return getContentOld.call(this);
                }
                var xml = Blockly.Xml.workspaceToDom(this.workspacePlayground);
                var xmlText = Blockly.Xml.domToPrettyText(xml);
                return xmlText;
            };
            var setContentOld = this.setContent;
            this.setContent = function(c) {
                setContentOld.call(this, c);
                if (this.isOpen()) {
                    this.workspacePlayground.clear();
                    var xml = Blockly.Xml.textToDom(c);
                    Blockly.Xml.domToWorkspace(xml, this.workspacePlayground);
                }
            };
            this.close = function() {
                if (this.isOpen()) {
                    setContentOld.call(this, this.getContent());
                    this.workspacePlayground.dispose();
                    this.workspacePlayground = false;
                    this.firstFocus = true;
                    this.setOpen(false);
                }
            };
            this.blocklyNotLoaded = true;
            this.blocklyToolbox = false;
            this.blockly0Toolbox = false;
            this.blockly1Toolbox = false;
            this.blockly2Toolbox = false;
            this.blockly3Toolbox = false;
            this.blocklyStrs = [
                'basic',
                'intermediate',
                'advanced',
                'variables',
                'operatorsvalues',
                'control',
                'inputoutput',
                'functions',
                'lists',
                'math',
                'text',
                'run',
                'start',
                'startanimate',
                'stop',
                'pause',
                'resume',
                'step'
            ];
            this.blocklyIn18 = function(data) {
                var l = this.blocklyStrs.length;
                for (var i = 0; i < l; i++) {
                    var str = this.blocklyStrs[i];
                    var reg = new RegExp('\\[\\[' + str + '\\]\\]', 'g');
                    var rep = VPLUtil.str(str);
                    data = data.replace(reg, rep);
                }
                return data;
            };
        };
    }
);
