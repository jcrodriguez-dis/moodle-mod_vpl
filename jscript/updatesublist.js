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
     * Returns opener or current window.
     * @returns {Window} opener or current window
     */
    function getWindow() {
        if (opener === null) {
            return window;
        } else {
            return opener;
        }
    }
    /**
     * Get the tag type element ancestor of a node
     * @param {Node} node Node to get tr ancestor
     * @returns {Node} td element
     */
    function getAncestor(node, typeName) {
        while (node && node.tagName != typeName) {
            node = node.parentNode;
        }
        return node;
    }

    /**
     * Get the tr element ancstro of a node
     * @param {Node} node Node to get tr ancestor
     * @returns {Node} tr element
     */
    function getTr(node) {
        return getAncestor(node, 'TR');
    }
    /**
     * Get the td element ancestor of a node
     * @param {Node} node Node to get td ancestor
     * @returns {Node} td element
     */
    function getTd(node) {
        return getAncestor(node, 'TD');
    }
    /**
     * Get nodes to highlight
     * @param {number} subid submission identification
     */
    function getNodes(subid) {
        var win = getWindow();
        var ssubid = "" + subid;
        var tdgrade = getTd(win.document.getElementById('g' + ssubid));
        var tdgrader = getTd(win.document.getElementById('m' + ssubid));
        var tdgradeon = getTd(win.document.getElementById('o' + ssubid));
        return [tdgrade, tdgrader, tdgradeon];
    }
    /**
     * Get table row node
     * @param {number} subid submission identification
     * @returns {Node} tr element
     */
    VPL.getTableRow = function(subid) {
        var win = getWindow();
        var ssubid = "" + subid;
        return getTr(win.document.getElementById('g' + ssubid));
    };
    /**
     * Hide all table rows
     * @param {number} subid submission identification
     */
    VPL.hideTableRows = function(subid) {
        var win = getWindow();
        var ssubid = "" + subid;
        var table = getAncestor(win.document.getElementById('g' + ssubid), 'TABLE');
        var list = table.querySelectorAll('tr');
        for (let i = 1; i < list.length; i++) {
            list[i].classList.add('vpl_hidden_evaluation_row');
        }
    };
    /**
     * Show table row
     * @param {number} subid submission identification
     */
    VPL.showTableRow = function(subid) {
        var row = VPL.getTableRow(subid);
        if (row) {
            row.classList.remove('vpl_hidden_evaluation_row');
        }
    };
    /**
     * Highlight table elements evaluating
     * @param {number} subid submission identification
     * @param {string} cssClass css class to add default vpl_hl_evaluation_row
     */
    VPL.hlrow = function(subid, cssClass) {
        if (typeof cssClass === 'undefined') {
            cssClass = 'vpl_hl_evaluation_row';
        }
        var nodes = getNodes(subid);
        var node;
        for (node of nodes) {
            if (node) {
                node.classList.add(cssClass);
            }
        }
        if (node) {
            node.scrollIntoView({block: 'nearest', behavior: 'smooth'});
        }
    };

    /**
     * Unhighlight elements highlighted with hlrow
     * @param {number} subid submission identification
    */
    VPL.unhlrow = function(subid) {
        var nodes = getNodes(subid);
        for (let node of nodes) {
            if (node) {
                node.classList.remove('vpl_hl_evaluation_row');
            }
        }
        VPL.hlrow(subid, 'vpl_finished_evaluation_row');
    };

    /**
     * Update submission list grade
     * @param {number} subid Submission identification
     * @param {string} grade Grade get
     * @param {string} grader Grader name
     * @param {string} gradeon Grade date
    */
    VPL.updatesublist = function(subid, grade, grader, gradeon) {
        var win = getWindow();
        VPL.unhlrow(subid);
        var ssubid = "" + subid;
        var tdgrade = win.document.getElementById('g' + ssubid);
        var tdgrader = win.document.getElementById('m' + ssubid);
        var tdgradeon = win.document.getElementById('o' + ssubid);
        if (tdgrade && typeof grade != 'undefined') {
            tdgrade.innerHTML = grade;
        }
        if (tdgrader && typeof grader != 'undefined') {
            tdgrader.innerHTML = grader;
        }
        if (tdgradeon && typeof gradeon != 'undefined') {
            tdgradeon.innerHTML = gradeon;
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
