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
 * @package mod_vpl
 * @copyright 2014 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

// Terminal constructor
VPL_Terminal = function(dialog_id, terminal_id, str) {
    var self = this;
    var ws;
    var onCloseAction = function() {
    };
    var title = '';
    var message = '';
    var tdialog = $JQVPL('#' + dialog_id);

    var terminal = new Terminal({
        cols : 80,
        rows : 24,
        useStyle : true,
        screenKeys : true
    });
    terminal.on('data', function(data) {
        if (ws && ws.readyState == ws.OPEN) {
            ws.send(data);
        }
    });
    var terminal_tag = $JQVPL('#' + terminal_id);
    this.updateTitle = function() {
        var text = title;
        if (message != '') {
            text += ' (' + message + ')';
        }
        tdialog.dialog("option", "title", str('console') + ": " + text);
    };
    this.setTitle = function(t) {
        title = t;
        this.updateTitle();
    };
    this.setMessage = function(t) {
        message = t;
        this.updateTitle();
    };

    this.connect = function(server, onClose) {
        onCloseAction = onClose;
        if ("WebSocket" in window) {
            terminal.reset();
            self.show();
            if (ws) {
                ws.close();
            }
            self.setMessage('');
            self.setTitle(str('connecting'));
            ws = new WebSocket(server);
            ws.writeBuffer = '';
            ws.writeIt = function() {
                terminal.write(ws.writeBuffer);
                ws.writeBuffer = '';
            };
            ws.onmessage = function(event) {
                if (ws.writeBuffer.length > 0) {
                    ws.writeBuffer += event.data;
                } else {
                    ws.writeBuffer = event.data;
                    setTimeout(ws.writeIt, 0);
                }
            };
            ws.onopen = function(event) {
                self.setMessage('');
                self.setTitle(str('connected'));
            };
            ws.onclose = function(event) {
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

    tdialog.dialog({
        title : str('console'),
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
        close : function() {
            self.disconnect();
        }
    });
    this.show = function() {
        tdialog.dialog('open');
        terminal.focus();
    };
    terminal.open(terminal_tag[0]);
    // End constructor
};
// VNC client constructor
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
    var firstStart = true;
    var widthDiff = 0;
    var heightDiff = 0;

    VNCDialog.dialog({
        title : str('console'),
        closeOnEscape : false,
        autoOpen : false,
        modal : true,
        width : 'auto',
        height : 'auto',
        dialogClass : 'vpl_ide vpl_vnc',
        beforeClose : function() {
            var w = VNCDialog.width() - widthDiff;
            var h = VNCDialog.height() - heightDiff;
            self.setCanvasSize(w, h);
        },
        close : function() {
            self.disconnect();
        }
    });
    this.updateTitle = function() {
        var text = title;
        if (message != '') {
            text += ' (' + message + ')';
        }
        VNCDialog.dialog("option", "title", str('console') + ": " + text);
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
        onCloseAction = onClose;
        if (firstStart) {
            self.setCanvasSize($JQVPL(window).width() - 150, $JQVPL(window).height() - 150);
        }
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
                'onPasswordRequired' : null
            });
            //Clipboard
            //Cambio de tamaño
            //Teclado en tabletas
            //rfb.clipboardPasteFrom
            //rfb.get_display
            //display
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
    };
    function round(v) {
        if (v < 100) {
            v = 100;
        }
        return Math.floor(v / 20) * 20;
    }
    this.getCanvasSize = function() {
        if (firstStart) {
            return ($JQVPL(window).width() - 150) + 'x' + ($JQVPL(window).height() - 150);
        }
        return canvas.width() + "x" + canvas.height();
    };

    this.setCanvasSize = function(w, h) {
        canvas.width(round(w));
        canvas.height(round(h));
    };
    function getFirstDiff() {
        if (firstStart) {
            firstStart = false;
            widthDiff = VNCDialog.width() - canvas.width();
            heightDiff = VNCDialog.height() - canvas.height();
        }
    }
    this.show = function() {
        VNCDialog.dialog('open');
        VNCDialog.width('auto');
        VNCDialog.height('auto');
        VPL_Util.delay(getFirstDiff);
    };
};
