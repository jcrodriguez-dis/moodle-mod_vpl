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
 * Syntaxhighlighters for geshi (Used for unknow types)
 *
 * @package mod_vpl
 * @copyright 2009 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodriguez-del-Pino
 **/

require_once dirname(__FILE__).'/sh_base.class.php';
require_once dirname(__FILE__).'/sh_factory.class.php';

class vpl_sh_geshi extends vpl_sh_base{
    static $map=array(
            'ada' => 'ada',
            'ads' => 'ada',
            'adb' => 'ada',
            'asm' => 'asm',
            'bash' => 'bash',
            'c' => 'c',
            'C' => 'cpp',
            'cases' => 'cases',
            'cbl' => 'cobol',
            'cob' => 'cobol',
            'coffee' => 'coffeescript',
            'cc' => 'cpp',
            'cpp' => 'cpp',
            'hxx' => 'cpp',
            'h' => 'cpp',
            'clj' => 'clojure',
            'cs' => 'csharp',
            'css' => 'css',
            'd' => 'd',
            'erl' => 'erlang',
            'hrl' => 'erlang',
            'f' => 'fortran',
            'f77' => 'fortran',
            'go' => 'go',
            'hs' => 'haskell',
            'htm' => 'html',
            'html' => 'html5',
            'hx' => 'haxe',
            'java' => 'java5',
            'js' => 'javascript',
            'json' => 'json',
            'scm' => 'scheme',
            's' => 'scheme',
            'm' => 'matlab',
            'lisp' => 'lisp',
            'lsp' => 'lisp',
            'lua' => 'lua',
            'pas' => 'pascal',
            'p' => 'pascal',
            'perl' => 'perl6',
            'prl' => 'perl6',
            'php' => 'php',
            'pro' => 'prolog',
            'pl' => 'prolog',
            'py' => 'python',
            'r' => 'r',
            'rb' => 'ruby',
            'ruby' => 'ruby',
            'scala' => 'scala',
            'sh' => 'bash',
            'sql' => 'sql',
            'tcl' => 'tcl',
            'xml' => 'xml',
            'yaml' => 'yaml'
    );
    function print_file($filename, $filedata, $showln=true){
        $ext = strtolower(vpl_fileExtension($filename));
        if(isset(self::$map[$ext])){
            $lang=self::$map[$ext];
        }else{
            $lang='text';
        }
        $line =0;
        $insert_link = function($found) use ($filename,&$line){
            $ret='<span class="vpl_ln">';
            $line++;
            $name = $filename.'.'.$line;
            $ret .= '<a name="'.$name.'"></a>';
            $ret .= sprintf('%5d',$line);
            $ret .= ' </span>';
            return $ret.'<span ';
        };
        $code='<pre class="vpl_sh vpl_g">';
        $code .='<span syntax="'.$lang.'"';
        $code .= $showln?' linenumbers="yes"':'';
        $code .= '>'.htmlentities($filedata,ENT_NOQUOTES).'</span>';
        $code .= '</pre>';
        $html =format_text($code,FORMAT_HTML, array('noclean' => true));
        if(preg_match('(<li )',$html)==1){
            $html = preg_replace_callback('(<li )',$insert_link,$html);
            $html = preg_replace('(</li>)','</span>',$html);
            $html = preg_replace('(<div [^>]*><ol>)','',$html);
            $html = preg_replace('(</ol></div>)','',$html);
            echo $html;
        }else{
            $printer= vpl_sh_factory::get_object('text');
            $printer->print_file($filename, $filedata, $showln);
        }
    }
}
