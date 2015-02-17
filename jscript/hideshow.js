/**
 * @version		$Id: hideshow.js,v 1.3 2012-06-19 10:47:01 juanca Exp $
 * @package mod_vpl. JavaScript function to show hide div
 * @copyright	Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html
 * @author		Juan Carlos Rodriguez-del-Pino
 **/
/**
* Show/hide div and change text ([+]/[-])
*/
(function() {
	if (typeof VPL != 'object') {
		VPL = new Object();
	}

	VPL.show_hide_div = function (id){
		var text= window.document.getElementById('sht'+id);
		var div=window.document.getElementById('shd'+id);
		if(text){
			if(text.innerHTML == '[+]'){
				div.style.display='';
				text.innerHTML = '[-]';
			}else{
				div.style.display='none';
				text.innerHTML = '[+]';
			}
		}
	};
	VPL.hide_later = function (){
		var div=window.document.getElementById('vpl.hide');
		if(div){
			div.style.transition='display 2s';
			div.style.display='none';
		}
	};
	setTimeout('VPL.hide_later()',5000);
})();
