<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// VPL for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with VPL for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * IDE utility functions
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */


class vpl_editor_util{
	static function generate_jquery(){
		global $PAGE;
		$PAGE->requires->css(new moodle_url('/mod/vpl/editor/font-awesome/css/font-awesome.min.css'));
		//Auto selection dont work in 2.3
		if(method_exists($PAGE->requires,'jquery_plugin')){
			$PAGE->requires->jquery();
			$PAGE->requires->jquery_plugin('ui');
			$PAGE->requires->jquery_plugin('ui-css');
			$PAGE->requires->js(new moodle_url('/mod/vpl/editor/jquery-touch/jquery.ui.touch-punch.min.js'),true);
		}else{
			$PAGE->requires->css(new moodle_url('/mod/vpl/editor/jquery/themes/smoothness/jquery-ui.css'));
			$PAGE->requires->js(new moodle_url('/mod/vpl/editor/jquery/jquery-1.9.1.js'),true);
			$PAGE->requires->js(new moodle_url('/mod/vpl/editor/jquery/jquery-ui-1.10.3.custom.js'),true);
			$PAGE->requires->js(new moodle_url('/mod/vpl/editor/jquery-touch/jquery.ui.touch-punch.min.js'),true);
		}
		$PAGE->requires->js(new moodle_url('/mod/vpl/editor/VPL_jquery_no_conflict.js'),true);
	}
	static function generate_requires_evaluation(){
		global $PAGE;
		self::generate_jquery();
		$PAGE->requires->css(new moodle_url('/mod/vpl/editor/VPLIDE.css'));
		$PAGE->requires->js(new moodle_url('/mod/vpl/editor/VPLUtil.js'),true);
		$PAGE->requires->js(new moodle_url('/mod/vpl/editor/evaluationMonitor.js'),true);
	}
	static function generate_requires(){
		global $PAGE;
		self::generate_jquery();
		$PAGE->requires->css(new moodle_url('/mod/vpl/editor/VPLIDE.css'));
		$PAGE->requires->js(new moodle_url('/mod/vpl/editor/zip/inflate.js'));
		$PAGE->requires->js(new moodle_url('/mod/vpl/editor/zip/unzip.js'));
		$PAGE->requires->js(new moodle_url('/mod/vpl/editor/ace9/ace.js'));
		$PAGE->requires->js(new moodle_url('/mod/vpl/editor/ace9/ext-language_tools.js'));
		$PAGE->requires->js(new moodle_url('/mod/vpl/editor/term.js'));
		$PAGE->requires->js(new moodle_url('/mod/vpl/editor/VPLUtil.js'));
		$PAGE->requires->js(new moodle_url('/mod/vpl/editor/VPLTerminal.js'));
		$PAGE->requires->js(new moodle_url('/mod/vpl/editor/VPLIDE.js'));
		$PAGE->requires->js(new moodle_url('/mod/vpl/editor/VPLIDEFile.js'));
		$PAGE->requires->js(new moodle_url('/mod/vpl/editor/noVNC/include/util.js'),true);
	}
static function print_tag($options,$files_to_send,$saved=true){
	global $CFG;
	$tag_id = 'vplide';
	$files = Array();
	foreach($files_to_send as $name => $data){
		$file = new stdClass();
		$file->name=$name;
		$file->data=vpl_encode_binary($name,$data);
		$files[] = $file;
	}
	$options['files']=$files;
	$options['i18n']=self::i18n();
	$options['saved']=($saved)?1:0;
	$joptions=json_encode($options);
?>
<div id="<?php echo $tag_id;?>" class="vpl_ide vpl_ide_root ui-widget">
	<div id="vpl_menu" class="vpl_ide_menu"></div>
	<div id="vpl_tr" class="vpl_ide_tr">
		<div id="vpl_filelist" style="display:none;">
			<div id="vpl_filelist_header"><?php p(get_string('filelist',VPL))?></div>
			<div id="vpl_filelist_content"></div>
		</div>
		<div id="vpl_tabs" class="vpl_ide_tabs">
			<div id="vpl_tabs_scroll">
				<ul id="vpl_tabs_ul"></ul>
			</div>
		</div>
		<div id="vpl_results" class="vpl_ide_results">
		 <div id="vpl_results_accordion"></div>
		</div>
	</div>
	<div id="vpl_ide_dialog_new" class="vpl_ide_dialog"
		style="display: none;">
		<fieldset>
			<label for="vpl_ide_input_newfilename">
				<?php p(get_string('new_file_name',VPL))?></label> <input
				type="text" id="vpl_ide_input_newfilename"
				name="vpl_ide_input_newfilename" value=""
				class="ui-widget-content ui-corner-all" autofocus /><br />
		</fieldset>
	</div>
	<div id="vpl_ide_dialog_rename" class="vpl_ide_dialog"
		style="display: none;">
		<fieldset>
			<label for="vpl_ide_input_renamefilename">
				<?php p(get_string('rename'))?></label> <input type="text"
				id="vpl_ide_input_renamefilename"
				name="vpl_ide_input_renamefilename" value=""
				class="ui-widget-content ui-corner-all" autofocus /><br />
		</fieldset>
	</div>
	<div id="vpl_ide_dialog_delete" class="vpl_ide_dialog"
		style="display: none;">
		<fieldset>
			<label for="vpl_ide_input_deletefilename">
				<?php p(get_string('delete'))?></label> <input type="text"
				id="vpl_ide_input_deletefilename"
				name="vpl_ide_input_deletefilename" value=""
				class="ui-widget-content ui-corner-all" autofocus /><br />
		</fieldset>
	</div>
	<div id="vpl_ide_dialog_sort" class="vpl_ide_dialog"
		style="display: none;">
		<ol id="vpl_sort_list"></ol>
	</div>
	<div id="vpl_ide_dialog_about" class="vpl_ide_dialog"
		style="display: none;">
		<h3>IDE for VPL</h3>
		This IDE is part of VPL <a href="http://vpl.dis.ulpgc.es" target="_blank">Virtual
			Programming Lab for Moodel</a><br /> Author: Juan Carlos Rodríguez
		del Pino &lt;jcrodriguez@dis.ulpgc.es&gt;<br /> Licence: <a
			href="http://www.gnu.org/copyleft/gpl.html" target="_blank">GNU GPL v3</a><br /> This
		software uses/includes the following software under the
		corresponding licence:
		<ul>
			<li><a href="http://http://ace.c9.io" target="_blank">ACE</a>: an embeddable code
				editor written in JavaScript. Copyright (c) 2010, Ajax.org B.V. (<a
				href="../editor/ace9/LICENSE" target="_blank">licence</a>)</li>
			<li><a href="https://github.com/chjj/term.js/" target="_blank">term.js</a>: A full
				xterm clone written in javascript. Copyright (c) 2012-2013,
				Christopher Jeffrey (MIT License)</li>
			<li><a href="http://kanaka.github.io/noVNC/" target="_blank">noVNC</a>: VNC client
				using HTML5 (WebSockets, Canvas). noVNC is Copyright (C) 2011 Joel
				Martin &lt;github@martintribe.org&gt; (<a href="../editor/noVNC/LICENSE.txt" target="_blank">licence</a>)</li>
			<li><a href="http://http://jquery.com/" target="_blank">jQuery and JQuery-ui</a>: jQuery is a fast, small, and feature-rich JavaScript library. Copyright The jQuery Foundation. (<a href="../editor/jquery/MIT-LICENSE.txt">licence</a>)</li>
				</ul>
	</div>
	<form style="display:none;">
		<input type="file" multiple="multiple" id="vpl_ide_input_file" />
	</form>
	<div id="vpl_dialog_terminal">
		<div style="display: none;"><input type="text" id="vpl_input" /></div>
		<pre id="vpl_terminal" class="vpl_terminal"></pre>
	</div>
	<div id="vpl_dialog_vnc">
		<canvas class="vpl_noVNC_canvas">
                Canvas not supported.
         </canvas>
	</div>
</div>
<script>
	INCLUDE_URI="../editor/noVNC/include/";
    Util.load_scripts(["webutil.js", "base64.js", "websock.js", "des.js",
                       "keysymdef.js", "keyboard.js", "input.js", "display.js",
                       "jsunzip.js", "rfb.js", "keysym.js"]);
	$JQVPL(document).ready(function(){
		$JQVPL("#page-footer").hide();
		vpl_ide = new VPL_IDE('<?php echo $tag_id;?>',<?php echo $joptions;?>);
	});
	</script>
<?php
}
static function send_CE($CE) {
		$jCE = json_encode ( $CE );
		$js = "vpl_ide.setResult({$jCE},true);";
		$js = '$JQVPL(document).ready(function(){' . $js . '});';
		return '<script>' . $js . '</script>';
	}
/**
	 * get list of i18n translations for the editor
	 **/
	static function i18n() {
		$vpl_words = array (
'about',
'acceptcertificates',
'acceptcertificatesnote',
'binaryfile',
'browserupdate',
'changesNotSaved',
'comments',
'compilation',
'connected',
'connecting',
'connection_closed',
'connection_fail',
'console',
'copy',
'create_new_file',
'cut',
'debug',
'debugging',
'delete',
'delete_file_fq',
'delete_file_q',
'download',
'edit',
'evaluate',
'evaluating',
'execution',
'getjails',
'file',
'filelist',
'filenotadded',
'filenotdeleted',
'filenotrenamed',
'find',
'find_replace',
'fullscreen',
'incorrect_file_name',
'maxfilesexceeded',
'new',
'next',
'options',
'outofmemory',
'paste',
'redo',
'regularscreen',
'rename',
'rename_file',
'resetfiles',
'retrieve',
'run',
'running',
'save',
'saving',
'select_all',
'sureresetfiles',
'timeout',
'undo',
		);
		$words = array (
'cancel',
'closebuttontitle',
'error',
'import',
'modified',
'notice',
'ok',
'required',
'sort',
'warning'
		);
		$list = Array ();
		foreach ( $vpl_words as $word ) {
			$list [$word] = get_string ( $word, VPL );
		}
		foreach ( $words as $word ) {
			$list [$word] = get_string ( $word );
		}
		return $list;
	}

	static function generateEvaluateScript($ajaxurl,$nexturl){
		$options = Array();
		$options['i18n']=self::i18n();
		$options['ajaxurl']=$ajaxurl;
		$options['nexturl']=$nexturl;
		$joptions=json_encode($options);
		?>
		<script>
			VPL_Single_Evaluation(<?php echo $joptions;?>);
	    </script>
		<?php
	}
	static function generateBatchEvaluateScript($ajaxurls){
		$options = Array();
		$options['i18n']=self::i18n();
		$options['ajaxurls']=$ajaxurls;
		$joptions=json_encode($options);
		?>
		<script>
			VPL_Batch_Evaluation(<?php echo $joptions;?>);
		</script>
		<?php
	}		
}
