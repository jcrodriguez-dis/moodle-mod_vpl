/**
 * @package VPL. Util for IDE and related
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
(function() {
	// Get scrollBarWidth
	VPL_Util = {};
	VPL_Util.scrollBarWidth = function() {
		var parent, child, width;
		parent = $JQVPL(
				'<div style="width:50px;height:50px;overflow:auto"><div/></div>')
				.appendTo('body');
		child = parent.children();
		width = child.innerWidth() - child.height(99).innerWidth();
		parent.remove();
		return width;
	};
	VPL_Util.sanitizeHTML=function(t) {
		return $JQVPL('<div>' + t + '</div>').html();
	};
	VPL_Util.sanitizeText=function(s) {
		return s.replace(/&/g, "&amp;").replace(/</g, "&lt;")
				.replace(/>/g, "&gt;");
	};

	VPL_Util.setProtocol = function(coninfo) {
		var secure=window.location.protocol == 'https:';
		var URLBase=(secure?'wss://':'ws://')+coninfo.server;
		coninfo.secure = secure;
		coninfo.portToUse = secure?coninfo.securePort:coninfo.port;
		URLBase+=':'+coninfo.portToUse+'/';
		coninfo.monitorURL=URLBase+coninfo.monitorPath;
		coninfo.executionURL=URLBase+coninfo.executionPath;
	};
	VPL_Util.ArrayBuffer2String = function(data) {
		var view = new Uint8Array(data);
		var chunks =[];
		var chunkSize = 32000;
		for (var i = 0, len = view.length; i < len; i += chunkSize) {
			chunks.push(String.fromCharCode.apply(String, view
					.subarray(i, Math.min(i + chunkSize, len))));
		}
		return chunks.join('');
	};
	VPL_Util.String2ArrayBuffer = function(data) {
		var len = data.length;
		var ret = new ArrayBuffer(len);
		var u8 = new Uint8Array(ret);
		for (var i = 0; i < len; i++)
			u8[i] = data.charCodeAt(i);
		return ret;
	};

	(function(){
		var file_unique_id = 0;
		VPL_Util.getUniqueId= function(){
			return file_unique_id++;
		};
	})();
	(function() {
		var reg_ext = /\.([^.]*)$/;
		var reg_img = /^(gif|jpg|jpeg|png|ico)$/i;
		var reg_bin = /^(zip|jar|pdf)$/i;
		VPL_Util.fileExtension = function(fileName) {
			var res = reg_ext.exec(fileName);
			return res !== null ? res[1] : '';
		};
		VPL_Util.isImage = function(fileName) {
			return reg_img.test(VPL_Util.fileExtension(fileName));
		};
		VPL_Util.isBinary = function(fileName) {
			return VPL_Util.isImage(fileName)
					|| reg_bin.test(VPL_Util.fileExtension(fileName));
		};

		var regInvalidFileName = /[\x00-\x1f]|[:-@]|[{-~]|\\|\[|\]|[\/\^`´]|^\-|^ | $|\.\./;
		VPL_Util.validFileName = function(fileName) {
			if (fileName.length < 1)
				return false;
			if (fileName.length > 128)
				return false;
			return !(regInvalidFileName.test(fileName));
		};
	})();
	VPL_Util.encodeBinary = function(name,data) {
		if(!VPL_Util.isBinary(name)) return data;
		return btoa(VPL_Util.ArrayBuffer2String(data));
	};

	VPL_Util.decodeBinary = function(name,data) {
		if(!VPL_Util.isBinary(name)) return data;
		return VPL_Util.String2ArrayBuffer(atob(data));		
	};

	VPL_Util.validPath = function(path) {
		if (path.length > 256)
			return false;
		var dirs = path.split("/");
		for (var i = 0; i < dirs.length; i++)
			if (!VPL_Util.validFileName(dirs[i]))
				return false;
		return true;
	};
	VPL_Util.getFileName = function(path) {
		var dirs = path.split("/");
		return dirs[dirs.length-1];
	};
	VPL_Util.readZipFile = function(data, save, progressBar) {
		var ab = VPL_Util.ArrayBuffer2String(data);
		var unzipper = new JUnzip(ab);
		if (unzipper.isZipFile()) {
			unzipper.readEntries();
			function process(i){
			    if(i >= unzipper.entries.length || progressBar.isClosed()) return; 
				var entry = unzipper.entries[i];
				var fileName = entry.fileName;
				var data;
				// Is directory entry then skip
				if (fileName.match(/\/$/)){
					process(i+1);
				}else{
					progressBar.processFile(fileName);
					var uncompressed = '';
					if (entry.compressionMethod === 0) {
						// plain
						uncompressed = entry.data;
					} else if (entry.compressionMethod === 8) {
						uncompressed = JSInflate.inflate(entry.data);
					}
					data = VPL_Util.String2ArrayBuffer(uncompressed);
					if (VPL_Util.isBinary(fileName)) {
						//If binary use as arrayBuffer
						save(fileName, data);
						progressBar.endFile();
					}else{
						blob = new Blob([data],{type:'text/plain'});
						fr = new FileReader();
						fr.onload=function(e){
							save(fileName, e.target.result);
							progressBar.endFile();
						};
						fr.readAsText(blob);
					}
					process(i+1);					
				}
			};
			process(0);
		}
	};

	VPL_Util.readSelectedFiles = function(filesToRead, save) {
		// process all File objects
		var pb=new VPL_Util.progressBar('import','import');
		var filePending=0;
		pb.processFile=function(name){
			pb.setLabel(name);
			filePending++;
		};
		pb.endFile=function(){
			filePending--;
			if(filePending==0){
				pb.close();
			}
		};
		function readSecuencial(sec) {
			if (sec >= filesToRead.length || pb.isClosed()){
				return;
			}
			var f = filesToRead[sec];
			pb.processFile(f.name);
			var reader = new FileReader();
			var ext = VPL_Util.fileExtension(f.name).toLowerCase();
			reader.onload = function(e) {
				if (ext == 'zip') {
					VPL_Util.readZipFile(e.target.result,save, pb);
				} else {
					save(f.name, e.target.result);
				}
				// Load next file
				readSecuencial(sec + 1);
				pb.endFile();
			};
			if (VPL_Util.isBinary(f.name))
				reader.readAsArrayBuffer(f);
			else
				reader.readAsText(f);
		}
		readSecuencial(0);
	};
	(function() {
		var MIME = {
			'gif' : 'image/gif',
			'jpg' : 'image/jpeg',
			'jpeg' : 'image/jpeg',
			'png' : 'image/png',
			'ico' : 'image/vnd.microsoft.icon',
			'pdf' : 'application/pdf'
		};
		VPL_Util.getMIME = function(fileName) {
			var ext = VPL_Util.fileExtension(fileName);
			if (ext in MIME) {
				return MIME[ext];
			}
			return 'application/octet-stream';
		};
	})();
	(function() {
		var maplang = {
			'ada' : 'ada',
			'ads' : 'ada',
			'adb' : 'ada',
			'asm' : 'assembly_x86',
			'bash' : 'bash',
			'c' : 'c_cpp',
			'C' : 'cpp',
			'cases' : 'cases',
			'cbl' : 'cobol',
			'cob' : 'cobol',
			'coffee' : 'coffee',
			'cc' : 'c_cpp',
			'cpp' : 'c_cpp',
			'hxx' : 'c_cpp',
			'h' : 'c_cpp',
			'clj' : 'clojure',
			'cs' : 'csharp',
			'css' : 'css',
			'd' : 'd',
			'erl' : 'erlang',
			'hrl' : 'erlang',
			'f' : 'fortran',
			'f77' : 'fortran',
			'go' : 'go',
			'hs' : 'haskell',
			'htm' : 'html',
			'html' : 'html',
			'hx' : 'haxe',
			'java' : 'java',
			'js' : 'javascript',
			'json' : 'json',
			'scm' : 'scheme',
			's' : 'scheme',
			'm' : 'matlab',
			'lisp' : 'lisp',
			'lsp' : 'lisp',
			'lua' : 'lua',
			'pas' : 'pascal',
			'p' : 'pascal',
			'perl' : 'perl',
			'prl' : 'perl',
			'php' : 'php',
			'pro' : 'prolog',
			'pl' : 'prolog',
			'py' : 'python',
			'r' : 'r',
			'rb' : 'ruby',
			'ruby' : 'ruby',
			'scala' : 'scala',
			'sh' : 'sh',
			'sql' : 'sql',
			'tcl' : 'tcl',
			'xml' : 'xml',
			'yaml' : 'yaml'
		};
		VPL_Util.langType = function(ext) {
			if (ext in maplang) {
				return maplang[ext];
			}
			return 'plain_text';
		};
	})();
	(function() {
		var i18n = {};
		VPL_Util.str = function(key) {
			if (! i18n[key]) {
				return '{' + key + '}';
			}
			return i18n[key];
		};
		VPL_Util.set_str = function(newi18n) {
			for ( var key in newi18n)
				i18n[key] = newi18n[key];
		};
	})();
	(function() {
		var delayedActions = {};
		var reg=/function ([^\(]*)/;
		function functionName(func){
			var fs=func.toString();
			var fn=reg.exec(fs)[1];
		    return fn==""?fs:fn;			
		}
		VPL_Util.delay = function(func,arg1,arg2) {
			var fn=functionName(func);
			if(delayedActions[fn]){
				clearTimeout(delayedActions[fn]);
			}
			delayedActions[fn]=setTimeout(function(){
				func(arg1,arg2);
				delayedActions[fn]=false;
			},100);
		};
		VPL_Util.longDelay = function(func,arg1,arg2) {
			var fn=functionName(func);
			if(delayedActions[fn]){
				clearTimeout(delayedActions[fn]);
			}
			delayedActions[fn]=setTimeout(function(){
				func(arg1,arg2);
				delayedActions[fn]=false;
			},1000);
		};
	})();
	VPL_Util.iconModified = function() {
		return '<span title="' + VPL_Util.str('modified')
				+ '" class="vpl_ide_charicon">' + '<i class="fa fa-star"></i>'
				+ '</span> ';
	};
	VPL_Util.iconDelete = function() {
		return ' <span title="' + VPL_Util.str('delete')
				+ '" class="vpl_ide_charicon vpl_ide_delicon">'
				+ '<i class="fa fa-trash"></i>' + '</span> ';
	};
	VPL_Util.iconClose = function() {
		return ' <span title="' + VPL_Util.str('closebuttontitle')
				+ '" class="vpl_ide_charicon vpl_ide_closeicon">'
				+ '<i class="fa fa-remove"></i>' + '</span> ';
	};
	VPL_Util.iconRequired = function() {
		return ' <span title="' + VPL_Util.str('required')
				+ '" class="vpl_ide_charicon">'
				+ '<i class="fa fa-shield"></i>' + '</span> ';
	};
	VPL_Util.iconFolder = function() {
		return '<i class="fa fa-folder-open-o"></i>';
	};
	(function() {
		var menu_icons = {
			'filelist': 'folder',
			'filelistclose': 'folder-open',
			'new' : 'file',
			'rename' : 'pencil',
			'delete' : 'trash',
			'close': 'remove',
			'import' : 'upload',
			'print' : 'print',
			'edit' : 'edit',
			'undo' : 'undo',
			'redo' : 'repeat',
			'select_all' : 'location-arrow',
			'find' : 'search',
			'find_replace' : 'exchange',
			'next' : 'search-plus',
			'resetfiles' : 'refresh',
			'download' : 'download',
			'fullscreen' : 'expand',
			'regularscreen' : 'compress',
			'save' : 'save',
			'sort' : 'sort-amount-asc',
			'run' : 'caret-square-o-right',
			'debug' : 'bug',
			'evaluate' : 'check-square-o',
			'console' : 'terminal',
			'about' : 'question',
			'info' : 'info-circle',
			'alert' : 'warning',
			'trash' : 'trash',
			'retrieve' : 'download',
			'spinner' : 'refresh fa-spin',
		};
		VPL_Util.gen_icon = function(icon, size) {
			if (! menu_icons[icon] )
				return '';
			var classes='fa fa-';
			if (! size)
				classes+='lg';
			else
				classes+=size;
			classes +=' fa-'+menu_icons[icon];
			return ret="<i class='"+classes+ "'></i>";
		};
	})();
	//UI operations
	VPL_Util.progressBar= function(title, message, onUserClose) {
		var labelHTML='<span class="vpl_ide_progressbarlabel"></span>';
		var sppiner='<div class="vpl_ide_progressbaricon">'+VPL_Util.gen_icon('spinner')+'</div>';
		var pbHTML=' <div class="vpl_ide_progressbar">'+sppiner+labelHTML+'</div>';
		var HTML='<div class="vpl_ide_dialog" style="display: none;">'+pbHTML+'</div>';
		var dialog = $JQVPL(HTML);
		$JQVPL('body').append(dialog);
		var progressbar=dialog.find('.vpl_ide_progressbar');
		//progressbar.progressbar({value : false});
		var label=progressbar.find('.vpl_ide_progressbarlabel');
		dialog.dialog({
			'title' : VPL_Util.str(title),
			resizable: false,
			autoOpen : false,
			width: 250,
			minHeight : 20,
			height : 20,
			modal : true,
			dialogClass : 'vpl_ide vpl_ide_dialog',
			close : function() {
				if(dialog){
					$JQVPL(dialog).remove();
					dialog=false;
					if(onUserClose)
						onUserClose();
					onUserClose=false;
				}
			}
		});		
		this.setLabel=function(t,icon){
			if(dialog){
				label.text(t);
				if(icon){
					label.html(VPL_Util.gen_icon(icon)+' '+label.html());
				}
			}
		};
		this.close=function(){
			onUserClose=false;
			if(dialog) dialog.dialog('close');
		};
		this.isClosed=function(){
			return dialog==false;
		};
		var titleTag=dialog.siblings().find('.ui-dialog-title');
		titleTag.html(VPL_Util.gen_icon(title)+' '+titleTag.html());
		this.setLabel(VPL_Util.str(message));
		dialog.dialog('open');
		dialog.dialog('option','height','auto');
	};
	VPL_Util.dialogbase_options = {
			autoOpen : false,
			minHeight : 10,
			width : 'auto',
			closeText : VPL_Util.str('cancel'),
			modal : true,
			dialogClass : 'vpl_ide vpl_ide_dialog'
	};
	VPL_Util.showMessage=function(message, options) {
		var message_dialog = $JQVPL('<div class="vpl_ide_dialog"></div>');
		if (! options ) {
			options = {};
		}
		if (! options.icon ) {
			options.icon = 'info';
		}
		if (! options.title ) {
			options.title = VPL_Util.str('warning');
		}
		message_dialog
				.html(VPL_Util.gen_icon(options.icon)+' <span class="dmessage">'
						+ message + '</span>');
		$JQVPL('body').append(message_dialog);
		var message_buttons = {};
		if (! options.ok ) {
			message_buttons[VPL_Util.str('ok')] = function() {
				$JQVPL(this).dialog('close');
			};
		} else {
			message_buttons[VPL_Util.str('ok')] = function() {
				$JQVPL(this).dialog('close');
				options.ok();
			};
			message_buttons[VPL_Util.str('cancel')] = function() {
				$JQVPL(this).dialog('close');
			};
		}
		if(options.next){
			message_buttons[VPL_Util.str('next')] = function() {
				$JQVPL(this).dialog('close');
				options.next();
			};
		}
		if(options.close){
			options.oldClose = options.close;
		}
		message_dialog.dialog($JQVPL.extend({}, VPL_Util.dialogbase_options, {
			title : options.title,
			buttons : message_buttons,
			close : function() {
				$JQVPL(this).remove();
				if(options.oldClose){
					options.oldClose();
				}
			}
		}));
		message_dialog.dialog('open');
		message_dialog.setMessage = function(men) {
			$JQVPL(message_dialog).find('.dmessage').html(men);
		};
		return message_dialog;
	};
	VPL_Util.showErrorMessage=function(message, options) {
		var currentOptions=$JQVPL.extend({}, VPL_Util.dialogbase_options,
			{
				title : VPL_Util.str('error'),
				icon : 'alert'
			});
		if(options){
			currentOptions=$JQVPL.extend(currentOptions, options);
		}
		return VPL_Util.showMessage(message,currentOptions);
	};

	VPL_Util.requestAction = function(action, title, data, URL, ok, error) {
		//console.log('Open request '+action);
		var request=null;
		if(title=='')
			title='connecting';
		var pb = new VPL_Util.progressBar(action,title,
			function(){
				//console.log('Close request '+action);
				if(request.readyState != 4)
					request.abort();
		});
		request=$JQVPL.ajax({
			async : true,
			type : "POST",
			url : URL + action,
			'data' : JSON.stringify(data),
			contentType : "application/json; charset=utf-8",
			dataType : "json"
		}).done(function(response) {
			pb.close();
			if (!response.success) {
				error(response.error);
			} else {
				ok(response.response);
			}
		}).fail(function(jqXHR, textStatus, errorThrown) {
			pb.close();
			if(errorThrown != 'abort')
				error(VPL_Util.str('connection_fail')+': '+textStatus);
		});
	};
	VPL_Util.supportWebSocket= function() {
		if ("WebSocket" in window)
			return true;
		return false;
	};
	VPL_Util.clickServer=function(e) {
		var w = 550;
		var h = 450;
		var left = (screen.width / 2) - (w / 2);
		var top = (screen.height / 2) - (h / 2);
		try {
			var win = window.open($JQVPL(this).attr('href'),'_blank',
							'toolbar=no, location=no, directories=no, status=no, menubar=no, resizable=yes, scrollbars=yes, copyhistory=no, width='
									+ w
									+ ', height='
									+ h
									+ ', top='
									+ top
									+ ', left='
									+ left);
			if (!win)
				return true;
		} catch (e) {
			return true;
		}
		e.preventDefault();
		$JQVPL(this).parent().hide();
		return false;
	};
	VPL_Util.acceptCertificates = function(servers, getLastAction) {
		if (servers.length > 0) {
			// generate links dialog
			var html = VPL_Util.str('acceptcertificatesnote');
			html += '<ol>';
			for ( var i in servers) {
				var n = Number(i) + 1;
				html += '<li><a href="' + servers[i]
						+ '" target="_blank">Server ' + n + '</a><br /></ul>';
			}
			html += '</ol>';
			var m = VPL_Util.showMessage(html, {
				ok : function() {
					var action=getLastAction();
					if (action) {
						action();
					}
				},
				icon : 'unlocked',
				title : VPL_Util.str('acceptcertificates')
			});
			$JQVPL(m).find('a').on('click keypress',VPL_Util.clickServer);
		} else
			VPL_Util.showErrorMessage(VPL_Util.str('connection_fail'));
	};

	VPL_Util.webSocketMonitor= function(coninfo, title, running, externalActions) {
		VPL_Util.setProtocol(coninfo);
		var ws=null;
		var pb=null;
		function showErrorMessage(message){
			VPL_Util.showErrorMessage(message,{next:externalActions.next});
		}
		var messageActions = {
			'message' : function(content) {
				var parsed = /^([^:]*):?(.*)/i.exec(content);
				var state = parsed[1];
				var detail = parsed[2];
				if (state == 'running')
					state = running;
				var text = VPL_Util.str(state);
				if (detail > '')
					text += ': ' + detail;
				if (externalActions.getConsole && externalActions.getConsole().isOpen())
					externalActions.getConsole().setMessage(text);
				else
					pb.setLabel(text);
			},
			'compilation' : function(content) {
				if(externalActions.setResult){
					externalActions.setResult({
						'grade' : '',
						'compilation' : content,
						'evaluation' : '',
						'execution' : '',
					}, false);
				}
			},
			'retrieve' : function() {
				pb.close();
				VPL_Util.requestAction('retrieve', '', '',
						externalActions.ajaxurl,
						function(response) {
							if(externalActions.setResult){
								externalActions.setResult(response, true);
							}
						},
						showErrorMessage);
			},
			'run' : function(content) {
				pb.close();
				externalActions.run(content,coninfo,ws);
			},
			'close' : function() {
				ws.close();
				if(externalActions.close){
					externalActions.close();
				}				
			}
		};
		try{
			if(VPL_Util.supportWebSocket()){
				ws= new WebSocket(coninfo.monitorURL);
			}else{
				showErrorMessage(VPL_Util.str('browserupdate'));
				return;
			}
		}catch(e){
			showErrorMessage(e.message);
			return;
		}
		pb = new VPL_Util.progressBar(title,'connecting',
			function(){ws.close();}
		);
		//console.log('Open ws '+response.monitorURL);
		ws.notOpen = true;
		ws.onopen = function(event) {
			ws.notOpen = false;
			pb.setLabel(VPL_Util.str('connected'));
		};
		ws.onerror = function(event) {
			pb.close();
			if(coninfo.secure && ws.notOpen){
				VPL_Util.requestAction('getjails', 'retrieve',{},
						externalActions.ajaxurl,
						function(response) {
							VPL_Util.acceptCertificates(servers, externalActions.getLastAction);
						},
						showErrorMessage);
			}
			else
				showErrorMessage(VPL_Util.str('connection_fail'));
		};
		ws.onclose = function(event) {
			//console.log('Close ws '+response.monitorURL);
			if(externalActions.getConsole){
				externalActions.getConsole().disconnect();
			}
			pb.close();
		};
		
		ws.onmessage = function(event) {
			//console.log("Monitor receive: " + event.data);
			var message = /^([^:]+):/i.exec(event.data);
			if (message !== null) {
				var action = message[1];
				var content = event.data.substr(action.length + 1);
				if(messageActions[action]){
					messageActions[action](content);
				}
			}else{
				pb.setLabel(VPL_Util.str('error')+': '+event.data);
			}
		};
	};

})();
