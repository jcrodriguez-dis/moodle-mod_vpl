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
 * Tools for the VPL IDE
 *
 * @copyright 2016 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/* globals JUnzip */
/* globals JSInflate */
/* globals Blockly */
/* globals ace */


define(
    [
        'jquery',
        'jqueryui',
        'core/log',
    ],
    function($, jqui, log) {
        var VPLUtil = {};
        VPLUtil.doNothing = $.noop;
        VPLUtil.returnFalse = function() {
            return false;
        };
        VPLUtil.returnTrue = function() {
            return true;
        };
        var debugMode = false;
        VPLUtil.log = function(m, forced) {
            if (debugMode || forced) {
                log.debug(m);
            }
        };
        VPLUtil.setUserPreferences = function(pref) {
            $.ajax({
                async: true,
                type: "POST",
                url: '../editor/userpreferences.json.php',
                'data': JSON.stringify(pref),
                contentType: "application/json; charset=utf-8",
                dataType: "json"
            });
        };
        VPLUtil.getUserPreferences = function(func) {
            $.ajax({
                async: true,
                type: "POST",
                url: '../editor/userpreferences.json.php',
                'data': JSON.stringify({getPreferences: true}),
                contentType: "application/json; charset=utf-8",
                dataType: "json"
            }).done(func);
        };
        // Get scrollBarWidth.
        VPLUtil.scrollBarWidth = function() {
            var parent, child, width;
            parent = $('<div style="width:50px;height:50px;overflow:auto"><div/></div>').appendTo('body');
            child = parent.children();
            width = child.innerWidth() - child.height(99).innerWidth();
            parent.remove();
            return width;
        };
        VPLUtil.sanitizeHTML = function(t) {
            if (typeof t == 'undefined' || t.replace(/^\s+$/g, '') == '') {
                return '';
            }
            return $('<div>' + t + '</div>').html();
        };
        VPLUtil.sanitizeText = function(s) {
            if (typeof s == 'undefined' || s.replace(/^\s+$/g, '') == '') {
                return '';
            }
            return s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        };

        VPLUtil.setProtocol = function(coninfo) {
            var secure;
            if (coninfo.securePort == 0 || coninfo.port == 0) {
                secure = coninfo.port == 0;
            } else {
                switch (coninfo.wsProtocol) {
                    case 'always_use_wss':
                        secure = true;
                        break;
                    case 'always_use_ws':
                        secure = false;
                        break;
                    default:
                        secure = window.location.protocol == 'https:';
                }
            }
            var URLBase = (secure ? 'wss://' : 'ws://') + coninfo.server;
            coninfo.secure = secure;
            coninfo.portToUse = secure ? coninfo.securePort : coninfo.port;
            URLBase += ':' + coninfo.portToUse + '/';
            coninfo.monitorURL = URLBase + coninfo.monitorPath;
            coninfo.executionURL = URLBase + coninfo.executionPath;
        };
        VPLUtil.ArrayBuffer2String = function(data) {
            var view = new Uint8Array(data);
            var chunks = [];
            var chunkSize = 32000;
            var i, len;
            for (i = 0, len = view.length; i < len; i += chunkSize) {
                chunks.push(String.fromCharCode.apply(String, view.subarray(i, Math.min(i + chunkSize, len))));
            }
            return chunks.join('');
        };
        VPLUtil.String2ArrayBuffer = function(data) {
            var len = data.length;
            var ret = new ArrayBuffer(len);
            var u8 = new Uint8Array(ret);
            for (var i = 0; i < len; i++) {
                u8[i] = data.charCodeAt(i);
            }
            return ret;
        };

        (function() {
            var fileUniqueId = 0;
            VPLUtil.getUniqueId = function() {
                return fileUniqueId++;
            };
        })();
        (function() {
            var regExt = /\.([^.]*)$/;
            var regImg = /^(gif|jpg|jpeg|png|ico)$/i;
            var regBin = /^(zip|jar|pdf|tar|bin|7z|arj|deb|gzip|rar|rpm|dat|db|dll|rtf|doc|docx|odt|exe|com)$/i;
            var regBlk = /^blockly[0123]?$/;
            VPLUtil.fileExtension = function(fileName) {
                var res = regExt.exec(fileName);
                return res !== null ? res[1] : '';
            };
            VPLUtil.isImage = function(fileName) {
                return regImg.test(VPLUtil.fileExtension(fileName));
            };
            VPLUtil.isBinary = function(fileName) {
                return VPLUtil.isImage(fileName) || regBin.test(VPLUtil.fileExtension(fileName));
            };
            VPLUtil.isBlockly = function(fileName) {
                return regBlk.test(VPLUtil.fileExtension(fileName));
            };
            var regInvalidFileName = /[\cA-\cZ]|[:-@]|[{-~]|\\|\[|\]|[/^`´]|^-|^ | $|\.\./;
            VPLUtil.validFileName = function(fileName) {
                if (fileName.length < 1) {
                    return false;
                }
                if (fileName.length > 128) {
                    return false;
                }
                return !(regInvalidFileName.test(fileName));
            };
        })();
        VPLUtil.getCurrentTime = function() {
            return parseInt((new Date()).valueOf() / 1000);
        };
        VPLUtil.encodeBinary = function(name, data) {
            if (!VPLUtil.isBinary(name)) {
                return btoa(unescape(encodeURIComponent(data)));
            }
            return btoa(VPLUtil.ArrayBuffer2String(data));
        };

        VPLUtil.decodeBinary = function(name, data) {
            var decoded = atob(data);
            if (!VPLUtil.isBinary(name)) {
                return decodeURIComponent(escape(decoded));
            }
            return VPLUtil.String2ArrayBuffer(decoded);
        };

        VPLUtil.validPath = function(path) {
            if (path.length > 256) {
                return false;
            }
            var dirs = path.split("/");
            for (var i = 0; i < dirs.length; i++) {
                if (!VPLUtil.validFileName(dirs[i])) {
                    return false;
                }
            }
            return true;
        };
        VPLUtil.getFileName = function(path) {
            var dirs = path.split("/");
            return dirs[dirs.length - 1];
        };
        VPLUtil.dataFromURLData = function(data) {
            return data.substr(data.indexOf(',') + 1);
        };
        VPLUtil.readZipFile = function(data, save, progressBar, end) {
            if (!end) {
                end = VPLUtil.doNothing;
            }
            if (typeof JUnzip == 'undefined') {
                VPLUtil.loadScript(['../editor/zip/inflate.js',
                    '../editor/zip/unzip.js']
                , function() {
                    VPLUtil.readZipFile(data, save, progressBar, end);
                });
                return;
            }
            var ab = VPLUtil.ArrayBuffer2String(data);
            var unzipper = new JUnzip(ab);
            if (!unzipper.isZipFile()) {
                VPLUtil.log('Not a ZIP file');
                end();
                return;
            }
            unzipper.readEntries();
            var out = unzipper.entries.length;
            /**
             * Process each entry in the zip file.
             * Recursive process.
             * @param {int} i Entry to process.
             */
            function process(i) {
                if (i >= out || progressBar.isClosed()) {
                    end();
                    return;
                }
                var entry = unzipper.entries[i];
                var fileName = entry.fileName;
                var data;
                // Is directory entry then skip.
                if (fileName.match(/\/$/)) {
                    process(i + 1);
                } else {
                    progressBar.processFile(fileName);
                    var uncompressed = '';
                    if (entry.compressionMethod === 0) {
                        // Plain file.
                        uncompressed = entry.data;
                    } else if (entry.compressionMethod === 8) {
                        uncompressed = JSInflate.inflate(entry.data);
                    }
                    if (VPLUtil.isBinary(fileName)) {
                        // If binary use as arrayBuffer.
                        save({name: fileName, contents: btoa(uncompressed), encoding: 1});
                        // TODO Show message when error.
                        process(i + 1);
                    } else {
                        data = VPLUtil.String2ArrayBuffer(uncompressed);
                        var blob = new Blob([data], {
                            type: 'text/plain'
                        });
                        var fr = new FileReader();
                        fr.onload = function(e) {
                            save({name: fileName, contents: e.target.result, encoding: 0});
                            process(i + 1);
                        };
                        fr.onerror = function(e) {
                            VPLUtil.log(e);
                            i = out;
                            process(i + 1);
                            // TODO Show message when error.
                        };
                        fr.readAsText(blob);
                    }
                }
            }
            process(0);
        };

        VPLUtil.readSelectedFiles = function(filesToRead, save, end) {
            // Process all File objects.
            var pb = new VPLUtil.progressBar('import', 'import');
            var errorsMessages = '';
            if (!end) {
                end = VPLUtil.doNothing;
            }
            pb.processFile = function(name) {
                pb.setLabel(name);
            };
            /**
             * Read each file in filesToReas
             * Recursive process.
             * @param {int} sec secuencial file to read
             */
            function readSecuencial(sec) {
                if (sec >= filesToRead.length || pb.isClosed()) {
                    end();
                    pb.close();
                    if (errorsMessages > '') {
                        VPLUtil.showErrorMessage(errorsMessages);
                    }
                    return;
                }
                var f = filesToRead[sec];
                pb.processFile(f.name);
                var binary = VPLUtil.isBinary(f.name);
                var reader = new FileReader();
                var ext = VPLUtil.fileExtension(f.name).toLowerCase();
                reader.onload = function(e) {
                    if (binary) {
                        if (ext == 'zip') {
                            try {
                                VPLUtil.readZipFile(e.target.result, save, pb, function() {
                                                                                  readSecuencial(sec + 1);
                                                                               });
                                return;
                            } catch (ex) {
                                VPLUtil.showErrorMessage(ex + " : " + f.name);
                            }
                        } else {
                            var data = VPLUtil.dataFromURLData(e.target.result);
                            save({name: f.name, contents: data, encoding: 1});
                        }
                    } else {
                        save({name: f.name, contents: e.target.result, encoding: 0});
                    }
                    readSecuencial(sec + 1);
                };
                reader.onerror = function(e) {
                    errorsMessages += "Error \"" + e.target.error + "\" reading " + f.name + "\n";
                    readSecuencial(sec + 1);
                };
                if (binary) {
                    if (ext == 'zip') {
                        reader.readAsArrayBuffer(f);
                    } else {
                        reader.readAsDataURL(f);
                    }
                } else {
                    reader.readAsText(f);
                }
            }
            readSecuencial(0);
        };
        (function() {
            var MIME = {
                'gif': 'image/gif',
                'jpg': 'image/jpeg',
                'jpeg': 'image/jpeg',
                'png': 'image/png',
                'ico': 'image/vnd.microsoft.icon',
                'pdf': 'application/pdf'
            };
            VPLUtil.getMIME = function(fileName) {
                var ext = VPLUtil.fileExtension(fileName);
                if (ext in MIME) {
                    return MIME[ext];
                }
                return 'application/octet-stream';
            };
            VPLUtil.getTimeLeft = function(timeLeft) {
                var res = '';
                var minute = 60;
                var hour = 60 * minute;
                var day = 24 * hour;
                if (timeLeft < 0) {
                    res += '-';
                    timeLeft = -timeLeft;
                }
                var timePending = timeLeft;
                var days = parseInt(timePending / day);
                timePending -= days * day;
                if (days !== 0) {
                    res += days + 'T';
                }
                var hours = parseInt(timePending / hour);
                timePending -= hours * hour;
                var minutes = parseInt(timePending / minute);
                timePending -= minutes * minute;
                var seconds = parseInt(timePending);
                res += ('00' + hours).substr(-2) + ':';
                res += ('00' + minutes).substr(-2);
                if (timeLeft < hour) {
                    res += ':' + ('00' + seconds).substr(-2);
                }
                return res;
            };
        })();
        (function() {
            var maplang = {
                'abap': 'abap',
                'abc': 'abc',
                'ada': 'ada', 'ads': 'ada', 'adb': 'ada',
                'as': 'actionscript', 'as3': 'actionscript',
                'asm': 'assembly_x86',
                'bash': 'sh',
                'bat': 'batchfile',
                'c': 'c_cpp', 'C': 'c_cpp', 'cc': 'c_cpp', 'cpp': 'c_cpp', 'c++': 'c_cpp',
                'hxx': 'c_cpp', 'h': 'c_cpp', 'H': 'c_cpp',
                'cases': 'cases',
                'cbl': 'cobol', 'cob': 'cobol',
                'coffee': 'coffee',
                'clj': 'clojure',
                'cs': 'csharp',
                'css': 'css',
                'd': 'd',
                'dart': 'dart',
                'e': 'eiffel',
                'erl': 'erlang', 'hrl': 'erlang',
                'f': 'fortran', 'f77': 'fortran', 'f90': 'fortran', 'for': 'fortran',
                'go': 'golang',
                'groovy': 'groovy',
                'gv': 'dot',
                'hs': 'haskell',
                'htm': 'html', 'html': 'html',
                'hx': 'haxe',
                'java': 'java',
                'jl': 'julia',
                'js': 'javascript',
                'json': 'json',
                'jsp': 'jsp',
                'jsx': 'jsx',
                'kt': 'kotlin', 'kts': 'kotlin',
                'm': 'matlab',
                'md': 'markdown',
                'less': 'less',
                'lisp': 'lisp', 'lsp': 'lisp',
                'lua': 'lua',
                'pas': 'pascal', 'p': 'pascal',
                'perl': 'perl', 'prl': 'perl',
                'php': 'php',
                'pro': 'prolog', 'pl': 'prolog',
                'py': 'python',
                'R': 'r', 'r': 'r',
                'rb': 'ruby', 'ruby': 'ruby',
                's': 'assembly_x86',
                'sass': 'sass',
                'scala': 'scala',
                'scm': 'scheme',
                'scss': 'scss',
                'sh': 'sh',
                'swift': 'swift',
                'sql': 'sql',
                'svg': 'svg',
                'tex': 'tex',
                'tcl': 'tcl',
                'ts': 'typescript',
                'twig': 'twig',
                'vbs': 'vbscript',
                'v': 'verilog', 'vh': 'verilog',
                'vhd': 'vhdl', 'vhdl': 'vhdl',
                'xml': 'xml',
                'yaml': 'yaml'
            };
            VPLUtil.langType = function(ext) {
                if (ext in maplang) {
                    return maplang[ext];
                }
                return 'plain_text';
            };
        })();
        (function() {
            var i18n = {};
            var strreg = /\{\\*\$a\\*}/g;
            VPLUtil.str = function(key, parm) {
                if (!i18n[key]) {
                    return '{' + key + '}';
                }
                if (typeof parm != 'undefined') {
                    return i18n[key].replace(strreg, parm);
                } else {
                    return i18n[key];
                }
            };
            VPLUtil.setStr = function(newi18n) {
                for (var key in newi18n) {
                    if (newi18n.hasOwnProperty(key)) {
                        i18n[key] = newi18n[key];
                    }
                }
                VPLUtil.dialogbaseOptions = {
                    minWidth: 200,
                    autoOpen: false,
                    width: 'auto',
                    closeText: VPLUtil.str('cancel'),
                    modal: true,
                    dialogClass: 'vpl_ide vpl_ide_dialog'
                };
            };
            VPLUtil.setStr(window.VPLi18n);
        })();
        (function() {
            var delayedActions = {};
            var afterAllActions = {};
            var shortTimeout = 20;
            var longTimeout = 100;
            var numberDelayed = 0;
            var internalDelay = function(timeout, id, func, arg1, arg2) {
                if (typeof delayedActions[id] != 'undefined') {
                    clearTimeout(delayedActions[id]);
                    numberDelayed--;
                }
                numberDelayed++;
                delayedActions[id] = setTimeout(function() {
                    numberDelayed--;
                    func(arg1, arg2);
                    delete delayedActions[id];
                }, timeout);
            };
            VPLUtil.delay = function(id, func, arg1, arg2) {
                internalDelay(shortTimeout, id, func, arg1, arg2);
            };
            VPLUtil.longDelay = function(id, func, arg1, arg2) {
                internalDelay(longTimeout, id, func, arg1, arg2);
            };
            var setAfterTimeout = function(id, func, arg1, arg2) {
                if (typeof afterAllActions[id] != 'undefined') {
                    clearTimeout(afterAllActions[id]);
                }
                afterAllActions[id] = setTimeout(function() {
                        if (numberDelayed > 0) {
                            afterAllActions[id] = setAfterTimeout(id, func, arg1, arg2);
                        } else {
                             func(arg1, arg2);
                             delete afterAllActions[id];
                        }
                    }, longTimeout);
            };
            VPLUtil.afterAll = function(id, func, arg1, arg2) {
                setAfterTimeout(id, func, arg1, arg2);
            };
        })();
        VPLUtil.iconModified = function() {
            var html = '<span title="' + VPLUtil.str('modified') + '" class="vpl_ide_charicon">';
            html += '<i class="fa fa-star"></i>' + '</span> ';
            return html;
        };
        VPLUtil.iconDelete = function() {
            var html = ' <span title="' + VPLUtil.str('delete') + '" class="vpl_ide_charicon vpl_ide_delicon">';
            html += '<i class="fa fa-trash"></i>' + '</span> ';
            return html;
        };
        VPLUtil.iconClose = function() {
            var html = ' <span title="' + VPLUtil.str('closebuttontitle');
            html += '" class="vpl_ide_charicon vpl_ide_closeicon">' + '<i class="fa fa-remove"></i>' + '</span> ';
            return html;
        };
        VPLUtil.iconRequired = function() {
            var html = ' <span title="' + VPLUtil.str('required') + '" class="vpl_ide_charicon">';
            html += '<i class="fa fa-shield"></i>' + '</span> ';
            return html;
        };
        VPLUtil.iconFolder = function() {
            return '<i class="fa fa-folder-open-o"></i>';
        };
        (function() {
            var menuIcons = {
                'filelist': 'folder-open-o',
                'filelistclose': 'folder-o',
                'new': 'file-code-o',
                'rename': 'pencil',
                'delete': 'trash',
                'multidelete': 'trash|list',
                'close': 'remove',
                'comments': 'commenting',
                'import': 'upload',
                'print': 'print',
                'edit': 'edit',
                'undo': 'undo',
                'redo': 'repeat',
                'select_all': 'location-arrow',
                'find': 'search',
                'find_replace': 'exchange',
                'next': 'search-plus',
                'resetfiles': 'refresh',
                'download': 'download',
                'fullscreen': 'expand',
                'regularscreen': 'compress',
                'save': 'save',
                'shortcuts': 'flash',
                'sort': 'list-ol',
                'run': 'rocket',
                'running': 'rocket fa-spin',
                'debug': 'bug',
                'evaluate': 'check-square-o',
                'console': 'terminal',
                'about': 'question',
                'info': 'info-circle',
                'alert': 'warning',
                'trash': 'trash',
                'retrieve': 'download',
                'spinner': 'refresh fa-spin',
                'keyboard': 'keyboard-o',
                'clipboard': 'clipboard',
                'timeleft': 'clock-o',
                'copy': 'copy',
                'paste': 'paste',
                'more': 'plus-square',
                'less': 'minus-square',
                'resize': 'arrows-alt',
                'graphic': 'picture-o',
                'send': 'send',
                'theme':  'paint-brush',
                'user': 'user',
                'fontsize': 'text-height',
                'close-rightpanel': 'caret-square-o-right',
                'open-rightpanel': 'caret-square-o-left'
            };
            VPLUtil.genIcon = function(icon, size) {
                if (!menuIcons[icon]) {
                    return '';
                }
                var classes = 'fa fa-';
                if (!size) {
                    classes += 'lg';
                } else {
                    classes += size;
                }
                var icons = menuIcons[icon].split('|');
                var ret = '';
                for (var i = 0; i < icons.length; i++) {
                    ret += "<i class='" + classes + ' fa-' + icons[i] + "'></i>";
                }
                return ret;
            };
        })();
        // UI operations.
        VPLUtil.setTitleBar = function(dialog, type, icon, buttons, handler) {
            var title = $(dialog).parent().find("span.ui-dialog-title");
            /**
             * Generate HTML for a button with icon
             * @param {string} e name of botton.
             * @returns {string} Html tag <a> as a button.
             */
            function genButton(e) {
                var html = "<a id='vpl_" + type + "_" + e + "' href='#' title='" + VPLUtil.str(e) + "'>";
                html += VPLUtil.genIcon(e, 'fw') + "</a>";
                return html;
            }
            var html = VPLUtil.genIcon(icon);
            html += " <span class='" + type + "-title-buttons'></span>";
            html += "<span class='" + type + "-title-text'></span>";
            title.html(html);
            var titleButtons = title.find("span." + type + "-title-buttons");
            var titleText = title.find("span." + type + "-title-text");
            html = "";
            for (var i = 0; i < buttons.length; i++) {
                html += genButton(buttons[i]);
            }
            titleButtons.html(html);
            for (var ih = 0; ih < handler.length; ih++) {
                var button = title.find('#vpl_' + type + '_' + buttons[ih]);
                button.button().click(handler[ih]);
                button.css('padding', '1px 3px');
            }
            titleButtons.on('focus', '*', function() {
                                              $(this).blur();
                                          });
            return titleText;
        };
        VPLUtil.setDialogTitleIcon = function(dialog, icon) {
            var title = $(dialog).parent().find("span.ui-dialog-title");
            title.html(VPLUtil.genIcon(icon) + ' ' + title.html());
        };
        VPLUtil.progressBar = function(title, message, onUserClose) {
            var labelHTML = '<span class="vpl_ide_progressbarlabel"></span>';
            var sppiner = '<div class="vpl_ide_progressbaricon">' + VPLUtil.genIcon('spinner') + '</div>';
            var pbHTML = ' <div class="vpl_ide_progressbar">' + sppiner + labelHTML + '</div>';
            var HTML = '<div class="vpl_ide_dialog" style="display:none;">' + pbHTML + '</div>';
            var dialog = $(HTML);
            $('body').append(dialog);
            var progressbar = dialog.find('.vpl_ide_progressbar');
            var label = progressbar.find('.vpl_ide_progressbarlabel');
            dialog.dialog({
                'title': VPLUtil.str(title),
                resizable: false,
                autoOpen: false,
                width: 200,
                height: 20,
                minHeight: 20,
                modal: true,
                dialogClass: 'vpl_ide vpl_ide_dialog',
                close: function(event) {
                    if (dialog) {
                        if (onUserClose && event.originalEvent) {
                            onUserClose();
                        }
                        onUserClose = false;
                    }
                }
            });
            this.setLabel = function(t, icon) {
                if (dialog) {
                    label.text(t);
                    if (icon) {
                        label.html(VPLUtil.genIcon(icon) + ' ' + label.html());
                    }
                }
            };
            this.close = function() {
                if (dialog) {
                    dialog.dialog('destroy');
                    $(dialog).remove();
                    dialog = false;
                }
            };
            this.isClosed = function() {
                return dialog === false;
            };
            var titleTag = dialog.siblings().find('.ui-dialog-title');
            titleTag.html(VPLUtil.genIcon(title) + ' ' + titleTag.html());
            this.setLabel(VPLUtil.str(message));
            dialog.dialog('open');
            dialog.dialog('option', 'height', 'auto');
        };
        /**
         * Shows a dialog with a message.
         * @param {string} message
         * @param {Object} initialoptions icon, title, actions handler (ok, yes, no, close)
         * @returns {JQuery} JQueryUI Dialog object already open
         */
        VPLUtil.showMessage = function(message, initialoptions) {
            var options = $.extend({}, VPLUtil.dialogbaseOptions, initialoptions);
            var messageDialog = $('<div class="vpl_ide_dialog" style="display:none"></div>');
            var icon = '';
            var contents = ' <span class="dmessage">' + message.replace(/\n/g, '<br>') + '</span>';
            messageDialog.html(contents);
            if (typeof options.icon == 'undefined') {
                icon = 'info';
            } else {
                icon = options.icon;
                delete options.icon;
            }
            if (!options.title) {
                options.title = VPLUtil.str('warning');
            }
            $('body').append(messageDialog);
            var messageButtons = {};
            if (typeof initialoptions.ok == 'function') {
                messageButtons[VPLUtil.str('ok')] = function() {
                    $(this).dialog('close');
                    initialoptions.ok();
                };
                messageButtons[VPLUtil.str('cancel')] = function() {
                    $(this).dialog('close');
                };
                delete options.ok;
            } else if (typeof initialoptions.yes == 'function') {
                messageButtons[VPLUtil.str('yes')] = function() {
                    $(this).dialog('close');
                    initialoptions.yes();
                };
                messageButtons[VPLUtil.str('no')] = function() {
                    $(this).dialog('close');
                };
                delete options.yes;
            } else {
                messageButtons[VPLUtil.str('close')] = function() {
                    $(this).dialog('close');
                };
            }
            if (options.next) {
                messageButtons[VPLUtil.str('next')] = function() {
                    $(this).dialog('close');
                    initialoptions.next();
                };
            }
            options.close = function() {
                    $(this).remove();
                    if (initialoptions.close) {
                        initialoptions.close();
                    }
            };
            options.buttons = messageButtons;

            messageDialog.dialog(options);
            var titleTag = messageDialog.siblings().find('.ui-dialog-title');
            titleTag.html(VPLUtil.genIcon(icon) + ' ' + titleTag.html());

            messageDialog.setMessage = function(men) {
                $(messageDialog).find('.dmessage').html(men.replace(/\n/g, '<br>'));
            };

            messageDialog.dialog('open');
            return messageDialog;
        };
        VPLUtil.showErrorMessage = function(message, options) {
            var currentOptions = $.extend({}, VPLUtil.dialogbaseOptions, {
                title: VPLUtil.str('error'),
                icon: 'alert'
            });
            if (options) {
                currentOptions = $.extend(currentOptions, options);
            }
            return VPLUtil.showMessage(message, currentOptions);
        };
        /**
         * Request an action to de server: save, run, debug, evaluate, update, getresult, etc.
         * @param {string} action Name of action to request.
         * @param {string} title The title that shows the progress dialog
         * @param {object} data Data to send to the server
         * @param {string} URL URL to the server entry point, lacking action
         * @param {boolean} noDialog If true then no dialog is shown
         * @returns {deferred} Defferred object
         */
        VPLUtil.requestAction = function(action, title, data, URL, noDialog) {
            var deferred = $.Deferred();
            var request = null;
            var xhr = false;
            var apb = false;
            if (!noDialog) {
                if (title === '') {
                    title = 'connecting';
                }
                apb = new VPLUtil.progressBar(action, title, function() {
                    if (request.readyState != 4) {
                        if (xhr && xhr.abort) {
                            xhr.abort();
                        }
                    }
                });
            }
            request = $.ajax({
                beforeSend: function(jqXHR) {
                    xhr = jqXHR;
                    return true;
                },
                async: true,
                type: "POST",
                url: URL + action,
                'data': JSON.stringify(data),
                contentType: "application/json; charset=utf-8",
                dataType: "json"
            }).always(function() {
                if (!noDialog) {
                    apb.close();
                }
            }).done(function(response) {
                if (!response.success) {
                    deferred.reject(response.error);
                } else {
                    deferred.resolve(response.response);
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                var message = VPLUtil.str('connection_fail') + ': ' + textStatus;
                if (debugMode && errorThrown.message != undefined) {
                    message += ': ' + errorThrown.message;
                }
                VPLUtil.log(message);
                deferred.reject(message);
            });
            return deferred;
        };
        VPLUtil.supportWebSocket = function() {
            if ("WebSocket" in window) {
                return true;
            }
            return false;
        };
        VPLUtil.isAndroid = function() {
            return window.navigator.userAgent.indexOf('Android') > -1;
        };
        VPLUtil.isFirefox = function() {
            return window.navigator.userAgent.indexOf('Firefox') > -1;
        };
        VPLUtil.isMac = function() {
            return window.navigator.userAgent.indexOf('Mac') > -1;
        };
        VPLUtil.clickServer = function(e) {
            var w = 550;
            var h = 450;
            var left = (screen.width / 2) - (w / 2);
            var top = (screen.height / 2) - (h / 2);
            try {
                var features = 'toolbar=no, location=no, directories=no, status=no, menubar=no';
                features += ', resizable=yes, scrollbars=yes, copyhistory=no, width=' + w;
                features += ', height=' + h + ', top=' + top + ', left=' + left;
                var win = window.open($(this).attr('href'), '_blank', features);
                if (!win) {
                    return true;
                }
            } catch (ex) {
                VPLUtil.log(ex);
                return true;
            }
            e.preventDefault();
            $(this).parent().hide();
            return false;
        };

        VPLUtil.acceptCertificates = function(servers, getLastAction) {
            if (servers.length > 0) {
                // Generate links dialog.
                var html = VPLUtil.str('acceptcertificatesnote');
                html += '<ol>';
                var i;
                for (i in servers) {
                    if (servers.hasOwnProperty(i)) {
                        var n = 1 + i;
                        html += '<li><a href="' + servers[i] + '" target="_blank">Server ';
                        html += n + '</a></li>';
                    }
                }
                html += '</ol>';
                var m = VPLUtil.showMessage(html, {
                    ok: function() {
                        var action = getLastAction();
                        if (action) {
                            action();
                        }
                    },
                    icon: 'unlocked',
                    title: VPLUtil.str('acceptcertificates')
                });
                $(m).find('a').on('click keypress', VPLUtil.clickServer);
            } else {
                VPLUtil.log('servers.length == 0');
                VPLUtil.showErrorMessage(VPLUtil.str('connection_fail'));
            }
        };
        VPLUtil.monitorRunning = VPLUtil.returnFalse;
        (function() {
            var lastProccessID = -1;
            VPLUtil.setProcessId = function(id) {
                lastProccessID = id;
            };
            VPLUtil.getProcessId = function() {
                return lastProccessID;
            };
        })();
        VPLUtil.webSocketMonitor = function(coninfo, title, running, externalActions) {
            VPLUtil.setProtocol(coninfo);
            VPLUtil.setProcessId(coninfo.processid);
            var ws = null;
            var pb = null;
            var deferred = $.Deferred();
            var defail = function(m) {
                deferred.reject(m);
            };
            var delegated = false;
            var messageActions = {
                'message': function(content) {
                    var parsed = /^([^:]*):?([^]*)/.exec(content);
                    var state = parsed[1];
                    var detail = parsed[2];
                    if (state == 'running') {
                        state = running;
                    }
                    var text = VPLUtil.str(state);
                    if (detail > '') {
                        text += ': ' + detail;
                    }
                    if (pb !== null && !pb.isClosed()) {
                        pb.setLabel(text);
                    } else if (externalActions.getConsole && externalActions.getConsole().isOpen()) {
                        externalActions.getConsole().setMessage(text);
                    } else {
                        VPLUtil.log('Error: no dialogo. Message not shown: ' + text);
                    }
                },
                'compilation': function(content) {
                    if (externalActions.setResult) {
                        externalActions.setResult({
                            'compilation': content,
                        }, false);
                    }
                },
                'retrieve': function() {
                    var data = {"processid": VPLUtil.getProcessId()};
                    pb.close();
                    delegated = true;
                    VPLUtil.requestAction('retrieve', '', data, externalActions.ajaxurl)
                    .done(
                        function(response) {
                            deferred.resolve();
                            if (externalActions.setResult) {
                                externalActions.setResult(response, true);
                            }
                        }
                   ).fail(defail);
                },
                'run': function(content) {
                    pb.close();
                    externalActions.run(content, coninfo, ws);
                },
                'close': function() {
                    VPLUtil.log('ws close message from jail');
                    ws.close();
                    var data = {"processid": VPLUtil.getProcessId()};
                    VPLUtil.requestAction('cancel', '', data, externalActions.ajaxurl, true);
                }
            };
            try {
                if (VPLUtil.supportWebSocket()) {
                    ws = new WebSocket(coninfo.monitorURL);
                } else {
                    VPLUtil.log('ws not available');
                    deferred.reject(VPLUtil.str('browserupdate'));
                    return deferred;
                }
            } catch (e) {
                VPLUtil.log('ws new say ' + e);
                deferred.reject(e.message);
                return deferred;
            }
            pb = new VPLUtil.progressBar(title, 'connecting', function() {
                deferred.reject('Stopped by user');
                ws.close();
            });
            ws.notOpen = true;
            ws.onopen = function() {
                ws.notOpen = false;
                pb.setLabel(VPLUtil.str('connected'));
                if (externalActions.open) {
                    externalActions.open();
                }
            };
            ws.onerror = function(event) {
                VPLUtil.log('ws error ' + event);
                pb.close();
                if (coninfo.secure && ws.notOpen) {
                    VPLUtil.requestAction('getjails', 'retrieve', {}, externalActions.ajaxurl)
                    .done(function(response) {
                        VPLUtil.acceptCertificates(response.servers, function() {
                            return externalActions.getLastAction();
                        });
                    })
                    .fail(defail);
                } else {
                    deferred.reject(VPLUtil.str('connection_fail'));
                }
                if (externalActions.close) {
                    VPLUtil.delay('externalActions.close', externalActions.close);
                }
            };
            ws.onclose = function() {
                if (externalActions.getConsole) {
                    externalActions.getConsole().disconnect();
                }
                if (!ws.notOpen) {
                    pb.close();
                    if (!delegated && deferred.state() != 'rejected') {
                        deferred.resolve();
                    }
                }
                if (externalActions.close) {
                    externalActions.close();
                }
            };

            ws.onmessage = function(event) {
                var message = /^([^:]+):([^]*)/.exec(event.data);
                if (message !== null) {
                    var action = message[1];
                    var content = message[2];
                    if (messageActions[action]) {
                        messageActions[action](content);
                    }
                } else {
                    pb.setLabel(VPLUtil.str('error') + ': ' + event.data);
                }
            };
            VPLUtil.monitorRunning = function() {
                return ws !== null && ws.readyState != WebSocket.CLOSED;
            };
            return deferred;
        };

        /**
         * Run a command in a execution server with input/output using a WebSocket
         * @param {string} URL to VPL editor services in Moodle server
         * @param {string} command Command to run in execution server
         * @param {array.<{name: string, contents: string, encoding: number}>} files
         *         array of objects name, contents and encoding 0 => UTF-8, 1 => Base64
         * @returns {object} deferred.
         *         Use done() to set handler to receive the WebSocket. Use fail to set error handler.
         */
        VPLUtil.directRun = function(URL, command, files) {
            var deferred = $.Deferred();
            $.ajax({
                async: true,
                type: "POST",
                url: URL + 'directrun',
                'data': JSON.stringify({"command": command, "files": files}),
                contentType: "application/json; charset=utf-8",
                dataType: "json"
            }).done(function(result) {
                if (!result.success) {
                    deferred.reject(result.error);
                } else {
                    var response = result.response;
                    VPLUtil.setProtocol(response);
                    var ws = new WebSocket(response.executionURL);
                    log.debug('Conecting with:' + response.executionURL);
                    deferred.resolve({processid: response.processid, homepath: response.homepath, connection: ws});
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                var message = 'Connection fail' + ': ' + textStatus;
                if (errorThrown.message != undefined) {
                    message += ': ' + errorThrown.message;
                }
                log.debug(message);
                deferred.reject(message);
            });
            return deferred;
        };
        /**
         * Function to experiment with Direct run.
         * Limits: one data send and 10 messages received and 10 minutes connected
         * @param {string} URL to server
         * @param {string} command Command to prepare direct run. Execution of command must generate vpl_execution
         * @param {object} data to send to server
         */
         VPLUtil.directRunTest = function(URL, command, data) {
            var files = [{name: 'a.c', contents: 'int main(){return 0;}', encoding: 0},
                         {name: 'b.c', contents: 'int f(){return 1;}', encoding: 0}];
            VPLUtil.directRun(URL, command, files)
                .done(function(result) {
                    var mcount = 0;
                    result.connection.onopen = function() {
                        log.debug("Ws open " + result.homepath + " processid " + result.processid);
                        if (data != undefined) {
                            result.connection.send(data);
                        }
                        setTimeout(function() { //  Close test if open for more than 10 minutes.
                            result.connection.close();
                        }, 60 * 10 * 1000);
                    };
                    result.connection.onmessage = function(event) {
                        log.debug("WS Message (" + ++mcount + "): " + event.data);
                        if (mcount >= 10) {
                            result.connection.close();
                        }
                    };
                    result.connection.onerror = function(event) {
                        log.debug("WS error: " + event);
                    };
                    result.connection.onclose = function(event) {
                        log.debug("WS close: " + event.code + " " + event.reason);
                    };
                })
                .fail(function(message) {
                    log.debug("Direct run fail. URL: " + URL + " command: " + command + " message: " + message);
                });
        };
        VPLUtil.processResult = function(text, filenames, sh, noFormat, folding) {
            if (typeof text == 'undefined' || text.replace(/^\s+$/gm, '') == '') {
                return '';
            }
            /**
             * Adds escape to the text
             * @param {string} t text to escape
             * @returns {string} result
             */
            function escReg(t) {
                return t.replace(/[-[\]{}()*+?.,\\^$|#\s]/, "\\$&");
            }
            var regtitgra = /\([-]?[\d]+[.]?[\d]*\)\s*$/;
            var regtit = /^-.*/;
            var regcas = /^\s*>/;
            // TODO adds error? use first anotation for icon.
            var regError = new RegExp('\\[err\\]|error|' + escReg(VPLUtil.str('error')), 'i');
            var regWarning = new RegExp('\\[warn\\]|warning|note|' + escReg(VPLUtil.str('warning')), 'i');
            var regInformation = new RegExp('\\[info\\]|information', 'i');
            var state = '';
            var html = '';
            var comment = '';
            var case_ = '';
            var lines = text.split(/\r\n|\n|\r/);
            var regFiles = [];
            var lastAnotation = false;
            var lastAnotationFile = false;
            var afterTitle = false;
            /**
             * Generate attribute href for the editor in sh
             * @param {int} i Index of sh
             * @return {string} href
             */
            function getHref(i) {
                if (typeof sh[i].getTagId === 'undefined') {
                    return 'href="#" ';
                } else {
                    return 'href="#' + sh[i].getTagId() + '" ';
                }
            }
            (function() {
                for (var i = 0; i < filenames.length; i++) {
                    var regf = escReg(filenames[i]);
                    // Filename:N, filename(N), filename N, filename line N, filename on line N.
                    // N=#|#:#|#,#.
                    var reg = "(^| |/)" + regf + "( on line | line |:|\\()(\\d+)(:|,)?(\\d+)?(\\))?";
                    regFiles[i] = new RegExp(reg, 'm');
                }
            })();
            /**
             * Generate the file links in the comments to point to the files
             * @param {string} line Line to modify
             * @param {string} rawline Text to include in annotation
             * @returns {string} The line modified
             */
            function genFileLinks(line, rawline) {
                var used = false;
                for (var i = 0; i < regFiles.length; i++) {
                    var reg = regFiles[i];
                    var match;
                    while ((match = reg.exec(line)) !== null) {
                        var anot = sh[i].getAnnotations();
                        // Annotation format {row:,column:,raw:,type:error,warning,info;text} .
                        lastAnotationFile = i;
                        used = true;
                        var type;
                        if (line.search(regError) > -1) {
                            type = 'error';
                        } else if (line.search(regWarning) > -1) {
                            type = 'warning';
                        } else if (line.search(regInformation) > -1) {
                            type = 'info';
                        } else {
                            type = 'error';
                        }
                        lastAnotation = {
                            'row': (match[3] - 1),
                            'column': match[5],
                            'type': type,
                            'text': rawline,
                        };
                        anot.push(lastAnotation);
                        var fileName = filenames[i];
                        var href = getHref(i);
                        var lt = VPLUtil.sanitizeText(fileName);
                        var data = 'data-file="' + fileName + '" data-line="' + match[3] + '"';
                        line = line.replace(reg, '$1<a ' + href + ' class="vpl_fl" ' + data + '>' + lt + '$2$3$4$5$6</a>');
                        sh[i].setAnnotations(anot);
                    }
                }
                if (!used && lastAnotation) {
                    if (rawline !== '') {
                        lastAnotation.text += "\n" + rawline;
                        sh[lastAnotationFile].setAnnotations(sh[lastAnotationFile].getAnnotations());
                    } else {
                        lastAnotation = false;
                    }
                }
                return line;
            }
            /**
             * Generates HTML for title line
             * @param {string} line The line to process
             * @returns {string} Line in HTML format
             */
            function getTitle(line) {
                lastAnotation = false;
                line = line.substr(1);
                var end = regtitgra.exec(line);
                if (end !== null) {
                    line = line.substr(0, line.length - end[0].length);
                }
                var html = '';
                if (folding) {
                    html += '<a href="javascript:void(0)" onclick="VPLUtil.showHideDiv(this)">[+]</a>';
                }
                html += '<b class="ui-widget-header ui-corner-all">' + VPLUtil.sanitizeText(line) + '</b><br>';
                html = genFileLinks(html, line);
                return html;
            }
            /**
             * Returns comment that has been saved
             * @returns {string}
             */
            function getComment() {
                lastAnotation = false;
                var ret = comment;
                comment = '';
                return ret;
            }
            /**
             * Adds a new comment in HTML
             * @param {string} rawline  Comment to add
             */
            function addComment(rawline) {
                var line = VPLUtil.sanitizeText(rawline);
                comment += genFileLinks(line, rawline) + '<br>';
            }
            /**
             * Adds a new case
             * @param {*} rawline Text to add
             */
            function addCase(rawline) {
                var line = VPLUtil.sanitizeText(rawline);
                case_ += genFileLinks(line, rawline) + "\n";
            }
            /**
             * Returns cases saved in HTML
             * @returns {string}
             */
            function getCase() {
                lastAnotation = false;
                var ret = case_;
                case_ = '';
                return '<pre><i>' + ret + '</i></pre>';
            }

            for (var i = 0; i < lines.length; i++) {
                var line = lines[i];
                if (noFormat) {
                    html += genFileLinks(VPLUtil.sanitizeText(line), line) + "\n";
                    continue;
                }
                var match = regcas.exec(line);
                var regcasv = regcas.test(line);
                if ((match !== null) != regcasv) {
                    VPLUtil.log('error');
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
                    if (afterTitle) {
                        html += '</div>';
                    }
                    html += getTitle(line);
                    html += folding ? '<div style="display:none">' : '<div>';
                    afterTitle = true;
                    state = '';
                } else if (regcasv) {
                    if (state == 'comment') {
                        html += getComment();
                    }
                    addCase(line.substr(match[0].length));
                    state = 'case';
                } else {
                    if (state == 'case') {
                        html += getCase();
                    }
                    addComment(line);
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
            if (afterTitle) {
                html += '</div>';
            }
            return html;
        };
        (function() {
            var scriptsLoaded = [];
            VPLUtil.loadScript = function(scripts, end) {
                if (scripts.length == 0) {
                    end();
                    return;
                }
                var scriptURL = scripts[0];
                if (typeof scriptsLoaded[scriptURL] == 'undefined') {
                    scripts.shift();
                    scriptsLoaded[scriptURL] = 1;
                    var script = document.createElement('script');
                    script.type = 'text/javascript';
                    script.src = VPLUtil.options.scriptPath + scriptURL;
                    script.onload = function() {
                        scriptsLoaded[scriptURL] = 2;
                        VPLUtil.loadScript(scripts, end);
                    };
                    document.head.appendChild(script);
                } else if (scriptsLoaded[scriptURL] == 2) {
                    scripts.shift();
                    VPLUtil.loadScript(scripts, end);
                } else {
                    VPLUtil.log('Error loading js ' + scriptURL + ' ' + scriptsLoaded[scriptURL]);
                    setTimeout(function() {
                                  VPLUtil.loadScript(scripts, end);
                               }, 50);
                }
            };
            VPLUtil.isScriptLoading = function(scriptURL) {
                if (typeof scriptsLoaded[scriptURL] == 'undefined') {
                    return false;
                }
                return scriptsLoaded[scriptURL] == 1;
            };
            VPLUtil.isScriptLoaded = function(scriptURL) {
                if (typeof scriptsLoaded[scriptURL] == 'undefined') {
                    return false;
                }
                return scriptsLoaded[scriptURL] == 2;
            };
        })();
        VPLUtil.adjustBlockly = function(work, offx, offy) {
            var blocks = work.getAllBlocks();
            var miy = 20000;
            var may = -20000;
            var mix = 20000;
            var max = -20000;
            for (var i = 0; i < blocks.length; i++) {
                var xy = blocks[i].getRelativeToSurfaceXY();
                miy = Math.min(miy, xy.y);
                may = Math.max(may, xy.y);
                mix = Math.min(mix, xy.x);
                max = Math.max(max, xy.x);
            }
            blocks = work.getTopBlocks();
            for (var j = 0; j < blocks.length; j++) {
                blocks[j].moveBy(offx - mix, offy - miy);
            }
            return may - miy + 100 + offy;
        };
        (function() {
            var files = [];
            var results = [];
            var shs = [];
            var nFileGroupHighlighter = 0;
            /**
             * Constructor for submission highlighter
             * @param {Array} files Files to show highlighted
             * @param {Array} results Output
             */
            function FileGroupHighlighter(files, results) {
                this.files = files.slice();
                this.results = results.slice();
                files = [];
                results = [];
                this.shFiles = [];
                this.shFileNames = [];
                nFileGroupHighlighter++;
                this.highlight();
            }

            FileGroupHighlighter.prototype.highlightBlockly = function(preid) {
                VPLUtil.loadScript(['../editor/blockly/blockly_compressed.js',
                    '../editor/blockly/msg/js/en.js',
                    '../editor/blockly/blocks_compressed.js']
                , function() {
                    var tag = $('#' + preid);
                    var c = tag.html();
                    $('#' + preid + 'load').remove();
                    tag.html('');
                    tag.show();
                    c = $('<div />').html(c).text().replace(/\n/g, "");
                    var xml = Blockly.Xml.textToDom(c);
                    tag.html('').height(300).width(tag.parent().width());
                    var options = {
                        toolbox: '',
                        readOnly: true,
                        media: '../editor/blockly/media/',
                    };
                    var work = Blockly.inject(preid, options);
                    Blockly.Xml.domToWorkspace(xml, work);
                    var hg = VPLUtil.adjustBlockly(work, 10, 10);
                    tag.height(hg);
                    tag.width('100%');
                    Blockly.svgResize(work);
                    Blockly.resizeSvgContents(work);
                    var h = tag.html();
                    work.dispose();
                    tag.html(h);
                });
            };

            FileGroupHighlighter.prototype.highlight = function() {
                var self = this;
                var needAce = false;
                var files = this.files;
                for (let i = 0; i < files.length; i++) {
                    let file = files[i];
                    if (VPLUtil.isBinary(file.fileName) || VPLUtil.isBlockly(file.fileName)) {
                        continue;
                    } else {
                        needAce = true;
                        break;
                    }
                }
                if (needAce && typeof ace === 'undefined') {
                    VPLUtil.loadScript(['../editor/ace9/ace.js'],
                        function() {
                           self.highlight();
                        });
                    return;
                }
                VPLUtil.delay("FFGH." + nFileGroupHighlighter, function() {
                    self.highlightStep(0);
                });
            };

            FileGroupHighlighter.prototype.highlightStep = function(pos) {
                if (pos >= this.files.length) {
                    this.resultStep(0);
                    return;
                }
                let file = this.files[pos];
                let preid = 'code' + file.tagId;
                if (VPLUtil.isBlockly(file.fileName)) {
                    this.highlightBlockly(preid);
                } else {
                    var ext = VPLUtil.fileExtension(file.fileName);
                    var lang = VPLUtil.langType(ext);
                    $('#' + preid).show();
                    $('#' + preid + 'load').remove();
                    var sh = ace.edit(preid);
                    sh.setTheme('ace/theme/' + file.theme);
                    sh.getSession().setMode('ace/mode/' + lang);
                    sh.renderer.setShowGutter(file.showln);
                    sh.setReadOnly(true);
                    sh.setHighlightActiveLine(false);
                    sh.setAutoScrollEditorIntoView(true);
                    sh.setOption('maxLines', file.nl);
                    sh.getAnnotations = function() {
                        return this.getSession().getAnnotations();
                    };
                    sh.setAnnotations = function(a) {
                        return this.getSession().setAnnotations(a);
                    };
                    sh.getTagId = function() {
                        return this.vplTagId;
                    };
                    sh.vplTagId = file.tagId;
                    this.shFiles.push(sh);
                    this.shFileNames.push(file.fileName);
                    shs[file.tagId] = sh;
                }
                var self = this;
                VPLUtil.delay(preid + ".next", function() {
                    self.highlightStep(pos + 1);
                });
            };

            FileGroupHighlighter.prototype.resultStep = function(pos) {
                if (pos >= this.results.length) {
                    return;
                }
                var self = this;
                var result = this.results[pos];
                var tag = document.getElementById(result.tagId);
                var text = tag.textContent || tag.innerText;
                tag.innerHTML = VPLUtil.processResult(text, this.shFileNames, this.shFiles,
                    result.noFormat, result.folding);
                VPLUtil.delay(tag + ".next", function() {
                    self.resultStep(pos + 1);
                });
            };

            VPLUtil.addResults = function(tagId, noFormat, folding) {
                results.push({'tagId': tagId, 'noFormat': noFormat, 'folding': folding});
            };
            VPLUtil.syntaxHighlightFile = function(tagId, fileName, theme, showln, nl) {
                files.push({
                    'tagId': tagId,
                    'fileName': fileName,
                    'theme': theme,
                    'showln': showln,
                    'nl': nl
                 });
            };
            VPLUtil.syntaxHighlight = function() {
                new FileGroupHighlighter(files, results);
            };
            VPLUtil.flEventHandler = function(event) {
                var tag = event.target.getAttribute('href').substring(1);
                var line = event.target.getAttribute('data-line');
                var sh = shs[tag];
                sh.gotoLine(line, 0);
                sh.scrollToLine(line, true);
            };
            VPLUtil.setflEventHandler = function() {
                var links = document.getElementsByClassName("vpl_fl");
                for (var i = 0; i < links.length; i++) {
                    links[i].onclick = VPLUtil.flEventHandler;
                }
            };
            VPLUtil.showHideDiv = function(a) {
                var text = a;
                var div = a;
                if (!div.nextSibling) {
                    div = div.parentNode;
                }
                div = div.nextSibling;
                while (div.nodeName != 'DIV' && div.nodeName != 'PRE') {
                    div = div.nextSibling;
                    if (!div) {
                        return;
                    }
                }
                if (text) {
                    if (text.innerHTML == '[+]') {
                        if (div.savedDisplay) {
                            div.style.display = div.savedDisplay;
                        } else {
                            div.style.display = '';
                        }
                        text.innerHTML = '[-]';
                    } else {
                        div.savedDisplay = div.style.display;
                        div.style.display = 'none';
                        text.innerHTML = '[+]';
                    }
                }
            };

        })();
        VPLUtil.options = {
            scriptPath: ''
        };
        if (typeof window.VPLDebugMode != 'undefined') {
            debugMode = window.VPLDebugMode;
        }
        VPLUtil.init = function(options) {
            VPLUtil.options = {
                scriptPath: ''
            };
            $.extend(VPLUtil.options, options);
            if (typeof window.VPLDebugMode != 'undefined') {
                debugMode = window.VPLDebugMode;
            }
            VPLUtil.log(VPLUtil.options);
        };
        // Needs global use of VPLUtil for view source.
        window.VPLUtil = VPLUtil;
        return VPLUtil;
    }
);
