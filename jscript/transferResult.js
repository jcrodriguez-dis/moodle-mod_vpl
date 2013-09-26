/**
 * @version		$Id: transferResult.js,v 1.13 2012-07-25 19:03:05 juanca Exp $
 * @package		VPL. JavaScript functions to transfer results to the applet
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/**
 * Transfer compilation result to the applet
 * Get information from HTML div elements with ids:
 * grade, compilation and execution
 * @param win windows where the applet is
 * @return void
 */
(function(){
	if (typeof VPL != 'object') {
		VPL = new Object();
	}
	VPL.transferResult = function (win){
	  if(win==null) return;
	  try{
		  var applet = win.document.getElementById('appleteditorid');
		  var grade_element = window.document.getElementById('grade');
		  var compilation_element = window.document.getElementById('compilation');
		  var execution_element = window.document.getElementById('execution');
		  var grade = '';
		  var compilation = '';
		  var execution = '';
		  if(grade_element && grade_element.innerHTML>'')
		    grade=grade_element.innerHTML;
		  if(compilation_element && compilation_element.innerHTML>'' )
		  	compilation='%3Chtml%3E%3Cbody%3E'+compilation_element.innerHTML+'%3C%2Fbody%3E%3C%2Fhtml%3E';
		  if(execution_element && execution_element.innerHTML > '')
		    execution='%3Chtml%3E%3Cbody%3E'+execution_element.innerHTML+'%3C%2Fbody%3E%3C%2Fhtml%3E';
		  applet.setResult(grade,compilation,execution);
	  }
	  catch(e){
		  window.status='Transfer result error: '+e;
	  }
	}
	
	/**
	 * Start/show applet status progress bar
	 * @param win windows where the applet is
	 * @return void
	 */
	VPL.startStatusBarProcess = function(win,text){
	  if(win==null) return;
	  var applet = win.document.getElementById('appleteditorid');
	  try{
		  applet.startStatusBarProcess(text);
	  }catch(e){}
	}
	
	/**
	 * Keep alive applet status bar
	 * @param win windows where the applet is
	 * @return void
	 */
	VPL.updateStatusBarProcess = function(win){
	  if(win==null) return;
	  var applet = win.document.getElementById('appleteditorid');
	  try{
	  	applet.updateStatusBarProcess();
	  }catch(e){}
	}
	
	/**
	 * Stop and set a message that will disapear
	 * @param win windows where the applet is
	 * @param text to show
	 * @return void
	 */
	VPL.endStatusBarProcess = function(win,text){
	  if(win==null) return;
	  var applet = win.document.getElementById('appleteditorid');
	  try{
		  if(typeof(text) == 'undefined'){
			  applet.endStatusBarProcess();
		  }else{
			  applet.endStatusBarProcess(text);
		  }
	  }catch(e){}
	}
})();
