<?php
/**
 * @version		$Id: status_box.class.php,v 1.5 2012-07-26 18:38:27 juanca Exp $
 * @package		VPL. class to show a process status in a box
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../locallib.php';

class vpl_status_box{
	static $last_id=0;
	protected $id;
	protected $start_time;

	/**
	 * Constructor
	 **/
	function __construct($text='') {
		global $OUTPUT;
		$this->id='vpl_sb_'.(self::$last_id+1);
		$this->start_time = time();
		self::$last_id++;
		echo $OUTPUT->box($text,'',$this->id);
	}

	/**
	 * print text
	 **/
	function print_text($text){
		$JS = 'window.document.getElementById(\'';
		$JS .= $this->id;
		$JS .= '\').innerHTML =\'';
		$JS .= addslashes($text);
		$JS .= '\';';
		echo vpl_include_js($JS);
		@ob_flush();
		flush();
	}

	/**
	 * hide box
	 **/
	function hide(){
		$JS = 'window.document.getElementById(\'';
		$JS .= $this->id;
		$JS .= '\').style.display=\'none\';';
		echo vpl_include_js($JS);
		@ob_flush();
		flush();
	}
}
class vpl_progress_bar extends vpl_status_box{
	protected $min;
	protected $max;
	protected $last_time;
	protected $text;
	/**
	 * Constructor
	 **/
	function __construct($text='', $min=0, $max=100) {
		parent::__construct($text);
		$this->text = $text;
		$this->min = $min;
		$this->max = $max;
		$this->last_time = 0;
	}

	function set_value($value){
		if(is_string($value)){
			$this->print_text($this->text.' ('.$value.')');
			return;
		}
		$current_time=time();
		$percent = ((($value-$this->min)*100)/($this->max-$this->min));
		if($this->last_time != $current_time || $percent>=100){
			if($percent>100){
				$percent = 100;
			}
			$this->last_time = $current_time;
			if($percent == 100){
				$text = $this->text.' ('.sprintf("%5.1f",$percent).'%)';
				$text .= ' '.get_string('numseconds','',$current_time-$this->start_time);
				//if(function_exists('memory_get_usage')){
				//	$text .= sprintf(" %5.1fMB",memory_get_usage()/1024000);
				//}
				$this->print_text($text);
			}else{
				$this->print_text($this->text.' ('.sprintf("%5.1f",$percent).'%)');
			}
		}
	}
	function set_max($max){
		$this->max = $max;
	}
}
?>