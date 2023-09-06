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

define(
    [
        'jquery',
        'jqueryui',
        'mod_vpl/vplutil',
    ],
    function($, jqui, VPLUtil) {
        var VPLUI = {};
        // Get scrollBarWidth.
        VPLUI.scrollBarWidth = function() {
            var parent, child, width;
            parent = $('<div style="width:50px;height:50px;overflow:auto"><div/></div>').appendTo('body');
            child = parent.children();
            width = child.innerWidth() - child.height(99).innerWidth();
            parent.remove();
            return width;
        };
        VPLUI.readZipFile = function(data, save, progressBar, end) {
            if (!end) {
                end = VPLUtil.doNothing;
            }
            if (typeof JUnzip == 'undefined') {
                VPLUtil.loadScript(['/zip/inflate.js', '/zip/unzip.js']
                , function() {
                    VPLUI.readZipFile(data, save, progressBar, end);
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

        VPLUI.readSelectedFiles = function(filesToRead, save, end) {
            // Process all File objects.
            var pb = new VPLUI.progressBar('import', 'import');
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
                        VPLUI.showErrorMessage(errorsMessages);
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
                                VPLUI.readZipFile(e.target.result, save, pb, function() {
                                                                                  readSecuencial(sec + 1);
                                                                               });
                                return;
                            } catch (ex) {
                                VPLUI.showErrorMessage(ex + " : " + f.name);
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
        VPLUI.dialogbaseOptions = {
            minWidth: 200,
            autoOpen: false,
            width: 'auto',
            closeText: VPLUtil.str('cancel'),
            modal: true,
            dialogClass: 'vpl_ide vpl_ide_dialog'
        };
        VPLUI.iconModified = function() {
            var html = '<span title="' + VPLUtil.str('modified') + '" class="vpl_ide_charicon">';
            html += '<i class="fa fa-star"></i>' + '</span> ';
            return html;
        };
        VPLUI.iconDelete = function() {
            var html = ' <span title="' + VPLUtil.str('delete') + '" class="vpl_ide_charicon vpl_ide_delicon">';
            html += '<i class="fa fa-trash"></i>' + '</span> ';
            return html;
        };
        VPLUI.iconClose = function() {
            var html = ' <span title="' + VPLUtil.str('closebuttontitle');
            html += '" class="vpl_ide_charicon vpl_ide_closeicon">' + '<i class="fa fa-remove"></i>' + '</span> ';
            return html;
        };
        VPLUI.iconRequired = function() {
            var html = ' <span title="' + VPLUtil.str('required') + '" class="vpl_ide_charicon">';
            html += '<i class="fa fa-shield"></i>' + '</span> ';
            return html;
        };
        VPLUI.iconReadOnly = function() {
            var html = ' <span title="' + VPLUtil.str('readOnly') + '" class="vpl_ide_charicon">';
            html += '<i class="fa fa-lock"></i>' + '</span> ';
            return html;
        };
        VPLUI.iconFolder = function() {
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
            VPLUI.genIcon = function(icon, size) {
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
        VPLUI.setTitleBar = function(dialog, type, icon, buttons, handler) {
            var title = $(dialog).parent().find("span.ui-dialog-title");
            /**
             * Generate HTML for a button with icon
             * @param {string} e name of botton.
             * @returns {string} Html tag <a> as a button.
             */
            function genButton(e) {
                var html = "<a id='vpl_" + type + "_" + e + "' href='#' title='" + VPLUtil.str(e) + "'>";
                html += VPLUI.genIcon(e, 'fw') + "</a>";
                return html;
            }
            var html = VPLUI.genIcon(icon);
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
        VPLUI.setDialogTitleIcon = function(dialog, icon) {
            var title = $(dialog).parent().find("span.ui-dialog-title");
            title.html(VPLUI.genIcon(icon) + ' ' + title.html());
        };
        VPLUI.progressBar = function(title, message, onUserClose) {
            var labelHTML = '<span class="vpl_ide_progressbarlabel"></span>';
            var sppiner = '<div class="vpl_ide_progressbaricon">' + VPLUI.genIcon('spinner') + '</div>';
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
                        label.html(VPLUI.genIcon(icon) + ' ' + label.html());
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
            titleTag.html(VPLUI.genIcon(title) + ' ' + titleTag.html());
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
        VPLUI.showMessage = function(message, initialoptions) {
            var options = $.extend({}, VPLUI.dialogbaseOptions, initialoptions);
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
            titleTag.html(VPLUI.genIcon(icon) + ' ' + titleTag.html());

            messageDialog.setMessage = function(men) {
                $(messageDialog).find('.dmessage').html(men.replace(/\n/g, '<br>'));
            };

            messageDialog.dialog('open');
            return messageDialog;
        };
        VPLUI.showErrorMessage = function(message, options) {
            var currentOptions = $.extend({}, VPLUI.dialogbaseOptions, {
                title: VPLUtil.str('error'),
                icon: 'alert'
            });
            if (options) {
                currentOptions = $.extend(currentOptions, options);
            }
            return VPLUI.showMessage(message, currentOptions);
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
        VPLUI.requestAction = function(action, title, data, URL, noDialog) {
            var deferred = $.Deferred();
            var request = null;
            var xhr = false;
            var apb = false;
            if (!noDialog) {
                if (title === '') {
                    title = 'connecting';
                }
                apb = new VPLUI.progressBar(action, title, function() {
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
                if (window.VPLDebugMode && errorThrown.message != undefined) {
                    message += ': ' + errorThrown.message;
                }
                VPLUtil.log(message);
                deferred.reject(message);
            });
            return deferred;
        };
        VPLUI.clickServer = function(e) {
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

        VPLUI.acceptCertificates = function(servers, getLastAction) {
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
                var m = VPLUI.showMessage(html, {
                    ok: function() {
                        var action = getLastAction();
                        if (action) {
                            action();
                        }
                    },
                    icon: 'unlocked',
                    title: VPLUtil.str('acceptcertificates')
                });
                $(m).find('a').on('click keypress', VPLUI.clickServer);
            } else {
                VPLUtil.log('servers.length == 0');
                VPLUI.showErrorMessage(VPLUtil.str('connection_fail'));
            }
        };
        VPLUI.monitorRunning = VPLUtil.returnFalse;
        VPLUI.webSocketMonitor = function(coninfo, title, running, externalActions) {
            VPLUtil.setProtocol(coninfo);
            VPLUtil.setProcessId(coninfo.processid);
            var ws = null;
            var pb = null;
            var deferred = $.Deferred();
            var defail = function(m) {
                deferred.reject(m);
                if (ws !== null) {
                    ws.close();
                }
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
                    VPLUI.requestAction('retrieve', '', data, externalActions.ajaxurl)
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
                    VPLUI.requestAction('cancel', '', data, externalActions.ajaxurl, true);
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
            pb = new VPLUI.progressBar(title, 'connecting', function() {
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
                    VPLUI.requestAction('getjails', 'retrieve', {}, externalActions.ajaxurl)
                    .done(function(response) {
                        VPLUI.acceptCertificates(response.servers, function() {
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
            VPLUI.monitorRunning = function() {
                return ws !== null && ws.readyState != WebSocket.CLOSED;
            };
            return deferred;
        };

        VPLUI.hideIDEStatus = function() {
            VPLUtil.delay('updateIDEStatus', function() {
                $('.vpl_ide_status').hide();
            });
        };

        VPLUI.showIDEStatus = function(status) {
            VPLUtil.delay('updateIDEStatus', function() {
                $('.vpl_ide_status').text(status);
                $('.vpl_ide_status').show();
            });
        };

        VPLUtil.init = VPLUtil.doNothing;
        // Needs global use of VPLUtil for view source? Review.
        window.VPLUI = VPLUI;
        return VPLUI;
    }
);
