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
 * @package mod_vpl
 * @copyright 2014 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/* globals VPL_Clipboard: true */
/* globals VPL_Terminal: true */
/* globals VPL_VNC_Client: true */
/* globals VPL_Util */
/* globals $JQVPL */
/* globals Terminal */
/* globals console */
/* globals RFB */

VPL_Clipboard = function(dialog_id, hlabel1, action1, hlabel2, action2, onFocus) {
    var tdialog = $JQVPL('#' + dialog_id);
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
        title : VPL_Util.str('clipboard'),
        closeOnEscape : true,
        autoOpen : false,
        width : 'auto',
        height : 'auto',
        resizable : true,
        dialogClass : 'vpl_clipboard vpl_ide',
    });
    if (onFocus) {
        tdialog.on("click", onFocus);
    }
    this.show = function() {
        tdialog.dialog('open');
    };
    this.hide = function() {
        tdialog.dialog('close');
    };
    this.setEntry1 = function(v) {
        entry1.val(v);
        entry1.select();
    };
    this.getEntry1 = function() {
        return entry1.val();
    };
    this.setEntry2 = function(v) {
        entry2.val(v);
    };
    this.getEntry2 = function() {
        return entry2.val();
    };
    var titleTag = tdialog.siblings().find('.ui-dialog-title');
    var clipboardTitle = VPL_Util.gen_icon('clipboard', 'sw');
    clipboardTitle += ' ' + VPL_Util.str('clipboard');
    titleTag.html(clipboardTitle);
    tdialog.parent().css('overflow', ''); // Fix problem with JQuery.
};

VPL_Terminal = function(dialog_id, terminal_id, str) {
    var self = this;
    var ws = null;
    var onCloseAction = function() {
    };
    var title = '';
    var message = '';
    var tdialog = $JQVPL('#' + dialog_id);
    var titleText = '';
    var clipboard = null;
    var cliboardMaxsize = 80 * 500;
    var clipboardData = '';
    var textWritten = '';
    var maxBuffer = 80 * 500;

    var terminal = new Terminal({
        cols : 80,
        rows : 24,
        useStyle : true,
        screenKeys : true
    });
    terminal.on('data', function(data) {
        if (ws && ws.readyState == ws.OPEN) {
            ws.send(data);
            textWritten += data;
        }
    });
    var terminal_tag = $JQVPL('#' + terminal_id);
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
    function receiveClipboard(data) {
        clipboardData += data;
        if (clipboardData.length > 2 * cliboardMaxsize) {
            var from = clipboardData.length - cliboardMaxsize;
            clipboardData = clipboardData.substr(from);
        }
    }
    function pasteClipboard() {
        if (ws && ws.readyState == ws.OPEN) {
            ws.send(clipboard.getEntry2());
        }
    }
    function updateClipboard() {
        clipboard.setEntry1(clipboardData);
    }
    function openClipboard() {
        updateClipboard();
        clipboard.show();
    }

    this.connect = function(server, onClose) {
        onCloseAction = onClose;
        if ("WebSocket" in window) {
            terminal.reset();
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
                if (ws.writeBuffer.length > maxBuffer) {
                    var from = ws.writeBuffer.length - maxBuffer;
                    ws.writeBuffer = ws.writeBuffer.substr(from);
                }
                terminal.write(ws.writeBuffer);
                receiveClipboard(ws.writeBuffer);
                ws.writeBuffer = '';
            };
            ws.onmessage = function(event) {
                if (ws.writeBuffer.length == 0) {
                    setTimeout(ws.writeIt, 35);
                }
                ws.writeBuffer += event.data;
                if (ws.writeBuffer.length > 2 * maxBuffer) {
                    var from = ws.writeBuffer.length - maxBuffer;
                    ws.writeBuffer = ws.writeBuffer.substr(from);
                }
            };
            ws.onopen = function() {
                self.setMessage('');
                self.setTitle(str('connected'));
            };
            ws.onclose = function() {
                self.setTitle(str('connection_closed'));
                terminal.blur();
                onClose();
                ws.stopOutput = true;
            };
        } else {
            terminal.write('WebSocket not available: Upgrade your browser');
        }
    };
    this.isOpen = function() {
        return tdialog.dialog("isOpen") === true;
    };
    this.isConnected = function() {
        return ws && ws.readyState != ws.CLOSED;
    };
    this.disconnect = function() {
        if (ws && ws.readyState == ws.OPEN) {
            ws.close();
        }
        onCloseAction();
    };
    var HTMLUpdateClipboard = VPL_Util.gen_icon('copy', 'sw') + ' ' + str('copy');
    var HTMLPaste = VPL_Util.gen_icon('paste', 'sw') + ' ' + str('paste');
    clipboard = new VPL_Clipboard('vpl_dialog_terminal_clipboard', HTMLUpdateClipboard, function() {
        updateClipboard();
        document.execCommand('copy');
    }, HTMLPaste, pasteClipboard);

    tdialog.dialog({
        closeOnEscape : false,
        autoOpen : false,
        width : 'auto',
        height : 'auto',
        resizable : false,
        focus : function() {
            terminal.focus();
        },
        modal : true,
        dialogClass : 'vpl_ide vpl_vnc',
        create : function() {
            titleText = VPL_Util.setTitleBar(tdialog, 'console', 'console', [ 'clipboard', 'keyboard' ], [ openClipboard,
                    function() {
                        terminal.focus();
                    } ]);
        },
        close : function() {
            clipboard.hide();
            self.disconnect();
        }
    });
    this.show = function() {
        tdialog.dialog('open');
        terminal.focus();
    };
    terminal.open(terminal_tag[0]);
};

VPL_VNC_Client = function(vnc_dialog_id, str) {
    var self = this;
    var rfb;
    var title = '';
    var message = '';
    var lastState = '';
    var VNCDialog = $JQVPL('#' + vnc_dialog_id);
    var canvas = $JQVPL('#' + vnc_dialog_id + " canvas");
    var onCloseAction = function() {
    };
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
                }
            }, 10);
        }
    }
    if (VPL_Util.isAndroid() && VPL_Util.isFirefox()) {
        this.send = function(v) {
            for (var i = 0; i < v.length; i++) {
                rfb.sendKey(v.charCodeAt(i));
            }
        };
        this.sendBackspace = function() {
            rfb.sendKey(0xff08);
        };

        inputarea.value = resetValue;
        inputarea.focus();
        try {
            inputarea.setSelectionRange(resetValue.length, resetValue.length);
        } catch (ex) {
        }
        $JQVPL(inputarea).on('change', function() {
            readInput();
            self.send('\r');
        });
        $JQVPL(inputarea).on('input', function() {
            readInput();
        });
        $JQVPL(inputarea).on('keypress', function(e) {
            e.stopImmediatePropagation();
        });
        $JQVPL(inputarea).on('focus', function() {
            inputarea.value = resetValue;
            lastValue = resetValue;
            try {
                inputarea.setSelectionRange(resetValue.length, resetValue.length);
            } catch (e) {
            }
        });
    }

    function keyboardButton() {
        if ($JQVPL(inputarea).is(':focus')) {
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
    var HTMLUpdateClipboard = VPL_Util.gen_icon('copy', 'sw') + ' ' + str('copy');
    var HTMLPaste = VPL_Util.gen_icon('paste', 'sw') + ' ' + str('paste');
    clipboard = new VPL_Clipboard('vpl_dialog_vnc_clipboard', HTMLUpdateClipboard,
                                  copyAction, HTMLPaste, pasteClipboard, lostFocus);
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
    VNCDialog.dialog({
        closeOnEscape : false,
        autoOpen : false,
        modal : true,
        width : 'auto',
        height : 'auto',
        dialogClass : 'vpl_ide vpl_vnc',
        create : function() {
            titleText = VPL_Util.setTitleBar(VNCDialog, 'vnc', 'graphic', [ 'clipboard', 'keyboard' ], [ openClipboard,
                    keyboardButton ]);
        },
        focus : getFocus,
        beforeClose : function() {
            if (needResize) {
                var w = VNCDialog.width();
                var h = VNCDialog.height();
                needResize = false;
                self.setCanvasSize(w, h);
            }
        },
        close : function() {
            self.disconnect();
        },
        resizeStop : function() {
            needResize = true;
        }
    });
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
        var target = $JQVPL('#' + vnc_dialog_id + " canvas")[0];
        if (!rfb) {
            rfb = new RFB({
                'target' : target,
                'encrypt' : secure,
                'repeaterID' : '',
                'true_color' : true,
                'local_cursor' : true,
                'shared' : false,
                'view_only' : false,
                'onUpdateState' : updateState,
                'onPasswordRequired' : null,
                'onClipboard' : receiveClipboard
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
    self.setCanvasSize($JQVPL(window).width() - 150, $JQVPL(window).height() - 150);
};
