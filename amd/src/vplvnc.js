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
        /**
         * Load VNC Script after the Util ("util.js" is loaded
         */
        function loadVNCScripts() {
            if (typeof Util == 'undefined') {
                VPLUtil.delay('loadVNCScripts', loadVNCScripts);
            } else {
                Util.load_scripts(["webutil.js", "base64.js", "websock.js", "des.js",
                "keysymdef.js", "keyboard.js", "input.js", "display.js",
                "jsunzip.js", "rfb.js", "keysym.js"]);
            }
        }
        VPLUtil.delay('loadVNCScripts', loadVNCScripts);
        var VPLVNCClient = function(VNCDialogId, str) {
            var self = this;
            var rfb;
            var title = '';
            var message = '';
            var lastState = '';
            var VNCDialog = $('#' + VNCDialogId);
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
            /**
             * Event handler of keyboard button.
             */
            function keyboardButton() {
                if ($(inputarea).is(':focus')) {
                    inputarea.blur();
                } else {
                    inputarea.focus();
                }
            }
            /**
             * Event handler of paste button at clipboard.
             */
            function pasteClipboard() {
                if (self.isConnected()) {
                    rfb.clipboardPasteFrom(clipboard.getEntry2());
                }
            }
            /**
             * Event handler of paste button at clipboard.
             *
             * @param {object} rfb vnc client object
             * @param {string} text Text received
             */
            function receiveClipboard(rfb, text) {
                clipboard.setEntry1(text);
            }
            /**
             * Event handler of clipboard button.
             */
            function openClipboard() {
                clipboard.show();
            }
            /**
             * Inform rfb of focus received.
             */
            function getFocus() {
                if (self.isConnected()) {
                    rfb.get_keyboard().set_focused(true);
                }
            }
            /**
             * Inform rfb of focus lost.
             */
            function lostFocus() {
                if (self.isConnected()) {
                    rfb.get_keyboard().set_focused(false);
                }
            }
            /**
             * Tries to do a copy.
             */
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
            /**
             * Event handler that limit the size of the vnc client windows.
             *
             */
            function controlDialogSize() {
                // Resize if dialog is large than screen.
                var bw = $('html').width();
                var bh = $(window).height();
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
            VNCDialog.parent().css('z-index', 2000);
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
            /**
             * Event handler to show vnc client state in windows title.
             *
             * @param {object} rfb vnc client
             * @param {string} state Name of the state
             * @param {string} oldstate Name of the old state. Not used
             * @param {string} msg State detail message
             */
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
                if (rfb) {
                    rfb.disconnect();
                }
                onCloseAction();
                clipboard.hide();
            };
            /**
             * Round a number to event and not less than 100.
             *
             * @param {number} v value to round
             *
             * @returns {int}
             */
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
