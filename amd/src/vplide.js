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

/* globals openpopup, ace */

import $ from 'jquery';
/* eslint-disable no-unused-vars */
import jqui from 'jqueryui';
/* eslint-enable no-unused-vars */
import {VPLUtil} from 'mod_vpl/vplutil';
import {VPLUI} from 'mod_vpl/vplui';
import {VPLFile} from 'mod_vpl/vplidefile';
import {VPLIDEButtons} from 'mod_vpl/vplidebutton';
import {VPLTerminal} from 'mod_vpl/vplterminal';
import {VPLVNCClient} from 'mod_vpl/vplvnc';
import {VPLLS} from 'mod_vpl/vplls';

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
    var readOnlyFiles = options.readOnlyFiles;
    var isTeacher = options.isTeacher;
    var fullScreen = false;
    var scrollBarWidth = VPLUI.scrollBarWidth();
    var str = VPLUtil.str;
    var rootObj = $('#' + rootId);
    $("head").append('<meta name="viewport" content="initial-scale=1">')
                    .append('<meta name="viewport" width="device-width">');
    if (rootObj.length === 0) {
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
        'showparentfiles': true,
        'preferences': true,
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
        options.preferences = true;
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
    if ((typeof options.editorFontSize) == 'undefined') {
        options.editorFontSize = 12;
    }
    options.editorFontSize = parseInt(options.editorFontSize);
    if ((typeof options.editorTheme) == 'undefined') {
        options.editorTheme = 'chrome';
    }
    if ((typeof options.terminalFontSize) == 'undefined') {
        options.terminalFontSize = 12;
    }
    options.terminalFontSize = parseInt(options.terminalFontSize);
    if ((typeof options.editorKeyBinding) == 'undefined') {
        options.editorKeyBinding = 'Ace';
    }
    if ((typeof options.editorShowInvisibles) == 'undefined') {
        options.editorShowInvisibles = false;
    }
    if ((typeof options.editorLiveAutocompletion) == 'undefined') {
        options.editorLiveAutocompletion = false;
    }
    if ((typeof options.terminalTheme) == 'undefined') {
        options.terminalTheme = '';
    }
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
        e.stopImmediatePropagation();
    }
    /**
     * Handler for drop event.
     * @param {object} e event.
     * @returns {boolean}
     */
    function dropHandler(e) {
        if (restrictedEdit) { // No drop allowed.
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
        var droppedFiles = [];
        // Function that lists all files and subfiles of given entry into droppedFiles.
        var listDroppedFiles = function(entry, path = '') {
            return new Promise(function(resolve) {
                if (entry.isFile) {
                    // Current entry is a file : add it to the list.
                    entry.file(function(file) {
                        // Change its name s.t. it preserves directories structure.
                        var fullName = path + file.name;
                        Object.defineProperty(file, "name", {
                            get: function() {
                                    return fullName;
                                    }
                        });
                        droppedFiles.push(file);
                        resolve();
                    });
                } else if (entry.isDirectory) {
                    // Current entry is a directory : process its content.
                    var dirReader = entry.createReader();
                    dirReader.readEntries(function(entries) {
                        var dirPromises = [];
                        for (var i = 0; i < entries.length; i++) {
                            dirPromises.push(listDroppedFiles(entries[i], path + entry.name + "/"));
                        }
                        Promise.all(dirPromises).then(resolve).catch(function(err) {
                            VPLUtil.log("Error reading directory entries: " + err);
                        });
                    });
                } else {
                    // This is neither a directory nor a file : ignore it.
                    resolve();
                }
            });
        };
        var dt = e.originalEvent.dataTransfer;

        // List every element of the drop event.
        var promises = [];
        for (var i = 0; i < dt.items.length; i++) {
            var entry = dt.items[i].webkitGetAsEntry();
            if (!entry) { // Used if testing with Behat
                const file = dt.items[i].getAsFile();
                if (file) {
                    // Create a fake entry to handle it like a file.
                    entry = {
                        isFile: true,
                        isDirectory: false,
                        file: function(callback) {
                            callback(file);
                        }
                    };
                    promises.push(listDroppedFiles(entry));
                }
            } else if (entry.isFile || entry.isDirectory) {
                promises.push(listDroppedFiles(entry));
            }
        }

        // Drop files.
        if (dt.files.length > 0) {
            Promise.all(promises)
            .then(function() {
                VPLUI.readSelectedFiles(droppedFiles, function(file) {
                    return fileManager.addFile(file, true, updateMenu, showErrorMessage);
                },
                function() {
                    fileManager.fileListVisibleIfNeeded();
                });
                return;
            })
            .catch(function(err) {
                VPLUtil.log("Error processing dropped files: " + err);
            });

            e.stopImmediatePropagation();
            return false;
        }
        return false;
    }
    /**
     * Handle paste under restricted editing at the IDE boundary.
     * @param {Event} e paste event.
     */
    function restrictedPasteHandler(e) {
        if (!restrictedEdit) {
            return;
        }
        var target = e.target;
        if (!target || !target.closest) {
            return;
        }
        if (target.closest('.ace_search')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
        }
        var editorContainer = target.closest('.ace_editor');
        var currentEditor = fileManager.currentFile('getEditor');
        if (!editorContainer || !currentEditor || currentEditor.container !== editorContainer) {
            return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        currentEditor.insert(fileManager.getClipboard());
    }
    /**
     * Block dragover under restricted editing at the IDE boundary.
     * @param {DragEvent} e dragover event.
     */
    function restrictedDragoverHandler(e) {
        if (!restrictedEdit) {
            return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        if (e.dataTransfer) {
            e.dataTransfer.dropEffect = 'none';
        }
    }
    /**
     * Block drop under restricted editing at the IDE boundary.
     * @param {DragEvent} e drop event.
     */
    function restrictedDropHandler(e) {
        if (!restrictedEdit) {
            return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();
    }
    rootObj.on('drop', dropHandler);
    rootObj.on('dragover', dragoverHandler);
    if (restrictedEdit) {
        rootObj[0].addEventListener('paste', restrictedPasteHandler, true);
        rootObj[0].addEventListener('dragover', restrictedDragoverHandler, true);
        rootObj[0].addEventListener('drop', restrictedDropHandler, true);
    }
    // Init editor vars.
    var menu = $('#vpl_menu');
    var menuButtons = new VPLIDEButtons(rootObj, isOptionAllowed);
    var tr = $('#vpl_tr');
    var fileListContainer = $('#vpl_filelist');
    var fileList = $('#vpl_filelist_header');
    var fileListContent = $('#vpl_filelist_content');
    var tabsUl = $('#vpl_tabs_ul');
    var tabs = $('#vpl_tabs');
    var resultContainer = $('#vpl_results');
    var result = $('#vpl_results_accordion');
    var renameDiretoryAction = VPLUtil.doNothing;
    fileListContainer.vplMinWidth = 80;
    resultContainer.vplMinWidth = 100;
    this.getMenuButtons = function() {
        return menuButtons;
    };
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
     * @param {object} IDEInstance The IDE instance
     * @param {object} options Object with configuration options
     */
    function FileManager(IDEInstance, options) {
        var tabsUl = $('#vpl_tabs_ul');
        $('#vpl_tabs').tabs();
        var tabs = $('#vpl_tabs').tabs("widget");
        var files = [];
        var openFiles = [];
        var modified = true;
        var self = this;
        var LSManager = null;
        (function() {
            var version;
            self.setVersion = function(v) {
                version = v;
            };
            self.getVersion = function() {
                return version;
            };
        })();
        this.getIDE = function() {
            return IDEInstance;
        };
        this.getLSManager = function() {
            return LSManager;
        };
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
         * Optionly ignores a position
         * @param {string} name Name of file
         * @param {number} posIgnore Position to ignore in the check
         * @returns {boolean} if found or not found
         */
        function fileNameIncluded(name, posIgnore) {
            // Adding '/' allows to check for directories too.
            // E.g. 'file' matches 'file/abc' but not 'file2/file'.
            var checkName = name.toLowerCase() + '/';
            for (var i = 0; i < files.length; i++) {
                if (i === posIgnore) {
                    continue;
                }
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
        this.dropHandler = dropHandler;
        this.dragoverHandler = dragoverHandler;
        this.readOnly = readOnly;
        this.readOnlyFiles = readOnlyFiles;
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
        if (restrictedEdit) {
            const allowedRegion = rootObj[0];
            const freeRegion = tabs[0];
            document.addEventListener('copy', function() {
                const selection = window.getSelection();
                // Check if the selection is inside IDE but outside editor.
                if (allowedRegion.contains(selection.anchorNode) &&
                    !freeRegion.contains(selection.anchorNode)) {
                    self.setClipboard(selection.toString());
                }
            });
        }
        this.getTabPos = function(fileId) {
            for (var i = 0; i < openFiles.length; i++) {
                if (openFiles[i].getId() == fileId) {
                    return i;
                }
            }
            return openFiles.length;
        };
        this.getTheme = function() {
            return options.editorTheme;
        };
        this.setTheme = function(theme) {
            options.editorTheme = theme;
            for (let file of files) {
                if (file.isCode() || file.isOpen()) {
                    file.setTheme(theme);
                }
            }
        };
        this.getEditorKeyBinding = function() {
            return options.editorKeyBinding;
        };
        this.setEditorKeyBinding = function(binding) {
            options.editorKeyBinding = binding;
            for (let file of files) {
                if (file.isCode() || file.isOpen()) {
                    file.setKeyBinding(binding);
                }
            }
        };
        this.getEditorShowInvisibles = function() {
            return options.editorShowInvisibles;
        };
        this.setEditorShowInvisibles = function(show) {
            options.editorShowInvisibles = show;
            for (let file of files) {
                if (file.isCode() || file.isOpen()) {
                    file.setShowInvisibles(show);
                }
            }
        };
        this.getEditorLiveAutocompletion = function() {
            return options.editorLiveAutocompletion;
        };
        this.setEditorLiveAutocompletion = function(enable) {
            options.editorLiveAutocompletion = enable;
            for (let file of files) {
                if (file.isCode() || file.isOpen()) {
                    file.setLiveAutocompletion(enable);
                }
            }
        };
        this.addTab = function(fid) {
            if (rootObj.find('#vpl_file' + fid).length > 0) {
                return;
            }
            var hlink = '<a href="#vpl_file' + fid + '"></a>';
            tabsUl.append('<li id="vpl_tab_name' + fid + '">' + hlink + '</li>');
            tabs.append('<div id="vpl_file' + fid + '" class="vpl_ide_file"></div>');
        };
        this.removeTab = function(fid) {
            tabsUl.find('#vpl_tab_name' + fid).remove();
            tabs.find('#vpl_file' + fid).remove();
        };
        this.isReadOnly = function(fileName) {
            return this.readOnly || this.readOnlyFiles.indexOf(fileName) != -1;
        };
        /**
         * Open file in the IDE.
         * @param {number|object} pos Position of the file in the files array or the file object.
         */
        this.openFile = function(pos) {
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
        this.closeFile = function(file) {
            if (!file.isOpen()) {
                return;
            }
            const fid = file.getId();
            file.close();
            LSManager.closeFile(file);
            VPLUI.clearIDEStatus();
            let ptab = self.getTabPos(fid);
            const lastTab = ptab === openFiles.length - 1;
            openFiles.splice(ptab, 1);
            self.removeTab(fid);
            tabs.tabs('refresh');
            adjustTabsTitles(false);
            self.fileListVisible(true);
            VPLUtil.delay('updateFileList', self.updateFileList);
            VPLUtil.delay('adjustTabsTitles', adjustTabsTitles, false);
            if (lastTab) {
                ptab--;
            }
            if (ptab >= 0 && openFiles.length > ptab) {
                self.gotoFile(openFiles[ptab], 'c');
            }
        };
        this.isClosed = function(pos) {
            const file = (typeof pos == 'object') ? pos : files[pos];
            return !file || !file.isOpen();
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
            options.editorFontSize = size;
            for (let file of files) {
                file.setFontSize(size);
            }
        };
        this.getFontSize = function() {
            return options.editorFontSize;
        };
        this.getTerminalFontSize = function() {
            return terminal.getFontSize();
        };
        this.setTerminalFontSize = function(size) {
            options.terminalFontSize = size;
            terminal.setFontSize(size);
        };
        this.getTerminalTheme = function() {
            return terminal ? terminal.getTheme() : options.terminalTheme;
        };
        this.setTerminalTheme = function(theme) {
            options.terminalTheme = theme;
            terminal.setTheme(theme);
        };
        /**
         * Adds a file to the IDE.
         * @param {object} file Object with name, contents and encoding of the file.
         * @param {boolean} [replace=false] Whether to replace the file if it already exists.
         * @param {function} ok Callback function to be called if the file is added successfully.
         * @param {function} showError Callback function to be called if there is an error adding the file.
         */
        this.addFile = function(file, replace, ok, showError) {
            if ((typeof file.name != 'string') || !VPLUtil.validPath(file.name)) {
                showError(str('incorrect_file_name') + '\n(' + file.name + ')');
                return false;
            }
            if (replace !== true) {
                replace = false;
            }
            var pos = this.fileNameExists(file.name);
            if (pos != -1) {
                if (replace && !files[pos].isReadOnly()) {
                    if (files[pos].getContent() != file.contents) {
                        files[pos].setContent(file.contents);
                        self.setModified();
                    }
                    ok();
                    VPLUtil.delay('updateFileList', self.updateFileList);
                    return files[pos];
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
                showError(str('maxfilesexceeded') + '\n(' + maxNumberOfFiles + ')');
                return false;
            }
            var fid = VPLUtil.getUniqueId();
            var newfile = new VPLFile(fid, file.name, file.contents, fileManager);
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
            LSManager.newFile(newfile);
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
                let file = files[pos];
                if (file.getId() < this.minNumberOfFiles) {
                    throw new Error("Cannot rename required filename");
                }
                // No change.
                if (file.getFileName() == newname) {
                    return true; // Equals name file.
                }
                // No valid new name.
                if (!VPLUtil.validPath(newname) ||
                        fileNameIncluded(newname, pos) ||
                        twoBlockly(oldname, newname)) {
                    throw str('incorrect_file_name');
                }
                // Binary files cannot change extension.
                if (file.isBinary() && VPLUtil.fileExtension(oldname) != VPLUtil.fileExtension(newname)) {
                    throw str('incorrect_file_name');
                }
                // Can not change from binary to text or viceversa.
                if (file.isBinary() != VPLUtil.isBinary(newname)) {
                    throw str('incorrect_file_name');
                }
                // Can not change from blockly to text or viceversa.
                if (VPLUtil.isBlockly(oldname) != VPLUtil.isBlockly(newname)) {
                    if (file.getContent() > '') {
                        showMessage(str('delete_file_fq', oldname), {
                            ok: function() {
                                var fileToAdd = {
                                    name: newname,
                                    contents: '',
                                    encoding: 0
                                };
                                fileManager.deleteFile(oldname, showError);
                                var fileResult = fileManager.addFile(fileToAdd, false, updateMenu, showError);
                                if (fileResult) {
                                    fileManager.gotoFileName(newname);
                                }
                            }
                        });
                    } else {
                        var fileToAdd = {
                            name: newname,
                            contents: '',
                            encoding: 0
                        };
                        fileManager.deleteFile(oldname, showError);
                        var fileResult = fileManager.addFile(fileToAdd, false, updateMenu, showError);
                        if (fileResult) {
                            fileManager.gotoFileName(newname);
                        }
                    }
                    return true;
                }
                let oldFile = new VPLFile('', file.getFileName(), '', fileManager);
                file.setFileName(newname);
                LSManager.renameFile(oldFile, file);
            } catch (e) {
                showError(str('filenotrenamed', oldname) + '\n' + e);
                return false;
            }
            self.setModified();
            self.currentFile('setModified');
            self.currentFile('updateStatus');
            adjustTabsTitles(false);
            VPLUtil.delay('updateFileList', self.updateFileList);
            return true;
        };
        this.directoryExists = function(dirName) {
            var checkName = dirName.toLowerCase() + '/';
            for (var i = 0; i < files.length; i++) {
                if (files[i].getFileName().toLowerCase().startsWith(checkName)) {
                    return true;
                }
            }
            return false;
        };
        this.renameDirectory = function(oldName, newName, showError) {
            if (oldName == newName) {
                return false;
            }
            try {
                if (!this.directoryExists(oldName)) {
                    throw new Error("Trying to rename a directory that doesn't exist: " + oldName);
                }
                if (!VPLUtil.validPath(newName + '/file.txt')) {
                    throw str('incorrect_directory_name');
                }
                // Prepare new names
                var oldNameLength = oldName.length + 1;
                var checkDirName = oldName.toLowerCase() + '/';
                var newFileNames = [];
                var i;
                for (i = 0; i < files.length; i++) {
                    var fileName = files[i].getFileName();
                    if (fileName.toLowerCase().startsWith(checkDirName)) {
                        if (files[i].getId() < this.minNumberOfFiles) { // Renaming required filename
                            throw str('incorrect_file_name');
                        }
                        newFileNames[i] = newName + '/' + fileName.substring(oldNameLength);
                    }
                }
                if (this.directoryExists(newName)) { // Checks if the merge is possible (no repeated names)
                    var oldNames = [];
                    for (i = 0; i < files.length; i++) {
                        oldNames[files[i].getFileName().toLowerCase()] = true;
                    }
                    for (i = 0; i < files.length; i++) {
                        if (newFileNames[i] && oldNames[newFileNames[i].toLowerCase()]) {
                            throw str('incorrect_file_name');
                        }
                    }
                }
                // Set the new file names
                for (i = 0; i < newFileNames.length; i++) {
                    if (newFileNames[i]) {
                        let newFile = files[i];
                        let oldFile = new VPLFile('', newFile.getFileName(), '', fileManager);
                        newFile.setFileName(newFileNames[i]);
                        LSManager.renameFile(oldFile, newFile);
                    }
                }
            } catch (e) {
                showError(str('directory_not_renamed', oldName) + '\n' + e);
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
            if (files[pos].getId() < minNumberOfFiles) {
                showError(str('filenotdeleted', name));
                return false;
            }
            this.setModified();
            this.closeFile(files[pos]);
            LSManager.deleteFile(files[pos]);
            files.splice(pos, 1);
            if (openFiles.length == 0) {
                VPLUI.clearIDEStatus();
            } else {
                self.currentFile('updateStatus');
            }
            VPLUtil.delay('updateFileList', self.updateFileList);
            return true;
        };
        this.currentFile = function() {
            // The active value is a DOM tab index (jQuery UI), not an openFiles index.
            // Resolve it through the tab <li> id so it always maps to the right file
            // even if openFiles order differs from the DOM tab order.
            var index = tabs.tabs('option', 'active');
            var li = tabsUl.children('li').eq(index);
            var file = false;
            if (li.length > 0) {
                var fid = parseInt(li.attr('id').replace('vpl_tab_name', ''), 10);
                for (var i = 0; i < openFiles.length; i++) {
                    if (openFiles[i].getId() == fid) {
                        file = openFiles[i];
                        break;
                    }
                }
            }
            if (file) {
                if (arguments.length === 0) {
                    return file;
                }
                var action = arguments[0];
                if (typeof file[action] === 'function') {
                    var fun = file[action];
                    var args = Array.prototype.slice.call(arguments);
                    args.shift();
                    return fun.apply(file, args);
                }
            }
            return false;
        };
        this.getCurrentFileName = function() {
            var currentFileName = '';
            var currentFile = fileManager.currentFile();
            if (currentFile) {
                currentFileName = currentFile.getFileName();
            }
            return currentFileName;
        };
        this.currentPos = function() {
            return tabs.tabs('option', 'active');
        };
        this.getFileTab = function(id) {
            var li = tabsUl.children('#vpl_tab_name' + id);
            if (li.length === 0) {
                return -1;
            }
            return tabsUl.children('li').index(li);
        };
        this.getFilePosById = function(id) {
            for (var i = 0; i < files.length; i++) {
                if (files[i].getId() == id) {
                    return i;
                }
            }
            return -1;
        };
        this.gotoFile = function(pos, l, setFocus = true) {
            var file = files[pos];
            if (!file) {
                return;
            }
            self.openFile(file);
            tabs.tabs('option', 'active', self.getFileTab(file.getId()));
            if (l != undefined && l !== 'c') {
                file.gotoLine(parseInt(l, 10));
            }
            if (setFocus) {
                file.focus();
            }
        };
        this.gotoFileLink = function(link) {
            var linkTag = $(link);
            var fileName = linkTag.data('file');
            var fpos = -1;
            if (fileName > '') {
                fpos = this.fileNameExists(fileName);
            } else {
                fpos = self.getFilePosById(linkTag.data('fileid'));
            }
            if (fpos >= 0) {
                var line = linkTag.data('line');
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
            self.currentFile('updateStatus');
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
        this.getFileByName = function(fileName) {
            var pos = this.fileNameExists(fileName);
            if (pos >= 0) {
                return files[pos];
            }
            return null;
        };
        this.getFiles = function() {
            return files;
        };
        this.getDirectoryStructure = function() {
            var structure = {
                isDir: true,
                content: {},
                path: '',
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
                var pathdir = '';
                for (var p = 0; p < path.length; p++) {
                    var part = path[p];
                    if (p == path.length - 1) { // File.
                        curdir.content[part] = {
                            isDir: false,
                            content: file,
                            pos: i,
                        };
                    } else {
                        pathdir += part;
                        if (!curdir.content[part]) { // New dir.
                            curdir.content[part] = {
                                isDir: true,
                                content: {},
                                path: pathdir,
                            };
                        }
                        // Descend Dir.
                        pathdir += '/';
                        curdir = curdir.content[part];
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
                var name, fd, sname, attrs, dirline, file, path, line;
                for (name in dir.content) {
                    if (dir.content.hasOwnProperty(name)) {
                        fd = dir.content[name];
                        if (fd.isDir) {
                            var dirpath = VPLUtil.sanitizeText(fd.path);
                            attrs = 'href="#" data-dirname="' + dirpath + '" ';
                            sname = VPLUtil.sanitizeText(name);
                            dirline = indent;
                            dirline += VPLUI.iconFolder() + '<a ' + attrs + '>' + sname + '</a>';
                            lines.push(dirline);
                            lister(fd, indent + dirIndent, lines);
                        } else {
                            file = fd.content;
                            sname = VPLUtil.sanitizeText(name);
                            path = VPLUtil.sanitizeText(file.getFileName());
                            if (file.isOpen()) {
                                sname = '<b>' + sname + '</b>';
                            }
                            attrs = 'href="#" data-fileid="' + file.getId() + '" title="' + path + '"';
                            line = '<a ' + attrs + '>' + sname + '</a>';
                            if (file.isModified()) {
                                line = VPLUI.iconModified() + line;
                            }
                            if (file.isReadOnly()) {
                                line = line + VPLUI.iconReadOnly();
                            } else if (file.getId() < minNumberOfFiles) {
                                line = line + VPLUI.iconRequired();
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
            fileManager.closeFile(fileManager.currentFile());
        });
        tabsUl.on('dblclick', 'span.vpl_ide_closeicon', menuButtons.getAction('delete'));
        tabsUl.on('dblclick', 'a', menuButtons.getAction('rename'));
        fileListContent.on('dblclick', 'a[data-fileid]', menuButtons.getAction('rename'));
        fileListContent.on('dblclick', 'a[data-dirname]', renameDiretoryAction);
        // LSManager creation must be here becouse init uses fileManager.
        LSManager = new VPLLS(options.ajaxurl, self, options.lsavailable, options.locale);
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
    this.setResultGrade = function(content, noUpdate) {
        var name = 'grade';
        var titleclass = 'vpl_ide_accordion_t_' + name;
        var contentclass = 'vpl_ide_accordion_c_' + name;
        if (result.find('.' + contentclass).length == 0) {
            result.append('<div class="' + titleclass + '"></div>');
            result.append('<div class="' + contentclass + '"></div>');
        }
        if (noUpdate) {
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
    /**
     * Set the content of a result tab. If content is empty, the tab is removed.
     * @param {String} name I18n key of the name of tab to set and identifier of the tab content class.
     * @param {String} content HTML content to set in the tab
     * @param {boolean} noUpdate Do not change the tab
     * @returns {boolean} true if the tab has content after after the call, false otherwise
     */
    this.setResultTab = function(name, content, noUpdate) {
        var titleclass = 'vpl_ide_accordion_t_' + name;
        var contentclass = 'vpl_ide_accordion_c_' + name;
        if (result.find('.' + contentclass).length == 0) {
            result.append('<div class="' + titleclass + '"></div>');
            result.append('<div class="' + contentclass + '"></div>');
        }
        if (noUpdate) {
            // If no update, only check if the tab has content to decide if it should be shown or not.
            return result.find('h4.' + titleclass).length > 0;
        }
        var titleTag = result.find('.' + titleclass);
        var contentTag = result.find('.' + contentclass);
        var HTMLcontent = $('<div>' + content + '</div>');
        // Downgrade h4 to h5 to avoid problems with accordion.
        HTMLcontent.find('h4').replaceWith(function() {
            return $("<h5>").append($(this).contents());
        });
        if (contentTag.html() == HTMLcontent.html()) {
            // No change. Keep the tab if it already exists, to avoid losing the tab position.
            return content > '';
        }
        if (content > '') {
            // Set content.
            titleTag.replaceWith('<h4 class="' + titleclass + '">' + str(name) + '</h4>');
            contentTag.replaceWith('<div class="ui-widget ' + contentclass + '">' + HTMLcontent.html() + '</div>');
            return true;
        } else {
            // Remove content.
            titleTag.replaceWith('<div class="' + titleclass + '"></div>');
            contentTag.replaceWith('<div class="' + contentclass + '"></div>');
            return false;
        }
    };
    this.applyMathJax = function() {
        if (typeof window.MathJax == 'object') { // MathJax is loaded
            try {
                let math = result.find(".vpl_ide_accordion_c_description")[0];
                if (math) {
                    if (window.MathJax.Hub && window.MathJax.Hub.Queue) {
                        window.MathJax.Hub.Queue(["Typeset", window.MathJax.Hub, math]);
                    } else if (window.MathJax.startup && window.MathJax.startup.promise) {
                        window.MathJax.startup.promise = window.MathJax.startup.promise
                        .then(() => window.MathJax.typesetPromise([math]))
                        .catch(e => {
                            VPLUtil.log("MathJax error" + e);
                        });
                    }
                }
            } catch (e) {
                VPLUtil.log("MathJax error" + e);
            }
        }
    };
    const initialPanelOrder = [
        'grade',
        'references',
        'variables',
        'compilation',
        'comments',
        'execution',
        'description',
    ];
    const needProcessingResult = ['compilation', 'comments'];
    const needSanitizeResult = ['execution'];

    this.setResult = function(res, go = false, clearAnnotations = true) {
        self.updateEvaluationNumber(res);
        res.description = window.VPLDescription;
        res.comments = res.evaluation;
        var files = fileManager.getFiles();
        var fileNames = [];
        if (res.compilation || res.comments) {
            for (let i = 0; i < files.length; i++) {
                fileNames[i] = files[i].getFileName();
                if (clearAnnotations) {
                    files[i].clearAnnotations();
                }
            }
        }
        var show = false;
        var gradeShow;
        for (let panelName of initialPanelOrder) {
            let hasContent;
            let panelContent = res[panelName];
            let noUpdate = panelContent === undefined;
            if (panelName == 'grade') {
                hasContent = self.setResultGrade(VPLUtil.sanitizeText(res.grade), noUpdate);
                gradeShow = hasContent;
            } else if (needProcessingResult.includes(panelName)) {
                let formated = VPLUtil.processResult(res[panelName], fileNames, files, panelName == 'compilation');
                hasContent = self.setResultTab(panelName, formated, noUpdate);
            } else if (needSanitizeResult.includes(panelName)) {
                hasContent = self.setResultTab(panelName, VPLUtil.sanitizeText(res[panelName]), noUpdate);
            } else {
                hasContent = self.setResultTab(panelName, res[panelName], noUpdate);
            }
            if (panelName == 'description' && hasContent) {
                // Description can contain math formulas, so we need to apply MathJax if it's loaded.
                self.applyMathJax();
            }
            show = show || hasContent;
        }
        if (show) {
            var currentFile = fileManager.currentFile();
            resultContainer.show();
            resultContainer.vplVisible = true;
            reinitAccordion(gradeShow ? 1 : 0);
            if (go) {
                for (let i = 0; i < files.length; i++) {
                    let annotations = files[i].getAnnotations();
                    for (let j = 0; j < annotations.length; j++) {
                        if (annotations[j].type == 'error') {
                            fileManager.gotoFile(i, annotations[j].row + 1);
                            break;
                        }
                    }
                }
            } else if (currentFile) {
                // Refreshing the current file must not take the focus, e.g. from the console.
                fileManager.gotoFile(fileManager.getFilePosById(currentFile.getId()), undefined, false);
            }
            $('.vpl_ide_statusbar_shrightpanel').show();
        } else {
            resultContainer.hide();
            resultContainer.vplVisible = false;
            $('.vpl_ide_statusbar_shrightpanel').hide();
        }
        VPLUtil.delay('autoResizeTab', autoResizeTab);
    };

    var accordionOptions = {
        heightStyle: 'fill',
        header: 'h4',
        animate: false,
        beforeActivate: avoidSelectGrade,
    };
    var reinitAccordion = function(activeIndex) {
        if (result.hasClass('ui-accordion')) {
            result.accordion('destroy');
        }
        result.accordion(accordionOptions);
        if (typeof activeIndex !== 'undefined') {
            result.accordion('option', 'active', activeIndex);
        }
    };
    result.accordion(accordionOptions);
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
    fileList.html(VPLUI.iconFolder() + fileList.html());
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
        var statusbarHeight = $('#vpl_ide_statusbar').outerHeight() || 0;
        newHeight -= menu.offset().top + menu.outerHeight() + getTabsAir();
        newHeight -= statusbarHeight;
        if (newHeight < 250) {
            newHeight = 250;
        }
        tr.height(newHeight);
        var panelHeight = newHeight - 2 * getTabsAir();
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
        if (last.length == 1) {
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
        if (resultContainer.vplVisible) {
            result.accordion('refresh');
        }
        fileManager.currentFile('adjustSize');
    };
    /**
     * Transfer focus to current file
     */
    function focusCurrentFile() {
        fileManager.currentFile('focus');
    }
    /**
     * Transfer focus away from the current file
     */
    function blurCurrentFile() {
        fileManager.currentFile('blur');
    }
    var dialogbaseOptions = $.extend({}, {
        close: focusCurrentFile
    }, VPLUI.dialogbaseOptions);
    /**
     * Shows a dialog with a message.
     * @param {string} message
     * @param {Object} options icon, title, actions handler (ok, yes, no, close)
     * @returns {JQuery} JQueryUI Dialog object already open
     */
    function showMessage(message, options) {
        return VPLUI.showMessage(message, $.extend({}, dialogbaseOptions, options));
    }
    showErrorMessage = function(message) {
        return VPLUI.showErrorMessage(message, {
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
            fileManager.gotoFileName(newfile.getFileName());
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
    VPLUI.setDialogTitleIcon(dialogNew, 'new');

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
    VPLUI.setDialogTitleIcon(dialogRename, 'rename');

    var dialogRenameDirectory = $('#vpl_ide_dialog_renamedir');
    /**
     * The event handler for rename a directory
     * @param {Object} event
     */
    function renameDirectoryHandler(event) {
        if (!(event.type == 'click' || ((event.type == 'keypress') && event.keyCode == 13))) {
            return;
        }
        dialogRenameDirectory.dialog('close');
        fileManager.renameDirectory($('#vpl_ide_input_olddirectoryname').val(),
                $('#vpl_ide_input_renamedirectory').val(), showErrorMessage);
        event.preventDefault();
    }
    dialogRenameDirectory.find('input').on('keypress', renameDirectoryHandler);
    dialogButtons[str('ok')] = renameDirectoryHandler;
    dialogRenameDirectory.dialog($.extend({}, dialogbaseOptions, {
        title: str('rename_directory'),
        buttons: dialogButtons
    }));
    VPLUI.setDialogTitleIcon(dialogRenameDirectory, 'filelist');
    renameDiretoryAction = function(event) {
        if (event.target.hasAttribute('data-dirname')) {
            var dirname = event.target.getAttribute('data-dirname');
            $('#vpl_ide_input_olddirectoryname').val(dirname);
            $('#vpl_ide_input_renamedirectory').val(dirname);
            dialogRenameDirectory.dialog('open');
        }
    };
    var dialogComments = $('#vpl_ide_dialog_comments');
    var oldStudentComments = '';
    dialogButtons[str('ok')] = function() {
        if (oldStudentComments != $('#vpl_ide_input_comments').val()) {
            fileManager.setModified();
        }
        $(this).dialog('close');
    };
    dialogComments.dialog($.extend({}, dialogbaseOptions, {
        open: function() {
            oldStudentComments = $('#vpl_ide_input_comments').val();
        },
        title: str('comments'),
        width: '40em',
        buttons: dialogButtons
    }));
    VPLUI.setDialogTitleIcon(dialogComments, 'comments');

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
    VPLUI.setDialogTitleIcon(shortcutDialog, 'shortcuts');

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
    VPLUI.setDialogTitleIcon(aboutDialog, 'about');

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
                if (files[i].getId() < minNumberOfFiles) {
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
    VPLUI.setDialogTitleIcon(dialogSort, 'sort');

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
    VPLUI.setDialogTitleIcon(dialogMultidelete, 'multidelete');

    var dialogPreferences = $('#vpl_ide_dialog_preferences');
    var prefEditorThemeSelect = $('#vpl_ide_preferences_editor_theme');
    var prefEditorFontsizeSlider = $('#vpl_ide_dialog_preferences .vpl_fontsize_slider');
    var prefEditorFontsizeValue = $('#vpl_ide_dialog_preferences .vpl_fontsize_slider_value');
    var prefEditorKeybindingSelect = $('#vpl_ide_preferences_editor_keybinding');
    var prefEditorShowinvisiblesCheck = $('#vpl_ide_preferences_editor_showinvisibles');
    var prefEditorLiveautocompletionCheck = $('#vpl_ide_preferences_editor_liveautocompletion');
    var prefTerminalThemeSelect = $('#vpl_ide_preferences_terminal_theme');
    var prefTerminalFontsizeSlider = $('#vpl_ide_dialog_preferences .vpl_termfontsize_slider');
    var prefTerminalFontsizeValue = $('#vpl_ide_dialog_preferences .vpl_termfontsize_slider_value');
    // Snapshot saved on dialog open, used to revert on cancel.
    var prefSnapshot = {};
    var populateSelect = function(select, options, selected) {
        select.empty();
        options.forEach(function(opt) {
            select.append($('<option>', {value: opt.value, text: opt.label}));
        });
        select.val(selected);
    };
    var loadPreferencesDialogState = function() {
        // Read live state from fileManager (source of truth).
        prefSnapshot = {
            editorTheme: fileManager.getTheme(),
            editorFontSize: fileManager.getFontSize(),
            editorKeyBinding: fileManager.getEditorKeyBinding(),
            editorShowInvisibles: fileManager.getEditorShowInvisibles(),
            editorLiveAutocompletion: fileManager.getEditorLiveAutocompletion(),
            terminalTheme: fileManager.getTerminalTheme(),
            terminalFontSize: fileManager.getTerminalFontSize(),
        };
        // Populate editor themes from ace/ext/themelist when ace is available.
        var aceThemeOptions = [];
        if (typeof ace !== 'undefined') {
            try {
                var themeList = ace.require('ace/ext/themelist');
                if (themeList && themeList.themes) {
                    aceThemeOptions = themeList.themes.map(function(t) {
                        return {value: t.name, label: t.caption};
                    });
                }
            } catch (e) { /* Ace/ext/themelist not loaded yet */ }
        }
        if (aceThemeOptions.length === 0) {
            // Fallback static list.
            aceThemeOptions = [
                'ambiance', 'chaos', 'chrome', 'clouds', 'clouds_midnight', 'cobalt',
                'crimson_editor', 'dawn', 'dracula', 'dreamweaver', 'eclipse', 'github',
                'gob', 'gruvbox', 'idle_fingers', 'iplastic', 'katzenmilch', 'kr_theme',
                'kuroir', 'merbivore', 'merbivore_soft', 'mono_industrial', 'monokai',
                'pastel_on_dark', 'solarized_dark', 'solarized_light', 'sqlserver',
                'terminal', 'textmate', 'tomorrow', 'tomorrow_night', 'tomorrow_night_blue',
                'tomorrow_night_bright', 'tomorrow_night_eighties', 'twilight',
                'vibrant_ink', 'xcode'
            ].map(function(v) {
                var name = v.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
                return {value: v, label: name};
            });
        }
        populateSelect(prefEditorThemeSelect, aceThemeOptions, prefSnapshot.editorTheme);
        const keybindingOptions = [
            {value: 'Ace', label: 'Ace'},
            {value: 'vim', label: 'Vim'},
            {value: 'emacs', label: 'Emacs'},
            {value: 'sublime', label: 'Sublime'},
            {value: 'vscode', label: 'VS Code'},
        ];
        populateSelect(prefEditorKeybindingSelect, keybindingOptions, prefSnapshot.editorKeyBinding);
        prefEditorFontsizeSlider.slider('value', prefSnapshot.editorFontSize);
        prefEditorFontsizeValue.text(prefSnapshot.editorFontSize);
        prefEditorShowinvisiblesCheck.prop('checked', prefSnapshot.editorShowInvisibles);
        prefEditorLiveautocompletionCheck.prop('checked', prefSnapshot.editorLiveAutocompletion);
        // Populate terminal themes from terminal instance.
        var terminalThemes = terminal.getThemeNames ? terminal.getThemeNames() : [];
        var termThemeOptions = terminalThemes.map(function(t) {
            return {value: t, label: t};
        });
        populateSelect(prefTerminalThemeSelect, termThemeOptions, prefSnapshot.terminalTheme);
        prefTerminalFontsizeSlider.slider('value', prefSnapshot.terminalFontSize);
        prefTerminalFontsizeValue.text(prefSnapshot.terminalFontSize);
    };
    var dialogPreferencesButtons = {};
    dialogPreferencesButtons[str('ok')] = function() {
        var editorTheme = prefEditorThemeSelect.val();
        var editorFontSize = prefEditorFontsizeSlider.slider('value');
        var editorKeyBinding = prefEditorKeybindingSelect.val();
        var editorShowInvisibles = prefEditorShowinvisiblesCheck.prop('checked');
        var editorLiveAutocompletion = prefEditorLiveautocompletionCheck.prop('checked');
        var terminalTheme = prefTerminalThemeSelect.val();
        var terminalFontSize = prefTerminalFontsizeSlider.slider('value');
        fileManager.setTheme(editorTheme);
        fileManager.setFontSize(editorFontSize);
        fileManager.setEditorKeyBinding(editorKeyBinding);
        fileManager.setEditorShowInvisibles(editorShowInvisibles);
        fileManager.setEditorLiveAutocompletion(editorLiveAutocompletion);
        fileManager.setTerminalTheme(terminalTheme);
        fileManager.setTerminalFontSize(terminalFontSize);
        VPLUtil.setUserPreferences({
            editorTheme: editorTheme,
            editorFontSize: editorFontSize,
            editorKeyBinding: editorKeyBinding,
            editorShowInvisibles: editorShowInvisibles,
            editorLiveAutocompletion: editorLiveAutocompletion,
            terminalTheme: terminalTheme,
            terminalFontSize: terminalFontSize,
        });
        $(this).dialog('close');
    };
    dialogPreferencesButtons[str('cancel')] = function() {
        // Revert all live changes made while the dialog was open.
        fileManager.setTheme(prefSnapshot.editorTheme);
        fileManager.setFontSize(prefSnapshot.editorFontSize);
        fileManager.setEditorKeyBinding(prefSnapshot.editorKeyBinding);
        fileManager.setEditorShowInvisibles(prefSnapshot.editorShowInvisibles);
        fileManager.setEditorLiveAutocompletion(prefSnapshot.editorLiveAutocompletion);
        fileManager.setTerminalTheme(prefSnapshot.terminalTheme);
        fileManager.setTerminalFontSize(prefSnapshot.terminalFontSize);
        $(this).dialog('close');
    };
    dialogPreferences.dialog($.extend({}, dialogbaseOptions, {
        title: str('preferences'),
        buttons: dialogPreferencesButtons,
        open: function() {
            loadPreferencesDialogState();
        },
    }));
    prefEditorFontsizeSlider.slider({
        min: 1,
        max: 48,
        change: function() {
            var value = prefEditorFontsizeSlider.slider('value');
            fileManager.setFontSize(value);
            prefEditorFontsizeValue.text(value);
        }
    });
    prefTerminalFontsizeSlider.slider({
        min: 1,
        max: 48,
        change: function() {
            var value = prefTerminalFontsizeSlider.slider('value');
            fileManager.setTerminalFontSize(value);
            prefTerminalFontsizeValue.text(value);
        }
    });
    prefEditorThemeSelect.on('change', function() {
        fileManager.setTheme(prefEditorThemeSelect.val());
    });
    VPLUI.setDialogTitleIcon(dialogPreferences, 'preferences');

    var terminal = new VPLTerminal('vpl_dialog_terminal', 'vpl_terminal', str);
    var VNCClient = new VPLVNCClient('vpl_dialog_vnc', str);
    var lastConsole = terminal;
    var fileSelect = $('#vpl_ide_input_file');
    var fileSelectHandler = function() {
        VPLUI.readSelectedFiles(this.files, function(file) {
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
            win: 'Ctrl-Alt-L',
            mac: 'Ctrl-Option-L'
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
            if (file && file.getId() >= minNumberOfFiles) {
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
                id: 'delete',
                ok: function() {
                    fileManager.deleteFile(filename, showErrorMessage);
                },
                title: str('delete_file_q'),
                icon: 'trash'
            });
        },
        bindKey: {
            win: 'Alt-D',
            mac: 'Option-D'
        }
    });
    menuButtons.add({
        name: 'close',
        originalAction: function() {
            var file = fileManager.currentFile();
            if (!file) {
                return;
            }
            fileManager.closeFile(file);
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
        name: 'showparentfiles',
        originalAction: function() {
            openpopup(null, {
                url: options.showparentfilesurl,
                options: 'width=' + Math.max(screen.availWidth / 2, 780) +
                            ',height=' + screen.availHeight +
                            ',left=' + (screen.availWidth / 4)
            });
        }
    });
    menuButtons.add({
        name: 'preferences',
        originalAction: function() {
            dialogPreferences.dialog('open');
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
            if (fullScreen) {
                rootObj.removeClass('vpl_ide_root_fullscreen');
                $('body').removeClass('vpl_body_fullscreen');
                menuButtons.setText('fullscreen', 'fullscreen');
                $('#vpl_ide_user').hide();
                fullScreen = false;
            } else {
                $(window).scrollTop(0);
                $('body').addClass('vpl_body_fullscreen').scrollTop(0);
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
        VPLUI.requestAction('resetfiles', '', {}, options.ajaxurl)
        .done(function(response) {
            for (var resetFile of response.files) {
                let pos = fileManager.fileNameExists(resetFile.name);
                if (pos != -1) {
                    // File already exists, update content if needed.
                    let file = fileManager.getFiles()[pos];
                    if (file.getContent() != resetFile.contents) {
                        file.setContent(resetFile.contents);
                        file.setModified(true);
                        self.setModified(true);
                    }
                } else {
                    // New file, add it.
                    fileManager.addFile(resetFile, false, VPLUtil.doNothing, showErrorMessage);
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
    var noconfirmation = false;
    menuButtons.add({
        name: 'save',
        originalAction: function() {
            var data = {
                files: fileManager.getFilesToSave(),
                comments: $('#vpl_ide_input_comments').val(),
                version: noconfirmation ? -1 : fileManager.getVersion()
            };
            if (JSON.stringify(data).length > options.postMaxSize) {
                showErrorMessage(str('maxpostsizeexceeded'));
                return;
            }
            /**
             * Save action
             */
            function doSave() {
                VPLUI.requestAction('save', 'saving', data, options.ajaxurl)
                .done(function(response) {
                    if (response.requestsconfirmation && !noconfirmation) {
                        var checkboxID = 'vpl_donotshowagain';
                        var donotshowagain = '<input type="checkbox" id="' + checkboxID + '"'
                                            + ' class="align-text-bottom mr-1 mt-3">'
                                            + '<label for="' + checkboxID + '">' + str('donotshowagain') + '</label>';
                        var $checkbox;
                        showMessage(response.question + '<br>' + donotshowagain, {
                            title: str('saving'),
                            icon: 'alert',
                            yes: function() {
                                if ($checkbox.length == 1 && $checkbox.prop('checked')) {
                                    noconfirmation = true;
                                }
                                data.version = 0;
                                doSave();
                            }
                        });
                        $checkbox = $('#' + checkboxID);
                    } else {
                        fileManager.resetModified();
                        fileManager.setVersion(response.version);
                        menuButtons.setTimeLeft(response);
                        VPLUtil.delay('updateMenu', updateMenu);
                        if (VPLUI.monitorRunning() || fileManager.getLSManager().isConnected()) {
                            data.processid = VPLUtil.getProcessId();
                            VPLUI.requestAction('update', '', data, options.ajaxurl, true);
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
            VPLUI.requestAction(action, '', data, options.ajaxurl)
            .done(function(response) {
                VPLUI.webSocketMonitor(response, action, acting, executionActions);
            })
            .fail(showErrorMessage);
        }
    }
    /**
     * Launches the run action
     */
    function runAction() {
        executionRequest('run', 'running', {
            XGEOMETRY: VNCClient.getCanvasSize(),
            currentFileName: fileManager.getCurrentFileName(),
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
            XGEOMETRY: VNCClient.getCanvasSize(),
            currentFileName: fileManager.getCurrentFileName(),
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
        name: 'shrightpanel',
        icon: 'close-rightpanel',
        originalAction: function() {
            if (resultContainer.vplVisible) {
                resultContainer.hide();
                resultContainer.vplVisible = false;
                menuButtons.setText('shrightpanel', 'open-rightpanel', VPLUtil.str('shrightpanel'));
            } else {
                menuButtons.setText('shrightpanel', 'close-rightpanel', VPLUtil.str('shrightpanel'));
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
    menuHtml += menuButtons.getHTML('new');
    menuHtml += menuButtons.getHTML('rename');
    menuHtml += menuButtons.getHTML('delete');
    menuHtml += menuButtons.getHTML('import');
    menuHtml += menuButtons.getHTML('download');
    menuHtml += menuButtons.getHTML('resetfiles');
    menuHtml += menuButtons.getHTML('sort');
    menuHtml += menuButtons.getHTML('multidelete');
    menuHtml += menuButtons.getHTML('showparentfiles');
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
    $('#vpl_ide_about').button();
    $('#vpl_ide_user').button().css('float', 'right').hide();
    $('#vpl_ide_timeleft').button().css('float', 'right').hide();
    $('.vpl_ide_statusbar_filelist').append(menuButtons.getHTML('filelist'));
    $('.vpl_ide_statusbar_preferences').append(menuButtons.getHTML('preferences'));
    $('.vpl_ide_statusbar_shrightpanel').append(menuButtons.getHTML('shrightpanel'));
    $('#vpl_menu .ui-button').css('padding', '6px');
    $('#vpl_menu .ui-button-text').css('padding', '0');
    var alwaysActive = ['filelist', 'more', 'fullscreen', 'about', 'resetfiles',
                        'download', 'comments', 'console', 'import',
                        'preferences', 'timeleft', 'shrightpanel'];
    for (let button of alwaysActive) {
        menuButtons.enable(button, true);
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
        var running = VPLUI.monitorRunning();
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
        menuButtons.enable('showparentfiles', !modified);
        menuButtons.enable('theme', true);
        var sel;
        if (!file || nfiles === 0) {
            sel = ['rename', 'delete', 'undo', 'redo', 'select_all', 'find', 'find_replace', 'next'];
            for (i = 0; i < sel.length; i++) {
                menuButtons.enable(sel[i], false);
            }
            return;
        }
        menuButtons.enable('rename', file.getId() >= minNumberOfFiles && nfiles !== 0);
        menuButtons.enable('delete', file.getId() >= minNumberOfFiles && nfiles !== 0);
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
        'close': function() {
            VPLUI.updateIDEStatus({action: null});
            updateMenu();
        },
        'getConsole': function() {
            return lastConsole;
        },
        'setResult': self.setResult,
        'ajaxurl': options.ajaxurl,
        'run': function(content, coninfo, ws) {
            var parsed = /^([^:]*):?(.*)/.exec(content);
            var type = parsed[1];
            if (type == 'terminal' || type == 'webterminal') {
                if (lastConsole && lastConsole.isOpen()) {
                    lastConsole.close();
                }
                lastConsole = terminal;
                terminal.connect(coninfo.executionURL, blurCurrentFile, function() {
                    ws.close();
                    focusCurrentFile();
                });
                if (type == 'webterminal') {
                    // Load favicon to get the cookie iwh
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
                URL += VPLUtil.sanitizeText(parsed[2]) + "/httpPassthrough";
                if (isTeacher) {
                    URL += "?private";
                }
                var action = {};
                action.href = URL;
                action.target = "_blank";
                action.rel = "noopener noreferrer";
                if (isTeacher) {
                    action.icon = 'open-private-browser';
                    action.text = VPLUtil.str('open_private_browser');
                } else {
                    action.icon = 'open-browser';
                    action.text = VPLUtil.str('open_browser');
                }
                VPLUI.updateIDEStatus({action: action});
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
    fileManager = new FileManager(self, options);
    self.fileManager = fileManager;
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
    VPLUI.requestAction('load', 'loading', options, options.loadajaxurl)
    .done(function(response) {
        let allOK = true;
        let loadedFiles = response.files;
        let showFileList = false;
        let openFirstFile = false;
        for (let loadFile of loadedFiles) {
            let file = fileManager.addFile(loadFile, false, updateMenu, showErrorMessage);
            if (file) {
                file.resetModified();
            } else {
                allOK = false;
            }
        }
        fileManager.getLSManager().startConnections();
        if (allOK) {
            let existingFiles = fileManager.getFiles();
            for (let i = 0; i < existingFiles.length; i++) {
                let file = existingFiles[i];
                if (i < minNumberOfFiles || existingFiles.length <= 5) {
                    fileManager.openFile(file);
                    openFirstFile = true;
                } else {
                    showFileList = true;
                }
            }
        }
        if (openFirstFile) {
            tabs.tabs('option', 'active', 0);
        }
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
        fileManager.setVersion(response.version);
        fileManager.fileListVisible(showFileList);
        VPLUtil.afterAll('AfterLoadFiles', function() {
            updateMenu();
            autoResizeTab();
            adjustTabsTitles(true);
            if (openFirstFile) {
                let file = fileManager.getFiles()[0];
                file.open();
                file.focus();
            }
        });
    })
    .fail(showErrorMessage);
};

export const init = (rootId, options) => {
    new VPLIDE(rootId, options);
};
