/**
 * @version		$Id: hide_footer.js,v 1.2 2012-06-19 10:47:01 juanca Exp $
 * @package mod_vpl. JavaScript action to hide page footer
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

(function() {
	var footer = document.getElementById('page-footer');
	if (footer != undefined) {
		footer.style.display = 'none';
	}
	footer = document.getElementById('footer');
	if (footer != undefined) {
		footer.style.display = 'none';
	}
})();
