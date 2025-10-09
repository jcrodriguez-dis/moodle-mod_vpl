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
 * JavaScript functions to help grade form
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino, 2021 Astor Bizard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
/**
 * Apply grade reduction comments to given grade.
 * @param {Number} grade Original grade.
 * @param {String} comments Comments with some grade reduction.
 * @returns {Number} The reduced grade.
 */
function reduceGradeWithComments(grade, comments) {
    var regDiscount = /^-.+\((-[0-9.]+)\)\s*$/gm;
    var match;
    while ((match = regDiscount.exec(comments)) !== null) {
        var rest = parseFloat(match[1]);
        if (rest < 0) {
            grade += rest;
        }
    }
    return grade;
}

/**
 * Format grade (in points) to a displayable float.
 * @param {Number} grade Grade to be formatted.
 * @returns {Number} The formatted grade.
 */
function formatGrade(grade) {
    // No negative grade.
    if (grade < 0) {
        return 0;
    }
    // Max two decimal points.
    return Math.round(100 * grade) / 100;
}

/**
 * Init method for grade buttons (Merge grade, Calculate).
 */
function setupGradeButtons() {
    // Calculate button.
    $('button[data-role="calculategrade"]').click(function() {
        // Recalculate numeric grade from the max grade,
        // substracting points found at the end of lines.
        // Valid reduction format: "- text (-grade)".
        var $form = $(this).closest('form');
        var text = $form.find('[name="comments"]').val();
        var grade = parseFloat($(this).data('maxgrade'));
        grade = formatGrade(reduceGradeWithComments(grade, text));
        $form.find('[name="grade"]').val(grade);
    });

    // Merge grade button.
    $('button[data-role="mergegrade"]').click(function() {
        // Merge numeric grade from the proposed grade and advancedgrading grid,
        // substracting points found at the end of lines.
        // Valid reduction format: "- text (-grade)".
        var $form = $(this).closest('form');
        var gridpoints = 0;
        if ($form.find('.score .scorevalue').length > 0) {
            $form.find('.checked .score .scorevalue')
            .each(function() {
                gridpoints += Number($(this).text());
            });
        } else {
            $form.find('.score input')
            .each(function() {
                gridpoints += Number($(this).val());
            });
        }
        var advancedgradingcomments = '';
        $form.find('.criterion .remark textarea')
        .each(function() {
            advancedgradingcomments += $(this).val() + "\n";
        });
        gridpoints = reduceGradeWithComments(gridpoints, advancedgradingcomments);

        var maxgrade = $(this).data('maxgrade');
        var currentgrade = $(this).data('currentgrade');
        var maxgridpoints = $(this).data('maxgridpoints');

        var grade = formatGrade(currentgrade - maxgridpoints * (currentgrade / maxgrade) + gridpoints);
        $form.find('[name="grade"]').val(grade);

        var $comments = $form.find('[name="comments"]');
        var text = $comments.val();

        var gridtag = '#Grid grade';
        var gridcomment = gridtag + ': ' + grade;
        if (text.search(gridtag) < 0) {
            text = gridcomment + "\n" + text;
        } else {
            text = text.replace(new RegExp(gridtag + '.*'), gridcomment);
        }
        var proposedtag = '#Proposed grade';
        var proposedcomment = proposedtag + ': ' + currentgrade;
        if (text.search(proposedtag) < 0) {
            text = proposedcomment + "\n" + text;
        } else {
            text = text.replace(new RegExp(proposedtag + '.*'), proposedcomment);
        }

        $comments.val(text);
    });
}

/**
 * Init method for import buttons.
 */
function setupImportButtons() {
    $('button[data-role="importfromsub"]').click(function() {
        var $form = $(this).closest('form');
        // Set grade and comments.
        $form.find('[name="grade"]').val($(this).data('grade'));
        $form.find('[name="comments"]').val($(this).data('comments'));

        // Set advanced grading data:

        // First, reset the fields.
        $('#advancedgrading-criteria .criterion .remark textarea').val('');
        $('#advancedgrading-criteria .criterion .score input').val('');
        // Handle rubric level selection slightly differently:
        // we trigger a click on it, so it updates its UI correctly.
        $('#advancedgrading-criteria .criterion .level.checked').click();

        // Then, set the fields to their new values.
        $(this).data('advgrading').forEach(function(criterion) {
            var id = criterion.criterionid;
            if (typeof (criterion.remark) !== 'undefined') {
                $form.find('[name="advancedgrading[criteria][' + id + '][remark]"]').val(criterion.remark);
            }
            if (typeof (criterion.score) !== 'undefined') {
                $form.find('[name="advancedgrading[criteria][' + id + '][score]"]').val(parseFloat(criterion.score));
            }
            // Handle rubric level selection slightly differently:
            // we trigger a click on it, so it updates its UI correctly.
            if (typeof (criterion.levelid) !== 'undefined') {
                $('#advancedgrading-criteria-' + id + '-levels-' + criterion.levelid).click();
            }
        });
    });
}

/**
 * Setup the grade form JS features.
 */
export const setup = () => {
    setupGradeButtons();
    setupImportButtons();
};

/**
 * Update the submission list in the opener window with the new grade data.
 * If nexturl is given, go to the next submission after updating.
 * @param {Number} submissionID The submission being graded.
 * @param {Object} gradeData The new grade data.
 * @param {String} nexturl URL of the next submission to be graded (if any).
 */
export const updateSubmissionsList = (submissionID, gradeData, nexturl) => {
    if (opener !== null) {
        $(opener.document).find('#g' + submissionID).html(gradeData.grade);
        $(opener.document).find('#m' + submissionID).html(gradeData.grader);
        $(opener.document).find('#o' + submissionID).html(gradeData.gradedon);
        $(opener.document).find('#c' + submissionID).html(gradeData.comments);
        $(opener.document).find('.gd' + submissionID).css('color', '').css('backgroundColor', '');
    }
    if (nexturl) {
        if (opener === null) {
            window.close();
        }
        var nextrow = $(opener.document).find('#g' + submissionID).closest('tr').next();
        if (nextrow.length == 0) {
            window.close();
        }
        var nextid = nextrow.html().match(/user\/view\.php\?(.*?)id=([0-9]+)/)[2];
        if (nextid) {
            location.replace(nexturl + nextid);
        } else {
            window.close();
        }
    }
};

/**
 * Highlight the submission being graded in the opener window.
 * @param {Number} submissionID The submission being graded.
 */
export const highlightSubmission = (submissionID) => {
    if (opener !== null) {
        $(opener.document).find('.gd' + submissionID).css('color', 'black').css('backgroundColor', 'yellow');
        window.onunload = function() {
            $(opener.document).find('.gd' + submissionID).css('color', '').css('backgroundColor', '');
        };
    }
};
