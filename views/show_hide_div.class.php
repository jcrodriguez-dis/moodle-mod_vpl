<?php 
/**
 * @version		$Id: show_hide_div.class.php,v 1.4 2012-06-05 23:22:09 juanca Exp $
 * @package		VPL. Show/hide HTML div
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
class vpl_hide_show_div{
	static private $globalid=0;
	var $id;
	var $show;
	function __construct($show=false){
		if(self::$globalid == 0){
			echo vpl_include_jsfile('hideshow.js');
		}
		$this->id = self::$globalid;
		$this->show = $show;
		self::$globalid++;
	}
	function generate($return=false){
		$HTML = '<a id="sht'.$this->id.'" href="javascript:void(0);"';
		$HTML .= ' onclick="VPL.show_hide_div('.$this->id.');">';
		if($this->show){
			$HTML .= '[-]';
		}else{
			$HTML .= '[+]';
		}
		$HTML .= '</a>';
		if($return){
			return $HTML;
		}else{
			echo $HTML;
			return '';
		}
	}
	function begin_div($return=false){
		$HTML = '<div id="shd'.$this->id.'"';
		if(!($this->show)){
			$HTML .= ' style="display:none"';
		}
		$HTML .= '>';
		if($return){
			return $HTML;
		}else{
			echo $HTML;
			return '';
		}
	}
	function end_div($return=false){
		if($return){
			return '</div>';
		}else{
			echo '</div>';
			return '';
		}
	}
}
?>