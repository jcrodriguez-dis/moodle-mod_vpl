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
 * IDE Buttons
 *
 * @copyright 2016 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

define(
    [
        'jquery',
        'jqueryui',
        'mod_vpl/vplutil',
    ],
    function($, jqui, VPLUtil) {
        if (typeof VPLIDEButtons !== 'undefined') {
            return VPLIDEButtons;
        }
        var VPLIDEButtons = function(menuElement, isOptionAllowed) {
            var self = this;
            var buttons = {};

            this.notAdded = function(buttonName) {
                return !buttons[buttonName];
            };
            this.setText = function(buttonName, icon, title) {
                if (self.notAdded(buttonName)) {
                    return;
                }
                if (!icon) {
                    icon = buttons[buttonName].icon;
                }
                if (!title) {
                    title = buttons[buttonName].title;
                }
                if (!title) {
                    title = VPLUtil.str(icon);
                }
                buttons[buttonName].icon = icon;
                buttons[buttonName].title = title;
                if (buttons[buttonName].hasOwnProperty('key')) {
                    title += ' (' + buttons[buttonName].key + ')';
                }
                $('#vpl_ide_' + buttonName).attr('title', title);
                $('#vpl_ide_' + buttonName + ' i').replaceWith(VPLUtil.genIcon(icon));
            };
            this.setExtracontent = function(buttonName, html) {
                if (self.notAdded(buttonName)) {
                    return;
                }
                var cl = 'bt_extrahtml';
                var btag = $('#vpl_ide_' + buttonName + ' i');
                if (btag.find('.' + cl).length == 0) {
                    btag.append(' <span class="' + cl + '"><span>');
                }
                btag.find('.' + cl).html(html);
            };
            this.add = function(button) {
                if (typeof button === 'string') {
                    var name = button;
                    button = {
                        'name': name
                    };
                }
                if (!isOptionAllowed(button.name)) {
                    return;
                }
                if (!button.hasOwnProperty('icon')) {
                    button.icon = button.name;
                }
                if (!button.hasOwnProperty('title')) {
                    button.title = VPLUtil.str(button.name);
                }
                if (!button.hasOwnProperty('active')) {
                    button.active = true;
                }
                if (!button.hasOwnProperty('editorName')) {
                    button.editorName = button.name;
                }
                if (!button.hasOwnProperty('originalAction')) {
                    button.originalAction = VPLUtil.doNothing;
                }
                if (self.notAdded(button.name)) {
                    buttons[button.name] = button;
                } else {
                    throw new Error("Button already set " + button.name);
                }
                self.setAction(button.name, button.originalAction);
                if (button.hasOwnProperty('bindKey')) {
                    button.command = {
                        name: button.editorName,
                        bindKey: button.bindKey,
                        exec: button.action
                    };
                    var platform = "win";
                    if (navigator.platform.startsWith("Mac")) {
                        platform = "mac";
                    }
                    button.key = button.bindKey[platform];
                }
            };
            this.getHTML = function(buttonName) {
                if (self.notAdded(buttonName)) {
                    return '';
                } else {
                    var title = buttons[buttonName].title;
                    if (buttons[buttonName].hasOwnProperty('key')) {
                        title += ' (' + buttons[buttonName].key + ')';
                    }

                    var html = "<a id='vpl_ide_" + buttonName + "' href='#' title='" + title + "'>";
                    html += VPLUtil.genIcon(buttons[buttonName].icon) + "</a>";
                    return html;
                }
            };

            this.enable = function(buttonName, active) {
                if (self.notAdded(buttonName)) {
                    return;
                }
                var bw = $('#vpl_ide_' + buttonName);
                buttons[buttonName].active = active;
                bw.data("vpl-active", active);
                if (!active) {
                    bw.addClass('ui-button-disabled ui-state-disabled');
                } else {
                    bw.removeClass('ui-button-disabled ui-state-disabled');
                }
            };
            this.setAction = function(buttonName, action) {
                if (self.notAdded(buttonName)) {
                    return;
                }
                buttons[buttonName].originalAction = action;
                buttons[buttonName].action = function() {
                    if (buttons[buttonName].active) {
                        action();
                    }
                };
            };
            this.getAction = function(buttonName) {
                if (self.notAdded(buttonName)) {
                    return VPLUtil.doNothing;
                }
                return buttons[buttonName].action;
            };
            this.launchAction = function(buttonName) {
                if (self.notAdded(buttonName)) {
                    return;
                }
                buttons[buttonName].originalAction();
            };
            this.setGetkeys = function(editor) {
                if (!editor) {
                    return;
                }
                var commands = editor.commands.commands;
                var platform = editor.commands.platform;
                for (var buttonName in buttons) {
                    if (buttons.hasOwnProperty(buttonName)) {
                        var editorName = buttons[buttonName].editorName;
                        if (commands[editorName] && commands[editorName].bindKey && !buttons[buttonName].Key) {
                            buttons[buttonName].key = commands[editorName].bindKey[platform];
                            self.setText(buttonName);
                        } else {
                            if (buttons[buttonName].bindKey &&
                                !buttons[buttonName].hasOwnProperty('key')) {
                                buttons[buttonName].key = buttons[buttonName].bindKey[platform];
                                self.setText(buttonName);
                            }
                        }
                    }
                }
            };
            this.getShortcuts = function(editor) {
                var html = '<ul>';
                for (var buttonName in buttons) {
                    if (buttons[buttonName].hasOwnProperty('key')) {
                        html += '<li>';
                        html += buttons[buttonName].title + ' (' + buttons[buttonName].key + ')';
                        html += '</li>';
                    }
                }
                html += '</ul>';
                if (editor) {
                    html += '<h5>' + VPLUtil.str('edit') + '</h5>';
                    var commands = editor.commands.commands;
                    var platform = editor.commands.platform;
                    html += '<ul>';
                    for (var editorName in commands) {
                        if (commands[editorName].hasOwnProperty('bindKey') && commands[editorName].bindKey[platform] > '') {
                            html += '<li>';
                            html += editorName + ' (' + commands[editorName].bindKey[platform] + ')';
                            html += '</li>';
                        }
                    }
                    html += '</ul>';
                }
                if (html == '<ul></ul>') {
                    return '';
                }
                return html;
            };
            $(menuElement).on("click", "a", function(event) {
                if ($(this).data("vpl-active")) {
                    var actionid = $(this).attr('id');
                    if (typeof actionid === 'string' && actionid.startsWith('vpl_ide_')) {
                        actionid = actionid.replace('vpl_ide_', '');
                    } else {
                        return true;
                    }
                    if (self.notAdded(actionid)) {
                        return true;
                    }
                    if (buttons[actionid] && !buttons[actionid].active) {
                        return true;
                    }
                    var action = self.getAction(actionid);
                    if (actionid != 'import') {
                        setTimeout(action, 10);
                    } else {
                        action();
                    }
                }
                event.stopImmediatePropagation();
                return false;
            });

            $('body').on('keydown', function(event) {
                var check = false;
                var strkey = '';
                if (event.shiftKey) {
                    strkey += 'shift-';
                }
                if (event.altKey) {
                    strkey += 'alt-';
                    check = true;
                }
                if (event.ctrlKey) {
                    strkey += 'ctrl-';
                    check = true;
                }
                if (event.metaKey) {
                    strkey += 'meta-';
                    check = true;
                }
                if (event.which >= 112 && event.which <= 123) {
                    strkey += 'f' + (event.which - 111);
                    check = true;
                } else {
                    var char = String.fromCharCode(event.which).toLowerCase();
                    if (char < 'a' || char > 'z') {
                        check = false;
                    } else {
                        strkey += char;
                    }
                }
                if (check) {
                    for (var buttonName in buttons) {
                        if (buttons[buttonName].hasOwnProperty('key')) {
                            if (strkey == buttons[buttonName].key.toLowerCase()) {
                                event.preventDefault();
                                event.stopImmediatePropagation();
                                buttons[buttonName].action();
                                return false;
                            }
                        }
                    }
                }
                return true;
            });
            this.multiple = function(v, m) {
                return v - (v % m);
            };

            (function() {
                var start = 0;
                var lastLap = 0;
                var interval = false;
                var hour = 60 * 60;
                var day = hour * 24;
                var cssclasses = 'vpl_buttonleft_orange vpl_buttonleft_red vpl_buttonleft_black';
                var show = false;
                var element = null;
                var precision = 5;
                var checkt = 1000;
                var timeLeft = 0;
                var updatePrecision = function(timeLeft) {
                    precision = 5;
                    checkt = 1000;
                    if (timeLeft > hour) {
                        precision = 60;
                        checkt = 5000;
                    } else if (timeLeft > day) {
                        precision = 5 * 60;
                        checkt = 5 * 5000;
                    }
                };
                var updateTimeLeft = function() {
                    var now = self.multiple(VPLUtil.getCurrentTime(), precision);
                    if (now === lastLap || element === null) {
                        return;
                    }
                    lastLap = now;
                    var tl = timeLeft - (lastLap - start);
                    var cssclass = '';
                    if (tl <= 0) {
                        cssclass = 'vpl_buttonleft_black';
                    } else if (tl <= 5 * 60) {
                        show = true;
                        cssclass = 'vpl_buttonleft_red';
                    } else if (tl <= 15 * 60) {
                        cssclass = 'vpl_buttonleft_orange';
                    } else {
                        updatePrecision(tl);
                    }
                    var thtml = '<span class="' + cssclass + '">' + VPLUtil.genIcon('timeleft');
                    if (show) {
                        thtml += ' ' + VPLUtil.getTimeLeft(tl);
                    }
                    thtml += '</span>';
                    element.html(thtml);
                    element.removeClass(cssclasses);
                    element.addClass(cssclass);
                };
                self.toggleTimeLeft = function() {
                    show = !show;
                    lastLap = 0;
                    updateTimeLeft();
                };
                self.setTimeLeft = function(options) {
                    element = $('#vpl_ide_timeleft');
                    if (interval !== false) {
                        clearInterval(interval);
                        interval = false;
                    }
                    if (options.hasOwnProperty('timeLeft')) {
                        timeLeft = options.timeLeft;
                        updatePrecision(timeLeft);
                        var sync = timeLeft % precision;
                        timeLeft = self.multiple(timeLeft, precision);
                        start = self.multiple(VPLUtil.getCurrentTime(), precision);
                        lastLap = start - precision;
                        setTimeout(function() {
                            interval = setInterval(updateTimeLeft, checkt);
                        }, sync * 1000);
                        $('#vpl_ide_timeleft').show();
                        updateTimeLeft();

                    } else {
                        $('#vpl_ide_timeleft').hide();
                    }
                };
            })();
        };
        window.VPLIDEButtons = VPLIDEButtons;
        return VPLIDEButtons;
    }
);
