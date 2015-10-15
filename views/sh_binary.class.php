<?php
/**
 * @package		vpl. vpl Syntaxhighlighters for binary files
 * @copyright	Copyright (C) 2014 Juan Carlos Rodríguez-del-Pino. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html
 * @author		Juan Carlos Rodriguez-del-Pino
 **/

require_once dirname ( __FILE__ ) . '/sh_base.class.php';
class vpl_sh_binary extends vpl_sh_base {
	function print_file($name, $data) {
		echo get_string('binaryfile',VPL);
	}
}
