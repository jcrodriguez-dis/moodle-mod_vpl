/**
 * @package VPL. IDE control
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
(function() {
	function doNothing(){};
	VPL_File = function(id,name, value,file_manager) {
		//General functions
		var tid = "#vpl_file" + id;
		var tabnameid = "#vpl_tab_name" + id;
		var fileName = name;
		var modified = true;
		var opened = false;
		var self = this;
		this.getId = function() {
			return id;
		};
		this.getTabNameId = function() {
			return tabnameid;
		};
		this.getTId = function() {
			return tid;
		};
		this.getFileName = function() {
			return fileName;
		};
		this.resetModified = function() {
			modified = false;
			this.setFileName(fileName);
		};
		this.getTabPos = function() {
			return file_manager.getTabPos(this);
		};
		this.isOpen = function() {
			return opened;
		};
		this.isModified = function() {
			return modified;
		};
		this.setFileName = function(name) {
			if (!VPL_Util.validPath(name))
				return false;
			if (name != fileName) {
				fileName = name;
				this.changed();
			}
			if(!opened){
				return true;
			}
			var fn = VPL_Util.getFileName(name);
			if (fn.length > 20) {
				fn = fn.substring(0,16)+'...';
			}
			var html = (modified ? VPL_Util.iconModified() : '') + fn;
			if (this.getTabPos() < file_manager.minNumberOfFiles) {
				html = html + VPL_Util.iconRequired();
			} else {
				html = html + VPL_Util.iconClose();
			}
			$JQVPL(tabnameid + ' a').html(html);
			if (fn != name)
				$JQVPL(tabnameid + ' a').attr('title', name);
			if(modified){
				file_manager.adjustTabsTitles(false);
			}
			this.langSelection();
			return true;
		};
		this.getContent = function() {
			return value;
		};
		this.setContent = function(c) {
			value=c;
		};

		this.destroy = function() {
			$JQVPL(tabnameid).remove();
			$JQVPL(tid).remove();
		};

		this.adjustSize = function(){
			if(!opened) return false;
			var editTag = $JQVPL(tid);
			var tabs=editTag.parent();
			if (editTag.length === 0)
				return;
			var editorHeight = editTag.height();
			var editorWidth = editTag.width();
			var newHeight = tabs.height();
			newHeight -= editTag.position().top + 8;
			var newWidth = tabs.width();
			newWidth -= 2 * file_manager.scrollBarWidth;
			newHeight -= file_manager.scrollBarWidth;
			if (newHeight != editorHeight || newWidth != editorWidth) {
				$JQVPL(editTag).height(newHeight);
				$JQVPL(editTag).width(newWidth);
				return true;
			}
			return false;
		};
		this.gotoLine = doNothing;
		this.setReadOnly = doNothing;
		this.focus = doNothing;
		this.blur = doNothing;
		this.undo = doNothing;
		this.redo = doNothing;
		this.selectAll = doNothing;
		this.hasUndo = function() {return false;};
		this.hasRedo = function() {return false;};
		this.find = doNothing;
		this.replace = doNothing;
		this.next = doNothing;
		this.getAnnotations = function() {return [];};
		this.setAnnotations = doNothing;
		this.clearAnnotations = doNothing;
		this.langSelection = doNothing;
        this.extendToCodeEditor= function(){
			var editor = null;
			var session = null;
			this.getContent = function() {
				if(!opened)	return value;
				return editor.getValue();
			};
			this.setContent = function(c) {
				if(!opened){
					value=c;
				}else{
					editor.setValue(c);
				}
			};
			this.oldDestroy=this.destroy;
			this.destroy = function() {
				if(opened) editor.destroy();
				this.oldDestroy();
			};
			this.oldAdjustSize=this.adjustSize;
			this.adjustSize = function() {
				if(this.oldAdjustSize()){
					editor.resize(true);
					return true;
				}
				return false;
			};
			this.gotoLine = function(line) {
				if(!opened) return;
				editor.gotoLine(line, 0);
				editor.scrollToLine(line, true);
				editor.focus();
			};
			this.setReadOnly = function(s) {
				if(opened){
					editor.setReadOnly(s);
				}
			};
			this.focus = function() {
				if(!opened)	return;
				this.adjustSize();
				editor.focus();
			};
			this.blur = function() {
				if(!opened)	return;
				editor.blur();
			};
			this.undo = function() {
				if(!opened)	return;
				editor.undo();
			};
			this.redo = function() {
				if(!opened)	return;
				editor.redo();
			};
			this.selectAll = function() {
				if(!opened)	return;
				editor.selectAll();
			};
			this.hasUndo = function() {
				if(!opened)	return false;
				return session.getUndoManager().hasUndo();
			};
			this.hasRedo = function() {
				if(!opened)	return false;
				return session.getUndoManager().hasRedo();
			};
			this.find = function(s) {
				if(!opened)	return;
				editor.execCommand('find');
			};
			this.replace = function(s) {
				if(!opened)	return;
				editor.execCommand('replace');
			};
			this.next = function(s) {
				if(!opened)	return;
				editor.execCommand('findnext');
			};
			this.getAnnotations = function() {
				if(!opened)	return [];
				return session.getAnnotations();
			};
			this.setAnnotations = function(a) {
				if(!opened)	return;
				return session.setAnnotations(a);
			};
			this.clearAnnotations = function() {
				if(!opened)	return;
				return session.clearAnnotations();
			};
			this.langSelection = function() {
				if(!opened)	return;
				var ext = VPL_Util.fileExtension(fileName);
				var lang = 'txt';
				if (ext != '') {
					lang = VPL_Util.langType(ext);
				}
				session.setMode("ace/mode/" + lang);
			};
			this.open = function(){
				if(opened)	return;
				ace.require("ext/language_tools");
				opened=true;
				editor = ace.edit("vpl_file" + id);
				session = editor.getSession();
				editor.setOptions({enableBasicAutocompletion: true,
					enableSnippets: true,});
				editor.setTheme("ace/theme/chrome");
				this.setFileName(fileName);
				editor.setValue(value);
				editor.gotoLine(0, 0);
				editor.setReadOnly(file_manager.readOnly);
				session.setUseSoftTabs(true);
				session.setTabSize(4);
				// Avoid undo of editor initial content

				session.setUndoManager(new ace.UndoManager());
				// =================================================
				// Code to control Paste and drop under restricted editing
				editor.execCommand('replace');
				function addEventDrop() {
					var tag=$JQVPL(tid + ' div.ace_search');
					if (tag.length) {
						tag.on('drop', file_manager.dropHandler);
						var button=$JQVPL('button.ace_searchbtn_close');
						button.css({ marginLeft : "1em", marginRight : "1em"});
						button.trigger('click');
					} else {
						setTimeout(addEventDrop, 50);
					}
				}
				function changed() {
					if (!modified) {
						modified = true;
						self.setFileName(fileName);
						file_manager.generateFileList();
					}
					VPL_Util.longDelay(file_manager.setModified);
				}
				editor.on('change', changed);
				// Try to grant dropHandler installation
				setTimeout(addEventDrop, 5);
				// Save previous onPaste and change for a new one
				var prevOnPaste = editor.onPaste;
				editor.onPaste = function(s) {
					if (file_manager.restrictedEdit) {
						editor.insert(file_manager.getClipboard());
					} else {
						prevOnPaste.call(editor, s);
					}
				};
				//Control copy and cut (yes cut also use this) for localClipboard
				editor.on('copy', function(t) {
					file_manager.setClipboard(t);
				});
				$JQVPL(tid).on('paste', '*', file_manager.restrictedPaste);
				$JQVPL(tid + ' div.ace_content').on('drop', file_manager.dropHandler);
				$JQVPL(tid + ' div.ace_content').on('dragover', file_manager.dragoverHandler);
				// size adjust
				this.adjustSize();
			};
			this.close= function(){
				opened=false;
				if(editor!==null){
					return;
				}
				editor.destroy();
				editor=null;
				session = null;
			};
        };

        this.extendToBinary= function(){
			this.setContent = function(c) {
				modified = true;
				value= c;
				this.setFileName(fileName);
				this.updateDataURL();
			};                
			this.updateDataURL = function(){
				var blob = new Blob([value],{type:VPL_Util.getMIME(fileName)});
				var fr=new FileReader();
				fr.onload= function(e){
					try{
						$JQVPL(tid).find('img').attr('src',e.target.result);
					}
					catch(e){
						$JQVPL(tid).find('img').attr('src','');
					}
				};
				fr.readAsDataURL(blob);
			};
			this.adjustSize = function(){
				if(!opened) return false;
				var editTag = $JQVPL(tid);
				if (editTag.length === 0)
					return;
				var tabs=editTag.parent();
				var newHeight = tabs.height();
				newHeight -= editTag.position().top + 8;
				newHeight -= file_manager.scrollBarWidth;
				if (newHeight != editTag.height()) {
					editTag.height(newHeight);
					return true;
				}
				return false;
			};
			this.open= function(){
				opened=true;
				if(VPL_Util.isImage(fileName)){
					$JQVPL(tid).addClass('vpl_ide_img').append('<img />');
					this.updateDataURL();
				}else{
					$JQVPL(tid).addClass('vpl_ide_binary').text(str('binaryfile'));
				}    					
				this.setFileName(fileName);
			};
			this.close=function(){
				opened=false;
			};
        };
	};
})();
