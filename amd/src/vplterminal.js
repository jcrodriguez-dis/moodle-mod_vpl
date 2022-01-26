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
 * Terminal control
 *
 * @copyright 2014 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/* globals Terminal */

define(
    [
        'jquery',
        'jqueryui',
        'mod_vpl/vplutil',
        'mod_vpl/vplclipboard'
    ],
    function($, jqui, VPLUtil, VPLClipboard) {
        if (typeof VPLTerminal !== 'undefined') {
            return VPLTerminal;
        }
        var NTHEMES = 5;
        var VPLTerminal = function(dialogId, terminalId, str) {
            var self = this;
            var ws = null;
            var onCloseAction = VPLUtil.doNothing;
            var title = '';
            var message = '';
            var tdialog = $('#' + dialogId);
            var tIde = $('#vplide');
            var titleText = '';
            var clipboard = null;
            var cliboardMaxsize = 64000;
            var clipboardData = '';

            var terminal;
            var terminalTag = $('#' + terminalId);
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
             * Manages the data received from clipboard
             * @param {string} data Data recieved
             */
            function receiveClipboard(data) {
                clipboardData += data;
                if (clipboardData.length > cliboardMaxsize) {
                    var from = clipboardData.length - cliboardMaxsize / 2;
                    clipboardData = clipboardData.substr(from);
                }
            }
            /**
             * Sends the clipboard data to the connection
             */
            function pasteClipboard() {
                if (ws && ws.readyState == ws.OPEN) {
                    ws.send(clipboard.getEntry2());
                }
            }
            /**
             * Updates the data in the clipboard dialog
             */
            function updateClipboard() {
                clipboard.setEntry1(clipboardData);
            }
            /**
             * Opens the clipboard dialog
             */
            function openClipboard() {
                updateClipboard();
                clipboard.show();
            }
            this.write = function(text) {
                terminal.write(text);
                return text;
            };

            this.connect = function(server, onClose) {
                onCloseAction = onClose;
                if ("WebSocket" in window) {
                    terminal.reset();
                    terminal.startBlink();
                    self.show();
                    if (ws) {
                        ws.close();
                    }
                    clipboardData = '';
                    self.setMessage('');
                    self.setTitle(str('connecting'));
                    ws = new WebSocket(server);
                    ws.writeBuffer = '';
                    ws.writeIt = function() {
                        terminal.write(ws.writeBuffer);
                        receiveClipboard(ws.writeBuffer);
                        ws.writeBuffer = '';
                    };
                    ws.onmessage = function(event) {
                        if (ws.writeBuffer.length > 0) {
                            ws.writeBuffer += event.data;
                        } else {
                            ws.writeBuffer = event.data;
                            setTimeout(ws.writeIt, 35);
                        }
                    };
                    ws.onopen = function() {
                        self.setMessage('');
                        self.setTitle(str('connected'));
                    };
                    ws.onclose = function() {
                        self.setTitle(str('connection_closed'));
                        terminal.blur();
                        terminal.stopBlink();
                        onClose();
                        ws.stopOutput = true;
                    };
                } else {
                    terminal.write('WebSocket not available: Upgrade your browser');
                }
            };
            this.writeLocal = function(text) {
                ws.onmessage({
                    data: text
                });
                return text;
            };
            this.setDataCallback = function(call) {
                ws.onData = call;
            };
            this.closeLocal = function() {
                if (ws) {
                    ws.writeIt();
                    ws.close();
                    terminal.stopBlink();
                    self.setTitle(str('connection_closed'));
                }
            };
            this.connectLocal = function(onClose, onData) {
                onCloseAction = onClose;
                terminal.reset();
                terminal.startBlink();
                self.show();
                if (ws) {
                    ws.close();
                }
                clipboardData = '';
                self.setMessage('');
                self.setTitle(str('running'));
                ws = {};
                ws.onData = onData;
                ws.writeBuffer = '';
                ws.readBuffer = '';
                ws.readyState = 1;
                ws.OPEN = 1;
                ws.close = function() {
                    ws = false;
                };
                ws.onmessage = function(event) {
                    ws.writeBuffer = event.data;
                    ws.writeIt();
                };
                ws.writeIt = function() {
                    if (ws) {
                        terminal.write(ws.writeBuffer);
                        receiveClipboard(ws.writeBuffer);
                        ws.writeBuffer = '';
                    }
                };
                ws.send = function(text) {
                    // Process backspace.
                    if (text == '\u007f') {
                        if (ws.readBuffer.length > 0) {
                            self.writeLocal('\b \b');
                            ws.readBuffer = ws.readBuffer.substr(0, ws.readBuffer.length - 1);
                        }
                    } else {
                        self.writeLocal(text);
                        ws.readBuffer += text;
                    }
                    var pos = ws.readBuffer.indexOf("\r");
                    if (pos != -1) {
                        var data = ws.readBuffer.substr(0, pos);
                        ws.readBuffer = ws.readBuffer.substr(pos + 1);
                        ws.onData(data);
                    }
                };
            };
            this.isOpen = function() {
                return tdialog.dialog("isOpen");
            };
            this.close = function() {
                tdialog.dialog("close");
            };
            this.isConnected = function() {
                return ws && ws.readyState != ws.CLOSED;
            };
            this.disconnect = function() {
                if (ws && ws.readyState == ws.OPEN) {
                    onCloseAction();
                    if (ws) {
                        ws.close();
                    }
                    terminal.stopBlink();
                }
            };
            var HTMLUpdateClipboard = VPLUtil.genIcon('copy', 'sw') + ' ' + str('copy');
            var HTMLPaste = VPLUtil.genIcon('paste', 'sw') + ' ' + str('paste');
            clipboard = new VPLClipboard('vpl_dialog_terminal_clipboard', HTMLUpdateClipboard, function() {
                    updateClipboard();
                    document.execCommand('copy');
                }, HTMLPaste, pasteClipboard);
            this.closeDialog = function() {
                clipboard.hide();
                self.disconnect();
            };
            /**
             * Sets the terminal theme
             * @param {int} theme
             */
            function setTheme(theme) {
                var cbase = "vpl_terminal_theme";
                var nthemes = 5;
                tdialog.data('terminal_theme', theme);
                VPLUtil.setUserPreferences({terminalTheme: theme});
                for (var i = 0; i < nthemes; i++) {
                    tdialog.removeClass(cbase + i);
                }
                tdialog.addClass(cbase + theme);
            }
            /**
             * Limits the size of the dialogo to the IDE
             */
            function controlDialogSize() {
                // Resize if dialog is large than screen.
                var bw = tIde.width();
                var bh = tIde.height();
                if (tdialog.width() > bw) {
                    tdialog.dialog("option", "width", bw);
                }
                if (tdialog.parent().height() > bh) {
                    tdialog.dialog("option", "height", bh - tdialog.prev().outerHeight());
                }
            }
            tdialog.dialog({
                closeOnEscape: false,
                autoOpen: false,
                width: 'auto',
                height: 'auto',
                resizable: true,
                dragStop: controlDialogSize,
                open: controlDialogSize,
                focus: function() {
                    controlDialogSize();
                    terminal.focus();
                },
                dialogClass: 'vpl_ide vpl_vnc',
                create: function() {
                    titleText = VPLUtil.setTitleBar(tdialog, 'console', 'console',
                            ['clipboard', 'keyboard', 'theme'],
                            [openClipboard,
                            function() {
                                terminal.focus();
                            },
                            function() {
                                // Cycle themes from 0 to NTHEMES-1.
                                var theme = (tdialog.data('terminal_theme') + 1) % NTHEMES;
                                setTheme(theme);
                            }]);
                },
                close: function() {
                    self.closeDialog();
                },
                resizeStop: function() {
                    tdialog.width(tdialog.parent().width());
                    tdialog.height(tdialog.parent().height() - tdialog.prev().outerHeight());
                    controlDialogSize();
                    terminal.focus();
                }
            });
            this.setFontSize = function(size) {
                terminalTag.css("font-size", size + "px");
            };
            VPLUtil.getUserPreferences(function(data) {
                setTheme(data.preferences.terminalTheme);
            });
            tdialog.css("padding", "1px");
            tdialog.parent().css('z-index', 2000);
            this.show = function() {
                tdialog.dialog('open');
                terminal.focus();
            };
            this.init = function() {
                if (typeof Terminal === 'undefined') {
                    VPLUtil.loadScript(['../../vpl/editor/xterm/term.js'], function() {
                                                                               self.init();
                                                                           });
                    return;
                }
                terminal = new Terminal({
                    cols: 80,
                    rows: 24,
                    useStyle: true,
                    screenKeys: true
                });
                terminal.on('data', function(data) {
                    if (ws && ws.readyState == ws.OPEN) {
                        ws.send(data);
                    }
                });
                terminal.open(terminalTag[0]);
                terminal.reset();
                terminal.stopBlink();
                terminal.setLineCallback(
                    function(line, nlines) {
                        var height = terminalTag.height();
                        var offset = tdialog.scrollTop();
                        var viewHeight = tdialog.innerHeight();
                        if (viewHeight >= height) {
                            return;
                        }
                        var lineHeight = height / nlines;
                        var pos = line * lineHeight;
                        // If cursor in view area return.
                        if (pos >= offset && pos < (viewHeight + offset - lineHeight)) {
                            return;
                        }
                        if (pos < offset) { // If cursor beforer view area scroll to first view line.
                            tdialog.scrollTop(pos);
                        } else {
                            tdialog.scrollTop((pos - viewHeight) + 2 * lineHeight);
                        }
                    }
                );
            };
            this.init();
        };
        window.VPLTerminal = VPLTerminal;
        return VPLTerminal;
    }
);
