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
 * JavaScript functions to update submission list grade
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/* globals VPL: true */

(function() {
    if (typeof VPL != 'object') {
        VPL = {};
    }
    /**
     * Highlight row
     * @param {number} subid submission identification
     */
    VPL.hlrow = function(subid) {
        if (opener === null) {
            return;
        }
        var ssubid = "" + subid;
        var divgrade = opener.document.getElementById('g' + ssubid);
        var divgrader = opener.document.getElementById('m' + ssubid);
        var divgradeon = opener.document.getElementById('o' + ssubid);
        if (divgrade) {
            divgrade.style.backgroundColor = 'yellow';
            divgrade.style.color = 'black';
        }
        if (divgrader) {
            divgrader.style.backgroundColor = 'yellow';
            divgrader.style.color = 'black';
        }
        if (divgradeon) {
            divgradeon.style.backgroundColor = 'yellow';
            divgradeon.style.color = 'black';
        }
    };

    /**
     * Unhighlight row
     * @param {number} subid submission identification
    */
    VPL.unhlrow = function(subid) {
        if (opener === null) {
            return;
        }
        var ssubid = "" + subid;
        var divgrade = opener.document.getElementById('g' + ssubid);
        var divgrader = opener.document.getElementById('m' + ssubid);
        var divgradeon = opener.document.getElementById('o' + ssubid);
        if (divgrade) {
            divgrade.style.backgroundColor = '';
            divgrade.style.color = '';
        }
        if (divgrader) {
            divgrader.style.backgroundColor = '';
            divgrader.style.color = '';
        }
        if (divgradeon) {
            divgradeon.style.backgroundColor = '';
            divgradeon.style.color = '';
        }
    };

    /**
     * Update submission list grade
     * @param {number} subid Submission identification
     * @param {string} grade Grade get
     * @param {string} grader Grader name
     * @param {string} gradeon Grade date
    */
    VPL.updatesublist = function(subid, grade, grader, gradeon) {
        if (opener === null) {
            return;
        }
        var ssubid = "" + subid;
        var divgrade = opener.document.getElementById('g' + ssubid);
        var divgrader = opener.document.getElementById('m' + ssubid);
        var divgradeon = opener.document.getElementById('o' + ssubid);
        if (divgrade) {
            divgrade.innerHTML = grade;
            divgrade.style.backgroundColor = '';
            divgrade.style.color = '';
        }
        if (divgrader) {
            divgrader.innerHTML = grader;
            divgrader.style.backgroundColor = '';
            divgrader.style.color = '';
        }
        if (divgradeon) {
            divgradeon.innerHTML = gradeon;
            divgradeon.style.backgroundColor = '';
            divgradeon.style.color = '';
        }
    };

    /**
     * Go to next submission
     * @param {number} subid submission id
     * @param {string} url base of next
    */
    VPL.goNext = function(subid, url) {
        if (opener === null) {
            window.close();
        }
        var ssubid = "" + subid;
        var divnext = opener.document.getElementById('n' + ssubid);
        if (divnext) {
            location.replace(url + divnext.innerHTML);
        } else {
            window.close();
        }
    };
})();
