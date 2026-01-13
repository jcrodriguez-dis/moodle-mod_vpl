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

export const binaryExtension = function() {
    var type2HTML = {
        'img': '<img />',
        'audio': '<audio controls></audio>',
        'video': '<video controls></video>',
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
        return 'binary';
    };
    var setOldContent = this.setContent;
    this.setContent = function(c) {
        setOldContent.call(this, c);
        this.setModified();
        this.updateDataURL();
    };
    this.updateDataURL = function(type, fileName, value) {
        var tid = this.getTId();
        var prevalue = 'data:' + VPLUtil.getMIME(fileName) + ';base64,';
        $(tid).find(type).attr('src', prevalue + value);
    };
    this.adjustSize = function() {
        if (!this.isOpen()) {
            return false;
        }
        var editTag = $(this.getTId());
        if (editTag.length === 0) {
            return false;
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
        this.showFileName();
        var tid = this.getTId();
        this.setOpen(true);
        var type = this.getType();
        $(tid).addClass('vpl_ide_' + type).append(type2HTML[type]);
        if (type === 'binary') {
            $(tid).find('div').text(VPLUtil.str('binaryfile') + ": '" + this.getFileName() + "'");
        } else {
            this.updateDataURL(type, this.getFileName(), this.getContent());
        }
    };
    this.close = function() {
        this.setOpen(false);
    };
    this.langSelection = function() {
        this.setLang('Binary');
    };
};
