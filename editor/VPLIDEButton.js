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
 * @package mod_vpl
 * @copyright 2016 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/* globals VPL_IDEButtons: true */
/* globals VPL_Util */
/* globals $JQVPL */

(function() {
    if (!window.VPL_IDEButtons) {
        VPL_IDEButtons = function(menu_element, isOptionAllowed) {
            var self = this;
            var buttons = {};

            this.noAdded = function(button) {
                return !buttons[button];
            };
            this.setText = function(button, icon, title) {
                if (self.noAdded(button)) {
                    return;
                }
                if (!icon) {
                    icon = buttons[button].icon;
                }
                if (!title) {
                    title = buttons[button].title;
                }
                if (!title) {
                    title = VPL_Util.str(icon);
                }
                buttons[button].icon = icon;
                buttons[button].title = title;
                if (buttons[button].hasOwnProperty('key')) {
                    title += ' (' + buttons[button].key + ')';
                }
                $JQVPL('#vpl_ide_' + button).attr('title', title);
                $JQVPL('#vpl_ide_' + button + ' i').replaceWith(VPL_Util.gen_icon(icon));
            };
            this.setExtracontent = function(button, html) {
                if (self.noAdded(button)) {
                    return;
                }
                var cl = 'bt_extrahtml';
                var btag = $JQVPL('#vpl_ide_' + button + ' i');
                if (btag.find('.' + cl).length == 0) {
                    btag.append(' <span class="' + cl + '"><span>');
                }
                btag.find('.' + cl).html(html);
            };
            this.add = function(button) {
                if (typeof button === 'string') {
                    var name = button;
                    button = {
                        'name' : name
                    };
                }
                if (!isOptionAllowed(button.name)) {
                    return;
                }
                if (!button.hasOwnProperty('icon')) {
                    button.icon = button.name;
                }
                if (!button.hasOwnProperty('active')) {
                    button.active = true;
                }
                if (!button.hasOwnProperty('editorName')) {
                    button.editorName = button.name;
                }
                if (!button.hasOwnProperty('originalAction')) {
                    button.originalAction = VPL_Util.doNothing;
                }
                if (self.noAdded(button)) {
                    buttons[button.name] = button;
                } else {
                    throw "Button already set " + button.name;
                }
                self.setAction(button.name, button.originalAction);
                if (button.hasOwnProperty('bindKey')) {
                    button.command = {
                        name : button.editorName,
                        bindKey : button.bindKey,
                        exec : button.action
                    };
                }
            };
            this.getHTML = function(button) {
                if (self.noAdded(button)) {
                    return '';
                } else {
                    var html = "<a id='vpl_ide_" + button + "' href='#' title='" + VPL_Util.str(button) + "'>";
                    html += VPL_Util.gen_icon(button) + "</a>";
                    return html;
                }
            };

            this.enable = function(button, active) {
                if (self.noAdded(button)) {
                    return '';
                }
                buttons[button].active = active;
                $JQVPL('#vpl_ide_' + button).button(active ? 'enable' : 'disable');
            };
            this.setAction = function(button, action) {
                if (self.noAdded(button)) {
                    return;
                }
                buttons[button].originalAction = action;
                buttons[button].action = function() {
                    if (buttons[button].active) {
                        action();
                    }
                };
            };
            this.getAction = function(button) {
                if (self.noAdded(button)) {
                    return VPL_Util.doNothing;
                }
                return buttons[button].action;
            };
            this.launchAction = function(button) {
                if (self.noAdded(button)) {
                    return;
                }
                buttons[button].originalAction();
            };
            this.setGetkeys = function(editor) {
                if (editor) {
                    var commands = editor.commands.commands;
                    var platform = editor.commands.platform;
                    for (var button in buttons) {
                        if ( buttons.hasOwnProperty(button) ) {
                            var editorName = buttons[button].editorName;
                            if (commands[editorName] && commands[editorName].bindKey && !buttons[button].Key) {
                                buttons[button].key = commands[editorName].bindKey[platform];
                                self.setText(button);
                            } else {
                                if (buttons[button].bindKey) {
                                    if (!buttons[button].hasOwnProperty('key')) {
                                        buttons[button].key = buttons[button].bindKey[platform];
                                        self.setText(button);
                                    }
                                }
                            }
                        }
                    }
                }
            };
            this.getShortcuts = function(editor) {
                var html = '<ul>';
                for (var button in buttons) {
                    if (buttons[button].hasOwnProperty('key')) {
                        html += '<li>';
                        html += buttons[button].title + ' (' + buttons[button].key + ')';
                        html += '</li>';
                    }
                }
                html += '</ul>';
                if (editor) {
                    html += '<h5>' + VPL_Util.str('edit') + '</h5>';
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
                return html;
            };
            $JQVPL(menu_element).on("click", "a", function(event) {
                var button = $JQVPL(this).attr('id');
                if (typeof button === 'string') {
                    button = button.replace('vpl_ide_', '');
                } else {
                    event.stopPropagation();
                    return false;
                }
                if (self.noAdded(button)) {
                    return;
                }
                var action = self.getAction(button);
                if (button != 'import') {
                    setTimeout(action, 10);
                } else {
                    action();
                    event.stopPropagation();
                    return false;
                }
            });

            $JQVPL('body').on('keydown', function(event) {
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
                    for (var button in buttons) {
                        if (buttons[button].hasOwnProperty('key')) {
                            if (strkey == buttons[button].key.toLowerCase()) {
                                event.preventDefault();
                                event.stopImmediatePropagation();
                                buttons[button].action();
                                return false;
                            }
                        }
                    }
                }
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
                var cssclases = 'vpl_buttonleft_orange vpl_buttonleft_red vpl_buttonleft_black';
                var show = false;
                var element = null;
                var precision = 5;
                var checkt = 1000;
                var timeLeft = 0;
                var update = function() {
                    var now = self.multiple(VPL_Util.getCurrentTime(), precision);
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
                    }
                    var thtml = VPL_Util.gen_icon('timeleft');
                    if (show) {
                        thtml += ' ' + VPL_Util.getTimeLeft(tl);
                    }
                    element.html(thtml);
                    element.removeClass(cssclases).addClass(cssclass);
                };
                self.toggleTimeLeft = function() {
                    show = !show;
                    lastLap = false;
                    update();
                };
                self.setTimeLeft = function(options) {
                    element = $JQVPL('#vpl_ide_timeleft span');
                    if (interval !== false) {
                        clearInterval(interval);
                        interval = false;
                    }
                    if (options.hasOwnProperty('timeLeft')) {
                        $JQVPL('#vpl_ide_timeleft').show();
                        precision = 5;
                        checkt = 1000;
                        timeLeft = options.timeLeft;
                        if (timeLeft > hour) {
                            precision = 60;
                            checkt = 5000;
                        }
                        if (timeLeft > day) {
                            precision = 5 * 60;
                        }
                        var sync = timeLeft % precision;
                        timeLeft = self.multiple(timeLeft, precision);
                        start = self.multiple(VPL_Util.getCurrentTime(), precision);
                        lastLap = start - 1;
                        update();
                        setTimeout( function() {
                            interval = setInterval(update, checkt);
                        }, sync * 1000);
                    } else {
                        $JQVPL('#vpl_ide_timeleft').hide();
                    }
                };
            })();
        };
    }
})();
