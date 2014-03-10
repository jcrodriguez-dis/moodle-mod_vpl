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
		var root_obj=$('#vpl_root');
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
		function showMessage(message, options) {
			var message_dialog = $('<div id="vpl_ide_message_dig" class="vpl_ide_dialog"></div>');
			if (typeof options == 'undefined') {
				options = {};
			}
			if (typeof options.icon == 'undefined') {
				options.icon = 'info';
			}
			if (typeof options.title == 'undefined') {
				options.title = str('warning');
			}
			message_dialog
					.html('<span class="ui-icon ui-icon-'
							+ options.icon
							+ '" style="float: left; margin: 0px 1em;"></span><span class="dmessage">'
							+ message + '</span>');
			root_obj.append(message_dialog);
			var message_buttons = {};
			if (typeof options.ok == 'undefined') {
				message_buttons[str('ok')] = function() {
					$(this).dialog('close');
				};
			} else {
				message_buttons[str('ok')] = function() {
					$(this).dialog('close');
					options.ok();
				};
				message_buttons[str('cancel')] = function() {
					$(this).dialog('close');
				};
			}
			message_dialog.dialog({
				title : options.title,
				buttons : message_buttons,
				dialogClass : 'vpl_ide vpl_ide_dialog',
				close : function() {
					$(this).remove();
				}
			});
			message_dialog.dialog('open');
			message_dialog.setMessage = function(men) {
				$(message_dialog).find('.dmessage').html(men);
			};
			return message_dialog;
		}
		function showErrorMessage(message) {
			return showMessage(message, {
				title : str('error'),
				icon : 'alert'
			});

		}
		function requestAction(action, title, data, ok) {
			if(title=='')
				title = 'connecting';
			progressbar.dialog('option','title',str(action));
			progressbar.setLabel(str(title));					
			progressbar.dialog('open');
			var request=$.ajax({
				async : true,
				type : "POST",
				url : options['ajaxurl'] + action,
				'data' : JSON.stringify(data),
				contentType : "application/json; charset=utf-8",
				dataType : "json"
			}).done(function(response) {
				progressbar.dialog('close');
				if (!response.success) {
					showErrorMessage(response.error);
				} else {
					ok(response.response);
				}
			}).fail(function(jqXHR, textStatus, errorThrown) {
				progressbar.dialog('close');
				if(errorThrown != 'abort')
					showErrorMessage(textStatus);
			});
			progressbar.on("dialogclose",function(){
				if(request.readyState != 4){
					request.abort();
				}
			});
		}
		
		function launchEvaluation(){
			requestAction('evaluate', 'evaluating', {},
					function(response) {
						evaluationMonitor(response.monitorURL, 'evaluate','evaluating');
					}
				);
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
				if(URL.search('wss:') == 0){
					requestAction('getjails', 'retrieve',{},
							function(response) {
								var servers = response.servers;
								if(servers.length >0){
									//generate links dialog
									var html=str('acceptcertificatesnote');
									html+='<ol>';
									for (var i in servers) {
										var n=Number(i)+1;
										html += '<li><a href="'+servers[i]+'" target="_blank">Server '+n+'</a><br /></ul>';
									}
									html+='</ol>';
									var m=showMessage(html,{
										ok: launchEvaluation,
										icon: 'unlocked',
										title: str('acceptcertificates')
									});
									$(m).find('a').on('click keypress',
											function(e){
										var w=550;
										var h=450;
										var left = (screen.width/2)-(w/2);
										var top = (screen.height/2)-(h/2);
										try{
											var win=window.open($(this).attr('href'),'_blank'
												,'toolbar=no, location=no, directories=no, status=no'
												+', menubar=no, resizable=yes, scrollbars=yes, copyhistory=no'
												+', width='+w+', height='+h+', top='+top+', left='+left);
											if(typeof win == 'undefined') return true;
										}catch(e){
											return true;
										}
										e.preventDefault();
										$(this).parent().hide();
										return false;
									}
									);
								}else
									showErrorMessage(str('connection_fail'));
							});
				}
				else
					showErrorMessage(str('connection_fail'));
			};
			ws.onclose = function(event) {
				if(progressbar.dialog('isOpen') && !(event.code != 1000 && URL.search('wss:') == 0))
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
						var text=str(state);
						if(detail >'')
							text += ': '+detail;
						progressbar.setLabel(text);
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
						next=options['nexturl'];
						ws.close();
						break;
					}
				}
			};
		}
		launchEvaluation();
	};
})();