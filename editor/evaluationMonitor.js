/**
 * HTML/JavaScript Code Editor
 * @package mod_vpl
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
		var root_obj=$JQVPL('#vpl_root');
		function progressBar(title, message, onClose) {
			var labelHTML='<div class="vpl_ide_progressbarlabel"></div>';
			var pbHTML='<div class="vpl_ide_progressbar">'+labelHTML+'</div>';
			var HTML='<div class="vpl_ide_dialog" style="display: none;">'+pbHTML+'</div>';
			var dialog = $JQVPL(HTML);
			root_obj.append(dialog);
			var progressbar=dialog.find('.vpl_ide_progressbar');
			progressbar.progressbar({value : false});
			var label=progressbar.find('.vpl_ide_progressbarlabel');
			dialog.dialog({
				'title' : title,
				resizable: false,
				autoOpen : false,
				minHeight : 20,
				width : 'auto',
				modal : true,
				dialogClass : 'vpl_ide vpl_ide_dialog',
				close : function() {
					if(dialog){
						$JQVPL(dialog).remove();
						dialog=false;
						if(onClose)
							onClose();
						onClose=false;
					}
				}
			});			
			label.text(message);
			this.setLabel=function(t){
				if(dialog) label.text(t);
			};
			this.close=function(){
				onClose=false;
				if(dialog) dialog.dialog('close');
			};
			dialog.dialog('open');
		}
		function showMessage(message, options) {
			var message_dialog = $JQVPL('<div id="vpl_ide_message_dig" class="vpl_ide_dialog"></div>');
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
					$JQVPL(this).dialog('close');
				};
			} else {
				message_buttons[str('ok')] = function() {
					$JQVPL(this).dialog('close');
					options.ok();
				};
				message_buttons[str('cancel')] = function() {
					$JQVPL(this).dialog('close');
				};
			}
			message_dialog.dialog({
				title : options.title,
				buttons : message_buttons,
				dialogClass : 'vpl_ide vpl_ide_dialog',
				close : function() {
					$JQVPL(this).remove();
				}
			});
			message_dialog.dialog('open');
			message_dialog.setMessage = function(men) {
				$JQVPL(message_dialog).find('.dmessage').html(men);
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
			var request;
			if(title=='')
				title = 'connecting';
			var pb= new progressBar(str(action),str(title),
				function(){
					if(request.readyState != 4){
						request.abort();
					}
				}
			);					
			request=$JQVPL.ajax({
				async : true,
				type : "POST",
				url : options['ajaxurl'] + action,
				'data' : JSON.stringify(data),
				contentType : "application/json; charset=utf-8",
				dataType : "json"
			}).done(function(response) {
				pb.close();
				if (!response.success) {
					showErrorMessage(response.error);
				} else {
					ok(response.response);
				}
			}).fail(function(jqXHR, textStatus, errorThrown) {
				pb.close();
				if(errorThrown != 'abort')
					showErrorMessage(str('connection_fail')+': '+textStatus);
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
			var pb= new progressBar(str(title),str('connecting'),
					function(){ws.close();}
				);
			ws.notOpen = true;
			ws.onopen = function(event) {
				ws.notOpen = false;
				pb.setLabel(str('connected'));
			};
			ws.onerror = function(event) {
				pb.close();
				if(URL.search('wss:') == 0 && ws.notOpen){
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
									$JQVPL(m).find('a').on('click keypress',
											function(e){
										var w=550;
										var h=450;
										var left = (screen.width/2)-(w/2);
										var top = (screen.height/2)-(h/2);
										try{
											var win=window.open($JQVPL(this).attr('href'),'_blank'
												,'toolbar=no, location=no, directories=no, status=no'
												+', menubar=no, resizable=yes, scrollbars=yes, copyhistory=no'
												+', width='+w+', height='+h+', top='+top+', left='+left);
											if(typeof win == 'undefined') return true;
										}catch(e){
											return true;
										}
										e.preventDefault();
										$JQVPL(this).parent().hide();
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
				pb.close();
				//console.log('Console monitor websocket close');
				if(next != ''){
					setTimeout(function(){
						window.location=next;
					},50);
				}
			};
			ws.onmessage = function(event) {
				//console.log("Monitor receive: " + event.data);
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
						pb.setLabel(text);
						break;
					case 'retrieve':
						pb.close();
						requestAction('retrieve', 'evaluating', '',function() {
							next=options['nexturl'];
							ws.close();
						});
						break;
					case 'run':
						pb.close();
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