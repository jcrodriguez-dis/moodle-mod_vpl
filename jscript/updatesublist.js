/**
 * @version		$Id: updatesublist.js,v 1.5 2012-06-19 10:47:01 juanca Exp $
 * @package mod_vpl. JavaScript functions to update submission list grade
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
(function(){
	if (typeof VPL != 'object') {
		VPL = new Object();
	}
	/**
	 * Highlight row
	 * @param subid submission identification 
	*/ 
	VPL.hlrow=function(subid){
		if(opener == null){
			return;
		}
		var ssubid=new String(subid);
		var divgrade= opener.document.getElementById('g'+ssubid);
		var divgrader= opener.document.getElementById('m'+ssubid);
		var divgradeon= opener.document.getElementById('o'+ssubid);
		if(divgrade){
			divgrade.style.backgroundColor='yellow';
			divgrade.style.color='black';
		}
		if(divgrader){
			divgrader.style.backgroundColor='yellow';
			divgrader.style.color='black';
		}
		if(divgradeon){
			divgradeon.style.backgroundColor='yellow';
			divgradeon.style.color='black';
		}
	};
	
	/**
	 * Unhighlight row
	 * @param subid submission identification 
	*/ 
	VPL.unhlrow=function(subid){
		if(opener == null){
			return;
		}
		var ssubid=new String(subid);
		var divgrade= opener.document.getElementById('g'+ssubid);
		var divgrader= opener.document.getElementById('m'+ssubid);
		var divgradeon= opener.document.getElementById('o'+ssubid);
		if(divgrade){
			divgrade.style.backgroundColor='';
			divgrade.style.color='';
		}
		if(divgrader){
			divgrader.style.backgroundColor='';
			divgrader.style.color='';
		}
		if(divgradeon){
			divgradeon.style.backgroundColor='';
			divgradeon.style.color='';
		}
	};
	
	/**
	 * Update submission list grade
	 * @param subid submission identification 
	*/ 
	VPL.updatesublist=function(subid,grade,grader,gradeon){
		if(opener == null){
			return;
		}
		var ssubid=new String(subid);
		var divgrade= opener.document.getElementById('g'+ssubid);
		var divgrader= opener.document.getElementById('m'+ssubid);
		var divgradeon= opener.document.getElementById('o'+ssubid);
		if(divgrade){
			divgrade.innerHTML=grade;
			divgrade.style.backgroundColor='';
			divgrade.style.color='';
		}
		if(divgrader){
			divgrader.innerHTML=grader;
			divgrader.style.backgroundColor='';
			divgrader.style.color='';
		}
		if(divgradeon){
			divgradeon.innerHTML=gradeon;
			divgradeon.style.backgroundColor='';
			divgradeon.style.color='';
		}
	};
	
	/**
	 * Go to next submission
	 * @param subid submission id
	 * @param url base of next
	*/
	VPL.go_next = function(subid,url){
		if(opener == null){
			window.close();
		}
		var ssubid=new String(subid);
		var divnext= opener.document.getElementById('n'+ssubid);
		if(divnext){
			location.replace(url+divnext.innerHTML);
		}else{
			window.close();
		}
	};
})();
