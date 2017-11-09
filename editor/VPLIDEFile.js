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
 * File managament
 *
 * @package mod_vpl
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/* globals VPL_File: true */
/* globals VPL_Util */
/* globals $JQVPL */
/* globals ace */

(function() {
    VPL_File = function(id, name, value, file_manager) {
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
        this.change = function() {
            if (!modified) {
                modified = true;
                file_manager.generateFileList();
                this.setFileName(fileName); // TODO update state of filename.
            }
            VPL_Util.longDelay(file_manager.setModified);
        };
        this.setFileName = function(name) {
            if (!VPL_Util.validPath(name)) {
                return false;
            }
            if (name != fileName) {
                fileName = name;
                self.change();
            }
            if (!opened) {
                return true;
            }
            var fn = VPL_Util.getFileName(name);
            if (fn.length > 20) {
                fn = fn.substring(0, 16) + '...';
            }
            var html = (modified ? VPL_Util.iconModified() : '') + fn;
            if (this.getTabPos() < file_manager.minNumberOfFiles) {
                html = html + VPL_Util.iconRequired();
            } else {
                html = html + VPL_Util.iconClose();
            }
            $JQVPL(tabnameid + ' a').html(html);
            if (fn != name) {
                $JQVPL(tabnameid + ' a').attr('title', name);
            }
            if (modified) {
                file_manager.adjustTabsTitles(false);
            }
            this.langSelection();
            return true;
        };
        this.getContent = function() {
            return value;
        };
        this.setContent = function(c) {
            value = c;
        };

        this.destroy = function() {
            $JQVPL(tabnameid).remove();
            $JQVPL(tid).remove();
        };

        this.adjustSize = function() {
            if (!opened) {
                return false;
            }
            var editTag = $JQVPL(tid);
            var tabs = editTag.parent();
            if (editTag.length === 0) {
                return;
            }
            var editorHeight = editTag.height();
            var editorWidth = editTag.width();
            var newHeight = tabs.height();
            newHeight -= editTag.position().top;
            var newWidth = tabs.width();
            if (newHeight != editorHeight || newWidth != editorWidth) {
                $JQVPL(editTag).height(newHeight);
                $JQVPL(editTag).width(newWidth);
                return true;
            }
            return false;
        };
        this.gotoLine = VPL_Util.doNothing;
        this.setReadOnly = VPL_Util.doNothing;
        this.focus = VPL_Util.doNothing;
        this.blur = VPL_Util.doNothing;
        this.undo = VPL_Util.doNothing;
        this.redo = VPL_Util.doNothing;
        this.selectAll = VPL_Util.doNothing;
        this.hasUndo = function() {
            return false;
        };
        this.hasRedo = function() {
            return false;
        };
        this.find = VPL_Util.doNothing;
        this.replace = VPL_Util.doNothing;
        this.next = VPL_Util.doNothing;
        this.getAnnotations = function() {
            return [];
        };
        this.setAnnotations = VPL_Util.doNothing;
        this.setFontSize = VPL_Util.doNothing;
        this.clearAnnotations = VPL_Util.doNothing;
        this.langSelection = VPL_Util.doNothing;
        this.isBinary = function() {
            return false;
        };
        this.extendToCodeEditor = function() {
            var editor = null;
            var session = null;
            this.getContent = function() {
                if (!opened) {
                    return value;
                }
                return editor.getValue();
            };
            this.setContent = function(c) {
                value = c;
                if (opened) {
                    editor.setValue(c);
                }
            };
            this.oldDestroy = this.destroy;
            this.destroy = function() {
                if (opened) {
                    editor.destroy();
                }
                this.oldDestroy();
            };
            this.setFontSize = function(size) {
                if (opened) {
                    editor.setFontSize(size);
                }
            };
            this.oldAdjustSize = this.adjustSize;
            this.adjustSize = function() {
                if (this.oldAdjustSize()) {
                    editor.resize(true);
                    return true;
                }
                return false;
            };
            this.gotoLine = function(line) {
                if (!opened) {
                    return;
                }
                editor.gotoLine(line, 0);
                editor.scrollToLine(line, true);
                editor.focus();
            };
            this.setReadOnly = function(s) {
                if (opened) {
                    editor.setReadOnly(s);
                }
            };
            this.focus = function() {
                if (!opened) {
                    return;
                }
                // Workaround to remove jquery-ui theme background color.
                $JQVPL(tid).removeClass('ui-tabs-panel');
                this.adjustSize();
                editor.focus();
            };
            this.blur = function() {
                if (!opened) {
                    return;
                }
                editor.blur();
            };
            this.undo = function() {
                if (!opened) {
                    return;
                }
                editor.undo();
                editor.focus();
            };
            this.redo = function() {
                if (!opened) {
                    return;
                }
                editor.redo();
                editor.focus();
            };
            this.selectAll = function() {
                if (!opened) {
                    return;
                }
                editor.selectAll();
                editor.focus();
            };
            this.hasUndo = function() {
                if (!opened) {
                    return false;
                }
                return session.getUndoManager().hasUndo();
            };
            this.hasRedo = function() {
                if (!opened) {
                    return false;
                }
                return session.getUndoManager().hasRedo();
            };
            this.find = function() {
                if (!opened) {
                    return;
                }
                editor.execCommand('find');
            };
            this.replace = function() {
                if (!opened) {
                    return;
                }
                editor.execCommand('replace');
            };
            this.next = function() {
                if (!opened) {
                    return;
                }
                editor.execCommand('findnext');
            };
            this.getAnnotations = function() {
                if (!opened) {
                    return [];
                }
                return session.getAnnotations();
            };
            this.setAnnotations = function(a) {
                if (!opened) {
                    return;
                }
                return session.setAnnotations(a);
            };
            this.clearAnnotations = function() {
                if (!opened) {
                    return;
                }
                return session.clearAnnotations();
            };
            this.langSelection = function() {
                if (!opened) {
                    return;
                }
                var ext = VPL_Util.fileExtension(fileName);
                var lang = 'text';
                if (ext !== '') {
                    lang = VPL_Util.langType(ext);
                }
                session.setMode("ace/mode/" + lang);
            };
            this.getEditor = function() {
                if (!opened) {
                    return false;
                }
                return editor;
            };
            this.open = function() {
                if (opened) {
                    return false;
                }
                ace.require("ext/language_tools");
                opened = true;
                editor = ace.edit("vpl_file" + id);
                session = editor.getSession();
                editor.setOptions({
                    enableBasicAutocompletion : true,
                    enableSnippets : true,
                });
                editor.setFontSize(file_manager.getFontSize());
                editor.setTheme("ace/theme/" + file_manager.getTheme());
                this.setFileName(fileName);
                editor.setValue(value);
                editor.gotoLine(0, 0);
                editor.setReadOnly(file_manager.readOnly);
                session.setUseSoftTabs(true);
                session.setTabSize(4);
                // Avoid undo of editor initial content.

                session.setUndoManager(new ace.UndoManager());
                // Code to control Paste and drop under restricted editing.
                editor.execCommand('replace');
                function addEventDrop() {
                    var tag = $JQVPL(tid + ' div.ace_search');
                    if (tag.length) {
                        tag.on('drop', file_manager.dropHandler);
                        var button = $JQVPL('button.ace_searchbtn_close');
                        button.css({
                            marginLeft : "1em",
                            marginRight : "1em"
                        });
                        button.trigger('click');
                    } else {
                        setTimeout(addEventDrop, 50);
                    }
                }
                editor.on('change', function() {
                    self.change();
                });
                // Try to grant dropHandler installation.
                setTimeout(addEventDrop, 5);
                // Save previous onPaste and change for a new one.
                var prevOnPaste = editor.onPaste;
                editor.onPaste = function(s) {
                    if (file_manager.restrictedEdit) {
                        editor.insert(file_manager.getClipboard());
                    } else {
                        prevOnPaste.call(editor, s);
                    }
                };
                // Control copy and cut (yes cut also use this) for localClipboard.
                editor.on('copy', function(t) {
                    file_manager.setClipboard(t);
                });
                $JQVPL(tid).on('paste', '*', file_manager.restrictedPaste);
                $JQVPL(tid + ' div.ace_content').on('drop', file_manager.dropHandler);
                $JQVPL(tid + ' div.ace_content').on('dragover', file_manager.dragoverHandler);
                this.adjustSize();
                return editor;
            };
            this.close = function() {
                opened = false;
                if (editor === null) {
                    return;
                }
                value = editor.getValue();
                editor.destroy();
                editor = null;
                session = null;
            };
        };

        this.extendToBinary = function() {
            this.isBinary = function() {
                return true;
            };
            this.setContent = function(c) {
                modified = true;
                value = c;
                this.setFileName(fileName);
                this.updateDataURL();
            };
            this.updateDataURL = function() {
                if(VPL_Util.isImage(fileName)){
                    var prevalue = 'data:' + VPL_Util.getMIME(fileName) + ';base64,';
                    $JQVPL(tid).find('img').attr('src', prevalue + value);
                } else {
                    $JQVPL(tid).find('img').attr('src', '');
                }
            };
            this.adjustSize = function() {
                if (!opened) {
                    return false;
                }
                var editTag = $JQVPL(tid);
                if (editTag.length === 0) {
                    return;
                }
                var tabs = editTag.parent();
                var newHeight = tabs.height();
                newHeight -= editTag.position().top;
                if (newHeight != editTag.height()) {
                    editTag.height(newHeight);
                    return true;
                }
                return false;
            };
            this.open = function() {
                opened = true;
                if (VPL_Util.isImage(fileName)) {
                    $JQVPL(tid).addClass('vpl_ide_img').append('<img />');
                    this.updateDataURL();
                } else {
                    $JQVPL(tid).addClass('vpl_ide_binary').text(VPL_Util.str('binaryfile'));
                }
                this.setFileName(fileName);
                return false;
            };
            this.close = function() {
                opened = false;
            };
        };
    };
})();
