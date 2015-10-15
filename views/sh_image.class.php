<?php
/**
 * @package		vpl. vpl Syntaxhighlighters for images
 * @copyright	Copyright (C) 2014 Juan Carlos Rodríguez-del-Pino. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html
 * @author		Juan Carlos Rodriguez-del-Pino
 **/

require_once dirname ( __FILE__ ) . '/sh_base.class.php';
class vpl_sh_image extends vpl_sh_base {
	private $MIME;
	function __construct(){
		$this->MIME = array (
				'jpg' => 'jpeg',
				'jpeg' => 'jpeg',
				'gif' => 'gif',
				'png' => 'png',
				'ico' => 'vnd.microsoft.icon'
		);
	}
	function getMIME($name) {
		$ext = strtolower(vpl_fileExtension($name));
		return $this->MIME[$ext];
	}
	function print_file($name, $data) {
		echo '<div class="vpl_sh vpl_g">';
		echo '<img src="data:image/'.$this->getMIME($name).';base64,';
		echo base64_encode($data);
		echo '" alt="'.s($name).'" />';
		echo '</div>';
	}
}
