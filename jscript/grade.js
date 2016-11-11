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
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

(function() {
    if (typeof VPL != 'object') {
        VPL = new Object();
    }
    VPL.getOffsetY = function(obj) {
        var offset = 0;
        var i;
        for (i = 0; i < 200 && obj != document.body; i++) {
            offset += obj.offsetTop;
            obj = obj.offsetParent;
        }
        return offset;
    };
    /**
     * resize the submission view div to greatest visible size
     */

    VPL.resizeSView = function() {
        var grade_view = window.document.getElementById('vpl_grade_view');
        var comments_view = window.document.getElementById('vpl_grade_comments');
        var textarea = window.document.getElementsByTagName('textarea')[0];
        var form_view = window.document.getElementById('vpl_grade_form');
        var submission_view = window.document.getElementById('vpl_submission_view');
        if (grade_view && comments_view && form_view && submission_view && textarea) {
            textarea.style.resize="both";
            form_view.style.width = (textarea.offsetWidth+8)+ 'px';
            grade_view.style.height = form_view.scrollHeight + 'px';
            comments_view.style.height = form_view.scrollHeight + 'px';
            comments_view.style.width = (grade_view.scrollWidth - form_view.scrollWidth - 8) + 'px';
            var newHeight;
            if (window.innerHeight) {
                newHeight = window.innerHeight - VPL.getOffsetY(submission_view) - 35;
            } else {
                newHeight = document.documentElement.clientHeight - VPL.getOffsetY(submission_view) - 35;
            }
            if(newHeight < 300) {
                newHeight = 300;
            }
            submission_view.style.height = newHeight + 'px';
        }
    };

    /* Set the resize controler */

    VPL.resizeSView();
    setInterval(VPL.resizeSView, 1000);
    /**
     * Recalculate numeric grade from the max sustracting grades found at the
     * end of lines. valid grade format: "- text (-grade)"
     */
    VPL.calculateGrade = function(maxgrade) {
        var form1 = window.document.getElementById('form1');
        var text = new String(form1.comments.value);
        var grade = new Number(maxgrade);
        while (text.length > 0) {
            /* Separate next line */
            var line = new String();
            var i;
            for (i = 0; i < text.length; i++) {
                if (text.charAt(i) == '\n' || text.charAt(i) == '\r') {
                    break;
                }
            }
            line = text.substr(0, i);
            if (i < text.length) {
                text = text.substr(i + 1, (text.length - i) - 1);
            } else {
                text = '';
            }
            if (line.length == 0) {
                continue;
            }

            /* Is a message title line */
            if (line.charAt(0) == '-') {
                var nline = new String();
                for (i = 0; i < line.length; i++) {
                    if (line.charAt(i) != ' ') {
                        nline += line.charAt(i);
                    }
                }
                if (nline.length == 0) {
                    continue;
                }
                /* End of line format (-grade) */
                if (nline.charAt(nline.length - 1) == ')') {
                    var pos = nline.lastIndexOf('(');
                    if (pos == -1) {
                        continue;
                    }
                    var rest = nline.substr(pos + 1, nline.length - 2 - pos);
                    /* update grade with rest */
                    if (rest < 0) {
                        grade += new Number(rest);
                    }
                }
            }
        }
        /* No negative grade */
        if (grade < 0) {
            grade = 0;
        }
        /* Max two decimal points */
        grade = Math.round(100 * grade) / 100;
        form1.grade.value = grade;
    };

    /**
     * Add new comment to the form comment string to add
     */
    VPL.addComment = function(comment) {
        if (comment == '') {
            return;
        }
        comment = '-' + comment;
        var form1 = window.document.getElementById('form1');
        var field = form1.comments;
        var text = new String(field.value);
        if (text.indexOf(comment, 0) >= 0) { /* Comment already in form */
            return;
        }
        if (document.selection) { /* For MS Explorer */
            field.focus();
            var sel = document.selection.createRange();
            sel.text = comment;
        } /* For Firefox */
        else if (field.selectionStart || field.selectionStart == '0') {
            var startPos = field.selectionStart;
            var endPos = field.selectionEnd;
            field.value = text.substring(0, startPos) + comment + text.substring(endPos, text.length);
        } else { /* Other case */
            field.value += comment;
        }
    };
    VPL.removeHeaderFooter = function() {
        var l = window.document.getElementsByTagName('header');
        for (var i = 0; i < l.length; i++) {
            l[i].style.display = 'none';
        }
        l = window.document.getElementsByTagName('footer');
        for (var i = 0; i < l.length; i++) {
            l[i].style.display = 'none';
        }
    };
})();
