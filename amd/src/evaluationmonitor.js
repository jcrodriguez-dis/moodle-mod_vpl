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
 * Evaluation monitoring
 *
 * @copyright 2013 onward Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/* globals VPL */

import {VPLUI} from 'mod_vpl/vplui';

/**
 * Evaluate a submission
 * @param {*} options {nexturl: url to go next, ajaxurl: url to evaluate}
 */
export const init = (options) => {
    options.next = function() {
        window.location = options.nexturl;
    };

    /**
     * Show a error message in a modal dialog.
     * Allows to go next evaluation.
     *
     * @param {string} message Message to shohw in dialog.
     */
    function showErrorMessage(message) {
        VPLUI.showErrorMessage(message, {
            next: options.next
        });
    }

    var action;
    var executionActions = {
        'ajaxurl': options.ajaxurl,
        'run': showErrorMessage,
        'getLastAction': function() {
            action();
        },
    };

    action = function() {
        VPLUI.requestAction('evaluate', 'evaluating', {}, options.ajaxurl)
        .done(
                function(response) {
                    VPLUI.webSocketMonitor(response, 'evaluate', 'evaluating', executionActions)
                    .done(options.next)
                    .fail(showErrorMessage);
                }
        )
        .fail(showErrorMessage);
    };
    action();
};
/**
 * Evaluation for multiple students
 * @param {object} options {baseurl: Base URL for the evaluation}
 */
export const multievaluation = (options) => {
    var baseurl = options.baseurl;
    var goon = true;

    /**
     * Get grade from result
     * @param {object} result Result object
     * @returns {string} Grade
     */
    function getGrade(result) {
        var grade;
        if (typeof result == 'undefined' || typeof result.grade === 'undefined' || result.grade === null) {
            grade = '⛔';
        } else {
            grade = '👉 ' + result.grade;
        }
        return grade;
    }

    /**
     * Evaluate a student
     * @param {number} id Student id
     * @param {number} subid Submission id
     */
    async function evaluateStudent(id, subid) {
        var ajaxurl = baseurl + id + '&action=';
        return new Promise(
            (resolve, reject) => {
                var ok = () => {
                    resolve(true);
                };
                var cancel = () => {
                    VPL.updatesublist(subid, getGrade());
                    goon = false;
                    reject(false);
                };
                var action;
                var showErrorMessage = function(message) {
                    VPLUI.showErrorMessage(message, {
                        closeOnEscape: false,
                        close: function() {
                            VPL.unhlrow(subid);
                        },
                        stop: cancel,
                        next: function() {
                            VPL.updatesublist(subid, getGrade());
                            ok();
                        },
                    });
                };
                var executionActions = {
                    'ajaxurl': ajaxurl,
                    'run': showErrorMessage,
                    'setResult': function(result) {
                        VPL.updatesublist(subid, getGrade(result));
                    },
                    'getLastAction': function() {
                        action();
                    },
                };
                action = function() {
                    VPL.hlrow(subid);
                    VPLUI.requestAction('evaluate', 'evaluating', {}, ajaxurl)
                    .done(
                            function(response) {
                                VPLUI.webSocketMonitor(response, 'evaluate', 'evaluating', executionActions)
                                .done(ok)
                                .fail(showErrorMessage);
                            }
                    )
                    .fail(showErrorMessage);
                };
                action();
            }
        );
    }

    /**
     * Evaluate all students
     */
    async function evaluateStudents() {
        var students = VPL.evaluateStudents;
        var nstudents = students.length;
        for (var i = 0; i < nstudents; i++) {
            var student = students[i];
            if (i === 0) {
                VPL.hideTableRows(student.subid);
            }
            var firstTD = VPL.getTableRow(student.subid).querySelector('td');
            firstTD.innerHTML = (i + 1) + '/' + nstudents;
            VPL.showTableRow(student.subid);
            try {
                await evaluateStudent(student.id, student.subid);
            } catch (e) {
                VPL.unhlrow(student.subid);
                VPL.updatesublist(student.subid, getGrade());
                if (!goon) {
                    break;
                }
            }
        }
    }
    evaluateStudents();
};
