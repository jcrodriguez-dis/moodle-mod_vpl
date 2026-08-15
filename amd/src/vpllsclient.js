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
import {VPLUI} from 'mod_vpl/vplui';
import {VPLMD} from 'mod_vpl/vplmd';

/**
 * LSTaskQueue class that implements a queue for tasks to be executed sequentially.
 */
class LSTaskQueue {
    constructor(LSClient) {
        this.LSClient = LSClient;
        this.taskQueue = [];
        this.running = false;
    }

    add(task) {
        return new Promise((resolve, reject) => {
            this.taskQueue.push({
                task,
                resolve,
                reject
            });

            this.process();
        });
    }

    async process() {
        if (this.running) {
            return;
        }
        this.running = true;
        while (this.taskQueue.length > 0) {
            const {task, resolve, reject} = this.taskQueue.shift();
            try {
                const result = await task();
                resolve(result);
            } catch (error) {
                reject(error);
            }
        }

        this.running = false;
    }
}

class LSServerCapabilities {
    constructor(capabilities) {
        const caps = capabilities || {};
        // Normalize number and object values for textDocumentSync
        if (typeof caps.textDocumentSync === 'number') {
            this.textDocumentSync = {
                change: caps.textDocumentSync,
                openClose: caps.textDocumentSync !== 0,
                save: {includeText: false}
            };
        } else if (typeof caps.textDocumentSync === 'object' && caps.textDocumentSync !== null) {
            const s = caps.textDocumentSync;
            this.textDocumentSync = {
                change: typeof s.change === 'number' ? s.change : 1,
                openClose: !!s.openClose,
                save: s.save ? {includeText: !!(s.save.includeText)} : null
            };
        } else {
            this.textDocumentSync = {
                change: 1,
                openClose: true,
                save: {includeText: false}
            };
        }
        this.definitionProvider = !!caps.definitionProvider;
        this.referencesProvider = !!caps.referencesProvider;
        this.implementationProvider = !!caps.implementationProvider;
        this.hoverProvider = !!caps.hoverProvider;
        this.codeActionProvider = !!caps.codeActionProvider;
        this.documentFormattingProvider = !!caps.documentFormattingProvider;
        this.documentRangeFormattingProvider = !!caps.documentRangeFormattingProvider;
        // ExecuteCommandProvider.
        const ecp = caps.executeCommandProvider;
        this.executeCommandProvider = {
            commands: (ecp && Array.isArray(ecp.commands)) ? ecp.commands.slice() : []
        };

        // Completion provider.
        if (caps.completionProvider && typeof caps.completionProvider === 'object') {
            this.completionProvider = {
                available: true,
                triggerCharacters: Array.isArray(caps.completionProvider.triggerCharacters)
                    ? caps.completionProvider.triggerCharacters.slice()
                    : []
            };
        } else {
            this.completionProvider = {available: false, triggerCharacters: []};
        }

        // Signature help provider.
        if (caps.signatureHelpProvider && typeof caps.signatureHelpProvider === 'object') {
            this.signatureHelpProvider = {
                available: true,
                triggerCharacters: Array.isArray(caps.signatureHelpProvider.triggerCharacters)
                    ? caps.signatureHelpProvider.triggerCharacters.slice()
                    : [],
                retriggerCharacters: Array.isArray(caps.signatureHelpProvider.retriggerCharacters)
                    ? caps.signatureHelpProvider.retriggerCharacters.slice()
                    : []
            };
        } else {
            this.signatureHelpProvider = {available: false, triggerCharacters: [], retriggerCharacters: []};
        }

        // Rename provider.
        if (caps.renameProvider) {
            if (typeof caps.renameProvider === 'object') {
                this.renameProvider = {
                    available: true,
                    prepareProvider: !!caps.renameProvider.prepareProvider
                };
            } else {
                this.renameProvider = {
                    available: true,
                    prepareProvider: false
                };
            }
        } else {
            this.renameProvider = {
                available: false,
                prepareProvider: false
            };
        }

        // Workspace file operations.
        const fo = (caps.workspace && caps.workspace.fileOperations) ? caps.workspace.fileOperations : {};
        this.fileOperations = {
            didCreate:  !!(fo.didCreate),
            willCreate: !!(fo.willCreate),
            didRename:  !!(fo.didRename),
            willRename: !!(fo.willRename),
            didDelete:  !!(fo.didDelete),
            willDelete: !!(fo.willDelete)
        };
    }

    // Helper methods
    hasDefinitionProvider() {
        return this.definitionProvider;
    }
    hasReferencesProvider() {
        return this.referencesProvider;
    }
    hasImplementationProvider() {
        return this.implementationProvider;
    }
    hasHoverProvider() {
        return this.hoverProvider;
    }
    hasCodeActionProvider() {
        return this.codeActionProvider;
    }
    hasCompletionProvider() {
        return this.completionProvider.available;
    }
    getCompletionTriggerCharacters() {
        return this.completionProvider.triggerCharacters.slice();
    }
    hasSignatureHelpProvider() {
        return this.signatureHelpProvider.available;
    }
    getSignatureHelpTriggerCharacters() {
        return this.signatureHelpProvider.triggerCharacters.slice();
    }
    getSignatureHelpRetriggerCharacters() {
        return this.signatureHelpProvider.retriggerCharacters.slice();
    }
    hasRenameProvider() {
        return this.renameProvider.available;
    }
    hasRenamePrepareProvider() {
        return this.renameProvider.prepareProvider;
    }
    hasDocumentFormattingProvider() {
        return this.documentFormattingProvider;
    }
    hasDocumentRangeFormattingProvider() {
        return this.documentRangeFormattingProvider;
    }
    hasExecuteCommand(command) {
        return Array.isArray(this.executeCommandProvider.commands)
            && this.executeCommandProvider.commands.includes(command);
    }
    getTextDocumentSyncKind() {
        return this.textDocumentSync.change;
    }
    supportsOpenClose() {
        return this.textDocumentSync.openClose;
    }
    supportsDidCreateFiles() {
        return this.fileOperations.didCreate;
    }
    supportsWillCreateFiles() {
        return this.fileOperations.willCreate;
    }
    supportsDidRenameFiles() {
        return this.fileOperations.didRename;
    }
    supportsWillRenameFiles() {
        return this.fileOperations.willRename;
    }
    supportsDidDeleteFiles() {
        return this.fileOperations.didDelete;
    }
    supportsWillDeleteFiles() {
        return this.fileOperations.willDelete;
    }
    supportsSave() {
        return this.textDocumentSync.save !== null;
    }
    saveIncludesText() {
        return this.textDocumentSync.save !== null && this.textDocumentSync.save.includeText;
    }
}
/**
 * VPLLSClient class that implements the Language Server Protocol client
 * @param {String} APIURL URL to the VPL plugin API
 * @param {FileManager} fileManager FileManager instance to manage the files in the client
 * @param {String} language Programming language name of the Language Server
 * @param {String} locale Locale language of the user
 */
export const VPLLSClient = function(APIURL, fileManager, language, locale) {
    // Reference to the current instance
    const self = this;
    // One second in milliseconds
    const seconds = 1000;
    // One minute in milliseconds
    const minutes = 60 * seconds;
    // Time in ms to wait after a file change before sending the notification to the Language Server
    const timeoutFileChange = 1 * seconds;
    // Time in ms to wait after connection to start sending notifications to the Language Server
    const waitTimeLSStart = 500;
    // Time in ms to wait for recheck if LS connected
    const waitTimeConecting = 100;
    // Time in ms to wait before retrying to connect with the Language Server after a connection loss
    const waitTimeForRetryingLSConection = 5 * minutes;
    // Maximum number of reconnection attempts before giving up
    const maxConnectionsAttempts = 5;
    // Time in ms to wait for a response before rejecting a pending request
    const requestTimeout = 30 * seconds;
    // Inactivity timeout. LS shuts down after this period of inactivity
    const inactivityTimeout = 10 * minutes;
    // Timer ID for the inactivity timeout
    var inactivityTimeoutId = null;
    // WebSocket connection with the Language Server
    var ws = null;
    // Home path of the Language Server
    var urihomepath = "";
    // Project folder in the Language Server
    const projectFolder = "vplproject";
    // Process ID of VPL task running the Language Server
    var VPLTaskId = null;
    // Number of reconnection attempts
    var reConnection = 0;
    // Message ID for requests
    var messageId = 0;
    // Version of the Language Server Protocol
    var version = 0;
    // Requests pending a message from the Language Server
    var requests = {};
    // All markers received from the Language Server
    var allMarkers = [];
    // Server capabilities received from the Language Server (normalized wrapper)
    var serverCapabilities = new LSServerCapabilities({});
    // Dynamic file watcher registrations from client/registerCapability
    var watchedFileRegistrations = {};
    // Content of the files when they are opened
    var openFilesFirstContent = {};
    // Task queue to ensure that requests are sent sequentially
    var taskQueue = new LSTaskQueue();
    // Controls whether event handlers should be active
    self.eventHandlersActive = false;
    /**
     * Log a message with the language of the Language Server as prefix
     * @param {String} message to log
     * @param {boolean} forced to log the message even if the log level is not debug
     */
    function log(message, forced = false) {
        VPLUtil.log("[" + language + " LS] " + message, forced);
    }
    this.log = log;
    // TODO
    // Parcial triggerCharacters
    // typeDefinitionProvider
    // documentSymbolProvider
    // documentOnTypeFormattingProvider
    // typeHierarchyProvider
    // callHierarchyProvider
    // semanticTokensProvider
    // inlayHintProvider
    /**
     * return Server capabilities
     */
    this.getCapabilities = function() {
        return serverCapabilities;
    };
    /**
     * Reset the inactivity timeout, so the LS will not shut down due to inactivity.
     */
    function resetInactivityTimeout() {
        if (inactivityTimeoutId === "triggered") {
            inactivityTimeoutId = null;
        } else if (inactivityTimeoutId !== null) {
            clearTimeout(inactivityTimeoutId);
            inactivityTimeoutId = null;
        }
        // If the LS is down (inactivity shutdown or exhausted reconnection attempts) and no
        // reconnection is scheduled or in progress, restart it now that there is activity again.
        if (self.isStopped() && reConnectionTimerId === null && !self.isConnecting()) {
            reConnection = 0;
            self.startConnection();
        }
        inactivityTimeoutId = setTimeout(() => {
            log("Inactivity timeout reached. Shutting down LS.");
            self.stopConnection();
            inactivityTimeoutId = "triggered";
        }, inactivityTimeout);
    }
    this.resetInactivityTimeout = resetInactivityTimeout;
    /**
     * Given a URI, it returns the corresponding file name
     * if the URI starts with the home path of the Language Server
     * otherwise, it returns null
     * @param {String} uri to the file in the Language Server
     * @returns {String|null} File name corresponding to the URI or null if not found
     */
    this.uriToFileName = function(uri) {
        if (uri.startsWith(urihomepath)) {
            let fileName = uri.substring(urihomepath.length + 1);
            return fileName.split("/").map(decodeURIComponent).join("/");
        }
        return null;
    };

    /**
     * Given a file name, it returns the corresponding URI
     * by concatenating the home path of the Language Server with the file name
     * @param {String} fileName to the file in the Language Server
     * @returns {String} URI corresponding to the file name
     */
    this.fileNameToUri = function(fileName) {
        return urihomepath + "/" + fileName.split("/").map(encodeURIComponent).join("/");
    };

    /**
     * Given a file name, it returns the corresponding path in the project folder of the Language Server
     * @param {String} fileName to the file VPL IDE
     * @returns {String} fileName in the project folder
     */
    this.fileNameToProjectPath = function(fileName) {
        return projectFolder + "/" + fileName;
    };

    this.getNewVersion = function() {
        return ++version;
    };

    this.getAllMarkers = function() {
        return allMarkers;
    };
    this.addTask = function(task) {
        return taskQueue.add(task);
    };
    /**
     * It returns the request corresponding to the given ID
     * and removes it from the pending requests.
     * @param {String} id of the request
     * @returns {Object} request corresponding to the ID or a not found message
     */
    function getRequestByIdAndRemove(id) {
        let request = {
            requestMethod:"Not found ID '" + id + "' in requests",
            fileName: null,
            reject: VPLUtil.doNothing,
            resolve: VPLUtil.doNothing,
        };
        if (requests[id] != undefined) {
            request = requests[id];
            delete requests[id];
            window.clearTimeout(request.timeoutId);
            return request;
        }
        return request;
    }

    this.applyFileChanges = function(fileURI, changes) {
        let fileName = self.uriToFileName(fileURI);
        let file = fileManager.getFileByName(fileName);
        if (file == false) {
            return;
        }
        if (!file.isOpen()) {
            fileManager.openFile(file);
        }
        // TODO open with await to ensure the file is open before applying the changes
        if (file.isOpen() && file.isCode()) {
            let editor = file.getEditor();
            let document = editor.getSession().getDocument();
            // Apply edits from the end of the document to the start so that applying one edit
            // does not shift the positions of the edits that have not been applied yet.
            // LSP guarantees the edits do not overlap, so sorting by start position is safe.
            let ordered = changes.slice().sort(function(a, b) {
                if (a.range.start.line !== b.range.start.line) {
                    return b.range.start.line - a.range.start.line;
                }
                return b.range.start.character - a.range.start.character;
            });
            for (let edit of ordered) {
                let range = editor.getSelectionRange();
                range.setStart(edit.range.start.line, edit.range.start.character);
                range.setEnd(edit.range.end.line, edit.range.end.character);
                document.replace(range, edit.newText);
            }
        }
    };
    this.applyWorkspaceEdit = function(workspaceEdit) {
        if (workspaceEdit.changes) {
            let textChanges = workspaceEdit.changes;
            for (let fileURI of Object.keys(textChanges)) {
                self.applyFileChanges(fileURI, textChanges[fileURI]);
            }
        }
        if (workspaceEdit.documentChanges) {
            let documentChanges = workspaceEdit.documentChanges;
            for (let documentChange of documentChanges) {
                if (documentChange.textDocument && documentChange.edits) {
                    let fileURI = documentChange.textDocument.uri;
                    self.applyFileChanges(fileURI, documentChange.edits);
                } else if (documentChange.kind) {
                    self.applyResourceOperation(documentChange);
                }
            }
        }
    };

    /**
     * Computes the files affected by a workspace edit without applying it.
     * A rename is treated as a delete of the old file plus an add of the new file.
     * @param {Object} workspaceEdit The workspace edit {changes?, documentChanges?}
     * @returns {Object} {changed: Array.<String>, deleted: Array.<String>} file names.
     *                   'changed' holds modified or added files (to write to the filesystem),
     *                   'deleted' holds removed files (rename sources included).
     */
    this.getAffectedFiles = function(workspaceEdit) {
        let changed = new Set();
        let deleted = new Set();
        // Adds a file name to 'changed' unless it is already marked as deleted.
        let addChanged = function(fileName) {
            if (fileName !== null) {
                deleted.delete(fileName);
                changed.add(fileName);
            }
        };
        // Marks a file name as deleted and removes it from 'changed' if present.
        let addDeleted = function(fileName) {
            if (fileName !== null) {
                changed.delete(fileName);
                deleted.add(fileName);
            }
        };
        if (workspaceEdit?.changes) {
            for (let fileURI of Object.keys(workspaceEdit.changes)) {
                addChanged(self.uriToFileName(fileURI));
            }
        }
        if (workspaceEdit?.documentChanges) {
            for (let documentChange of workspaceEdit.documentChanges) {
                if (documentChange.textDocument && documentChange.edits) {
                    addChanged(self.uriToFileName(documentChange.textDocument.uri));
                } else if (documentChange.kind === "create") {
                    addChanged(self.uriToFileName(documentChange.uri));
                } else if (documentChange.kind === "delete") {
                    addDeleted(self.uriToFileName(documentChange.uri));
                } else if (documentChange.kind === "rename") {
                    addDeleted(self.uriToFileName(documentChange.oldUri));
                    addChanged(self.uriToFileName(documentChange.newUri));
                }
            }
        }
        return {
            changed: Array.from(changed),
            deleted: Array.from(deleted)
        };
    };

    /**
     * Applies a workspace resource operation (file creation, deletion or renaming)
     * received from the Language Server to the file manager.
     * @param {Object} operation The resource operation {kind, uri|oldUri|newUri, options}
     */
    this.applyResourceOperation = function(operation) {
        // Logs a resource-operation error reported by the file manager.
        let showError = function(message) {
            log("LS resource operation '" + operation.kind + "' failed: " + message);
        };
        let options = operation.options || {};
        switch (operation.kind) {
            case "create": {
                let fileName = self.uriToFileName(operation.uri);
                if (fileName === null) {
                    return;
                }
                let exists = fileManager.fileNameExists(fileName) !== -1;
                // Honor the ignoreIfExists / overwrite options from the LSP spec.
                if (exists && options.ignoreIfExists && !options.overwrite) {
                    return;
                }
                let replace = exists && options.overwrite === true;
                fileManager.addFile(
                    {name: fileName, contents: "", encoding: 0},
                    replace,
                    VPLUtil.doNothing,
                    showError
                );
                break;
            }
            case "delete": {
                let fileName = self.uriToFileName(operation.uri);
                let file = fileManager.getFileByName(fileName);
                if (!file) {
                    if (!options.ignoreIfNotExists) {
                        showError("file does not exist (" + fileName + ")");
                    }
                    return;
                }
                self.removeMarkersOfFile(file);
                fileManager.deleteFile(fileName, showError);
                break;
            }
            case "rename": {
                let oldName = self.uriToFileName(operation.oldUri);
                let newName = self.uriToFileName(operation.newUri);
                if (oldName === null || newName === null) {
                    return;
                }
                let targetExists = fileManager.fileNameExists(newName) !== -1;
                if (targetExists && options.ignoreIfExists && !options.overwrite) {
                    return;
                }
                if (targetExists && options.overwrite === true) {
                    self.removeMarkersOfFile(fileManager.getFileByName(newName));
                    fileManager.deleteFile(newName, showError);
                }
                self.removeMarkersOfFile(fileManager.getFileByName(oldName));
                fileManager.renameFile(oldName, newName, showError);
                break;
            }
            default:
                log("LS Unsupported document change kind: " + operation.kind);
                break;
        }
    };

    /**
     * Removes all markers associated with a given file from the editor
     * and from the internal list of markers.
     * @param {Object} file to remove markers from
     * @param {string} [oldFileName=null] Optional old file name used in marks
     */
    this.removeMarkersOfFile = function(file, oldFileName = null) {
        if (!file) {
            return;
        }
        let fileName = oldFileName || file.getFileName();
        let session = null;
        if (file.isOpen() && file.isCode()) {
            session = file.getEditor().getSession();
        }
        for (let i = 0; i < allMarkers.length; i++) {
            if (allMarkers[i].fileName == fileName) {
                if (session) {
                    session.removeMarker(allMarkers[i].id);
                }
                allMarkers.splice(i, 1);
                i--;
            }
        }
    };

    this.removeAllMarkers = function() {
        var files = fileManager.getFiles();
        for (let file of files) {
            if (file.getLSLang() == language) {
                self.removeMarkersOfFile(file);
            }
        }
        allMarkers = [];
    };
    /**
     * Publishes diagnostics received from the Language Server
     * @param {Object} message containing the diagnostics
     */
    function publishDiagnostics(message) {
        let fileName = self.uriToFileName(message?.params?.uri);
        if (fileName === null) {
            return;
        }
        let pos = fileManager.fileNameExists(fileName);
        if (pos > -1) {
            let allFiles = fileManager.getFiles();
            let file = allFiles[pos];
            if (!file.isOpen() || !file.isCode()) {
                return;
            }
            self.removeMarkersOfFile(file);
            let editorSession = file.getEditor().getSession();
            let annotation = [];
            for (let i = 0; i < message.params.diagnostics.length; i++) {
                let diagnostic = message.params.diagnostics[i];
                let rangeStart = diagnostic.range.start;
                let rangeEnd = diagnostic.range.end;
                let type;
                let typeMarker;
                switch (diagnostic.severity) {
                    case 1:
                        type = "error";
                        break;
                    case 2:
                        type = "warning";
                        break;
                    default:
                        type = "info";
                        break;
                }
                typeMarker = "vpl_ace-" + type + "-marker";
                annotation.push({
                    "row": rangeStart.line,
                    "column": rangeStart.character,
                    "text": diagnostic.message,
                    "type": type
                });
                let range = file.getRange(
                    rangeStart.line,
                    rangeStart.character,
                    rangeEnd.line,
                    rangeEnd.character);
                let markerId = editorSession.addMarker(range, typeMarker, "text", true);
                allMarkers.push({
                    file: pos,
                    id: markerId,
                    fileName: file.getFileName(),
                    range: range,
                    type: type,
                    text: diagnostic.message
                });
            }
            editorSession.setAnnotations(annotation);
        }
    }
    /**
     * Send the completion request to the Language Server
     * to compute completion items at a given cursor position
     * @param {VPLFile} file The file to send the completion request for
     * @param {Object} context The completion context to send with the request
     */
    this.completionRequest = function(file, context) {
        if (!serverCapabilities.hasCompletionProvider()) {
            return Promise.resolve({result: null});
        }
        if (file == false || file.getLSLang() != language || file.isOpen() == false) {
            return Promise.resolve({result: null});
        }
        let cursor = file.getEditor().getCursorPosition();
        let fileName = file.getFileName();
        let fileURI = self.fileNameToUri(fileName);
        let param = {
            "textDocument": {
                "uri": fileURI
            },
            "position": {
                "line": cursor.row,
                "character": cursor.column
            },
            "context": context
        };
        self.notifyPendingFileChanges(file);
        return self.addTask(
            async () => {
                return self.sendRequest("textDocument/completion", param, fileName);
            }
        );
    };
    /**
     * Send the signatureHelp request to the Language Server to request signature
     * information at a given cursor position (e.g. the parameters of a function call).
     * @param {VPLFile} file The file to send the signatureHelp request for
     * @param {Object} context The signature help context to send with the request
     * @returns {Promise} A promise that resolves with the signature help received from the Language Server
     */
    this.signatureHelpRequest = function(file, context) {
        if (!serverCapabilities.hasSignatureHelpProvider()) {
            return Promise.resolve({result: null});
        }
        if (file == false || file.getLSLang() != language || file.isOpen() == false) {
            return Promise.resolve({result: null});
        }
        let cursor = file.getEditor().getCursorPosition();
        let fileName = file.getFileName();
        let fileURI = self.fileNameToUri(fileName);
        let param = {
            "textDocument": {
                "uri": fileURI
            },
            "position": {
                "line": cursor.row,
                "character": cursor.column
            },
            "context": context
        };
        self.notifyPendingFileChanges(file);
        return self.addTask(
            async () => {
                return self.sendRequest("textDocument/signatureHelp", param, fileName);
            }
        );
    };
    /**
     * Build the HTML content to show for a SignatureHelp result, highlighting the active parameter.
     * @param {Object} result The SignatureHelp result received from the Language Server
     * @returns {String|null} The HTML content or null if there is nothing to show
     */
    function buildSignatureHelpHTML(result) {
        if (!result || !Array.isArray(result.signatures) || result.signatures.length === 0) {
            return null;
        }
        let sigIndex = result.activeSignature ?? 0;
        sigIndex = sigIndex >= 0 && sigIndex < result.signatures.length ? sigIndex : 0;
        let signature = result.signatures[sigIndex];
        let label = signature.label || "";
        let parameters = Array.isArray(signature.parameters) ? signature.parameters : [];
        let activeParam = signature.activeParameter ?? result.activeParameter ?? 0;
        let parameter = parameters[activeParam];
        let range = parameter?.label;
        if (typeof range === "string") {
            let start = label.indexOf(range);
            range = start === -1 ? null : [start, start + range.length];
        }
        let labelHTML = Array.isArray(range) && range.length === 2
            ? VPLUtil.sanitizeText(label.substring(0, range[0]))
                + "<b class='vpl_ls_active_parameter'>"
                + VPLUtil.sanitizeText(label.substring(range[0], range[1])) + "</b>"
                + VPLUtil.sanitizeText(label.substring(range[1]))
            : VPLUtil.sanitizeText(label);
        let html = "<div class='vpl_ls_signature_label'>" + labelHTML + "</div>";
        let signatureDoc = signature.documentation;
        if (signatureDoc) {
            html += "<div>" + VPLMD.markDownToHTML(signatureDoc.value || signatureDoc) + "</div>";
        }
        if (parameter?.documentation) {
            let paramDoc = parameter.documentation;
            html += "<div>" + VPLMD.markDownToHTML(paramDoc.value || paramDoc) + "</div>";
        }
        if (result.signatures.length > 1) {
            html += "<div class='vpl_ls_signature_count'>(" + (sigIndex + 1) + "/" + result.signatures.length + ")</div>";
        }
        return html;
    }
    /**
     * Request signature help to the Language Server and show the result in a tooltip
     * placed near the cursor, or hide the tooltip if there is no signature to show.
     * @param {VPLFile} file The file to request signature help for
     * @param {Object} context The signature help context to send with the request
     */
    async function triggerSignatureHelp(file, context) {
        if (file == false || file.getLSLang() != language || file.isOpen() == false || !file.isCode()) {
            return;
        }
        let tooltip = file.getSignatureTooltip();
        let message = await self.signatureHelpRequest(file, context);
        let html = buildSignatureHelpHTML(message?.result);
        if (!html) {
            tooltip.hide();
            return;
        }
        let editor = file.getEditor();
        let cursor = editor.getCursorPosition();
        let coords = editor.renderer.textToScreenCoordinates(cursor.row, cursor.column);
        let fontSize = file.getFileManager().getFontSize();
        let element = tooltip.getElement();
        element.className = "vpl_hover_tooltip ace_tooltip" + (file.isDarkTheme() ? " ace_dark" : "");
        element.style.fontSize = fontSize + "px";
        tooltip.setHtml(html);
        tooltip.show();
        let rect = element.getBoundingClientRect();
        let top = coords.pageY - rect.height - fontSize;
        if (top < 0) {
            top = coords.pageY + fontSize;
        }
        tooltip.setPosition(coords.pageX, top);
    }
    /**
     * React to a document change to trigger, update or hide the signature help tooltip
     * according to the trigger and retrigger characters of the Language Server.
     * @param {VPLFile} file The file that has been changed
     * @param {Object} delta Ace change delta {action, start, end, lines}
     */
    function handleSignatureHelpChange(file, delta) {
        if (!serverCapabilities.hasSignatureHelpProvider()) {
            return;
        }
        // The Ace 'change' event fires before the editor has finished updating the cursor
        // position (and before bracket auto-close has inserted its matching character),
        // so inspecting the cursor synchronously would give a stale position. Defer the
        // inspection to a microtask so the document, the cursor and any auto-close
        // insertion have all settled before we decide what to do.
        Promise.resolve().then(function() {
            if (file == false || file.getLSLang() != language || file.isOpen() == false || !file.isCode()) {
                return;
            }
            let tooltip = file.getSignatureTooltip();
            let triggerChars = serverCapabilities.getSignatureHelpTriggerCharacters();
            let retriggerChars = serverCapabilities.getSignatureHelpRetriggerCharacters();
            let isVisible = tooltip && tooltip.isOpen;
            // Decide based on the character right before the cursor instead of the delta text.
            // This naturally handles bracket auto-close: when typing "(" Ace inserts "()" and
            // leaves the cursor between them, so the char before the cursor is "(" (trigger),
            // and the auto-inserted ")" change leaves the cursor before it (char before is "(",
            // not ")"), so it does not hide the tooltip. A ")" typed by the user leaves the
            // cursor after it, so the char before the cursor is ")" and the tooltip is hidden.
            let editor = file.getEditor();
            let cursor = editor.getCursorPosition();
            let charBeforeCursor = editor.getSession().getLine(cursor.row)[cursor.column - 1];
            // Trigger/retrigger only happen when the user inserts a trigger character.
            // Hiding and updating the active parameter must also work while deleting text.
            if (delta.action === "insert" && triggerChars.includes(charBeforeCursor)) {
                triggerSignatureHelp(file, {triggerKind: 2, triggerCharacter: charBeforeCursor, isRetrigger: false});
            } else if (delta.action === "insert" && retriggerChars.includes(charBeforeCursor)) {
                triggerSignatureHelp(file, {triggerKind: 2, triggerCharacter: charBeforeCursor, isRetrigger: true});
            } else if (charBeforeCursor === ")" && isVisible) {
                tooltip.hide();
            } else if (isVisible) {
                // Keep the active parameter in sync while the user types or deletes inside the argument list.
                triggerSignatureHelp(file, {triggerKind: 3, isRetrigger: true});
            }
            return Promise.resolve();
        });
    }
    /**
     * Convert the mouse coordinates to the corresponding text document position in the editor
     * @param {Editor} editor Ace Editor instance
     * @param {MouseEvent} event Mouse event with the coordinates to convert
     * @returns {Object} Object with the row and column of the corresponding text document position
     */
    self.mouseToEditorPosition = function(editor, event) {
        const renderer = editor.renderer;
        const cursor = renderer.screenToTextCoordinates(event.clientX, event.clientY);
        let rect = renderer.scroller.getBoundingClientRect();
        // Mouse position inside the editor
        const x = event.clientX - rect.left + renderer.scrollLeft - renderer.$padding;
        const screenColumn = Math.floor(x / renderer.characterWidth);
        return {
            row: cursor.row,
            column: screenColumn
        };
    };
    /**
     * Send the hover request to the Language Server
     * to request hover information at a given text document position
     * @param {Event} event Mouse event with the coordinates to request hover information for
     * @param {VPLFile} file The file to send the hover request for
     * @returns {Promise} A promise that resolves with the hover information received from the Language Server
     */
    self.hoverRequest = function(event, file) {
        if (!serverCapabilities.hasHoverProvider()) {
            return Promise.resolve({result: null});
        }
        if (file == false || file.getLSLang() != language || file.isOpen() == false || !file.isCode()) {
            return Promise.resolve({result: null});
        }
        const pos = self.mouseToEditorPosition(file.getEditor(), {clientX: event.x, clientY: event.y});
        const fileName = file.getFileName();
        const fileURI = self.fileNameToUri(fileName);
        var param = {
            "textDocument": {
                "uri": fileURI
            },
            "position": {
                "line": pos.row,
                "character": pos.column
            }
        };
        return self.sendRequest("textDocument/hover", param, fileName);
    };

    /**
     * Sets event handlers for the file
     * @param {VPLFile} file The file to set the event handlers for
     */
    this.setEventHandlers = function(file) {
        if (file.isOpen() == false || file.getLSLang() != language || !file.isCode()) {
            return;
        }
        let editor = file.getEditor();
        let session = editor.getSession();
        // We check if the event handlers have already been set.
        if (editor.settedVPLLSEventHandlers != undefined) {
            return;
        }
        session.$vplfile = file;
        if (serverCapabilities.hasCompletionProvider()) {
            editor.completers = [self.aceCompleterAdapter];
        }
        session.on('change', function(delta) {
            resetInactivityTimeout();
            if (self.eventHandlersActive) {
                self.addFileChangeDelta(file, delta);
                handleSignatureHelpChange(file, delta);
            }
        });
        session.selection.on('changeCursor', function() {
            let tooltip = file.getSignatureTooltip();
            if (self.eventHandlersActive && tooltip?.isOpen) {
                triggerSignatureHelp(file, {triggerKind: 3, isRetrigger: true});
            }
        });
        editor.on('blur', function() {
            if(file.isOpen()) {
                file.getSignatureTooltip()?.hide();
                file.getHoverTooltip()?.hide();
                file.getTooltip()?.hide();
            }
        });
        var hoverTooltip = file.getHoverTooltip();
        // We add a delay to the hover tooltip hide function
        hoverTooltip.vpl = {};
        hoverTooltip.vpl.hideDelay = 300;
        hoverTooltip.vpl.originalHide = hoverTooltip.hide.bind(hoverTooltip);
        hoverTooltip.vpl.hideTimer = null;
        hoverTooltip.hide = function(e) {
            clearTimeout(hoverTooltip.vpl.hideTimer);
            hoverTooltip.vpl.hideTimer = setTimeout(() => {
                hoverTooltip.vpl.originalHide(e);
            }, hoverTooltip.vpl.hideDelay);
        };
        hoverTooltip.vpl.cancelHide = function() {
            clearTimeout(hoverTooltip.vpl.hideTimer);
        };
        hoverTooltip.getElement().addEventListener("mouseenter", () => {
            hoverTooltip.vpl.cancelHide();
        });
        hoverTooltip.setDataProvider(async function(event, editor) {
            if (!self.eventHandlersActive || !self.isConnected()) {
                hoverTooltip.hide();
                return;
            }
            const pos = event.getDocumentPosition();
            const message = await self.hoverRequest(event, file);
            if (!message?.result?.contents) {
                hoverTooltip.hide();
                return;
            }
            let markDown = getContentMarkDown(message.result.contents);
            let html = VPLMD.markDownToHTML(markDown);
            const messageRange = message.result.range;
            var range;
            if (messageRange) {
                range = file.getRange(
                    messageRange.start.line,
                    messageRange.start.character,
                    messageRange.end.line,
                    messageRange.end.character);
            } else {
                const token = session.getTokenAt(pos.row, pos.column);
                range = file.getRange(pos.row, token.start, pos.row, token.start + token.value.length);
            }
            let DOMNode = document.createElement('div');
            DOMNode.className = "vpl_hover_tooltip" + (file.isDarkTheme() ? " ace_dark" : "");
            DOMNode.style.fontSize = file.getFileManager().getFontSize() + "px";
            DOMNode.innerHTML = html;
            hoverTooltip.showForRange(editor, range, DOMNode, event);
            var tooltip = file.getTooltip();
            if (tooltip?.isOpen) {
                var tooltipRect = tooltip.getElement().getBoundingClientRect();
                var hoverRect = hoverTooltip.getElement().getBoundingClientRect();
                var x = hoverRect.left;
                var y = hoverRect.top - tooltipRect.height;
                tooltip.setPosition(x, y);
            }
        });

        hoverTooltip.addToEditor(editor);
        // Mark that the event handlers have been set.
        editor.settedVPLLSEventHandlers = true;
    };
    // Manage the change file timer acumulating deltas
    // to avoid sending too many notifications to the Language Server.
    (function() {
        var changeFilesTimerId = {};
        var deltaChangeFiles = {};
        /**
         * Checks if there is a change file timer set for a file.
         * @param {VPLFile} file that have been changed in the client
         * @return {boolean} true if there is a change file timer set for the file, false otherwise
         */
        self.isChangeFileTimerSet = function(file) {
            return changeFilesTimerId[file.getFileName()] !== undefined;
        };
        /**
         * Clear the change file timer for a file.
         * @param {VPLFile} file that have been changed in the client
         */
        self.clearChangeFileTimer = function(file) {
            if (self.isChangeFileTimerSet(file)) {
                window.clearTimeout(changeFilesTimerId[file.getFileName()]);
                delete changeFilesTimerId[file.getFileName()];
            }
        };
        self.notifyPendingFileChanges = function(file) {
            if (deltaChangeFiles[file.getFileName()] !== undefined) {
                let deltas = deltaChangeFiles[file.getFileName()];
                self.addTask(
                    async function() {
                        self.fileChangeNotification(file, deltas);
                    }
                );
                delete deltaChangeFiles[file.getFileName()];
            }
            self.clearChangeFileTimer(file);
        };
        /**
         * Start/restart timer for sending a changeFile notification to the Language Server
         * @param {VPLFile} file that have been changed in the client
         * @param {Object} delta Ace change delta to send as an incremental change
         */
        self.addFileChangeDelta = function(file, delta) {
            self.clearChangeFileTimer(file);
            if (delta !== undefined) {
                if (deltaChangeFiles[file.getFileName()] === undefined) {
                    deltaChangeFiles[file.getFileName()] = [];
                }
                if (Array.isArray(delta)) {
                    deltaChangeFiles[file.getFileName()] = deltaChangeFiles[file.getFileName()].concat(delta);
                } else {
                    deltaChangeFiles[file.getFileName()].push(delta);
                }
            }
            changeFilesTimerId[file.getFileName()] = window.setTimeout(function() {
                self.notifyPendingFileChanges(file);
            }, timeoutFileChange);
        };
    })();
    /**
     * Converts an Ace editor change delta into an LSP incremental content change.
     * @param {Object} delta Ace change delta {action, start, end, lines}
     * @returns {Object} LSP content change {range, text}
     */
    function aceDeltaToContentChange(delta) {
        let start = {"line": delta.start.row, "character": delta.start.column};
        if (delta.action == "insert") {
            return {
                "range": {"start": start, "end": start},
                "text": delta.lines.join("\n")
            };
        }
        // Then the delta.action == "remove"
        return {
            "range": {
                "start": start,
                "end": {"line": delta.end.row, "character": delta.end.column}
            },
            "text": ""
        };
    }
    /**
     * Send the didChangeWatchedFiles notification to the Language Server to inform
     * the server about changes to files and folders watched by the language client
     * @param {[Object]} changedFiles File that have been created, changed or deleted {file, type}
     */
    this.didChangeWatchedFilesNotification = async function(changedFiles) {
        if (Object.keys(watchedFileRegistrations).length === 0) {
            return;
        }
        var changes = [];
        for (let changedFile of changedFiles) {
            let file = changedFile.file;
            let type = changedFile.type;
            if (!fileMatchesWatchedRegistrations(file.getFileName(), type)) {
                continue;
            }
            changes.push({
                "uri": self.fileNameToUri(file.getFileName()),
                "type": type
            });
        }
        if (changes.length === 0) {
            return;
        }
        var param = {
            "changes": changes
        };
        await self.sendNotification("workspace/didChangeWatchedFiles", param);
    };

    /**
     * Send the didChange notification to the Language Server
     * to indicate changes to a file in the client
     *
     * @param {VPLFile} file object of the changed file
     * @param {Object[]} delta Array of delta changes to send as an incremental change
     */
    this.fileChangeNotification = function(file, delta) {
        if (file.isOpen() == false || file.getLSLang() != language) {
            return;
        }
        if (self.isStopped()) {
            tryReconnect();
            return;
        }
        let syncKind = serverCapabilities.getTextDocumentSyncKind();
        if (syncKind === 0) {
            return;
        }
        let fileURI = self.fileNameToUri(file.getFileName());
        let contentChanges;
        if (syncKind === 2 && delta) {
            // Incremental synchronization: send only the change described by the Ace delta.
            contentChanges = delta.map(aceDeltaToContentChange);
        } else {
            // Full synchronization: send the whole file content.
            contentChanges = [{"text": file.getContent()}];
        }
        var param = {
            "textDocument": {
                "uri": fileURI,
                "version": self.getNewVersion()
            },
            "contentChanges": contentChanges
        };
        self.sendNotification("textDocument/didChange", param);
    };
    /**
     * Send the didOpen notification to the Language Server
     * to signal newly opened text documents
     * @param {File} file
     */
    this.openFileNotification = function(file) {
        if (file.getLSLang() != language || !file.isOpen() || !self.isConnected()) {
            return;
        }
        self.setEventHandlers(file);
        if (!self.getCapabilities().supportsOpenClose()) {
            return;
        }
        let fileURI = self.fileNameToUri(file.getFileName());
        var param = {
            "textDocument": {
                "uri": fileURI,
                "languageId": file.getLSLang(),
                "version": self.getNewVersion(),
                "text": file.getContent()
            }
        };
        openFilesFirstContent[file.getFileName()] = file.getContent();
        self.sendNotification("textDocument/didOpen", param);
    };

    /**
     * Send the didSave notification to the Language Server to
     * inform when the document was saved in the client before close
     * @param {File} file file object of the saved file
     */
    this.saveFileNotification = async function(file) {
        let resolveSaveFile, rejectSaveFile;
        const promiseSaveFile = new Promise(function(resolve, reject) {
            resolveSaveFile = resolve;
            rejectSaveFile = reject;
        });
        if (!file || !self.getCapabilities().supportsSave() || self.isStopped()) {
            resolveSaveFile({result: null});
            return promiseSaveFile;
        }
        const fileName = file.getFileName();
        const fileURI = self.fileNameToUri(fileName);
        const content = file.getContent();
        if (openFilesFirstContent[fileName] === content) {
            resolveSaveFile({result: null});
            return promiseSaveFile;
        }
        var param = {
            "textDocument": {"uri": fileURI}
        };
        if (self.getCapabilities().saveIncludesText()) {
            param.text = content;
        }
        const data = {
            files: [{
                name: self.fileNameToProjectPath(fileName),
                contents: content,
                encoding: 0
            }],
            filestodelete: [],
            processid: self.getVPLTaskId()
        };
        VPLUI.requestAction('update', '', data, APIURL, true)
            .done(
                async function() {
                    resolveSaveFile(await self.sendNotification("textDocument/didSave", param));
                }
            ).fail(function(error) {
                rejectSaveFile(error);
            });
        return promiseSaveFile;
    };

    /**
     * Process initialization response from the Language Server
     * and send the initialized notification to the Language Server
     * and notify all open files in the client that are supported by the Language Server
     * @param {*} message The message received from the Language Server
     */
    this.initializeProcess = function(message) {
        // Wrap raw capabilities into LSServerCapabilities instance for helpers
        serverCapabilities = new LSServerCapabilities(message?.result?.capabilities);
        self.aceCompleterAdapter.triggerCharacters = serverCapabilities.getCompletionTriggerCharacters();
        let serverName = message?.result?.serverInfo?.name;
        let serverVersion = message?.result?.serverInfo?.version;
        serverName = serverName ? serverName : "Unknown";
        serverVersion = serverVersion ? serverVersion : "Unknown";
        log("Server " + serverName + " " + serverVersion, true);
        self.sendNotification("initialized", {});
        // Enable event handlers while LS is running triggering LSP activity
        self.eventHandlersActive = true;
        for (let file of fileManager.getFiles()) {
            if (file.isOpen() && file.getLSLang() == language) {
                self.openFileNotification(file);
            }
        }
    };

    /**
     * Processes the log messages received from the Language Server and if it contains
     * the message "Main thread is waiting", it initializes the connection with the Language Server
     * @param {*} message The message received from the Language Server containing the log information
     */
    function windowLogMessage(message) {
        if (!message?.params) {
            return;
        }
        var type = message?.params?.type;
        var messageText = message?.params?.message;
        const typeNames = ["Error", "Warning", "Info", "Log"];
        var typeName;
        if (typeof type === "number") {
            typeName = typeNames[type - 1] || "Unknown";
        } else if (typeof type === "string") {
            typeName = type;
        } else {
            typeName = "Unknown";
        }
        const forced = type == 0 || messageText.includes("starting");
        log(message.method + " " + typeName + ": " + messageText, forced);
    }

    /**
     * Generate HTML code from the hover information received from the Language Server
     * @param {*} contents part of the message received
     * @returns {string} HTML code generated for the hover information
     */
    function getContentMarkDown(contents) {
        let html = "";
        if (contents === undefined || contents === null) {
            return html;
        }
        if (Array.isArray(contents)) {
            for (let contentPart of contents) {
                html += getContentMarkDown(contentPart) + "\n";
            }
        } else if (typeof contents === "string") {
            html += contents + "\n";
        } else {
            let header = "";
            if (contents.language !== undefined) {
                header += contents.language.toUpperCase() + " - ";
            }
            if (contents.value !== undefined) {
                header += contents.value;
            }
            html += header + "\n";
        }
        return html;
    }

    /**
     * It goes to file and start line of the definition requested
     * received from the Language Server
     * @param {*} message The message received from the Language Server
     */
    function definition(message) {
        if (!message.result || message.result.length === 0) {
            return;
        }
        let place = message.result[0];
        if (place.uri.startsWith(urihomepath)) {
            let fileName = self.uriToFileName(place.uri);
            if (fileName === null) {
                return;
            }
            fileManager.gotoFileName(fileName, place.range.start.line + 1);
        }
    }

    /**
     * It goes to file and start line of the implementation requested
     * received from the Language Server
     * @param {*} message The message received from the Language Server
     */
    function implementation(message) {
        definition(message);
    }

    /**
     * Returns the file data (file name and lines) for a given file name
     * @param {string} fileName The name of the file to get the data for
     * @returns {Object} An object containing the file name and lines, or null if the file is not found
     */
    function getFileDataForReferences(fileName) {
        let file = fileManager.getFileByName(fileName);
        if  (file) {
            return {fileName: fileName, lines: file.getContent().split("\n")};
        }
        return {fileName: null, lines: null};
    }

    /**
     * It shows the references received from the Language Server
     * with the file name and line number, and when clicking on it, goes to the corresponding file and line.
     * @param {*} message The message received from the Language Server containing the references
     * @param {*} request The request sent to the Language Server that generated the references
     */
    function references(message, request) {
        if(!Array.isArray(message.result) || message.result.length === 0) {
            return;
        }
        var inList = false;
        var content = "";
        var referenceName = request?.data?.name ?? "?";
        content +=  "<b>" + VPLUtil.str('referencesfor', VPLUtil.sanitizeText(referenceName)) + "</b>\n<hr>\n<br>\n";
        let fileData = {fileName: null, line: null};
        for (let place of message.result) {
            let start = place.range.start;
            let end = place.range.end;
            let fileName = self.uriToFileName(place.uri);
            if (fileData.fileName !== fileName) {
                fileData = getFileDataForReferences(fileName);
                if (inList) {
                    // Close the previous list of references for the previous file.
                    content += "</ul>\n";
                    inList = false;
                }
                if (fileData.fileName !== null) {
                    // Show the file name in bold and start a new list for the references in that file.
                    content += "<b>" + VPLUtil.sanitizeText(fileName) + "</b>\n<ul>\n";
                    inList = true;
                }
            }
            if (fileData.fileName === null) {
                continue;
            }
            let line = fileData.lines[start.line];
            let saniFilename = VPLUtil.sanitizeText(fileName);
            let iniText = VPLUtil.sanitizeText(line.substring(0, start.character));
            var reference = '<a href="#" data-file="' + saniFilename + '" data-line="' + (start.line + 1) + '">';
            reference += VPLUtil.sanitizeText(line.substring(start.character, end.character)) + '</a>';
            let endText = VPLUtil.sanitizeText(line.substring(end.character));
            content += "<li>Line " + (start.line + 1) + ": " + iniText + reference + endText + "</li>\n";
        }
        if (content === "") {
            return;
        } else {
            content += "</ul>\n";
        }
        let ide = fileManager.getIDE();
        ide.setResult({references: content});
    }

    /**
     * It applies the identifier rename changes received from the Language Server
     * and identifier renames affecting multiple files.
     * @param {*} message The message received from the Language Server containing the changes to apply
     */
    function rename(message) {
        self.applyWorkspaceEdit(message.result);
    }

    /**
     * Handles a 'workspace/applyEdit' request sent by the Language Server, applies the
     * workspace edit to the client files and replies with an ApplyWorkspaceEditResponse.
     * @param {Object} message The request received from the Language Server. message.params
     *                          contains an ApplyWorkspaceEditParams {label?, edit}.
     */
    async function applyEdit(message) {
        var resolveApplyEdit, rejectApplyEdit;
        var promiseApplyEdit = new Promise(function(resolve, reject) {
            resolveApplyEdit = resolve;
            rejectApplyEdit = reject;
        });

        var applied = false;
        var failureReason;
        var affected = {changed: [], deleted: []};
        try {
            let edit = message.params?.edit;
            if (edit) {
                affected = self.getAffectedFiles(edit);
                self.applyWorkspaceEdit(edit);
                applied = true;
            } else {
                failureReason = "No edit provided in workspace/applyEdit request";
            }
        } catch (error) {
            failureReason = typeof error === 'string' ? error : (error?.message || "applyEdit failed");
            log("workspace/applyEdit failed: " + failureReason);
        }
        // ApplyWorkspaceEditResponse: {applied: boolean, failureReason?: string}
        let result = {"applied": applied};
        if (!applied && failureReason !== undefined) {
            result.failureReason = failureReason;
        }
        var files = [];
        for (let fileName of affected.changed) {
            let file = fileManager.getFileByName(fileName);
            if (file) {
                files.push({
                    name: self.fileNameToProjectPath(fileName),
                    contents: file.getContent(),
                    encoding: 0
                });
            }
        }
        var filestodelete = [];
        for (let fileName of affected.deleted) {
            filestodelete.push(self.fileNameToProjectPath(fileName));
        }
        var data = {
            files: files,
            filestodelete: filestodelete,
            processid: self.getVPLTaskId()
        };
        VPLUI.requestAction('update', '', data, APIURL, true)
        .done(
            async function() {
                resolveApplyEdit(await self.sendResponse(message.id, result));
            }
        ).fail(function(error) {
            rejectApplyEdit(error);
        });
        return promiseApplyEdit;
    }

    /**
     * It applies the changes received from the Language Server to the corresponding file
     * @param {*} message The message received from the Language Server containing the changes to apply
     * @param {*} request The request object containing the fileName and other request details
     */
    function formatting(message, request) {
        let file = fileManager.currentFile();
        if (file == false || file.getFileName() != request.fileName || file.getLSLang() != language || !file.isCode()) {
            return;
        }
        self.removeMarkersOfFile(file);
        let editor = file.getEditor();
        let document = editor.getSession().getDocument();
        for (let i = message.result.length - 1; i > -1; i--) {
            let change = message.result[i];
            let start = change.range.start;
            let end = change.range.end;
            let range = file.getRange(start.line, start.character, end.line, end.character);
            document.replace(range, change.newText);
        }
    }

    /**
     * Does the same as formatting but for a specific range of the file.
     * @param {*} message The message received from the Language Server containing the changes to apply
     * @param {*} request The request object containing the fileName and other request details
     */
    function rangeFormatting(message, request) {
        formatting(message, request);
    }
    /**
     * Initialize the Ace editor autocompletion with a custom completer
     * that sends completion requests to the Language Server
     */
    (function() {
        const defaultTriggerCharacters = [".", ">", "@", "$"];
        const mapKindToMeta = [
            undefined,
            "text", // Number 1 - Text
            "method", // Number 2 - Method
            "function", // Number 3 - Function
            "constructor", // Number 4 - Constructor
            "field", // Number 5 - Field
            "variable", // Number 6 - Variable
            "class", // Number 7 - Class
            "interface", // Number 8 - Interface
            "module", // Number 9 - Module
            "property", // Number 10 - Property
            "unit", // Number 11 - Unit
            "value", // Number 12 - Value
            "enum", // Number 13 - Enum
            "keyword", // Number 14 - Keyword
            "snippet", // Number 15 - Snippet
            "color", // Number 16 - Color
            "file", // Number 17 - File
            "reference", // Number 18 - Reference
            "folder", // Number 19 - Folder
            "enumMember", // Number 20 - EnumMember
            "constant", // Number 21 - Constant
            "struct", // Number 22 - Struct
            "event", // Number 23 - Event
            "operator", // Number 24 - Operator
            "type" // Number 25 - TypeParameter
        ];

        /**
         * Build the HTML content for the documentation of a completion item
         * using the 'detail' and 'documentation' properties of the item
         * @param {Object} item of LSP completion
         * @returns {string} The HTML content for the documentation
         */
        function buildDocHTML(item) {
            let parts = [];
            if (item.detail) {
                parts.push(VPLUtil.sanitizeText(item.detail));
            }
            if (item.documentation) {
                parts.push(VPLMD.markDownToHTML(item.documentation?.value || item.documentation));
            }
            return parts.join("<br>");
        }
        /**
         * Translate a completion item received from the Language Server
         * to the format expected by the Ace editor
         * @param {Object} item The completion item received from the Language Server
         * @param {Number} rank Position of the item in the server-ordered list (0 = highest priority)
         * @param {Number} total Total number of items, used to compute a descending Ace score
         * @return {Object} The completion item translated to the Ace editor
         */
        function translateCompletionItemToAce(item, rank, total) {
            return {
                caption: item.label,
                value: item.textEdit?.newText ?? item.insertText ?? item.label,
                meta: (mapKindToMeta[item.kind] || "completion") + " - LSP",
                docHTML: buildDocHTML(item),
                filterText: item.filterText ?? undefined,
                // Ace orders by score descending. Preserve the server-provided ordering by
                // assigning a score that decreases with the rank in the sorted list.
                score: total - rank,
                completer: self.aceCompleterAdapter,
                lspItem: item, // Keep original LSP item for resolve/apply logic
            };
        }

        /**
         * Sort completion items following the LSP rules so the most relevant item comes first:
         * preselected items first, then by 'sortText' (lexicographic), then by 'label'.
         * @param {Array<Object>} items The completion items received from the Language Server
         * @return {Array<Object>} A new array with the items sorted by relevance
         */
        function sortCompletionItems(items) {
            return items.slice().sort(function(a, b) {
                if ((a.preselect ? 1 : 0) !== (b.preselect ? 1 : 0)) {
                    return a.preselect ? -1 : 1;
                }
                const aKey = a.sortText ?? a.label ?? "";
                const bKey = b.sortText ?? b.label ?? "";
                if (aKey !== bKey) {
                    return aKey < bKey ? -1 : 1;
                }
                const aLabel = a.label ?? "";
                const bLabel = b.label ?? "";
                return aLabel < bLabel ? -1 : (aLabel > bLabel ? 1 : 0);
            });
        }

        self.aceCompleterAdapter = {
            id: "VPL LSPClient Completer",
            identifierRegexps: [/[\p{L}0-9_$]/u],
            getCompletions: async function(editor, session, pos, prefix, callback) {
                try {
                    if (!self.isConnected()) {
                        callback(null, []);
                        return;
                    }
                    // Stop too agressive autocompletion
                    // editor.setOption('enableLiveAutocompletion', enable)
                    // Add an empty message when no completions are available to avoid the "No suggestions" message from Ace
                    const line = editor.getSession().getLine(pos.row);
                    const lastChar = line[pos.column - 1];
                    const triggerChars = self.aceCompleterAdapter.triggerCharacters;
                    var context = {
                        triggerKind: triggerChars.includes(lastChar) ? 2 : 1, // Invoked manually or by typing
                    };
                    if (context.triggerKind === 2) {
                        context.triggerCharacter = lastChar;
                    }
                    const message = await self.completionRequest(session.$vplfile, context);
                    const result = message?.result;
                    // LSP can return completion items or an object with an 'items' property with completion items
                    var items;
                    if (Array.isArray(result)) {
                        items = result;
                    } else if (Array.isArray(result?.items)) {
                        items = result.items;
                    } else {
                        items = [];
                    }
                    var sortedItems = sortCompletionItems(items);
                    var aceItems = sortedItems.map(function(item, index) {
                        return translateCompletionItemToAce(item, index, sortedItems.length);
                    });
                    // When the prefix starts with $ the LSP server may return items without $
                    // (e.g. bash). Prepend $ to caption and filterText for display/matching,
                    // and mark items that need $ prepended during insertion.
                    if (prefix && prefix[0] === '$') {
                        aceItems.forEach(function(aceItem) {
                            if (!aceItem.caption.startsWith('$')) {
                                aceItem.caption = '$' + aceItem.caption;
                                aceItem.filterText = '$' + (aceItem.filterText || aceItem.lspItem.label);
                                aceItem.vplDollarPrefix = true;
                            }
                        });
                    }
                    callback(null, aceItems);
                } catch (err) {
                    log("Completion failed", true);
                    callback(null, []);
                }
            },
            insertMatch: function(editor, data) {
                const lspItem = data.lspItem;
                var text = lspItem.textEdit?.newText ?? lspItem.insertText ?? lspItem.label;
                // If the server omitted the leading $ (e.g. bash), restore it.
                if (data.vplDollarPrefix && !text.startsWith('$')) {
                    text = '$' + text;
                }
                const isSnippet = lspItem.insertTextFormat === 2;
                const session = editor.getSession();
                /**
                 * Remove the prefix already typed by the user before inserting a completion.
                 * @param {Object} ed The Ace editor instance
                 */
                function removeTypedPrefix(ed) {
                    const idRegexp = self.aceCompleterAdapter.identifierRegexps[0];
                    for (const range of ed.selection.getAllRanges()) {
                        const line = ed.session.getLine(range.start.row);
                        let start = range.start.column;
                        while (start > 0 && idRegexp.test(line[start - 1])) {
                            start--;
                        }
                        if (start < range.start.column) {
                            range.start.column = start;
                            ed.session.remove(range);
                        }
                    }
                }

                if (lspItem.textEdit) {
                    // The textEdit is either a TextEdit (range) or an InsertReplaceEdit (insert/replace).
                    const lsprange = lspItem.textEdit.range ?? lspItem.textEdit.replace ?? lspItem.textEdit.insert;
                    // The textEdit range was computed when the completion was requested. While the
                    // popup stayed open the user may have typed more characters, which now lie after
                    // lsprange.end. Extend the end of the range up to the current cursor so those
                    // characters are replaced too and are not left trailing the inserted completion.
                    let endRow = lsprange.end.line;
                    let endColumn = lsprange.end.character;
                    const cursorPos = editor.getCursorPosition();
                    if (cursorPos.row > endRow || (cursorPos.row === endRow && cursorPos.column > endColumn)) {
                        endRow = cursorPos.row;
                        endColumn = cursorPos.column;
                    }
                    const acerange = session.$vplfile.getRange(
                            lsprange.start.line,
                            lsprange.start.character,
                            endRow,
                            endColumn
                        );
                    if (isSnippet) {
                        editor.session.replace(acerange, "");
                        editor.moveCursorTo(lsprange.start.line, lsprange.start.character);
                        editor.insertSnippet(text);
                    } else {
                        editor.session.replace(acerange, text);
                    }
                } else {
                    removeTypedPrefix(editor);
                    if (isSnippet) {
                        editor.insertSnippet(text);
                    } else {
                        editor.insert(text);
                    }
                }
                // Apply additional edits (e.g. auto-imports) requested by the server alongside the completion.
                if (Array.isArray(lspItem.additionalTextEdits) && lspItem.additionalTextEdits.length > 0) {
                    const fileURI = self.fileNameToUri(session.$vplfile.getFileName());
                    self.applyWorkspaceEdit({"changes": {[fileURI]: lspItem.additionalTextEdits}});
                }
                if (lspItem.command) {
                    self.sendRequest("workspace/executeCommand", lspItem.command);
                }
            },
            triggerCharacters: defaultTriggerCharacters
        };
    })();

    /**
     * Converts an LSP glob pattern string to a regex string (without anchors).
     * Supports *, **, ?, {a,b} and character classes.
     * @param {string} glob LSP glob pattern
     * @returns {string} Regex string
     */
    function globToRegexStr(glob) {
        var str = '';
        var i = 0;
        /**
         * Converts an asterisk (*) in a glob pattern to a regex string.
         * @returns {string} Regex string for the asterisk
         */
        function asteriskToRegex() {
            if (glob[i + 1] === '*') {
                str += '.*';
                i += 2;
                if (glob[i] === '/') {
                    i++;
                }
            } else {
                str += '[^/]*';
                i++;
            }
        }
        /**
         * Converts a question mark (?) in a glob pattern to a regex string.
         * @returns {string} Regex string for the question mark
         */
        function questionMarkToRegex() {
            str += '[^/]';
            i++;
        }
        /**
         * Converts a brace ({a,b}) in a glob pattern to a regex string.
         * @returns {string} Regex string for the brace
         */
        function braceToRegex() {
            let j = i + 1;
            let depth = 1;
            let parts = [];
            let part = '';
            while (j < glob.length && depth > 0) {
                if (glob[j] === '{') {
                    depth++;
                    part += glob[j];
                } else if (glob[j] === '}') {
                    depth--;
                    if (depth === 0) {
                        parts.push(part);
                    } else {
                        part += glob[j];
                    }
                } else if (glob[j] === ',' && depth === 1) {
                    parts.push(part);
                    part = '';
                } else {
                    part += glob[j];
                }
                j++;
            }
            str += '(?:' + parts.map(globToRegexStr).join('|') + ')';
            i = j;
        }
        /**
         * Converts a bracket ([abc]) in a glob pattern to a regex string.
         * @returns {string} Regex string for the bracket
         */
        function bracketToRegex() {
            let j = i + 1;
            while (j < glob.length && glob[j] !== ']') {
                j++;
            }
            str += glob.substring(i, j + 1);
            i = j + 1;
        }
        while (i < glob.length) {
            const c = glob[i];
            if (c === '*') {
                asteriskToRegex();
            } else if (c === '?') {
                questionMarkToRegex();
            } else if (c === '{') {
                braceToRegex();
            } else if (c === '[') {
                bracketToRegex();
            } else {
                str += c.replace(/[.+^${}()|[\]\\]/g, '\\$&');
                i++;
            }
        }
        return str;
    }

    /**
     * Returns true if fileName and change type match any registered file watcher.
     * @param {string} fileName
     * @param {number} fileChangeType LSP FileChangeType: Created=1, Changed=2, Deleted=3
     * @returns {boolean}
     */
    function fileMatchesWatchedRegistrations(fileName, fileChangeType) {
        // Map LSP FileChangeType to WatchKind bit (Created=1, Changed=2, Deleted=4)
        const watchKindBit = fileChangeType < 3 ? fileChangeType : 4;
        for (let reg of Object.values(watchedFileRegistrations)) {
            for (let watcher of reg.watchers) {
                const kind = watcher.kind !== undefined ? watcher.kind : 7;
                // eslint-disable-next-line no-bitwise
                if ((kind & watchKindBit) && watcher.regex && watcher.regex.test(fileName)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Handle client/registerCapability request from the Language Server.
     * Stores didChangeWatchedFiles registrations and responds with an empty result.
     * @param {Object} message The request received from the Language Server
     */
    async function registerCapability(message) {
        const registrations = message?.params?.registrations || [];
        for (let reg of registrations) {
            if (reg.method === 'workspace/didChangeWatchedFiles') {
                const watchers = (reg.registerOptions?.watchers || []).map(function(watcher) {
                    const globPattern = watcher.globPattern;
                    const pattern = typeof globPattern === 'string' ? globPattern : globPattern?.pattern;
                    let regex = null;
                    if (pattern) {
                        try {
                            regex = new RegExp('^' + globToRegexStr(pattern) + '$');
                        } catch (e) {
                            log('Invalid glob pattern: ' + pattern);
                        }
                    }
                    return {globPattern: globPattern, kind: watcher.kind, regex: regex};
                });
                watchedFileRegistrations[reg.id] = {watchers: watchers};
                log('Registered file watcher id=' + reg.id + ' watchers=' + watchers.length);
            }
        }
        if (message.id !== undefined) {
            return self.sendResponse(message.id, null);
        } else {
            return Promise.resolve({result: null});
        }
    }

    /**
     * Handle client/unregisterCapability request from the Language Server.
     * Removes the stored registrations and responds with an empty result.
     * @param {Object} message The request received from the Language Server
     */
    async function unregisterCapability(message) {
        const unregistrations = message?.params?.unregistrations || [];
        for (let unreg of unregistrations) {
            if (watchedFileRegistrations[unreg.id]) {
                delete watchedFileRegistrations[unreg.id];
                log('Unregistered file watcher id=' + unreg.id);
            }
        }
        if (message.id !== undefined) {
            return self.sendResponse(message.id, null);
        } else {
            return Promise.resolve({result: null});
        }
    }

    /**
     * Object that maps the methods of the messages received from the Language Server
     * to the corresponding functions that process them in the client.
     */
    const responseActions = {
        "language/status": windowLogMessage,
        "window/logMessage": windowLogMessage,
        "window/showMessage": windowLogMessage,
        "initialize": VPLUtil.doNothing,
        "textDocument/publishDiagnostics": publishDiagnostics,
        "textDocument/completion": VPLUtil.doNothing,
        "textDocument/hover": VPLUtil.doNothing, // Hover is handled in the request promise
        "textDocument/definition": definition,
        "textDocument/implementation": implementation,
        "textDocument/references": references,
        "textDocument/signatureHelp": VPLUtil.doNothing, // Handled in the signatureHelp request promise
        "textDocument/prepareRename": VPLUtil.doNothing, // Done in the prepareRename request promise
        "textDocument/rename": rename,
        "textDocument/formatting": formatting,
        "textDocument/rangeFormatting": rangeFormatting,
        "textDocument/codeAction": VPLUtil.doNothing,
        "textDocument/command": VPLUtil.doNothing,
        "workspace/executeCommand": VPLUtil.doNothing,
        "workspace/applyEdit": applyEdit,
        "client/registerCapability": registerCapability,
        "client/unregisterCapability": unregisterCapability
    };
    /**
     * It processes a message received from the Language Server
     * and executes the corresponding actions in the client.
     * @param {Object} message received from the Language Server
     */
    function processArrivedMessage(message) {
        // Type error message
        if (message.error != undefined) {
            log('Error code: ' + message.error.code + ' message: ' + message.error.message);
            if (message.error.data != undefined) {
                log(JSON.stringify(message.error.data));
            }
            if (message.id != undefined) {
                let errRequest = getRequestByIdAndRemove(message.id);
                if (errRequest.resolve) {
                    errRequest.resolve({result: null});
                }
            }
            return;
        }
        // Type notification message
        var request = {
            fileName: null,
            requestMethod: null,
            data: null
        };
        if (message.id != undefined) {
            request = getRequestByIdAndRemove(message.id);
        }

        if (message.method != undefined) {
            if (responseActions[message.method] != undefined) {
                log("Processing method: " + message.method);
                responseActions[message.method](message, request);
                return;
            }
        }
        if (responseActions[request.requestMethod] != undefined) {
            log("Processing method: " + request.requestMethod);
            responseActions[request.requestMethod](message, request);
            if (request.resolve) {
                request.resolve(message);
            }
        } else {
            let method = message.method != undefined ? message.method : (request.requestMethod + " (from request)");
            log("Unsupported response method: " + method);
            if (request.resolve) {
                request.resolve({result: null});
            }
        }
    }

    /**
     * It processes the messages received from the Language Server
     * and executes the corresponding actions in the client.
     * @param {Array} messages received from the Language Server
     */
    function processArrivedMessages(messages) {
        for (let message of messages) {
            processArrivedMessage(message);
        }
        self.setStatus();
    }

    // Pending message for the Language Server
    var pendingMessage = {
        "active": false,
        "buffer": new Uint8Array(0),  // The byte buffer — Content-Length is in bytes, not chars
        "expectedLength": 0,
    };
    const contentLengthRegex = /Content-Length:\s*(\d+)/i;
    const firstEmptyLineRegex = /\r?\n\r?\n/;
    const lsTextEncoder = new TextEncoder();
    const lsTextDecoder = new TextDecoder();

    /**
     * It processes the response from the WebSocket connection with the Language Server
     * and executes the corresponding actions in the client.
     * @param {*} response read from the WebSocket connection with the Language Server.
     */
    function processIncomingData(response) {
        var datos = response.data;
        // Append incoming bytes to the pending byte buffer
        const newBytes = lsTextEncoder.encode(datos);
        const combined = new Uint8Array(pendingMessage.buffer.length + newBytes.length);
        combined.set(pendingMessage.buffer);
        combined.set(newBytes, pendingMessage.buffer.length);
        pendingMessage.buffer = combined;
        var messages = [];
        while (pendingMessage.buffer.length > 0) {
            if (pendingMessage.active) {
                // We are in the middle of receiving a message, we need to read the remaining bytes
                if (pendingMessage.buffer.length < pendingMessage.expectedLength) {
                    // We have not received the complete message
                    break;
                }
                // We have received the complete message (expectedLength bytes), we can process it
                let messageContent = lsTextDecoder.decode(
                    pendingMessage.buffer.subarray(0, pendingMessage.expectedLength)
                );
                pendingMessage.buffer = pendingMessage.buffer.subarray(pendingMessage.expectedLength);
                pendingMessage.active = false;
                try {
                    messages.push(JSON.parse(messageContent));
                } catch (e) {
                    log("Error parsing JSON message: " + e);
                    log("Wrong JSON content: " + messageContent);
                }
            } else {
                // Headers are ASCII — byte index equals char index, safe to decode for search
                let bufferStr = lsTextDecoder.decode(pendingMessage.buffer);
                let firstEmptyLineMatch = bufferStr.match(firstEmptyLineRegex);
                if (firstEmptyLineMatch) {
                    let headers = bufferStr.substring(0, firstEmptyLineMatch.index).trim();
                    // Header bytes are ASCII so char offset == byte offset
                    let headerSize = firstEmptyLineMatch.index + firstEmptyLineMatch[0].length;
                    pendingMessage.buffer = pendingMessage.buffer.subarray(headerSize);
                    if (headers.length == 0) {
                        // Ignore empty lines between messages
                        continue;
                    }
                    let contentLengthMatch = headers.match(contentLengthRegex);
                    if (!contentLengthMatch) {
                        log("Ignoring header response: no Content-Length header found.");
                        log("Header received: " + headers);
                        continue;
                    }
                    pendingMessage.active = true;
                    pendingMessage.expectedLength = parseInt(contentLengthMatch[1]);
                } else {
                    // We have not received the complete headers, we need to wait for more data
                    break;
                }
            }
        }
        processArrivedMessages(messages);
    }
    var reConnectionTimerId = null;
    /**
     * It tries to reconnect with the Language Server
     * if the connection is lost.
     */
    function tryReconnect() {
        if (reConnectionTimerId !== null || self.isConnecting()) {
            return;
        }
        ws = null;
        // Resolve all pending requests with null result to avoid hanging promises
        for (let id of Object.keys(requests)) {
            window.clearTimeout(requests[id].timeoutId);
            requests[id].resolve({result: null});
        }
        requests = {};
        if (reConnection < maxConnectionsAttempts) {
            reConnectionTimerId = setTimeout(function() {
                self.startConnection();
            }, waitTimeForRetryingLSConection);
            reConnection++;
        } else {
            log("Max reconnection attempts reached. Stopping reconnecting.");
        }
    }
    /**
     * Start connection with the Language Server
     */
    this.startConnection = function() {
        VPLTaskId = null;
        messageId = 0;
        version = 0;
        ws = null;
        requests = {};
        self.removeAllMarkers();
        serverCapabilities = new LSServerCapabilities({});
        watchedFileRegistrations = {};
        let files = [];
        for (let file of fileManager.getFiles()) {
            files.push(
                {
                    name: projectFolder + "/" + file.getFileName(),
                    contents: file.getContent(),
                    encoding: file.isBinary() ? 1 : 0
                }
            );
        }
        log("Starting connection");
        // Placeholder socket used while the real connection is being established.
        // It mimics the WebSocket readyState constants so the connection-state helpers
        // (isConnecting/isConnected/isStopped) work during this window.
        ws = {
            connection: {
                readyState: 0,
                CONNECTING: 0,
                OPEN: 1,
                CLOSING: 2,
                CLOSED: 3
            }
        };
        self.setStatus();
        VPLUtil.directRun(APIURL, '$' + language, files)
        .then(function(openws) {
            log("Connection established. home: " + openws.homepath);
            ws = openws;
            let projectpath = openws.homepath + "/" + projectFolder;
            urihomepath = "file://" + projectpath.split("/").map(encodeURIComponent).join("/");
            VPLTaskId = openws.processid;
            reConnection = 0;
            ws.connection.onmessage = processIncomingData;
            ws.connection.onopen = self.setStatus;
            ws.connection.onerror = function() {
                log("WebSocket error occurred.");
                self.setStatus();
            };
            ws.connection.onclose = function() {
                // Disable event handlers while LS is stopped to avoid triggering LSP activity.
                log("WebSocket closed.");
                self.eventHandlersActive = false;
                tryReconnect();
            };
            reConnectionTimerId = null;
            self.setStatus();
            self.initializeRequest();
            return Promise.resolve();
        })
        .catch(function() {
            ws = null;
            reConnectionTimerId = null;
            self.setStatus();
            tryReconnect();
        });
    };
    /**
     * Close the underlying socket detaching its event handlers first, so an
     * intentional close does not trigger the onclose reconnection logic.
     */
    function closeSocket() {
        // Disable event handlers while LS is stopped to avoid triggering LSP activity
        if (ws?.connection) {
            ws.connection.onclose = null;
            ws.connection.onerror = null;
            ws.connection.onopen = null;
            ws.connection.onmessage = null;
            try {
                ws.connection.close();
            } catch (e) {
                // Ignore errors while closing the socket.
            }
        }
    }
    /**
     * Close connection with the Language Server
     */
    this.stopConnection = function() {
        let taskId = VPLTaskId;
        VPLTaskId = null;
        // Cancel any pending reconnection so the LS stays down until there is activity again.
        if (reConnectionTimerId !== null) {
            window.clearTimeout(reConnectionTimerId);
            reConnectionTimerId = null;
        }
        closeSocket();
        // Disable event handlers while LS is stopped to avoid triggering LSP activity
        self.eventHandlersActive = false;
        ws = null;
        self.setStatus();
        if (taskId !== null) {
            VPLUtil.cancelDirectRun(APIURL, taskId)
            .then(function() {
                log("LS stopped");
                return Promise.resolve();
            })
            .catch(function() {
                log("Error stopping LS");
            });
        }
    };

    /**
     * Send a notification to the Language Server
     * @param {String} method Name of the notification
     * @param {String} params Parameters needed for the notification
     */
    this.sendNotification = function(method, params) {
        let jsonMessage = {
            "jsonrpc": "2.0",
            "method": method,
            "params": params
        };
        if (this.isConnected()) {
            resetInactivityTimeout();
            var payload = JSON.stringify(jsonMessage);
            const contentLength = new TextEncoder().encode(payload).length;
            var request = "Content-Length: " + contentLength + "\r\n\r\n" + payload;
            ws.connection.send(request);
            log("Sent notification: " + method);
        } else {
            log("Cannot send notification: " + method + " due to connection lost.");
        }
    };

    /**
     * Send a response to a request issued by the Language Server (server-to-client request).
     * @param {Number} id The id of the request being answered
     * @param {*} result The result to send back to the Language Server
     */
    this.sendResponse = function(id, result) {
        let jsonMessage = {
            "jsonrpc": "2.0",
            "id": id,
            "result": result
        };
        if (this.isConnected()) {
            resetInactivityTimeout();
            var payload = JSON.stringify(jsonMessage);
            const contentLength = new TextEncoder().encode(payload).length;
            var response = "Content-Length: " + contentLength + "\r\n\r\n" + payload;
            ws.connection.send(response);
            log("Sent response for id: " + id);
        } else {
            log("Cannot send response for id: " + id + " due to connection lost.");
        }
    };

    /**
     * Send a request to the Language Server
     * @param {String} method Name of the request
     * @param {String} params Parameters needed for the request
     * @param {String|null} fileName Name of the file related to the request, if any
     * @param {Object|null} data Additional data related to the request, if any
     */
    this.sendRequest = function(method, params, fileName = null, data = null) {
        let id = ++messageId;
        let resolveRequest, rejectRequest;
        const promiseRequest = new Promise(function(res, rej) {
            resolveRequest = res;
            rejectRequest = rej;
        });
        resetInactivityTimeout();
        if (!self.isConnected()) {
            log("Cannot send request: " + method + " due to connection lost.");
            resolveRequest({result: null});
            tryReconnect();
            return promiseRequest;
        }
        const timeoutId = window.setTimeout(function() {
            if (requests[id]) {
                log('Request timeout: ' + method + ' id: ' + id, true);
                delete requests[id];
                resolveRequest({result: null});
            }
        }, requestTimeout);
        requests[id] = {
            'requestMethod': method,
            'fileName': fileName,
            'data': data,
            'resolve': resolveRequest,
            'reject': rejectRequest,
            'timeoutId': timeoutId
        };
        let jsonMessage = {
            "jsonrpc": "2.0",
            "id": id,
            "method": method,
            "params": params
        };
        var payload = JSON.stringify(jsonMessage);
        const contentLength = new TextEncoder().encode(payload).length;
        var request = "Content-Length: " + contentLength + "\r\n\r\n" + payload;
        ws.connection.send(request);
        self.setStatus();
        log("Sent request: " + method + " with id: " + jsonMessage.id);
        return promiseRequest;
    };

    /**
     * Returns id of VPL task running the Language Server
     * @returns string
     */
    this.getVPLTaskId = function() {
        return VPLTaskId;
    };

    /**
     * Returns true if the connection with the Language Server
     * is in the process of being established, false otherwise.
     * @returns boolean
     */
    this.isConnecting = function() {
        return ws !== null && ws.connection.readyState == ws.connection.CONNECTING;
    };
    /**
     * Returns true if the connection with the Language Server
     * is established and ready to send requests, false otherwise.
     * @returns boolean
     */
    this.isConnected = function() {
        return ws !== null && ws.connection.readyState == ws.connection.OPEN;
    };

    /**
     * Returns true if the connection with the Language Server
     * has encountered an error or is closed, false otherwise.
     * @returns boolean
     */
    this.isStopped = function() {
        return ws === null ||
               ws.connection.readyState == ws.connection.CLOSING ||
               ws.connection.readyState == ws.connection.CLOSED;
    };
    /**
     * Returns the status of the Language Server connection as a string.
     * @returns string
     */
    this.getStatus = function() {
        let status = '';
        if (self.isConnecting()) {
            status = '↻';
        } else if (self.isConnected()) {
            if (Object.keys(requests).length > 0) {
                status = '⚙️';
            } else {
                status = '🟢';
            }
        } else if (self.isStopped()) {
            status = '🔴';
        }
        return 'LS ' + status;
    };
    /**
     * Sets the status of the Language Server connection in the IDE.
     */
    this.setStatus = function() {
        const file = fileManager.currentFile();
        if (file && file.getLSLang() === language) {
            const status = {
                lsp: self.getStatus(),
            };
            VPLUI.updateIDEStatus(status);
        }
    };
    /**
     * Returns the language of the Language Server
     * @returns string
     */
    this.getLanguage = function() {
        return language;
    };

    /**
     * Send the initialize request to the Language Server
     */
    this.initializeRequest = function() {
        if (self.isConnecting()) {
            setTimeout(self.initializeRequest, waitTimeConecting);
            return;
        }
        const param = {
            "processId": null,
            "clientInfo": {
                "name": "VPL Language Server Client",
                "version": "1.0"
            },
            "locale": locale,
            "rootUri": urihomepath,
            "workspaceFolders": [
                {
                    "uri": urihomepath,
                    "name": projectFolder
                }
            ],
            "capabilities": {
                "workspace": {
                    "workspaceFolders": true,
                    "configuration": false,
                    "fileOperations": {
                        "didCreate": true,
                        "didRename": true,
                        "didDelete": true
                    },
                    "applyEdit": true,
                    "workspaceEdit": {
                        "documentChanges": true,
                        "resourceOperations": ["create", "rename", "delete"],
                        "failureHandling": "abort",
                        "changeAnnotationSupport": {
                            "groupsOnLabel": true
                        }
                    },
                    "executeCommand": {
                        "dynamicRegistration": false
                    },
                    "didChangeWatchedFiles": {
                        "dynamicRegistration": true
                    },
                },
                "textDocument": {
                    "synchronization": {
                        "dynamicRegistration": false,
                        "didSave": true
                    },
                    "completion": {
                        "dynamicRegistration": false,
                        "contextSupport": true,
                        "completionItem": {
                            "snippetSupport": true,
                            "commitCharactersSupport": false,
                            "deprecatedSupport": false,
                            "insertReplaceSupport": false,
                            "preselectSupport": true,
                            "documentationFormat": ["markdown", "plaintext"]
                        },
                        "completionItemKind": {
                            "valueSet": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
                                16, 17, 18, 19, 20, 21, 22, 23, 24, 25]
                        }
                    }, "hover": {
                        "dynamicRegistration": false,
                        "contentFormat": ["markdown", "plaintext"]
                    }, "signatureHelp": {
                        "dynamicRegistration": false,
                        "contextSupport": true,
                        "signatureInformation": {
                            "documentationFormat": ["markdown", "plaintext"],
                            "parameterInformation": {
                                "labelOffsetSupport": true
                            },
                            "activeParameterSupport": true
                        }
                    }, "definition": {
                        "dynamicRegistration": false,
                        "linkSupport": true
                    }, "implementation": {
                        "dynamicRegistration": false,
                        "linkSupport": true
                    }, "references": {
                        "dynamicRegistration": false
                    }, "formatting": {
                        "dynamicRegistration": false
                    }, "rangeFormatting": {
                        "dynamicRegistration": false
                    }, "rename": {
                        "dynamicRegistration": false,
                        "prepareSupport": true,
                        "honorsChangeAnnotations": false
                    }, "publishDiagnostics": {
                        "relatedInformation": true,
                        "codeDescriptionSupport": true,
                        "dataSupport": true
                    }, "codeAction": {
                        "dynamicRegistration": false,
                        "isPreferredSupport": true,
                        "codeActionLiteralSupport": {
                            "codeActionKind": {
                                "valueSet": [
                                    "",
                                    "quickfix",
                                    "source",
                                    "source.organizeImports",
                                    "source.addMissingImports",
                                    "source.removeUnusedImports",
                                    "source.fixAll",
                                    "source.generate",
                                    "refactor",
                                    "refactor.extract",
                                    "refactor.inline",
                                    "refactor.rewrite",
                                    "refactor.move"
                                ]
                            }
                        }
                    }
                }
            }
        };
        // We wait waitTimeLSStart ms to send the initialize request to the Language Server
        // to avoid sending it before the Language Server is ready to receive it
        setTimeout(async function() {
            let result = await self.sendRequest("initialize", param);
            self.initializeProcess(result);
        }, waitTimeLSStart);
    };
};
