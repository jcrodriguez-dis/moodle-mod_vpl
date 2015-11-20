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
 * VPL Syntaxhighlighter for shell scripts
 *
 * @package mod_vpl
 * @copyright 2009 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(__FILE__).'/sh_text.class.php');

class vpl_sh_sh extends vpl_sh_text {
    protected $predefinedvars;
    protected function is_identifier_char($c) {
        return ($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || ($c >= '0' && $c <= '9') || ($c == '$') || ($c == '_');
    }
    protected function show_pending(&$rest) {
        if (array_key_exists( $rest, $this->reserved )) {
            $this->initTag( self::C_RESERVED );
            parent::show_pending( $rest );
            echo self::endTag;
        } else if (array_key_exists( $rest, $this->predefinedvars )) {
            $this->initTag( self::C_VARIABLE );
            parent::show_pending( $rest );
            echo self::endTag;
        } else {
            parent::show_pending( $rest );
        }
        $rest = '';
    }
    public function __construct() {
        $list = array (
                'exec',
                'eval',
                'cd',
                'exit',
                'export',
                'getopts',
                'hash',
                'pwd',
                'readonly',
                'return',
                'shift',
                'test',
                'times',
                'trap',
                'unset',
                'umask',
                'alias',
                'bind',
                'builtin',
                'command',
                'declare',
                'echo',
                'enable',
                'help',
                'let',
                'local',
                'logout',
                'printf',
                'read',
                'shopt',
                'source',
                'type',
                'typeset',
                'ulimit',
                'unalias',
                'set',
                'until',
                'do',
                'done',
                'while',
                'for',
                'break',
                'continue',
                'if',
                'then',
                'elif',
                'else',
                'fi',
                'case',
                'in',
                'esac',
                'select',
                'function'
        );
        foreach ($list as $word) {
            $this->reserved [$word] = 1;
        }
        $list = array (
                'CDPATH',
                'HOME',
                'IFS',
                'MAIL',
                'MAILPATH',
                'OPTARG',
                'OPTIND',
                'PATH',
                'PS1',
                'PS2',
                'BASH',
                'BASH_ENV',
                'BASH_VERSION',
                'BASH_VERSINFO',
                'COLUMNS',
                'COMP_CWORD',
                'COMP_LINE',
                'COMP_POINT',
                'COMP_WORDS',
                'COMPREPLY',
                'DIRSTACK',
                'EUID',
                'FCEDIT',
                'FIGNORE',
                'FUNCNAME',
                'GLOBIGNORE',
                'GROUPS',
                'histchars',
                'HISTCMD',
                'HISTCONTROL',
                'HISTFILE',
                'HISTFILESIZE',
                'HISTIGNORE',
                'HISTSIZE',
                'HOSTFILE',
                'HOSTNAME',
                'HOSTTYPE',
                'IGNOREEOF',
                'INPUTRC',
                'LANG',
                'LC_ALL',
                'LC_COLLATE',
                'LC_CTYPE',
                'LC_MESSAGES',
                'LC_NUMERIC',
                'LINENO',
                'LINES',
                'MACHTYPE',
                'MAILCHECK',
                'OLDPWD',
                'OPTERR',
                'OSTYPE',
                'PIPESTATUS',
                'POSIXLY_CORRECT',
                'PPID',
                'PROMPT_COMMAND',
                'PS3',
                'PS4',
                'PWD',
                'RANDOM',
                'REPLY',
                'SECONDS',
                'SHELLOPTS',
                'SHLVL',
                'TIMEFORMAT',
                'TMOUT',
                'UID',
                '1',
                '2',
                '3'
        );
        foreach ($list as $word) {
            $this->predefinedvars [$word] = 1;
        }
        parent::__construct();
    }
    const IN_REGULAR = 0;
    const IN_STRING = 1;
    const IN_DSTRING = 2;
    const IN_CSTRING = 3;
    const IN_COMMENT = 4;
    public function print_file($filename, $filedata, $showln = true) {
        $this->begin( $filename, $showln );
        $state = self::IN_REGULAR;
        $pending = '';
        $l = strlen( $filedata );
        if ($l) {
            $this->show_line_number();
        }
        $current = '';
        $previous = '';
        for ($i = 0; $i < $l; $i ++) {
            $previous = $current;
            $current = $filedata [$i];
            if ($i < ($l - 1)) {
                $next = $filedata [$i + 1];
            } else {
                $next = '';
            }
            if ($current == self::CR) {
                if ($next == self::LF) {
                    continue;
                } else {
                    $current = self::LF;
                }
            }

            switch ($state) {
                case self::IN_REGULAR :
                    if ($current == '#') { // Begin coment.
                        $this->show_pending( $pending );
                        $this->initTag( self::C_COMMENT );
                        $pending = $current;
                        $state = self::IN_COMMENT;
                    } else if ($current == '"') {
                        $this->show_pending( $pending );
                        $this->initTag( self::C_STRING );
                        $pending = $current;
                        $state = self::IN_DSTRING;
                    } else if ($current == '\\') {
                        $pending .= $current . $next;
                        $current = $next;
                        $i ++;
                    } else if ($current == "'") {
                        $this->show_pending( $pending );
                        $this->initTag( self::C_STRING );
                        $pending = $current;
                        $state = self::IN_STRING;
                    } else if ($current == '$') {
                        $this->show_pending( $pending );
                        if ($next == '\'') {
                            $this->initTag( self::C_STRING );
                            $pending = $current . $next;
                            $current = $next;
                            $state = self::IN_CSTRING;
                            $i ++;
                        } else if (($next >= '0' && $next <= '9') || $next == '*'
                                   || $next == '@' || $next == '#' || $next == '?'
                                   || $next == '-' || $next == '$' || $next == '!'
                                   || $next == '_') { // Parms.
                            $this->show_pending( $pending );
                            $this->initTag( self::C_VARIABLE );
                            $this->show_text( $current . $next );
                            $this->endTag();
                            $current = $next;
                            $i ++;
                        } else {
                            $pending .= $current;
                        }
                    } else if ($current == self::LF) {
                        $this->show_pending( $pending );
                        $this->show_text( $current );
                        $this->show_line_number();
                    } else if ($this->is_identifier_char( $current )) {
                        $pending .= $current;
                    } else {
                        $this->show_pending( $pending );
                        $this->show_text( $current );
                    }
                    break;
                case self::IN_STRING :
                    if ($current == "'") {
                        $this->show_pending( $pending );
                        $this->show_text( $current );
                        $this->endTag();
                        $state = self::IN_REGULAR;
                    } else {
                        $pending .= $current;
                    }
                    break;
                case self::IN_DSTRING :
                    if ($current == '"') {
                        if ($pending > '' && $pending [0] == '$') {
                            $this->initTag( self::C_VARIABLE );
                            $this->show_pending( $pending );
                            $this->endTag();
                        } else {
                            $this->show_pending( $pending );
                        }
                        $this->show_text( $current );
                        $this->endTag();
                        $state = self::IN_REGULAR;
                    } else if ($current == '\\') {
                        $pending .= $current . $next;
                        $current = $next;
                        $i ++;
                    } else if ($current == '$') {
                        if ($pending > '' && $pending [0] == '$') {
                            $this->initTag( self::C_VARIABLE );
                            $this->show_pending( $pending );
                            $this->endTag();
                        } else {
                            $this->show_pending( $pending );
                        }
                        $pending .= $current;
                    } else {
                        if ($pending > '' && $pending [0] == '$' && ! $this->is_identifier_char( $current )) {
                            $this->initTag( self::C_VARIABLE );
                            $this->show_pending( $pending );
                            $this->endTag();
                        }
                        $pending .= $current;
                    }
                    break;
                case self::IN_CSTRING :
                    if ($current == '\'') {
                        $this->show_pending( $pending );
                        $this->show_text( $current );
                        $this->endTag();
                        $state = self::IN_REGULAR;
                    } else if ($current == '\\') {
                        $pending .= $current . $next;
                        $current = $next;
                        $i ++;
                    }
                    break;
                case self::IN_COMMENT :
                    if ($current == self::LF) {
                        $this->show_pending( $pending );
                        $this->endTag();
                        $this->show_text( $current );
                        $this->show_line_number();
                        $state = self::IN_REGULAR;
                    } else {
                        $pending .= $current;
                    }
                    break;
            }
        }
        $this->show_pending( $pending );
        if ($state != self::IN_REGULAR) {
            $this->endTag();
        }
        $this->end();
    }
}
