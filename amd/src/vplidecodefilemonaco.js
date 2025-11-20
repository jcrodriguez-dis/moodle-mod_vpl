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
 * Code files extension method using ACE editorfiles. Add to VPLFile object.
 *
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/* globals requirejs */
import $ from 'jquery';
import {VPLUtil} from 'mod_vpl/vplutil';
import {VPLUI} from 'mod_vpl/vplui';
import console from 'core/log';
import url from 'core/url';

export const codeExtensionMonaco = function() {
    var monaco = null;
    var self = this;
    var editor = null;
    var opening = false;
    var readOnly = self.getFileManager().readOnly;
    var getOldContent = this.getContent;
    this.getContent = function() {
        if (!this.isOpen()) {
            return getOldContent.call(this);
        }
        return editor.getValue();
    };
    var setOldContent = this.setContent;
    this.setContent = function(c) {
        setOldContent.call(this, c);
        if (this.isOpen()) {
            editor.setValue(c);
        }
    };
    var oldDestroy = this.destroy;
    this.destroy = function() {
        if (this.isOpen()) {
            editor.dispose();
        }
        oldDestroy.call(this);
    };
    this.setFontSize = function(size) {
        if (this.isOpen()) {
            editor.updateOptions({fontSize: size});
        }
    };
    var oldAdjustSize = this.adjustSize;
    this.adjustSize = function() {
        if (oldAdjustSize.call(this)) {
            editor.layout();
            return true;
        }
        return false;
    };
    this.gotoLine = function(line) {
        if (!this.isOpen()) {
            return;
        }
        var position = {lineNumber: line, column: 1};
        editor.setPosition(position);
        editor.revealPositionInCenter(position);
        editor.focus();
        this.updateStatus();
    };
    this.setReadOnly = function(s) {
        readOnly = s;
        if (this.isOpen()) {
            editor.updateOptions({readOnly: s});
        }
    };
    this.isReadOnly = function() {
        return readOnly;
    };
    this.focus = function() {
        if (!this.isOpen()) {
            return;
        }
        var tid = this.getTId();
        // Workaround to remove JQwery-UI background color.
        $(tid).removeClass('ui-widget-content ui-tabs-panel');
        editor.focus();
        this.updateStatus();
    };
    this.blur = function() {
        if (!this.isOpen()) {
            return;
        }
        editor.blur();
    };
    this.undo = function() {
        if (!this.isOpen()) {
            return;
        }
        editor.trigger('keyboard', 'undo', null);
        editor.focus();
    };
    this.redo = function() {
        if (!this.isOpen()) {
            return;
        }
        editor.trigger('keyboard', 'redo', null);
        editor.focus();
    };
    this.selectAll = function() {
        if (!this.isOpen()) {
            return;
        }
        editor.trigger('keyboard', 'editor.action.selectAll', null);
        editor.focus();
    };
    this.hasUndo = function() {
        if (!this.isOpen()) {
            return false;
        }
        var model = editor.getModel();
        return model.canUndo && model.canUndo();
    };
    this.hasRedo = function() {
        if (!this.isOpen()) {
            return false;
        }
        var model = editor.getModel();
        return model.canRedo && model.canRedo();
    };
    this.hasSelectAll = VPLUtil.returnTrue;
    this.hasFind = VPLUtil.returnTrue;
    this.hasFindReplace = VPLUtil.returnTrue;
    this.hasNext = VPLUtil.returnTrue;
    this.find = function() {
        if (!this.isOpen()) {
            return;
        }
        var action = editor.getAction('actions.find');
        if (action) {
            action.run();
        }
    };
    this.replace = function() {
        if (!this.isOpen()) {
            return;
        }
        editor.getAction('editor.action.replaceOne').run();
    };
    this.next = function() {
        if (!this.isOpen()) {
            return;
        }
        editor.getAction('editor.action.nextMatchFindAction').run();
    };
    this.getAnnotations = function() {
        return [];
    };
    this.setAnnotations = function(a) {
        if (!this.isOpen()) {
            return false;
        }
        // Adapt to monaco markers
        //{row:,column:,raw:,type:error,warning,info;text}
        var markers = [];
        for (var i = 0; i < a.length; i++) {
            var ann = a[i];
            var marker = {};
            marker.severity = monaco.MarkerSeverity.Info;
            if (ann.type === 'error') {
                marker.severity = monaco.MarkerSeverity.Error;
            } else if (ann.type === 'warning') {
                marker.severity = monaco.MarkerSeverity.Warning;
            }
            marker.startLineNumber = ann.row;
            marker.startColumn = ann.column;
            marker.endLineNumber = ann.row;
            marker.endColumn = ann.column + 10;
            marker.message = ann.text;
            markers.push(marker);
        }
        return monaco.editor.setModelMarkers(editor.getModel(), 'vpl-id', markers);
    };

    this.clearAnnotations = function() {
        if (!this.isOpen()) {
            return false;
        }
        return monaco.editor.setModelMarkers(editor.getModel(), 'vpl-id', []);
    };

    this.isLanguageLoaded = function(lang) {
        if (monaco === null) {
            return false;
        }
        var languages = monaco.languages.getLanguages();
        for (var i = 0; i < languages.length; i++) {
            if (languages[i].id === lang) {
                return true;
            }
        }
        return false;
    };
    this.registerLanguage = async function(lang) {
        if (monaco === null) {
            return false;
        }
        if (!this.isLanguageLoaded(lang)) {
            const moduleName = 'monaco-editor/esm/vs/basic-languages/'+ lang + '/' + lang + '.contribution';
            return VPLUtil.loadModule(moduleName, 'vpl_monaco_lang_' + lang, false);
        }
        return true;
    };
    this.langSelection = function() {
        if (!this.isOpen()) {
            return;
        }
        var filenamepath = this.getFileName();
        var lang = VPLUtil.langType(filenamepath);
        var options = {
            tabSize: 4,
            insertSpaces: !VPLUtil.useHardTabs(filenamepath),
        };
        editor.updateOptions(options);
        if (lang == "plain_text") {
            lang = "plaintext";
        }
        if (lang == "c_cpp") {
            lang = "cpp";
        }
        this.registerLanguage(lang).then(() => {
            var uri = monaco.Uri.parse("file:///" + filenamepath);
            var model = monaco.editor.getModel(uri);
            if (model) {
                monaco.editor.setModelLanguage(model, lang);
                model.setValue(self.getContent());
            } else {
                model = monaco.editor.createModel(
                    self.getContent(),
                    lang,
                    uri
                );
            }
            var oldModel = editor.getModel();
            editor.setModel(model);
            if (oldModel && oldModel !== model) {
                oldModel.dispose();
            }
            this.setLang(lang);
            editor.layout();
        }).catch((error) => {
            console.error('Failed to load language ' + lang + ': ' + error);
        });
    };
    this.getEditor = function() {
        if (!this.isOpen()) {
            return false;
        }
        return editor;
    };
    this.setTheme = function(theme) {
        if (!this.isOpen()) {
            return;
        }
        monaco.editor.setTheme("vs" + (theme.includes('dark') ? '-dark' : ''));
    };
    this.updateStatus = function() {
        if (!this.isOpen()) {
            return;
        }
        var text = '';
        var pos = editor.getPosition();
        var fullname = this.getFileName();
        var name = VPLUtil.getFileName(fullname);
        if (fullname.length > 20 || name != fullname) {
            text = fullname + ' ';
        }
        text += "Ln " + pos.lineNumber + ', Col ' + pos.column;
        text += " " + VPLUtil.langName(name);
        VPLUI.showIDEStatus(text);
    };

    this.open = function() {
        var idefile = this;
        idefile.showFileName();
        if (idefile.isOpen()) {
            return editor;
        }
        if (opening) {
            return null;
        }
        opening = true;
        this.getMonaco().then(function(monacoModule) {
            opening = false;
            monaco = monacoModule;
            var fileManager = idefile.getFileManager();
            var tid = idefile.getTId();
            // Workaround to remove jquery-ui theme background color.
            $(tid).removeClass('ui-widget-content ui-tabs-panel vpl_ide_file ui-corner-bottom');
            editor = monaco.editor.create(document.getElementById("vpl_file" + idefile.getId()), {
                value: idefile.getContent(),
                language: VPLUtil.langType(idefile.getFileName()),
                fontSize: fileManager.getFontSize(),
                readOnly: readOnly
            });
            this.setTheme(fileManager.getTheme());
            idefile.setOpen(true);
            idefile.langSelection();
            editor.onDidChangeCursorPosition(function() {
                idefile.updateStatus();
            });
            editor.onDidChangeModelContent(function() {
                if (!idefile.isModified()) {
                    idefile.setModified();
                }
                idefile.updateStatus();
            });
        }).catch(function(error) {
            opening = false;
            console.error('Failed to load Monaco:' + error);
            return false;
        });
        return editor;
    };
    this.close = function() {
        this.setOpen(false);
        if (editor === null) {
            return;
        }
        this.setContent(editor.getValue());
        editor.dispose();
        editor = null;
    };
    this.getMonaco = async function() {
        return new Promise((resolve, reject) => {
            try {
                const base = url.relativeUrl('/mod/vpl/thirdpartylibs/monaco-editor/min/vs');
                const baseRel = '/../thirdpartylibs/monaco-editor/min/vs';
                VPLUtil.loadScript([baseRel + '/loader.js'], function() {
                    requirejs.config({paths: {vs: base}});
                    if (typeof window.MonacoEnvironment === 'undefined') {
                        window.MonacoEnvironment = {
                            getWorkerUrl: function(_moduleId, label) {
                                    const workers = {
                                        json:        base + 'language/json/json.worker.js',
                                        css:         base + 'language/css/css.worker.js',
                                        html:        base + 'language/html/html.worker.js',
                                        typescript:  base + 'language/typescript/ts.worker.js',
                                        javascript:  base + 'language/typescript/ts.worker.js',
                                        editor:      base + 'editor/editor.worker.js',
                                    };
                                    return workers[label] || workers.editor;
                                }
                        };
                    }
                    require(['vs/editor/editor.main'], function() {
                        console.debug('Monaco loaded');
                        resolve(window.monaco);
                    });
                });
            } catch (e) {
                console.error('Failed to load Monaco:' + e);
                reject(e);
            }
        });
    };

    this.getMonacoEms = async function() {
        const variable = 'vpl_monaco';
        const moduleName = 'monaco-editor/esm/vs/editor/editor.api';
        const base = url.relativeUrl('/mod/vpl/thirdpartylibs/monaco-editor/esm/vs/');
        if (typeof window[variable] !== 'undefined') {
            return Promise.resolve(window[variable]);
        }
        if (typeof window.MonacoEnvironment === 'undefined') {
            window.MonacoEnvironment = {
                getWorkerUrl: function(_moduleId, label) {
                        const workers = {
                            json:        base + 'language/json/json.worker.js',
                            css:         base + 'language/css/css.worker.js',
                            html:        base + 'language/html/html.worker.js',
                            typescript:  base + 'language/typescript/ts.worker.js',
                            javascript:  base + 'language/typescript/ts.worker.js',
                            editor:      base + 'editor/editor.worker.js',
                        };
                        return workers[label] || workers.editor;
                    }
            };
        }
        await VPLUtil.loadModule(moduleName, variable, false);
        return Promise.resolve(window[variable]);
    };
};
