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
 
/* globals VPL: true */
 
(function() {
    if (typeof VPL != 'object') {
        VPL = {};
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
            textarea.style.resize = "both";
            form_view.style.width = (textarea.offsetWidth + 8) + 'px';
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
        var text = form1.comments.value;
        var grade = parseFloat(maxgrade);
        var regDiscount = /^-.+\(([0-9\.\-]+)\) *$/gm;
        var match;
        while((match = regDiscount.exec(text)) !== null) {
            var rest = parseFloat(match[1]);
                    if (rest < 0) {
                grade += rest;
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
     * Merge numeric grade from the proposed grade and advancedgrading
     * end of lines. valid grade format: "- text (-grade)"
     */
    VPL.mergeGrade = function(maxgrade,vplgrade,gridscore) {
        var form1 = window.document.getElementById('mform1');
        var fieldcomments = form1.comments;
        var text = new String(fieldcomments.value);
        var grade = 0;
        var rubricvalues = window.document.querySelectorAll("#mform1 .checked .score .scorevalue");
        var find = false;
        rubricvalues.forEach(function(item){
            grade += new Number(item.textContent);
        });
        grade = vplgrade - ((vplgrade*gridscore/maxgrade)- grade)
        var proposedcomment = '#Proposed grade : ' +vplgrade;
        var gridcomment = '#Grid grade : ' +grade;
        if (text.search('#Proposed grade')<0 ) {
            text += proposedcomment+"\n";
        }else{
            text = text.replace(/#Proposed grade.*/,proposedcomment);  
        }
        if (text.search('#Grid grade') <0) {
            text += gridcomment+"\n";
        }else{
            text = text.replace(/#Grid grade.*/,gridcomment);  
        }
               
        fieldcomments.value = text;
        /* Max two decimal points */
        grade = Math.round(100 * grade) / 100;
        form1.grade.value = grade;
    };
    /**
     * Add new comment to the form comment string to add
     */
    VPL.addComment = function(comment) {
        if (comment === '') {
            return;
        }
        comment = '-' + comment;
        var form1 = window.document.getElementById('mform1');
        var field = form1.comments;
        var text = field.value;
        if (text.indexOf(comment, 0) >= 0) { /* Comment already in form */
            return;
        }
        if (document.selection) { /* For MS Explorer */
            field.focus();
            var sel = document.selection.createRange();
            sel.text = comment;
        } /* For Firefox */
        else if (field.selectionStart || field.selectionStart === 0) {
            var startPos = field.selectionStart;
            var endPos = field.selectionEnd;
            if(startPos != endPos) {
                field.value = text.substring(0, startPos) + comment + text.substring(endPos, text.length);
            } else {
                var pos = text.substr(startPos).indexOf("\n");
                if (pos == -1){
                    pos = text.length;
                } else {
                    pos += startPos;
                }
                if ( pos > 0 ) {
                    comment = '\n' + comment;
                }
                field.value = text.substring(0, pos) + comment + text.substring(pos, text.length);
            }
        } else { /* Other case */
                   if(text > '' && text.substr(-1) != '\n'){
                comment = '\n' + comment;
            }
            field.value += comment;
        }
    };
    VPL.removeHeaderFooter = function() {
        var i;
        var l = window.document.getElementsByTagName('header');
        for (i = 0; i < l.length; i++) {
            l[i].style.display = 'none';
        }
        l = window.document.getElementsByTagName('footer');
        for (i = 0; i < l.length; i++) {
            l[i].style.display = 'none';
        }
    };
})();
