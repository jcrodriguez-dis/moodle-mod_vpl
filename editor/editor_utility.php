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

class vpl_editor_util {
    public static function generate_jquery() {
        global $PAGE;
        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('ui');
        $PAGE->requires->jquery_plugin('ui-css');
    }
    public static function generate_requires_evaluation() {
        global $PAGE;
        self::generate_jquery();
        $PAGE->requires->css( new moodle_url( '/mod/vpl/editor/VPLIDE.css' ) );
    }
    public static function generate_requires($vpl, $options) {
        global $PAGE;
        global $CFG;
        $plugincfg = get_config('mod_vpl');
        $tagid = 'vplide';
        if ( isset($plugincfg->editor_theme) ) {
            $options['theme'] = $plugincfg->editor_theme;
        } else {
            $options['theme'] = 'chrome';
        }
        $options['fontSize'] = get_user_preferences('vpl_editor_fontsize', 12);
        $options['theme'] = get_user_preferences('vpl_acetheme', $options['theme']);
        $options['lang'] = $CFG->lang;
        $options['postMaxSize'] = \mod_vpl\util\phpconfig::get_post_max_size();
        $options['isGroupActivity'] = $vpl->is_group_activity();
        $options['isTeacher'] = $vpl->has_capability(VPL_GRADE_CAPABILITY) || $vpl->has_capability(VPL_MANAGE_CAPABILITY);
        self::generate_jquery();
        $PAGE->requires->js( new moodle_url( '/mod/vpl/editor/zip/inflate.js' ) );
        $PAGE->requires->js( new moodle_url( '/mod/vpl/editor/zip/unzip.js' ) );
        $PAGE->requires->js( new moodle_url( '/mod/vpl/editor/xterm/term.js' ) );
        $PAGE->requires->js( new moodle_url( '/mod/vpl/editor/noVNC/include/util.js' ) );
        $PAGE->requires->js_call_amd('mod_vpl/vplide', 'init', array($tagid, $options));
    }
    public static function print_js_i18n() {
        global $CFG;
        ?>
        <script>
        window.VPLi18n = <?php echo json_encode(self::i18n());?>;
        </script>
        <?php
        if ($CFG->debugdeveloper) {
            echo '<script>window.VPLDebugMode = true;</script>';
        }
    }
    public static function print_js_description($vpl, $userid) {
        $html = $vpl->get_variation_html($userid);
        $html .= $vpl->get_fulldescription_with_basedon();
        ?>
        <script>
        window.VPLDescription = <?php echo json_encode($html);?>;
        </script>
        <?php
    }
    public static function print_tag() {
        $tagid = 'vplide';
        ?>
<div id="<?php echo $tagid;?>" class="vpl_ide vpl_ide_root">
    <div id="vpl_menu" class="vpl_ide_menu"></div>
    <div id="vpl_tr" class="vpl_ide_tr">
        <div id="vpl_filelist" style="display: none;">
            <div id="vpl_filelist_header"><?php p(get_string('filelist', VPL));?></div>
            <div id="vpl_filelist_content"></div>
        </div>
        <div id="vpl_tabs" class="vpl_ide_tabs">
            <div id="vpl_tabs_scroll">
                <ul id="vpl_tabs_ul"></ul>
            </div>
            <span class="vpl_ide_status"></span>
        </div>
        <div id="vpl_results" class="vpl_ide_results">
            <div id="vpl_results_accordion"></div>
        </div>
    </div>
    <div id="vpl_ide_dialog_new" class="vpl_ide_dialog"
        style="display: none;">
        <fieldset>
            <label for="vpl_ide_input_newfilename">
                <?php p(get_string('new_file_name', VPL));?></label> <input
                type="text" id="vpl_ide_input_newfilename"
                name="vpl_ide_input_newfilename" value=""
                class="ui-widget-content ui-corner-all" autofocus /><br>
        </fieldset>
    </div>
    <div id="vpl_ide_dialog_rename" class="vpl_ide_dialog"
        style="display: none;">
        <fieldset>
            <label for="vpl_ide_input_renamefilename">
                <?php p(get_string('rename'));?></label> <input
                type="text" id="vpl_ide_input_renamefilename"
                name="vpl_ide_input_renamefilename" value=""
                class="ui-widget-content ui-corner-all" autofocus /><br>
        </fieldset>
    </div>
    <div id="vpl_ide_dialog_delete" class="vpl_ide_dialog"
        style="display: none;">
        <fieldset>
            <label for="vpl_ide_input_deletefilename">
                <?php p(get_string('delete'));?></label> <input
                type="text" id="vpl_ide_input_deletefilename"
                name="vpl_ide_input_deletefilename" value=""
                class="ui-widget-content ui-corner-all" autofocus /><br>
        </fieldset>
    </div>
    <div id="vpl_ide_dialog_sort" class="vpl_ide_dialog"
        style="display: none;">
        <ol id="vpl_sort_list"></ol>
    </div>
    <div id="vpl_ide_dialog_multidelete" class="vpl_ide_dialog"
        style="display: none;">
        <fieldset id="vpl_multidelete_list"></fieldset>
    </div>
    <div id="vpl_ide_dialog_fontsize" class="vpl_ide_dialog"
        style="display: none;">
        <div class="vpl_fontsize_slider_value"></div>
        <div class="vpl_fontsize_slider"></div>
    </div>
    <div id="vpl_ide_dialog_acetheme" class="vpl_ide_dialog" style="display: none;">
        <select>
           <option value="ambiance">Ambiance</option>
           <option value="chaos">Chaos</option>
           <option value="chrome">Chrome</option>
           <option value="clouds">Clouds</option>
           <option value="clouds_midnight">Clouds Midnight</option>
           <option value="cobalt">Cobalt</option>
           <option value="crimson_editor">Crimson Editor</option>
           <option value="dawn">Dawn</option>
           <option value="dracula">Dracula</option>
           <option value="dreamweaver">Dreamweaver</option>
           <option value="eclipse">Eclipse</option>
           <option value="github">GitHub</option>
           <option value="gob">Gob</option>
           <option value="gruvbox">Gruvbox</option>
           <option value="idle_fingers">idle Fingers</option>
           <option value="iplastic">IPlastic</option>
           <option value="katzenmilch">Katzenmilch</option>
           <option value="kr_theme">Kr theme</option>
           <option value="kuroir">Kuroir</option>
           <option value="merbivore">Merbivore</option>
           <option value="merbivore_soft">Merbivore Soft</option>
           <option value="mono_industrial">Mono Industrial</option>
           <option value="monokai">Monokai</option>
           <option value="pastel_on_dark">Pastel on dark</option>
           <option value="solarized_dark">Solarized Dark</option>
           <option value="solarized_light">Solarized Light</option>
           <option value="sqlserver">SQL Server</option>
           <option value="terminal">Terminal</option>
           <option value="textmate">TextMate</option>
           <option value="tomorrow">Tomorrow</option>
           <option value="tomorrow_night">Tomorrow Night</option>
           <option value="tomorrow_night_blue">Tomorrow Night Blue</option>
           <option value="tomorrow_night_bright">Tomorrow Night Bright</option>
           <option value="tomorrow_night_eighties">Tomorrow Night 80s</option>
           <option value="twilight">Twilight</option>
           <option value="vibrant_ink">Vibrant Ink</option>
           <option value="xcode">XCode</option>
        </select>
    </div>
    <div id="vpl_ide_dialog_comments" class="vpl_ide_dialog"
        style="display: none;">
        <fieldset>
            <label for="vpl_ide_input_comments">
                <?php p(get_string('comments', VPL));?></label> <textarea
                id="vpl_ide_input_comments" name="vpl_ide_input_comments"
                class="ui-widget-content ui-corner-all" autofocus ></textarea>
        </fieldset>
    </div>
    <div id="vpl_ide_dialog_about" class="vpl_ide_dialog"
        style="display: none;">
        <div>
        <h3>IDE for VPL</h3>
        This IDE is part of VPL <a href="http://vpl.dis.ulpgc.es"
            target="_blank">Virtual Programming Lab for Moodle</a><br> Author:
        Juan Carlos Rodríguez del Pino &lt;jcrodriguez@dis.ulpgc.es&gt;<br>
        Licence: <a href="http://www.gnu.org/copyleft/gpl.html"
            target="_blank">GNU GPL v3</a><br> This software uses/includes the
        following software under the corresponding licence:
        <ul>
            <li><a href="http://ace.c9.io" target="_blank">ACE</a>: an embeddable
                code editor written in JavaScript. Copyright (c) 2010, Ajax.org B.V.
                (<a href="../editor/ace9/LICENSE" target="_blank">licence</a>)</li>
            <li><a href="https://github.com/chjj/term.js/" target="_blank">term.js</a>:
                A full xterm clone written in javascript. Copyright (c) 2012-2013,
                Christopher Jeffrey (MIT License)</li>
            <li><a href="http://kanaka.github.io/noVNC/" target="_blank">noVNC</a>:
                VNC client using HTML5 (WebSockets, Canvas). noVNC is Copyright (C)
                2011 Joel Martin &lt;github@martintribe.org&gt; (<a
                href="../editor/noVNC/LICENSE.txt" target="_blank">licence</a>)</li>
            <li>unzip.js: August Lilleaas</li>
            <li>inflate.js: August Lilleaas and Masanao Izumo &lt;iz@onicos.co.jp&gt;</li>
            <li><a href="https://developers.google.com/blockly" target="_blank">Blockly</a>:
               A library for building visual programming editors
               (<a href="../editor/blockly/LICENSE" target="_blank">licence</a>)</li>
            <li><a href="https://github.com/NeilFraser/JS-Interpreter" target="_blank">JS-Interpreter</a>:
               A sandboxed JavaScript interpreter in JavaScript
               (<a href="../editor/acorn/LICENSE" target="_blank">licence</a>)</li>
        </ul>
        </div>
    </div>
    <form style="display: none;">
        <input type="file" multiple="multiple" id="vpl_ide_input_file" />
    </form>
    <div id="vpl_ide_dialog_shortcuts" class="vpl_ide_dialog" style="display: none;" >
        <div class="vpl_ide_dialog_content"></div>
    </div>
    <div id="vpl_dialog_terminal">
        <pre id="vpl_terminal" class="vpl_terminal"></pre>
    </div>
    <div id="vpl_dialog_terminal_clipboard" class="vpl_ide_dialog vpl_clipboard" style="display: none;">
        <div class="vpl_clipboard_label1"></div><br>
        <textarea readonly="readonly" class="vpl_clipboard_entry1"></textarea><br>
        <div class="vpl_clipboard_label2"></div><br>
        <textarea class="vpl_clipboard_entry2"></textarea>
    </div>
    <div id="vpl_dialog_vnc_clipboard" class="vpl_ide_dialog vpl_clipboard" style="display: none;">
        <div class="vpl_clipboard_label1"></div><br>
        <textarea readonly="readonly" class="vpl_clipboard_entry1"></textarea><br>
        <div class="vpl_clipboard_label2"></div><br>
        <textarea class="vpl_clipboard_entry2"></textarea>
    </div>
    <div id="vpl_dialog_vnc" style="display: none;">
        <canvas class="vpl_noVNC_canvas">
                Canvas not supported.
         </canvas>
    </div>
</div>
        <?php
    }
    /**
     * Get the list of i18n translations for the editor
     */
    public static function i18n() {
        $vplwords = array (
                'about',
                'acceptcertificates',
                'acceptcertificatesnote',
                'binaryfile',
                'browserupdate',
                'changesNotSaved',
                'clipboard',
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
                'description',
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
                'keyboard',
                'maxfilesexceeded',
                'new',
                'next',
                'load',
                'loading',
                'open',
                'options',
                'outofmemory',
                'paste',
                'print',
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
                'shortcuts',
                'starting',
                'sureresetfiles',
                'timeleft',
                'timeout',
                'undo',
                'multidelete',
                'basic',
                'intermediate',
                'advanced',
                'variables',
                'operatorsvalues',
                'control',
                'inputoutput',
                'functions',
                'lists',
                'math',
                'text',
                'start',
                'startanimate',
                'stop',
                'pause',
                'resume',
                'step',
                'breakpoint',
                'selectbreakpoint',
                'removebreakpoint',
                'maxpostsizeexceeded',
        );
        $words = array (
                'cancel',
                'closebuttontitle',
                'error',
                'import',
                'modified',
                'no',
                'notice',
                'ok',
                'required',
                'sort',
                'warning',
                'yes',
                'deleteselected',
                'selectall',
                'deselectall',
                'reset'
        );
        $list = Array ();
        foreach ($vplwords as $word) {
            $list[$word] = get_string( $word, VPL );
        }
        foreach ($words as $word) {
            $list[$word] = get_string( $word );
        }
        $list['close'] = get_string( 'closebuttontitle' );
        $list['more'] = get_string( 'showmore', 'form' );
        $list['less'] = get_string( 'showless', 'form' );
        $list['fontsize'] = get_string( 'fontsize', 'editor' );
        $list['theme'] = get_string( 'theme' );
        return $list;
    }
    public static function generate_evaluate_script($ajaxurl, $nexturl) {
        global $PAGE;
        $options = Array ();
        $options['ajaxurl'] = $ajaxurl;
        $options['nexturl'] = $nexturl;
        $PAGE->requires->js_call_amd('mod_vpl/evaluationmonitor', 'init', array($options) );
    }
    public static function generate_batch_evaluate_sript($ajaxurls) {
        $options = Array ();
        $options['ajaxurls'] = $ajaxurls;
        $joptions = json_encode( $options );
        self::print_js_i18n();
        ?>
<script>
    VPL_Batch_Evaluation(<?php echo $joptions;?>);
</script>
        <?php
    }
}
