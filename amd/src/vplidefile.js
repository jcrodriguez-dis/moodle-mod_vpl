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
         function($, jqui , VPLUtil) {
    if ( typeof VPLFile != 'undefined') {
        return VPLFile;
    }
    var VPLFile = function(id, name, value, fileManager, vplIdeInstance) {
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
            return fileManager.getTabPos(this);
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
                fileManager.generateFileList();
                this.setFileName(fileName); // TODO update state of filename.
            }
            VPLUtil.longDelay('setModified', fileManager.setModified);
        };
        this.setFileName = function(name) {
            if (!VPLUtil.validPath(name)) {
                return false;
            }
            if (name != fileName) {
                fileName = name;
                self.change();
            }
            if (!opened) {
                return true;
            }
            var fn = VPLUtil.getFileName(name);
            if (fn.length > 20) {
                fn = fn.substring(0, 16) + '...';
            }
            var html = (modified ? VPLUtil.iconModified() : '') + fn;
            if (this.getTabPos() < fileManager.minNumberOfFiles) {
                html = html + VPLUtil.iconRequired();
            } else {
                html = html + VPLUtil.iconClose();
            }
            $(tabnameid + ' a').html(html);
            if (fn != name) {
                $(tabnameid + ' a').attr('title', name);
            }
            if (modified) {
                fileManager.adjustTabsTitles(false);
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
            $(tabnameid).remove();
            $(tid).remove();
        };

        this.adjustSize = function() {
            if (!opened) {
                return false;
            }
            var editTag = $(tid);
            var tabs = editTag.parent();
            if (editTag.length === 0) {
                return;
            }
            var editorHeight = editTag.height();
            var editorWidth = editTag.width();
            var newHeight = tabs.height();
            newHeight -= editTag.position().top;
            var newWidth = $('#vpl_tabs_scroll').width();
            if (newHeight != editorHeight || newWidth != editorWidth) {
                $(editTag).height(newHeight);
                $(editTag).width(newWidth);
                return true;
            }
            return false;
        };
        this.gotoLine = VPLUtil.doNothing;
        this.setReadOnly = VPLUtil.doNothing;
        this.focus = VPLUtil.doNothing;
        this.blur = VPLUtil.doNothing;
        this.undo = VPLUtil.doNothing;
        this.redo = VPLUtil.doNothing;
        this.selectAll = VPLUtil.doNothing;
        this.open = VPLUtil.doNothing;
        this.hasUndo = VPLUtil.returnFalse;
        this.hasRedo = VPLUtil.returnFalse;
        this.hasSelectAll = VPLUtil.returnFalse;
        this.hasFind = VPLUtil.returnFalse;
        this.hasFindReplace = VPLUtil.returnFalse;
        this.hasNext = VPLUtil.returnFalse;
        this.find = VPLUtil.doNothing;
        this.replace = VPLUtil.doNothing;
        this.next = VPLUtil.doNothing;
        this.getAnnotations = function() {
            return [];
        };
        this.setAnnotations = VPLUtil.doNothing;
        this.setFontSize = VPLUtil.doNothing;
        this.setTheme = VPLUtil.doNothing;
        this.clearAnnotations = VPLUtil.doNothing;
        this.langSelection = VPLUtil.doNothing;
        this.isBinary = function() {
            return false;
        };
        this.extendToCodeEditor = function() {
            var editor = null;
            var session = null;
            var readOnly = fileManager.readOnly;
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
                $(tid).removeClass('ui-widget-content ui-tabs-panel');
                $(tid).addClass('ui-corner-bottom');
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
            this.hasSelectAll = VPLUtil.returnTrue;
            this.hasFind = VPLUtil.returnTrue;
            this.hasFindReplace = VPLUtil.returnTrue;
            this.hasNext = VPLUtil.returnTrue;
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
                var ext = VPLUtil.fileExtension(fileName);
                var lang = 'text';
                if (ext !== '') {
                    lang = VPLUtil.langType(ext);
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
            };

            this.$_GET = function(param) {
                var vars = {};
                window.location.href.replace( location.hash, '' ).replace(
                    /[?&]+([^=&]+)=?([^&]*)?/gi, // regexp
                    function( m, key, value ) { // callback
                            vars[key] = value !== undefined ? value : '';
                    }
                );

                if ( param ) {
                    return vars[param] ? vars[param] : null;
                }
                return vars;
            };

            this.check_difference = function() {
             $.ajax({
                        method: "POST",
                        url: "../similarity/diff_check_requested_files.php",
                        data: { id : this.$_GET('id'), name: fileName, val : editor.getValue() }
                      })
                        .done(function( data ) {
                            var modified = 'ace-changed'; // css class
                            var i;
                            for (i = 0; i < data.length; i++) {
                              if(data[i].type != "=") {
                                  editor.session.removeGutterDecoration(data[i].ln2-1, modified);
                                  editor.session.addGutterDecoration(data[i].ln2-1, modified);
                              } else {
                                  editor.session.removeGutterDecoration(data[i].ln2-1, modified);
                              }
                            }
                        });
            };

            this.open = function() {
                if ( typeof ace === 'undefined' ) {
                    VPLUtil.loadScript(['../editor/ace9/ace.js',
                        '../editor/ace9/ext-language_tools.js'], function() {self.open();});
                    return;
                }
                if (opened) {
                    return false;
                }
                // Workaround to remove jquery-ui theme background color.
                $(tid).removeClass('ui-widget-content ui-tabs-panel');
                $(tid).addClass('ui-corner-bottom');
                ace.require("ext/language_tools");
                opened = true;
                editor = ace.edit("vpl_file" + id);
                session = editor.getSession();
                editor.setOptions({
                    enableBasicAutocompletion : true,
                    enableSnippets : true,
                });
                editor.setFontSize(fileManager.getFontSize());
                editor.setTheme("ace/theme/" + fileManager.getTheme());
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
                    var tag = $(tid + ' div.ace_search');
                    if (tag.length) {
                        tag.on('drop', fileManager.dropHandler);
                        var button = $('button.ace_searchbtn_close');
                        button.css({
                            marginLeft : "1em",
                            marginRight : "1em"
                        });
                        button.trigger('click');
                    } else {
                        setTimeout(addEventDrop, 50);
                    }
                }

                this.check_difference();

                editor.on('change', function() {
                    self.change();
                    self.check_difference();
                });
                // Try to grant dropHandler installation.
                setTimeout(addEventDrop, 5);
                // Save previous onPaste and change for a new one.
                var prevOnPaste = editor.onPaste;
                editor.onPaste = function(s) {
                    if (fileManager.restrictedEdit) {
                        editor.insert(fileManager.getClipboard());
                    } else {
                        prevOnPaste.call(editor, s);
                    }
                };
                // Control copy and cut (yes cut also use this) for localClipboard.
                editor.on('copy', function(t) {
                    fileManager.setClipboard(t);
                });
                $(tid).on('paste', '*', fileManager.restrictedPaste);
                $(tid + ' div.ace_content').on('drop', fileManager.dropHandler);
                $(tid + ' div.ace_content').on('dragover', fileManager.dragoverHandler);
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
                if (!opened || !this.workspacePlayground) {
                    return false;
                }
                var editTag = $(tid);
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
                var ter = vplIdeInstance.getTerminal();
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
                            vplIdeInstance.getTerminal().setMessage(VPLUtil.str('breakpoint'));
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
                        HTML = '"' + VPLUtil.sanitizeText(val) + '"';
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
                vplIdeInstance.setResult({variables:HTML});
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
                    vplIdeInstance.getTerminal().closeLocal();
                    vplIdeInstance.setResult({variables:''});
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
                vplIdeInstance.setResult({variables:''});
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
                var oldExt = VPLUtil.fileExtension(fileName);
                self.oldSetFileName(name);
                var ext2 = regExt2.exec(fileName);
                var fn2 = regFn2.exec(fileName);
                if ( ext2 !== null && fn2 !== null && typeof this.generatorMap[ext2[1]] == 'string' ) {
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
                if ( event.type == 'ui' && event.element == 'selected') {
                    self.breakpoint = event.newValue;
                    return;
                }
                if ( event.type == 'ui'
                        && event.element == 'category'
                        && event.newValue == VPLUtil.str('run')) {
                    self.updateRunButtons();
                    return;
                }
                if ( ! event.recordUndo ) {
                    return;
                }
                self.change();
                if ( this.generator != '' ) {
                    var code = Blockly[this.generator].workspaceToCode(self.workspacePlayground);
                    var fid = fileManager.fileNameExists(this.generatedFilename);
                    // Try to create generated code file
                    if ( fid == -1 ) {
                        fileManager.addFile({
                            name: this.generatedFilename,
                            contents: ''},
                            false, VPLUtil.doNothing, VPLUtil.doNothing);
                        fid = fileManager.fileNameExists(this.generatedFilename);
                    }
                    if ( fid != -1 ) {
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
                var ext = VPLUtil.fileExtension(fileName);
                var toolboxname = ext + 'Toolbox';
                if (VPLFile[toolboxname] === false) {
                    $.ajax({
                        url: '../editor/blocklytoolboxes/' + toolboxname + '.xml',
                        dataType: 'text',
                        success: function(data) {
                            VPLFile[toolboxname] = VPLFile.blocklyIn18(data);
                            self.setToolbox();
                        },
                    });
                    return;
                }
               this.workspacePlayground.updateToolbox(VPLFile[toolboxname]);
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
                if ( VPLFile.blocklyNotLoaded ){
                    VPLUtil.loadScript( [
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
                        VPLFile.blocklyNotLoaded = false;
                        self.open();
                    });
                    return;
                }
                opened = true;
                var horizantalMenu = false;
                if( /.*[0-9]$/.test(VPLUtil.fileExtension(fileName)) ) {
                    var horizantalMenu = true;
                }
                // Workaround to remove jquery-ui theme background color.
                $(tid).removeClass('ui-widget-content ui-tabs-panel');
                $(tid).addClass('ui-corner-bottom');
                this.bdiv = 'bkdiv'+ id;
                $(tid).html('<div id="' + this.bdiv + '" style="height: 480px; width: 600px;"></div>');
                var options = {
                    toolbox: '<xml><category name=""><block type="math_number"></block></category></xml>',
                    media: '../editor/blockly/media/',
                    horizontalLayout: horizantalMenu,
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
                var xmlText = Blockly.Xml.domToPrettyText (xml);
                return xmlText;
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
                if(VPLUtil.isImage(fileName)){
                    var prevalue = 'data:' + VPLUtil.getMIME(fileName) + ';base64,';
                    $(tid).find('img').attr('src', prevalue + value);
                } else {
                    $(tid).find('img').attr('src', '');
                }
            };
            this.adjustSize = function() {
                if (!opened) {
                    return false;
                }
                var editTag = $(tid);
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
                if (VPLUtil.isImage(fileName)) {
                    $(tid).addClass('vpl_ide_img').append('<img />');
                    this.updateDataURL();
                } else {
                    $(tid).addClass('vpl_ide_binary').text(VPLUtil.str('binaryfile'));
                }
                this.setFileName(fileName);
                return false;
            };
            this.close = function() {
                opened = false;
            };
        };
    };
    VPLFile.blocklyNotLoaded = true;
    VPLFile.blocklyToolbox = false;
    VPLFile.blockly0Toolbox = false;
    VPLFile.blockly1Toolbox = false;
    VPLFile.blockly2Toolbox = false;
    VPLFile.blockly3Toolbox = false;
    VPLFile.blocklyStrs= [
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
    VPLFile.blocklyIn18 = function(data) {
        var l = VPLFile.blocklyStrs.length;
         for ( var i = 0; i < l; i++) {
             var str = VPLFile.blocklyStrs[i];
             var reg = new RegExp('\\[\\[' + str + '\\]\\]', 'g');
             var rep = VPLUtil.str(str);
             data = data.replace(reg, rep);
         }
         return data;
    };
    window.VPLFile = VPLFile;
    return VPLFile;

});
