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
 * IDE Control
 * @package mod_vpl
 * @copyright 2017 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/* globals VPL_IDE: true */
/* globals VPL_Util */
/* globals $JQVPL */
/* globals VPL_File */
/* globals VPL_IDEButtons */
/* globals VPL_Terminal */
/* globals VPL_VNC_Client */

(function() {
    if (!window.VPL_IDE) {
        // Editor constructor (only one at this moment).
        VPL_IDE = function(root_id, options) {
            var self = this;
            var file_manager;
            var adjustTabsTitles;
            var autoResizeTab;
            var showErrorMessage;
            var updateMenu;
            var minNumberOfFiles = options.minfiles || 0;
            var maxNumberOfFiles = options.maxfiles || 0;
            var restrictedEdit = options.restrictededitor || options.example;
            var readOnly = options.example;
            var fullScreen = false;
            var scrollBarWidth = VPL_Util.scrollBarWidth();
            VPL_Util.set_str(options.i18n);
            var str = VPL_Util.str;
            var root_obj = $JQVPL('#' + root_id);
            if (typeof root_obj != 'object') {
                throw "VPL: constructor tag_id not found";
            }
            var optionsToCheck = {
                'new' : true,
                'rename' : true,
                'delete' : true,
                'save' : true,
                'run' : true,
                'edit' : true,
                'debug' : true,
                'evaluate' : true,
                'import' : true,
                'resetfiles' : true,
                'sort' : true,
                'multidelete' : true,
                'console' : true,
                'comments' : true
            };
            if ( (typeof options.loadajaxurl) == 'undefined' ) {
                options.loadajaxurl = options.ajaxurl;
            }
            (function() {
                var activateModification = (minNumberOfFiles < maxNumberOfFiles);
                options.new = activateModification;
                options.rename = activateModification;
                options.delete = activateModification;
                options.comments = options.comments && ! options.example;
            })();
            options.sort = (maxNumberOfFiles - minNumberOfFiles >= 2);
            options.multidelete = options.sort;
            options.import = !restrictedEdit;
            function isOptionAllowed(op) {
                if (!optionsToCheck[op]) {
                    return true;
                }
                return options[op];
            }
            options.console = isOptionAllowed('run') || isOptionAllowed('debug');
            if ( (typeof options.fontSize) == 'undefined' ) {
                options.fontSize = 12;
            }
            options.fontSize = parseInt(options.fontSize);
            function dragoverHandler(e) {
                if (restrictedEdit) {
                    e.originalEvent.dataTransfer.dropEffect = 'none';
                } else {
                    e.originalEvent.dataTransfer.dropEffect = 'copy';
                }
                e.preventDefault();
            }

            function dropHandler(e) {
                if (restrictedEdit) { // No drop allowed.
                    e.stopImmediatePropagation();
                    return false;
                }
                var dt = e.originalEvent.dataTransfer;
                // Drop files.
                if (dt.files.length > 0) {
                    VPL_Util.readSelectedFiles(dt.files, function(file) {
                        return file_manager.addFile(file, true, updateMenu, showErrorMessage);
                    },
                    function(){
                        file_manager.fileListVisibleIfNeeded();
                    });
                    e.stopImmediatePropagation();
                    return false;
                }
            }
            root_obj.on('drop', dropHandler);
            root_obj.on('dragover', dragoverHandler);

            // Control paste.
            function restrictedPaste(e) {
                if (restrictedEdit) {
                    e.stopPropagation();
                    return false;
                }
            }
            // Init editor vars.

            var menu = $JQVPL('#vpl_menu');
            var menuButtons = new VPL_IDEButtons(menu,isOptionAllowed);
            var tr = $JQVPL('#vpl_tr');
            var file_list_container = $JQVPL('#vpl_filelist');
            var file_list = $JQVPL('#vpl_filelist_header');
            var file_list_content = $JQVPL('#vpl_filelist_content');
            var tabs_ul = $JQVPL('#vpl_tabs_ul');
            var tabs = $JQVPL('#vpl_tabs');
            var result_container = $JQVPL('#vpl_results');
            var result = $JQVPL('#vpl_results_accordion');
            file_list_container.vpl_minWidth = 80;
            result_container.vpl_minWidth = 100;

            function avoidSelectGrade(event, ui) {
                if ("newHeader" in ui) {
                    if (ui.newHeader.hasClass('vpl_ide_accordion_t_grade')) {
                        return false;
                    }
                }
            }
            function File_manager() {
                var tabs_ul = $JQVPL('#vpl_tabs_ul');
                var tabs = $JQVPL('#vpl_tabs').tabs("widget");
                var files = [];
                var openFiles = [];
                var modified = true;
                var self = this;
                this.updateFileList = function() {
                    self.generateFileList();
                };
                function fileNameExists(name) {
                    var checkName = name.toLowerCase();
                    for (var i = 0; i < files.length; i++) {
                        if (files[i].getFileName().toLowerCase() == checkName) {
                            return i;
                        }
                    }
                    return -1;
                }
                function fileNameIncluded(name) {
                    var checkName = name.toLowerCase() + '/';
                    for (var i = 0; i < files.length; i++) {
                        var nameMod = files[i].getFileName().toLowerCase() + '/';
                        // Check for name as directory existent.
                        if (nameMod.indexOf(checkName) === 0 || checkName.indexOf(nameMod) === 0) {
                            return true;
                        }
                    }
                    return false;
                }

                this.restrictedPaste = restrictedPaste;
                this.dropHandler = dropHandler;
                this.dragoverHandler = dragoverHandler;
                this.readOnly = readOnly;
                this.restrictedEdit = restrictedEdit;
                this.adjustTabsTitles = adjustTabsTitles;
                this.minNumberOfFiles = minNumberOfFiles;
                this.scrollBarWidth = scrollBarWidth;
                var localClipboard = "";
                this.setClipboard = function(t) {
                    localClipboard = t;
                };
                this.getClipboard = function() {
                    return localClipboard;
                };
                this.getTabPos = function(file) {
                    for (var i = 0; i < openFiles.length; i++) {
                        if (openFiles[i] == file){
                            return i;
                        }
                    }
                    return openFiles.length;
                };
                this.getTheme = function(){
                    return options.theme;
                };
                this.addTab = function(fid) {
                    var hlink = '<a href="#vpl_file' + fid + '"></a>';
                    tabs_ul.append('<li id="vpl_tab_name' + fid + '">' + hlink + '</li>');
                    tabs.append('<div id="vpl_file' + fid + '" class="vpl_ide_file"></div>');
                };
                this.removeTab = function(fid) {
                    tabs_ul.find('#vpl_tab_name' + fid).remove();
                    tabs.find('#vpl_file' + fid).remove();
                };
                this.open = function(pos) {
                    var file;
                    if (typeof pos == 'object') {
                        file = pos;
                    } else {
                        file = files[pos];
                    }
                    if (file.isOpen()){
                        return;
                    }
                    var fid = file.getId();
                    self.addTab(fid);
                    openFiles.push(file);
                    menuButtons.setGetkeys(file.open());
                    tabs.tabs('refresh');
                    adjustTabsTitles(false);
                    VPL_Util.delay(updateMenu);
                    VPL_Util.delay(self.updateFileList);
                };
                this.close = function(file) {
                    if (!file.isOpen()) {
                        return;
                    }
                    var pos;
                    var fid = file.getId();
                    file.close();
                    self.removeTab(fid);
                    var ptab = self.getTabPos(file);
                    openFiles.splice(ptab, 1);
                    tabs.tabs('refresh');
                    adjustTabsTitles(false);
                    self.fileListVisible(true);
                    VPL_Util.delay(self.updateFileList);
                    VPL_Util.delay(adjustTabsTitles, false);
                    if (openFiles.length > ptab) {
                        pos = self.getFilePosById(openFiles[ptab].getId());
                        self.gotoFile(pos, 'c');
                        return;
                    }
                    if (ptab > 0) {
                        pos = self.getFilePosById(openFiles[ptab - 1].getId());
                        self.gotoFile(pos, 'c');
                        return;
                    }
                };
                this.isClosed = function(pos) {
                    return !files[pos].isOpen();
                };
                this.fileListVisible = function(b) {
                    if (b === file_list_container.vpl_visible){
                        return;
                    }
                    file_list_container.vpl_visible = b;
                    if (b) {
                        file_list_container.show();
                        autoResizeTab();
                    } else {
                        file_list_container.hide();
                        autoResizeTab();
                    }
                };
                this.isFileListVisible = function() {
                    return file_list_container.vpl_visible;
                };
                this.fileListVisibleIfNeeded = function() {
                    if ( this.isFileListVisible() ){
                        return;
                    }
                    for (var i = 0; i < files.length; i++) {
                        if (!files[i].isOpen()) {
                            this.fileListVisible(true);
                            return;
                        }
                    }
                };
                this.setFontSize = function(size) {
                    options.fontSize = size;
                    for (var i = 0; i < files.length; i++) {
                        files[i].setFontSize(size);
                    }
                };
                this.getFontSize = function() {
                    return options.fontSize;
                };
                this.addFile = function(file, replace, ok, showError) {
                    if ((typeof file.name != 'string') || !VPL_Util.validPath(file.name)) {
                        showError(str('incorrect_file_name') + ' (' + file.name + ')');
                        return false;
                    }
                    if (replace !== true) {
                        replace = false;
                    }
                    var pos = fileNameExists(file.name);
                    if (pos != -1) {
                        if (replace) {
                            files[pos].setContent(file.contents);
                            self.setModified();
                            ok();
                            VPL_Util.delay(self.updateFileList);
                            return file;
                        } else {
                            showError(str('filenotadded').replace(/\{\$a\}/g, file.name));
                            return false;
                        }
                    }
                    if (fileNameIncluded(file.name)) {
                        showError(str('filenotadded').replace(/\{\$a\}/g, file.name));
                        return false;
                    }
                    if (files.length >= maxNumberOfFiles) {
                        showError(str('maxfilesexceeded') + ' (' + maxNumberOfFiles + ')');
                        return false;
                    }
                    var fid = VPL_Util.getUniqueId();
                    var newfile = new VPL_File(fid, file.name, file.contents, this);
                    if (file.encoding == 1) {
                        newfile.extendToBinary();
                    } else {
                        newfile.extendToCodeEditor();
                    }
                    files.push(newfile);
                    self.setModified();
                    if (files.length > 5) {
                        self.fileListVisible(true);
                    }
                    ok();
                    VPL_Util.delay(self.updateFileList);
                    return newfile;
                };
                this.renameFile = function(oldname, newname, showError) {
                    var pos = fileNameExists(oldname);
                    try {
                        if (pos == -1){
                            throw "";
                        }
                        if (pos < minNumberOfFiles){
                            throw "";
                        }
                        if (files[pos].getFileName() == newname){
                            return true; // Equals name file.
                        }
                        if (!VPL_Util.validPath(newname) || fileNameIncluded(newname)) {
                            throw str('incorrect_file_name');
                        }
                        files[pos].setFileName(newname);
                    } catch (e) {
                        showError(str('filenotrenamed').replace(/\{\$a\}/g, newname) + ': ' + e);
                        return false;
                    }
                    self.setModified();
                    adjustTabsTitles(false);
                    VPL_Util.delay(self.updateFileList);
                    return true;
                };
                this.deleteFile = function(name, ok, showError) {
                    var pos = fileNameExists(name);
                    if (pos == -1) {
                        showError(str('filenotdeleted').replace(/\{\$a\}/g, name));
                        return false;
                    }
                    if (pos < minNumberOfFiles) {
                        showError(str('filenotdeleted').replace(/\{\$a\}/g, name));
                        return false;
                    }
                    self.setModified();
                    self.close(files[pos]);
                    files.splice(pos, 1);
                    VPL_Util.delay(self.updateFileList);
                    return true;
                };
                this.currentFile = function() {
                    var id = tabs.tabs('option', 'active');
                    if (id in openFiles) {
                        var file = openFiles[id];
                        if (arguments.length === 0) {
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
                };
                this.currentPos = function() {
                    return tabs.tabs('option', 'active');
                };
                this.getFileTab = function(id) {
                    for (var i = 0; i < openFiles.length; i++) {
                        if (openFiles[i].getId() == id) {
                            return i;
                        }
                    }
                    return -1;
                };
                this.getFilePosById = function(id) {
                    for (var i = 0; i < files.length; i++) {
                        if (files[i].getId() == id) {
                            return i;
                        }
                    }
                    return -1;
                };
                this.gotoFile = function(pos, l) {
                    var file = files[pos];
                    self.open(file);
                    tabs.tabs('option', 'active', self.getFileTab(file.getId()));
                    if (l !== 'c') {
                        file.gotoLine(parseInt(l, 10));
                    }
                    file.focus();
                };
                this.gotoFileLink = function(a) {
                    var tag = $JQVPL(a);
                    var fname = tag.data( 'file' );
                    var fpos = -1;
                    if (fname > '') {
                        fpos = fileNameExists( fname );
                    } else {
                        fpos = self.getFilePosById( tag.data( 'fileid' ) );
                    }
                    if (fpos >= 0) {
                        var line = tag.data('line');
                        if ( typeof line == 'undefined' ) {
                            line = 'c';
                        }
                        self.gotoFile(fpos, line);
                    }
                };
                this.getFilesToSave = function() {
                    var ret = [];
                    for (var i = 0; i < files.length; i++) {
                        var file = {};
                        file.name = files[i].getFileName();
                        file.contents = files[i].getContent();
                        file.encoding = files[i].isBinary() ? 1 : 0;
                        ret.push(file);
                    }
                    return ret;
                };
                this.resetModified = function() {
                    modified = false;
                    for (var i = 0; i < files.length; i++) {
                        files[i].resetModified();
                    }
                    VPL_Util.delay(updateMenu);
                    VPL_Util.delay(self.updateFileList);
                };
                this.setModified = function() {
                    if (!modified) {
                        modified = true;
                        VPL_Util.delay(self.updateFileList);
                    }
                    VPL_Util.delay(updateMenu);
                };
                this.isModified = function() {
                    return modified;
                };
                this.length = function() {
                    return files.length;
                };
                this.clearAnnotations = function() {
                    for (var i = 0; i < files.length; i++) {
                        files[i].clearAnnotations();
                    }
                };
                this.getFile = function(i) {
                    return files[i];
                };
                this.getFiles = function() {
                    return files;
                };
                this.getDirectoryStructure = function() {
                    var structure = {
                        isDir : true,
                        content : {}
                    };
                    for (var i in files) {
                        if ( files.hasOwnProperty(i) ) {
                            var file = files[i];
                            var fileName = file.getFileName();
                            var path = fileName.split("/");
                            var curdir = structure;
                            for (var p in path) {
                                if ( path.hasOwnProperty(p) ) {
                                    var part = path[p];
                                    if (p == path.length - 1) { // File.
                                        curdir.content[part] = {
                                            isDir : false,
                                            content : file,
                                            pos : i
                                        };
                                    } else {
                                        if (!curdir.content[part]) { // New dir.
                                            curdir.content[part] = {
                                                isDir : true,
                                                content : {}
                                            };
                                        }
                                        // Descend Dir.
                                        curdir = curdir.content[part];
                                    }
                                }
                            }
                        }
                    }
                    return structure;
                };
                this.generateFileList = function() {
                    if (!self.isFileListVisible()){
                        return;
                    }
                    var dirIndent = '<span class="vpl_ide_dirindent"></span>';
                    function lister(dir,indent,lines) {
                        for (var name in dir.content) {
                            if ( dir.content.hasOwnProperty(name) ) {
                                var fd = dir.content[name];
                                if (fd.isDir) {
                                    lines.push( indent + VPL_Util.iconFolder() + VPL_Util.sanitizeText(name) );
                                    lister(fd, indent + dirIndent, lines);
                                } else {
                                    var file = fd.content;
                                    var sname = VPL_Util.sanitizeText( name );
                                    var path = VPL_Util.sanitizeText( file.getFileName() );
                                    if ( file.isOpen() ) {
                                        sname = '<b>' + sname + '</b>';
                                    }
                                    var attrs = 'href="#" data-fileid="' + file.getId() + '" title="' + path + '"';
                                    var line = '<a ' + attrs + '>' + sname + '</a>';
                                    if (file.isModified()) {
                                        line = VPL_Util.iconModified() + line;
                                    }
                                    if (fd.pos < minNumberOfFiles) {
                                        line = line + VPL_Util.iconRequired();
                                    }
                                    lines.push( indent + line );
                                }
                            }
                        }
                    }

                    var structure = self.getDirectoryStructure();
                    var lines = [];
                    var html = '';
                    lister(structure,'',lines);
                    for (var i = 0; i < lines.length; i++) {
                        html += lines[i] + '<br />';
                    }
                    file_list_content.html('<div>' + html + '</div>');
                };
                tabs_ul.on('click', 'span.vpl_ide_closeicon', function() {
                    file_manager.close(file_manager.currentFile());
                });
                tabs_ul.on('dblclick', 'span.vpl_ide_closeicon', menuButtons.getAction('delete'));
                tabs_ul.on('dblclick', 'a',  menuButtons.getAction('rename'));
                file_list_content.on('dblclick', 'a',  menuButtons.getAction('rename'));

            }
            this.updateEvaluationNumber = function(res) {
                if( typeof res.nevaluations != 'undefined' ) {
                    var text = res.nevaluations;
                    if ( typeof res.reductionbyevaluation != 'undefined'
                         && res.reductionbyevaluation > ''
                         && res.reductionbyevaluation != 0) {
                        if ( res.freeevaluations != 0 ) {
                            text = text + '/' + res.freeevaluations;
                        }
                        text = text + ' -' + res.reductionbyevaluation;
                    }
                    menuButtons.setExtracontent('evaluate', text);
                }
            };
            this.lastResult = null;
            this.setResultGrade = function(content, raw) {
                var name = 'grade';
                var titleclass = 'vpl_ide_accordion_t_' + name;
                var contentclass = 'vpl_ide_accordion_c_' + name;
                if ( result.find('.' + contentclass).length == 0 ) {
                    result.append('<div class="' + titleclass + '"></div>');
                    result.append('<div class="' + contentclass + '"></div>');
                }
                if (typeof raw == 'undefined') {
                    return result.find('h4.' + titleclass).length > 0;
                }
                var titleTag = result.find('.' + titleclass);
                if ( content > '' ) {
                    titleTag.replaceWith('<h4 class="' + titleclass + '">' + content + '</h4>');
                    return true;
                } else {
                    titleTag.replaceWith('<div class="' + titleclass + '"></div>');
                    return false;
                }
            };
            this.setResultTab = function(name, content, raw) {
                var titleclass = 'vpl_ide_accordion_t_' + name;
                var contentclass = 'vpl_ide_accordion_c_' + name;
                if ( result.find('.' + contentclass).length == 0 ) {
                    result.append('<div class="' + titleclass + '"></div>');
                    result.append('<div class="' + contentclass + '"></div>');
                }
                if (typeof raw == 'undefined') {
                    return result.find('h4.' + titleclass).length > 0;
                }
                var titleTag = result.find('.' + titleclass);
                var contentTag = result.find('.' + contentclass);
                var HTMLcontent = $JQVPL('<div>' + content + '</div>');
                HTMLcontent.find('h4').replaceWith(function () {
                    return $JQVPL("<h5>").append($JQVPL(this).contents());
                });
                if ( contentTag.html() == HTMLcontent.html() ) {
                    return content > '';
                }
                if ( content > '' ) {
                    titleTag.replaceWith('<h4 class="' + titleclass + '">' + str(name) + '</h4>');
                    contentTag.replaceWith('<div class="ui-widget ' + contentclass + '">' + HTMLcontent.html() + '</div>');
                    return true;
                } else {
                    titleTag.replaceWith('<div class="' + titleclass + '"></div>');
                    contentTag.replaceWith('<div class="' + contentclass + '"></div>');
                    return false;
                }
            };
            this.setResult = function(res, go) {
                self.updateEvaluationNumber(res);
                var files = file_manager.getFiles();
                var fileNames = [];
                var i;
                for (i = 0; i < files.length; i++) {
                    fileNames [i] = files[i].getFileName();
                    files[i].clearAnnotations();
                }
                var show = false;
                var hasContent;
                var grade = VPL_Util.sanitizeText(res.grade);
                var gradeShow;
                var formated;
                gradeShow = self.setResultGrade(grade, res.grade);
                show = show || gradeShow;
                formated = VPL_Util.processResult( res.compilation, fileNames, files, true, false );
                hasContent = self.setResultTab('compilation', formated, res.compilation);
                show = show || hasContent;
                formated = VPL_Util.processResult( res.evaluation, fileNames, files, false, false );
                hasContent = self.setResultTab('comments', formated, res.evaluation);
                show = show || hasContent;
                formated = VPL_Util.sanitizeText(res.execution);
                hasContent = self.setResultTab('execution', formated, res.execution);
                show = show || hasContent;
                hasContent = self.setResultTab('description', options.description, options.description);
                show = show || hasContent;
                if ( show ) {
                    result_container.show();
                    result_container.vpl_visible = true;
                    result.accordion("refresh");
                    result.accordion('option', 'active', gradeShow ? 1 : 0);
                    for (i = 0; i < files.length; i++) {
                        var anot = files[i].getAnnotations();
                        for (var j = 0; j < anot.length; j++) {
                            if (go || anot[j].type == 'error') {
                                file_manager.gotoFile(i, anot[j].row + 1);
                                break;
                            }
                        }
                    }
                } else {
                    result_container.hide();
                    result_container.vpl_visible = false;
                }
                VPL_Util.longDelay(autoResizeTab);
            };

            result.accordion({
                heightStyle : 'fill',
                header : 'h4',
                beforeActivate : avoidSelectGrade,
            });
            result_container.width(2 * result_container.vpl_minWidth);
            result.on('click', 'a', function(event) {
                event.preventDefault();
                file_manager.gotoFileLink(event.currentTarget);
            });
            result_container.vpl_visible = false;
            result_container.hide();

            file_list_container.addClass('ui-tabs ui-widget ui-widget-content ui-corner-all');
            file_list.text(str('filelist'));
            file_list.html(VPL_Util.iconFolder() + file_list.html());
            file_list.addClass("ui-widget-header ui-button-text-only ui-corner-all");
            file_list_content.addClass("ui-widget ui-corner-all");
            file_list_container.width(2 * file_list_container.vpl_minWidth);
            file_list_container.on('click', 'a', function(event) {
                event.preventDefault();
                file_manager.gotoFileLink(event.currentTarget);
            });
            file_list_container.vpl_visible = false;
            file_list_container.hide();
            tabs.tabs();
            var tabsAir = false;
            function getTabsAir() {
                if (tabsAir === false) {
                    tabsAir = (tabs.outerWidth(true) - tabs.width()) / 2;
                }
                return tabsAir;
            }
            function resizeTabWidth(e, ui) {
                var diff_left = ui.position.left - ui.originalPosition.left;
                var maxWidth;
                if (diff_left !== 0) {
                    maxWidth = tabs.width() + file_list_container.width() - file_list_container.vpl_minWidth;
                    tabs.resizable('option', 'maxWidth', maxWidth);
                    file_list_container.width(file_list_container.vpl_original_width + diff_left);
                } else {
                    maxWidth = tabs.width() + result_container.width() - result_container.vpl_minWidth;
                    tabs.resizable('option', 'maxWidth', maxWidth);
                    var diff_width = ui.size.width - ui.originalSize.width;
                    result_container.width(result_container.vpl_original_width - diff_width);
                }
                file_manager.currentFile('adjustSize');
            }
            var resizableOptions = {
                containment : 'parent',
                resize : resizeTabWidth,
                start : function() {
                    $JQVPL(window).off('resize', autoResizeTab);
                    tabs.resizable('option', 'minWidth', 100);
                    if (result_container.vpl_visible) {
                        result_container.vpl_original_width = result_container.width();
                    }
                    if (file_list_container.vpl_visible) {
                        file_list_container.vpl_original_width = file_list_container.width();
                    }
                },
                stop : function(e, ui) {
                    resizeTabWidth(e, ui);
                    tabs.resizable('option', 'maxWidth', 100000);
                    tabs.resizable('option', 'minWidth', 0);
                    autoResizeTab();
                    $JQVPL(window).on('resize', autoResizeTab);
                },
                handles : ""
            };
            tabs.resizable(resizableOptions);
            function updateTabsHandles() {
                var handles = [ 'e', 'w', 'e', 'e, w' ];
                var index = 0;
                index += file_list_container.vpl_visible ? 1 : 0;
                index += result_container.vpl_visible ? 2 : 0;
                tabs.resizable('destroy');
                resizableOptions.handles = handles[index];
                resizableOptions.disable = index === 0;
                tabs.resizable(resizableOptions);
            }
            function resizeHeight() {
                var newHeight = $JQVPL(window).outerHeight();
                newHeight -= menu.offset().top + menu.height() + (fullScreen ? getTabsAir() : 20);
                if (newHeight < 150) {
                    newHeight = 150;
                }
                tr.height( newHeight );
                var panelHeight = newHeight - 3 * getTabsAir();
                tabs.height(panelHeight);
                if (result_container.vpl_visible) {
                    result_container.height( panelHeight + getTabsAir());
                    result.accordion( 'refresh' );
                }
                if (file_list_container.vpl_visible) {
                    file_list_content.height( panelHeight - (file_list.outerHeight() + getTabsAir()));
                    file_list_container.height( panelHeight );
                }
            }
            adjustTabsTitles = function(center) {
                var newWidth = tabs.width();
                var tabs_ul_width = 0;
                tabs_ul.width(100000);
                var last = tabs_ul.children('li:visible').last();
                if (last.length) {
                    var parentScrollLeft = tabs_ul.parent().scrollLeft();
                    tabs_ul_width = parentScrollLeft + last.position().left + last.width() + tabsAir;
                    tabs_ul.width(tabs_ul_width);
                    var file = file_manager.currentFile();
                    if (file && center) {
                        var fileTab = $JQVPL(file.getTabNameId());
                        var scroll = parentScrollLeft + fileTab.position().left;
                        scroll -= (newWidth - fileTab.outerWidth()) / 2;
                        if (scroll < 0) {
                            scroll = 0;
                        }
                        tabs_ul.parent().finish().animate({
                            scrollLeft : scroll
                        }, 'slow');
                    }
                }
                if (tabs_ul_width < newWidth) {
                    tabs_ul.width('');
                }
            };
            autoResizeTab = function () {
                var oldWidth = tabs.width();
                var newWidth = menu.width();
                var planb = false;
                updateTabsHandles();
                tr.width(menu.outerWidth());
                if (file_list_container.vpl_visible) {
                    var left = file_list_container.outerWidth() + tabsAir;
                    oldWidth += left;
                    if (left >= 100) {
                        newWidth -= left;
                        tabs.css('left', left);
                    } else {
                        planb = true;
                    }
                } else {
                    tabs.css('left', 0);
                }
                if (result_container.vpl_visible) {
                    var rigth = result_container.outerWidth() + tabsAir;
                    oldWidth += rigth;
                    newWidth -= rigth;
                    if (newWidth < 100) {
                        planb = true;
                    }
                }
                if (planb) {
                    var rel = menu.width() / oldWidth;
                    var wfl = 0;
                    if (file_list_container.vpl_visible) {
                        wfl = file_list_container.width() * rel;
                        file_list_container.width(wfl - tabsAir);
                        wfl += tabsAir;
                        tabs.css('left', wfl);
                    }
                    tabs.width(tabs.width() * rel);
                    if (result_container.vpl_visible) {
                        result_container.width(menu.width() - (wfl + tabs.width() + tabsAir));
                    }
                } else {
                    tabs.width(newWidth);
                }
                adjustTabsTitles(true);
                resizeHeight();
                file_manager.currentFile('adjustSize');
            };
            function focusCurrentFile() {
                file_manager.currentFile('focus');
            }
            var dialogbase_options = $JQVPL.extend({}, {
                close : focusCurrentFile
            }, VPL_Util.dialogbase_options);
            function showMessage(message, options) {
                return VPL_Util.showMessage(message, $JQVPL.extend({}, dialogbase_options, options));
            }
            showErrorMessage = function(message) {
                return VPL_Util.showErrorMessage(message, {
                    close : focusCurrentFile
                });
            };

            var dialog_new = $JQVPL('#vpl_ide_dialog_new');
            function newFileHandler(event) {
                if (!(event.type == 'click' || ((event.type == 'keypress') && event.keyCode == 13))) {
                    return;
                }
                dialog_new.dialog('close');
                var file = {
                    name:$JQVPL('#vpl_ide_input_newfilename').val(),
                    contents:'',
                    encoding:0
                };
                var newfile = file_manager.addFile(file, false, updateMenu, showErrorMessage);
                if (newfile) {
                    file_manager.open(newfile);
                    tabs.tabs('option', 'active', file_manager.getTabPos(newfile));
                    newfile.focus();
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

            var dialog_rename = $JQVPL('#vpl_ide_dialog_rename');
            function renameHandler(event) {
                if (!(event.type == 'click' || ((event.type == 'keypress') && event.keyCode == 13))) {
                    return;
                }
                dialog_rename.dialog('close');
                file_manager.renameFile(file_manager.currentFile('getFileName'),
                        $JQVPL('#vpl_ide_input_renamefilename').val(), showErrorMessage);
                event.preventDefault();
            }
            dialog_rename.find('input').on('keypress', renameHandler);
            dialogButtons[str('ok')] = renameHandler;
            dialog_rename.dialog($JQVPL.extend({}, dialogbase_options, {
                open : function() {
                    $JQVPL('#vpl_ide_input_renamefilename').val(file_manager.currentFile('getFileName'));
                },
                title : str('rename_file'),
                buttons : dialogButtons
            }));
            dialogButtons[str('ok')] = function(){
                $JQVPL(this).dialog('close');
            };
            var dialog_comments = $JQVPL('#vpl_ide_dialog_comments');
            dialog_comments.dialog($JQVPL.extend({}, dialogbase_options, {
                title : str('comments'),
                width : '40em',
                buttons : dialogButtons
            }));
            $JQVPL('#vpl_ide_input_comments').width('30em');
            var aboutDialog = $JQVPL('#vpl_ide_dialog_about');
            var OKButtons = {};
            OKButtons[str('ok')] = function() {
                $JQVPL(this).dialog('close');
            };
            var shortcutDialog = $JQVPL('#vpl_ide_dialog_shortcuts');
            shortcutDialog.dialog($JQVPL.extend({}, dialogbase_options, {
                open: function(){
                    var html = menuButtons.getShortcuts(file_manager.currentFile('getEditor'));
                    $JQVPL('#vpl_ide_dialog_shortcuts .vpl_ide_dialog_content').html(html);
                },
                title : str('shortcuts'),
                width : 'auto',
                height: 'auto',
                buttons : OKButtons
            }));
            OKButtons[str('shortcuts')] = function() {
                $JQVPL(this).dialog('close');
                shortcutDialog.dialog('open');
            };
            aboutDialog.dialog($JQVPL.extend({}, dialogbase_options, {
                title : str('about'),
                width : 'auto',
                height: 'auto',
                buttons : OKButtons
            }));
            var dialog_sort = $JQVPL('#vpl_ide_dialog_sort');
            var dialogSortButtons = {};
            dialogSortButtons[str('ok')] = function() {
                var files = file_manager.getFiles();
                var regNoNumber = /[^\d]*/;
                var sorted = [];
                var i = 0;
                var newOrder = $JQVPL('#vpl_sort_list li');
                if (newOrder.length != files.length) {
                    return;
                }
                newOrder.each(function() {
                    var orig = parseInt(this.id.replace(regNoNumber, ''));
                    sorted.push(files[orig]);
                });
                for (i = 0; i < newOrder.length; i++) {
                    files[i] = sorted[i];
                }
                file_manager.setModified();
                VPL_Util.delay(updateMenu);
                VPL_Util.delay(file_manager.updateFileList);
                $JQVPL(this).dialog('close');
            };
            dialogSortButtons[str('cancel')] = function() {
                $JQVPL(this).dialog('close');
            };
            dialog_sort.dialog($JQVPL.extend({}, dialogbase_options, {
                title : str('sort'),
                buttons : dialogSortButtons,
                open : function() {
                    var list = $JQVPL('#vpl_sort_list');
                    list.html('');
                    var files = file_manager.getFiles();
                    for (var i = 0; i < files.length; i++) {
                        var file = $JQVPL('<li id="vpl_fsort_' + i + '"class="ui-widget-content"></li>');
                        if (i < minNumberOfFiles) {
                            file.addClass('ui-state-disabled');
                        }
                        file.text((i + 1) + '-' + files[i].getFileName());
                        list.append(file);
                    }
                    list.sortable({
                        items : "li:not(.ui-state-disabled)",
                        placeholder : "ui-state-highlight",
                        start : function(event, ui) {
                            ui.item.addClass('ui-state-highlight');
                        },
                        stop : function(event, ui) {
                            ui.item.removeClass('ui-state-highlight');
                        },
                    });
                    list.disableSelection();
                },
                maxHeight : 400
            }));
            var dialog_multidelete = $JQVPL('#vpl_ide_dialog_multidelete');
            var dialogMultideleteButtons = {};
            dialogMultideleteButtons[str('selectall')] = function() {
                $JQVPL(this).find('input').prop( "checked", true );
            };
            dialogMultideleteButtons[str('deselectall')] = function() {
                $JQVPL(this).find('input').prop( "checked", false );
            };
            dialogMultideleteButtons[str('deleteselected')] = function() {
                var files = file_manager.getFiles();
                var toDeleteList = [];
                var labelList = $JQVPL('#vpl_multidelete_list label');
                labelList.each(function() {
                    var label = $JQVPL(this);
                    if ( label.find('input').prop('checked') ) {
                        var id = label.data('fileid');
                        toDeleteList.push(files[id].getFileName());
                    }
                });
                for (var i = 0; i < toDeleteList.length; i++) {
                    file_manager.deleteFile(toDeleteList[i], false, showErrorMessage);
                }
                VPL_Util.delay(updateMenu);
                VPL_Util.delay(file_manager.updateFileList);
                $JQVPL(this).dialog('close');
            };
            dialogMultideleteButtons[str('cancel')] = function() {
                $JQVPL(this).dialog('close');
            };
            dialog_multidelete.dialog($JQVPL.extend({}, dialogbase_options, {
                title : str('multidelete'),
                buttons : dialogMultideleteButtons,
                open : function() {
                    var list = $JQVPL('#vpl_multidelete_list');
                    list.html('');
                    var files = file_manager.getFiles();
                    for (var i = minNumberOfFiles; i < files.length; i++) {
                        var name = VPL_Util.sanitizeText(files[i].getFileName());
                        var file = $JQVPL('<label><input type="checkbox"> ' + name + '</label>');
                        file.data('fileid', i);
                        list.append(file);
                        list.append('<br>');
                    }
                    list.find('label').button();
                },
                maxHeight : 400,
                maxWidth : 400
            }));
            var dialog_fontsize = $JQVPL('#vpl_ide_dialog_fontsize');
            var fontsize_slider = $JQVPL('#vpl_ide_dialog_fontsize .vpl_fontsize_slider');
            var dialogFontFizeButtons = {};
            dialogFontFizeButtons[str('ok')] = function() {
                var value = fontsize_slider.slider( "value");
                file_manager.setFontSize(value);
                $JQVPL(this).dialog('close');
                $JQVPL.ajax({
                    async : true,
                    type : "POST",
                    url : '../editor/userpreferences.json.php',
                    'data' : JSON.stringify({fontSize:value}),
                    contentType : "application/json; charset=utf-8",
                    dataType : "json"
                });
            };
            dialogFontFizeButtons[str('cancel')] = function() {
                file_manager.setFontSize(fontsize_slider.data( "vpl_fontsize" ));
                $JQVPL(this).dialog('close');
            };
            dialogFontFizeButtons[str('reset')] = function() {
                fontsize_slider.slider('value', 12);
            };
            dialog_fontsize.dialog($JQVPL.extend({}, dialogbase_options, {
                title : str('fontsize'),
                buttons : dialogFontFizeButtons,
                open : function() {
                    fontsize_slider.data( "vpl_fontsize" , file_manager.getFontSize() );
                    fontsize_slider.slider('value', file_manager.getFontSize());
                },
            }));
            fontsize_slider.slider({
                min: 1,
                max: 48,
                change: function() {
                    var value = fontsize_slider.slider( "value");
                    file_manager.setFontSize( value );
                    dialog_fontsize.find('.vpl_fontsize_slider_value').text( value );
                }
            });
            var terminal = new VPL_Terminal('vpl_dialog_terminal', 'vpl_terminal', str);
            var VNCClient = new VPL_VNC_Client('vpl_dialog_vnc', str);
            var lastConsole = terminal;
            var file_select = $JQVPL('#vpl_ide_input_file');
            var file_select_handler = function() {
                VPL_Util.readSelectedFiles(this.files, function(file) {
                    return file_manager.addFile(file, true, updateMenu, showErrorMessage);
                },
                function(){
                    file_manager.fileListVisibleIfNeeded();
                });
            };
            file_select.on('change', file_select_handler);
            // Menu acctions.
            menuButtons.add({
                name:'filelist',
                originalAction: function() {
                    file_manager.fileListVisible(!file_manager.isFileListVisible());
                    VPL_Util.delay(updateMenu);
                    VPL_Util.delay(autoResizeTab);
                    VPL_Util.delay(file_manager.updateFileList);
                },
                bindKey: {
                    win: 'Ctrl-L',
                    mac: 'Ctrl-L'
                }
            });

            menuButtons.add({
                name: 'new',
                originalAction: function() {
                    if (file_manager.length() < maxNumberOfFiles) {
                        dialog_new.dialog('open');
                    }
                },
                bindKey: {
                    win: 'Alt-N',
                    mac: 'Option-N'
                }
            });
            menuButtons.add({
                name: 'rename',
                originalAction: function() {
                    var file = file_manager.currentFile();
                    if (file && file_manager.getFilePosById(file.getId()) >= minNumberOfFiles) {
                        dialog_rename.dialog('open');
                    }
                },
                bindKey: {
                    win: 'Ctrl-R',
                    mac: 'Ctrl-R'
                }
            });
            menuButtons.add({
                name:'delete',
                originalAction: function() {
                    var file = file_manager.currentFile();
                    if (!file) {
                        return;
                    }
                    var filename = file.getFileName();
                    var message = str('delete_file_fq').replace(/\{\$a\}/g, filename);
                    showMessage(message, {
                        ok : function() {
                            file_manager.deleteFile(filename, showErrorMessage);
                        },
                        title : str('delete_file_q'),
                        icon : 'trash'
                    });
                },
                bindKey:{
                    win: 'Ctrl-D',
                    mac: 'Ctrl-D'
                }
            });
            menuButtons.add({
                name:'close',
                originalAction: function() {
                    var file = file_manager.currentFile();
                    if (!file) {
                        return;
                    }
                    file_manager.close(file);
                },
                bindKey:{
                    win: 'Alt-W',
                    mac: 'Option-W'
                }
            });
            menuButtons.add({
                name:'import',
                originalAction: function() {
                    file_select.val('');
                    file_select.trigger('click');
                },
                bindKey:{
                    win: 'Ctrl-I',
                    mac: 'Ctrl-I'
                }
            });
            menuButtons.add({
                name:'sort',
                originalAction: function() {
                    dialog_sort.dialog('open');
                },
                bindKey:{
                    win: 'Ctrl-O',
                    mac: 'Ctrl-O'
                }
            });
            menuButtons.add({
                name:'multidelete',
                originalAction: function() {
                    dialog_multidelete.dialog('open');
                }
            });
            menuButtons.add({
                name:'fontsize',
                originalAction: function() {
                    dialog_fontsize.dialog('open');
                }
            });
            menuButtons.add({
                name:'print',
                originalAction: function() {
                    window.print();
                },
                bindKey:{
                    win: 'Alt-P',
                    mac: 'Command-P'
                }
            });
            menuButtons.add({
                name:'undo',
                originalAction: function() {
                    file_manager.currentFile('undo');
                }
            });
            menuButtons.add({
                name:'redo',
                originalAction: function() {
                    file_manager.currentFile('redo');
                }
            });
            menuButtons.add({
                name:'select_all',
                editorName:'selectall',
                originalAction: function() {
                    file_manager.currentFile('selectAll');
                }
            });
            menuButtons.add({
                name:'find',
                originalAction: function() {
                    file_manager.currentFile('find');
                }
            });
            menuButtons.add({
                name:'find_replace',
                editorName:'replace',
                originalAction: function() {
                    file_manager.currentFile('replace');
                }
            });
            menuButtons.add({
                name:'next',
                editorName:'findnext',
                originalAction: function() {
                    file_manager.currentFile('next');
                }
            });
            menuButtons.add({
                name:'fullscreen',
                originalAction: function() {
                    var tags = 'header, nav, footer, aside, .dropdown, #page-header, div.navbar, #nav-drawer';
                    tags += ', div.tabtree, #dock, .breadcrumb-nav, .moodle-actionmenu';
                    if (fullScreen) {
                        root_obj.removeClass('vpl_ide_root_fullscreen');
                        $JQVPL('body').removeClass('vpl_body_fullscreen');
                        menuButtons.setText('fullscreen', 'fullscreen');
                        $JQVPL(tags).show();
                        $JQVPL('#vpl_ide_user').hide();
                        fullScreen = false;
                    } else {
                        $JQVPL('body').addClass('vpl_body_fullscreen').scrollTop(0);
                        $JQVPL(tags).hide();
                        root_obj.addClass('vpl_ide_root_fullscreen');
                        menuButtons.setText('fullscreen', 'regularscreen');
                        if ( options.username ) {
                            $JQVPL('#vpl_ide_user').show();
                        }
                        fullScreen = true;
                    }
                    focusCurrentFile();
                    setTimeout(autoResizeTab, 10);
                },
                bindKey:{
                    win: 'Alt-F',
                    mac: 'Ctrl-F'
                }
            });
            menuButtons.add({
                name:'download',
                originalAction: function() {
                    window.location = options.download;
                }
            });

            function resetFiles() {
                VPL_Util.requestAction('resetfiles', '', {}, options.ajaxurl)
                .done( function(response) {
                    var files = response.files;
                    for (var fileName in files) {
                        if ( files.hasOwnProperty(fileName) ) {
                            file_manager.addFile(files[fileName], true, VPL_Util.doNothing, showErrorMessage);
                        }
                    }
                    file_manager.fileListVisibleIfNeeded();
                    VPL_Util.delay(updateMenu);
                }).fail(showErrorMessage);
            }
            menuButtons.add({
                name:'resetfiles',
                originalAction: function() {
                    showMessage(str('sureresetfiles'), {
                        title : str('resetfiles'),
                        ok : resetFiles
                    });
                }
            });
            menuButtons.add({
                name:'save',
                originalAction: function() {
                    var data = {
                        files: file_manager.getFilesToSave(),
                        comments: $JQVPL('#vpl_ide_input_comments').val()
                    };
                    VPL_Util.requestAction('save', 'saving', data, options.ajaxurl)
                    .done( function(response) {
                        file_manager.resetModified();
                        menuButtons.setTimeLeft(response);
                        VPL_Util.delay(updateMenu);
                    }).fail(showErrorMessage);
                },
                bindKey:{
                    win: 'Ctrl-S',
                    mac: 'Command-S'
                }
            });

            var executionActions = {
                'getConsole' : function() {
                    return lastConsole;
                },
                'setResult' : self.setResult,
                'ajaxurl' : options.ajaxurl,
                'run' : function(type, coninfo, ws) {
                    if (type == 'terminal') {
                        lastConsole = terminal;
                        terminal.connect(coninfo.executionURL, function() {
                            ws.close();
                            focusCurrentFile();
                        });
                    } else {
                        lastConsole = VNCClient;
                        VNCClient.connect(coninfo.secure, coninfo.server, coninfo.portToUse, coninfo.VNCpassword,
                                coninfo.executionPath, function() {
                                    ws.close();
                                    focusCurrentFile();
                                });
                    }
                },
                'lastAction' : false,
                'getLastAction' : function() {
                    var ret = this.lastAction;
                    this.lastAction = false;
                    return ret;
                },
                'setLastAction' : function(action) {
                    this.lastAction = action;
                }
            };
            function executionRequest(action, acting, data) {
                if (!data) {
                    data = {};
                }
                if (!lastConsole.isConnected()) {
                    VPL_Util.requestAction(action, '', data, options.ajaxurl)
                    .done(function(response) {
                        VPL_Util.webSocketMonitor(response, action, acting, executionActions);
                    })
                    .fail(showErrorMessage);
                }
            }
            function runAction(){
                executionRequest('run', 'running', {
                    XGEOMETRY : VNCClient.getCanvasSize()
                });
            }
            menuButtons.add({
                name:'run',
                originalAction: function() {
                    executionActions.setLastAction(runAction);
                    runAction();
                },
                bindKey:{
                    win: 'Ctrl-F11',
                    mac: 'Command-U'
                }
            });
            function debugAction(){
                executionRequest('debug', 'debugging', {
                    XGEOMETRY : VNCClient.getCanvasSize()
                });
            }
            menuButtons.add({
                name:'debug',
                originalAction: function() {
                    executionActions.setLastAction(debugAction);
                    debugAction();
                },
                bindKey:{
                    win: 'Alt-F11',
                    mac: 'Option-U'
                }
            });
            function evaluateAction(){
                executionRequest('evaluate', 'evaluating');
            }
            menuButtons.add({
                name:'evaluate',
                originalAction: function() {
                    executionActions.setLastAction(evaluateAction);
                    evaluateAction();
                },
                bindKey:{
                    win: 'Shift-F11',
                    mac: 'Command-Option-U'
                }
            });
            menuButtons.add({
                name:'comments',
                originalAction: function() {
                    dialog_comments.dialog('open');
                },
            });
            menuButtons.add({
                name:'console',
                originalAction: function() {
                    lastConsole.show();
                }
            });
            menuButtons.add({ name: 'user' } );
            menuButtons.add({
                name:'about',
                originalAction: function() {
                    aboutDialog.dialog('open');
                }
            });
            menuButtons.add({
                name:'timeleft',
                originalAction: function() {
                    menuButtons.toggleTimeLeft();
                }
            });
            menuButtons.add({
                name:'more',
                originalAction: function() {
                    var tag = $JQVPL('#vpl_ide_menuextra');
                    if ( tag.is(":visible") ) {
                        menuButtons.setText('more', 'more', VPL_Util.str('more'));
                        tag.hide();
                    } else {
                        menuButtons.setText('more', 'less', VPL_Util.str('less'));
                        tag.show();
                    }
                    VPL_Util.delay(updateMenu);
                    VPL_Util.delay(autoResizeTab);
                }
            });
            menu.addClass("ui-widget-header ui-corner-all");
            var menu_html = "";
            menu_html += menuButtons.getHTML('more');
            menu_html += menuButtons.getHTML('save');
            menu_html += "<span id='vpl_ide_mexecution'>";
            menu_html += menuButtons.getHTML('run');
            menu_html += menuButtons.getHTML('debug');
            menu_html += menuButtons.getHTML('evaluate');
            menu_html += menuButtons.getHTML('comments');
            menu_html += menuButtons.getHTML('console');
            menu_html += "</span> ";
            menu_html += "<span id='vpl_ide_menuextra'>";
            menu_html += "<span id='vpl_ide_file'>";
            // TODO autosave not implemented.
            menu_html += menuButtons.getHTML('filelist');
            menu_html += menuButtons.getHTML('new');
            menu_html += menuButtons.getHTML('rename');
            menu_html += menuButtons.getHTML('delete');
            menu_html += menuButtons.getHTML('import');
            menu_html += menuButtons.getHTML('download');
            menu_html += menuButtons.getHTML('resetfiles');
            menu_html += menuButtons.getHTML('sort');
            menu_html += menuButtons.getHTML('multidelete');
            menu_html += menuButtons.getHTML('fontsize');
            menu_html += "</span> ";
            // TODO print still not implemented.
            menu_html += "<span id='vpl_ide_edit'>";
            menu_html += menuButtons.getHTML('undo');
            menu_html += menuButtons.getHTML('redo');
            menu_html += menuButtons.getHTML('select_all');
            menu_html += menuButtons.getHTML('find');
            menu_html += menuButtons.getHTML('find_replace');
            menu_html += menuButtons.getHTML('next');
            menu_html += "</span> ";
            menu_html += "</span> ";
            menu_html += menuButtons.getHTML('fullscreen') + ' ';
            menu_html += menuButtons.getHTML('about') + ' ';
            menu_html += menuButtons.getHTML('user') + ' ';
            menu_html += menuButtons.getHTML('timeleft');
            menu_html += '<div class="clearfix"></div>';
            menu.append(menu_html);
            $JQVPL('#vpl_ide_more').button();
            $JQVPL('#vpl_ide_save').button();
            $JQVPL('#vpl_ide_menuextra').hide();
            $JQVPL('#vpl_ide_file').buttonset();
            $JQVPL('#vpl_ide_edit').buttonset();
            $JQVPL('#vpl_ide_mexecution').buttonset();
            $JQVPL('#vpl_ide_fullscreen').button();
            $JQVPL('#vpl_ide_about').button();
            $JQVPL('#vpl_ide_user').button().css('float','right').hide();
            $JQVPL('#vpl_ide_timeleft').button().css('float','right').hide();
            $JQVPL('#vpl_menu .ui-button').css('padding','6px');
            $JQVPL('#vpl_menu .ui-button-text').css('padding','0');
            menuButtons.setExtracontent('user', options.username);
            menuButtons.setTimeLeft(options);
            updateMenu = function() {
                var i;
                var file = file_manager.currentFile();
                var nfiles = file_manager.length();
                if (nfiles) {
                    tabs.show();
                } else {
                    tabs.hide();
                }
                if (file_manager.isFileListVisible()) {
                    menuButtons.setText('filelist', 'filelistclose', VPL_Util.str('filelist'));
                } else {
                    menuButtons.setText('filelist', 'filelist', VPL_Util.str('filelist'));
                }
                var modified = file_manager.isModified();
                menuButtons.enable('save', modified);
                menuButtons.enable('run', !modified);
                menuButtons.enable('debug', !modified);
                menuButtons.enable('evaluate', !modified);
                menuButtons.enable('download', !modified);
                menuButtons.enable('new', nfiles < maxNumberOfFiles);
                menuButtons.enable('sort', nfiles - minNumberOfFiles > 1);
                menuButtons.enable('multidelete', nfiles - minNumberOfFiles > 1);
                var sel;
                if (!file) {
                    sel = [ 'rename', 'delete', 'undo', 'redo', 'select_all', 'find', 'find_replace', 'next' ];
                    for (i = 0; i < sel.length; i++) {
                        menuButtons.enable(sel[i], false);
                    }
                    return;
                }
                var id = file_manager.getFilePosById(file.getId());
                menuButtons.enable('rename', id >= minNumberOfFiles && nfiles !== 0);
                menuButtons.enable('delete', id >= minNumberOfFiles && nfiles !== 0);

                if (nfiles === 0 || VPL_Util.isBinary(file.getFileName())) {
                    sel = [ 'undo', 'redo', 'select_all', 'find', 'find_replace', 'next' ];
                    for (i = 0; i < sel.length; i++) {
                        menuButtons.enable(sel[i], false);
                    }
                } else {
                    menuButtons.enable('undo', file.hasUndo());
                    menuButtons.enable('redo', file.hasRedo());
                    sel = [ 'select_all', 'find', 'find_replace', 'next' ];
                    for (i = 0; i < sel.length; i++) {
                        menuButtons.enable(sel[i], true);
                    }
                }
                VPL_Util.delay(file_manager.updateFileList);
            };

            tabs.on("tabsactivate", function() {
                file_manager.currentFile('focus');
                VPL_Util.delay(updateMenu);
                VPL_Util.delay(autoResizeTab);
            });

            // VPL_IDE resize view control.
            var jw = $JQVPL(window);
            jw.on('resize', autoResizeTab);
            // Save? before exit.
            if (!options.example) {
                jw.on('beforeunload', function() {
                    if (file_manager.isModified()) {
                        return str('changesNotSaved');
                    }
                });
            }
            file_manager = new File_manager();

            autoResizeTab();
            // Check the menu width that can change without event.
            (function() {
                var oldMenuWidth = menu.width();
                function checkMenuWidth() {
                    var newMenuWidth = menu.width();
                    if (oldMenuWidth != newMenuWidth) {
                        oldMenuWidth = newMenuWidth;
                        autoResizeTab();
                    }
                }
                checkMenuWidth();
                setInterval(checkMenuWidth, 1000);
            }());
            file_manager.resetModified();
            VPL_Util.requestAction('load', 'loading', options, options.loadajaxurl)
            .done(function(response) {
                var allOK = true;
                var files = response.files;
                for (var i = 0; i < files.length; i++) {
                    var file = files[i];
                    var r = file_manager.addFile(file, false, updateMenu, showErrorMessage);
                    if (r) {
                        r.resetModified();
                        if (i < minNumberOfFiles || files.length <= 5) {
                            file_manager.open(r);
                        } else {
                            file_manager.fileListVisible(true);
                        }
                    } else {
                        allOK = false;
                    }
                }
                if (allOK) {
                    file_manager.resetModified();
                } else {
                    file_manager.setModified();
                }
                VPL_Util.delay(updateMenu);
                file_manager.generateFileList();
                tabs.tabs('option', 'active', 0);
                if (file_manager.length() === 0 && maxNumberOfFiles > 0) {
                    menuButtons.getAction('new')();
                } else if ( ! options.saved ) {
                    file_manager.setModified();
                }
                if(response.compilationexecution){
                    self.setResult(response.compilationexecution,false);
                }
                menuButtons.setTimeLeft(response);
                if( response.comments > '') {
                    $JQVPL('#vpl_ide_input_comments').val(response.comments);
                }
            })
            .fail(showErrorMessage);
        };
    }
})();
