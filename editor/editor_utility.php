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
    /**
     * @var bool $jquerynoload Flag to indicate if jQuery and jQuery UI libraries have been loaded.
     */
    private static $jquerynoload = true;

    /**
     * Generates the jQuery and jQuery UI libraries if not already loaded.
     * This function is called when the page may need jQuery.
     */
    public static function generate_jquery() {
        global $PAGE;
        if (self::$jquerynoload) {
            $PAGE->requires->jquery();
            $PAGE->requires->jquery_plugin('ui');
            $PAGE->requires->jquery_plugin('ui-css');
            self::$jquerynoload = false;
        }
    }

    /**
     * Generates the CSS and JavaScript required for evaluation.
     * This function is called when the page may need evaluation.
     */
    public static function generate_requires_evaluation() {
        global $PAGE;
        self::generate_jquery();
    }

    /**
     * Generates the JavaScript and HTML required for the VPL IDE.
     *
     * @param \mod_vpl $vpl The VPL instance.
     * @param array $options Additional options for the IDE.
     */
    public static function generate_requires($vpl, $options) {
        require_once(__DIR__ . '/../jail/jailserver_manager.class.php');
        global $PAGE;
        global $CFG;
        $userprefs = \mod_vpl\util\userpreferences::get();
        $userdefaults = \mod_vpl\util\userpreferences::FIELDSDEFAULTS;
        $plugincfg = get_config('mod_vpl');
        $tagid = 'vplide';
        if (isset($plugincfg->editor_theme)) {
            $userdefaults['editorTheme'] = $plugincfg->editor_theme;
        }
        $userprefs = array_merge($userdefaults, (array)$userprefs);
        foreach ($userprefs as $key => $value) {
            $options[$key] = $value;
        }
        $options['lang'] = $CFG->lang;
        $options['postMaxSize'] = \mod_vpl\util\phpconfig::get_post_max_size();
        $options['isGroupActivity'] = $vpl->is_group_activity();
        $options['isTeacher'] = $vpl->has_capability(VPL_GRADE_CAPABILITY) || $vpl->has_capability(VPL_MANAGE_CAPABILITY);
        $options['lsavailable'] = vpl_jailserver_manager::get_ls_list($vpl);
        self::generate_jquery();
        $opt = new stdClass();
        $opt->scriptPath = $CFG->wwwroot . '/mod/vpl/editor';
        $PAGE->requires->js(new moodle_url('/mod/vpl/thirdpartylibs/marked/marked.umd.min.js'));
        $PAGE->requires->js(new moodle_url('/mod/vpl/thirdpartylibs/filterxss/xss.min.js'));
        $PAGE->requires->js_call_amd('mod_vpl/vplutil', 'init', [$opt]);
        $PAGE->requires->js_call_amd('mod_vpl/vplide', 'init', [$tagid, $options]);
    }

    /**
     * Prints the JavaScript i18n translations for the VPL editor.
     */
    public static function print_js_i18n() {
        global $CFG;
        $i18n = json_encode(self::i18n());
        $html = <<<"HTML"
        <script>
        window.VPLi18n = $i18n;
        </script>
        HTML;
        if ($CFG->debugdeveloper) {
            $html .= <<<"HTML"
            <script>window.VPLDebugMode = true;</script>
            HTML;
        }
        echo $html;
    }

    /**
     * Prints the JavaScript description of the VPL activity.
     *
     * @param \mod_vpl $vpl The VPL instance.
     * @param int $userid The user ID for which to get the variation HTML.
     */
    public static function print_js_description($vpl, $userid) {
        $description = $vpl->get_variation_html($userid);
        $description .= $vpl->get_fulldescription_with_basedon();
        $descriptionstr = json_encode($description);
        $html = <<<"HTML"
        <script>
        window.VPLDescription = $descriptionstr;
        </script>
        HTML;
        echo $html;
    }

    /**
     * Prints the HTML tag for the VPL IDE.
     */
    public static function print_tag() {
        $tagid = 'vplide';
        $filelist = s(get_string('filelist', VPL));
        $newfilename = s(get_string('new_file_name', VPL));
        $rename = s(get_string('rename'));
        $comments = s(get_string('comments', VPL));
        $editor = s(get_string('editor', VPL));
        $editortheme = s(get_string('editortheme', VPL));
        $fontsize = s(get_string('fontsize', VPL));
        $keybinding = s(get_string('keybinding', VPL));
        $showinvisibles = s(get_string('showinvisibles', VPL));
        $liveautocompletion = s(get_string('liveautocompletion', VPL));
        $terminal = s(get_string('terminal', VPL));
        $terminaltheme = s(get_string('terminaltheme', VPL));
        $unsaved = s(get_string('unsaved', VPL));
        $lscmdefinition = s(get_string('ls:cmdefinition', VPL));
        $lscmreferences = s(get_string('ls:cmreferences', VPL));
        $lscmimplementation = s(get_string('ls:cmimplementation', VPL));
        $lscmrename = s(get_string('ls:cmrename', VPL));
        $lscmformatrange = s(get_string('ls:cmformatrange', VPL));
        $lscmformat = s(get_string('ls:cmformat', VPL));
        $lscmcodeaction = s(get_string('ls:cmcodeaction', VPL));
        $ok = s(get_string('ok'));
        $cancel = s(get_string('cancel'));

        $html = <<<"HTML"
<div id="$tagid" class="vpl_ide vpl_ide_root">
    <div id="vpl_menu" class="vpl_ide_menu"></div>
    <div id="vpl_tr" class="vpl_ide_tr">
        <div id="vpl_filelist" style="display: none;">
            <div id="vpl_filelist_header">$filelist</div>
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
    <div id="vpl_ide_statusbar" class="vpl_ide_statusbar ui-corner-all">
            <span class="vpl_ide_statusbar_filelist"></span>
            <span class="vpl_ide_statusbar_filename" style="display: none;"></span>
            <span class="vpl_ide_statusbar_unsaved" style="display: none;">$unsaved</span>
            <span class="vpl_ide_statusbar_shrightpanel" style="display: none;"></span>
            <span class="vpl_ide_statusbar_preferences"></span>
            <span class="vpl_ide_statusbar_position"></span>
            <span class="vpl_ide_statusbar_lsp" style="display: none;"></span>
            <span class="vpl_ide_statusbar_language"></span>
            <span class="vpl_ide_statusbar_action"></span>
    </div>
    <div id="vpl_ide_dialog_new" class="vpl_ide_dialog"
        style="display: none;">
        <fieldset>
            <label for="vpl_ide_input_newfilename">
                $newfilename</label> <input
                type="text" id="vpl_ide_input_newfilename"
                name="vpl_ide_input_newfilename" value=""
                class="ui-widget-content ui-corner-all" autofocus /><br>
        </fieldset>
    </div>
    <div id="vpl_ide_dialog_renamedir" class="vpl_ide_dialog"
        style="display: none;">
        <fieldset>
            <label for="vpl_ide_input_renamedirectory">
                $rename</label> <input
                type="text" id="vpl_ide_input_renamedirectory"
                name="vpl_ide_input_renamedirectory" value=""
                class="ui-widget-content ui-corner-all" autofocus />
                <input type="hidden" id="vpl_ide_input_olddirectoryname" value=""/>
        </fieldset>
    </div>
    <div id="vpl_ide_dialog_rename" class="vpl_ide_dialog"
        style="display: none;">
        <fieldset>
            <label for="vpl_ide_input_renamefilename">
                $rename</label> <input
                type="text" id="vpl_ide_input_renamefilename"
                name="vpl_ide_input_renamefilename" value=""
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
    <div id="vpl_ide_dialog_preferences" class="vpl_ide_dialog"
        style="display: none;">
        <div>
        <fieldset>
            <legend>$editor</legend>
            <label>$editortheme</label><br>
            <select id="vpl_ide_preferences_editor_theme">
            </select>
            <br><br>
            <label>$fontsize:&nbsp;<span class="vpl_fontsize_slider_value"></span></label><br>
            <div class="vpl_fontsize_slider"></div>
            <br>
            <label>$keybinding</label><br>
            <select id="vpl_ide_preferences_editor_keybinding">
            </select>
            <br><br>
            <label><input type="checkbox" id="vpl_ide_preferences_editor_showinvisibles"> $showinvisibles</label>
            <br>
            <label><input type="checkbox" id="vpl_ide_preferences_editor_liveautocompletion"> $liveautocompletion</label>
        </fieldset>
        <fieldset>
            <legend>$terminal</legend>
            <label>$terminaltheme</label><br>
            <select id="vpl_ide_preferences_terminal_theme">
            </select>
            <br><br>
            <label>$fontsize:&nbsp;<span class="vpl_termfontsize_slider_value"></span></label><br>
            <div class="vpl_termfontsize_slider"></div>
        </fieldset>
        </div>
    </div>
    <div id="vpl_ide_dialog_comments" class="vpl_ide_dialog"
        style="display: none;">
        <fieldset>
            <label for="vpl_ide_input_comments">
                $comments</label> <textarea
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
            <li><a href="https://xtermjs.org/" target="_blank">xterm.js</a>:
                A terminal emulator written in JavaScript. Copyright (c) 2017-2019,
                The xterm.js authors (MIT License)</li>
            <li><a href="http://kanaka.github.io/noVNC/" target="_blank">noVNC</a>:
                VNC client using HTML5 (WebSockets, Canvas). noVNC is Copyright (C)
                2011 Joel Martin &lt;github@martintribe.org&gt; (<a
                href="../thirdpartylibs/noVNC/LICENSE.txt" target="_blank">licence</a>)</li>
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
        <div></div>
    </div>
    <div id="vpl_ls_contextmenu" class="vpl_ls_menu">
        <ul>
            <li id='vpl_ls_cm_definition' data-lsaction="definitionRequest">
                <span class="vpl_ls_cm_label">$lscmdefinition</span>
                <span class="vpl_ls_cm_shortcut">F12</span>
            </li>
            <li id='vpl_ls_cm_implementation' data-lsaction="implementationRequest">
                <span class="vpl_ls_cm_label">$lscmimplementation</span>
                <span class="vpl_ls_cm_shortcut">Ctrl-F12</span>
            </li>
            <li id='vpl_ls_cm_references' data-lsaction="referencesRequest">
                <span class="vpl_ls_cm_label">$lscmreferences</span>
                <span class="vpl_ls_cm_shortcut">Shift-F12</span>
            </li>
            <li id='vpl_ls_cm_rename' data-lsaction="renameSymbolRequest">
                <span class="vpl_ls_cm_label">$lscmrename</span>
                <span class="vpl_ls_cm_shortcut">Ctrl-F2</span>
            </li>
            <li id='vpl_ls_cm_formatrange' data-lsaction="rangeFormattingRequest">
                <span class="vpl_ls_cm_label">$lscmformatrange</span>
                <span class="vpl_ls_cm_shortcut">Ctrl-Shift-F</span>
            </li>
            <li id='vpl_ls_cm_format' data-lsaction="formattingRequest">
                <span class="vpl_ls_cm_label">$lscmformat</span>
                <span class="vpl_ls_cm_shortcut">Shift-Alt-F</span>
            </li>
            <li id='vpl_ls_cm_codeaction' data-lsaction="codeActionRequest">
                <span class="vpl_ls_cm_label">$lscmcodeaction</span>
                <span class="vpl_ls_cm_shortcut">Ctrl-.</span>
            </li>
        </ul>
    </div>
    <div id="vpl_ls_rename">
        <label for="vpl_ls_rename_input_field">$rename </label>
        <input type="text" id="vpl_ls_rename_input_field" size="20" autofocus />
        <button id="vpl_ls_rename_ok_btn" class="vpl_ls_rename_btn">$ok</button>
        <button id="vpl_ls_rename_cancel_btn" class="vpl_ls_rename_btn">$cancel</button>
    </div>
    <div id="vpl_ls_codeactions" class="vpl_ls_menu">
        <ul>
        </ul>
    </div>
HTML;
        echo $html;
    }

    /**
     * Get the list of i18n translations for the editor
     */
    public static function i18n() {
        $vplwords = [
                'about',
                'execution_connection_failed',
                'execution_connection_failed_help',
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
                'directory_not_renamed',
                'donotshowagain',
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
                'incorrect_directory_name',
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
                'rename_directory',
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
                'shrightpanel',
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
                'showparentfiles',
                'preferences',
                'editor',
                'editortheme',
                'keybinding',
                'showinvisibles',
                'liveautocompletion',
                'terminal',
                'terminaltheme',
                'open_browser',
                'open_private_browser',
                'update',
                'updating',
                'unsaved',
                'unzipping',
                'references',
                'referencesto',
        ];
        $words = [
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
                'reset',
        ];
        $list = [];
        foreach ($vplwords as $word) {
            $list[$word] = get_string($word, VPL);
        }
        foreach ($words as $word) {
            $list[$word] = get_string($word);
        }
        $list['close'] = get_string('closebuttontitle');
        $list['more'] = get_string('showmore', 'form');
        $list['less'] = get_string('showless', 'form');
        $list['fontsize'] = get_string('fontsize', 'editor');
        $list['theme'] = get_string('theme');
        return $list;
    }

    /**
     * Generates the script to evaluate a student.
     *
     * @param string $ajaxurl The AJAX URL for evaluation.
     * @param string $nexturl The URL to redirect after evaluation.
     */
    public static function generate_evaluate_script($ajaxurl, $nexturl) {
        global $PAGE;
        $options = [];
        $options['ajaxurl'] = $ajaxurl;
        $options['nexturl'] = $nexturl;
        $PAGE->requires->js_call_amd('mod_vpl/evaluationmonitor', 'init', [$options]);
    }

    /**
     * Generates the script to evaluate multiple students in a batch.
     *
     * @param array $ajaxurls Array of AJAX URLs for evaluation.
     * @param array $users Array of users to be evaluated.
     */
    public static function generate_batch_evaluate_sript($ajaxurls, $users) {
        global $PAGE;
        $jsonusers = json_encode($users);
        $url = new moodle_url('/mod/vpl/jscript/updatesublist.js');
        $html = <<<"HTML"
        <script src="{$url}"></script>
        <script>
            if  (typeof window.VPL === 'undefined') {
                window.VPL = {};
            }
            window.VPL.evaluateStudents = {$jsonusers};
        </script>
        HTML;
        echo $html;
        $PAGE->requires->js_call_amd('mod_vpl/evaluationmonitor', 'multievaluation', ['ajaxutls' => $ajaxurls]);
    }
}
