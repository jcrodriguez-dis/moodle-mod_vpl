<?php
/**
 * @version		$Id: watermark.php,v 1.3 2012-06-05 23:22:11 juanca Exp $
 * @package		VPL. class to process watermark
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

class vpl_watermark{
	const pre="\t \t  ";
	const post="  \t \t";
	private static $encoder= array("\t","\t ","\t  ","\t   ","\t    ","\t     ","\t      "); 
	
	private static function encode($wm){
		$wm=(int)$wm;
		$ret='';
		while($wm>0){
			$ret=self::$encoder[$wm % 7].$ret;
			$wm = (int)($wm / 7);			
		}
		return $ret;
	}

	private static function decode($wm){
		$ret=0;
		$digits=explode("\t",$wm);
		foreach($digits as $digit){
			$ret = ($ret*7)+strlen($digit);
		}
		return $ret;
	}
	
	/**
	 * Generate a water mark from the $userid 
	 * @param $userid int
	 * @return string with spaces and tabs
	 */
	private static function genwm($userid){
		$userid = (int)$userid;
		//Add CRC
		$useridcrc= ($userid*10)+(($userid+7)%10);
		return self::pre . self::encode($useridcrc) . self::post;
	}

	/**
	 * @param string $data
	 * @return int userid found in watermark
	 */
	static function getwm($data){
		$nl = vpl_detect_newline($data);
		$lines=explode($nl,$data);
		foreach($lines as $line){
			$pos_pre=strpos($line,self::pre);
			if($pos_pre !== false){
				$start=$pos_pre+strlen(self::post);
				$pos_post=strrpos($line,self::post);
				if($pos_post !== false && ($start < $pos_post)){
					$wm=substr($line,$start,$pos_post-$start);
					$useridcrc=self::decode($wm);
					$userid=(int)($useridcrc/10);
					//Check CRC
					if(($userid+7)%10 == $useridcrc%10){
						//TODO return an array of userids?
						return $userid;
					}
				}
			}
		}
		return false;
	}	
	
	/**
	 * @param string $filename
	 * @return int userid found in watermark
	 */
	static function getfilewm($filename){
		if(file_exists($filename)){
			return self::getwm(get_file_content($filename));
		}
		return false;
	}

	/**
	 * @param string $data file content
	 * @param userid of wm
	 * @return string $data with water mark
	 */
	static function addwm_c(&$data,$userid){
		//Check if need water mark
		if(self::getwm($data) == false){
			$wm=self::genwm($userid);
			$nl=vpl_detect_newline($data);
			$lines = explode($nl,$data);
			$count_nwm = 0;
			$wm_added=0;
			foreach($lines as &$line){
				if($count_nwm>35){ //if 35 lines without wm try to add one
					$lclean= rtrim($line);
					$l=strlen($lclean);
					if($l>0 && ($lclean[$l-1] == '}' || $lclean[$l-1] == '{')){
						$line = $lclean.$wm;
						$wm_added++;
						$count_nwm = 0;
					}
				}
				$count_nwm++;
			}
			if($wm_added){
				$data = implode($nl,$lines);
			}
			if($count_nwm >25){//if last 25 lines without wm add last
				$data .= $wm.$nl;
			}
		}
	}

	/**
	 * Add watermark to the end of data
	 * @param string $data file content
	 * @param userid of wm
	 * @return string $data with water mark
	 */
	static function addwm_generic(&$data,$userid){
		//Check if need water mark
		if(self::getwm($data) == false){
			$wm=self::genwm($userid);
			$nl=vpl_detect_newline($data);
			$data .= $wm.$nl;
		}
	}
	
	/**
	 * @param string $from filename
	 * @param string $to filename with watermark added
	 * @return void
	 */
	static function addfilewm($from,$to,$userid){
		$data = file_get_contents($from);
		if(strlen($data)>500){
			$ext = pathinfo($from, PATHINFO_EXTENSION);
			if($ext == 'c' || $ext == 'h' || $ext = 'hxx' ||
				$ext == 'cpp' || $ext == 'cc' || $ext = 'C'){
				self::addwm_c($data,$userid); 
			}else{
				self::addwm_generic($data,$userid);
			}
		}
		file_put_contents($to,$data);
	}
}
?>