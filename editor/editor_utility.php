<?php
/**
 * @version		$Id: editor_utility.php,v 1.8 2013-04-16 17:40:10 juanca Exp $
 * @package		VPL. editor internationalization
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../locallib.php';
function vpl_param_tag($name,$rawdata='',$plain=false){
	global $CFG;
	if($plain){
		$data = $rawdata;
	}else{
		$data = urlencode($rawdata);
	}
	if(isset($CFG->vpl_direct_applet) && $CFG->vpl_direct_applet){
		return '<param name="'.$name.'" value="'.$data.'" />'."\n";
	}else{
		return 'applet_parms.'.$name."='".$data."';\n";
	}
}

function vpl_get_editor_tag($param_tags=''){
	global $CFG;
	$user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	$msie = false;
	$firefox = false;
	if(strpos($user_agent, 'msie')!== false){
		$msie = true;
	}
	if(strpos($user_agent, 'firefox')!== false){
		$firefox = true;
	}
	$object_tag = ($msie || $firefox);
	$main='org.acodeeditor.gui.Main.class';
	$archive = vpl_mod_href('editor/appleteditor.jar');
	$applet ='<div id="appletdivid" style="height:100%;width:100%">';
	if(isset($CFG->vpl_direct_applet) && $CFG->vpl_direct_applet){
/*		if($object_tag){ //Use object or applet
			$common_atr =' type="application/x-java-applet"';
			$common_atr .=' id="appleteditorid"';
			$common_atr .=' height="100%" width="100%"';
			$common_atr .= ' codebase="'.vpl_mod_href('editor').'"';
			$common_atr .= ' codetype="application/java-archive"';
			$common_atr .= ' archive="'.$archive.'"';
			$common_atr .=' jnlp_href="'.vpl_mod_href('editor/appleteditor.jnlp').'"';
			$applet .='<object';
			$applet .=' classid="java:org.acodeeditor.gui.Main"';
			$applet .=$common_atr.' >';
			$applet .= vpl_param_tag('codebase',vpl_mod_href('editor'),true);
			$applet .= vpl_param_tag('codetype','application/java-archive',true);
			$applet .= vpl_param_tag('archive',$archive,true);
			$applet .= vpl_param_tag('code',$main,true);
			$applet.=$param_tags;
			$applet.='<object>Please, <a href="http://www.java.com/">download Java</a> or/and update your browser</object>';
			$applet.='</object>';
		}else*/{
			$common_atr =' id="appleteditorid"';
			$common_atr .=' height="100%" width="100%"';
			$common_atr .=' archive="'.$archive.'"';
			$common_atr .=' code="'.$main.'"';
			$common_atr .=' codebase="'.vpl_mod_href('editor').'"';
			$common_atr .=' jnlp_href="'.vpl_mod_href('editor/appleteditor.jnlp').'"';
			$applet .='<applet';
			$applet .=$common_atr.">\n";
			$applet.=$param_tags;
			$applet.=vpl_param_tag('jnlp_href',vpl_mod_href('editor/appleteditor.jnlp'),true);
			$applet.='<a href="http://www.java.com/">Download Java</a> or/and update your browser';
			$applet.="\n</applet>\n";
		}
	}
	else{
		$http= (strpos($CFG->wwwroot,'https')== 0)?'https':'http';
		$applet .='<script type="text/javascript" src="'.$http.'://www.java.com/js/deployJava.js"></script>'."\n";
		$js = "var applet_parms=new Object;\nvar applet_atr=new Object;\n";
		$js .="applet_atr.id='appleteditorid';\n";
		$js .="applet_atr.height='100%';\n";
		$js .="applet_atr.width='100%';\n";
		$js .="applet_atr.archive='".$archive."';\n";
		$js .="applet_atr.code='".$main."';\n";
		$js .="applet_atr.codebase='".vpl_mod_href('editor/')."';\n";
		$js .=vpl_param_tag('jnlp_href',vpl_mod_href('editor/appleteditor.jnlp'),true);
		$js .=$param_tags;
		$js .="deployJava.runApplet( applet_atr, applet_parms, '1.6' );\n";
		$applet .=vpl_include_js($js);
	}
	
	$applet.='</div>';
	return $applet;
}

class vpl_editor_i18n{
	/**
	 * Return editor i18n string list
	 * @return  string of lines with format "word=i18nword"
	 **/
	protected static function editor_i18n_strings(){
		$list=array(
			'previous_page',
			'return_to_previous_page',
			'next_page',
			'go_next_page',
			'help',
			'contextual_help',
			'general_help',
			'edit',
			'file',
			'options',
			'new',
			'create_new_file',
			'file_name',
			'incorrect_file_name',
			'rename',
			'renameFile',
			'new_file_name',
			'delete',
			'delete_file',
			'delete_file_q',
			'save',
			'undo',
			'undo_change',
			'redo',
			'redo_undone',
			'cut',
			'cut_text',
			'copy',
			'copy_text',
			'paste',
			'paste_text',
			'select_all',
			'select_all_text',
			'find_replace',
			'find_find_replace',
			'program_help',
			'page_unaccessible',
			'about',
			'help_about',
			'figure',
			'applet_code_editor_about',
			'line_number',
			'toggle_show_line_number',
			'next',
			'find_next_search_string',
			'replace',
			'replace_selection_if_match',
			'replace_find',
			'replace_find_next',
			'replace_all',
			'replace_all_next',
			'language_help',
			'console',
			'find',
			'case_sensitive',
			'replace_find',
			'font_size',
			'connecting',
			'connection_fail',
			'connected',
		    'connection_closed',
			'saving',
			'running',
			'evaluating',
			'debugging'
			);
			$ret ='';
			foreach($list as $word){
				$ret .=$word.'='.get_string($word,VPL)."\n";
			}
			return $ret;
	}

	public static function add_editori18n_field(& $mform){
		$mform->addElement('hidden','editori18n');
		$mform->setType('editori18n', PARAM_RAW);
		$mform->setDefault('editori18n', self::editor_i18n_strings());
	}

	public static function add_jseditori18n($return=false){
		$lang = vpl_get_lang();
		if($return){
			return vpl_include_js('setI18n(\''.$lang.'\');',true);
		}else{
			echo vpl_include_js('setI18n(\''.$lang.'\');',true);
		}
	}
	public static function get_params_tag(){
		return	vpl_param_tag('lang',vpl_get_lang()).
		vpl_param_tag('i18n',self::editor_i18n_strings());
	}
}
?>
