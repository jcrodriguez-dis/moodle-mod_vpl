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
 * Terminal Clipboard
 *
 * @copyright 2014 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

import $ from 'jquery';
/* eslint-disable no-unused-vars */
import jqui from 'jqueryui';
/* eslint-enable no-unused-vars */
import {VPLUtil} from 'mod_vpl/vplutil';
import {VPLUI} from 'mod_vpl/vplui';

export class VPLClipboard {
    constructor(dialogId, hlabel1, action1, hlabel2, action2, onFocus) {
        var tdialog = $('#' + dialogId);
        var label1 = tdialog.find('.vpl_clipboard_label1');
        var label2 = tdialog.find('.vpl_clipboard_label2');
        var entry1 = tdialog.find('.vpl_clipboard_entry1');
        var entry2 = tdialog.find('.vpl_clipboard_entry2');
        label1.html(hlabel1);
        label2.html(hlabel2);
        if (action1) {
            label1.button().click(action1);
        }
        if (action2) {
            label2.button().click(action2);
        }
        tdialog.dialog({
            title: VPLUtil.str('clipboard'),
            closeOnEscape: true,
            autoOpen: false,
            width: 'auto',
            height: 'auto',
            resizable: true,
            classes: {
                "ui-dialog": 'vpl_clipboard vpl_ide',
            },
        });
        if (onFocus) {
            tdialog.on("click", onFocus);
        }
        this.show = function () {
            tdialog.dialog('open');
        };
        this.hide = function () {
            tdialog.dialog('close');
        };
        this.setEntry1 = function (v) {
            entry1.val(v);
            entry1.select();
        };
        this.getEntry1 = function () {
            return entry1.val();
        };
        this.setEntry2 = function (v) {
            entry2.val(v);
        };
        this.getEntry2 = function () {
            return entry2.val();
        };
        var titleTag = tdialog.siblings().find('.ui-dialog-title');
        var clipboardTitle = VPLUI.genIcon('clipboard', 'sw');
        clipboardTitle += ' ' + VPLUtil.str('clipboard');
        titleTag.html(clipboardTitle);
        tdialog.parent().css('overflow', ''); // Fix problem with JQuery.
    }
}
