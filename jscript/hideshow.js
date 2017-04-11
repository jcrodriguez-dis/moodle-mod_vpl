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

    VPL.show_hide_div = function (id){
        var text = window.document.getElementById('sht' + id);
        var div = window.document.getElementById('shd' + id);
        if(text){
            if(text.innerHTML == '[+]'){
                div.style.display = '';
                text.innerHTML = '[-]';
            }else{
                div.style.display = 'none';
                text.innerHTML = '[+]';
            }
        }
    };
    VPL.hide_later = function (){
        var div = window.document.getElementById('vpl.hide');
        if(div){
            div.style.transition = 'display 2s';
            div.style.display = 'none';
        }
    };
    setTimeout(VPL.hide_later, 5000);
})();
