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

define(['jquery',
         'jqueryui',
         'mod_vpl/vplutil',
         'mod_vpl/vplidefile',
         'mod_vpl/vplidebutton',
         'mod_vpl/vplterminal',
         'mod_vpl/vplvnc',
         ],
         function($, jqui, VPLUtil, VPLFile, VPLIDEButtons, VPLTerminal, VPLVNCClient ) {
       if ( typeof VPLIDE !== 'undefined' ) {
           return VPLIDE;
       }
       var vplIdeInstance;
       var VPLIDE = function(root_id, options) {
            var self = this;
            var fileManager;
            var adjustTabsTitles;
            var autoResizeTab;
            var showErrorMessage;
            var updateMenu;
            var minNumberOfFiles = options.minfiles || 0;
            var maxNumberOfFiles = options.maxfiles || 0;
            var restrictedEdit = options.restrictededitor || options.example;
            var readOnly = options.example;
            var fullScreen = false;
            var scrollBarWidth = VPLUtil.scrollBarWidth();
            VPLUtil.setStr(options.i18n);
            var str = VPLUtil.str;
            var rootObj = $('#' + root_id);
            $("head").append('<meta name="viewport" content="initial-scale=1">')
                          .append('<meta name="viewport" width="device-width">')
                          .append('<link rel="stylesheet" href="../editor/VPLIDE.css"/>');
            if (typeof rootObj != 'object') {
                throw "VPL: constructor tag_id not found";
            }
            var optionsToCheck = {
                'new' :true,
                'rename' :true,
                'delete' :true,
                'save' :true,
                'run' :true,
                'edit' :true,
                'debug' :true,
                'evaluate' :true,
                'import' :true,
                'resetfiles' :true,
                'correctedfiles' : true,
                'sort' :true,
                'multidelete' :true,
                'acetheme' :true,
                'console' :true,
                'comments' :true
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
                options.acetheme = true;
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
                    VPLUtil.readSelectedFiles(dt.files, function(file) {
                        return fileManager.addFile(file, true, updateMenu, showErrorMessage);
                    },
                    function(){
                        fileManager.fileListVisibleIfNeeded();
                    });
                    e.stopImmediatePropagation();
                    return false;
                }
            }
            rootObj.on('drop', dropHandler);
            rootObj.on('dragover', dragoverHandler);

            // Control paste.
            function restrictedPaste(e) {
                if (restrictedEdit) {
                    e.stopPropagation();
                    return false;
                }
            }
            // Init editor vars.

            var menu = $('#vpl_menu');
            var menuButtons = new VPLIDEButtons(menu,isOptionAllowed);
            var tr = $('#vpl_tr');
            var fileListContainer = $('#vpl_filelist');
            var fileList = $('#vpl_filelist_header');
            var fileListContent = $('#vpl_filelist_content');
            var tabsUl = $('#vpl_tabs_ul');
            var tabs = $('#vpl_tabs');
            var result_container = $('#vpl_results');
            var result = $('#vpl_results_accordion');
            fileListContainer.vpl_minWidth = 80;
            result_container.vpl_minWidth = 100;

            function avoidSelectGrade(event, ui) {
                if ("newHeader" in ui) {
                    if (ui.newHeader.hasClass('vpl_ide_accordion_t_grade')) {
                        return false;
                    }
                }
            }
            function FileManager() {
                var tabsUl = $('#vpl_tabs_ul');
                var tabs = $('#vpl_tabs').tabs("widget");
                var files = [];
                var openFiles = [];
                var modified = true;
                var self = this;
                this.updateFileList = function() {
                    self.generateFileList();
                };
                this.fileNameExists = function(name) {
                    var checkName = name.toLowerCase();
                    for (var i = 0; i < files.length; i++) {
                        if (files[i].getFileName().toLowerCase() == checkName) {
                            return i;
                        }
                    }
                    return -1;
                };
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
                function twoBlockly(oldname, newname) {
                    if (VPLUtil.isBlockly(oldname)) {
                        return false;
                    }
                    if (VPLUtil.isBlockly(newname)) {
                        for (var i = 0; i < files.length; i++) {
                            if (VPLUtil.isBlockly(files[i].getFileName())) {
                                return true;
                            }
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
                this.setTheme = function(theme){
                    options.theme = theme;
                    for (var i = 0; i < files.length; i++) {
                        files[i].setTheme(theme);
                    }
                };
                this.addTab = function(fid) {
                    var hlink = '<a href="#vpl_file' + fid + '"></a>';
                    tabsUl.append('<li id="vpl_tab_name' + fid + '">' + hlink + '</li>');
                    tabs.append('<div id="vpl_file' + fid + '" class="vpl_ide_file"></div>');
                };
                this.removeTab = function(fid) {
                    tabsUl.find('#vpl_tab_name' + fid).remove();
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
                    VPLUtil.delay('updateMenu', updateMenu);
                    VPLUtil.delay('updateFileList', self.updateFileList);
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
                    VPLUtil.delay('updateFileList', self.updateFileList);
                    VPLUtil.delay('adjustTabsTitles', adjustTabsTitles, false);
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
                    if (b === fileListContainer.vpl_visible){
                        return;
                    }
                    fileListContainer.vpl_visible = b;
                    if (b) {
                        fileListContainer.show();
                        autoResizeTab();
                    } else {
                        fileListContainer.hide();
                        autoResizeTab();
                    }
                };
                this.isFileListVisible = function() {
                    return fileListContainer.vpl_visible;
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
                    if ((typeof file.name != 'string') || !VPLUtil.validPath(file.name)) {
                        showError(str('incorrect_file_name') + ' (' + file.name + ')');
                        return false;
                    }
                    if (replace !== true) {
                        replace = false;
                    }
                    var pos = this.fileNameExists(file.name);
                    if (pos != -1) {
                        if (replace) {
                            files[pos].setContent(file.contents);
                            self.setModified();
                            ok();
                            VPLUtil.delay('updateFileList', self.updateFileList);
                            return file;
                        } else {
                            showError(str('filenotadded').replace(/\{\$a\}/g, file.name));
                            return false;
                        }
                    }
                    if (fileNameIncluded(file.name) || twoBlockly('', file.name)) {
                        showError(str('filenotadded').replace(/\{\$a\}/g, file.name));
                        return false;
                    }
                    if (files.length >= maxNumberOfFiles) {
                        showError(str('maxfilesexceeded') + ' (' + maxNumberOfFiles + ')');
                        return false;
                    }
                    var fid = VPLUtil.getUniqueId();
                    var newfile = new VPLFile(fid, file.name, file.contents, this, vplIdeInstance);
                    if (file.encoding == 1) {
                        newfile.extendToBinary();
                    } else {
                        if (VPLUtil.isBlockly(file.name)) {
                            newfile.extendToBlockly();
                        } else {
                            newfile.extendToCodeEditor();
                        }
                    }
                    files.push(newfile);
                    self.setModified();
                    if (files.length > 5) {
                        self.fileListVisible(true);
                    }
                    ok();
                    VPLUtil.delay('updateFileList', self.updateFileList);
                    return newfile;
                };
                this.renameFile = function(oldname, newname, showError) {
                    var pos = this.fileNameExists(oldname);
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
                        if (!VPLUtil.validPath(newname) ||
                               fileNameIncluded(newname) ||
                               twoBlockly(oldname, newname)) {
                            throw str('incorrect_file_name');
                        }
                        if ( VPLUtil.isBinary() && VPLUtil.fileExtension(oldname) != VPLUtil.fileExtension(newname)) {
                            throw str('incorrect_file_name');
                        }
                        if ( !VPLUtil.isBlockly(oldname) && VPLUtil.isBlockly(newname) ||
                             VPLUtil.isBlockly(oldname) && !VPLUtil.isBlockly(newname) ) {
                            throw str('incorrect_file_name');
                        }
                        files[pos].setFileName(newname);
                    } catch (e) {
                        showError(str('filenotrenamed').replace(/\{\$a\}/g, newname) + ': ' + e);
                        return false;
                    }
                    self.setModified();
                    adjustTabsTitles(false);
                    VPLUtil.delay('updateFileList', self.updateFileList);
                    return true;
                };
                this.deleteFile = function(name, ok, showError) {
                    var pos = this.fileNameExists(name);
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
                    VPLUtil.delay('updateFileList', self.updateFileList);
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
                    var tag = $(a);
                    var fname = tag.data( 'file' );
                    var fpos = -1;
                    if (fname > '') {
                        fpos = this.fileNameExists( fname );
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
                    VPLUtil.delay('updateMenu', updateMenu);
                    VPLUtil.delay('updateFileList', self.updateFileList);
                };
                this.setModified = function() {
                    if (!modified) {
                        modified = true;
                        VPLUtil.delay('updateFileList', self.updateFileList);
                    }
                    VPLUtil.delay('updateMenu', updateMenu);
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
                                    lines.push( indent + VPLUtil.iconFolder() + VPLUtil.sanitizeText(name) );
                                    lister(fd, indent + dirIndent, lines);
                                } else {
                                    var file = fd.content;
                                    var sname = VPLUtil.sanitizeText( name );
                                    var path = VPLUtil.sanitizeText( file.getFileName() );
                                    if ( file.isOpen() ) {
                                        sname = '<b>' + sname + '</b>';
                                    }
                                    var attrs = 'href="#" data-fileid="' + file.getId() + '" title="' + path + '"';
                                    var line = '<a ' + attrs + '>' + sname + '</a>';
                                    if (file.isModified()) {
                                        line = VPLUtil.iconModified() + line;
                                    }
                                    if (fd.pos < minNumberOfFiles) {
                                        line = line + VPLUtil.iconRequired();
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
                    fileListContent.html('<div>' + html + '</div>');
                };
                tabsUl.on('click', 'span.vpl_ide_closeicon', function() {
                    fileManager.close(fileManager.currentFile());
                });
                tabsUl.on('dblclick', 'span.vpl_ide_closeicon', menuButtons.getAction('delete'));
                tabsUl.on('dblclick', 'a',  menuButtons.getAction('rename'));
                fileListContent.on('dblclick', 'a',  menuButtons.getAction('rename'));

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
            this.getTerminal = function() {
                return terminal;
            };
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
                var HTMLcontent = $('<div>' + content + '</div>');
                HTMLcontent.find('h4').replaceWith(function () {
                    return $("<h5>").append($(this).contents());
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
                var files = fileManager.getFiles();
                var fileNames = [];
                var i;
                for (i = 0; i < files.length; i++) {
                    fileNames [i] = files[i].getFileName();
                    files[i].clearAnnotations();
                }
                var show = false;
                var hasContent;
                var grade = VPLUtil.sanitizeText(res.grade);
                var gradeShow;
                var formated;
                gradeShow = self.setResultGrade(grade, res.grade);
                show = show || gradeShow;
                hasContent = self.setResultTab('variables', res.variables, res.variables);
                show = show || hasContent;
                formated = VPLUtil.processResult( res.compilation, fileNames, files, true, false );
                hasContent = self.setResultTab('compilation', formated, res.compilation);
                show = show || hasContent;
                formated = VPLUtil.processResult( res.evaluation, fileNames, files, false, false );
                hasContent = self.setResultTab('comments', formated, res.evaluation);
                show = show || hasContent;
                formated = VPLUtil.sanitizeText(res.execution);
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
                                fileManager.gotoFile(i, anot[j].row + 1);
                                break;
                            }
                        }
                    }
                } else {
                    result_container.hide();
                    result_container.vpl_visible = false;
                }
                VPLUtil.delay('autoResizeTab', autoResizeTab);
            };

            result.accordion({
                heightStyle : 'fill',
                header : 'h4',
                animate: false,
                beforeActivate : avoidSelectGrade,
            });
            result_container.width(2 * result_container.vpl_minWidth);
            result.on('click', 'a', function(event) {
                event.preventDefault();
                fileManager.gotoFileLink(event.currentTarget);
            });
            result_container.vpl_visible = false;
            result_container.hide();

            fileListContainer.addClass('ui-tabs ui-widget ui-widget-content ui-corner-all');
            fileList.text(str('filelist'));
            fileList.html(VPLUtil.iconFolder() + fileList.html());
            fileList.addClass("ui-widget-header ui-button-text-only ui-corner-all");
            fileListContent.addClass("ui-widget ui-corner-all");
            fileListContainer.width(2 * fileListContainer.vpl_minWidth);
            fileListContainer.on('click', 'a', function(event) {
                event.preventDefault();
                fileManager.gotoFileLink(event.currentTarget);
            });
            fileListContainer.vpl_visible = false;
            fileListContainer.hide();
            tabs.tabs({classes: {"ui-tabs-panel" : null}});
            var tabsAir = false;
            function getTabsAir() {
                if (tabsAir === false) {
                    tabsAir = (tabs.outerWidth(true) - tabs.width()) / 2;
                }
                return tabsAir;
            }
            function resizeTabWidth(e, ui) {
                var diffLeft = ui.position.left - ui.originalPosition.left;
                var maxWidth;
                if (diffLeft !== 0) {
                    maxWidth = tabs.width() + fileListContainer.width() - fileListContainer.vpl_minWidth;
                    tabs.resizable('option', 'maxWidth', maxWidth);
                    fileListContainer.width(fileListContainer.vpl_original_width + diffLeft);
                } else {
                    maxWidth = tabs.width() + result_container.width() - result_container.vpl_minWidth;
                    tabs.resizable('option', 'maxWidth', maxWidth);
                    var diff_width = ui.size.width - ui.originalSize.width;
                    result_container.width(result_container.vpl_original_width - diff_width);
                }
                fileManager.currentFile('adjustSize');
            }
            var resizableOptions = {
                containment : 'parent',
                resize : resizeTabWidth,
                start : function() {
                    $(window).off('resize', autoResizeTab);
                    tabs.resizable('option', 'minWidth', 100);
                    if (result_container.vpl_visible) {
                        result_container.vpl_original_width = result_container.width();
                    }
                    if (fileListContainer.vpl_visible) {
                        fileListContainer.vpl_original_width = fileListContainer.width();
                    }
                },
                stop : function(e, ui) {
                    resizeTabWidth(e, ui);
                    tabs.resizable('option', 'maxWidth', 100000);
                    tabs.resizable('option', 'minWidth', 0);
                    autoResizeTab();
                    $(window).on('resize', autoResizeTab);
                },
                handles : ""
            };
            tabs.resizable(resizableOptions);
            function updateTabsHandles() {
                var handles = [ 'e', 'w', 'e', 'e, w' ];
                var index = 0;
                index += fileListContainer.vpl_visible ? 1 : 0;
                index += result_container.vpl_visible ? 2 : 0;
                tabs.resizable('destroy');
                resizableOptions.handles = handles[index];
                resizableOptions.disable = index === 0;
                tabs.resizable(resizableOptions);
            }
            function resizeHeight() {
                var newHeight = $(window).outerHeight();
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
                if (fileListContainer.vpl_visible) {
                    fileListContent.height( panelHeight - (fileList.outerHeight() + getTabsAir()));
                    fileListContainer.height( panelHeight );
                }
            }
            adjustTabsTitles = function(center) {
                var newWidth = tabs.width();
                var tabsUlWidth = 0;
                tabsUl.width(100000);
                var last = tabsUl.children('li:visible').last();
                if (last.length) {
                    var parentScrollLeft = tabsUl.parent().scrollLeft();
                    tabsUlWidth = parentScrollLeft + last.position().left + last.width() + tabsAir;
                    tabsUl.width(tabsUlWidth);
                    var file = fileManager.currentFile();
                    if (file && center) {
                        var fileTab = $(file.getTabNameId());
                        var scroll = parentScrollLeft + fileTab.position().left;
                        scroll -= (newWidth - fileTab.outerWidth()) / 2;
                        if (scroll < 0) {
                            scroll = 0;
                        }
                        tabsUl.parent().finish().animate({
                            scrollLeft : scroll
                        }, 'slow');
                    }
                }
                if (tabsUlWidth < newWidth) {
                    tabsUl.width('');
                }
            };
            autoResizeTab = function () {
                var oldWidth = tabs.width();
                var newWidth = menu.width();
                var planb = false;
                updateTabsHandles();
                tr.width(menu.outerWidth());
                if (fileListContainer.vpl_visible) {
                    var left = fileListContainer.outerWidth() + tabsAir;
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
                    if (fileListContainer.vpl_visible) {
                        wfl = fileListContainer.width() * rel;
                        fileListContainer.width(wfl - tabsAir);
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
                fileManager.currentFile('adjustSize');
            };
            function focusCurrentFile() {
                fileManager.currentFile('focus');
            }
            var dialogbaseOptions = $.extend({}, {
                close : focusCurrentFile
            }, VPLUtil.dialogbaseOptions);
            function showMessage(message, options) {
                return VPLUtil.showMessage(message, $.extend({}, dialogbaseOptions, options));
            }
            showErrorMessage = function(message) {
                return VPLUtil.showErrorMessage(message, {
                    close : focusCurrentFile
                });
            };

            var dialogNew = $('#vpl_ide_dialog_new');
            function newFileHandler(event) {
                if (!(event.type == 'click' || ((event.type == 'keypress') && event.keyCode == 13))) {
                    return;
                }
                dialogNew.dialog('close');
                var file = {
                    name:$('#vpl_ide_input_newfilename').val(),
                    contents:'',
                    encoding:0
                };
                var newfile = fileManager.addFile(file, false, updateMenu, showErrorMessage);
                if (newfile) {
                    fileManager.open(newfile);
                    tabs.tabs('option', 'active', fileManager.getTabPos(newfile));
                    newfile.focus();
                }
                return false;
            }

            var dialogButtons = {};
            dialogButtons[str('ok')] = newFileHandler;
            dialogButtons[str('cancel')] = function() {
                $(this).dialog('close');
            };
            dialogNew.find('input').on('keypress', newFileHandler);
            dialogNew.dialog($.extend({}, dialogbaseOptions, {
                title : str('create_new_file'),
                buttons : dialogButtons
            }));

            var dialogRename = $('#vpl_ide_dialog_rename');
            function renameHandler(event) {
                if (!(event.type == 'click' || ((event.type == 'keypress') && event.keyCode == 13))) {
                    return;
                }
                dialogRename.dialog('close');
                fileManager.renameFile(fileManager.currentFile('getFileName'),
                        $('#vpl_ide_input_renamefilename').val(), showErrorMessage);
                event.preventDefault();
            }
            dialogRename.find('input').on('keypress', renameHandler);
            dialogButtons[str('ok')] = renameHandler;
            dialogRename.dialog($.extend({}, dialogbaseOptions, {
                open : function() {
                    $('#vpl_ide_input_renamefilename').val(fileManager.currentFile('getFileName'));
                },
                title : str('rename_file'),
                buttons : dialogButtons
            }));
            dialogButtons[str('ok')] = function(){
                $(this).dialog('close');
            };
            var dialogComments = $('#vpl_ide_dialog_comments');
            dialogComments.dialog($.extend({}, dialogbaseOptions, {
                title : str('comments'),
                width : '40em',
                buttons : dialogButtons
            }));
            $('#vpl_ide_input_comments').width('30em');
            var aboutDialog = $('#vpl_ide_dialog_about');
            var OKButtons = {};
            OKButtons[str('ok')] = function() {
                $(this).dialog('close');
            };
            var shortcutDialog = $('#vpl_ide_dialog_shortcuts');
            shortcutDialog.dialog($.extend({}, dialogbaseOptions, {
                open: function(){
                    var html = menuButtons.getShortcuts(fileManager.currentFile('getEditor'));
                    $('#vpl_ide_dialog_shortcuts').html(html);
                },
                title : str('shortcuts'),
                width : 400,
                height: 300,
                buttons : OKButtons
            }));
            shortcutDialog.dialog('option', 'height', 300);
            OKButtons[str('shortcuts')] = function() {
                $(this).dialog('close');
                shortcutDialog.dialog('open');
            };
            aboutDialog.dialog($.extend({}, dialogbaseOptions, {
                open: function(){
                    var html = menuButtons.getShortcuts(fileManager.currentFile('getEditor'));
                    aboutDialog.next().find("button").filter(function() {
                        return $(this).text() == str('shortcuts');
                      }).button(html != '' ? 'enable' : 'disable');
                },
                title : str('about'),
                width : 400,
                height: 300,
                buttons : OKButtons
            }));
            aboutDialog.dialog('option', 'height', 300);
            var dialogSort = $('#vpl_ide_dialog_sort');
            var dialogSortButtons = {};
            dialogSortButtons[str('ok')] = function() {
                var files = fileManager.getFiles();
                var regNoNumber = /[^\d]*/;
                var sorted = [];
                var i = 0;
                var newOrder = $('#vpl_sort_list li');
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
                fileManager.setModified();
                VPLUtil.delay('updateMenu', updateMenu);
                VPLUtil.delay('updateFileList', fileManager.updateFileList);
                $(this).dialog('close');
            };
            dialogSortButtons[str('cancel')] = function() {
                $(this).dialog('close');
            };
            dialogSort.dialog($.extend({}, dialogbaseOptions, {
                title : str('sort'),
                buttons : dialogSortButtons,
                open : function() {
                    var list = $('#vpl_sort_list');
                    list.html('');
                    var files = fileManager.getFiles();
                    for (var i = 0; i < files.length; i++) {
                        var file = $('<li id="vpl_fsort_' + i + '"class="ui-widget-content"></li>');
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
            var dialogMultidelete = $('#vpl_ide_dialog_multidelete');
            var dialogMultideleteButtons = {};
            dialogMultideleteButtons[str('selectall')] = function() {
                $(this).find('input').prop( "checked", true );
            };
            dialogMultideleteButtons[str('deselectall')] = function() {
                $(this).find('input').prop( "checked", false );
            };
            dialogMultideleteButtons[str('deleteselected')] = function() {
                var files = fileManager.getFiles();
                var toDeleteList = [];
                var labelList = $('#vpl_multidelete_list label');
                labelList.each(function() {
                    var label = $(this);
                    if ( label.find('input').prop('checked') ) {
                        var id = label.data('fileid');
                        toDeleteList.push(files[id].getFileName());
                    }
                });
                for (var i = 0; i < toDeleteList.length; i++) {
                    fileManager.deleteFile(toDeleteList[i], false, showErrorMessage);
                }
                VPLUtil.delay('updateMenu', updateMenu);
                VPLUtil.delay('updateFileList', fileManager.updateFileList);
                $(this).dialog('close');
            };
            dialogMultideleteButtons[str('cancel')] = function() {
                $(this).dialog('close');
            };
            dialogMultidelete.dialog($.extend({}, dialogbaseOptions, {
                title : str('multidelete'),
                buttons : dialogMultideleteButtons,
                open : function() {
                    var list = $('#vpl_multidelete_list');
                    list.html('');
                    var files = fileManager.getFiles();
                    for (var i = minNumberOfFiles; i < files.length; i++) {
                        var name = VPLUtil.sanitizeText(files[i].getFileName());
                        var file = $('<label><input type="checkbox"> ' + name + '</label>');
                        file.data('fileid', i);
                        list.append(file);
                        list.append('<br>');
                    }
                    list.find('label').button();
                },
                maxHeight : 400,
                maxWidth : 400
            }));
            var dialogFontsize = $('#vpl_ide_dialog_fontsize');
            var fontsizeSlider = $('#vpl_ide_dialog_fontsize .vpl_fontsize_slider');
            var dialogFontFizeButtons = {};
            dialogFontFizeButtons[str('ok')] = function() {
                var value = fontsizeSlider.slider( "value");
                fileManager.setFontSize(value);
                $(this).dialog('close');
                $.ajax({
                    async : true,
                    type : "POST",
                    url : '../editor/userpreferences.json.php',
                    'data' : JSON.stringify({fontSize:value}),
                    contentType : "application/json; charset=utf-8",
                    dataType : "json"
                });
            };
            dialogFontFizeButtons[str('cancel')] = function() {
                fileManager.setFontSize(fontsizeSlider.data( "vpl_fontsize" ));
                $(this).dialog('close');
            };
            dialogFontFizeButtons[str('reset')] = function() {
                fontsizeSlider.slider('value', 12);
            };
            dialogFontsize.dialog($.extend({}, dialogbaseOptions, {
                title : str('fontsize'),
                buttons : dialogFontFizeButtons,
                open : function() {
                    fontsizeSlider.data( "vpl_fontsize" , fileManager.getFontSize() );
                    fontsizeSlider.slider('value', fileManager.getFontSize());
                },
            }));
            fontsizeSlider.slider({
                min: 1,
                max: 48,
                change: function() {
                    var value = fontsizeSlider.slider( "value");
                    fileManager.setFontSize( value );
                    dialogFontsize.find('.vpl_fontsize_slider_value').text( value );
                }
            });
            var dialogAceTheme = $('#vpl_ide_dialog_acetheme');
            var acethemeSelect = $('#vpl_ide_dialog_acetheme select');
            var dialogAceThemeButtons = {};
            dialogAceThemeButtons[str('ok')] = function() {
                fileManager.setTheme(acethemeSelect.val());
                $(this).dialog('close');
                $.ajax({
                    async : true,
                    type : "POST",
                    url : '../editor/userpreferences.json.php',
                    'data' : JSON.stringify({aceTheme:acethemeSelect.val()}),
                    contentType : "application/json; charset=utf-8",
                    dataType : "json"
                });
            };
            dialogAceThemeButtons[str('cancel')] = function() {
                fileManager.setTheme(acethemeSelect.data( "acetheme" ));
                $(this).dialog('close');
            };
            dialogAceThemeButtons[str('reset')] = function() {
                acethemeSelect.val(acethemeSelect.data( "acetheme" ));
                fileManager.setTheme(acethemeSelect.val());
            };
            dialogAceTheme.dialog($.extend({}, dialogbaseOptions, {
                title : str('theme'),
                buttons : dialogAceThemeButtons,
                modal: false,
                open : function() {
                    acethemeSelect.data( "acetheme", fileManager.getTheme() );
                    acethemeSelect.val(fileManager.getTheme());
                },
            }));
            acethemeSelect.on('change', function() {
                    fileManager.setTheme(acethemeSelect.val());
            });
            var terminal = new VPLTerminal('vpl_dialog_terminal', 'vpl_terminal', str);
            var VNCClient = new VPLVNCClient('vpl_dialog_vnc', str);
            var lastConsole = terminal;
            var fileSelect = $('#vpl_ide_input_file');
            var fileSelectHandler = function() {
                VPLUtil.readSelectedFiles(this.files, function(file) {
                    return fileManager.addFile(file, true, updateMenu, showErrorMessage);
                },
                function(){
                    fileManager.fileListVisibleIfNeeded();
                });
            };
            fileSelect.on('change', fileSelectHandler);
            // Menu acctions.
            menuButtons.add({
                name:'filelist',
                originalAction: function() {
                    fileManager.fileListVisible(!fileManager.isFileListVisible());
                    VPLUtil.delay('updateMenu', updateMenu);
                    VPLUtil.delay('autoResizeTab', autoResizeTab);
                    VPLUtil.delay('updateFileList', fileManager.updateFileList);
                },
                bindKey: {
                    win: 'Ctrl-L',
                    mac: 'Ctrl-L'
                }
            });

            menuButtons.add({
                name: 'new',
                originalAction: function() {
                    if (fileManager.length() < maxNumberOfFiles) {
                        dialogNew.dialog('open');
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
                    var file = fileManager.currentFile();
                    if (file && fileManager.getFilePosById(file.getId()) >= minNumberOfFiles) {
                        dialogRename.dialog('open');
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
                    var file = fileManager.currentFile();
                    if (!file) {
                        return;
                    }
                    var filename = file.getFileName();
                    var message = str('delete_file_fq').replace(/\{\$a\}/g, filename);
                    showMessage(message, {
                        ok : function() {
                            fileManager.deleteFile(filename, showErrorMessage);
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
                    var file = fileManager.currentFile();
                    if (!file) {
                        return;
                    }
                    fileManager.close(file);
                },
                bindKey:{
                    win: 'Alt-W',
                    mac: 'Option-W'
                }
            });
            menuButtons.add({
                name:'import',
                originalAction: function() {
                    fileSelect.val('');
                    fileSelect.trigger('click');
                },
                bindKey:{
                    win: 'Ctrl-I',
                    mac: 'Ctrl-I'
                }
            });
            menuButtons.add({
                name:'sort',
                originalAction: function() {
                    dialogSort.dialog('open');
                },
                bindKey:{
                    win: 'Ctrl-O',
                    mac: 'Ctrl-O'
                }
            });
            menuButtons.add({
                name:'multidelete',
                originalAction: function() {
                    dialogMultidelete.dialog('open');
                }
            });
            menuButtons.add({
                name:'fontsize',
                originalAction: function() {
                    dialogFontsize.dialog('open');
                }
            });
            menuButtons.add({
                name:'acetheme',
                originalAction: function() {
                    dialogAceTheme.dialog('open');
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
                    fileManager.currentFile('undo');
                }
            });
            menuButtons.add({
                name:'redo',
                originalAction: function() {
                    fileManager.currentFile('redo');
                }
            });
            menuButtons.add({
                name:'select_all',
                editorName:'selectall',
                originalAction: function() {
                    fileManager.currentFile('selectAll');
                }
            });
            menuButtons.add({
                name:'find',
                originalAction: function() {
                    fileManager.currentFile('find');
                }
            });
            menuButtons.add({
                name:'find_replace',
                editorName:'replace',
                originalAction: function() {
                    fileManager.currentFile('replace');
                }
            });
            menuButtons.add({
                name:'next',
                editorName:'findnext',
                originalAction: function() {
                    fileManager.currentFile('next');
                }
            });
            menuButtons.add({
                name:'fullscreen',
                originalAction: function() {
                    var tags = 'header, footer, aside, #page-header, div.navbar, #nav-drawer';
                    tags += ', div.tabtree, #dock, .breadcrumb-nav, .moodle-actionmenu';
                    if (fullScreen) {
                        rootObj.removeClass('vpl_ide_root_fullscreen');
                        $('body').removeClass('vpl_body_fullscreen');
                        menuButtons.setText('fullscreen', 'fullscreen');
                        $(tags).show();
                        $('#vpl_ide_user').hide();
                        fullScreen = false;
                    } else {
                        $('body').addClass('vpl_body_fullscreen').scrollTop(0);
                        $(tags).hide();
                        rootObj.addClass('vpl_ide_root_fullscreen');
                        menuButtons.setText('fullscreen', 'regularscreen');
                        if ( options.username ) {
                            $('#vpl_ide_user').show();
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
                VPLUtil.requestAction('resetfiles', '', {}, options.ajaxurl)
                .done( function(response) {
                    var files = response.files;
                    for (var fileName in files) {
                        if ( files.hasOwnProperty(fileName) ) {
                            fileManager.addFile(files[fileName], true, VPLUtil.doNothing, showErrorMessage);
                        }
                    }
                    fileManager.fileListVisibleIfNeeded();
                    VPLUtil.delay('updateMenu', updateMenu);
                }).fail(showErrorMessage);
            }

          function correctedFiles() {
                VPLUtil.requestAction('correctedfiles', '', {}, options.ajaxurl)
                .done( function(response) {
                    var files = response.files;
                    for (var fileName in files) {
                        fileManager.addFile(files[fileName], true, VPLUtil.doNothing, showErrorMessage);
                    }
                    fileManager.fileListVisibleIfNeeded();
                    //VPLUtil.delay(updateMenu);
                 }).fail(showErrorMessage);
            }

            menuButtons.add({
                name:'correctedfiles',
                originalAction: function() {
                    showMessage(str('surecorrectedfiles'), {
                        title : str('correctedfiles'),
                        ok : correctedFiles
                    });
                }
            });

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
                        files: fileManager.getFilesToSave(),
                        comments: $('#vpl_ide_input_comments').val()
                    };
                    VPLUtil.requestAction('save', 'saving', data, options.ajaxurl)
                    .done( function(response) {
                        fileManager.resetModified();
                        menuButtons.setTimeLeft(response);
                        VPLUtil.delay('updateMenu', updateMenu);
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
                    VPLUtil.requestAction(action, '', data, options.ajaxurl)
                    .done(function(response) {
                        VPLUtil.webSocketMonitor(response, action, acting, executionActions);
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
                    dialogComments.dialog('open');
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
                    var tag = $('#vpl_ide_menuextra');
                    if ( tag.is(":visible") ) {
                        menuButtons.setText('more', 'more', VPLUtil.str('more'));
                        tag.hide();
                    } else {
                        menuButtons.setText('more', 'less', VPLUtil.str('less'));
                        tag.show();
                    }
                    VPLUtil.delay('updateMenu', updateMenu);
                    VPLUtil.delay('autoResizeTab', autoResizeTab);
                }
            });
            menu.addClass("ui-widget-header ui-corner-all");
            var menuHtml = "";
            menuHtml += menuButtons.getHTML('more');
            menuHtml += menuButtons.getHTML('save');
            menuHtml += "<span id='vpl_ide_mexecution'>";
            menuHtml += menuButtons.getHTML('run');
            menuHtml += menuButtons.getHTML('debug');
            menuHtml += menuButtons.getHTML('evaluate');
            menuHtml += menuButtons.getHTML('comments');
            menuHtml += menuButtons.getHTML('console');
            menuHtml += "</span> ";
            menuHtml += "<span id='vpl_ide_menuextra'>";
            menuHtml += "<span id='vpl_ide_file'>";
            // TODO autosave not implemented.
            menuHtml += menuButtons.getHTML('filelist');
            menuHtml += menuButtons.getHTML('new');
            menuHtml += menuButtons.getHTML('rename');
            menuHtml += menuButtons.getHTML('delete');
            menuHtml += menuButtons.getHTML('import');
            menuHtml += menuButtons.getHTML('download');
            menuHtml += menuButtons.getHTML('resetfiles');
            menuHtml += menuButtons.getHTML('correctedfiles');
            menuHtml += menuButtons.getHTML('sort');
            menuHtml += menuButtons.getHTML('multidelete');
            menuHtml += menuButtons.getHTML('fontsize');
            menuHtml += menuButtons.getHTML('acetheme');
            menuHtml += "</span> ";
            // TODO print still not implemented.
            menuHtml += "<span id='vpl_ide_edit'>";
            menuHtml += menuButtons.getHTML('undo');
            menuHtml += menuButtons.getHTML('redo');
            menuHtml += menuButtons.getHTML('select_all');
            menuHtml += menuButtons.getHTML('find');
            menuHtml += menuButtons.getHTML('find_replace');
            menuHtml += menuButtons.getHTML('next');
            menuHtml += "</span> ";
            menuHtml += "</span> ";
            menuHtml += menuButtons.getHTML('fullscreen') + ' ';
            menuHtml += menuButtons.getHTML('about') + ' ';
            menuHtml += menuButtons.getHTML('user') + ' ';
            menuHtml += menuButtons.getHTML('timeleft');
            menuHtml += '<div class="clearfix"></div>';
            menu.append(menuHtml);
            $('#vpl_ide_more').button();
            $('#vpl_ide_save').button();
            $('#vpl_ide_menuextra').hide();
            $('#vpl_ide_file').controlgroup();
            $('#vpl_ide_edit').controlgroup();
            $('#vpl_ide_mexecution').controlgroup();
            $('#vpl_ide_fullscreen').button();
            $('#vpl_ide_acetheme').button();
            $('#vpl_ide_about').button();
            $('#vpl_ide_user').button().css('float','right').hide();
            $('#vpl_ide_timeleft').button().css('float','right').hide();
            $('#vpl_menu .ui-button').css('padding','6px');
            $('#vpl_menu .ui-button-text').css('padding','0');
            var alwaysActive = ['filelist', 'more', 'fullscreen', 'about', 'resetfiles',
                                'download', 'comments', 'console','import',
                                'fontsize'];
            for (var i = 0; i < alwaysActive.length; i++) {
                menuButtons.enable(alwaysActive[i], true);
            }
            menuButtons.setExtracontent('user', options.username);
            menuButtons.setTimeLeft(options);
            updateMenu = function() {
                var i;
                var file = fileManager.currentFile();
                var nfiles = fileManager.length();
                if (nfiles) {
                    tabs.show();
                } else {
                    tabs.hide();
                }
                if (fileManager.isFileListVisible()) {
                    menuButtons.setText('filelist', 'filelistclose', VPLUtil.str('filelist'));
                } else {
                    menuButtons.setText('filelist', 'filelist', VPLUtil.str('filelist'));
                }
                VPLUtil.log('updateMenu', true);
                var modified = fileManager.isModified();
                menuButtons.enable('save', modified);
                menuButtons.enable('run', !modified);
                menuButtons.enable('debug', !modified);
                menuButtons.enable('evaluate', !modified);
                menuButtons.enable('download', !modified);
                menuButtons.enable('new', nfiles < maxNumberOfFiles);
                menuButtons.enable('sort', nfiles - minNumberOfFiles > 1);
                menuButtons.enable('multidelete', nfiles - minNumberOfFiles > 1);
                menuButtons.enable('acetheme', true);
                menuButtons.enable('correctedfiles', options.correctedfiles);
                var sel;
                if (!file || nfiles === 0) {
                    sel = [ 'rename', 'delete', 'undo', 'redo', 'select_all', 'find', 'find_replace', 'next' ];
                    for (i = 0; i < sel.length; i++) {
                        menuButtons.enable(sel[i], false);
                    }
                    return;
                }
                var id = fileManager.getFilePosById(file.getId());
                menuButtons.enable('rename', id >= minNumberOfFiles && nfiles !== 0);
                menuButtons.enable('delete', id >= minNumberOfFiles && nfiles !== 0);
                menuButtons.enable('undo', file.hasUndo());
                menuButtons.enable('redo', file.hasRedo());
                menuButtons.enable('select_all', file.hasSelectAll());
                menuButtons.enable('find', file.hasFind());
                menuButtons.enable('find_replace', file.hasFindReplace());
                menuButtons.enable('next', file.hasNext());
                VPLUtil.delay('updateFileList', fileManager.updateFileList);
            };

            tabs.on("tabsactivate", function() {
                fileManager.currentFile('focus');
                VPLUtil.delay('updateMenu', updateMenu);
                VPLUtil.delay('autoResizeTab', autoResizeTab);
            });

            // VPLIDE resize view control.
            var jw = $(window);
            jw.on('resize', autoResizeTab);
            // Save? before exit.
            if (!options.example) {
                jw.on('beforeunload', function() {
                    if (fileManager.isModified()) {
                        return str('changesNotSaved');
                    }
                });
            }
            fileManager = new FileManager();

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
            fileManager.resetModified();
            VPLUtil.requestAction('load', 'loading', options, options.loadajaxurl)
            .done(function(response) {
                var allOK = true;
                var files = response.files;
                for (var i = 0; i < files.length; i++) {
                    var file = files[i];
                    var r = fileManager.addFile(file, false, updateMenu, showErrorMessage);
                    if (r) {
                        r.resetModified();
                        if (i < minNumberOfFiles || files.length <= 5) {
                            fileManager.open(r);
                        } else {
                            fileManager.fileListVisible(true);
                        }
                    } else {
                        allOK = false;
                    }
                }
                VPLUtil.delay('updateMenu', updateMenu);
                fileManager.generateFileList();
                tabs.tabs('option', 'active', 0);

                if(response.compilationexecution){
                    self.setResult(response.compilationexecution,false);
                }
                menuButtons.setTimeLeft(response);
                if( response.comments > '') {
                    $('#vpl_ide_input_comments').val(response.comments);
                }
                if (allOK) {
                    fileManager.resetModified();
                } else {
                    fileManager.setModified();
                }
                if (fileManager.length() === 0 && maxNumberOfFiles > 0) {
                    menuButtons.getAction('new')();
                } else if ( ! options.saved ) {
                    fileManager.setModified();
                }
            })
            .fail(showErrorMessage);
        };
        window.VPLIDE = VPLIDE;
        return {
            init: function(root_id, options) {
                vplIdeInstance = new VPLIDE(root_id, options);
            }
        };
    }
);