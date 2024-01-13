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
 * JavaScript function to Show/hide div and change text ([+] <=> [-]).
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 **/

/* globals VPL: true */

(function() {
    if (typeof VPL != 'object') {
        VPL = {};
    }

    VPL.showHideDiv = function(id) {
        var button_show = window.document.getElementById('vpl_shb' + id + 's');
        var button_hide = window.document.getElementById('vpl_shb' + id + 'h');
        var content = window.document.getElementById('vpl_shc' + id);
        if (content) {
            if (content.style.display == 'none') {
                content.style.display = '';
                button_show.style.display = 'none';
                button_hide.style.display = '';
            } else {
                content.style.display = 'none';
                button_show.style.display = '';
                button_hide.style.display = 'none';
            }
        }
    };
})();
