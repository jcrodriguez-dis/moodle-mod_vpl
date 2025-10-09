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
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

import $ from 'jquery';
/* eslint-disable no-unused-vars */
import jqui from 'jqueryui';
/* eslint-enable no-unused-vars */
import {VPLUtil} from 'mod_vpl/vplutil';
import {VPLUI} from 'mod_vpl/vplui';
import {codeExtension} from 'mod_vpl/vplidecodefile';
import {blocklyExtension} from 'mod_vpl/vplideblocklyfile';
import {binaryExtension} from 'mod_vpl/vplidebinaryfile';

export const VPLFile = function(id, name, value, fileManager, vplIdeInstance) {
    var tid = "#vpl_file" + id;
    var tabnameid = "#vpl_tab_name" + id;
    var fileName = name;
    var modified = true;
    var opened = false;
    var langType = 'text';
    var self = this;
    this.getContent = function() {
        return value;
    };
    this.setContent = function(c) {
        value = c;
    };
    this.getFileManager = function() {
        return fileManager;
    };
    this.getFileName = function() {
        return fileName;
    };
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
    this.isModified = function() {
        return modified;
    };
    this.resetModified = function() {
        modified = false;
        this.showFileName();
    };
    this.setModified = function() {
        modified = true;
        this.showFileName();
    };
    this.getTabPos = function() {
        return fileManager.getTabPos(this);
    };
    this.setLang = function(lang) {
        langType = lang;
    };
    this.getLang = function() {
        return langType;
    };
    this.isOpen = function() {
        return opened;
    };
    this.setOpen = function(openState) {
        opened = openState;
    };
    this.getVPLIDE = function() {
        return vplIdeInstance;
    };
    this.change = function() {
        if (!modified) {
            this.setModified();
            fileManager.generateFileList();
            this.showFileName();
            VPLUtil.longDelay('setModified', fileManager.setModified);
        }
    };
    this.setFileName = function(name) {
        if (!VPLUtil.validPath(name)) {
            return false;
        }
        if (name != fileName) {
            fileName = name;
            self.change();
        }
        this.setReadOnly(fileManager.isReadOnly(name));
        if (!this.isOpen()) {
            return true;
        }
        this.showFileName();
        this.langSelection();
        return true;
    };
    this.showFileName = function() {
        var name = this.getFileName();
        var fn = VPLUtil.getFileName(name);
        if (fn.length > 20) {
            fn = fn.substring(0, 16) + '...';
        }
        var html = (modified ? VPLUI.iconModified() : '') + fn;
        if (this.isReadOnly()) {
            html = html + VPLUI.iconReadOnly();
        } else if (this.getId() < fileManager.minNumberOfFiles) {
            html = html + VPLUI.iconRequired();
        }
        html = html + VPLUI.iconClose();
        $(tabnameid + ' a').html(html);
        if (fn != name) {
            $(tabnameid + ' a').attr('title', name);
        }
        VPLUtil.afterAll('adjustTabsTitles' + self.id, function() {
            fileManager.adjustTabsTitles(true);
            VPLUtil.delay('adjustTabsTitles' + self.id, function() {
                self.adjustSize();
            });
        });
    };

    this.destroy = function() {
        $(tabnameid).remove();
        $(tid).remove();
    };

    this.adjustSize = function() {
        if (!this.isOpen()) {
            return false;
        }
        var editTag = $(tid);
        var tabs = editTag.parent();
        if (editTag.length === 0) {
            return false;
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
    this.updateStatus = VPLUI.hideIDEStatus;
    this.gotoLine = VPLUtil.doNothing;
    this.setReadOnly = VPLUtil.doNothing;
    this.isReadOnly = VPLUtil.returnFalse;
    this.focus = VPLUI.hideIDEStatus;
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
    this.isBinary = VPLUtil.returnFalse;
    // Adds support for current extensions
    this.extendToCodeEditor = codeExtension;
    this.extendToBlockly = blocklyExtension;
    this.extendToBinary = binaryExtension;
};
