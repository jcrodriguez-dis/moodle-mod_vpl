<?php 
/**
 * @version		$Id: submissionsgraph.php,v 1.3 2012-07-25 19:03:46 juanca Exp $
 * @package		VPL. Graph submissions statistics for a vpl instance and a user
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
require_once dirname(__FILE__).'/vpl_graph.class.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../vpl_submission.class.php';
$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$type = optional_param('type', 0, PARAM_INT);
$vpl = new mod_vpl($id);
$course = $vpl->get_course();
$vpl->require_capability(VPL_GRADE_CAPABILITY);
//No log
$subsn = array();
$series = array();
$names = array();
$submissionslist = $vpl->user_submissions($userid);
if(count($submissionslist)>0){
	$submissionslist=array_reverse($submissionslist);
	//Create submissions object
	$subs = array();
	foreach ($submissionslist as $submission) {
		$subs[] = new mod_vpl_submission($vpl,$submission);
	}
	foreach ($subs as $sub) {
		$files_array = $sub->get_submitted_files();
		foreach ( $files_array as $file){
			$name = $file['name'];
			if(! in_array($name,$names,true)){
				$names[]=$name;
				$series[$name]=array();
			}
		}
	}
	//Initial value
	$subsn[] = 0;
	foreach($names as $name){
		$series[$name][]=0;
	}
	$subshowl = (int)(count($subs)/20);
	if($subshowl < 1){
		$subshow = 1;
	}else{
		$subshow = 5;
		while(true){
			if($subshow >=$subshowl) break;
			$subshow *=2;
			if($subshow >=$subshowl) break;
			$subshow =(int)(2.5*$subshow);
			if($subshow >=$subshowl) break;
			$subshow *=2;
			if($subshow >=$subshowl) break;
		}
	}
	$nsub = 1;
	foreach ($subs as $sub) {
		$subsn[] = $nsub%$subshow == 0?$nsub:'';
		$nsub++;
		$files_array = $sub->get_submitted_files();
		$files = array();
		//Used to give stack format last bar has less size
		$total_size=0;
		foreach($files_array as $file){
			$size=strlen($file['data']);
			$files[$file['name']]=$size;
			$total_size += $size;
		}
		foreach($names as $name){
			if(isset($files[$name])){
				$series[$name][]=$total_size;
				$total_size -= $files[$name];
			}else{
				$series[$name][]=$total_size;
			}
		}
	}
}
$user = $DB->get_record('user',array('id' => $userid));
vpl_graph::draw($vpl->get_printable_name().' - '.$vpl->fullname($user,false),
							get_string('submissions',VPL),
							get_string("sizeb"),
							$subsn,
							$series,
							$names);
?>