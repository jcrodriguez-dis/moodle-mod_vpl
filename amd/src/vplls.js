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
 * Language Server Client
 *
 * @copyright 2022 Héctor Miguel Martín Álvarez
 * @copyright 2026 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Héctor Miguel Martín Álvarez
 * @author Juan Carlos Rodríguez-del-Pino
 */

import {VPLUtil} from 'mod_vpl/vplutil';
import {VPLLSClient} from 'mod_vpl/vpllsclient';
import {VPLUI} from 'mod_vpl/vplui';

/**
 * VPLLSClient class that implements the Language Server Protocol client
 * @param {String} APIURL URL to the VPL plugin API
 * @param {FileManager} fileManager FileManager instance to manage the files in the client
 * @param {Array.<String>} LSAvailable Array of programming languages with Language Server available.
 * @param {String} userLocale User's locale
 */
export const VPLLS = function(APIURL, fileManager, LSAvailable, userLocale) {
    if (LSAvailable.length == 0) {
        VPLUtil.log("No LS available for the current VPL activity.");
        // Set all methods to do nothing to avoid errors when trying to use the LS client.
        this.getLS = function() {
            return null;
        };
        this.newFile = VPLUtil.doNothing;
        this.deleteFile = VPLUtil.doNothing;
        this.renameFile = VPLUtil.doNothing;
        this.openFile = VPLUtil.doNothing;
        this.openFileNotification = VPLUtil.doNothing;
        this.closeFileNotification = VPLUtil.doNothing;
        this.didChangeWatchedFilesNotification = VPLUtil.doNothing;
        this.hoverRequest = VPLUtil.doNothing;
        this.definitionRequest = VPLUtil.doNothing;
        this.referencesRequest = VPLUtil.doNothing;
        this.implementationRequest = VPLUtil.doNothing;
        this.renameSymbolRequest = VPLUtil.doNothing;
        this.renameSymbolHandler = VPLUtil.doNothing;
        this.formattingRequest = VPLUtil.doNothing;
        this.rangeFormattingRequest = VPLUtil.doNothing;
        this.codeActionRequest = VPLUtil.doNothing;
        this.executeCodeAction = VPLUtil.doNothing;
        this.startConnections = VPLUtil.doNothing;
        this.isConnected = function() {
            return false;
        };
        this.getStatus = function() {
            return '';
        };
        this.init = VPLUtil.doNothing;
        return;
    }
    // Time in milliseconds to delay the closing of contextualmenu
    const delayedCloseTime = 300;
    // Variable to access the VPLLS instance in inner functions.
    const self = this;
    // Global variable to access the Language Server Client instances.
    var languageServers = {};
    // Started to connect LSs. IDE ready to use LS features.
    var startedConnections = false;

    this.getLSByLanguage = function(language) {
        if (languageServers[language] == undefined) {
            if (LSAvailable.includes(language)) {
                languageServers[language] = new VPLLSClient(APIURL, fileManager, language, userLocale);
                if (startedConnections) {
                    languageServers[language].startConnection();
                }
            } else {
                return null;
            }
        }
        let LSInstance = languageServers[language];
        return LSInstance.isConnected() ? LSInstance : null;
    };
    this.getLS = function(file) {
        if (!file) {
            return null;
        }
        return self.getLSByLanguage(file.getLSLang());
    };
    this.getLSByFileName = function(fileName) {
        return self.getLSByLanguage(VPLUtil.getFileLangInfo(fileName).lsName);
    };
    this.startConnections = function() {
        for (let language of Object.keys(languageServers)) {
            if (languageServers[language]?.isStopped()) {
                languageServers[language].startConnection();
            }
        }
        startedConnections = true;
    };
    this.isConnected = function() {
        for (let language of Object.keys(languageServers)) {
            if (languageServers[language]?.isConnected()) {
                return true;
            }
        }
        return false;
    };
    this.getStatus = function(file) {
        return languageServers[file?.getLSLang()]?.getStatus() ?? '';
    };
    this.createFileNotification = async function(LS, fileName) {
        if (LS === null || !LS.getCapabilities().supportsDidCreateFiles()) {
            return {result: null};
        }
        var param = {
            "files": [{
                "uri": LS.fileNameToUri(fileName),
            }]
        };
        return LS.sendNotification("workspace/didCreateFiles", param);
    };

    this.deleteFileNotification = async function(LS, fileName) {
        if (LS === null || !LS.getCapabilities().supportsDidDeleteFiles()) {
            return {result: null};
        }
        var param = {
            "files": [{
                "uri": LS.fileNameToUri(fileName),
            }]
        };
        return await LS.sendNotification("workspace/didDeleteFiles", param);
    };
    this.renameFileNotification = async function(LS, oldFileName, newFileName) {
        if (LS === null || !LS.getCapabilities().supportsDidRenameFiles()) {
            return {result: null};
        }
        var param = {
            "files": [{
                "oldUri": LS.fileNameToUri(oldFileName),
                "newUri": LS.fileNameToUri(newFileName)
            }]
        };
        return await LS.sendNotification("workspace/didRenameFiles", param);
    };

    this.openFile = async function(file) {
        let LS = self.getLS(file);
        if (LS === null) {
            return {result: null};
        }
        return LS.addTask(
            async function() {
                return self.openFileNotification(file);
            }
        );
    };
    /**
     * Create a new file in the server
     * @param {File} file
     */
    this.newFile = async function(file) {
        var resolveNewFile, rejectNewFile;
        var promiseNewFile = new Promise(function(resolve, reject) {
            resolveNewFile = resolve;
            rejectNewFile = reject;
        });
        let LS = self.getLS(file);
        if (LS === null) {
            resolveNewFile({result: null});
            return promiseNewFile;
        }
        // TODO is it needed to create de file in all LSs?
        LS.addTask(
            async function() {
                if (LS.isStopped()) {
                    resolveNewFile({result: null});
                    return promiseNewFile;
                }
                const data = {
                    files: [{
                        name: LS.fileNameToProjectPath(file.getFileName()),
                        contents: file.getContent(),
                        encoding: 0
                    }],
                    filestodelete: [],
                    processid: LS.getVPLTaskId()
                };
                VPLUI.requestAction('update', '', data, APIURL, true)
                .done(
                    async function() {
                        var response = await self.createFileNotification(LS, file.getFileName());
                        await LS.didChangeWatchedFilesNotification([{file: file, type: 1}]);
                        resolveNewFile(response);
                    }
                ).fail(function(error) {
                    rejectNewFile(error);
                });
                return promiseNewFile;
            }
        );
        return promiseNewFile;
    };

    /**
     * Delete a file in the server
     * @param {File} file
     */
    this.deleteFile = async function(file) {
        var resolveDelete, rejectDelete;
        var promiseDelete = new Promise(function(resolve, reject) {
            resolveDelete = resolve;
            rejectDelete = reject;
        });
        // TODO is it needed to remove de file in all LSs?
        let LS = self.getLS(file);
        if (LS === null) {
            resolveDelete({result: null});
            return promiseDelete;
        }
        LS.addTask(
            async function() {
                if (LS.isStopped()) {
                    resolveDelete({result: null});
                    return promiseDelete;
                }
                const data = {
                    files: [],
                    filestodelete: [LS.fileNameToProjectPath(file.getFileName())],
                    processid: LS.getVPLTaskId()
                };
                await self.closeFileNotification(file, false);
                VPLUI.requestAction('update', '', data, APIURL, true)
                .done(
                    async function() {
                        var response = await self.deleteFileNotification(LS, file.getFileName());
                        await LS.didChangeWatchedFilesNotification([{file: file, type: 3}]);
                        resolveDelete(response);
                    }
                ).fail(function(error) {
                    rejectDelete(error);
                });
                return promiseDelete;
            }
        );
        return promiseDelete;
    };

    /**
     * Send the didOpen notification to the Language Server
     * to signal newly opened text documents
     * @param {File} file
     */
    this.openFileNotification = async function(file) {
        let LS = self.getLS(file);
        if (LS === null) {
            return Promise.resolve({result: null});
        }
        return await LS.openFileNotification(file);
    };

    this.closeFile = async function(file, save = true) {
        let LS = self.getLS(file);
        if (LS === null) {
            return {result: null};
        }
        return LS.addTask(
            async function() {
                return self.closeFileNotification(file, save);
            }
        );
    };

    /**
     * Send the didClose notification to the Language Server to
     * inform the server which the document got closed in the client
     * @param {File} file
     * @param {Boolean} [save=true] Whether to save the file before closing
     */
    this.closeFileNotification = async function(file, save = true) {
        let LS = self.getLS(file);
        if (LS === null) {
            return {result: null};
        }
        LS.removeMarkersOfFile(file);
        let fileURI = LS.fileNameToUri(file.getFileName());
        var param = {
            "textDocument": {
                "uri": fileURI
            }
        };
        if (save) {
            try {
                await LS.saveFileNotification(file);
            } catch (error) {
                VPLUtil.log("Error saving file before closing: " + JSON.stringify(error));
            }
        }
        if (LS.getCapabilities().supportsOpenClose()) {
            return LS.sendNotification("textDocument/didClose", param);
        }
        return {result: null};
    };

    /**
     * Rename a file in the server
     * @param {File} oldFile Old file
     * @param {File} newFile New file
     */
    this.renameFile = async function(oldFile, newFile) {
        var resolveRename, rejectRename;
        var promiseRename = new Promise(function(resolve, reject) {
            resolveRename = resolve;
            rejectRename = reject;
        });
        let changeLanguage = oldFile.getLSLang() != newFile.getLSLang();
        try {
            if (changeLanguage) {
                let LS = self.getLS(oldFile);
                LS?.removeMarkersOfFile(newFile, oldFile.getFileName());
                await self.newFile(newFile);
                await self.openFile(newFile);
                await self.deleteFile(oldFile);
                resolveRename({result: null});
                return promiseRename;
            } else {
                let LS = self.getLS(oldFile);
                if (LS === null) {
                    resolveRename({result: null});
                    return promiseRename;
                }
                return LS.addTask(async function() {
                    if (LS.isStopped()) {
                        resolveRename({result: null});
                        return promiseRename;
                    }
                    if (newFile.isOpen()) { // Was open before rename
                        await self.closeFileNotification(oldFile, false);
                    }
                    var data = {
                        files: [{
                            name: LS.fileNameToProjectPath(newFile.getFileName()),
                            contents: newFile.getContent(),
                            encoding: 0
                        }],
                        filestodelete: [LS.fileNameToProjectPath(oldFile.getFileName())],
                        processid: LS.getVPLTaskId()
                    };
                    VPLUI.requestAction('update', '', data, APIURL, true).
                    done(async function() {
                        try {
                            var response = {result: null};
                            if (LS.getCapabilities().supportsDidRenameFiles() === false) {
                                await self.deleteFileNotification(LS, oldFile.getFileName());
                                await self.createFileNotification(LS, newFile.getFileName());
                            } else {
                                response = await self.renameFileNotification(
                                    LS, oldFile.getFileName(), newFile.getFileName());
                            }
                            await LS.didChangeWatchedFilesNotification([
                                {file: oldFile, type: 3},
                                {file: newFile, type: 1}
                            ]);
                            // Re-open the renamed document on the server if it is open in the client.
                            if (newFile.isOpen()) {
                               await self.openFileNotification(newFile);
                            }
                            resolveRename(response);
                        } catch (error) {
                            rejectRename({result: null, error: error});
                        }
                    }).fail(function(error) {
                        rejectRename({result: null, error: error});
                    });
                    return promiseRename;
                });
            }
        } catch (error) {
            rejectRename({result: null, error: error});
        }
        return promiseRename;
    };

    /**
     * Send the didChangeWatchedFiles notification to Language Servers to inform
     * the server about changes to files and folders watched by the language client
     * @param {[Object]} changedFiles File that have been created, changed or deleted {file, type}
     */
    this.didChangeWatchedFilesNotification = async function(changedFiles) {
        for (let LS of Object.values(languageServers)) {
            await LS.didChangeWatchedFilesNotification(changedFiles);
        }
    };

    /**
     * Send the definition request to the Language Server to resolve
     * the definition location of a symbol at a given text document position
     */
    this.definitionRequest = function() {
        let file = fileManager.currentFile();
        let LS = self.getLS(file);
        if (LS === null || !LS.getCapabilities().hasDefinitionProvider()) {
            return;
        }
        let fileName = file.getFileName();
        let fileURI = LS.fileNameToUri(fileName);
        let cursor = file.getEditor().getCursorPosition();
        var param = {
            "textDocument": {
                "uri": fileURI
            },
            "position": {
                "line": cursor.row,
                "character": cursor.column
            }
        };
        LS.sendRequest("textDocument/definition", param, fileName);
    };

    /**
     * Send the references request to the Language Server
     * to resolve project-wide references for the symbol
     * denoted by the given text document position
     */
    this.referencesRequest = function() {
        let file = fileManager.currentFile();
        let LS = self.getLS(file);
        if (LS === null || !LS.getCapabilities().hasReferencesProvider()) {
            return;
        }
        let editor = file.getEditor();
        let cursor = editor.getCursorPosition();
        let token = editor.getSession().getTokenAt(cursor.row, cursor.column);
        if (!(token?.value)) {
            return;
        }
        let fileName = file.getFileName();
        let fileURI = LS.fileNameToUri(fileName);
        var param = {
            "textDocument": {
                "uri": fileURI
            },
            "position": {
                "line": cursor.row,
                "character": cursor.column
            },
            "context": {
                "includeDeclaration": true
            }
        };
        LS.sendRequest("textDocument/references", param, fileName, {name: token.value});
    };

    /**
     * Send the implementation request to the Language Server
     * to resolve the implementation location of a symbol at
     * a given text document position
     */
    this.implementationRequest = function() {
        let file = fileManager.currentFile();
        let LS = self.getLS(file);
        if (LS === null || !LS.getCapabilities().hasImplementationProvider()) {
            return;
        }
        let fileName = file.getFileName();
        let cursor = file.getEditor().getCursorPosition();
        let fileURI = LS.fileNameToUri(fileName);
        var param = {
            "textDocument": {"uri": fileURI},
            "position": {
                "line": cursor.row,
                "character": cursor.column
            }
        };
        LS.sendRequest("textDocument/implementation", param, fileName);
    };

    var renameSymbolDialog = null;

    /**
     * Opens the rename Dialog
     * Send the prepareRename request to the Language Server to check if the symbol can be renamed
     */
    this.renameSymbolRequest = async function() {
        let file = fileManager.currentFile();
        let LS = self.getLS(file);
        if (LS === null || !LS.getCapabilities().hasRenameProvider()) {
            return;
        }
        let editor = file.getEditor();
        let cursor = editor.getCursorPosition();
        self.closeAllOverlays();
        var oldValue = "";
        if (LS.getCapabilities().hasRenamePrepareProvider()) {
            let fileName = file.getFileName();
            let fileURI = LS.fileNameToUri(fileName);
            var param = {
                "textDocument": {"uri": fileURI},
                "position": {
                    "line": cursor.row,
                    "character": cursor.column
                }
            };
            let response = await LS.sendRequest("textDocument/prepareRename", param, fileName);
            if (!(response?.result)) {
                LS.log("The symbol selected cannot be renamed.");
                return;
            }
            // Get the range of the symbol to rename if the server needs it to perform the rename operation
            let result = response.result;
            if (result.replaceholder) {
                oldValue = result.replaceholder;
            } else {
                const resultRange = result.range ? result.range : result;
                let range = editor.getSelectionRange();
                range.setStart(resultRange.start.line, resultRange.start.character);
                range.setEnd(resultRange.end.line, resultRange.end.character);
                editor.selection.setRange(range);
                oldValue = editor.getSelectedText();
            }
        } else {
            const token = editor.getSession().getTokenAt(cursor.row, cursor.column);
            if (token?.value) {
                oldValue = token.value;
            }
        }
        renameSymbolDialog.querySelector("input").value = oldValue;
        renameSymbolDialog.classList.toggle('ace_dark', file.isDarkTheme());
        renameSymbolDialog.style.visibility = 'visible';
        renameSymbolDialog.querySelector("input").focus();
        var coords = editor.renderer.textToScreenCoordinates(cursor.row, cursor.column);
        renameSymbolDialog.style.left = coords.pageX + "px";
        renameSymbolDialog.style.top = coords.pageY + "px";
    };

    /**
     * Send the rename symbol request to the Language Server to ask
     * the server to compute a workspace change so that the
     * client can perform a workspace-wide rename of a symbol
     * @param {String} name to change for
     */
    this.renameSymbolHandler = async function(name) {
        if (name == "") {
            return {result: null};
        }
        let file = fileManager.currentFile();
        let LS = self.getLS(file);
        if (LS === null || !LS.getCapabilities().hasRenameProvider()) {
            return {result: null};
        }
        let cursor = file.getEditor().getCursorPosition();
        let fileName = file.getFileName();
        let fileURI = LS.fileNameToUri(fileName);
        var param = {
            "textDocument": {
                "uri": fileURI
            },
            "position": {
                "line": cursor.row,
                "character": cursor.column
            },
            "newName": name
        };
        return await LS.sendRequest("textDocument/rename", param, fileName);

    };

    /**
     * Send the formatting request to the Language Server
     * to format a whole document
     */
    this.formattingRequest = function() {
        let file = fileManager.currentFile();
        let LS = self.getLS(file);
        if (LS === null || !LS.getCapabilities().hasDocumentFormattingProvider()) {
            return;
        }
        let fileName = file.getFileName();
        let fileURI = LS.fileNameToUri(fileName);
        var param = {
            "textDocument": {
                "uri": fileURI
            },
            "options": {
                "tabSize": 4,
                "insertSpaces": true,
                "trimTrailingWhitespace": true,
                "insertFinalNewline": true,
                "trimFinalNewlines": true
            }
        };
        LS.sendRequest("textDocument/formatting", param, fileName);
    };

    /**
     * Send the rangeFormatting request to the Language Server
     * to format a given range in a document
     */
    this.rangeFormattingRequest = function() {
        let file = fileManager.currentFile();
        let LS = self.getLS(file);
        if (LS === null || !LS.getCapabilities().hasDocumentRangeFormattingProvider()) {
            return;
        }
        let fileName = file.getFileName();
        let fileURI = LS.fileNameToUri(fileName);
        let cursor = file.getEditor().getSelectionRange();
        var param = {
            "textDocument": {
                "uri": fileURI
            },
            "range": {
                "start": {
                    "line": cursor.start.row,
                    "character": cursor.start.column
                },
                "end": {
                    "line": cursor.end.row,
                    "character": cursor.end.column
                }
            },
            "options": {
                "tabSize": 4,
                "insertSpaces": true,
                "trimTrailingWhitespace": true,
                "insertFinalNewline": true,
                "trimFinalNewlines": true
            }
        };
        LS.sendRequest("textDocument/rangeFormatting", param, fileName);
    };
    /**
     * Returns true if the given range is included in the given cursor selection
     * @param {Range} range The range to check
     * @param {Range} cursor The cursor selection range
     * @returns {boolean} True if the range is included in the cursor selection, false otherwise
     */
    function isRangeIncluded(range, cursor) {
        let startIncluded = range.start.row > cursor.start.row
                    || (range.start.row == cursor.start.row && range.start.column >= cursor.start.column);
        let endIncluded = range.end.row < cursor.end.row
                    || (range.end.row == cursor.end.row && range.end.column <= cursor.end.column);
        return startIncluded && endIncluded;
    }
    // Code action kinds asked to the Language Server
    const codeActionKinds = [
        'quickfix',
        'refactor',
        'refactor.extract',
        'refactor.inline',
        'refactor.rewrite',
        'source',
        'source.organizeImports'
    ];
    var lastCodeActionSuggestions = [];
    /**
     * Send the codeAction request to the Language Server
     * to compute commands for a given text document and range
     */
    this.codeActionRequest = async function() {
        let file = fileManager.currentFile();
        let LS = self.getLS(file);
        if (LS === null || !LS.getCapabilities().hasCodeActionProvider()) {
            return;
        }
        let cursor = file.getEditor().getSelectionRange();
        const onRange = cursor.start.row != cursor.end.row || cursor.start.column != cursor.end.column;
        let fileName = file.getFileName();
        let fileURI = LS.fileNameToUri(fileName);
        let diagnostics = [];
        for (let marker of LS.getAllMarkers()) {
            if (marker.fileName == fileName) {
                let range = marker.range;
                if (!onRange) {
                    diagnostics.push({
                        "range": {
                            "start": {
                                "line": range.start.row,
                                "character": range.start.column
                            },
                            "end": {
                                "line": range.end.row,
                                "character": range.end.column
                            }
                        },
                        "message": marker.text
                    });
                } else if (isRangeIncluded(range, cursor)) {
                    diagnostics.push({
                        "range": {
                            "start": {
                                "line": range.start.row,
                                "character": range.start.column
                            },
                            "end": {
                                "line": range.end.row,
                                "character": range.end.column
                            }
                        },
                        "message": marker.text
                    });
                }
            }
        }
        var param = {
            "textDocument": {
                "uri": fileURI
            },
            "range": {
                "start": {
                    "line": cursor.start.row,
                    "character": cursor.start.column
                },
                "end": {
                    "line": cursor.end.row,
                    "character": cursor.end.column
                }
            },
            "context": {
                "diagnostics": diagnostics,
                "only": codeActionKinds,
            }
        };
        lastCodeActionSuggestions = [];
        let message = await LS.sendRequest("textDocument/codeAction", param, fileName);
        showCodeActionSuggestions(LS, message);
    };
    /**
     * Create a custom context menu for code actions
     */
    function initializeCodeActionMenu() {
        var closeTimer = null;
        const menu = document.getElementById('vpl_ls_codeactions');
        /**
         * Function to delay the closing of the menu.
         * @param {number} multiplier delayedCloseTime multiplier for the delay time in milliseconds
         */
        function delayedClose(multiplier = 1) {
            clearCloseTimer();
            closeTimer = setTimeout(function() {
                menu.style.visibility = 'hidden';
            }, delayedCloseTime * multiplier);
        }
        /**
         * Function to clear the close timer.
         */
        function clearCloseTimer() {
            if (closeTimer) {
                clearTimeout(closeTimer);
                closeTimer = null;
            }
        }
        /**
         * Returns true if the context menu is open, false otherwise
         * @returns {boolean} True if the context menu is open, false otherwise
         */
        self.isCodeActionMenuOpen = function() {
            return menu.style.visibility == "visible";
        };
        self.openCodeActionMenu = function() {
            delayedClose(10);
            menu.style.visibility = "visible";
        };
        self.closeCodeActionMenu = function() {
            clearCloseTimer();
            menu.style.visibility = "hidden";
        };
        menu.classList.add('ace_tooltip');
        menu.addEventListener('click', function(e) {
            let target = e.target;
            while (target && target !== menu) {
                if (target.dataset.actionNumber) {
                    break;
                }
                target = target.parentElement;
            }
            if (!target || target === menu) {
                return;
            }
            let actionNumber = target.dataset.actionNumber;
            if (actionNumber >= 0 && actionNumber < lastCodeActionSuggestions.length) {
                let codeAction = lastCodeActionSuggestions[actionNumber];
                self.executeCodeAction(codeAction);
                self.closeCodeActionMenu();
            }
            e.preventDefault();
        });
        menu.addEventListener('mouseleave', function() {
            delayedClose();
        });
        menu.addEventListener('mousemove', function(e) {
            e.stopPropagation();
            e.preventDefault();
        });
        menu.addEventListener('mouseenter', function() {
            clearCloseTimer();
        });
    }

    /**
     * Show the code action suggestions in a custom context menu
     * @param {Object} LS Language Server instance that sent the code action suggestions
     * @param {Object} message received from the Language Server with the code action suggestions
     */
    function showCodeActionSuggestions(LS, message) {
        if (message === null || message.result === null) {
            return;
        }
        const capabilities = LS.getCapabilities();
        let actionsMenu = document.getElementById('vpl_ls_codeactions');
        let ul = actionsMenu.querySelector("ul");
        if (!ul) {
            ul = document.createElement("ul");
            actionsMenu.appendChild(ul);
        }
        ul.innerHTML = "";
        lastCodeActionSuggestions = [];
        for (let i = 0; i < message.result.length; i++) {
            let codeAction = message.result[i];
            // If the code action has a command that is not supported by the server capabilities, ignore it.
            if (codeAction.command) {
                let command = codeAction.command?.command;
                if (!capabilities.hasExecuteCommand(command)) {
                    continue;
                }
            }
            lastCodeActionSuggestions.push(codeAction);
            let liOption = document.createElement("li");
            let liName = document.createElement("span");
            liName.classList.add("vpl_ls_cm_label");
            let liExtra = document.createElement("span");
            liExtra.classList.add("vpl_ls_cm_shortcut");
            liName.textContent = codeAction.title.length > 40 ? codeAction.title.substring(0, 40) + "..." : codeAction.title;
            liOption.appendChild(liName);
            var extraInfo = codeAction.kind ? codeAction.kind : "";
            let pos = extraInfo.lastIndexOf(".");
            if (pos != -1) {
                extraInfo = extraInfo.substring(pos + 1);
            }
            if (extraInfo.length > 15) {
                extraInfo = extraInfo.substring(0, 15) + "...";
            }
            liExtra.textContent = extraInfo;
            liOption.appendChild(liExtra);
            liOption.classList.toggle("vpl_ls_menuoption_preferred", codeAction.isPreferred ?? false);
            liOption.classList.toggle("vpl_ls_menuoption_disabled", codeAction.disabled ?? false);
            liOption.dataset.actionNumber = lastCodeActionSuggestions.length - 1;
            ul.appendChild(liOption);
        }
        if (lastCodeActionSuggestions.length > 0) {
            let file = fileManager.currentFile();
            if (file == false) {
                return;
            }
            let fontSize = fileManager.getFontSize();
            actionsMenu.style.fontSize = fontSize + "px";
            actionsMenu.classList.toggle('ace_dark', file.isDarkTheme());
            self.openCodeActionMenu();
            let mousePosition = self.getLastMousePosition();
            let x = mousePosition.x + fontSize;
            let y = mousePosition.y - fontSize - actionsMenu.offsetHeight / 2;
            if (window.innerWidth - actionsMenu.offsetWidth < x) {
                x = mousePosition.x - fontSize - actionsMenu.offsetWidth;
            }
            actionsMenu.style.left = x + "px";
            actionsMenu.style.top = y + "px";
        } else {
            self.closeCodeActionMenu();
        }
    }
    /**
     * Apply the selected codeAction
     * @param {Object} codeAction selected code action
     */
    this.executeCodeAction = async function(codeAction) {
        let LS = self.getLS(fileManager.currentFile());
        if (LS === null) {
            return;
        }
        if (codeAction.edit) {
            LS.applyWorkspaceEdit(codeAction.edit);
        }
        var request = null;
        if (typeof codeAction.command === "string" && codeAction.arguments) {
            request = codeAction;
        } else if (typeof codeAction.command === "object") {
            request = codeAction.command;
        }
        if (request !== null) {
            let response = await LS.sendRequest("workspace/executeCommand", {
                "command": request.command,
                "arguments": request.arguments ? request.arguments : []
            });
            let edit = response?.result?.edit;
            if (edit) {
                LS.applyWorkspaceEdit(edit);
            }
        }
    };
    /**
     * Returns true if the hover tooltip is open, false otherwise
     * @returns {boolean} True if the hover tooltip is open, false otherwise
     */
    this.isHoverTooltipOpen = function() {
        let file = fileManager.currentFile();
        return file?.getHoverTooltip()?.isOpen;
    };

    /**
     * Close all the LS related overlays (diagnostic tooltips, code action menu and context menu)
     */
    this.closeAllOverlays = function() {
        let file = fileManager.currentFile();
        if (file && file.isOpen() && file.isCode()) {
            file.getTooltip()?.hide();
            file.getHoverTooltip()?.hide();
            file.getSignatureTooltip()?.hide();
        }
        self.closeCodeActionMenu();
        self.closeContextMenu();
    };

    (function() {
        var lastMousePosition = {x: 0, y: 0};
        self.getLastMousePosition = function() {
            return lastMousePosition;
        };
        /**
         * Handle the mouse move event to show or hide the diagnostic information tooltip.
         * @param {MouseEvent} event Mouse event
         */
        function handleTooltipTrigger(event) {
            lastMousePosition.x = event.clientX;
            lastMousePosition.y = event.clientY;
            let file = fileManager.currentFile();
            if (file == false || !file.isCode() || !file.isOpen()) {
                self.closeAllOverlays();
                return;
            }
            let LS = self.getLS(file);
            if (LS === null || self.isCodeActionMenuOpen() || self.isContextMenuOpen()) {
                file.getTooltip()?.hide();
                return;
            }
            let editor = file.getEditor();
            let cursor = LS.mouseToEditorPosition(editor, event);
            let markersFound = LS.getAllMarkers().filter(marker =>
                marker.fileName == file.getFileName() && marker.range.contains(cursor.row, cursor.column)
            );
            if (markersFound.length > 0) {
                let html = "";
                for (let marker of markersFound) {
                    let icon = "<span class='ace_" + marker.type + " ace_icon' aria-label='"
                               + marker.type + "' role='img'> </span>";
                    html = "<div>" + icon + "<span>" + marker.text + "</span></div>";
                }
                let tooltip = file.getTooltip();
                let tooltipElement = tooltip.getElement();
                let fontSize = fileManager.getFontSize();

                tooltip.setTheme(editor.getTheme());
                tooltipElement.style.fontSize = fontSize + "px";
                tooltipElement.classList.toggle('ace_dark', file.isDarkTheme());
                tooltip.setHtml(html);
                tooltip.show();
                var tooltipRect = tooltipElement.getBoundingClientRect();
                var x;
                var y;
                if (self.isHoverTooltipOpen()) {
                    var hoverTooltip = file.getHoverTooltip();
                    var hoverRect = hoverTooltip.getElement().getBoundingClientRect();
                    x = hoverRect.left;
                    y = hoverRect.top - tooltipRect.height;
                } else {
                    x = event.clientX + fontSize;
                    y = event.clientY - fontSize - tooltipRect.height;
                }
                if (y < 0) {
                    y = 0;
                }
                tooltip.setPosition(x, y);
            } else {
                file.getTooltip().hide();
            }
        }
        // IDE DOM element
        const IDE = document.getElementById('vplide');
        /**
         * Show and hide markers of diagnostic information in a tooltip
         * when the mouse is moved over the editor
         */
        IDE.addEventListener('mousemove', handleTooltipTrigger);
    })();
    /**
     * Create a custom context menu with the available Language Server actions
     * Show or hide options according to the capabilities of the Language Server and the context of the click
     */
    function initializeContextMenu() {
        const menu = document.getElementById('vpl_ls_contextmenu');
        menu.classList.add('ace_tooltip');
        var contextMenuActions = {
            "definitionRequest": {win: 'F12', mac: 'F12'},
            "implementationRequest": {win: 'Ctrl-F12', mac: 'Command-F12'},
            "referencesRequest": {win: 'Shift-F12', mac: 'Shift-F12'},
            "renameSymbolRequest": {win: 'Ctrl-F2', mac: 'Command-F2'},
            "formattingRequest": {win: 'Shift-Alt-F', mac: 'Shift-Option-F'},
            "rangeFormattingRequest": {win: 'Shift-Ctrl-F', mac: 'Command-Option-F'},
            "codeActionRequest": {win: 'Ctrl-.', mac: 'Command-.'},
        };
        /**
         * Returns true if the Language Server has any of the capabilities that can be
         * triggered from the context menu.
         * @param {Object} LS Language Server instance
         * @returns {boolean} True if context menu should be shown, false otherwise
         */
        function hasContextMenu(LS) {
            return LS.getCapabilities().hasDefinitionProvider()
                || LS.getCapabilities().hasImplementationProvider()
                || LS.getCapabilities().hasReferencesProvider()
                || LS.getCapabilities().hasRenameProvider()
                || LS.getCapabilities().hasDocumentFormattingProvider()
                || LS.getCapabilities().hasDocumentRangeFormattingProvider()
                || LS.getCapabilities().hasCodeActionProvider();
        }
        var closeTimer = null;
        /**
         * Function to delay the closing of the menu.
         * @param {number} multiplier delayedCloseTime multiplier for the delay time in milliseconds
         */
        function delayedClose(multiplier = 1) {
            clearCloseTimer();
            closeTimer = setTimeout(function() {
                menu.style.visibility = 'hidden';
            }, delayedCloseTime * multiplier);
        }
        /**
         * Function to clear the close timer.
         */
        function clearCloseTimer() {
            if (closeTimer) {
                clearTimeout(closeTimer);
                closeTimer = null;
            }
        }
        /**
         * Returns true if the context menu is open, false otherwise
         * @returns {boolean} True if the context menu is open, false otherwise
         */
        self.isContextMenuOpen = function() {
            return menu.style.visibility == "visible";
        };
        self.openContextMenu = function() {
            delayedClose(10);
            menu.style.visibility = "visible";
        };
        self.closeContextMenu = function() {
            menu.style.visibility = "hidden";
        };

        menu.addEventListener('click', function(e) {
            let target = e.target;
            while (target && target !== menu) {
                if (target.dataset.lsaction) {
                    break;
                }
                target = target.parentElement;
            }
            if (!target || target === menu) {
                return;
            }
            let action = target.dataset.lsaction;
            if (contextMenuActions[action]) {
                self.closeContextMenu();
                self[action](e);
            }
            e.preventDefault();
        });
        menu.addEventListener('mouseleave', function() {
            delayedClose();
        });
        menu.addEventListener('mouseenter', function() {
            clearCloseTimer();
        });
        menu.addEventListener('mousemove', function(e) {
            e.stopPropagation();
            e.preventDefault();
        });
        let optionDefinition = document.getElementById('vpl_ls_cm_definition');
        let optionImplementation = document.getElementById('vpl_ls_cm_implementation');
        let optionReferences = document.getElementById('vpl_ls_cm_references');
        let optionRename = document.getElementById('vpl_ls_cm_rename');
        let optionRangeFormatting = document.getElementById('vpl_ls_cm_formatrange');
        let optionFormatting = document.getElementById('vpl_ls_cm_format');
        let optionCodeAction = document.getElementById('vpl_ls_cm_codeaction');
        document.addEventListener('contextmenu', function(e) {
            if (e.target.className == 'ace_text-input') {
                let file = fileManager.currentFile();
                var LS = self.getLS(file);
                if (LS === null || !hasContextMenu(LS)) {
                    return;
                }
                e.preventDefault();
                let capabilities = LS.getCapabilities();
                let editor = file.getEditor();
                let session = editor.getSession();
                // Set dark mode for the context menu if the editor is in dark mode.
                const isDark = file.isDarkTheme();
                document.getElementById('vpl_ls_contextmenu').classList.toggle('ace_dark', isDark);
                // Hide unavailable options according to the capabilities of the Language Server
                optionDefinition.style.display = capabilities.hasDefinitionProvider() ? "" : "none";
                optionImplementation.style.display = capabilities.hasImplementationProvider() ? "" : "none";
                optionReferences.style.display = capabilities.hasReferencesProvider() ? "" : "none";
                optionRename.style.display = capabilities.hasRenameProvider() ? "" : "none";
                optionCodeAction.style.display = capabilities.hasCodeActionProvider() ? "" : "none";
                optionFormatting.style.display = capabilities.hasDocumentFormattingProvider() ? "" : "none";
                optionRangeFormatting.style.display = capabilities.hasDocumentRangeFormattingProvider() ? "" : "none";

                let cursor = editor.getSelectionRange();
                var available = cursor.start.row != cursor.end.row || cursor.start.column != cursor.end.column;
                optionRangeFormatting.classList.toggle('vpl_ls_menuoption_disabled', !available);
                const pos = editor.getCursorPosition();
                const token = session.getTokenAt(pos.row, pos.column);
                var notAvalable = !token || !token.type || /comment|string|number|keyword/.test(token.type);
                optionDefinition.classList.toggle('vpl_ls_menuoption_disabled', notAvalable);
                optionImplementation.classList.toggle('vpl_ls_menuoption_disabled', notAvalable);
                optionReferences.classList.toggle('vpl_ls_menuoption_disabled', notAvalable);
                optionRename.classList.toggle('vpl_ls_menuoption_disabled', notAvalable);
                self.closeAllOverlays();
                self.openContextMenu();
                if (window.innerWidth - 200 > e.clientX) {
                    menu.style.left = e.clientX + "px";
                } else {
                    menu.style.left = (e.clientX - 200) + "px";
                }
                const menuHeight = menu.offsetHeight;
                let top = e.clientY - Math.round(menuHeight / 2);
                if (top < 0) {
                    top = 0;
                } else if (top + menuHeight > window.innerHeight) {
                    top = window.innerHeight - menuHeight;
                }
                menu.style.top = top + "px";
            } else {
                menu.style.visibility = 'hidden';
            }
        });
        // Register the context menu actions in the IDE menu buttons to allow keyboard shortcuts.
        const menuButtons = fileManager.getIDE().getMenuButtons();
        for (let actionName of Object.keys(contextMenuActions)) {
            menuButtons.add({
                "name": actionName,
                "originalAction": self[actionName],
                "bindKey": contextMenuActions[actionName]
            });
        }
    }
    /**
     * Create rename dialog
     */
    function initializeRenameDialog() {
        renameSymbolDialog = document.getElementById('vpl_ls_rename');
        let input = renameSymbolDialog.querySelector("input");
        let okBtn = document.getElementById('vpl_ls_rename_ok_btn');
        let cancelBtn = document.getElementById('vpl_ls_rename_cancel_btn');
        renameSymbolDialog.classList.add('ace_tooltip');
        renameSymbolDialog.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
        });
        renameSymbolDialog.addEventListener('mousemove', function(e) {
            e.stopPropagation();
            e.preventDefault();
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === "Enter") {
                okBtn.click();
                e.preventDefault();
            } else if (e.key === "Escape") {
                cancelBtn.click();
                e.preventDefault();
            }
        });
        okBtn.addEventListener('click', function() {
            let name = renameSymbolDialog.querySelector("input").value;
            self.renameSymbolHandler(name);
            renameSymbolDialog.querySelector("input").value = "";
            renameSymbolDialog.style.visibility = 'hidden';
        });
        cancelBtn.addEventListener('click', function() {
            renameSymbolDialog.style.visibility = 'hidden';
        });
        self.renameDialog = renameSymbolDialog;
    }
    /**
     * Register a click event handler for the Language Server status element in the IDE status bar.
     * When clicked, it resets the inactivity timeout of the current Language Server.
     */
    function registerLSStatusClickHandler() {
        let statusElement = document.querySelector('#vpl_ide_statusbar .vpl_ide_statusbar_lsp');
        statusElement?.addEventListener('click', function() {
            let file = fileManager.currentFile();
            let LS = languageServers[file?.getLSLang()];
            LS?.resetInactivityTimeout();
        });
    }
    var dialogInitialized = false;
    /**
     * Create custom context menu
     * create a native dialog element for the rename request
     */
    this.init = function() {
        if (dialogInitialized) {
            return;
        }
        dialogInitialized = true;
        initializeRenameDialog();
        initializeContextMenu();
        initializeCodeActionMenu();
        registerLSStatusClickHandler();
    };
    self.init();
};
