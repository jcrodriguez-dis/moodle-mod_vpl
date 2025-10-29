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

import $ from 'jquery';
import {VPLUtil} from 'mod_vpl/vplutil';
import {VPLUI} from 'mod_vpl/vplui';
import {VPLClipboard} from 'mod_vpl/vplclipboard';
import console from 'core/log';

export class VPLVNCClient {
    constructor(VNCDialogId, str) {
        var self = this;
        var rfb;
        var title = '';
        var message = '';
        var lastState = 'disconnected';
        var lastCanvas = null;
        var VNCDialog = $('#' + VNCDialogId);
        var canvas = $('#' + VNCDialogId + " div");
        var onCloseAction = VPLUtil.doNothing;
        var clipboard;
        var needResize = true;
        var titleText;
        /**
         * Add input for activating virtual keyboard
         */
        function addVirtualKeyboardInput() {
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
        }
        addVirtualKeyboardInput();

        /**
         * Event handler of paste button at clipboard.
         */
        function pasteClipboard() {
            VPLUtil.log('Pasting clipboard');
            if (self.isConnected()) {
                const text = clipboard.getEntry2();
                rfb.clipboardPasteFrom(text);
            }
        }
        /**
         * Event handler of paste button at clipboard.
         * @param {Event} event
         */
        function receiveClipboard(event) {
            VPLUtil.log('Receiving clipboard');
            clipboard.setEntry1(event.detail.text);
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
                // Removed: rfb.get_keyboard().set_focused(true);
            }
        }
        /**
         * Inform rfb of focus lost.
         */
        function lostFocus() {
            if (self.isConnected()) {
                // Removed: rfb.get_keyboard().set_focused(false);
            }
        }
        /**
         * Tries to do a copy.
         */
        function copyAction() {
            clipboard.setEntry1(clipboard.getEntry1());
            document.execCommand('copy');
        }
        var HTMLUpdateClipboard = VPLUI.genIcon('copy', 'sw') + ' ' + str('copy');
        var HTMLPaste = VPLUI.genIcon('paste', 'sw') + ' ' + str('paste');
        clipboard = new VPLClipboard('vpl_dialog_vnc_clipboard', HTMLUpdateClipboard, copyAction, HTMLPaste, pasteClipboard,
            lostFocus);
        canvas.on('click', function(e) {
            if (e.target == canvas[0]) {
                getFocus();
            } else {
                lostFocus();
            }
        });
        this.displayResize = function() {
            var w = round(VNCDialog.width());
            var h = round(VNCDialog.parent().height() - VNCDialog.prev().outerHeight());
            self.setCanvasSize(w, h);
        };

        /**
         * Event handler for the VNC dialog open event.
         */
        function openHandler() {
            self.restoreLastCanvas();
            controlDialogSize();
        }

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
            classes: {
                "ui-dialog": 'vpl_ide vpl_vnc',
            },
            create: function() {
                titleText = VPLUI.setTitleBar(VNCDialog, 'vnc', 'graphic',
                                              ['clipboard', 'keyboard'],
                                              [openClipboard, getFocus]);
            },
            dragStop: controlDialogSize,
            focus: getFocus,
            open: openHandler,
            beforeClose: function() {
                if (needResize) {
                    needResize = false;
                    self.displayResize();
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
        this.saveLastCanvas = function() {
            if (self.isConnected()) {
                lastCanvas = rfb.toDataURL();
            }
        };
        this.restoreLastCanvas = function() {
            if (lastCanvas && lastState === 'disconnected') {
                var img = document.createElement('img');
                img.src = lastCanvas;
                img.style.width = '100%';
                img.style.height = '100%';
                canvas.html('');
                canvas.append(img);
                lastCanvas = null;
            }
        };
        /**
         * Event handler for the VNC connection established.
         * @param {*} event
         */
        function connectHandler(event) {
            updateState('connected', event);
        }
        /**
         * Event handler for the VNC connection closed.
         * @param {*} event
         */
        function disconnectHandler(event) {
            self.saveLastCanvas();
            updateState('disconnect', event);
        }

        /**
         * Event handler for the VNC server verification.
         * @param {*} event
         */
        function serververificationHandler(event) {
            VPLUtil.log('VNC server verification ' + event.detail.status);
        }
        /**
         * Event handler for the VNC credentials required.
         * @param {*} event
         */
        function credentialsrequiredHandler(event) {
            VPLUtil.log('VNC credentials required ' + event.detail.status);
        }
        /**
         * Event handler for the VNC security failure.
         * @param {*} event
         */
        function securityfailureHandler(event) {
            updateState('failed', event);
            VPLUtil.log('VNC security failure ' + event.detail.status);
        }
        /**
         * Event handler for the VNC clipping viewport.
         * @param {*} event
         */
        function clippingviewportHandler(event) {
            VPLUtil.log('VNC clipping viewport ' + event.detail.status);
        }
        /**
         * Event handler for the VNC capabilities.
         * @param {*} event
         */
        function capabilitiesHandler(event) {
            VPLUtil.log('VNC capabilities ' + event.detail.status);
        }
        /**
         * Event handler for the VNC desktop name.
         * @param {*} event
         */
        function desktopnameHandler(event) {
            VPLUtil.log('VNC desktop name ' + event.detail.status);
            self.setTitle(event.detail.name);
        }

        /**
         * Event handler to show vnc client state in windows title.
         *
         * @param {string} newstate Name of the new state
         * @param {Event} event Event that produce the state change
         */
        function updateState(newstate, event) {
            switch (newstate) {
                case "connected":
                    lastState = 'connected';
                    self.displayResize();
                    self.setMessage('');
                    self.setTitle(str('connected'));
                    break;
                case "disconnect":
                case "disconnected":
                    lastState = 'disconnected';
                    self.setTitle(str('connection_closed'));
                    self.restoreLastCanvas();
                    break;
                case "failed":
                    lastState = 'disconnected';
                    self.setTitle(str('connection_fail'));
                    console.log("VNC client: " + event.detail.status);
                    break;
                default:
                    self.setMessage('');
                    self.setTitle(str('connecting'));
            }
        }

        this.connect = function(secure, host, port, password, path, onClose) {
            VPLUtil.loadModule('noVNC/core/rfb', 'RFB')
                .then(function(RFB) {
                if (!port) {
                    port = secure ? 443 : 80;
                }
                clipboard.setEntry1('');
                onCloseAction = onClose;
                if (rfb) {
                    if (lastState !== 'disconnected') {
                        rfb.disconnect();
                    }
                    rfb = null;
                }
                canvas.html('');
                self.show();
                lastState = 'connecting';
                self.setTitle(str('connecting'));
                var target = canvas[0];
                var url = (secure ? 'wss' : 'ws') + '://' + host + ':' + port + '/' + path;
                rfb = new RFB(target, url, {
                        'encrypt': secure,
                        'repeaterID': '',
                        'true_color': true,
                        'local_cursor': true,
                        'shared': false,
                        'view_only': false,
                        'credentials': {'password': password}
                    });
                rfb.addEventListener("connect", connectHandler);
                rfb.addEventListener("disconnect", disconnectHandler);
                rfb.addEventListener("serververification", serververificationHandler);
                rfb.addEventListener("credentialsrequired", credentialsrequiredHandler);
                rfb.addEventListener("securityfailure", securityfailureHandler);
                rfb.addEventListener("clippingviewport", clippingviewportHandler);
                rfb.addEventListener("capabilities", capabilitiesHandler);
                rfb.addEventListener("clipboard", receiveClipboard);
                rfb.addEventListener("bell", () => console.log('\x07Bell received'));
                rfb.addEventListener("desktopname", desktopnameHandler);
                rfb.clipViewport = true;
                rfb.scaleViewport = false;
                rfb.resizeSession = true;
                rfb.qualityLevel = 6;
                rfb.compressionLevel = 2;
                rfb.showDotCursor = true;
                return true;
            }).catch(function(error) {
                console.error('Failed to load RFB module:', error);
                self.setTitle(str('connection_fail'));
                self.show();
                return false;
            });
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
            if (this.isConnected()) {
                self.saveLastCanvas();
                rfb.disconnect();
            }
            onCloseAction();
            clipboard.hide();
        };
        /**
         * Round a number to even and not less than 100.
         * @param {number} v value to round
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
            w = round(w);
            h = round(h);
            canvas.width(w);
            canvas.height(h);
            var inner = canvas.find('canvas');
            if (inner.length > 0) {
                inner.width(w);
                inner.height(h);
            }
        };
        this.show = function() {
            VNCDialog.dialog('open');
            VNCDialog.width('auto');
            VNCDialog.height('auto');
        };
        self.setCanvasSize($(window).width() - 150, $(window).height() - 150);
    }
}
