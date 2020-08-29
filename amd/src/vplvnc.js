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
 * VNC client control
 *
 * @package mod_vpl
 * @copyright 2014 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/* globals RFB */
/* globals Util */

define(
    [
        'jquery',
        'jqueryui',
        'mod_vpl/vplutil',
        'mod_vpl/vplclipboard',
        'core/log'
    ],
    function($, jqui, VPLUtil, VPLClipboard, console) {
        window.INCLUDE_URI = "../editor/noVNC/include/";
        Util.load_scripts(["webutil.js", "base64.js", "websock.js", "des.js",
                           "keysymdef.js", "keyboard.js", "input.js", "display.js",
                           "jsunzip.js", "rfb.js", "keysym.js"]);
        var VPLVNCClient = function(VNCDialogId, str) {
            var self = this;
            var rfb;
            var title = '';
            var message = '';
            var lastState = '';
            var VNCDialog = $('#' + VNCDialogId);
            var tIde = $('#vplide');
            var canvas = $('#' + VNCDialogId + " canvas");
            var onCloseAction = VPLUtil.doNothing;
            var clipboard;
            var needResize = true;
            var titleText;
            var inputarea = window.document.createElement('input');
            inputarea.style.position = 'absolute';
            inputarea.style.left = '0px';
            inputarea.style.top = '-10000px';
            inputarea.style.width = '1em';
            inputarea.style.height = '1ex';
            inputarea.style.opacity = '0';
            inputarea.style.backgroundColor = 'transparent';
            inputarea.style.borderStyle = 'none';
            inputarea.style.outlineStyle = 'none';
            inputarea.autocapitalize = 'off';
            inputarea.autocomplete = 'off';
            inputarea.autocorrect = 'off';
            inputarea.wrap = 'off';
            inputarea.spellcheck = 'false';
            VNCDialog.append(inputarea);
            var resetValue = "_________________________________________________________";
            var lastValue = resetValue;
            function readInput() {
                var value = inputarea.value;
                if (value == lastValue && value == resetValue) {
                    return;
                }
                var l = Math.min(value.length, lastValue.length);
                var mod = 0;
                for (mod = 0; mod < l; mod++) {
                    if (value.charAt(mod) != lastValue.charAt(mod)) {
                        break;
                    }
                }
                for (var i = lastValue.length - 1; i >= value.length; i--) {
                    self.sendBackspace();
                }
                if (mod < value.length) {
                    self.send(value.substr(mod));
                }
                lastValue = value;
                if (value.length > 500 || value.length === 0) {
                    inputarea.blur();
                    setTimeout(function() {
                        inputarea.focus();
                        inputarea.value = resetValue;
                        lastValue = resetValue;
                        try {
                            inputarea.setSelectionRange(resetValue.length, resetValue.length);
                        } catch (err) {
                            /* Nothing to do. */
                        }
                    }, 10);
                }
            }
            function keyboardButton() {
                if ($(inputarea).is(':focus')) {
                    inputarea.blur();
                } else {
                    inputarea.focus();
                }
            }
            function pasteClipboard() {
                if (self.isConnected()) {
                    rfb.clipboardPasteFrom(clipboard.getEntry2());
                }
            }
            function receiveClipboard(rfb, text) {
                clipboard.setEntry1(text);
            }
            function openClipboard() {
                clipboard.show();
            }
            function getFocus() {
                if (self.isConnected()) {
                    rfb.get_keyboard().set_focused(true);
                }
            }
            function lostFocus() {
                if (self.isConnected()) {
                    rfb.get_keyboard().set_focused(false);
                }
            }
            function copyAction() {
                clipboard.setEntry1(clipboard.getEntry1());
                document.execCommand('copy');
            }
            var HTMLUpdateClipboard = VPLUtil.genIcon('copy', 'sw') + ' ' + str('copy');
            var HTMLPaste = VPLUtil.genIcon('paste', 'sw') + ' ' + str('paste');
            clipboard = new VPLClipboard('vpl_dialog_vnc_clipboard', HTMLUpdateClipboard, copyAction, HTMLPaste, pasteClipboard,
                    lostFocus);
            canvas.on('click', function(e) {
                if (e.target == canvas[0]) {
                    getFocus();
                } else {
                    lostFocus();
                }
            });
            this.displayResize = function() { // TODO hot screen resize.
                if (self.isConnected()) {
                    var w = VNCDialog.width();
                    var h = VNCDialog.height();
                    self.setCanvasSize(w, h);
                    rfb.get_display().viewportChange(0, 0, w, h);
                }
            };
            function controlDialogSize() {
                // Resize if dialog is large than screen.
                var bw = tIde.width();
                var bh = tIde.height();
                if (VNCDialog.width() > bw) {
                    needResize = true;
                    VNCDialog.dialog("option", "width", bw);
                }
                if (VNCDialog.parent().height() > bh) {
                    needResize = true;
                    VNCDialog.dialog("option", "height", bh - VNCDialog.prev().outerHeight());
                }
            }
            VNCDialog.dialog({
                closeOnEscape: false,
                autoOpen: false,
                modal: true,
                width: 'auto',
                height: 'auto',
                dialogClass: 'vpl_ide vpl_vnc',
                create: function() {
                    titleText = VPLUtil.setTitleBar(VNCDialog, 'vnc', 'graphic', ['clipboard', 'keyboard'], [openClipboard,
                            keyboardButton]);
                },
                dragStop: controlDialogSize,
                focus: getFocus,
                open: controlDialogSize,
                beforeClose: function() {
                    if (needResize) {
                        var w = VNCDialog.width();
                        var h = VNCDialog.height();
                        needResize = false;
                        self.setCanvasSize(w, h);
                    }
                },
                close: function() {
                    self.disconnect();
                },
                resizeStop: function() {
                    controlDialogSize();
                    needResize = true;
                }
            });
            VNCDialog.css("padding", "1px");
            this.updateTitle = function() {
                var text = title;
                if (message !== '') {
                    text += ' (' + message + ')';
                }
                titleText.text(str('console') + ": " + text);
            };
            this.setTitle = function(t) {
                title = t;
                this.updateTitle();
            };
            this.setMessage = function(t) {
                message = t;
                this.updateTitle();
            };
            function updateState(rfb, state, oldstate, msg) {
                lastState = state;
                switch (state) {
                    case "normal":
                        self.setMessage('');
                        self.setTitle(str('connected'));
                        break;
                    case "disconnect":
                    case "disconnected":
                        self.setTitle(str('connection_closed'));
                        break;
                    case "failed":
                        self.setTitle(str('connection_fail'));
                        console.log("VNC client: " + msg);
                        break;
                    default:
                        self.setMessage('');
                        self.setTitle(str('connecting'));
                }
            }

            this.connect = function(secure, host, port, password, path, onClose) {
                clipboard.setEntry1('');
                onCloseAction = onClose;
                self.show();
                var target = $('#' + VNCDialogId + " canvas")[0];
                if (!rfb) {
                    rfb = new RFB({
                        'target': target,
                        'encrypt': secure,
                        'repeaterID': '',
                        'true_color': true,
                        'local_cursor': true,
                        'shared': false,
                        'view_only': false,
                        'onUpdateState': updateState,
                        'onPasswordRequired': null,
                        'onClipboard': receiveClipboard
                    });
                    rfb.set_local_cursor(rfb.get_display().get_cursor_uri());
                }
                if (!port) {
                    port = secure ? 443 : 80;
                }
                rfb.connect(host, port, password, path);
            };
            this.isOpen = function() {
                return VNCDialog.dialog("isOpen");
            };
            this.close = function() {
                VNCDialog.dialog("close");
            };
            this.isConnected = function() {
                return rfb && lastState != 'disconnected';
            };
            this.disconnect = function() {
                if (rfb && lastState == 'normal') {
                    rfb.disconnect();
                }
                onCloseAction();
                clipboard.hide();
            };
            function round(v) {
                if (v < 100) {
                    v = 100;
                }
                return Math.floor(v / 2) * 2;
            }
            this.getCanvasSize = function() {
                return canvas.width() + "x" + canvas.height();
            };

            this.setCanvasSize = function(w, h) {
                canvas.width(round(w));
                canvas.height(round(h));
            };
            this.show = function() {
                VNCDialog.dialog('open');
                VNCDialog.width('auto');
                VNCDialog.height('auto');
            };
            self.setCanvasSize($(window).width() - 150, $(window).height() - 150);
        };
        return VPLVNCClient;
    }
);
