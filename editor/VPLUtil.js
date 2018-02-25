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
 * Tools for IDE and related
 * @package mod_vpl
 * @copyright 2016 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/* globals VPL_Util: true */
/* globals $JQVPL */
/* globals console */
/* globals unescape */
/* globals escape */
/* globals JUnzip */
/* globals JSInflate */
/* globals ace */
/* globals M */

(function() {
    VPL_Util = {};
    VPL_Util.doNothing = function() {
    };
    var debugMode = M && M.cfg && M.cfg.developerdebug;
    VPL_Util.log = function( m, forced) {
        if ( (debugMode || forced) && console && console.log ) {
            console.log( m );
        }
        if ( (debugMode || forced) && console && console.trace ) {
            console.trace();
        }
    };
    // Get scrollBarWidth.
    VPL_Util.scrollBarWidth = function() {
        var parent, child, width;
        parent = $JQVPL('<div style="width:50px;height:50px;overflow:auto"><div/></div>').appendTo('body');
        child = parent.children();
        width = child.innerWidth() - child.height(99).innerWidth();
        parent.remove();
        return width;
    };
    VPL_Util.sanitizeHTML = function(t) {
        if ( typeof t == 'undefined' || t.replace('/^\s+|\s+$/g','') == '') {
            return '';
        }
        return $JQVPL('<div>' + t + '</div>').html();
    };
    VPL_Util.sanitizeText = function(s) {
        if ( typeof s == 'undefined' || s.replace('/^\s+|\s+$/g','') == '') {
            return '';
        }
        return s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    };

    VPL_Util.setProtocol = function(coninfo) {
        var secure;
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
        var URLBase = (secure ? 'wss://' : 'ws://') + coninfo.server;
        coninfo.secure = secure;
        coninfo.portToUse = secure ? coninfo.securePort : coninfo.port;
        URLBase += ':' + coninfo.portToUse + '/';
        coninfo.monitorURL = URLBase + coninfo.monitorPath;
        coninfo.executionURL = URLBase + coninfo.executionPath;
    };
    VPL_Util.ArrayBuffer2String = function(data) {
        var view = new Uint8Array(data);
        var chunks = [];
        var chunkSize = 32000;
        for (var i = 0, len = view.length; i < len; i += chunkSize) {
            chunks.push(String.fromCharCode.apply(String, view.subarray(i, Math.min(i + chunkSize, len))));
        }
        return chunks.join('');
    };
    VPL_Util.String2ArrayBuffer = function(data) {
        var len = data.length;
        var ret = new ArrayBuffer(len);
        var u8 = new Uint8Array(ret);
        for (var i = 0; i < len; i++) {
            u8[i] = data.charCodeAt(i);
        }
        return ret;
    };

    (function() {
        var file_unique_id = 0;
        VPL_Util.getUniqueId = function() {
            return file_unique_id++;
        };
    })();
    (function() {
        var reg_ext = /\.([^.]*)$/;
        var reg_img = /^(gif|jpg|jpeg|png|ico)$/i;
        var reg_bin = /^(zip|jar|pdf|tar|bin|7z|arj|deb|gzip|rar|rpm|dat|db|rtf|doc|docx|odt)$/i;
        VPL_Util.fileExtension = function(fileName) {
            var res = reg_ext.exec(fileName);
            return res !== null ? res[1] : '';
        };
        VPL_Util.isImage = function(fileName) {
            return reg_img.test(VPL_Util.fileExtension(fileName));
        };
        VPL_Util.isBinary = function(fileName) {
            return VPL_Util.isImage(fileName) || reg_bin.test(VPL_Util.fileExtension(fileName));
        };

        var regInvalidFileName = /[\x00-\x1f]|[:-@]|[{-~]|\\|\[|\]|[\/\^`´]|^\-|^ | $|\.\./;
        VPL_Util.validFileName = function(fileName) {
            if (fileName.length < 1) {
                return false;
            }
            if (fileName.length > 128) {
                return false;
            }
            return !(regInvalidFileName.test(fileName));
        };
    })();
    VPL_Util.getCurrentTime = function() {
        return parseInt((new Date()).valueOf() / 1000);
    };
    VPL_Util.encodeBinary = function(name, data) {
        if (!VPL_Util.isBinary(name)) {
            return btoa(unescape(encodeURIComponent(data)));
        }
        return btoa(VPL_Util.ArrayBuffer2String(data));
    };

    VPL_Util.decodeBinary = function(name, data) {
        var decoded = atob(data);
        if (!VPL_Util.isBinary(name)) {
            return decodeURIComponent(escape(decoded));
        }
        return VPL_Util.String2ArrayBuffer(decoded);
    };

    VPL_Util.validPath = function(path) {
        if (path.length > 256) {
            return false;
        }
        var dirs = path.split("/");
        for (var i = 0; i < dirs.length; i++) {
            if (!VPL_Util.validFileName(dirs[i])) {
                return false;
            }
        }
        return true;
    };
    VPL_Util.getFileName = function(path) {
        var dirs = path.split("/");
        return dirs[dirs.length - 1];
    };
    VPL_Util.dataFromURLData = function(data) {
        return data.substr(data.indexOf(',') + 1);
    };
    VPL_Util.readZipFile = function(data, save, progressBar,end) {
        var ab = VPL_Util.ArrayBuffer2String(data);
        var unzipper = new JUnzip(ab);
        if ( ! unzipper.isZipFile()) {
            return;
        }
        unzipper.readEntries();
        var out = unzipper.entries.length;
        function process(i) {
            if (i >= out || progressBar.isClosed()) {
                if ( end ) {
                    end();
                }
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
                data = VPL_Util.String2ArrayBuffer(uncompressed);
                if (VPL_Util.isBinary(fileName)) {
                    // If binary use as arrayBuffer.
                    if ( ! save({name:fileName, contents:btoa(uncompressed), encoding:1}) ) {
                        i = out;
                    }
                    process(i + 1);
                    progressBar.endFile();
                } else {
                    var blob = new Blob([ data ], {
                        type : 'text/plain'
                    });
                    var fr = new FileReader();
                    fr.onload = function(e) {
                        if ( ! save({name:fileName, contents:e.target.result, encoding:0}) ) {
                            i = out;
                        }
                        process(i + 1);
                        progressBar.endFile();
                    };
                    fr.readAsText(blob);
                }
            }
        }
        process(0);
    };

    VPL_Util.readSelectedFiles = function(filesToRead, save, end) {
        // Process all File objects.
        var pb = new VPL_Util.progressBar('import', 'import');
        var filePending = 0;
        if ( ! end ) {
            end = VPL_Util.doNothing;
        }
        pb.processFile = function(name) {
            pb.setLabel(name);
            filePending++;
        };
        pb.endFile = function() {
            filePending--;
            if (filePending === 0) {
                end();
                pb.close();
            }
        };
        function readSecuencial(sec) {
            if (sec >= filesToRead.length || pb.isClosed()) {
                return;
            }
            var f = filesToRead[sec];
            pb.processFile(f.name);
            var binary = VPL_Util.isBinary(f.name);
            var reader = new FileReader();
            var ext = VPL_Util.fileExtension(f.name).toLowerCase();
            reader.onload = function(e) {
                var goNext = false;
                if (binary) {
                    if (ext == 'zip') {
                        try {
                            VPL_Util.readZipFile(e.target.result, save, pb, function(){readSecuencial(sec + 1);});
                        } catch (ex) {
                            VPL_Util.showErrorMessage(ex + " : " + f.name);
                        }
                    } else {
                        var data = VPL_Util.dataFromURLData(e.target.result);
                        goNext = save({name:f.name, contents:data, encoding:1});
                    }
                } else{
                    goNext = save({name:f.name, contents:e.target.result, encoding:0});
                }
                // Load next file if OK.
                if ( goNext ) {
                    readSecuencial(sec + 1);
                }
                pb.endFile();
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
            'gif' : 'image/gif',
            'jpg' : 'image/jpeg',
            'jpeg' : 'image/jpeg',
            'png' : 'image/png',
            'ico' : 'image/vnd.microsoft.icon',
            'pdf' : 'application/pdf'
        };
        VPL_Util.getMIME = function(fileName) {
            var ext = VPL_Util.fileExtension(fileName);
            if (ext in MIME) {
                return MIME[ext];
            }
            return 'application/octet-stream';
        };
        VPL_Util.getTimeLeft = function(timeLeft) {
            var res = '';
            var minute = 60;
            var hour = 60 * minute;
            var day = 24 * hour;
            if (timeLeft < 0) {
                res += '-';
                timeLeft = -timeLeft;
            }
            var days = parseInt(timeLeft / day);
            timeLeft -= days * day;
            if (days !== 0) {
                res += days + 'T';
            }
            var hours = parseInt(timeLeft / hour);
            timeLeft -= hours * hour;
            var minutes = parseInt(timeLeft / minute);
            timeLeft -= minutes * minute;
            var seconds = parseInt(timeLeft);
            res += ('00' + hours).substr(-2) + ':';
            res += ('00' + minutes).substr(-2);
            if (days === 0) {
                res += ':' + ('00' + seconds).substr(-2);
            }
            return res;
        };
    })();
    (function() {
        var maplang = {
            'abap' : 'abap',
            'abc' : 'abc',
            'ada' : 'ada', 'ads' : 'ada', 'adb' : 'ada',
            'as' : 'actionscript','as3' : 'actionscript',
            'asm' : 'assembly_x86',
            'bash' : 'sh',
            'bat' : 'batchfile',
            'c' : 'c_cpp', 'C' : 'c_cpp', 'cc' : 'c_cpp', 'cpp' : 'c_cpp', 'hxx' : 'c_cpp', 'h' : 'c_cpp',
            'cases' : 'cases',
            'cbl' : 'cobol', 'cob' : 'cobol',
            'coffee' : 'coffee',
            'clj' : 'clojure',
            'cs' : 'csharp',
            'css' : 'css',
            'd' : 'd',
            'dart' : 'dart',
            'e' : 'eiffel',
            'erl' : 'erlang', 'hrl' : 'erlang',
            'f' : 'fortran', 'f77' : 'fortran',
            'go' : 'go',
            'groovy' : 'groovy',
            'hs' : 'haskell',
            'htm' : 'html', 'html' : 'html',
            'hx' : 'haxe',
            'java' : 'java',
            'js' : 'javascript',
            'json' : 'json',
            'jsp' : 'jsp',
            'jsx' : 'jsx',
            'kt' : 'kotlin', 'kts' : 'kotlin',
            'm' : 'matlab',
            'md' : 'markdown',
            'less' : 'less',
            'lisp' : 'lisp', 'lsp' : 'lisp',
            'lua' : 'lua',
            'pas' : 'pascal', 'p' : 'pascal',
            'perl' : 'perl', 'prl' : 'perl',
            'php' : 'php',
            'pro' : 'prolog', 'pl' : 'prolog',
            'py' : 'python',
            'r' : 'r',
            'rb' : 'ruby', 'ruby' : 'ruby',
            'sass' : 'sass',
            'scala' : 'scala',
            'scm' : 'scheme', 's' : 'scheme',
            'scss' : 'scss',
            'sh' : 'sh',
            'swift' : 'swift',
            'sql' : 'sql',
            'svg' : 'svg',
            'tex' : 'tex',
            'tcl' : 'tcl',
            'twig' : 'twig',
            'v' : 'verilog',
            'vhd' : 'vhdl', 'vhdl' : 'vhdl',
            'xml' : 'xml',
            'yaml' : 'yaml'
        };
        VPL_Util.langType = function(ext) {
            if (ext in maplang) {
                return maplang[ext];
            }
            return 'plain_text';
        };
    })();
    (function() {
        var i18n = {};
        VPL_Util.str = function(key) {
            if (!i18n[key]) {
                return '{' + key + '}';
            }
            return i18n[key];
        };
        VPL_Util.set_str = function(newi18n) {
            for (var key in newi18n) {
                if ( newi18n.hasOwnProperty(key) ) {
                    i18n[key] = newi18n[key];
                }
            }
            VPL_Util.dialogbase_options = {
                autoOpen : false,
                minHeight : 10,
                width : 'auto',
                closeText : VPL_Util.str('cancel'),
                modal : true,
                dialogClass : 'vpl_ide vpl_ide_dialog'
            };

        };
    })();
    (function() {
        var delayedActions = {};
        var reg = /function ([^\(]+)/;
        function functionName(func) {
            var fs = func.toString();
            var res = reg.exec(fs);
            if (res === null) {
                return fs;
            }
            return res[1];
        }
        VPL_Util.delay = function(func, arg1, arg2) {
            var fn = functionName(func);
            if (delayedActions[fn]) {
                clearTimeout(delayedActions[fn]);
            }
            delayedActions[fn] = setTimeout(function() {
                func(arg1, arg2);
                delayedActions[fn] = false;
            }, 50);
        };
        VPL_Util.longDelay = function(func, arg1, arg2) {
            var fn = functionName(func);
            if (delayedActions[fn]) {
                clearTimeout(delayedActions[fn]);
            }
            delayedActions[fn] = setTimeout(function() {
                func(arg1, arg2);
                delayedActions[fn] = false;
            }, 200);
        };
    })();
    VPL_Util.iconModified = function() {
        var html = '<span title="' + VPL_Util.str('modified') + '" class="vpl_ide_charicon">';
        html += '<i class="fa fa-star"></i>' + '</span> ';
        return html;
    };
    VPL_Util.iconDelete = function() {
        var html = ' <span title="' + VPL_Util.str('delete') + '" class="vpl_ide_charicon vpl_ide_delicon">';
        html += '<i class="fa fa-trash"></i>' + '</span> ';
        return html;
    };
    VPL_Util.iconClose = function() {
        var html = ' <span title="' + VPL_Util.str('closebuttontitle');
        html += '" class="vpl_ide_charicon vpl_ide_closeicon">' + '<i class="fa fa-remove"></i>' + '</span> ';
        return html;
    };
    VPL_Util.iconRequired = function() {
        var html = ' <span title="' + VPL_Util.str('required') + '" class="vpl_ide_charicon">';
        html += '<i class="fa fa-shield"></i>' + '</span> ';
        return html;
    };
    VPL_Util.iconFolder = function() {
        return '<i class="fa fa-folder-open-o"></i>';
    };
    (function() {
        var menu_icons = {
            'filelist' : 'folder',
            'filelistclose' : 'folder-open',
            'new' : 'file',
            'rename' : 'pencil',
            'delete' : 'trash',
            'multidelete' : 'trash|list',
            'close' : 'remove',
            'comments' : 'commenting',
            'import' : 'upload',
            'print' : 'print',
            'edit' : 'edit',
            'undo' : 'undo',
            'redo' : 'repeat',
            'select_all' : 'location-arrow',
            'find' : 'search',
            'find_replace' : 'exchange',
            'next' : 'search-plus',
            'resetfiles' : 'refresh',
            'download' : 'download',
            'fullscreen' : 'expand',
            'regularscreen' : 'compress',
            'save' : 'save',
            'sort' : 'list-ol',
            'run' : 'rocket',
            'debug' : 'bug',
            'evaluate' : 'check-square-o',
            'console' : 'terminal',
            'about' : 'question',
            'info' : 'info-circle',
            'alert' : 'warning',
            'trash' : 'trash',
            'retrieve' : 'download',
            'spinner' : 'refresh fa-spin',
            'keyboard' : 'keyboard-o',
            'clipboard' : 'clipboard',
            'timeleft' : 'clock-o',
            'copy' : 'copy',
            'paste' : 'paste',
            'more' : 'plus-square',
            'less' : 'minus-square',
            'resize' : 'arrows-alt',
            'graphic' : 'picture-o',
            'send' : 'send',
            'user' : 'user',
            'fontsize' : 'text-height'
        };
        VPL_Util.gen_icon = function(icon, size) {
            if (!menu_icons[icon]) {
                return '';
            }
            var classes = 'fa fa-';
            if (!size) {
                classes += 'lg';
            } else {
                classes += size;
            }
            var icons = menu_icons[icon].split('|');
            var ret = '';
            for(var i = 0; i < icons.length; i++) {
                ret += "<i class='" + classes + ' fa-' + icons[i] + "'></i>";
            }
            return ret;
        };
    })();
    // UI operations.
    VPL_Util.setTitleBar = function(dialog, type, icon, buttons, handler) {
        var title = $JQVPL(dialog).parent().find("span.ui-dialog-title");
        function genButton(e) {
            var html = "<a id='vpl_" + type + "_" + e + "' href='#' title='" + VPL_Util.str(e) + "'>";
            html += VPL_Util.gen_icon(e, 'fw') + "</a>";
            return html;
        }
        var html = VPL_Util.gen_icon(icon);
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
            title.find('#vpl_' + type + '_' + buttons[ih]).button().click(handler[ih]);
        }
        titleButtons.on('focus','*', function(){$JQVPL(this).blur();});
        return titleText;
    };
    VPL_Util.progressBar = function(title, message, onUserClose) {
        var labelHTML = '<span class="vpl_ide_progressbarlabel"></span>';
        var sppiner = '<div class="vpl_ide_progressbaricon">' + VPL_Util.gen_icon('spinner') + '</div>';
        var pbHTML = ' <div class="vpl_ide_progressbar">' + sppiner + labelHTML + '</div>';
        var HTML = '<div class="vpl_ide_dialog" style="display: none;">' + pbHTML + '</div>';
        var dialog = $JQVPL(HTML);
        $JQVPL('body').append(dialog);
        var progressbar = dialog.find('.vpl_ide_progressbar');
        var label = progressbar.find('.vpl_ide_progressbarlabel');
        dialog.dialog({
            'title' : VPL_Util.str(title),
            resizable : false,
            autoOpen : false,
            width : 250,
            minHeight : 20,
            height : 20,
            modal : true,
            dialogClass : 'vpl_ide vpl_ide_dialog',
            close : function( event ) {
                if (dialog) {
                    if (onUserClose && event.originalEvent ) {
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
                    label.html(VPL_Util.gen_icon(icon) + ' ' + label.html());
                }
            }
        };
        this.close = function() {
            if(dialog) {
                dialog.dialog('destroy');
                $JQVPL(dialog).remove();
                dialog = false;
            }
        };
        this.isClosed = function() {
            return dialog === false;
        };
        var titleTag = dialog.siblings().find('.ui-dialog-title');
        titleTag.html(VPL_Util.gen_icon(title) + ' ' + titleTag.html());
        this.setLabel(VPL_Util.str(message));
        dialog.dialog('open');
        dialog.dialog('option', 'height', 'auto');
    };
    VPL_Util.showMessage = function(message, initialoptions) {
        var options = $JQVPL.extend({}, initialoptions);
        var message_dialog = $JQVPL('<div class="vpl_ide_dialog"></div>');
        if (!options) {
            options = {};
        }
        if (!options.icon) {
            options.icon = 'info';
        }
        if (!options.title) {
            options.title = VPL_Util.str('warning');
        }
        message_dialog.html(VPL_Util.gen_icon(options.icon) + ' <span class="dmessage">' + message + '</span>');
        $JQVPL('body').append(message_dialog);
        var message_buttons = {};
        if (!options.ok) {
            message_buttons[VPL_Util.str('ok')] = function() {
                $JQVPL(this).dialog('close');
            };
        } else {
            message_buttons[VPL_Util.str('ok')] = function() {
                $JQVPL(this).dialog('close');
                options.ok();
            };
            message_buttons[VPL_Util.str('cancel')] = function() {
                $JQVPL(this).dialog('close');
            };
        }
        if (options.next) {
            message_buttons[VPL_Util.str('next')] = function() {
                $JQVPL(this).dialog('close');
                options.next();
            };
        }
        if (options.close) {
            options.oldClose = options.close;
        }
        message_dialog.dialog($JQVPL.extend({}, VPL_Util.dialogbase_options, {
            title : options.title,
            buttons : message_buttons,
            close : function() {
                $JQVPL(this).remove();
                if (options.oldClose) {
                    options.oldClose();
                }
            }
        }));
        message_dialog.dialog('open');
        message_dialog.setMessage = function(men) {
            $JQVPL(message_dialog).find('.dmessage').html(men);
        };
        return message_dialog;
    };
    VPL_Util.showErrorMessage = function(message, options) {
        var currentOptions = $JQVPL.extend({}, VPL_Util.dialogbase_options, {
            title : VPL_Util.str('error'),
            icon : 'alert'
        });
        if (options) {
            currentOptions = $JQVPL.extend(currentOptions, options);
        }
        return VPL_Util.showMessage(message, currentOptions);
    };

    VPL_Util.requestAction = function(action, title, data, URL) {
        var deferred = $JQVPL.Deferred();
        var request = null;
        var xhr = false;
        if (title === '') {
            title = 'connecting';
        }
        var apb = new VPL_Util.progressBar(action, title, function() {
            if (request.readyState != 4) {
                if ( xhr && xhr.abort ) {
                    xhr.abort();
                }
            }
        });
        request = $JQVPL.ajax({
            beforeSend: function (jqXHR) {
                xhr = jqXHR;
                return true;
            },
            async : true,
            type : "POST",
            url : URL + action,
            'data' : JSON.stringify(data),
            contentType : "application/json; charset=utf-8",
            dataType : "json"
        }).always(function() {
            apb.close();
        }).done(function(response) {
            if (!response.success) {
                deferred.reject(response.error);
            } else {
                deferred.resolve(response.response);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            var message = VPL_Util.str('connection_fail') + ': ' + textStatus;
            if ( debugMode ) {
                message += '<br>' + errorThrown.message;
                message += '<br>' + jqXHR.responseText.substr(0,80);
            }
            VPL_Util.log(message);
            deferred.reject(message);
        });
        return deferred;
    };
    VPL_Util.supportWebSocket = function() {
        if ("WebSocket" in window) {
            return true;
        }
        return false;
    };
    VPL_Util.isAndroid = function() {
        return window.navigator.userAgent.indexOf('Android') > -1;
    };
    VPL_Util.isFirefox = function() {
        return window.navigator.userAgent.indexOf('Firefox') > -1;
    };
    VPL_Util.isMac = function() {
        return window.navigator.userAgent.indexOf('Mac') > -1;
    };
    VPL_Util.clickServer = function(e) {
        var w = 550;
        var h = 450;
        var left = (screen.width / 2) - (w / 2);
        var top = (screen.height / 2) - (h / 2);
        try {
            var features = 'toolbar=no, location=no, directories=no, status=no, menubar=no';
            features += ', resizable=yes, scrollbars=yes, copyhistory=no, width=' + w;
            features += ', height=' + h + ', top=' + top + ', left=' + left;
            var win = window.open($JQVPL(this).attr('href'), '_blank', features);
            if (!win) {
                return true;
            }
        } catch (ex) {
            VPL_Util.log( ex );
            return true;
        }
        e.preventDefault();
        $JQVPL(this).parent().hide();
        return false;
    };

    VPL_Util.acceptCertificates = function(servers, getLastAction) {
        if (servers.length > 0) {
            // Generate links dialog.
            var html = VPL_Util.str('acceptcertificatesnote');
            html += '<ol>';
            for (var i in servers) {
                if ( servers.hasOwnProperty(i) ) {
                    var n = 1 + i;
                    html += '<li><a href="' + servers[i] + '" target="_blank">Server ';
                    html += n + '</a><br /></ul>';
                }
            }
            html += '</ol>';
            var m = VPL_Util.showMessage(html, {
                ok : function() {
                    var action = getLastAction();
                    if (action) {
                        action();
                    }
                },
                icon : 'unlocked',
                title : VPL_Util.str('acceptcertificates')
            });
            $JQVPL(m).find('a').on('click keypress', VPL_Util.clickServer);
        } else {
            VPL_Util.log('servers.length == 0');
            VPL_Util.showErrorMessage(VPL_Util.str('connection_fail'));
        }
    };

    VPL_Util.webSocketMonitor = function(coninfo, title, running, externalActions) {
        VPL_Util.setProtocol(coninfo);
        var ws = null;
        var pb = null;
        var deferred = $JQVPL.Deferred();
        var defail = function( m ) {
            deferred.reject( m );
        };
        var delegated = false;
        var messageActions = {
            'message' :function(content) {
                var parsed = /^([^:]*):?(.*)/i.exec(content);
                var state = parsed[1];
                var detail = parsed[2];
                if (state == 'running') {
                    state = running;
                }
                var text = VPL_Util.str(state);
                if (detail > '') {
                    text += ': ' + detail;
                }
                if (externalActions.getConsole && externalActions.getConsole().isOpen()) {
                    externalActions.getConsole().setMessage(text);
                } else {
                    pb.setLabel(text);
                }
            },
            'compilation' :function(content) {
                if (externalActions.setResult) {
                    externalActions.setResult({
                        'compilation' :content,
                    }, false);
                }
            },
            'retrieve' :function() {
                pb.close();
                delegated = true;
                VPL_Util.requestAction('retrieve', '', '', externalActions.ajaxurl)
                .done(
                    function(response) {
                        deferred.resolve();
                        if (externalActions.setResult) {
                            externalActions.setResult(response, true);
                        }
                    }
                ).fail(defail);
            },
            'run' :function(content) {
                pb.close();
                externalActions.run(content, coninfo, ws);
            },
            'close' :function() {
                VPL_Util.log('ws close message from jail');
                ws.close();
                if (externalActions.close) {
                    externalActions.close();
                }
            }
        };
        try {
            if (VPL_Util.supportWebSocket()) {
                ws = new WebSocket(coninfo.monitorURL);
            } else {
                VPL_Util.log('ws not available');
                deferred.reject(VPL_Util.str('browserupdate'));
                return deferred;
            }
        } catch (e) {
            VPL_Util.log('ws new say ' + e);
            deferred.reject(e.message);
            return deferred;
        }
        pb = new VPL_Util.progressBar(title, 'connecting', function() {
            deferred.reject('Stopped by user');
            ws.close();
        });
        ws.notOpen = true;
        ws.onopen = function() {
            ws.notOpen = false;
            pb.setLabel(VPL_Util.str('connected'));
        };
        ws.onerror = function(event) {
            VPL_Util.log('ws error ' + event);
            pb.close();
            if (coninfo.secure && ws.notOpen) {
                VPL_Util.requestAction('getjails', 'retrieve', {}, externalActions.ajaxurl)
                .done(function(response) {
                    VPL_Util.acceptCertificates(response.servers, function(){
                        return externalActions.getLastAction();
                    });
                })
                .fail(defail);
            } else {
                deferred.reject(VPL_Util.str('connection_fail'));
            }
        };
        ws.onclose = function() {
            if (externalActions.getConsole) {
                externalActions.getConsole().disconnect();
            }
            if ( !ws.notOpen ) {
                pb.close();
                if ( ! delegated && deferred.state() != 'rejected' ) {
                    deferred.resolve();
                }
            }
        };

        ws.onmessage = function(event) {
            var message = /^([^:]+):/i.exec(event.data);
            if (message !== null) {
                var action = message[1];
                var content = event.data.substr(action.length + 1);
                if (messageActions[action]) {
                    messageActions[action](content);
                }
            } else {
                pb.setLabel(VPL_Util.str('error') + ': ' + event.data);
            }
        };
        return deferred;
    };
    VPL_Util.processResult = function(text, filenames, sh, noFormat, folding) {
        if ( typeof text == 'undefined' || text.replace('/^\s+|\s+$/gm','') == '' ) {
            return '';
        }
        function escReg(t) {
            return t.replace(/[-[\]{}()*+?.,\\^$|#\s]/, "\\$&");
        }
        var regtitgra = /\([-]?[\d]+[\.]?[\d]*\)\s*$/;
        var regtit = /^-.*/;
        var regcas = /^\s*\>/;
        var regWarning = new RegExp('warning|' + escReg(VPL_Util.str('warning')), 'i');
        var state = '';
        var html = '';
        var comment = '';
        var case_ = '';
        var lines = text.split(/\r\n|\n|\r/);
        var regFiles = [];
        var lastAnotation = false;
        var lastAnotationFile = false;
        var afterTitle = false;
        function getHref( i ) {
            if ( typeof sh[i].getTagId === 'undefined' ) {
                return 'href="#" ';
            } else {
                return 'href="#' + sh[i].getTagId() + '" ';
            }
        }
        for (var i = 0; i < filenames.length; i++) {
            var regf = escReg(filenames[i]);
            var reg = "(^|.* |.*/)" + regf + "[:\(](\\d+)([:\,]?(\\d+)?\\)?)";
            regFiles[i] = new RegExp(reg, '');
        }
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
                    var type = line.search(regWarning) == -1 ? 'error' : 'warning';
                    lastAnotation = {
                        row : (match[2] - 1),
                        column : match[3],
                        type : type,
                        text : rawline,
                    };
                    anot.push(lastAnotation);
                    var fileName = filenames[i];
                    var href = getHref( i );
                    var lt = VPL_Util.sanitizeText( fileName );
                    var data = 'data-file="' + fileName + '" data-line="' + match[2] + '"';
                    line = line.replace(reg, '$1<a ' + href + ' class="vpl_fl" ' + data + '>' + lt + ':$2$3</a>');
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
        function getTitle(line) {
            lastAnotation = false;
            line = line.substr(1);
            var end = regtitgra.exec(line);
            if (end !== null) {
                line = line.substr(0, line.length - end[0].length);
            }
            var html = '';
            if ( folding ) {
                html += '<a href="javascript:void(0)" onclick="VPL_Util.show_hide_div(this)">[+]</a>';
            }
            html += '<b class="ui-widget-header ui-corner-all">' + VPL_Util.sanitizeText(line) + '</b><br />';
            html = genFileLinks(html, line);
            return html;
        }
        function getComment() {
            lastAnotation = false;
            var ret = comment;
            comment = '';
            return ret;
        }
        function addComment(rawline) {
            var line = VPL_Util.sanitizeText(rawline);
            comment += genFileLinks(line, rawline) + '<br />';
        }
        function addCase(rawline) {
            var line = VPL_Util.sanitizeText(rawline);
            case_ += genFileLinks(line, rawline) + "\n";
        }
        function getCase() {
            lastAnotation = false;
            var ret = case_;
            case_ = '';
            return '<pre><i>' + ret + '</i></pre>';
        }

        for (i = 0; i < lines.length; i++) {
            var line = lines[i];
            if ( noFormat ) {
                html += genFileLinks(VPL_Util.sanitizeText(line), line) + "\n";
                continue;
            }
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
                if ( afterTitle ) {
                    html += '</div>';
                }
                html += getTitle(line);
                html += folding ? '<div style="display:none">' : '<div>';
                afterTitle = true;
                state = '';
            } else if (regcasv) {
                if ( state == 'comment' ) {
                    html += getComment();
                }
                addCase(line.substr(match[0].length));
                state = 'case';
            } else {
                if ( state == 'case' ) {
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
        if ( afterTitle ) {
            html += '</div>';
        }
        return html;
    };

    (function() {
        var files = [];
        var results = [];
        var shs = [];

        function SubmissionHighlighter(files, results) {
            var self = this;
            this.files = files;
            this.results = results;
            setTimeout(function() {self.highlight();}, 10);
        }

        SubmissionHighlighter.prototype.highlight = function(){
            var self = this;
            if ( typeof ace === 'undefined' ) {
                setTimeout(function() {self.highlight();}, 100);
                return;
            }
            var files = this.files;
            var results = this.results;
            var shFiles = [];
            var shFileNames = [];
            for (var i = 0; i < files.length; i++) {
                var file = files[i];
                var lang = VPL_Util.langType(VPL_Util.fileExtension( file.fileName ));
                var tagId = file.tagId;
                var sh = ace.edit( 'code' + tagId);
                sh.setTheme( 'ace/theme/' + file.theme );
                sh.getSession().setMode( 'ace/mode/' + lang );
                sh.renderer.setShowGutter( file.showln );
                sh.setReadOnly( true );
                sh.setHighlightActiveLine( false );
                sh.setAutoScrollEditorIntoView( true );
                sh.setOption('maxLines', file.nl);
                sh.getAnnotations = function(){
                    return this.getSession().getAnnotations();
                };
                sh.setAnnotations = function(a){
                    return this.getSession().setAnnotations(a);
                };
                sh.getTagId = function(){
                    return this.vplTagId;
                };
                sh.vplTagId = tagId;
                shFiles.push( sh );
                shFileNames.push( file.fileName );
                shs[ file.tagId ] = sh;
            }
            for (var ri = 0; ri < results.length; ri++) {
                var tag = document.getElementById(results[ri].tagId);
                var text = tag.textContent || tag.innerText;
                tag.innerHTML = VPL_Util.processResult(text, shFileNames, shFiles,
                                                       results[ri].noFormat, results[ri].folding);
            }
            for (var si = 0; si < shFiles.length; si++) {
                shFiles[si].getSession().setUseWorker(false);
            }
        };

        VPL_Util.addResults = function( tagId, noFormat, folding ){
            results.push({ 'tagId' : tagId, 'noFormat' : noFormat, 'folding' : folding });
        };
        VPL_Util.syntaxHighlightFile = function( tagId, fileName, theme, showln, nl){
            files.push({
                'tagId' : tagId,
                'fileName' : fileName,
                'theme' : theme,
                'showln' : showln,
                'nl' : nl
             });
        };
        VPL_Util.syntaxHighlight = function(){
            if ( typeof ace === 'undefined' ) {
                setTimeout(VPL_Util.syntaxHighlight, 100);
                return;
            }
            new SubmissionHighlighter(files,results);
            files = [];
            results = [];
        };
        VPL_Util.flEventHandler = function( event ){
            var tag = event.target.getAttribute('href').substring(1);
            var line = event.target.getAttribute('data-line');
            var sh = shs[tag];
            sh.gotoLine(line, 0);
            sh.scrollToLine(line, true);
        };
        VPL_Util.setflEventHandler = function(){
            var links = document.getElementsByClassName("vpl_fl");
            for(var i = 0; i < links.length; i++) {
                links[i].onclick = VPL_Util.flEventHandler;
            }
        };
        VPL_Util.show_hide_div = function (a){
            var text = a;
            var div = a;
            if ( ! div.nextSibling ) {
                div = div.parentNode;
            }
            div = div.nextSibling;
            while ( div.nodeName != 'DIV' && div.nodeName != 'PRE' ) {
                div = div.nextSibling;
                if ( ! div ) {
                    return;
                }
            }
            if(text){
                if(text.innerHTML == '[+]'){
                    if ( div.savedDisplay ) {
                        div.style.display = div.savedDisplay;
                    } else {
                        div.style.display = '';
                    }
                    text.innerHTML = '[-]';
                }else{
                    div.savedDisplay = div.style.display;
                    div.style.display = 'none';
                    text.innerHTML = '[+]';
                }
            }
        };

    })();
})();
