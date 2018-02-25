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
 * File managament
 *
 * @package mod_vpl
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
/* globals Interpreter */
/* globals ace */
/* globals Blockly */

define(['jquery',
         'jqueryui',
         'mod_vpl/vplutil',
         ],
         function($JQVPL, jqui , VPL_Util) {
    if ( typeof VPL_File != 'undefined') {
        return VPL_File;
    }
    var VPL_File = function(id, name, value, file_manager, vpl_ide) {
        var tid = "#vpl_file" + id;
        var tabnameid = "#vpl_tab_name" + id;
        var fileName = name;
        var modified = true;
        var opened = false;
        var self = this;
        this.getId = function() {
            return id;
        };
        this.getTabNameId = function() {
            return tabnameid;
        };
        this.getTId = function() {
            return tid;
        };
        this.getFileName = function() {
            return fileName;
        };
        this.resetModified = function() {
            modified = false;
            this.setFileName(fileName);
        };
        this.getTabPos = function() {
            return file_manager.getTabPos(this);
        };
        this.isOpen = function() {
            return opened;
        };
        this.isModified = function() {
            return modified;
        };
        this.change = function() {
            if (!modified) {
                modified = true;
                file_manager.generateFileList();
                this.setFileName(fileName); // TODO update state of filename.
            }
            VPL_Util.longDelay('setModified', file_manager.setModified);
        };
        this.setFileName = function(name) {
            if (!VPL_Util.validPath(name)) {
                return false;
            }
            if (name != fileName) {
                fileName = name;
                self.change();
            }
            if (!opened) {
                return true;
            }
            var fn = VPL_Util.getFileName(name);
            if (fn.length > 20) {
                fn = fn.substring(0, 16) + '...';
            }
            var html = (modified ? VPL_Util.iconModified() : '') + fn;
            if (this.getTabPos() < file_manager.minNumberOfFiles) {
                html = html + VPL_Util.iconRequired();
            } else {
                html = html + VPL_Util.iconClose();
            }
            $JQVPL(tabnameid + ' a').html(html);
            if (fn != name) {
                $JQVPL(tabnameid + ' a').attr('title', name);
            }
            if (modified) {
                file_manager.adjustTabsTitles(false);
            }
            this.langSelection();
            return true;
        };
        this.getContent = function() {
            return value;
        };
        this.setContent = function(c) {
            value = c;
        };

        this.destroy = function() {
            $JQVPL(tabnameid).remove();
            $JQVPL(tid).remove();
        };

        this.adjustSize = function() {
            if (!opened) {
                return false;
            }
            var editTag = $JQVPL(tid);
            var tabs = editTag.parent();
            if (editTag.length === 0) {
                return;
            }
            var editorHeight = editTag.height();
            var editorWidth = editTag.width();
            var newHeight = tabs.height();
            newHeight -= editTag.position().top;
            var newWidth = $JQVPL('#vpl_tabs_scroll').width();
            if (newHeight != editorHeight || newWidth != editorWidth) {
                $JQVPL(editTag).height(newHeight);
                $JQVPL(editTag).width(newWidth);
                return true;
            }
            return false;
        };
        this.gotoLine = VPL_Util.doNothing;
        this.setReadOnly = VPL_Util.doNothing;
        this.focus = VPL_Util.doNothing;
        this.blur = VPL_Util.doNothing;
        this.undo = VPL_Util.doNothing;
        this.redo = VPL_Util.doNothing;
        this.selectAll = VPL_Util.doNothing;
        this.open = VPL_Util.doNothing;
        this.hasUndo = VPL_Util.returnFalse;
        this.hasRedo = VPL_Util.returnFalse;
        this.hasSelectAll = VPL_Util.returnFalse;
        this.hasFind = VPL_Util.returnFalse;
        this.hasFindReplace = VPL_Util.returnFalse;
        this.hasNext = VPL_Util.returnFalse;
        this.find = VPL_Util.doNothing;
        this.replace = VPL_Util.doNothing;
        this.next = VPL_Util.doNothing;
        this.getAnnotations = function() {
            return [];
        };
        this.setAnnotations = VPL_Util.doNothing;
        this.setFontSize = VPL_Util.doNothing;
        this.setTheme = VPL_Util.doNothing;
        this.clearAnnotations = VPL_Util.doNothing;
        this.langSelection = VPL_Util.doNothing;
        this.isBinary = function() {
            return false;
        };
        this.extendToCodeEditor = function() {
            var editor = null;
            var session = null;
            var readOnly = file_manager.readOnly;
            this.getContent = function() {
                if (!opened) {
                    return value;
                }
                return editor.getValue();
            };
            this.setContent = function(c) {
                value = c;
                if (opened) {
                    editor.setValue(c);
                }
            };
            this.oldDestroy = this.destroy;
            this.destroy = function() {
                if (opened) {
                    editor.destroy();
                }
                this.oldDestroy();
            };
            this.setFontSize = function(size) {
                if (opened) {
                    editor.setFontSize(size);
                }
            };
            this.oldAdjustSize = this.adjustSize;
            this.adjustSize = function() {
                if (this.oldAdjustSize()) {
                    editor.resize();
                    return true;
                }
                return false;
            };
            this.gotoLine = function(line) {
                if (!opened) {
                    return;
                }
                editor.gotoLine(line, 0);
                editor.scrollToLine(line, true);
                editor.focus();
            };
            this.setReadOnly = function(s) {
                readOnly = s;
                if (opened) {
                    editor.setReadOnly(s);
                }
            };
            this.focus = function() {
                if (!opened) {
                    return;
                }
                $JQVPL(tid).removeClass('ui-widget-content ui-tabs-panel');
                $JQVPL(tid).addClass('ui-corner-bottom');
                this.adjustSize();
                editor.focus();
            };
            this.blur = function() {
                if (!opened) {
                    return;
                }
                editor.blur();
            };
            this.undo = function() {
                if (!opened) {
                    return;
                }
                editor.undo();
                editor.focus();
            };
            this.redo = function() {
                if (!opened) {
                    return;
                }
                editor.redo();
                editor.focus();
            };
            this.selectAll = function() {
                if (!opened) {
                    return;
                }
                editor.selectAll();
                editor.focus();
            };
            this.hasUndo = function() {
                if (!opened) {
                    return false;
                }
                return session.getUndoManager().hasUndo();
            };
            this.hasRedo = function() {
                if (!opened) {
                    return false;
                }
                return session.getUndoManager().hasRedo();
            };
            this.hasSelectAll = VPL_Util.returnTrue;
            this.hasFind = VPL_Util.returnTrue;
            this.hasFindReplace = VPL_Util.returnTrue;
            this.hasNext = VPL_Util.returnTrue;
            this.find = function() {
                if (!opened) {
                    return;
                }
                editor.execCommand('find');
            };
            this.replace = function() {
                if (!opened) {
                    return;
                }
                editor.execCommand('replace');
            };
            this.next = function() {
                if (!opened) {
                    return;
                }
                editor.execCommand('findnext');
            };
            this.getAnnotations = function() {
                if (!opened) {
                    return [];
                }
                return session.getAnnotations();
            };
            this.setAnnotations = function(a) {
                if (!opened) {
                    return;
                }
                return session.setAnnotations(a);
            };
            this.clearAnnotations = function() {
                if (!opened) {
                    return;
                }
                return session.clearAnnotations();
            };
            this.langSelection = function() {
                if (!opened) {
                    return;
                }
                var ext = VPL_Util.fileExtension(fileName);
                var lang = 'text';
                if (ext !== '') {
                    lang = VPL_Util.langType(ext);
                }
                session.setMode("ace/mode/" + lang);
            };
            this.getEditor = function() {
                if (!opened) {
                    return false;
                }
                return editor;
            };
            this.setTheme = function(theme) {
                if (!opened) {
                    return;
                }
                editor.setTheme("ace/theme/" + theme);
            }
            this.open = function() {
                if ( typeof ace === 'undefined' ) {
                    VPL_Util.loadScript(['../editor/ace9/ace.js',
                        '../editor/ace9/ext-language_tools.js'], function() {self.open();});
                    return;
                }
                if (opened) {
                    return false;
                }
                // Workaround to remove jquery-ui theme background color.
                $JQVPL(tid).removeClass('ui-widget-content ui-tabs-panel');
                $JQVPL(tid).addClass('ui-corner-bottom');
                ace.require("ext/language_tools");
                opened = true;
                editor = ace.edit("vpl_file" + id);
                session = editor.getSession();
                editor.setOptions({
                    enableBasicAutocompletion : true,
                    enableSnippets : true,
                });
                editor.setFontSize(file_manager.getFontSize());
                editor.setTheme("ace/theme/" + file_manager.getTheme());
                this.setFileName(fileName);
                editor.setValue(value);
                editor.gotoLine(0, 0);
                editor.setReadOnly(readOnly);
                session.setUseSoftTabs(true);
                session.setTabSize(4);
                // Avoid undo of editor initial content.

                session.setUndoManager(new ace.UndoManager());
                // Code to control Paste and drop under restricted editing.
                editor.execCommand('replace');
                function addEventDrop() {
                    var tag = $JQVPL(tid + ' div.ace_search');
                    if (tag.length) {
                        tag.on('drop', file_manager.dropHandler);
                        var button = $JQVPL('button.ace_searchbtn_close');
                        button.css({
                            marginLeft : "1em",
                            marginRight : "1em"
                        });
                        button.trigger('click');
                    } else {
                        setTimeout(addEventDrop, 50);
                    }
                }
                editor.on('change', function() {
                    self.change();
                });
                // Try to grant dropHandler installation.
                setTimeout(addEventDrop, 5);
                // Save previous onPaste and change for a new one.
                var prevOnPaste = editor.onPaste;
                editor.onPaste = function(s) {
                    if (file_manager.restrictedEdit) {
                        editor.insert(file_manager.getClipboard());
                    } else {
                        prevOnPaste.call(editor, s);
                    }
                };
                // Control copy and cut (yes cut also use this) for localClipboard.
                editor.on('copy', function(t) {
                    file_manager.setClipboard(t);
                });
                $JQVPL(tid).on('paste', '*', file_manager.restrictedPaste);
                $JQVPL(tid + ' div.ace_content').on('drop', file_manager.dropHandler);
                $JQVPL(tid + ' div.ace_content').on('dragover', file_manager.dragoverHandler);
                this.adjustSize();
                return editor;
            };
            this.close = function() {
                opened = false;
                if (editor === null) {
                    return;
                }
                value = editor.getValue();
                editor.destroy();
                editor = null;
                session = null;
            };
        };
        this.adaptBlockly = function() {
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
        this.extendToBlockly = function() {
            this.firstFocus = true;
            this.workspacePlayground = false;
            this.focus = function() {
                if ( self.firstFocus ) {
                    if ( self.workspacePlayground ) {
                        self.firstFocus = false;
                        Blockly.Events.disable();
                        self.setContent(value);
                        VPL_Util.adjustBlockly(self.workspacePlayground, 10, 10);
                        self.workspacePlayground.scrollX = 0;
                        self.workspacePlayground.scrollY = 0;
                        Blockly.svgResize(self.workspacePlayground);
                        Blockly.resizeSvgContents(self.workspacePlayground);
                        self.adjustSize();
                        Blockly.Events.enable();
                    } else {
                        VPL_Util.longDelay('focus', self.focus);
                    }
                }
            };
            this.adjustSize = function() {
                if (!opened || !this.workspacePlayground) {
                    return false;
                }
                var editTag = $JQVPL(tid);
                if (editTag.length === 0) {
                    return false;
                }
                var tabs = editTag.parent();
                var newHeight = tabs.height();
                newHeight -= editTag.position().top;
                editTag.height(newHeight);
                $JQVPL('#' + this.bdiv).height(newHeight);
                $JQVPL('#' + this.bdiv).width(editTag.width());
                Blockly.svgResize(this.workspacePlayground);
                return false;
            };
            this.undo = function() {
                if (opened) {
                    this.workspacePlayground.undo(false);
                }
            };
            this.redo = function() {
                if (opened) {
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
                var ter = vpl_ide.getTerminal();
                if (ter.isConnected()) {
                    ter.closeLocal();
                }
                this.animateRun = animate;
                Blockly.JavaScript.STATEMENT_PREFIX = 'highlightBlock(%1);\n';
                Blockly.JavaScript.addReservedWords('highlightBlock');
                var code = Blockly.JavaScript.workspaceToCode(self.workspacePlayground);
                function initApi(interpreter, scope) {
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
                        ter.setDataCallback(function(t) {ter.writeLocal('\n');callback(t);});
                    };
                    interpreter.setProperty(scope, 'prompt',
                        interpreter.createAsyncFunction(wrapper));
                    wrapper = function(id) {
                        if (id == self.breakpoint) {
                            self.executionState = self.STEPSTATE;
                            self.updateRunButtons();
                            vpl_ide.getTerminal().setMessage(VPL_Util.str('breakpoint'));
                        }
                        if (self.animateRun || self.executionState == self.STEPSTATE) {
                            self.workspacePlayground.highlightBlock(id);
                        }
                        self.goNext = false;
                    };
                    interpreter.setProperty(scope, 'highlightBlock',
                            interpreter.createNativeFunction(wrapper));
                }
                self.interpreter = new Interpreter(code, initApi);
                ter.connectLocal(self.stop,function(){});
            };
            this.reservedWords = {'Infinity': true, 'Array': true, 'Boolean': true,
                    'Date': true, 'Error': true, 'EvalError': true,
                    'Function': true, 'JSON': true, 'Math': true,
                    'NaN': true, 'Number': true, 'Object': true, 'RangeError': true,
                    'ReferenceError': true, 'RegExp': true, 'String': true,
                    'SyntaxError': true, 'TypeError': true, 'URIError': true,
                    'alert': true, 'arguments': true, 'constructor': true, 'eval': true,
                    'highlightBlock': true, 'isFinite': true,
                    'isNaN': true, 'parseFloat': true, 'parseInt' : true, 'prompt': true,
                    'self': true, 'this': true, 'window': true};
            this.breakpoint = '';
            this.getVarValue = function(val) {
                var HTML = '';
                if ( val === null ) {
                    HTML = "<b>null</b>";
                } else if (val != undefined){
                    var type = typeof val;
                    if (type == 'string' ) {
                        HTML = '"' + VPL_Util.sanitizeText(val) + '"';
                    } else if (type == 'boolean') {
                        HTML = "<b>" + val + "</b>";
                    } else if (type == 'object' && val.class === "Array") {
                        HTML = '[';
                        var ar = val.properties;
                        for (var i = 0; i < ar.length; i++) {
                            HTML += self.getVarValue(ar[i]);
                            if ( i != ar.length - 1) {
                                HTML += ', ';
                            }
                        }
                        HTML += ']';
                    } else if (type == 'object') {
                        HTML = "<b>" + val.toString() + "</b>";
                    } else {
                        HTML += ''+ val;
                    }
                }
                return HTML;
            };
           this.getVariables = function(properties) {
                var HTML = '';
                for (var proname in properties) {
                    if ( this.reservedWords[proname] === true) {
                        continue;
                    }
                    var pro = properties[proname];
                    if (pro != undefined && !(pro.class === "Function")){
                        HTML += '<b>' + proname + "</b>: " + self.getVarValue(pro) + "<br>\n";
                    }
                }
                return HTML;
            };
            this.getParameters = function(args) {
                var HTML = '(';
                for ( var i = 0; i < args.length; i++) {
                    HTML += '' + args[i];
                    if ( i < args.length - 1) {
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
                    if (lastFunc >'' && (level.node.type == 'CallExpression' || i == stack.length - 1)) {
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
                vpl_ide.setResult({variables:HTML});
            };
            this.runLoop = function() {
                if (! self.interpreter ) {
                    return;
                }
                self.goNext = true;
                for (var i=0; i< 30000 && self.goNext; i++) {
                    if (self.executionState == self.STOPSTATE){
                        break;
                    }
                    if ( ! self.interpreter || !self.interpreter.step() ) {
                        self.executionState = self. STOPSTATE;
                        self.updateRunButtons();
                        break;
                    }
                }
                if ( self.executionState == self.STOPSTATE ) {
                    self.workspacePlayground.highlightBlock(-1);
                    vpl_ide.getTerminal().closeLocal();
                    vpl_ide.setResult({variables:''});
                    return;
                }
                if ( self.executionState == self.RUNSTATE) {
                    setTimeout(self.runLoop, 0);
                } else {
                    self.showStack(self.interpreter);
                }
            };
            this.start = function() {
                self.initRun(false);
                self.executionState = self.RUNSTATE;
                self.updateRunButtons();
                vpl_ide.getTerminal().setMessage(VPL_Util.str('start'));
                self.runLoop();
            };
            this.startAnimate = function() {
                self.initRun(true);
                self.executionState = self.RUNSTATE;
                self.updateRunButtons();
                vpl_ide.getTerminal().setMessage(VPL_Util.str('startanimate'));
                self.runLoop();
            };
            this.stop = function() {
                self.executionState = self.STOPSTATE;
                self.updateRunButtons();
                vpl_ide.getTerminal().setMessage(VPL_Util.str('stop'));
                vpl_ide.getTerminal().closeLocal();
                self.interpreter = false;
                vpl_ide.setResult({variables:''});
            };
            this.pause = function() {
                self.executionState = self.STEPSTATE;
                vpl_ide.getTerminal().setMessage(VPL_Util.str('pause'));
                self.updateRunButtons();
            };
            this.resume = function() {
                self.executionState = self.RUNSTATE;
                vpl_ide.getTerminal().setMessage(VPL_Util.str('resume'));
                self.updateRunButtons();
                self.runLoop();
            };
            this.step = function() {
                if (self.executionState == self.STOPSTATE) {
                    self.initRun(true);
                }
                self.executionState = self.STEPSTATE;
                self.updateRunButtons();
                vpl_ide.getTerminal().setMessage(VPL_Util.str('step'));
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
                var reg_ext2 = /\.([^.]+)\.blockly[123]?$/;
                var reg_fn2 = /(.+)\.blockly[123]?$/;
                var oldExt = VPL_Util.fileExtension(fileName);
                self.oldSetFileName(name);
                var ext2 = reg_ext2.exec(fileName);
                var fn2 = reg_fn2.exec(fileName);
                if ( ext2 !== null && fn2 !== null && typeof this.generatorMap[ext2[1]] == 'string' ) {
                    this.generator = this.generatorMap[ext2[1]];
                    this.generatedFilename = fn2[1];
                } else {
                    this.generator = '';
                }
                if (fn2 !== null && oldExt != VPL_Util.fileExtension(fileName)) {
                    this.setToolbox();
                }
            };

            this.changeCode = function(event) {
                if ( event.type == 'ui' && event.element == 'selected') {
                    self.breakpoint = event.newValue;
                    return;
                }
                if ( event.type == 'ui'
                        && event.element == 'category'
                        && event.newValue == VPL_Util.str('run')) {
                    self.updateRunButtons();
                    return;
                }
                if ( ! event.recordUndo ) {
                    return;
                }
                self.change();
                if ( this.generator != '' ) {
                    var code = Blockly[this.generator].workspaceToCode(self.workspacePlayground);
                    var fid = file_manager.fileNameExists(this.generatedFilename);
                    // Try to create generated code file
                    if ( fid == -1 ) {
                        file_manager.addFile({
                            name: this.generatedFilename,
                            contents: ''},
                            false, VPL_Util.doNothing, VPL_Util.doNothing);
                        fid = file_manager.fileNameExists(this.generatedFilename);
                    }
                    if ( fid != -1 ) {
                        var fc = file_manager.getFile(fid);
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
                        $JQVPL('.blocklyStartC').hide();
                        $JQVPL('.blocklyStartAnimateC').hide();
                        $JQVPL('.blocklyStopC').show();
                        $JQVPL('.blocklyPauseC').show();
                        $JQVPL('.blocklyResumeC').hide();
                        $JQVPL('.blocklyStepC').hide();
                        break;
                    }
                    case self.STEPSTATE: {
                        $JQVPL('.blocklyStartC').hide();
                        $JQVPL('.blocklyStartAnimateC').hide();
                        $JQVPL('.blocklyStopC').show();
                        $JQVPL('.blocklyPauseC').hide();
                        $JQVPL('.blocklyResumeC').show();
                        $JQVPL('.blocklyStepC').show();
                        break;
                    }
                    case self.STOPSTATE: {
                        $JQVPL('.blocklyStartC').show();
                        $JQVPL('.blocklyStartAnimateC').show();
                        $JQVPL('.blocklyStopC').hide();
                        $JQVPL('.blocklyPauseC').hide();
                        $JQVPL('.blocklyResumeC').hide();
                        $JQVPL('.blocklyStepC').show();
                        break;
                    }
                }
            };
            this.setToolbox = function() {
                var ext = VPL_Util.fileExtension(fileName);
                var toolboxname = ext + 'Toolbox';
                if (VPL_File[toolboxname] === false) {
                    $JQVPL.ajax({
                        url: '../editor/blocklytoolboxes/' + toolboxname + '.xml',
                        dataType: 'text',
                        success: function(data) {
                            VPL_File[toolboxname] = VPL_File.blocklyIn18(data);
                            self.setToolbox();
                        },
                    });
                    return;
                }
               this.workspacePlayground.updateToolbox(VPL_File[toolboxname]);
               this.workspacePlayground.registerButtonCallback('blocklyStartButton', this.start);
               this.workspacePlayground.registerButtonCallback('blocklyStartAnimateButton', this.startAnimate);
               this.workspacePlayground.registerButtonCallback('blocklyStopButton', this.stop);
               this.workspacePlayground.registerButtonCallback('blocklyPauseButton', this.pause);
               this.workspacePlayground.registerButtonCallback('blocklyResumeButton', this.resume);
               this.workspacePlayground.registerButtonCallback('blocklyStepButton', this.step);
               this.adjustSize();
            };
            this.open = function() {
                opened = true;
                this.setFileName(fileName);
                opened = false;
                if ( VPL_File.blocklyNotLoaded ){
                    VPL_Util.loadScript( [
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
                        ]
                            , function() {
                        self.adaptBlockly();
                        VPL_File.blocklyNotLoaded = false;
                        self.open();
                    });
                    return;
                }
                opened = true;
                // Workaround to remove jquery-ui theme background color.
                $JQVPL(tid).removeClass('ui-widget-content ui-tabs-panel');
                $JQVPL(tid).addClass('ui-corner-bottom');
                this.bdiv = 'bkdiv'+ id;
                $JQVPL(tid).html('<div id="' + this.bdiv + '" style="height: 480px; width: 600px;"></div>');
                var options = {
                    toolbox: '<xml><category name=""><block type="math_number"></block></category></xml>',
                    media: '../editor/blockly/media/',
                    zoom: {
                        controls: true,
                        wheel: true,
                        startScale: 1.0,
                        maxScale: 3,
                        minScale: 0.2,
                        scaleSpeed: 1.15
                    }
                };
                this.workspacePlayground = Blockly.inject( this.bdiv, options);
                this.workspacePlayground.addChangeListener(function (event) {
                    self.changeCode(event);
                });
                this.setToolbox();
                return false;
            };
            this.getContent = function() {
                if (!opened) {
                    return value;
                }
                var xml = Blockly.Xml.workspaceToDom(this.workspacePlayground);
                var xml_text = Blockly.Xml.domToPrettyText (xml);
                return xml_text;
            };
            this.setContent = function(c) {
                value = c;
                if (opened) {
                    this.workspacePlayground.clear();
                    var xml = Blockly.Xml.textToDom(c);
                    Blockly.Xml.domToWorkspace(xml, this.workspacePlayground);
                }
            };
            this.close = function() {
                value = this.getContent();
                this.workspacePlayground.dispose();
                this.workspacePlayground = false;
                this.firstFocus = true;
                opened = false;
            };
        };

        this.extendToBinary = function() {
            this.isBinary = function() {
                return true;
            };
            this.setContent = function(c) {
                modified = true;
                value = c;
                this.setFileName(fileName);
                this.updateDataURL();
            };
            this.updateDataURL = function() {
                if(VPL_Util.isImage(fileName)){
                    var prevalue = 'data:' + VPL_Util.getMIME(fileName) + ';base64,';
                    $JQVPL(tid).find('img').attr('src', prevalue + value);
                } else {
                    $JQVPL(tid).find('img').attr('src', '');
                }
            };
            this.adjustSize = function() {
                if (!opened) {
                    return false;
                }
                var editTag = $JQVPL(tid);
                if (editTag.length === 0) {
                    return;
                }
                var tabs = editTag.parent();
                var newHeight = tabs.height();
                newHeight -= editTag.position().top;
                if (newHeight != editTag.height()) {
                    editTag.height(newHeight);
                    return true;
                }
                return false;
            };
            this.open = function() {
                opened = true;
                if (VPL_Util.isImage(fileName)) {
                    $JQVPL(tid).addClass('vpl_ide_img').append('<img />');
                    this.updateDataURL();
                } else {
                    $JQVPL(tid).addClass('vpl_ide_binary').text(VPL_Util.str('binaryfile'));
                }
                this.setFileName(fileName);
                return false;
            };
            this.close = function() {
                opened = false;
            };
        };
    };
    VPL_File.blocklyNotLoaded = true;
    VPL_File.blocklyToolbox = false;
    VPL_File.blockly0Toolbox = false;
    VPL_File.blockly1Toolbox = false;
    VPL_File.blockly2Toolbox = false;
    VPL_File.blockly3Toolbox = false;
    VPL_File.blocklyStrs= [
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
    VPL_File.blocklyIn18 = function(data) {
        var l = VPL_File.blocklyStrs.length;
         for ( var i = 0; i < l; i++) {
             var str = VPL_File.blocklyStrs[i];
             var reg = new RegExp('\\[\\[' + str + '\\]\\]', 'g');
             var rep = VPL_Util.str(str);
             data = data.replace(reg, rep);
         }
         return data;
    };
    window.VPL_File = VPL_File;
    return VPL_File;

});
