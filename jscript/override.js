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
 * JavaScript functions to help override form
 * @package mod_vpl
 * @copyright 2021 Astor Bizard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* globals VPL: true */

(function() {
    if (typeof VPL != 'object') {
        VPL = {};
    }
    var usersForm = 'vpl_override_users_form';
    var optionsForm = 'vpl_override_options_form';
    VPL.submitOverrideForms = function() {
        var users = [];
        var groups = [];
        for(var [name, value] of new FormData(document.getElementById(usersForm))) {
            if (name == 'users[]') {
                users.push(value);
            } else if (name == 'groups[]') {
                groups.push(value);
            }
        };
        document.querySelector('#' + optionsForm + ' [name=userids]').value = users.join(',');
        document.querySelector('#' + optionsForm + ' [name=groupids]').value = groups.join(',');
        document.querySelector('#' + optionsForm + ' [name=submitbutton]').click();
    };
    VPL.cancelOverrideForms = function() {
        document.querySelector('#' + optionsForm + ' [name=cancel]').click();
    };
})();
