/**
 * @version $Id: WCodeEditor.js,v 1. 2012-10-05 09:03:48 juanca Exp $
 * @package VPL. HTML/JavaScript Code Editor
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
(function(){
	VPL_Evaluation= function(options){
		var i18n=options.i18n;
		var str = function(key) {
			if (typeof i18n[key] == 'undefined') {
				return '{' + key + '}';
			}
			return i18n[key];
		};
		var progressbar=$('#vpl_ide_dialog_progress');
		var progressbar_pb=progressbar.find('.vpl_ide_progressbar');
		progressbar_pb.progressbar({value : false});
		var progressbar_pbl=progressbar_pb.find('.vpl_ide_progressbarlabel');
		progressbar.dialog({
			resizable: false,
			autoOpen : false,
			height : 70,
			width : 'auto',
			modal : true,
			dialogClass : 'vpl_ide vpl_ide_dialog'
		});
		
		progressbar.setLabel=function(t){
			progressbar_pbl.text(t);
		};
		function showErrorMessage(text){
			alert(text);
		}
		function requestAction(action, title, data, ok) {
			if(title==''){
				progressbar.dialog('option','title',str(action));
				progressbar.setLabel(str('connecting'));
			}else{
				progressbar.dialog('option','title',str(action));
				progressbar.setLabel(str(title));					
			}
			progressbar.dialog('open');
			var request=$.ajax({
				async : true,
				type : "POST",
				url : options['ajaxurl'] + action,
				'data' : JSON.stringify(data),
				contentType : "application/json; charset=utf-8",
				dataType : "json"
			}).done(function(response) {
				progressbar.off("dialogclose");
				progressbar.dialog('close');
				if (!response.success) {
					showErrorMessage(response.error);
				} else {
					ok(response.response);
				}
			}).fail(function(jqXHR, textStatus, errorThrown) {
				progressbar.off("dialogclose");
				progressbar.dialog('close');
				if(errorThrown != 'abort')
					showErrorMessage(textStatus);
			});
			progressbar.on("dialogclose",function(){request.abort();});
		}

		function evaluationMonitor(URL, title,running) {
			var ws = new WebSocket(URL);
			var next='';
			progressbar.dialog('option','title',str(title));
			progressbar.setLabel(str('connecting'));
			progressbar.dialog('open');
			ws.onopen = function(event) {
				progressbar.setLabel(str('connected'));
			};
			ws.onerror = function(event) {
				progressbar.setLabel(str('connection_fail'));
				progressbar.off("dialogclose");
			};
			ws.onclose = function(event) {
				if(progressbar.dialog('isOpen'))
					progressbar.dialog('close');
				console.log('Console monitor websocket close');
				if(next != ''){
					setTimeout(function(){
						window.location=next;
					},50);
				}
			};
			ws.onmessage = function(event) {
				console.log("Monitor receive: " + event.data);
				var message = /^([^:]+):/i.exec(event.data);
				if (message !== null) {
					var action = message[1];
					var content = event.data.substr(action.length + 1);
					switch (action) {
					case 'message':
						var parsed = /^([^:]*):?(.*)/i.exec(content);
						var state = parsed[1];
						var detail = parsed[2];
						if(state == 'running')
							state = running;
						progressbar.setLabel(str(state)+detail);
						break;
					case 'retrieve':
						requestAction('retrieve', 'evaluating', '',function() {
							next=options['nexturl'];
							ws.close();
						});
						break;
					case 'run':
						showErrorMessage('unexpected error');
					case 'close':
						progressbar.off("dialogclose");
						next=options['nexturl'];
						ws.close();
						break;
					}
				}
			};
		}
		requestAction('evaluate', 'evaluating', {},
			function(response) {
				evaluationMonitor(response.monitorURL, 'evaluate','evaluating');
			}
		);
	};
})();