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
 *
 * @copyright 2017 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/* globals MathJax */

define(
    [
        'jquery',
        'jqueryui',
        'mod_vpl/vplutil',
        'mod_vpl/vplidefile',
        'mod_vpl/vplidebutton',
        'mod_vpl/vplterminal',
        'mod_vpl/vplvnc',
    ],
    function($, jqui, VPLUtil, VPLFile, VPLIDEButtons, VPLTerminal, VPLVNCClient) {
        if (typeof VPLIDE !== 'undefined') {
            return VPLIDE;
        }
        var vplIdeInstance;
        var VPLIDE = function(rootId, options) {
            var self = this;
            var fileManager;
            var adjustTabsTitles;
            var autoResizeTab;
            var showErrorMessage;
            var updateMenu;
            var executionActions;
            var minNumberOfFiles = options.minfiles || 0;
            var maxNumberOfFiles = options.maxfiles || 0;
            var restrictedEdit = options.restrictededitor || options.example;
            var readOnly = options.example;
            var isTeacher = options.isTeacher;
            var fullScreen = false;
            var scrollBarWidth = VPLUtil.scrollBarWidth();
            var str = VPLUtil.str;
            var rootObj = $('#' + rootId);
            $("head").append('<meta name="viewport" content="initial-scale=1">')
                          .append('<meta name="viewport" width="device-width">')
                          .append('<link rel="stylesheet" href="../editor/VPLIDE.css"/>');
            if (typeof rootObj != 'object') {
                throw new Error("VPL: constructor tag_id not found");
            }
            var optionsToCheck = {
                'new': true,
                'rename': true,
                'delete': true,
                'save': true,
                'run': true,
                'edit': true,
                'debug': true,
                'evaluate': true,
                'import': true,
                'resetfiles': true,
                'sort': true,
                'multidelete': true,
                'acetheme': true,
                'console': true,
                'comments': true
            };
            if ((typeof options.loadajaxurl) == 'undefined') {
                options.loadajaxurl = options.ajaxurl;
            }
            (function() {
                var activateModification = (minNumberOfFiles < maxNumberOfFiles);
                options.new = activateModification;
                options.rename = activateModification;
                options.delete = activateModification;
                options.comments = options.comments && !options.example;
                options.acetheme = true;
            })();
            options.sort = (maxNumberOfFiles - minNumberOfFiles >= 2);
            options.multidelete = options.sort;
            options.import = !restrictedEdit;
            var isOptionAllowed = function(op) {
                if (!optionsToCheck[op]) {
                    return true;
                }
                return options[op];
            };
            options.console = isOptionAllowed('run') || isOptionAllowed('debug');
            if ((typeof options.fontSize) == 'undefined') {
                options.fontSize = 12;
            }
            options.fontSize = parseInt(options.fontSize);
            /**
             * Handler for dragover event.
             * @param {object} e event.
             */
            function dragoverHandler(e) {
                if (restrictedEdit) {
                    e.originalEvent.dataTransfer.dropEffect = 'none';
                } else {
                    e.originalEvent.dataTransfer.dropEffect = 'copy';
                }
                e.preventDefault();
            }
            /**
             * Handler for drop event.
             * @param {object} e event.
             * @returns {boolean}
             */
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
                    function() {
                        fileManager.fileListVisibleIfNeeded();
                    });
                    e.stopImmediatePropagation();
                    return false;
                }
                return false;
            }
            rootObj.on('drop', dropHandler);
            rootObj.on('dragover', dragoverHandler);
            /**
             * Handler for paste limited by restrictedEdit var.
             * @param {object} e event.
             * @returns {boolean}
             */
            function restrictedPaste(e) {
                if (restrictedEdit) {
                    e.stopPropagation();
                    return false;
                }
                return true;
            }
            // Init editor vars.
            var menu = $('#vpl_menu');
            var menuButtons = new VPLIDEButtons(menu, isOptionAllowed);
            var tr = $('#vpl_tr');
            var fileListContainer = $('#vpl_filelist');
            var fileList = $('#vpl_filelist_header');
            var fileListContent = $('#vpl_filelist_content');
            var tabsUl = $('#vpl_tabs_ul');
            var tabs = $('#vpl_tabs');
            var resultContainer = $('#vpl_results');
            var result = $('#vpl_results_accordion');
            fileListContainer.vplMinWidth = 80;
            resultContainer.vplMinWidth = 100;
            /**
             * Avoids selecting grade.
             * @param {object} event Unuse.
             * @param {object} ui UI origen.
             * @returns {boolean}
             */
            function avoidSelectGrade(event, ui) {
                if ("newHeader" in ui) {
                    if (ui.newHeader.hasClass('vpl_ide_accordion_t_grade')) {
                        return false;
                    }
                }
                return true;
            }
            /**
             * Constructor of FileManager objects
             */
            function FileManager() {
                var tabsUl = $('#vpl_tabs_ul');
                $('#vpl_tabs').tabs();
                var tabs = $('#vpl_tabs').tabs("widget");
                var files = [];
                var openFiles = [];
                var modified = true;
                var self = this;
                (function() {
                    var version;
                    self.setVersion = function(v) {
                       version = v;
                    };
                    self.getVersion = function() {
                       return version;
                    };
                })();
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
                /**
                 * Checks if name is included in current files names
                 * @param {string} name Name of file
                 * @returns {boolean} if found or not found
                 */
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
                /**
                 * Checks if changing file name results in two blovkly files
                 * @param {string} oldname The old file name
                 * @param {string} newname The new file name
                 * @returns {boolean} if results two two blovkly files
                 */
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
                        if (openFiles[i] == file) {
                            return i;
                        }
                    }
                    return openFiles.length;
                };
                this.getTheme = function() {
                    return options.theme;
                };
                this.setTheme = function(theme) {
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
                    if (file.isOpen()) {
                        return;
                    }
                    var fid = file.getId();
                    self.addTab(fid);
                    openFiles.push(file);
                    menuButtons.setGetkeys(file.open());
                    tabs.tabs('refresh');
                    adjustTabsTitles(false);
                    VPLUtil.delay('updateFileList', self.updateFileList);
                    VPLUtil.delay('updateMenu', updateMenu);
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
                    if (b === fileListContainer.vplVisible) {
                        return;
                    }
                    if (b) {
                        VPLUtil.delay('fileListVisible', function() {
                            fileListContainer.vplVisible = true;
                            self.updateFileList();
                            fileListContainer.show();
                            autoResizeTab();
                         });
                    } else {
                        VPLUtil.delay('fileListVisible', function() {
                            fileListContainer.vplVisible = false;
                            fileListContainer.hide();
                            autoResizeTab();
                         });
                    }
                };
                this.isFileListVisible = function() {
                    return fileListContainer.vplVisible;
                };
                this.fileListVisibleIfNeeded = function() {
                    if (this.isFileListVisible()) {
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
                    terminal.setFontSize(size);
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
                            showError(str('filenotadded', file.name));
                            return false;
                        }
                    }
                    if (fileNameIncluded(file.name) || twoBlockly('', file.name)) {
                        showError(str('filenotadded', file.name));
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
                    newfile.setFileName(file.name);
                    files.push(newfile);
                    self.setModified();
                    if (files.length > 5) {
                        self.fileListVisible(true);
                    }
                    ok();
                    return newfile;
                };
                this.renameFile = function(oldname, newname, showError) {
                    var pos = this.fileNameExists(oldname);
                    try {
                        if (pos == -1) {
                            throw new Error("Internal error: File name not found");
                        }
                        if (pos < minNumberOfFiles) {
                            throw new Error("Internal error: Renaming requested filename");
                        }
                        if (files[pos].getFileName() == newname) {
                            return true; // Equals name file.
                        }
                        if (!VPLUtil.validPath(newname) ||
                               fileNameIncluded(newname) ||
                               twoBlockly(oldname, newname)) {
                            throw str('incorrect_file_name');
                        }
                        if (VPLUtil.isBinary(oldname) && VPLUtil.fileExtension(oldname) != VPLUtil.fileExtension(newname)) {
                            throw str('incorrect_file_name');
                        }
                        if (VPLUtil.isBlockly(oldname) != VPLUtil.isBlockly(newname)) {
                            if (files[pos].getContent() > '') {
                                showMessage(str('delete_file_fq', oldname), {
                                    ok: function() {
                                        var file = {
                                            name: newname,
                                            contents: '',
                                            encoding: 0
                                        };
                                        fileManager.deleteFile(oldname, showError);
                                        var fileResult = fileManager.addFile(file, false, updateMenu, showErrorMessage);
                                        if (fileResult) {
                                            fileManager.gotoFileName(newname);
                                        }
                                    }
                                });
                            } else {
                                var file = {
                                    name: newname,
                                    contents: '',
                                    encoding: 0
                                };
                                fileManager.deleteFile(oldname, showError);
                                var fileResult = fileManager.addFile(file, false, updateMenu, showError);
                                if (fileResult) {
                                    fileManager.gotoFileName(newname);
                                }
                            }
                            return true;
                        }
                        files[pos].setFileName(newname);
                    } catch (e) {
                        showError(str('filenotrenamed', newname) + ': ' + e);
                        return false;
                    }
                    self.setModified();
                    adjustTabsTitles(false);
                    VPLUtil.delay('updateFileList', self.updateFileList);
                    return true;
                };
                this.deleteFile = function(name, showError) {
                    var pos = this.fileNameExists(name);
                    if (pos == -1) {
                        showError(str('filenotdeleted', name));
                        return false;
                    }
                    if (pos < minNumberOfFiles) {
                        showError(str('filenotdeleted', name));
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
                    var fname = tag.data('file');
                    var fpos = -1;
                    if (fname > '') {
                        fpos = this.fileNameExists(fname);
                    } else {
                        fpos = self.getFilePosById(tag.data('fileid'));
                    }
                    if (fpos >= 0) {
                        var line = tag.data('line');
                        if (typeof line == 'undefined') {
                            line = 'c';
                        }
                        self.gotoFile(fpos, line);
                        return true;
                    }
                    return false;
                };
                this.gotoFileName = function(fname, line) {
                    var fpos = this.fileNameExists(fname);
                    if (fpos >= 0) {
                        if (typeof line == 'undefined') {
                            line = 'c';
                        }
                        self.gotoFile(fpos, line);
                        return true;
                    }
                    return false;
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
                    modified = true;
                    VPLUtil.delay('updateFileList', self.updateFileList);
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
                        isDir: true,
                        content: {}
                    };
                    /**
                     * Adds a new file the structure of directories
                     * @param {int} i Index of file to add in the file array
                     */
                    function addFilePath(i) {
                        var file = files[i];
                        var fileName = file.getFileName();
                        var path = fileName.split("/");
                        var curdir = structure;
                        for (var p in path) {
                            if (path.hasOwnProperty(p)) {
                                var part = path[p];
                                if (p == path.length - 1) { // File.
                                    curdir.content[part] = {
                                        isDir: false,
                                        content: file,
                                        pos: i
                                    };
                                } else {
                                    if (!curdir.content[part]) { // New dir.
                                        curdir.content[part] = {
                                            isDir: true,
                                            content: {}
                                        };
                                    }
                                    // Descend Dir.
                                    curdir = curdir.content[part];
                                }
                            }
                        }
                    }
                    for (var i in files) {
                        if (files.hasOwnProperty(i)) {
                            addFilePath(i);
                        }
                    }
                    return structure;
                };
                this.generateFileList = function() {
                    if (!self.isFileListVisible()) {
                        return;
                    }
                    var dirIndent = '<span class="vpl_ide_dirindent"></span>';
                    /**
                     * Generates an array of string with the HTML code to represent the list of IDE files
                     * @param {Object} dir Current directory
                     * @param {int} indent Html code to indent subdirectories
                     * @param {Array} lines Output. Each line contains the HTML to represent an IDE file
                     */
                    function lister(dir, indent, lines) {
                        for (var name in dir.content) {
                            if (dir.content.hasOwnProperty(name)) {
                                var fd = dir.content[name];
                                if (fd.isDir) {
                                    lines.push(indent + VPLUtil.iconFolder() + VPLUtil.sanitizeText(name));
                                    lister(fd, indent + dirIndent, lines);
                                } else {
                                    var file = fd.content;
                                    var sname = VPLUtil.sanitizeText(name);
                                    var path = VPLUtil.sanitizeText(file.getFileName());
                                    if (file.isOpen()) {
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
                                    lines.push(indent + line);
                                }
                            }
                        }
                    }

                    var structure = self.getDirectoryStructure();
                    var lines = [];
                    var html = '';
                    lister(structure, '', lines);
                    for (var i = 0; i < lines.length; i++) {
                        html += lines[i] + '<br>';
                    }
                    fileListContent.html('<div>' + html + '</div>');
                };
                tabsUl.on('click', 'span.vpl_ide_closeicon', function() {
                    fileManager.close(fileManager.currentFile());
                });
                tabsUl.on('dblclick', 'span.vpl_ide_closeicon', menuButtons.getAction('delete'));
                tabsUl.on('dblclick', 'a', menuButtons.getAction('rename'));
                fileListContent.on('dblclick', 'a', menuButtons.getAction('rename'));

            }
            this.updateEvaluationNumber = function(res) {
                if (typeof res.nevaluations != 'undefined') {
                    var text = res.nevaluations;
                    if (typeof res.reductionbyevaluation != 'undefined'
                         && res.reductionbyevaluation > ''
                         && res.reductionbyevaluation != 0) {
                        if (res.freeevaluations != 0) {
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
                if (result.find('.' + contentclass).length == 0) {
                    result.append('<div class="' + titleclass + '"></div>');
                    result.append('<div class="' + contentclass + '"></div>');
                }
                if (typeof raw == 'undefined') {
                    return result.find('h4.' + titleclass).length > 0;
                }
                var titleTag = result.find('.' + titleclass);
                if (content > '') {
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
                if (result.find('.' + contentclass).length == 0) {
                    result.append('<div class="' + titleclass + '"></div>');
                    result.append('<div class="' + contentclass + '"></div>');
                }
                if (typeof raw == 'undefined') {
                    return result.find('h4.' + titleclass).length > 0;
                }
                var titleTag = result.find('.' + titleclass);
                var contentTag = result.find('.' + contentclass);
                var HTMLcontent = $('<div>' + content + '</div>');
                HTMLcontent.find('h4').replaceWith(function() {
                    return $("<h5>").append($(this).contents());
                });
                if (contentTag.html() == HTMLcontent.html()) {
                    return content > '';
                }
                if (content > '') {
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
                    fileNames[i] = files[i].getFileName();
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
                formated = VPLUtil.processResult(res.compilation, fileNames, files, true, false);
                hasContent = self.setResultTab('compilation', formated, res.compilation);
                show = show || hasContent;
                formated = VPLUtil.processResult(res.evaluation, fileNames, files, false, false);
                hasContent = self.setResultTab('comments', formated, res.evaluation);
                show = show || hasContent;
                formated = VPLUtil.sanitizeText(res.execution);
                hasContent = self.setResultTab('execution', formated, res.execution);
                show = show || hasContent;
                hasContent = self.setResultTab('description', window.VPLDescription, window.VPLDescription);
                if (hasContent && typeof MathJax == 'object') { // MathJax workaround.
                    var math = result.find(".vpl_ide_accordion_c_description")[0];
                    MathJax.Hub.Queue(["Typeset", MathJax.Hub, math]);
                }
                show = show || hasContent;
                if (show) {
                    resultContainer.show();
                    resultContainer.vplVisible = true;
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
                    $('#vpl_ide_rightpanel').show();
                } else {
                    resultContainer.hide();
                    resultContainer.vplVisible = false;
                    $('#vpl_ide_rightpanel').hide();
                }
                VPLUtil.delay('autoResizeTab', autoResizeTab);
            };

            result.accordion({
                heightStyle: 'fill',
                header: 'h4',
                animate: false,
                beforeActivate: avoidSelectGrade,
            });
            resultContainer.width(2 * resultContainer.vplMinWidth);
            result.on('click', 'a', function(event) {
                if (fileManager.gotoFileLink(event.currentTarget)) {
                    event.preventDefault();
                }
            });
            resultContainer.vplVisible = false;
            resultContainer.hide();

            fileListContainer.addClass('ui-tabs ui-widget ui-widget-content ui-corner-all');
            fileList.text(str('filelist'));
            fileList.html(VPLUtil.iconFolder() + fileList.html());
            fileList.addClass("ui-widget-header ui-button-text-only ui-corner-all");
            fileListContent.addClass("ui-widget ui-corner-all");
            fileListContainer.width(2 * fileListContainer.vplMinWidth);
            fileListContainer.on('click', 'a', function(event) {
                event.preventDefault();
                fileManager.gotoFileLink(event.currentTarget);
            });
            fileListContainer.vplVisible = false;
            fileListContainer.hide();
            var tabsAir = false;
            /**
             * Returns separation space
             * @returns {int} size in pixels
             */
            function getTabsAir() {
                if (tabsAir === false) {
                    tabsAir = (tabs.outerWidth(true) - tabs.width()) / 2;
                }
                return tabsAir;
            }
            /**
             * Resize tab width
             * @param {Event} e Unused
             * @param {Object} ui UI object
             */
            function resizeTabWidth(e, ui) {
                var diffLeft = ui.position.left - ui.originalPosition.left;
                var maxWidth;
                if (diffLeft !== 0) {
                    maxWidth = tabs.width() + fileListContainer.width() - fileListContainer.vplMinWidth;
                    tabs.resizable('option', 'maxWidth', maxWidth);
                    fileListContainer.width(fileListContainer.vplOriginalWidth + diffLeft);
                } else {
                    maxWidth = tabs.width() + resultContainer.width() - resultContainer.vplMinWidth;
                    tabs.resizable('option', 'maxWidth', maxWidth);
                    var diffWidth = ui.size.width - ui.originalSize.width;
                    resultContainer.width(resultContainer.vplOriginalWidth - diffWidth);
                }
                fileManager.currentFile('adjustSize');
            }
            var resizableOptions = {
                containment: 'parent',
                resize: resizeTabWidth,
                start: function() {
                    $(window).off('resize', autoResizeTab);
                    tabs.resizable('option', 'minWidth', 100);
                    if (resultContainer.vplVisible) {
                        resultContainer.vplOriginalWidth = resultContainer.width();
                    }
                    if (fileListContainer.vplVisible) {
                        fileListContainer.vplOriginalWidth = fileListContainer.width();
                    }
                },
                stop: function(e, ui) {
                    resizeTabWidth(e, ui);
                    tabs.resizable('option', 'maxWidth', 100000);
                    tabs.resizable('option', 'minWidth', 0);
                    autoResizeTab();
                    $(window).on('resize', autoResizeTab);
                },
                handles: ""
            };
            tabs.resizable(resizableOptions);
             /**
              * Updates handles for internal IDE resize
              */
            function updateTabsHandles() {
                var handles = ['e', 'w', 'e', 'e, w'];
                var index = 0;
                index += fileListContainer.vplVisible ? 1 : 0;
                index += resultContainer.vplVisible ? 2 : 0;
                tabs.resizable('destroy');
                resizableOptions.handles = handles[index];
                resizableOptions.disable = index === 0;
                tabs.resizable(resizableOptions);
            }
            /**
             * Resize the IDE height
             */
            function resizeHeight() {
                var newHeight = $(window).outerHeight();
                newHeight -= menu.offset().top + menu.height() + (fullScreen ? getTabsAir() : 20);
                if (newHeight < 150) {
                    newHeight = 150;
                }
                tr.height(newHeight);
                var panelHeight = newHeight - 3 * getTabsAir();
                tabs.height(panelHeight);
                if (resultContainer.vplVisible) {
                    resultContainer.height(panelHeight + getTabsAir());
                    result.accordion('refresh');
                }
                if (fileListContainer.vplVisible) {
                    fileListContent.height(panelHeight - (fileList.outerHeight() + getTabsAir()));
                    fileListContainer.height(panelHeight);
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
                            scrollLeft: scroll
                        }, 'slow');
                    }
                }
                if (tabsUlWidth < newWidth) {
                    tabsUl.width('');
                }
            };
            autoResizeTab = function() {
                var oldWidth = tabs.width();
                var newWidth = menu.width();
                var planb = false;
                updateTabsHandles();
                tr.width(menu.outerWidth());
                if (fileListContainer.vplVisible) {
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
                if (resultContainer.vplVisible) {
                    var right = resultContainer.outerWidth() + tabsAir;
                    oldWidth += right;
                    newWidth -= right;
                    if (newWidth < 100) {
                        planb = true;
                    }
                }
                if (planb) {
                    var rel = menu.width() / oldWidth;
                    var wfl = 0;
                    if (fileListContainer.vplVisible) {
                        wfl = fileListContainer.width() * rel;
                        fileListContainer.width(wfl - tabsAir);
                        wfl += tabsAir;
                        tabs.css('left', wfl);
                    }
                    tabs.width(tabs.width() * rel);
                    if (resultContainer.vplVisible) {
                        resultContainer.width(menu.width() - (wfl + tabs.width() + tabsAir));
                    }
                } else {
                    tabs.width(newWidth);
                }
                adjustTabsTitles(true);
                resizeHeight();
                fileManager.currentFile('adjustSize');
            };
            /**
             * Transfer focus to current file
             */
            function focusCurrentFile() {
                fileManager.currentFile('focus');
            }
            var dialogbaseOptions = $.extend({}, {
                close: focusCurrentFile
            }, VPLUtil.dialogbaseOptions);
            /**
             * Shows a dialog with a message.
             * @param {string} message
             * @param {Object} options icon, title, actions handler (ok, yes, no, close)
             * @returns {JQuery} JQueryUI Dialog object already open
             */
            function showMessage(message, options) {
                return VPLUtil.showMessage(message, $.extend({}, dialogbaseOptions, options));
            }
            showErrorMessage = function(message) {
                return VPLUtil.showErrorMessage(message, {
                    close: focusCurrentFile
                });
            };

            var dialogNew = $('#vpl_ide_dialog_new');
            /**
             * The event handler for the new file action
             * @param {Object} event
             * @return {boolean}
             */
            function newFileHandler(event) {
                if (!(event.type == 'click' || ((event.type == 'keypress') && event.keyCode == 13))) {
                    return true;
                }
                dialogNew.dialog('close');
                var file = {
                    name: $('#vpl_ide_input_newfilename').val(),
                    contents: '',
                    encoding: 0
                };
                var newfile = fileManager.addFile(file, false, updateMenu, showErrorMessage);
                if (newfile) {
                    fileManager.open(newfile);
                    tabs.tabs('option', 'active', fileManager.getTabPos(newfile));
                    newfile.focus();
                    return true;
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
                title: str('create_new_file'),
                buttons: dialogButtons
            }));
            VPLUtil.setDialogTitleIcon(dialogNew, 'new');

            var dialogRename = $('#vpl_ide_dialog_rename');
            /**
             * The event handler for the rename current file action
             * @param {Object} event
             */
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
                open: function() {
                    $('#vpl_ide_input_renamefilename').val(fileManager.currentFile('getFileName'));
                },
                title: str('rename_file'),
                buttons: dialogButtons
            }));
            VPLUtil.setDialogTitleIcon(dialogRename, 'rename');

            dialogButtons[str('ok')] = function() {
                $(this).dialog('close');
            };
            var dialogComments = $('#vpl_ide_dialog_comments');
            dialogComments.dialog($.extend({}, dialogbaseOptions, {
                title: str('comments'),
                width: '40em',
                buttons: dialogButtons
            }));
            VPLUtil.setDialogTitleIcon(dialogComments, 'comments');

            $('#vpl_ide_input_comments').width('30em');
            var aboutDialog = $('#vpl_ide_dialog_about');
            var OKButtons = {};
            OKButtons[str('ok')] = function() {
                $(this).dialog('close');
            };
            var shortcutDialog = $('#vpl_ide_dialog_shortcuts');
            shortcutDialog.dialog($.extend({}, dialogbaseOptions, {
                open: function() {
                    var html = menuButtons.getShortcuts(fileManager.currentFile('getEditor'));
                    $('#vpl_ide_dialog_shortcuts').html(html);
                },
                title: str('shortcuts'),
                width: 400,
                height: 300,
                buttons: OKButtons
            }));
            shortcutDialog.dialog('option', 'height', 300);
            VPLUtil.setDialogTitleIcon(shortcutDialog, 'shortcuts');

            OKButtons[str('shortcuts')] = function() {
                $(this).dialog('close');
                shortcutDialog.dialog('open');
            };
            aboutDialog.dialog($.extend({}, dialogbaseOptions, {
                open: function() {
                    var html = menuButtons.getShortcuts(fileManager.currentFile('getEditor'));
                    aboutDialog.next().find("button").filter(
                        function() {
                            return $(this).text() == str('shortcuts');
                        }
                  ).button(html != '' ? 'enable' : 'disable');
                },
                title: str('about'),
                width: 400,
                height: 300,
                buttons: OKButtons
            }));
            aboutDialog.dialog('option', 'height', 300);
            VPLUtil.setDialogTitleIcon(aboutDialog, 'about');

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
                $(this).dialog('close');
            };
            dialogSortButtons[str('cancel')] = function() {
                $(this).dialog('close');
            };
            dialogSort.dialog($.extend({}, dialogbaseOptions, {
                title: str('sort'),
                buttons: dialogSortButtons,
                open: function() {
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
                        items: "li:not(.ui-state-disabled)",
                        placeholder: "ui-state-highlight",
                        start: function(event, ui) {
                            ui.item.addClass('ui-state-highlight');
                        },
                        stop: function(event, ui) {
                            ui.item.removeClass('ui-state-highlight');
                        },
                    });
                    list.disableSelection();
                },
                maxHeight: 400
            }));
            VPLUtil.setDialogTitleIcon(dialogSort, 'sort');

            var dialogMultidelete = $('#vpl_ide_dialog_multidelete');
            var dialogMultideleteButtons = {};
            dialogMultideleteButtons[str('selectall')] = function() {
                $(this).find('input').prop("checked", true);
            };
            dialogMultideleteButtons[str('deselectall')] = function() {
                $(this).find('input').prop("checked", false);
            };
            dialogMultideleteButtons[str('deleteselected')] = function() {
                var files = fileManager.getFiles();
                var toDeleteList = [];
                var labelList = $('#vpl_multidelete_list label');
                labelList.each(function() {
                    var label = $(this);
                    if (label.find('input').prop('checked')) {
                        var id = label.data('fileid');
                        toDeleteList.push(files[id].getFileName());
                    }
                });
                for (var i = 0; i < toDeleteList.length; i++) {
                    fileManager.deleteFile(toDeleteList[i], showErrorMessage);
                }
                VPLUtil.delay('updateMenu', updateMenu);
                VPLUtil.delay('updateFileList', fileManager.updateFileList);
                $(this).dialog('close');
            };
            dialogMultideleteButtons[str('cancel')] = function() {
                $(this).dialog('close');
            };
            dialogMultidelete.dialog($.extend({}, dialogbaseOptions, {
                title: str('multidelete'),
                buttons: dialogMultideleteButtons,
                open: function() {
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
                maxHeight: 400,
                maxWidth: 400
            }));
            VPLUtil.setDialogTitleIcon(dialogMultidelete, 'multidelete');

            var dialogFontsize = $('#vpl_ide_dialog_fontsize');
            var fontsizeSlider = $('#vpl_ide_dialog_fontsize .vpl_fontsize_slider');
            var dialogFontFizeButtons = {};
            dialogFontFizeButtons[str('ok')] = function() {
                var value = fontsizeSlider.slider("value");
                fileManager.setFontSize(value);
                $(this).dialog('close');
                $.ajax({
                    async: true,
                    type: "POST",
                    url: '../editor/userpreferences.json.php',
                    'data': JSON.stringify({fontSize: value}),
                    contentType: "application/json; charset=utf-8",
                    dataType: "json"
                });
            };
            dialogFontFizeButtons[str('cancel')] = function() {
                fileManager.setFontSize(fontsizeSlider.data("vpl_fontsize"));
                $(this).dialog('close');
            };
            dialogFontFizeButtons[str('reset')] = function() {
                fontsizeSlider.slider('value', 12);
            };
            dialogFontsize.dialog($.extend({}, dialogbaseOptions, {
                title: str('fontsize'),
                buttons: dialogFontFizeButtons,
                open: function() {
                    fontsizeSlider.data("vpl_fontsize", fileManager.getFontSize());
                    fontsizeSlider.slider('value', fileManager.getFontSize());
                },
            }));
            fontsizeSlider.slider({
                min: 1,
                max: 48,
                change: function() {
                    var value = fontsizeSlider.slider("value");
                    fileManager.setFontSize(value);
                    dialogFontsize.find('.vpl_fontsize_slider_value').text(value);
                }
            });
            VPLUtil.setDialogTitleIcon(dialogFontsize, 'fontsize');

            var dialogAceTheme = $('#vpl_ide_dialog_acetheme');
            var acethemeSelect = $('#vpl_ide_dialog_acetheme select');
            var dialogAceThemeButtons = {};
            dialogAceThemeButtons[str('ok')] = function() {
                fileManager.setTheme(acethemeSelect.val());
                $(this).dialog('close');
                VPLUtil.setUserPreferences({aceTheme: acethemeSelect.val()});
            };
            dialogAceThemeButtons[str('cancel')] = function() {
                fileManager.setTheme(acethemeSelect.data("acetheme"));
                $(this).dialog('close');
            };
            dialogAceThemeButtons[str('reset')] = function() {
                acethemeSelect.val(acethemeSelect.data("acetheme"));
                fileManager.setTheme(acethemeSelect.val());
            };
            dialogAceTheme.dialog($.extend({}, dialogbaseOptions, {
                title: str('theme'),
                buttons: dialogAceThemeButtons,
                modal: false,
                open: function() {
                    acethemeSelect.data("acetheme", fileManager.getTheme());
                    acethemeSelect.val(fileManager.getTheme());
                },
            }));
            acethemeSelect.on('change', function() {
                    fileManager.setTheme(acethemeSelect.val());
            });
            VPLUtil.setDialogTitleIcon(dialogAceTheme, 'theme');

            var terminal = new VPLTerminal('vpl_dialog_terminal', 'vpl_terminal', str);
            var VNCClient = new VPLVNCClient('vpl_dialog_vnc', str);
            var lastConsole = terminal;
            var fileSelect = $('#vpl_ide_input_file');
            var fileSelectHandler = function() {
                VPLUtil.readSelectedFiles(this.files, function(file) {
                    return fileManager.addFile(file, true, updateMenu, showErrorMessage);
                },
                function() {
                    fileManager.fileListVisibleIfNeeded();
                });
            };
            fileSelect.on('change', fileSelectHandler);
            // Menu acctions.
            menuButtons.add({
                name: 'filelist',
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
                name: 'delete',
                originalAction: function() {
                    var file = fileManager.currentFile();
                    if (!file) {
                        return;
                    }
                    var filename = file.getFileName();
                    var message = str('delete_file_fq', filename);
                    showMessage(message, {
                        ok: function() {
                            fileManager.deleteFile(filename, showErrorMessage);
                        },
                        title: str('delete_file_q'),
                        icon: 'trash'
                    });
                },
                bindKey: {
                    win: 'Ctrl-D',
                    mac: 'Ctrl-D'
                }
            });
            menuButtons.add({
                name: 'close',
                originalAction: function() {
                    var file = fileManager.currentFile();
                    if (!file) {
                        return;
                    }
                    fileManager.close(file);
                },
                bindKey: {
                    win: 'Alt-W',
                    mac: 'Option-W'
                }
            });
            menuButtons.add({
                name: 'import',
                originalAction: function() {
                    fileSelect.val('');
                    fileSelect.trigger('click');
                },
                bindKey: {
                    win: 'Ctrl-I',
                    mac: 'Ctrl-I'
                }
            });
            menuButtons.add({
                name: 'sort',
                originalAction: function() {
                    dialogSort.dialog('open');
                },
                bindKey: {
                    win: 'Ctrl-O',
                    mac: 'Ctrl-O'
                }
            });
            menuButtons.add({
                name: 'multidelete',
                originalAction: function() {
                    dialogMultidelete.dialog('open');
                }
            });
            menuButtons.add({
                name: 'fontsize',
                originalAction: function() {
                    dialogFontsize.dialog('open');
                }
            });
            menuButtons.add({
                name: 'theme',
                originalAction: function() {
                    dialogAceTheme.dialog('open');
                }
            });
            menuButtons.add({
                name: 'print',
                originalAction: function() {
                    window.print();
                },
                bindKey: {
                    win: 'Alt-P',
                    mac: 'Command-P'
                }
            });
            menuButtons.add({
                name: 'undo',
                originalAction: function() {
                    fileManager.currentFile('undo');
                }
            });
            menuButtons.add({
                name: 'redo',
                originalAction: function() {
                    fileManager.currentFile('redo');
                }
            });
            menuButtons.add({
                name: 'select_all',
                editorName: 'selectall',
                originalAction: function() {
                    fileManager.currentFile('selectAll');
                }
            });
            menuButtons.add({
                name: 'find',
                originalAction: function() {
                    fileManager.currentFile('find');
                }
            });
            menuButtons.add({
                name: 'find_replace',
                editorName: 'replace',
                originalAction: function() {
                    fileManager.currentFile('replace');
                }
            });
            menuButtons.add({
                name: 'next',
                editorName: 'findnext',
                originalAction: function() {
                    fileManager.currentFile('next');
                }
            });
            menuButtons.add({
                name: 'fullscreen',
                originalAction: function() {
                    var tags = 'header, nav, footer, aside, .dropdown, #page-header, div.navbar, #nav-drawer';
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
                        if (options.username) {
                            $('#vpl_ide_user').show();
                        }
                        fullScreen = true;
                    }
                    focusCurrentFile();
                    setTimeout(autoResizeTab, 10);
                },
                bindKey: {
                    win: 'Alt-F',
                    mac: 'Ctrl-F'
                }
            });
            menuButtons.add({
                name: 'download',
                originalAction: function() {
                    window.location = options.download;
                }
            });
            /**
             * Reset files action
             */
            function resetFiles() {
                VPLUtil.requestAction('resetfiles', '', {}, options.ajaxurl)
                .done(function(response) {
                    var files = response.files;
                    for (var fileName in files) {
                        if (files.hasOwnProperty(fileName)) {
                            fileManager.addFile(files[fileName], true, VPLUtil.doNothing, showErrorMessage);
                        }
                    }
                    fileManager.fileListVisibleIfNeeded();
                    VPLUtil.delay('updateMenu', updateMenu);
                }).fail(showErrorMessage);
            }
            menuButtons.add({
                name: 'resetfiles',
                originalAction: function() {
                    showMessage(str('sureresetfiles'), {
                        title: str('resetfiles'),
                        ok: resetFiles,
                        icon: 'resetfiles'
                    });
                }
            });
            menuButtons.add({
                name: 'save',
                originalAction: function() {
                    var data = {
                        files: fileManager.getFilesToSave(),
                        comments: $('#vpl_ide_input_comments').val(),
                        version: fileManager.getVersion()
                    };
                    if (JSON.stringify(data).length > options.postMaxSize) {
                        showErrorMessage(str('maxpostsizeexceeded'));
                        return;
                    }
                    /**
                     * Save action
                     */
                    function doSave() {
                        VPLUtil.requestAction('save', 'saving', data, options.ajaxurl)
                        .done(function(response) {
                            if (response.requestsconfirmation) {
                                showMessage(response.question, {
                                    title: str('saving'),
                                    icon: 'alert',
                                    yes: function() {
                                        data.version = 0;
                                        doSave();
                                    }
                                });
                            } else {
                                fileManager.resetModified();
                                fileManager.setVersion(response.version);
                                menuButtons.setTimeLeft(response);
                                VPLUtil.delay('updateMenu', updateMenu);
                                if (VPLUtil.monitorRunning()) {
                                    data.processid = VPLUtil.getProcessId();
                                    VPLUtil.requestAction('update', 'updating', data, options.ajaxurl);
                                }
                            }
                        }).fail(showErrorMessage);
                    }
                    doSave();
                },
                bindKey: {
                    win: 'Ctrl-S',
                    mac: 'Command-S'
                }
            });

            /**
             * Launches the action
             *
             * @param {string} action Action 'run', 'debug', 'evaluate'
             * @param {string} acting I18n for the action in progress
             * @param {string} data Data attached to the action
             */
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
            /**
             * Launches the run action
             */
            function runAction() {
                executionRequest('run', 'running', {
                    XGEOMETRY: VNCClient.getCanvasSize()
                });
            }
            menuButtons.add({
                name: 'run',
                originalAction: function() {
                    executionActions.setLastAction(runAction);
                    runAction();
                },
                bindKey: {
                    win: 'Ctrl-F11',
                    mac: 'Command-U'
                }
            });
            /**
             * Launches the debug action
             */
            function debugAction() {
                executionRequest('debug', 'debugging', {
                    XGEOMETRY: VNCClient.getCanvasSize()
                });
            }
            menuButtons.add({
                name: 'debug',
                originalAction: function() {
                    executionActions.setLastAction(debugAction);
                    debugAction();
                },
                bindKey: {
                    win: 'Alt-F11',
                    mac: 'Option-U'
                }
            });
            /**
             * Launches the evaluate action
             */
            function evaluateAction() {
                executionRequest('evaluate', 'evaluating');
            }
            menuButtons.add({
                name: 'evaluate',
                originalAction: function() {
                    executionActions.setLastAction(evaluateAction);
                    evaluateAction();
                },
                bindKey: {
                    win: 'Shift-F11',
                    mac: 'Command-Option-U'
                }
            });
            menuButtons.add({
                name: 'comments',
                originalAction: function() {
                    dialogComments.dialog('open');
                },
            });
            menuButtons.add({
                name: 'console',
                originalAction: function() {
                    if (lastConsole.isOpen()) {
                        lastConsole.close();
                    } else {
                        lastConsole.show();
                    }
                }
            });
            menuButtons.add({name: 'user'});
            menuButtons.add({
                name: 'about',
                originalAction: function() {
                    aboutDialog.dialog('open');
                }
            });
            menuButtons.add({
                name: 'timeleft',
                originalAction: function() {
                    menuButtons.toggleTimeLeft();
                }
            });
            menuButtons.add({
                name: 'more',
                originalAction: function() {
                    var tag = $('#vpl_ide_menuextra');
                    if (tag.is(":visible")) {
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
            menuButtons.add({
                name: 'rightpanel',
                icon: 'close-rightpanel',
                originalAction: function() {
                    if (resultContainer.vplVisible) {
                        resultContainer.hide();
                        resultContainer.vplVisible = false;
                        menuButtons.setText('rightpanel', 'open-rightpanel', VPLUtil.str('rightpanel'));
                    } else {
                        menuButtons.setText('rightpanel', 'close-rightpanel', VPLUtil.str('rightpanel'));
                        resultContainer.show();
                        resultContainer.vplVisible = true;
                    }
                    VPLUtil.delay('autoResizeTab', autoResizeTab);
                },
                bindKey: {
                    win: 'Ctrl-M',
                    mac: 'Ctrl-M'
                }
            });
            var rightpanelstyle = "position:absolute;right:0;top:60px;z-index:100;margin:3px";
            tr.append('<span style="' + rightpanelstyle + '">' + menuButtons.getHTML('rightpanel') + '</span>');
            var rightPanelButton = $('#vpl_ide_rightpanel');
            menuButtons.setText('rightpanel', 'close-rightpanel', VPLUtil.str('rightpanel'));

            rightPanelButton.button();
            rightPanelButton.css('padding', '0');
            $('#vpl_ide_rightpanel.ui-button-text').css('padding', '0');
            rightPanelButton.on('click', function() {
                menuButtons.launchAction('rightpanel');
            });
            rightPanelButton.hide();
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
            menuHtml += menuButtons.getHTML('sort');
            menuHtml += menuButtons.getHTML('multidelete');
            menuHtml += menuButtons.getHTML('fontsize');
            menuHtml += menuButtons.getHTML('theme');
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
            $('#vpl_ide_user').button().css('float', 'right').hide();
            $('#vpl_ide_timeleft').button().css('float', 'right').hide();
            $('#vpl_menu .ui-button').css('padding', '6px');
            $('#vpl_menu .ui-button-text').css('padding', '0');
            var alwaysActive = ['filelist', 'more', 'fullscreen', 'about', 'resetfiles',
                                'download', 'comments', 'console', 'import',
                                'fontsize', 'timeleft'];
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
                var modified = fileManager.isModified();
                menuButtons.enable('save', modified);
                var running = VPLUtil.monitorRunning();
                if (running) {
                    menuButtons.setText('run', 'running');
                } else {
                    menuButtons.setText('run', 'run');
                }
                menuButtons.enable('run', !running && (!modified || options.example) && isOptionAllowed('run'));
                menuButtons.enable('debug', !running && (!modified || options.example) && isOptionAllowed('debug'));
                menuButtons.enable('evaluate', !running && (!modified || options.example) && isOptionAllowed('evaluate'));
                menuButtons.enable('download', !modified);
                menuButtons.enable('new', nfiles < maxNumberOfFiles);
                menuButtons.enable('sort', nfiles - minNumberOfFiles > 1);
                menuButtons.enable('multidelete', nfiles - minNumberOfFiles > 1);
                menuButtons.enable('theme', true);
                var sel;
                if (!file || nfiles === 0) {
                    sel = ['rename', 'delete', 'undo', 'redo', 'select_all', 'find', 'find_replace', 'next'];
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

            executionActions = {
                'open': updateMenu,
                'close': updateMenu,
                'getConsole': function() {
                    return lastConsole;
                },
                'setResult': self.setResult,
                'ajaxurl': options.ajaxurl,
                'run': function(content, coninfo, ws) {
                    var parsed = /^([^:]*):?(.*)/i.exec(content);
                    var type = parsed[1];
                    if (type == 'terminal' || type == 'webterminal') {
                        if (lastConsole && lastConsole.isOpen()) {
                            lastConsole.close();
                        }
                        lastConsole = terminal;
                        terminal.connect(coninfo.executionURL, function() {
                            ws.close();
                            focusCurrentFile();
                        });
                        if (type == 'webterminal') {
                            var URLfavicon = (coninfo.secure ? "https" : "http") + "://" + coninfo.server + ":" + coninfo.portToUse;
                            URLfavicon += "/favicon.ico";
                            var imgFavicon = $('<img>');
                            imgFavicon.attr('src', URLfavicon);
                            imgFavicon.attr('style', 'display:none');
                            $('body').append(imgFavicon);
                        }
                    } else if (type == 'vnc') {
                        if (lastConsole && lastConsole.isOpen()) {
                            lastConsole.close();
                        }
                        lastConsole = VNCClient;
                        VNCClient.connect(coninfo.secure, coninfo.server, coninfo.portToUse, coninfo.VNCpassword,
                                coninfo.executionPath, function() {
                                    ws.close();
                                    focusCurrentFile();
                                });
                    } else if (type == "browser") {
                        var URL = (coninfo.secure ? "https" : "http") + "://" + coninfo.server + ":" + coninfo.portToUse + "/";
                        URL += parsed[2] + "/httpPassthrough";
                        if (isTeacher) {
                            URL += "?private";
                        }
                        var message = '<a href="' + URL + '" target="_blank">';
                        message += VPLUtil.str('open') + '</a>';
                        var options = {
                            width: 200,
                            icon: 'run',
                            title: VPLUtil.str('run'),
                        };
                        showMessage(message, options);
                    } else {
                        VPLUtil.log("Type of run error " + content, true);
                    }
                },
                'lastAction': false,
                'getLastAction': function() {
                    var ret = this.lastAction;
                    this.lastAction = false;
                    return ret;
                },
                'setLastAction': function(action) {
                    this.lastAction = action;
                }
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
                    return undefined;
                });
            }
            fileManager = new FileManager();

            autoResizeTab();
            // Checks menu width every 1 sec as it can change without event.
            (function() {
                var oldMenuWidth = menu.width();
                /**
                 * Checks menu width change
                 */
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
                var showFileList = false;
                for (var i = 0; i < files.length; i++) {
                    var file = files[i];
                    var r = fileManager.addFile(file, false, updateMenu, showErrorMessage);
                    if (r) {
                        r.resetModified();
                        if (i < minNumberOfFiles || files.length <= 5) {
                            fileManager.open(r);
                        } else {
                            showFileList = true;
                        }
                    } else {
                        allOK = false;
                    }
                }
                tabs.tabs('option', 'active', 0);

                if (response.compilationexecution) {
                    self.setResult(response.compilationexecution, false);
                }
                menuButtons.setTimeLeft(response);
                if (response.comments > '') {
                    $('#vpl_ide_input_comments').val(response.comments);
                }
                if (allOK) {
                    fileManager.resetModified();
                } else {
                    fileManager.setModified();
                }
                if (fileManager.length() === 0 && maxNumberOfFiles > 0) {
                    menuButtons.getAction('new')();
                } else if (!options.saved) {
                    fileManager.setModified();
                }
                fileManager.setFontSize(options.fontSize);
                fileManager.setVersion(response.version);
                fileManager.fileListVisible(showFileList);
                VPLUtil.afterAll('AfterLoadFiles', function() {
                    updateMenu();
                    autoResizeTab();
                    adjustTabsTitles(true);
                });
            })
            .fail(showErrorMessage);
        };
        window.VPLIDE = VPLIDE;
        return {
            init: function(rootId, options) {
                vplIdeInstance = new VPLIDE(rootId, options);
            }
        };
    }
);
