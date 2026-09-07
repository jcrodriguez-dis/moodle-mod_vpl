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

import $ from 'jquery';
/* eslint-disable no-unused-vars */
import jqui from 'jqueryui';
/* eslint-enable no-unused-vars */
import url from 'core/url';
import {VPLUtil} from 'mod_vpl/vplutil';
import {VPLUI} from 'mod_vpl/vplui';
import {VPLClipboard} from 'mod_vpl/vplclipboard';
import {VPLTerminalThemes} from 'mod_vpl/vplterminalthemes';

export const VPLTerminal = function(dialogId, terminalId, str) {
    var self = this;
    var ws = null;
    var onCloseAction = VPLUtil.doNothing;
    var title = '';
    var message = '';
    var tdialog = $('#' + dialogId);
    var tIde = $('#vplide');
    var titleText = '';
    var clipboard = null;
    var clipboardMaxsize = 64000;
    var clipboardData = '';
    var terminal;
    var fitAddon;
    var initReady;
    var terminalTag = $('#' + terminalId);
    var themes = VPLTerminalThemes.getThemes();

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
        if (clipboardData.length > clipboardMaxsize) {
            var from = clipboardData.length - clipboardMaxsize / 2;
            clipboardData = clipboardData.substring(from);
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
        initReady.then(function() {
            if (terminal) {
                terminal.write(text);
            }
            return;
        }).catch(VPLUtil.doNothing);
        return text;
    };
    this.focus = function() {
        initReady.then(function() {
            if (!terminal) {
                return;
            }
            terminal.focus();
            // The dialog moves the focus to a tabbable element while opening,
            // so the terminal focus is claimed again once the dialog has settled.
            setTimeout(function() {
                if (!terminal) {
                    return;
                }
                terminal.focus();
            }, 0);
            return;
        }).catch(function(error) {
            VPLUtil.log('Error focusing terminal: ' + error);
        });
    };
    this.blur = function() {
        initReady.then(function() {
            if (terminal) {
                terminal.blur();
            }
            return;
        }).catch(VPLUtil.doNothing);
    };
    this.connect = function(server, onShow, onClose) {
        onCloseAction = onClose;
        if ("WebSocket" in window) {
            initReady.then(function() {
                terminal.reset();
                onShow();
                self.show();
                if (ws) {
                    ws.close();
                }
                clipboardData = '';
                self.startBlinking();
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
                    self.setTitle(str('connected'));
                    self.startBlinking();
                    self.setMessage('');
                    self.focus();
                };
                ws.onclose = function() {
                    self.setTitle(str('connection_closed'));
                    self.blur();
                    self.stopBlinking();
                    onCloseAction();
                    ws.stopOutput = true;
                };
                self.focus();
                return;
            }).catch(VPLUtil.doNothing); // InitReady.then
        } else {
            initReady.then(function() {
                terminal.write('WebSocket not available: Upgrade your browser');
                return;
            }).catch(VPLUtil.doNothing);
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
            var localWs = ws;
            localWs.writeIt();
            localWs.close();
            self.setTitle(str('connection_closed'));
            self.blur();
            onCloseAction();
        }
        self.stopBlinking();
    };
    this.connectLocal = function(onClose, onData) {
        onCloseAction = onClose;
        initReady.then(function() {
            terminal.reset();
            self.show();
            if (ws) {
                ws.close();
            }
            clipboardData = '';
            self.setMessage('');
            self.setTitle(str('running'));
            self.startBlinking();
            ws = {};
            ws.onData = onData;
            ws.writeBuffer = '';
            ws.readBuffer = '';
            ws.OPEN = 1;
            ws.CLOSED = 2;
            ws.close = function() {
                ws.readyState = ws.CLOSED;
                self.stopBlinking();
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
                        ws.readBuffer = ws.readBuffer.substring(0, ws.readBuffer.length - 1);
                    }
                } else {
                    self.writeLocal(text);
                    ws.readBuffer += text;
                }
                var pos = ws.readBuffer.indexOf("\r");
                if (pos != -1) {
                    var data = ws.readBuffer.substring(0, pos);
                    ws.readBuffer = ws.readBuffer.substring(pos + 1);
                    ws.onData(data);
                }
            };
            ws.readyState = ws.OPEN;
            return;
        }).catch(VPLUtil.doNothing); //
    };
    this.isOpen = function() {
        return tdialog.dialog("isOpen");
    };
    this.close = function() {
        tdialog.dialog("close");
        self.stopBlinking();
    };
    this.isConnected = function() {
        return ws && ws.readyState === ws.OPEN;
    };
    this.disconnect = function() {
        if (this.isConnected()) {
            onCloseAction();
            ws.close();
            this.stopBlinking();
        }
    };
    var HTMLUpdateClipboard = VPLUI.genIcon('copy', 'sw') + ' ' + str('copy');
    var HTMLPaste = VPLUI.genIcon('paste', 'sw') + ' ' + str('paste');
    clipboard = new VPLClipboard('vpl_dialog_terminal_clipboard', HTMLUpdateClipboard, function() {
            updateClipboard();
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(clipboardData).catch(function() {
                    VPLUtil.log('Clipboard write failed');
                });
            }
        }, HTMLPaste, pasteClipboard);
    this.closeDialog = function() {
        clipboard.hide();
        self.disconnect();
    };
    /**
     * Sets the terminal theme
     * @param {string} themeName Name of the theme to set
     */
    function setTheme(themeName) {
        let themeNames = Object.keys(themes);
        if (themeNames.indexOf(themeName) === -1) {
            themeName = themeNames[0]; // Default to the first theme if not found.
        }
        tdialog.data('terminal_theme', themeName);
        VPLUtil.setUserPreferences({terminalTheme: themeName});
        if (terminal) {
            var themeSettings = Object.assign({}, themes[themeName]);
            terminal.options.theme = themeSettings;
        }
    }
    /**
     * Adjusts the terminal element to the current size of the dialog content
     */
    function fitTerminalToDialog() {
        const margin = 13;
        terminalTag.width(tdialog.width() - margin);
        terminalTag.height(tdialog.height() - margin);
    }
    /**
     * Limits the size of the dialogo to the IDE
     */
    function controlDialogSize() {
        // Resize if dialog is large than screen.
        var bw = tIde.width();
        var bh = tIde.height();
        var clamped = false;
        if (tdialog.parent().outerWidth() > bw) {
            tdialog.dialog("option", "width", bw);
            clamped = true;
        }
        if (tdialog.parent().outerHeight() > bh) {
            tdialog.dialog("option", "height", bh);
            clamped = true;
        }
        // The dialog width is 'auto', so sizing the terminal from the dialog width would
        // shrink both of them a bit on every call. Only do it when the dialog has been resized.
        if (clamped) {
            fitTerminalToDialog();
        }
        if (fitAddon) {
            fitAddon.fit();
        }
        self.focus();
    }
    tdialog.dialog({
        closeOnEscape: false,
        autoOpen: false,
        width: 'auto',
        height: 'auto',
        resizable: true,
        dragStop: function() {
            self.focus();
        },
        open: controlDialogSize,
        focus: function() {
            controlDialogSize();
            self.focus();
            if (self.isConnected()) {
                self.startBlinking();
            }
        },
        classes: {
            "ui-dialog":  'vpl_ide vpl_vnc',
        },
        create: function() {
            titleText = VPLUI.setTitleBar(tdialog, 'console', 'console',
                    ['clipboard', 'keyboard', 'theme'],
                    [openClipboard,
                    self.focus,
                    function() {
                        let themeNames = Object.keys(themes);
                        let oldTheme = tdialog.data('terminal_theme');
                        var theme = (themeNames.indexOf(oldTheme) + 1) % themeNames.length;
                        setTheme(themeNames[theme]);
                        // Show a message with the new theme name.
                        titleText.text('[' + themeNames[theme] + ']');
                    }]);
        },
        close: function() {
            self.stopBlinking();
            self.closeDialog();
        },
        resizeStop: function() {
            tdialog.width(tdialog.parent().width());
            tdialog.height(tdialog.parent().height() - tdialog.prev().outerHeight());
            fitTerminalToDialog();
            controlDialogSize();
            if (fitAddon) {
                fitAddon.fit();
            }
            self.focus();
        }
    });
    // Clicking the title bar moves the focus out of the editor, so give it back to the terminal.
    tdialog.parent().find('.ui-dialog-titlebar').on('click', function(event) {
        if ($(event.target).closest('button, a, input, select, textarea').length === 0) {
            self.focus();
        }
    });
    this.setFontSize = function(size) {
        size = parseInt(size, 10);
        if (isNaN(size) || size < 1 || size > 48) {
            return;
        }
        terminalTag.css("font-size", size + "px");
        if (terminal) {
            terminal.options.fontSize = size;
            if (fitAddon) {
                fitAddon.fit();
            }
        }
    };
    this.getFontSize = function() {
        if (terminal && terminal.options.fontSize) {
            return terminal.options.fontSize;
        } else {
            var fontSize = parseInt(terminalTag.css("font-size"), 10);
            if (!isNaN(fontSize) && fontSize > 0 && fontSize <= 48) {
                return fontSize;
            }
            return 12; // Default font size
        }
    };
    this.setTheme = function(themeName) {
        setTheme(themeName);
    };
    this.getTheme = function() {
        return tdialog.data('terminal_theme') || Object.keys(themes)[0];
    };
    this.getThemeNames = function() {
        return Object.keys(themes);
    };
    tdialog.css("padding", "1px");
    tdialog.parent().css('z-index', 2000);
    this.show = function() {
        tdialog.dialog('open');
        if (terminal) {
            self.focus();
            fitAddon.fit();
        }
    };
    this.startBlinking = function() {
        if (!terminal) {
            return;
        }
        terminal.options.cursorBlink = true;
    };
    this.stopBlinking = function() {
        if (!terminal) {
            return;
        }
        terminal.options.cursorBlink = false;
    };
    this.init = async function() {
        // Load xterm.js library
        const libpath = url.relativeUrl('/mod/vpl/thirdpartylibs/xterm/');
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = libpath + 'xterm.css';
        document.head.appendChild(link);
        const xterm = await import(libpath + 'xterm.js');
        const xtermFit = await import(libpath + 'addon-fit/addon-fit.js');
        terminal = new xterm.Terminal({
                    scrollback: 5000,
                });
        fitAddon = new xtermFit.FitAddon();
        terminal.loadAddon(fitAddon);
        self.stopBlinking();
        terminal.onData(function(data) {
            if (ws && ws.readyState == ws.OPEN) {
                ws.send(data);
            }
        });
        terminal.open(terminalTag[0]);
        terminal.reset();
        VPLUtil.getUserPreferences(function(data) {
            setTheme(data.preferences.terminalTheme);
            const fontSize = parseInt(data.preferences.terminalFontSize, 10);
            if (!isNaN(fontSize) && fontSize > 0 && fontSize <= 48) {
                terminal.options.fontSize = fontSize;
                terminalTag.css("font-size", fontSize + "px");
                if (fitAddon) {
                    fitAddon.fit();
                }
            }
        });
    };
    initReady = this.init();
};
