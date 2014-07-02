/**
 * @version $Id: WCodeEditor.js,v 1. 2012-10-05 09:03:48 juanca Exp $
 * @package VPL. HTML/JavaScript Code Editor
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
(function() {
	// "use strict";
	if (typeof VPL_IDE == 'undefined') {
		var file_unique_id = 0;
		// Editor constructor (only one at this moment)
		VPL_IDE = function(root_id, options) {
			var self = this;
			var i18n = options.i18n;
			// Get scrollBarWidth
			var scrollBarWidth = (function() {
				var parent, child, width;
				parent = $JQVPL(
						'<div style="width:50px;height:50px;overflow:auto"><div/></div>')
						.appendTo('body');
				child = parent.children();
				width = child.innerWidth() - child.height(99).innerWidth();
				parent.remove();
				return width;
			})();
			var str = function(key) {
				if (typeof i18n[key] == 'undefined') {
					return '{' + key + '}';
				}
				return i18n[key];
			};
			var root_obj = $JQVPL('#' + root_id);
			if (typeof root_obj != 'object') {
				throw "VPL: constructor tag_id not found";
			}
			var optionsToCheck = {
				'save' : true,
				'run' : true,
				'edit' : true,
				'debug' : true,
				'evaluate' : true,
				'import' : true,
				'resetfiles' : true,
				'console' : true
			};
			var global_modified = false;
			function activateGlobalModified() {
				if (!global_modified) {
					global_modified = true;
					updateMenu();
				}
			}
			options['import'] = !options.restrictededitor;
			function isOptionAllowed(op) {
				if (typeof optionsToCheck[op] == 'undefined') {
					return true;
				}
				return options[op];
			}
			options['console'] = isOptionAllowed('run')
					|| isOptionAllowed('debug');

			var files = new Array();
			var minNumberOfFiles = options.minfiles;
			var maxNumberOfFiles = options.maxfiles;
			var restrictedEdit = options.restrictededitor;
			var fullScreen = false;
			var localClipboard = "";
			var regInvalidFileName = /[\x00-\x1f]|[:-@]|[{-~]|\\|\[|\]|[\/\^`´]|^\-|^ | $|\.\./;
			function validFileName(fileName) {
				if (fileName.length < 1)
					return false;
				if (fileName.length > 128)
					return false;
				return !(regInvalidFileName.test(fileName));
			}
			function validPath(path) {
				if (path.length > 256)
					return false;
				var dirs = path.split("/");
				for (var i = 0; i < dirs.length; i++)
					if (!validFileName(dirs[i]))
						return false;
				return true;
			}
			// used by VPL_FILE objects
			// File constructor
			var VPL_File = function(name, value) {
				var id = file_unique_id++;
				var tid = "#vpl_file" + id;
				var tabnameid = "#vpl_tab_name" + id;
				var fileName = name;
				var modified = true;
				var self = this;
				this.getId = function() {
					return id;
				};
				this.getFileName = function() {
					return fileName;
				};
				this.resetModified = function() {
					modified = false;
					this.setFileName(fileName);
				};
				this.getTabPos = function() {
					for (var i = 0; i < files.length; i++) {
						if (files[i] == this)
							return i;
					}
					return files.length;
				};
				this.isModified = function() {
					return modified;
				};
				this.setFileName = function(name) {
					if (!validPath(fileName))
						return false;
					if (name != fileName) {
						fileName = name;
						modified = true;
					}
					var fn = name;
					if (fn.length > 20) {
						fn = '...' + fn.substr(-16);
					}
					var html = (modified ? this.iconModified : '') + fn;
					if (this.getTabPos() < minNumberOfFiles) {
						html = html + this.iconRequired;
					} else {
						html = html + this.iconDelete;
					}
					$JQVPL(tabnameid + ' a').html(html);
					if (fn != name)
						$JQVPL(tabnameid + ' a').attr('title', name);
					tabs.tabs('refresh');
					this.langSelection();
				};
				this.getContent = function() {
					return editor.getValue();
				};
				this.setContent = function(c) {
					editor.setValue(c);
				};
				this.destroy = function() {
					editor.destroy();
					$JQVPL(tabnameid).remove();
					$JQVPL(tid).remove();
					tabs.tabs('refresh');
				};
				this.ajustSize = function() {
					var editTag = $JQVPL(tid);
					if (editTag.length === 0)
						return;
					var editorHeight = editTag.height();
					var editorWidth = editTag.width();
					var newHeight = tabs.height();
					newHeight -= editTag.position().top + 8;
					var newWidth = tabs.width() - editTag.position().left;
					newWidth -= 2 * scrollBarWidth;
					newHeight -= scrollBarWidth;
					if (newHeight != editorHeight || newWidth != editorWidth) {
						$JQVPL(editTag).height(newHeight);
						$JQVPL(editTag).width(newWidth);
						editor.resize(true);
					}
					;
				};
				this.gotoLine = function(line) {
					editor.gotoLine(line, 0);
					editor.scrollToLine(line, true);
					editor.focus();
				};
				this.setReadOnly = function(s) {
					editor.setReadOnly(s);
				};
				this.focus = function() {
					editor.focus();
				};
				this.blur = function() {
					editor.blur();
				};
				this.undo = function() {
					editor.undo();
				};
				this.redo = function() {
					editor.redo();
				};
				this.getTabNameId = function() {
					return tabnameid;
				};
				this.selectAll = function() {
					editor.selectAll();
				};
				this.hasUndo = function() {
					session.getUndoManager().hasUndo();
				};
				this.hasRedo = function() {
					session.getUndoManager().hasRedo();
				};
				this.find = function(s) {
					editor.execCommand('find');
				};
				this.replace = function(s) {
					editor.execCommand('replace');
				};
				this.next = function(s) {
					editor.execCommand('findnext');
				};
				this.getAnnotations = function() {
					return session.getAnnotations();
				};
				this.setAnnotations = function(a) {
					return session.setAnnotations(a);
				};
				this.clearAnnotations = function() {
					return session.clearAnnotations();
				};
				this.langSelection = function() {
					var res = /[^.]+\.(.*)$/.exec(fileName);
					var lang = 'txt';
					if (res !== null) {
						var ext = res[1];
						//console.log('extension ' + ext);
						lang = this.langType(ext);
					}
					session.setMode("ace/mode/" + lang);
				};

				/**
				 * Create new tab and new ace editor object
				 */

				tabs_lu.append('<li id="vpl_tab_name' + id + '">' + '<a href="'
						+ tid + '"></a>' + '</li>');
				tabs.append('<pre id="vpl_file' + id
						+ '" class="vpl_ide_file"></pre>');
				var editor = ace.edit("vpl_file" + id);
				var session = editor.getSession();
				editor.setTheme("ace/theme/chrome");
				this.setFileName(fileName);
				editor.setValue(value);
				editor.gotoLine(0, 0);
				editor.setReadOnly(readOnly);
				session.setUseSoftTabs(true);
				session.setTabSize(4);
				// adjust container tag border
				tabs.tabs('refresh');
				// Avoid undo of editor initial content

				session.setUndoManager(new ace.UndoManager());
				// =================================================
				// Code to control Paste and drop under restricted editing
				editor.execCommand('replace');
				function addEventDrop() {
					var tag=$JQVPL(tid + ' div.ace_search');
					if (tag.length) {
						tag.on('drop', noDrop);
						var button=$JQVPL('button.ace_searchbtn_close');
						button.css({ marginLeft : "1em", marginRight : "1em" });
						button.trigger('click');
					} else {
						setTimeout(addEventDrop, 50);
					}
				}
				function changed() {
					if (!modified) {
						modified = true;
						self.setFileName(fileName);
						activateGlobalModified();
					}
				}
				editor.on('change', changed);
				// Try to grant noDrop installation
				setTimeout(addEventDrop, 5);
				// Save previous onPaste and change for a new one
				var prevOnPaste = editor.onPaste;
				editor.onPaste = function(s) {
					if (restrictedEdit) {
						editor.insert(localClipboard);
					} else {
						prevOnPaste.call(editor, s);
					}
				};
				//Control copy and cut (yes cut also use this) for localClipboard
				editor.on('copy', function(t) {
					localClipboard = t;
				});
				// Avoid drop operations
				function noDrop(e) {
					if (restrictedEdit) {
						e.stopImmediatePropagation();
						return false;
					}
				}
				function restrictedPaste(e) {
					if (restrictedEdit) {
						e.stopPropagation();
						return false;
					}
				}
				$JQVPL(tid).on('paste', '*', restrictedPaste);
				$JQVPL(tid + ' div.ace_content').on('drop', noDrop);
				/*
				var dropzone = $JQVPL('<div dropzone="dropzone" class="vpl_ide_dropzone"><div>'
						+ str('drophere') + '</div></div>');
				dropzone.dialog({
					title : str('drophere'),
					autoOpen : false,
					width : 100,
					height : 100,
					modal : true,
					dialogClass : 'vpl_ide_dropzone',
				});
				dropzone.hide();

				root_obj.append(dropzone);*/
				$JQVPL(tid + ' div.ace_content,' + tid + ' div.ace_search').on(
						'dragenter', function(e) {
							//console.log('dragenter');
							if (!restrictedEdit) {
								//console.log('dragenter show');
								//dropzone.dialog('open');
							}
							return false;
						});
				$JQVPL(tid + ' div.ace_content,' + tid + ' div.ace_search').on(
						'dragleave', function(e) {
							//console.log('dragleave');
							if (!restrictedEdit) {
								// dropzone.dialog('close');
							}
							return false;
						});
				//dropzone.on('drop', file_select_handler);

				// size adjust
				this.ajustSize();
			};
			VPL_File.prototype.iconModified = '<span title="' + str('modified')
					+ '" class="vpl_ide_charicon">+</span> ';
			VPL_File.prototype.iconDelete = ' <span title="' + str('delete')
					+ '" class="vpl_ide_charicon vpl_ide_delicon">x</span>';
			VPL_File.prototype.iconRequired = ' <span title="'
					+ str('required') + '" class="vpl_ide_charicon">▼</span>';
			function fileNameExists(name) {
				var checkName = name.toLowerCase();
				for (var i = 0; i < files.length; i++) {
					if (files[i].getFileName().toLowerCase() == checkName)
						return i;
				}
				return -1;
			}
			function fileNameIncluded(name) {
				var checkName = name.toLowerCase() + '/';
				for (var i = 0; i < files.length; i++) {
					var nameMod = files[i].getFileName().toLowerCase() + '/';
					// Check for name as directory existent
					if (nameMod.indexOf(checkName) == 0
							|| checkName.indexOf(nameMod) == 0)
						return true;
				}
				return false;
			}

			function addNewFile(name, value, replace) {
				if (!validPath(name)) {
					showMessage(str('incorrect_file_name') + ' (' + name + ')');
					return false;
				}
				if (replace !== true) {
					replace = false;
				}
				var pos = fileNameExists(name);
				if (pos != -1) {
					if (replace) {
						files[pos].setContent(value);
						return true;
					} else {
						showMessage(str('filenotadded').replace(/\{\$a\}/g, name));
						return false;
					}
					;
				}
				if (fileNameIncluded(name)) {
					showMessage(str('filenotadded').replace(/\{\$a\}/g, name));
					return false;
				}
				if (files.length >= maxNumberOfFiles) {
					showMessage(str('maxfilesexceeded') + ' ('
							+ maxNumberOfFiles + ')');
					return false;
				}
				var ret = new VPL_File(name, value);
				files.push(ret);
				if (files.length > minNumberOfFiles) {
					tabname = $JQVPL(ret.getTabNameId());
					tabname.on('click', 'span.vpl_ide_delicon',
							menuActions['delete']);
					/*console.log('on click delete ' + newfile.getTabNameId()
							+ ' span.vpl_ide_delicon');*/
					tabname.on('dblclick',
							menuActions['rename']);
				}

				if (files.length == 1) {
					$JQVPL(tabs).tabs('option', 'active', 0);
				}
				updateMenu();
				return ret;
			}
			function renameFile(id, newname) {
				// TODO check file name
				try {
					if (!(id in files))
						throw "";
					if (id < minNumberOfFiles)
						throw "";
					if (files[id].getFileName() == newname)
						return true; // equals name file
					if (!validPath(newname) || fileNameIncluded(newname))
						throw str('incorrect_file_name');
					files[id].setFileName(newname);
					activateGlobalModified();
				} catch (e) {
					showMessage(str('filenotrenamed').replace(/\{\$a\}/g, newname)+ ': ' + e);
					return false;
				}
				return true;
			}
			function deleteFile(name) {
				var pos = fileNameExists(name);
				if (pos == -1) {
					showMessage(str('filenotdeleted').replace(/\{\$a\}/g, name));
					return false;
				}
				if (pos < minNumberOfFiles) {
					showMessage(str('filenotdeleted').replace(/\{\$a\}/g, name));
					return false;
				}
				activateGlobalModified();
				files[pos].destroy();
				files.splice(pos, 1);
				if (files.length > 0) {
					tabs.tabs('option', 'active',
							pos >= files.length ? files.length - 1 : pos);
				}
				return true;
			}
			;
			function currentFile() {
				var id = tabs.tabs('option', 'active');
				if (id in files) {
					var file = files[id];
					if (arguments.legth === 0) {
						return file;
					}
					var action = arguments[0];
					if (typeof file[action] === 'function') {
						var fun = file[action];
						var args = Array.prototype.slice(arguments);
						args.shift();
						return fun.apply(file, args);
					}
				}
				return false;
			}
			VPL_File.prototype.maplang = {
				'ada' : 'ada','ads' : 'ada','adb' : 'ada',
				'asm' : 'assembly_x86',
				'bash' : 'bash',
				'c' : 'c_cpp','C' : 'cpp',
				'cases':'cases',
				'cbl': 'cobol',
				'cob': 'cobol',
				'coffee' : 'coffee',
				'cc' : 'c_cpp','cpp' : 'c_cpp','hxx' : 'c_cpp', 'h' : 'c_cpp',
				'clj':'clojure',
				'cs' : 'csharp',
				'css' : 'css',
				'd' : 'd',
				'erl':'erlang','hrl':'erlang',
				'f' : 'fortran',
				'f77' : 'fortran',
				'go' :'go',
				'hs' : 'haskell',
				'htm' : 'html', 'html' : 'html',
				'hx' : 'haxe',
				'java' : 'java',
				'js' : 'javascript',
				'json' : 'json',
				'scm' : 'scheme',
				's' : 'scheme',
				'm' : 'matlab',
				'lisp': 'lisp','lsp': 'lisp',
				'lua':'lua',
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
				'yaml':'yaml'
			};
			VPL_File.prototype.langType = function(ext) {
				if (ext in this.maplang) {
					return this.maplang[ext];
				}
				return 'text';
			};

			/*
			 * Public methods
			 */

			function sanitizeHTML(t) {
				return $JQVPL('<div>' + t + '</div>').html();
			}
			function sanitizeText(s) {
				return s.replace(/&/g, "&amp;").replace(/</g, "&lt;")
						.replace(/>/g, "&gt;");
			}
			function getFilePosById(id) {
				for (var i = 0; i < files.length; i++)
					if (files[i].getId() == id)
						return i;
				return -1;
			}

			function resultToHTML(text) {
				var regtitgra = /\([-]?[\d]+[\.]?[\d]*\)\s*$/;
				var regtit = /^-.*/;
				var regcas = /^\s*\>/;
				var regWarning = new RegExp('warning|'+escReg(str('warning')),'i');
				var state = '';
				var html = '';
				var comment = ''; // Comment
				var case_ = '';
				var lines = text.split(/\r\n|\n|\r/);
				var regFiles = new Array();
				var lastAnotation = false;
				var lastAnotationFile = false;
				function escReg(t) {
					return t.replace(/[-[\]{}()*+?.,\\^$|#\s]/, "\\$&");
				}
				for (var i = 0; i < files.length; i++) {
					var regf = escReg(files[i].getFileName());
					var reg = "(^|.* |.*/)" + regf + "[:\(](\\d+)[:\,]?(\\d+)?\\)?";// ($|[^\\d])
					regFiles[i] = new RegExp(reg, '');
				}
				function genFileLinks(line,rawline) {
					var used=false;
					for (var i = 0; i < regFiles.length; i++) {
						var reg = regFiles[i];
						var match;
						while ((match = reg.exec(line)) !== null) {
							var anot = files[i].getAnnotations();
							// annotations[]
							// {row:,column:,raw:,type:error,warning,info;text}
							lastAnotationFile = i;
							used =true;
							type = line.search(regWarning) == -1 ? 'error'
									: 'warning';
							lastAnotation = {
									row : (match[2] - 1),
									column : match[3],
									type : type,
									text : rawline
								};
							anot.push(lastAnotation);
							var lt = sanitizeText(files[i].getFileName());
							var cl = 'vpl_l_' + files[i].getId() + '_'
									+ match[2];
							line = line.replace(reg, '$1<a href="#" class="'
									+ cl + '">' + lt + ':$2</a>');
							files[i].setAnnotations(anot);
						}
					}
					if(! used && lastAnotation){
						if(rawline!=''){
							lastAnotation.text +="\n"+rawline;
							files[lastAnotationFile].setAnnotations(files[lastAnotationFile].getAnnotations());
						}
						else
							lastAnotation=false;
					}
					return line;
				}
				function getTitle(line) {
					lastAnotation=false;
					line = line.substr(1);
					var end = regtitgra.exec(line);
					if (end !== null)
						line = line.substr(0, line.length - end[0].length);
					return '<div class="ui-widget-header ui-corner-all">'
							+ sanitizeText(line) + '</div>';
				}
				function getComment() {
					lastAnotation=false;
					var ret = comment;
					comment = '';
					return ret;
				}
				function addComment(rawline) {
					var line = sanitizeText(rawline);
					comment += genFileLinks(line,rawline) + '<br />';
				}
				function addCase(rawline) {
					var line = sanitizeText(rawline);
					case_ += genFileLinks(line,rawline) + "\n";
				}
				function getCase() {
					lastAnotation=false;
					var ret = case_;
					case_ = '';
					return '<pre>' + ret + '</pre>';
				}

				for (i = 0; i < lines.length; i++) {
					var line = lines[i];
					var match = regcas.exec(line);
					var regcasv = regcas.test(line);
					if ((match !== null) != regcasv) {
						console.log('error');
					}
					if (regtit.test(line)) {
						switch (state) {
						case 'comment':
							html += getComment();
							break;
						case 'case':
							html += getCase();
							break;
						}
						html += getTitle(line);
						state = '';
					} else if (regcasv) {
						switch (state) {
						case 'comment':
							html += getComment();
						default:
						case 'case':
							addCase(line.substr(match[0].length));
						}
						state = 'case';
					} else {
						switch (state) {
						case 'case':
							html += getCase();
						default:
						case 'comment':
							addComment(line);
							break;
						}
						state = 'comment';
					}
				}
				switch (state) {
				case 'comment':
					html += getComment();
					break;
				case 'case':
					html += getCase();
					break;
				}
				return html;
			}
			function avoidSelectGrade(event, ui) {
				if ("newHeader" in ui) {
					if (ui.newHeader.hasClass('vpl_ide_grade')) {
						return false;
					}
				}
			}
			this.setResult = function(res,go) {
				var grade = sanitizeText(res.grade);
				var compilation = res.compilation;
				var evaluation = res.evaluation;
				var execution = res.execution;
				for (var i = 0; i < files.length; i++)
					files[i].clearAnnotations();
				if (grade + compilation + evaluation + execution === '') {
					result.hide();
					result.vpl_visible = false;
					result.width(0);
				} else {
					var html = '';
					if (grade > '') {
						html += '<h3 class="vpl_ide_grade">' + grade
								+ '</h3><div></div>';
					}
					if (compilation > '') {
						html += '<h3>' + str('compilation')
								+ '</h3><div class="ui-widget vpl_ide_result_compilation">'
								+ resultToHTML(compilation) + '</div>';
					}
					if (evaluation > '') {
						html += '<h3>' + str('comments')
								+ '</h3><div class="ui-widget">'
								+ resultToHTML(evaluation) + '</div>';						
					}
					if (execution > '') {
						html += '<h3>' + str('execution')
								+'</h3><div class="ui-widget vpl_ide_result_execution">'
								+sanitizeText(execution)+'</div>';
					}
					result.html(html);
					if (!result.vpl_visible) {
						result.vpl_visible = true;
						result.show();
						result.width(tabs.width() / 3);
					}
					result.accordion('destroy').accordion({
						heightStyle : 'fill',
						beforeActivate : avoidSelectGrade
					});
					//console.log(tabs.height());
					if (grade > '')
						result.accordion('option', 'active', 1);
					else
						result.accordion('option', 'active', 0);
					if(go)
						for (var i = 0; i < files.length; i++) {
							var anot = files[i].getAnnotations();
							if (anot.length > 0) {
								tabs.tabs('option', 'active', i);
								files[i].gotoLine(anot[0].row + 1);
								break;
							}
						}
					autoResizeTab();
					autoResizeTab();
				}
			};

			function getFiles() {
				var ret = {};
				for (var i = 0; i < files.length; i++)
					ret[files[i].getFileName()] = files[i].getContent();
				return ret;
			}

			var readOnly = false;

			// Init editor
			var menu = $JQVPL('#vpl_menu');
			var tr = $JQVPL('#vpl_tr');
			var tabs_lu = $JQVPL('#vpl_tabs_lu');
			var tabs = $JQVPL('#vpl_tabs');
			var result = $JQVPL('#vpl_results');
			function menu_option(e) {
				if (isOptionAllowed(e)) {
					return "<li id='vpl_ide_" + e + "'><a href='#'>" + str(e)
							+ "</a></li>";
				} else {
					return '';
				}
			}
			var submenu_separator = "<li class='ui-state-disabled'><a href='#'>________</a></li>";
			var option_separator ="<li class='ui-state-disabled vpl_menu_option_separator'>";
			option_separator += "<a href='#'>|</a></li>";
			var menu_html = '';
			menu_html += "<li id='vpl_ide_file'><a href='#'>" + str('file')
					+ "</a><ul>";
			menu_html += menu_option('new');
			menu_html += menu_option('rename');
			menu_html += menu_option('delete');
			menu_html += menu_option('import');
			// TODO print still not full implemented
			// menu_html += menu_option('print');
			menu_html += '</ul></li>';
			menu_html += "<li><a href='#'>" + str('edit') + "</a><ul>";
			menu_html += menu_option('undo');
			menu_html += menu_option('redo');
			//TODO add message for cut/copy/paste
			/*
			menu_html += submenu_separator;
			menu_html += menu_option('cut');
			menu_html += menu_option('copy');
			menu_html += menu_option('paste');*/
			menu_html += submenu_separator;
			menu_html += menu_option('select_all');
			menu_html += menu_option('find');
			menu_html += menu_option('find_replace');
			menu_html += menu_option('next');
			menu_html += '</ul></li>';
			menu_html += "<li><a href='#'>" + str('options') + "</a><ul>";
			menu_html += menu_option('resetfiles');
			menu_html += menu_option('download');
			// TODO autosave
			// menu_html += menu_option('autosave');
			menu_html += '</ul></li>';
			menu_html += menu_option('fullscreen');
			menu_html += option_separator;
			menu_html += menu_option('save');
			menu_html += menu_option('run');
			menu_html += menu_option('debug');
			menu_html += menu_option('evaluate');
			menu_html += menu_option('console');
			menu_html += option_separator;
			menu_html += menu_option('about');

			menu.html(menu_html);
			function setMenuOptionText(option, text) {
				var a = $JQVPL('#vpl_ide_' + option + ' a');
				if (a.length === 0)
					return;
				a.text(text);
			}

			function setActiveMenuOption(option, active) {
				var e = $JQVPL('#vpl_ide_' + option);
				var a = $JQVPL('#vpl_ide_' + option + ' a');
				if (e.length === 0)
					return;
				if (active) {
					a.attr('disable', 'false');
					e.removeClass('ui-state-disabled');
				} else {
					a.attr('disable', 'disable');
					e.addClass('ui-state-disabled');
				}
			}
			function updateMenu() {
				var id = tabs.tabs('option', 'active');
				if (files.length) {
					tabs.show();
				} else {
					tabs.hide();
				}
				var modified=files.length > 0 && global_modified;
				setActiveMenuOption('save', modified);
				setActiveMenuOption('run', !modified);
				setActiveMenuOption('debug', !modified);
				setActiveMenuOption('evaluate', !modified);
				setActiveMenuOption('download', !modified);
				if (!(id in files)) {
					return;
				}
				setActiveMenuOption('rename', id >= minNumberOfFiles);
				setActiveMenuOption('delete', id >= minNumberOfFiles);
				setActiveMenuOption('new', files.length < maxNumberOfFiles);
				// Not working ace problem
				// setActiveMenuOption('undo',files[id].hasUndo());
				// setActiveMenuOption('redo',files[id].hasRedo());
			}
			var menuActions = new Array();
			var lastAction='';
			menu.menu({
				position : {
					at : "left bottom"
				},
				select : function(event, ui) {
					var actionId = ui.item.attr('id');
					if (typeof actionId != 'undefined') {
						actionId = actionId.replace('vpl_ide_', '');
						//console.log(actionId);
					}
					if (typeof menuActions[actionId] == 'function') {
						lastAction = actionId;
						menuActions[actionId]();
						if (actionId == 'import') {
							event.stopPropagation();
							return false;
						}
					}
				}
			});
			result.accordion({
				heightStyle : 'fill',
				beforeActivate : avoidSelectGrade
			});
			result.width(100);
			result.on('click', 'a', function(event) {
				event.preventDefault();
				var className = event.currentTarget.className;
				var m = /vpl_l_(\d+)_(\d+)/g.exec(className);
				if (m !== null && m[1] in files) {
					var fid = parseInt(m[1], 10);
					var ln = parseInt(m[2], 10);
					var fpos = getFilePosById(fid);
					tabs.tabs('option', 'active', fpos);
					files[fpos].gotoLine(ln);
				}
			});
			result.vpl_visible = false;
			result.hide();
			tabs.tabs();
			tabs.resizable({
				resize : resizeTabs,
				start : function() {
					$JQVPL(window).off('resize', autoResizeTab);
				},
				stop : function() {
					autoResizeTab();
					$JQVPL(window).on('resize', autoResizeTab);
				},
				handles : "e"
			});
			var tabsAir = false;
			function getTabsAir() {
				if (tabsAir === false)
					tabsAir = tabs.outerWidth(true) - tabs.width();
				return tabsAir;
			}
			var resultAir = false;
			function getResultAir() {
				if (resultAir === false)
					resultAir = result.outerWidth(true) - result.width();
				return resultAir;
			}
			function resizeTabs() {
				// console.log('resizeTabs result.width '+result.width());
				var newWidth = menu.outerWidth(true);
				tr.width(newWidth);
				newWidth -= tabs.outerWidth(true) + 2;
				var newHeight = $JQVPL(window).outerHeight();
				newHeight -= tabs.offset().top
						+ (fullScreen ? getTabsAir() : 35);
				tr.height(newHeight);
				if (result.vpl_visible) {
					result.width(newWidth - getResultAir());
					result.height(newHeight);
					result.accordion('refresh');
				}
				currentFile('ajustSize');
			}

			// TODO try to remade based on diff with position of container
			function autoResizeTab() {
				 //console.log('autoResizeTab tabs.width '+tabs.width());
				var newWidth = menu.outerWidth(true);
				if (result.vpl_visible) {
					newWidth -= result.outerWidth(true);
				}
				newWidth -= 2;  //for resize zone
				var newHeight = $JQVPL(window).outerHeight();
				newHeight -= tabs.offset().top
						+ (fullScreen ? getTabsAir() : 35);
				tabs.width(newWidth - getTabsAir());
				tabs.height(newHeight);
				root_obj.height(menu.outerHeight() + tabs.outerHeight());
				resizeTabs();
			}
			setInterval(autoResizeTab, 3500);
			var dialogbase_options = {
				autoOpen : false,
				open : function() {
					currentFile('blur');
					$JQVPL(this).find('input').focus();
				},
				close : function() {
					currentFile('focus');
				},
				minHeight : 10,
				width : 'auto',
				closeText : str('cancel'),
				modal : true,
				dialogClass : 'vpl_ide vpl_ide_dialog'
			};
			function showMessage(message, options) {
				var message_dialog = $JQVPL('<div class="vpl_ide_dialog"></div>');
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
				message_dialog.dialog($JQVPL.extend({}, dialogbase_options, {
					title : options.title,
					buttons : message_buttons,
					close : function() {
						currentFile('focus');
						$JQVPL(this).remove();
					}
				}));
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

			// New file dialog
			var dialog_new = $JQVPL('#vpl_ide_dialog_new');
			function newFileHandler(event) {
				if (!(event.type == 'click' || ((event.type == 'keypress') && event.keyCode == 13))) {
					return;
				}
				dialog_new.dialog('close');
				if (addNewFile($JQVPL('#vpl_ide_input_newfilename').val(), '')) {
					setTimeout(activateGlobalModified, 100);
				}
				return false;
			}

			var dialogButtons = {};
			dialogButtons[str('ok')] = newFileHandler;
			dialogButtons[str('cancel')] = function() {
				$JQVPL(this).dialog('close');
			};
			dialog_new.find('input').on('keypress', newFileHandler);
			dialog_new.dialog($JQVPL.extend({}, dialogbase_options, {
				title : str('create_new_file'),
				buttons : dialogButtons
			}));

			// Rename file dialog
			var dialog_rename = $JQVPL('#vpl_ide_dialog_rename');
			root_obj.append(dialog_rename);
			function renameHandler(event) {
				if (!(event.type == 'click' || ((event.type == 'keypress') && event.keyCode == 13))) {
					return;
				}
				dialog_rename.dialog('close');
				if (renameFile(tabs.tabs('option', 'active'), $JQVPL(
						'#vpl_ide_input_renamefilename').val())) {
					setTimeout(activateGlobalModified, 100);
				}
				event.preventDefault();
			}
			dialog_rename.find('input').on('keypress', renameHandler);
			dialogButtons[str('ok')] = renameHandler;
			dialog_rename.dialog($JQVPL.extend({}, dialogbase_options, {
				open : function() {
					$JQVPL('#vpl_ide_input_renamefilename').val(
							currentFile('getFileName'));
					currentFile('blur');
				},
				title : str('rename_file'),
				buttons : dialogButtons
			}));
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
					minHeight: 20,
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
			var aboutDialog = $JQVPL('#vpl_ide_dialog_about');
			var OKButtons={};
			OKButtons[str('ok')]=function(){
				$JQVPL(this).dialog('close');
			};
			aboutDialog.dialog($JQVPL.extend({}, dialogbase_options, {
				title : str('about'),
				width: 400,
				buttons : OKButtons
			}));
			var terminal = new VPL_Terminal('vpl_dialog_terminal','vpl_terminal',str);
			var VNCClient = new VPL_VNC_Client('vpl_dialog_vnc',str);
			var lastConsole = terminal;
			var file_select = $JQVPL('#vpl_ide_input_file');
			var file_select_handler = function(e) {
				//console.log('drop file_select_handler');
				var filesToRead = this.files;
				// process all File objects
				function readSecuencial(i) {
					if (i >= filesToRead.length)
						return;
					var f = filesToRead[i];
					var reader = new FileReader();
					reader.onload = function(e) {
						//console.log('file: ' + JSON.stringify(f));
						if (addNewFile(f.name, e.target.result, true)) {
							setTimeout(activateGlobalModified, 1000);
						}
						// Load next file
						readSecuencial(i + 1);
					};
					reader.readAsText(f);
				}
				readSecuencial(0);
			};
			file_select.on('change', file_select_handler);
			// Set menu acctions
			menuActions['new'] = function() {
				dialog_new.dialog('open');
			};
			menuActions['rename'] = function() {
				dialog_rename.dialog('open');
			};
			menuActions['delete'] = function() {
				var filename = currentFile('getFileName');
				var message = str('delete_file_fq').replace(/\{\$a\}/g,
						filename);
				showMessage(message, {
					ok : function() {
						if (deleteFile(filename)) {
							setTimeout(activateGlobalModified, 100);
						}
					},
					title : str('delete_file_q'),
					icon : 'trash'
				});
			};
			menuActions['import'] = function(e) {
				file_select.trigger('click');
			};
			menuActions['print'] = function() {
				window.print();
			};
			menuActions['undo'] = function() {
				currentFile('undo');
			};
			menuActions['redo'] = function() {
				currentFile('redo');
			};
			menuActions['select_all'] = function() {
				currentFile('selectAll');
			};
			menuActions['find'] = function() {
				currentFile('find');
			};
			menuActions['find_replace'] = function() {
				currentFile('replace');
			};
			menuActions['next'] = function() {
				currentFile('next');
			};
			menuActions['fullscreen'] = function() {
				if (fullScreen) {
					root_obj.removeClass('vpl_ide_root_fullscreen');
					$JQVPL('body').removeClass('vpl_body_fullscreen');
					setMenuOptionText('fullscreen', str('fullscreen'));
					$JQVPL('header, footer, aside, #page-header, fdiv.navbar, div.tabtree, #dock').show();
					fullScreen = false;
				} else {
					$JQVPL('body').addClass('vpl_body_fullscreen').scrollTop(0);
					$JQVPL('header, footer, aside,#page-header, div.navbar, div.tabtree, #dock').hide();
					root_obj.addClass('vpl_ide_root_fullscreen');
					setMenuOptionText('fullscreen', str('regularscreen'));
					fullScreen = true;
				}
				autoResizeTab();
			};
			menuActions['about'] = function() {
			};
			menuActions['download'] = function() {
				window.location = options['download'];
			};
			function requestAction(action, title, data, ok) {
				//console.log('Open request '+action);
				var request;
				if(title=='')
					title='connecting';
				var pb = new progressBar(str(action),str(title),
					function(){
						//console.log('Close request '+action);
						if(request.readyState != 4)
							request.abort();
				});
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
			function resetFiles() {
				requestAction('resetfiles', '',{},
						function(response) {
							var files = response.files;
							for ( var filename in files) {
								addNewFile(filename, files[filename], true);
							}
							updateMenu();
						});
			}
			menuActions['resetfiles'] = function() {
				showMessage(str('sureresetfiles'), {
					title : str('resetfiles'),
					ok : resetFiles
				});
			};
			menuActions['save'] = function() {
				requestAction('save', 'saving', getFiles(), function() {
					global_modified = false;
					for (var i = 0; i < files.length; i++)
						files[i].resetModified();
					updateMenu();
				});
			};
			function supportWebSocket() {
				if ("WebSocket" in window)
					return true;
				showErrorMessage(str('browserupdate'));
				return false;
			}
			function webSocketMonitor(response, title,running) {
				var ws;
				try{
				   ws= new WebSocket(response.monitorURL);
				}catch(e){
					showErrorMessage(e.message);
					return;
				}
				var pb = new progressBar(str(title),str('connecting'),
					function(){ws.close();}
				);
				//console.log('Open ws '+response.monitorURL);
				ws.notOpen = true;
				ws.onopen = function(event) {
					ws.notOpen = false;
					pb.setLabel(str('connected'));
				};
				ws.onerror = function(event) {
					pb.close();
					if(response.monitorURL.search('wss:') == 0 && ws.notOpen){
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
											ok: function(){
												if(lastAction!=''){
													menuActions[lastAction]();
													lastAction='';
												}
												},
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
													,'toolbar=no, location=no, directories=no, status=no, menubar=no, resizable=yes, scrollbars=yes, copyhistory=no, width='+w+', height='+h+', top='+top+', left='+left);
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
					//console.log('Close ws '+response.monitorURL);
					if (typeof lastConsole != 'undefined')
						lastConsole.disconnect();
					pb.close();
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
							var text = str(state);
							if(detail >'')
								text += ': '+detail;
							if(lastConsole.isOpen())
								lastConsole.setMessage(text);
							else
								pb.setLabel(text);
							break;
						case 'compilation':
							self.setResult({
								grade : '',
								compilation : content,
								evaluation : '',
								execution : ''
							},false);
							break;
						case 'retrieve':
							pb.close();
							requestAction('retrieve', '', '',
									function(response) {
										self.setResult(response,true);
									});
							break;
						case 'run':
							pb.close();
							if (content == 'terminal') {
								lastConsole = terminal;
								terminal.connect(response.executionURL,
										function() {
											ws.close();
										});
							} else {
								lastConsole = VNCClient;
								VNCClient.connect(response.VNCsecure,response.VNChost,
										response.port, response.VNCpassword,
										response.VNCpath);
							}
							break;
						case 'close':
							ws.close();
							break;
						}
					}else{
						pb.setLabel(str('error')+': '+event.data);
					}
				};
			}
			menuActions['run'] = function() {
				if (!terminal.isConnected() && supportWebSocket())
					requestAction('run', '', {}, function(response) {
						webSocketMonitor(response, 'run','running');
					});
			};
			menuActions['debug'] = function() {
				if (!terminal.isConnected() && supportWebSocket())
					requestAction('debug', '', {}, function(response) {
						webSocketMonitor(response, 'debug','debugging');
					});
			};
			menuActions['evaluate'] = function() {
				if (!terminal.isConnected() && supportWebSocket())
					requestAction('evaluate', '', {}, function(response) {
						webSocketMonitor(response, 'evaluate','evaluating');
					});
			};
			menuActions['console'] = function() {
				lastConsole.show();
			};
			menuActions['about'] = function() {
				aboutDialog.dialog('open');
			};
			tabs.on("tabsactivate", function(event, ui) {
				var tabindex = tabs.tabs('option', 'active');
				newfile = files[tabindex];
				updateMenu();
				newfile.focus();
				autoResizeTab();
			});

			// VPL_IDE resize view control
			$JQVPL(window).on('resize', autoResizeTab);
			if(!options['example']){
				$JQVPL(window).on('beforeunload', function() {
					if (global_modified) {
						return str('changesNotSaved');
					}
				});
			}
			$JQVPL(window).resize(autoResizeTab);
			var initFiles = options.files;
			for (var i = 0; i < initFiles.length; i++) {
				var file = initFiles[i];
				var r = addNewFile(file.name, file.data);
				if (r)
					r.resetModified();
			}
			tabs.tabs('option', 'active', 0);
			autoResizeTab();
			if(files.length == 0 && maxNumberOfFiles > 0){
				menuActions['new']();
			}else if(typeof options['saved'] != 'undefined' && !options['saved']){
				activateGlobalModified();
			}

		};
	}
})();
