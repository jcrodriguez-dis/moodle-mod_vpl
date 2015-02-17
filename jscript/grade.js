/**
 * @version		$Id: grade.js,v 1.6 2012-06-19 10:47:01 juanca Exp $
 * @package mod_vpl. JavaScript functions to help grade form
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

(function(){
	if (typeof VPL != 'object') {
		VPL = new Object();
	}
	VPL.getOffsetY = function (obj){
		var offset=0;
		var i;
		for(i=0; i<200 && obj != document.body; i++){
			offset += obj.offsetTop;
			obj=obj.offsetParent;
		}
		return offset;
	};
	/**
	* resize the submission view div to greatest visible size 
	*/

	VPL.resizeSView = function (){
		var gview=window.document.getElementById('vpl_grade_view');
		var cview=window.document.getElementById('vpl_grade_comments');
		var fview=window.document.getElementById('vpl_grade_form');
		var sview=window.document.getElementById('vpl_submission_view');
		if(gview && cview && fview && sview){
			gview.style.height=fview.scrollHeight+'px';
			cview.style.height=fview.scrollHeight+'px';
			cview.style.width=(gview.scrollWidth-fview.scrollWidth-8)+'px';
			var newHeight;
			if(window.innerHeight)
				newHeight = window.innerHeight
						-VPL.getOffsetY(sview)-35;
			else
				newHeight = document.documentElement.clientHeight
				-VPL.getOffsetY(sview)-35;
			sview.style.height = newHeight+'px';
		}
	};

	/* Set the resize controler */

	window.onresize=VPL.resizeSView;
	VPL.resizeSView();
	setInterval(VPL.resizeSView,3000);
	/**
	* Recalculate numeric grade from the max sustracting grades found at the end of
	* lines.
	* valid grade format: "- text (-grade)" 
	*/ 
	VPL.calculateGrade = function(maxgrade){
		var form1 = window.document.getElementById('form1');
		var text = new String(form1.comments.value);
		var grade = new Number(maxgrade);
		while(text.length > 0) {
			/* Separate next line*/
			var line = new String();
			var i;
			for(i=0; i < text.length; i++){
				if(text.charAt(i) == '\n') break;
				if(text.charAt(i) == '\r') break;
			}
			line = text.substr(0,i);
			if(i<text.length)
				text = text.substr(i+1,(text.length-i)-1);
			else
				text = '';
			if(line.length == 0) continue;
	
			/* Is a message title line */
			if(line.charAt(0) == '-'){
				var nline = new String();
				for(i=0; i < line.length; i++)
					if(line.charAt(i) != ' ') nline += line.charAt(i);
				if(nline.length == 0) continue;
				/* End of line format (-grade) */
				if(nline.charAt(nline.length-1) == ')'){
					var pos = nline.lastIndexOf('(');
					if(pos == -1) continue;
					var rest= nline.substr(pos+1,nline.length-2-pos);
					/* update grade with rest */
					if(rest < 0) grade += new Number(rest);
				}
			}
		}
		/*No negative grade*/
		if(grade <0) grade = 0;
		/*Max two decimal points*/
		grade = Math.round(100*grade)/100;
		form1.grade.value = grade;
	};

	/**
	 * Add new comment to the form
	 * comment string to add
	 */
	VPL.addComment = function(comment) {
		if(comment=='') return;
		comment = '-'+comment;
		var form1 = window.document.getElementById('form1');
		var field = form1.comments;
		var text = new String(field.value);
		if(text.indexOf(comment,0) >=0) { /*Comment already in form*/
			return;
		}
		if (document.selection) { /* For MS Explorer */
			field.focus();
			var sel = document.selection.createRange();
			sel.text = comment;
		} /* For Firefox */
		else if (field.selectionStart || field.selectionStart == '0'){
			var startPos = field.selectionStart;
			var endPos = field.selectionEnd;
			field.value = text.substring(0, startPos)+ comment+ text.substring(endPos, text.length);
		} else { /* Other case */
			field.value += comment;
		}
	};
})();
