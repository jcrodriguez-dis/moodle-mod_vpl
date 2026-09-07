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

import $ from 'jquery';
import {VPLUtil} from 'mod_vpl/vplutil';
import {VPLUI} from 'mod_vpl/vplui';

export const codeExtension = function() {
    var self = this;
    var editor = null;
    var session = null;
    var Range = null;
    var tooltip = null;
    var hoverTooltip = null;
    var signatureTooltip = null;
    var getOldContent = this.getContent;
    this.isCode = function() {
        return true;
    };
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
            this.close();
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
        this.updateStatus();
    };
    var oldSetReadOnly = this.setReadOnly;
    this.setReadOnly = function(s) {
        oldSetReadOnly.call(this, s);
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
        var filenamepath = this.getFileName();
        session.setMode("ace/mode/" + self.getAceLang());
        session.setTabSize(4);
        session.setUseSoftTabs(!VPLUtil.useHardTabs(filenamepath));
    };
    this.getEditor = function() {
        if (!this.isOpen()) {
            return false;
        }
        return editor;
    };
    this.getRange = function(startRow = 0, startColumn = 0, endRow = 0, endColumn = 0) {
        return new Range(startRow, startColumn, endRow, endColumn);
    };
    this.getTooltip = function() {
        return tooltip;
    };
    this.getHoverTooltip = function() {
        return hoverTooltip;
    };
    this.getSignatureTooltip = function() {
        return signatureTooltip;
    };
    this.setTheme = function(theme) {
        if (!this.isOpen()) {
            return;
        }
        editor.setTheme("ace/theme/" + theme);
        tooltip.setTheme(editor.getTheme());
        hoverTooltip.setTheme(editor.getTheme());
    };
    this.isDarkTheme = function() {
        if (!this.isOpen()) {
            return false;
        }
        const fileDOM = document.getElementById('vpl_file' + this.getId());
        return fileDOM.classList.contains('ace_dark');
    };
    this.setKeyBinding = function(binding) {
        if (!this.isOpen()) {
            return;
        }
        if (binding && binding !== 'Ace') {
            editor.setKeyboardHandler('ace/keyboard/' + binding.toLowerCase());
        } else {
            editor.setKeyboardHandler(null);
        }
    };
    this.setShowInvisibles = function(show) {
        if (!this.isOpen()) {
            return;
        }
        editor.setShowInvisibles(show);
    };
    this.setLiveAutocompletion = function(enable) {
        if (!this.isOpen()) {
            return;
        }
        editor.setOption('enableLiveAutocompletion', enable);
    };
    this.updateStatus = function() {
        if (!this.isOpen()) {
            return;
        }
        var status = {};
        var pos = editor.getCursorPosition();
        var fullname = this.getFileName();
        status.fileName = fullname;
        status.position = "Ln " + (pos.row + 1) + ', Col ' + (pos.column + 1);
        status.language = VPLUtil.langName(fullname);
        status.unsaved = this.isModified();
        status.lsp = self.getFileManager().getLSManager().getStatus(this);
        VPLUI.updateIDEStatus(status);
    };

    this.open = function() {
        this.showFileName();
        if (typeof ace === 'undefined') {
            VPLUtil.loadScript(['/../thirdpartylibs/ace/ace.js',
                '/../thirdpartylibs/ace/ext-language_tools.js'],
                function() {
                    self.open();
                });
            return false;
        }
        if (this.isOpen()) {
            return editor;
        }
        if ($('#vpl_file' + this.getId()).length === 0) {
            return false;
        }
        var fileManager = this.getFileManager();
        var tid = this.getTId();
        // Workaround to remove jquery-ui theme background color.
        $(tid).removeClass('ui-widget-content ui-tabs-panel');
        ace.require("ace/ext/language_tools");
        ace.require("ace/ext/snippets");
        const aceTooltipModule = ace.require("ace/tooltip");
        tooltip = new aceTooltipModule.Tooltip(document.body, "vpl_tooltip ace_tooltip" + this.getId());
        hoverTooltip = new aceTooltipModule.HoverTooltip();
        signatureTooltip = new aceTooltipModule.Tooltip(document.body);
        Range = ace.require("ace/range").Range;
        editor = ace.edit("vpl_file" + this.getId());
        session = editor.getSession();
        editor.setOptions({
            enableBasicAutocompletion: true,
            enableSnippets: true,
        });
        editor.setValue(this.getContent());
        const keyBinding = fileManager.getEditorKeyBinding ? fileManager.getEditorKeyBinding() : null;
        if (keyBinding && keyBinding !== 'Ace') {
            editor.setKeyboardHandler('ace/keyboard/' + keyBinding.toLowerCase());
        }
        if (fileManager.getEditorShowInvisibles) {
            editor.setShowInvisibles(fileManager.getEditorShowInvisibles());
        }
        if (fileManager.getEditorLiveAutocompletion) {
            editor.setOption('enableLiveAutocompletion', fileManager.getEditorLiveAutocompletion());
        }
        editor.$blockScrolling = Infinity;
        editor.gotoLine(1, 0);
        editor.setReadOnly(this.isReadOnly());
        // Avoid undo of editor initial content.
        session.setUndoManager(new ace.UndoManager());
        this.setOpen(true);
        editor.setFontSize(fileManager.getFontSize());
        self.setTheme(fileManager.getTheme());
        this.langSelection();
        // Code to control Paste and drop under restricted editing.
        editor.on('change', function() {
            self.change();
        });
        session.selection.on('changeCursor', function() {
            self.updateStatus();
        });
        // Control copy and cut (yes cut also use this) for localClipboard.
        editor.on('copy', function(t) {
            fileManager.setClipboard(t.text);
        });
        $(tid + ' div.ace_content').on('drop', fileManager.dropHandler);
        $(tid + ' div.ace_content').on('dragover', fileManager.dragoverHandler);
        // Workaround to avoid hidden first line in editor.
        $(tid).find('div.ace_scroller').css('position', 'static');
        this.adjustSize();
        $(tid).find('div.ace_scroller').css('position', 'absolute');
        self.updateStatus();
        fileManager.getLSManager().openFile(self);
        return editor;
    };
    this.close = function() {
        this.setOpen(false);
        if (editor === null) {
            return;
        }
        this.setContent(editor.getValue());
        VPLUI.clearIDEStatus();
        tooltip.hide();
        tooltip.destroy();
        hoverTooltip.hide();
        hoverTooltip.destroy();
        signatureTooltip.hide();
        signatureTooltip.destroy();
        editor.destroy();
        editor = null;
        session = null;
    };
};
