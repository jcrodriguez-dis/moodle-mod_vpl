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

/* globals ace */

define(
    [
        'jquery',
        'mod_vpl/vplutil',
    ],
    function($, VPLUtil) {
        return function() {
            var self = this;
            var editor = null;
            var session = null;
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
                    editor.destroy();
                }
                oldDestroy.call(this);
            };
            this.setFontSize = function(size) {
                if (this.isOpen()) {
                    editor.setFontSize(size);
                }
            };
            var oldAdjustSize = this.adjustSize;
            this.adjustSize = function() {
                if (oldAdjustSize.call(this)) {
                    editor.resize(true);
                    return true;
                }
                return false;
            };
            this.gotoLine = function(line) {
                if (!this.isOpen()) {
                    return;
                }
                editor.gotoLine(line, 0);
                editor.scrollToLine(line, true);
                editor.focus();
            };
            this.setReadOnly = function(s) {
                readOnly = s;
                if (this.isOpen()) {
                    editor.setReadOnly(s);
                }
            };
            this.focus = function() {
                if (!this.isOpen()) {
                    return;
                }
                var tid = this.getTId();
                // Workaround to remove JQwery-UI background color.
                $(tid).removeClass('ui-widget-content ui-tabs-panel');
                editor.focus();
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
                editor.undo();
                editor.focus();
            };
            this.redo = function() {
                if (!this.isOpen()) {
                    return;
                }
                editor.redo();
                editor.focus();
            };
            this.selectAll = function() {
                if (!this.isOpen()) {
                    return;
                }
                editor.selectAll();
                editor.focus();
            };
            this.hasUndo = function() {
                if (!this.isOpen()) {
                    return false;
                }
                return session.getUndoManager().hasUndo();
            };
            this.hasRedo = function() {
                if (!this.isOpen()) {
                    return false;
                }
                return session.getUndoManager().hasRedo();
            };
            this.hasSelectAll = VPLUtil.returnTrue;
            this.hasFind = VPLUtil.returnTrue;
            this.hasFindReplace = VPLUtil.returnTrue;
            this.hasNext = VPLUtil.returnTrue;
            this.find = function() {
                if (!this.isOpen()) {
                    return;
                }
                editor.execCommand('find');
            };
            this.replace = function() {
                if (!this.isOpen()) {
                    return;
                }
                editor.execCommand('replace');
            };
            this.next = function() {
                if (!this.isOpen()) {
                    return;
                }
                editor.execCommand('findnext');
            };
            this.getAnnotations = function() {
                if (!this.isOpen()) {
                    return [];
                }
                return session.getAnnotations();
            };
            this.setAnnotations = function(a) {
                if (!this.isOpen()) {
                    return false;
                }
                return session.setAnnotations(a);
            };
            this.clearAnnotations = function() {
                if (!this.isOpen()) {
                    return false;
                }
                return session.clearAnnotations();
            };
            this.langSelection = function() {
                if (!this.isOpen()) {
                    return;
                }
                var ext = VPLUtil.fileExtension(this.getFileName());
                var lang = (ext !== '') ? VPLUtil.langType(ext) : 'text';
                session.setMode("ace/mode/" + lang);
                this.setLang(lang);
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
                editor.setTheme("ace/theme/" + theme);
            };
            this.open = function() {
                this.showFileName();
                if (typeof ace === 'undefined') {
                    VPLUtil.loadScript(['../editor/ace9/ace.js',
                        '../editor/ace9/ext-language_tools.js'],
                        function() {
                            self.open();
                        });
                    return false;
                }
                if (this.isOpen()) {
                    return editor;
                }
                var fileManager = this.getFileManager();
                var tid = this.getTId();
                // Workaround to remove jquery-ui theme background color.
                $(tid).removeClass('ui-widget-content ui-tabs-panel');
                ace.require("ext/language_tools");
                editor = ace.edit("vpl_file" + this.getId());
                session = editor.getSession();
                editor.setOptions({
                    enableBasicAutocompletion: true,
                    enableSnippets: true,
                });
                editor.setValue(this.getContent());
                editor.setFontSize(fileManager.getFontSize());
                editor.setTheme("ace/theme/" + fileManager.getTheme());
                editor.$blockScrolling = Infinity;
                editor.gotoLine(1, 0);
                editor.setReadOnly(readOnly);
                session.setUseSoftTabs(true);
                session.setTabSize(4);
                // Avoid undo of editor initial content.
                session.setUndoManager(new ace.UndoManager());
                this.setOpen(true);
                this.langSelection();
                // Code to control Paste and drop under restricted editing.
                editor.execCommand('replace');
                var addEventDrop = function() {
                    var tag = $(tid + ' div.ace_search');
                    if (tag.length) {
                        tag.on('drop', fileManager.dropHandler);
                        var button = $('.ace_searchbtn_close');
                        button.trigger('click');
                    } else {
                        setTimeout(addEventDrop, 50);
                    }
                };
                editor.on('change', function() {
                    self.change();
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
                    fileManager.setClipboard(t.text);
                });
                $(tid).on('paste', '*', fileManager.restrictedPaste);
                $(tid + ' div.ace_content').on('drop', fileManager.dropHandler);
                $(tid + ' div.ace_content').on('dragover', fileManager.dragoverHandler);
                // Workaround to avoid hidden first line in editor.
                $(tid).find('div.ace_scroller').css('position', 'static');
                this.adjustSize();
                $(tid).find('div.ace_scroller').css('position', 'absolute');
                return editor;
            };
            this.close = function() {
                this.setOpen(false);
                if (editor === null) {
                    return;
                }
                this.setContent(editor.getValue());
                editor.destroy();
                editor = null;
                session = null;
            };
        };
    }
);
