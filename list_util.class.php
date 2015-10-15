<?php
/**
 * @version		$Id: list_util.class.php,v 1.5 2013-06-11 18:35:23 juanca Exp $
 * @package		VPL. List utility class
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();
class vpl_list_util{
	static $fields;   //field to compare
	static $ascending; //value to return when ascending or descending order

	static public function cpm($avpl,$bvpl){ //Compare two submission fields
		$a = $avpl->get_instance();
		$b = $bvpl->get_instance();
		foreach(self::$fields as $field){
			$avalue = $a->$field;
			$bvalue = $b->$field;
			if($avalue == $bvalue){
				continue;
			}elseif($avalue < $bvalue){
				return self::$ascending;
			}else{
				return -self::$ascending;
			}
		}
		return 0;
	}

	/**
	 * Check and set data to sort return comparation function
	 * $field field to compare
	 * $descending order
	 */
	static public function set_order($field,$ascending = true){
		$sortfields = array('name'=>array('name'),
		'shortdescription' => array('shortdescription'),
		'startdate' => array('startdate','duedate','name'),
		'duedate' => array('duedate','startdate','name'),
		'automaticgrading' => array('automaticgrading','duedate','name'));
		if(isset($sortfields[$field])){
			self::$fields = $sortfields[$field];
		}else{ //Unknow field
			self::$fields = $sortfields['duedate'];
		}
		if($ascending){
			self::$ascending = -1;
		}else{
			self::$ascending = 1;
		}
	}
	static public function vpl_list_arrow($burl,$sort,$instanceselection,$selsort, $seldir){
		global $OUTPUT;
		$newdir = 'down'; //Dir to go if click
		$url = vpl_url_add_param($burl,'sort',$sort);
		$url = vpl_url_add_param($url,'selection',$instanceselection);
		if($sort == $selsort){
			$sortdir = $seldir;
			if($sortdir == 'up'){
				$newdir = 'down';
			}elseif($sortdir == 'down'){
				$newdir = 'up';
			}else{ //Unknow sortdir
				$sortdir = 'down';
			}
			$url = vpl_url_add_param($url,'sortdir',$newdir);
		}else{
			$sortdir = 'move';
		}
		return '<a href="'.$url.'">'.($OUTPUT->pix_icon('t/'.$sortdir,get_string($sortdir))).'</a>';
	}
	static public function count_graded($vpl){ //Count submissions graded
		$numsubs = 0;
		$numgraded = 0;
		$subs = $vpl->all_last_user_submission('s.dategraded');
		$students = $vpl->get_students();
		foreach($students as $student){
			if(isset($subs[$student->id])){
				$sub=$subs[$student->id];
				$numsubs++;
				if($sub->dategraded > 0){ //is graded
					$numgraded++;
				}
			}
		}
		return array('submissions' => $numsubs, 'graded' => $numgraded);
	}
}
?>