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
 * Binary files extension methods. Add to VPLFile object.
 *
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

import $ from 'jquery';
import {VPLUtil} from 'mod_vpl/vplutil';
import {VPLUI} from 'mod_vpl/vplui';

export const binaryExtension = function() {
    var type2HTML = {
        'img': '<img />',
        'audio': '<audio controls></audio>',
        'video': '<video controls></video>',
        'embed': '<embed></embed>',
        'binary': '<div></div>'
    };
    this.isBinary = function() {
        return true;
    };
    this.getType = function() {
        if (VPLUtil.isImage(this.getFileName())) {
            return 'img';
        }
        if (VPLUtil.isAudio(this.getFileName())) {
            return 'audio';
        }
        if (VPLUtil.isVideo(this.getFileName())) {
            return 'video';
        }
        if (VPLUtil.getMIME(this.getFileName()) !== 'application/octet-stream') {
            return 'embed';
        }
        return 'binary';
    };
    var setOldContent = this.setContent;
    this.setContent = function(c) {
        setOldContent.call(this, c);
        this.setModified();
        this.updateDataURL(this.getType(), this.getFileName(), c);
    };
    this.updateDataURL = function(type, fileName, value) {
        var tid = this.getTId();
        var prevalue = 'data:' + VPLUtil.getMIME(fileName) + ';base64,';
        var element = $(tid).find(type);
        element.attr('src', prevalue + value);
    };
    this.adjustSize = function() {
        if (!this.isOpen()) {
            return false;
        }
        var editTag = $(this.getTId());
        if (editTag.length === 0) {
            return false;
        }
        var change = false;
        var tabs = editTag.parent();
        var newHeight = tabs.height();
        newHeight -= editTag.position().top;
        if (newHeight != editTag.height()) {
            editTag.height(newHeight);
            change = true;
        }
        var newWidth = $('#vpl_tabs_scroll').width();
        if (newWidth != editTag.width()) {
            editTag.width(newWidth);
            change = true;
        }
        return change;
    };
    this.open = function() {
        this.showFileName();
        var tid = this.getTId();
        this.setOpen(true);
        var type = this.getType();
        $(tid).addClass('vpl_ide_' + type).append(type2HTML[type]);
        if (type === 'binary') {
            var text = VPLUtil.str('binaryfile') + ": ";
            text += "'" + this.getFileName() + "'";
            text += " (" + this.getHumanReadableSize() + ")";
            $(tid).find('div').text(text);
        } else {
            this.updateDataURL(this.getType(), this.getFileName(), this.getContent());
        }
    };
    this.close = function() {
        this.setOpen(false);
    };
    /**
     * Returns the size of the content in a human-readable string (e.g., 1.2 MB, 512 KB).
     * @returns {string} Human-readable size string.
     */
    this.getHumanReadableSize = function() {
        var base64 = this.getContent();
        // Calculate decoded byte length from base64 string
        let extra = 0;
        for (let i = 0; i < base64.length; i++) {
            let char = base64[i];
             // Ignore padding characters and newlines
            if (char === '=') {
                extra++;
            }
            if (char === '\n') {
                extra++;
            }
            if (char === '\r') {
                extra++;
            }
        }
        var bytes = Math.floor((base64.length - extra) * 3 / 4);
        if (bytes === 0) {
            return '0 B';
        }
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };
    this.updateStatus = function() {
        VPLUI.updateIDEStatus(
            {
                fileName: this.getFileName(),
                position: this.getHumanReadableSize(),
                language: this.getLangName() + " " + VPLUtil.str('binaryfile'),
                unsaved: this.isModified(),
            }
        );
    };
};
