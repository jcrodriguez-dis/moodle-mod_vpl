/**
 * @version $Id: WCodeEditor.js,v 1. 2012-10-05 09:03:48 juanca Exp $
 * @package VPL. HTML/JavaScript Code Editor
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

// Terminal constructor

VPL_Terminal = function(dialog_id,terminal_id,str) {
	var self = this;
	var ws = undefined;
	var tdialog = $('#'+dialog_id);
	var terminal = new Terminal({
		cols : 80,
		rows : 24,
		useStyle : true,
		screenKeys : true
	});
	terminal.on('data', function(data) {
		if (typeof ws != 'undefined' && ws.readyState == ws.OPEN)
			ws.send(data);
	});
	var terminal_tag = $('#' + terminal_id);
	this.connect = function(server, onClose) {
		self.show();
		if ("WebSocket" in window) {
			terminal.reset();
			if (typeof ws != 'undefined')
				ws.close();
			tdialog.dialog("option", "title",
					str('console') + ": " + str('connecting'));
			ws = new WebSocket(server);
			ws.onmessage = function(event) {
				terminal.write(event.data);
			};
			ws.onopen = function(event) {
				tdialog.dialog("option", "title",
						str('console') + ": " + str('connected'));
				terminal.focus();
			};

			ws.onclose = function(event) {
				tdialog.dialog("option", "title",
						str('console') + ": " + str('connection_closed'));
				terminal.blur();
				onClose();
			};
		} else {
			terminal
					.write('WebSocket not available: Upgrade your browser');
		};
	};
	this.isConnected = function() {
		return ws && ws.readyState != ws.CLOSED;
	};
	this.disconnect = function() {
		if (typeof ws != 'undefined' && ws.readyState == ws.OPEN)
			ws.close();
	};
	
	tdialog.dialog({
		title : str('console'),
		closeOnEscape : false,
		autoOpen : false,
		width : 'auto',
		height : 'auto',
		resizable : false,
		modal:true,
		dialogClass : 'vpl_ide vpl_vnc',
		close : function() {
			self.disconnect();
		}
	});
	this.show= function(){
		tdialog.dialog('open');
	};
	// End constructor
	terminal.open(terminal_tag[0]);
};
// VNC client constructor
VPL_VNC_Client = function(vnc_dialog_id, str) {
	var self=this;
	var rfb = undefined;
	var lastState ='';
	var VNCDialog = $('#'+vnc_dialog_id);
	VNCDialog.dialog({
		title : str('console'),
		closeOnEscape : false,
		autoOpen : false,
		modal:true,
		width : 'auto',
		height : 'auto',
		dialogClass : 'vpl_ide vpl_vnc',
		close : function() {
			self.disconnect();
		}
	});
	function updateState(rfb, state, oldstate, msg) {
		lastState=state;
		switch(state){
		case "normal":
			VNCDialog.dialog("option", "title",
					str('console') + ": " + str('connected'));
			break;
		case "disconnect":
		case "disconnected":
			VNCDialog.dialog("option", "title",
					str('console') + ": " + str('connection_closed'));
			break;
		case "failed":
			VNCDialog.dialog("option", "title",
				str('console') + ": " + str('connection_fail'));			
			console.log("VNC client: "+msg);
			break;
		default:
			VNCDialog.dialog("option", "title",
				str('console') + ": " + str('connecting'));
		}
	}

	this.connect = function(secure,host, port, password, path) {
		self.show();
		var target = $('#' + vnc_dialog_id+ " .noVNC_canvas")[0];
		if (typeof rfb == 'undefined')
			rfb = new RFB(
					{
						'target' : target,
						'encrypt' : secure,
						'repeaterID' : '',
						'true_color' : true,
						'local_cursor' : true,
						'shared' : false,
						'view_only' : false,
						'updateState' : updateState,
						'onPasswordRequired' : null
					});
		if(typeof port == 'undefined'){
			port = secure?443:80;
		}
		rfb.connect(host, port, password, path);
	};
	this.isConnected = function() {
		return typeof rfb != 'undefined' && lastState != 'disconnected';
	};
	this.disconnect = function() {
		if (typeof rfb != 'undefined' && lastState == 'normal' )
			rfb.disconnect();
	};
	this.show = function() {
		VNCDialog.dialog('open');
	};

};
