/**
 * @version $Id: editor.js,v 1.32 2012-10-05 09:03:48 juanca Exp $
 * @package VPL. JavaScript functions to manage applet editor
 * @copyright	2012 Juan Carlos RodrÃ­guez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos RodrÃ­guez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

(function() {
	var vpl_fileName = new Array();
	var vpl_fileData = new Array();
	var vpl_NFiles = 0;
	var vpl_appletWidth = 0;
	var vpl_appletHeight = 0;
	var vpl_maxFiles = 1;
	if (typeof VPL != 'object') {
		VPL = new Object();
	}

	/**
	 * When editing some files, this function get the initial filenames and data
	 * from the editor
	 */
	VPL.initFiles = function() {
		var ae = window.document.getElementById('appleteditorid');
		if (ae != null) {
			try {
				if (ae.isActive()) {
					vpl_NFiles = ae.getNFiles();
					for (i = 0; i < vpl_NFiles; i++) {
						vpl_fileName[i] = new String(ae.getFileName(i));
						vpl_fileData[i] = new String(ae.getFileContent(i));
					}
				} else {
					setTimeout('VPL.initFiles()', 100);
				}
			} catch (e) {
				setTimeout('VPL.initFiles()', 250);
			}
		} else {
			setTimeout('VPL.initFiles()', 100);
		}
	}

	/**
	 * When editing one file, this function check data change and set data in
	 * form fields
	 */
	VPL.readFile = function() {
		var fm = window.document.getElementById('mform1');
		var ae = window.document.getElementById('appleteditorid');
		if (ae == null || fm == null) {
			window.status='VPL: readFile() javascript error';
			return false;
		}
		try {
			newData = new String(ae.getFileContent(0));
			previousData = new String(vpl_fileData[0]);
			fm.filedata.value = newData;
			fm.fontsize.value = ae.getFontSize();
			vpl_fileData[0] = newData;
			return true;
		} catch (e) {
			window.status='VPL: readFile() javascript error: ' + e;
			return false;
		}
	}

	/**
	 * This function set edit target in form (XHTML restriction)
	 */
	VPL.setETarget = function() {
		var fm = window.document.getElementById('mform1');
		var er = window.document.getElementById('div_er');
		if (er == null || fm == null) {
			window.status='VPL: setETarget() javascript error';
			throw 'VPL: setETarget() javascript error';
		}
		fm.target = 'edit_result';
		er.innerHTML = '<iframe name="edit_result"></iframe>';
	}

	/**
	 * When editing files, this function check if data change and set data in
	 * form fields
	 */
	VPL.readFiles = function() {
		var fm = window.document.getElementById('mform1');
		var ae = window.document.getElementById('appleteditorid');
		var i;
		if (ae == null || fm == null) {
			window.status='VPL: readFiles() javascript error';
			return false;
		}
		try {
			vpl_NFiles = ae.getNFiles();
			for (i = 0; i < vpl_NFiles; i++) {
				newName = new String(ae.getFileName(i));
				fm.elements['filename' + i].value = newName;
				vpl_fileName[i] = newName;
				newData = new String(ae.getFileContent(i));
				fm.elements['filedata' + i].value = newData;
				vpl_fileData[i] = newData;
			}
			for (i = vpl_NFiles; i < vpl_maxFiles; i++) {
				fm.elements['filename' + i].value = '';
				vpl_fileName[i] = '';
				fm.elements['filedata' + i].value = '';
				vpl_fileData[i] = '';
			}
			fm.fontsize.value = ae.getFontSize();
			return true;
		} catch (e) { // No access to applet
			window.status='VPL: JavaScript error reading files from applet:' + e;
			return false;
		}
	}

	/**
	 * This function check if data has changed
	 */
	VPL.filesChanged = function() {
		var fm = window.document.getElementById('mform1');
		var ae = window.document.getElementById('appleteditorid');
		var newName, newData, previousName, previousData;
		if (ae == null || fm == null) {
			window.status='VPL: filesChanged() javascript error';
		}
		try {
			if (vpl_NFiles != ae.getNFiles()) {
				return true;
			}
			if (fm.elements['filename'] != null) {
				newData = new String(ae.getFileContent(0));
				return newData.valueOf() != vpl_fileData[0].valueOf();
			}
			for (i = 0; i < vpl_NFiles; i++) {
				newName = new String(ae.getFileName(i));
				previousName = vpl_fileName[i];
				newData = new String(ae.getFileContent(i));
				previousData = vpl_fileData[i];
				if (newName.valueOf() != previousName.valueOf()
						|| previousData.valueOf() != newData.valueOf()) {
					return true;
				}
			}
			return false;
		} catch (e) { // No access to applet
			window.status='VPL: error no access to applet';
			return false;
		}
	}

	/**
	 * This function alert if files in editor has change
	 */
	VPL.alertFilesChanged = function() {
		if (VPL.filesChanged()) {
			return confirm(M.str.vpl.filesChangedNotSaved);
		}
		return true;
	}

	/**
	 * This function start action if files changed or not changed
	 */
	VPL.action = function(action, save) {
		res = true;
		if (save) {
			res = VPL.readFiles();
		} else {
			if (VPL.filesChanged()) {
				alert(M.str.vpl.filesChangedNotSaved);
			}
		}
		if (res) {
			var applet = window.document.getElementById('appleteditorid');
			applet.startStatusBarProcess(action);
		}
		return res;
	}

	/**
	 * This function start action if files changed or not changed
	 */
	VPL.action1 = function(action) {
		VPL.setETarget();
		if (VPL.filesChanged()) {
			alert(M.str.vpl.filesChangedNotSaved);
		}
		var applet = window.document.getElementById('appleteditorid');
		applet.startStatusBarProcess(action);
		return true;
	}

	/**
	 * This function install the checker to alert if going to leave the page and
	 * files has changed
	 */
	VPL.installCloseCheck = function() {
		window.onbeforeunload = function() {
			if (VPL.filesChanged(vpl_maxFiles)) {
				return M.str.vpl.changesNotSaved;
			}
		}
	}
	
	VPL.getOffsetY = function (obj){
		var offset=0;
		var i;
		for(i=0; i<200 && obj != null && obj != document.body; i++){
			offset += obj.offsetTop;
			obj=obj.offsetParent;
		}
		return offset;
	}

	/**
	 * This function resize the applet to greatest visible size
	 */
	VPL.resizeApplet = function() {
		var fm = window.document.getElementById('mform1');
		var appletObject = window.document.getElementById('appleteditorid');
		var appletDiv = window.document.getElementById('appletdivid');
		try {
			var newWidth = fm.clientWidth;
			var newHeight;
			//TODO need change instruction place 
			fm.style.padding='0px';
			if(window.innerHeight)
				newHeight = window.innerHeight
						-VPL.getOffsetY(appletDiv)-35;
			else
				newHeight = document.documentElement.clientHeight
				-VPL.getOffsetY(appletDiv)-35;
			if (vpl_appletWidth != newWidth
					|| vpl_appletHeight != newHeight) {
				appletDiv.style.padding = '0px';
				appletDiv.style.margin = '0px';
				if(newWidth>30){
					vpl_appletWidth = newWidth;
					appletDiv.style.width = newWidth + "px";
				}
				if(newHeight>30){
					vpl_appletHeight = newHeight;
					appletDiv.style.height = newHeight + "px";
				}

			}
		} catch (e) {
			window.status=e.message;
			// Do nothing
		}
	}

	/**
	 * This function init editor page
	 */
	VPL.init_editor_page = function() {
		var fm = window.document.getElementById('mform1');
		var er = window.document.getElementById('div_er');
		VPL.resizeApplet();
		VPL.initFiles();
		//For changes of form clientWidth
		setInterval('VPL.resizeApplet()',5000);
		if (er != null && fm != null) {
			VPL.installCloseCheck();
			fm.onresize = VPL.resizeApplet;
			fm.target = 'edit_result';
			er.innerHTML = '<iframe name="edit_result"></iframe>';
		}
	}

	/**
	 * This set the resize controler
	 */
	window.onresize = VPL.resizeApplet;
	(function(){
		var fm = window.document.getElementById('mform1');
		var i;
		for(i=1;true; i++){
			if(typeof (fm['filename' + i]) == 'undefined')
				break;
		}
		vpl_maxFiles = i;
		VPL.init_editor_page();
	})();
})()
