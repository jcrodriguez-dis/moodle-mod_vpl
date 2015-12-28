//This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
//VPL for Moodle is free software: you can redistribute it and/or modify
//it under the terms of the GNU General Public License as published by
//the Free Software Foundation, either version 3 of the License, or
//(at your option) any later version.
//
//VPL for Moodle is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
//GNU General Public License for more details.
//
//You should have received a copy of the GNU General Public License
//along with VPL for Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * IDE Menu
 * 
 * @package mod_vpl
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
(function() {
    // "use strict";
    if (!window.VPL_IDEButtons) {
        VPL_IDEButtons = function(menu_element, isOptionAllowed) {
            var self = this;
            var buttons = {};

            this.noAdded = function(button){
                return ! buttons[button];
            };
            this.setText= function(button, icon, title) {
                if ( self.noAdded(button) ) {
                    return;
                }
                if( ! icon ) {
                    icon = buttons[button].icon;
                }
                if (! title) {
                    title = buttons[button].title;
                }
                if (! title) {
                    title = VPL_Util.str(icon);
                }
                buttons[button].icon = icon;
                buttons[button].title = title;
                if ( buttons[button].hasOwnProperty( 'key' ) ) {
                    title += ' ('+buttons[button].key+')';
                }
                $JQVPL('#vpl_ide_' + button).attr('title', title);
                $JQVPL('#vpl_ide_' + button + ' .ui-button-text').html(VPL_Util.gen_icon(icon));
            };
            this.add = function(button) {
                if(typeof button === 'string' ) {
                    var name = button;
                    button = {'name':name};
                }
                if (! isOptionAllowed(button.name)) {
                    return;
                }
                if(! button.hasOwnProperty('icon')) {
                    button.icon = button.name;
                }
                if(! button.hasOwnProperty('active')) {
                    button.active = true;
                }
                if(! button.hasOwnProperty('editorName')) {
                    button.editorName = button.name;
                }
                if ( self.noAdded(button) ) {
                    buttons[button.name] = button;
                } else {
                    throw "Button already set "+button.name;
                }
                self.setAction(button.name,button.originalAction);
                if ( button.hasOwnProperty('bindKey') ) {
                    button.command = {
                        name:  button.editorName,
                        bindKey: button.bindKey,
                        exec: button.action
                    };
                }
            };
            this.getHTML = function(button) {
                if (self.noAdded(button) ) {
                    return '';
                } else {
                    var html = "<a id='vpl_ide_" + button + "' href='#' title='" + VPL_Util.str(button) + "'>";
                    html += VPL_Util.gen_icon(button) + "</a>";
                    return html;
                }                
            };
            
            this.enable = function(button, active) {
                if ( self.noAdded(button) ) {
                    return '';
                }
                buttons[button].active = active;
                $JQVPL('#vpl_ide_' + button).button(active ? 'enable' : 'disable');
            };
            this.setAction = function(button, action) {
                if ( self.noAdded(button) ) {
                    return;
                }
                buttons[button].originalAction = action;
                buttons[button].action = function(){
                    if( buttons[button].active ) {
                        action();
                    }
                };
            };
            this.getAction = function(button) {
                if ( self.noAdded(button) ) {
                    return VPL_Util.doNothing;
                }
                return buttons[button].action;
            };
            this.launchAction = function(button) {
                if ( self.noAdded(button) ) {
                    return;
                }
                buttons[button].originalAction();
            };
            this.setGetkeys = function(editor) {
                if( editor ) {
                    var commands = editor.commands.commands;
                    var platform = editor.commands.platform;
                    for ( var button in buttons ) {
                        var editorName = buttons[button].editorName;
                        if ( commands[editorName] && commands[editorName].bindKey &&  ! buttons[button].Key) {
                            buttons[button].key = commands[editorName].bindKey[platform];
                            self.setText(button);
                        } else {
                            if ( buttons[button].bindKey ) {
                                //editor.commands.addCommand(buttons[button].command);
                                if( ! buttons[button].hasOwnProperty( 'key' ) ) {
                                    buttons[button].key = buttons[button].bindKey[platform];
                                    self.setText(button);
                                }
                            }
                        }
                    }
                }
            };
            $JQVPL(menu_element).on("click", "a", function(event) {
                var button = $JQVPL(this).attr('id');
                if (typeof button === 'string') {
                    button = button.replace('vpl_ide_', '');
                } else {
                    event.stopPropagation();
                    return false;                    
                }
                if ( self.noAdded(button) ) {
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
                if( event.shiftKey ){
                    strkey += 'shift-';
                }
                if( event.altKey ){
                    strkey += 'alt-';
                    check = true;
                }
                if( event.ctrlKey ){
                    strkey += 'ctrl-';
                    check = true;
                }
                if( event.metaKey ){
                    strkey += 'meta-';
                    check = true;
                }
                if( event.which >= 112 && event.which <= 123) {
                    strkey += 'f'+ (event.which - 111);
                    check = true;
                } else {
                    var char = String.fromCharCode(event.which).toLowerCase();
                    if( char < 'a' || char > 'z' ) {
                        check = false;
                    } else {
                        strkey += char;                        
                    }
                }
                if( check ){
                    for ( var button in buttons ) {
                        if( buttons[button].hasOwnProperty( 'key' ) ) {
                            if ( strkey == buttons[button].key.toLowerCase() ) {
                                event.preventDefault();
                                event.stopImmediatePropagation();
                                buttons[button].action();
                                return false;
                            }
                        }
                    }
                }
            });
        };
    }
})();
