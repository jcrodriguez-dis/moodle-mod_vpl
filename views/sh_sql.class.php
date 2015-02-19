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
 * Syntaxhighlighter for SQL language
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/sh_base.class.php';

class vpl_sh_sql extends vpl_sh_base{
    var $keywords;
    protected function show_pending(&$rest){
        $upper = strtoupper($rest);
        if(array_key_exists($upper  , $this->reserved)){
            $this->initTag(self::c_reserved);
            parent::show_pending($rest);
            echo self::endTag;
        }elseif(array_key_exists($upper  , $this->keywords)){
            $this->initTag(self::c_variable);
            parent::show_pending($rest);
            echo self::endTag;
        }else{
            parent::show_pending($rest);
        }
    }
    protected function show_text($rest){
        parent::show_text($rest);
        if($rest == self::LF){
            $this->show_line_number();
        }
    }
    function __construct(){
        $this->reserved= array('ABSOLUTE'=>0, 'ACTION'=>0, 'ADD'=>0, 'ALL'=>0, 'ALLOCATE'=>0, 'ALTER'=>0, 'AND'=>0, 'ANY'=>0, 'ARE'=>0,
                            'AS'=>0, 'ASC'=>0, 'ASSERTION'=>0, 'AT'=>0, 'AUTHORIZATION'=>0, 'AVG'=>0,
                            'BEGIN'=>0, 'BETWEEN'=>0, 'BIT'=>0, 'BIT_LENGTH'=>0, 'BOTH'=>0, 'BY'=>0,
                            'CASCADE'=>0, 'CASCADED'=>0, 'CASE'=>0, 'CAST'=>0, 'CATALOG'=>0, 'CHAR'=>0,
                            'CHARACTER'=>0, 'CHARACTER_LENGTH'=>0, 'CHAR_LENGTH'=>0, 'CHECK'=>0,
                            'CLOSE'=>0, 'COALESCE'=>0, 'COLLATE'=>0, 'COLLATION'=>0, 'COLUMN'=>0,
                            'COMMIT'=>0, 'CONNECT'=>0, 'CONNECTION'=>0, 'CONSTRAINT'=>0,
                            'CONSTRAINTS'=>0, 'CONTINUE'=>0, 'CONVERT'=>0, 'CORRESPONDING'=>0,
                            'CREATE'=>0, 'CROSS'=>0, 'CURRENT'=>0, 'CURRENT_DATE'=>0, 'CURRENT_TIME'=>0,
                            'CURRENT_TIMESTAMP'=>0, 'CURRENT_USER'=>0, 'CURSOR'=>0, 'DATE'=>0, 'DAY'=>0, 'DEALLOCATE'=>0,
                            'DEC'=>0, 'DECIMAL'=>0, 'DECLARE'=>0, 'DEFAULT'=>0, 'DEFERRABLE'=>0, 'DEFERRED'=>0, 'DELETE'=>0,
                            'DESC'=>0, 'DESCRIBE'=>0, 'DESCRIPTOR'=>0, 'DIAGNOSTICS'=>0, 'DISCONNECT'=>0, 'DISTINCT'=>0,
                            'DOMAIN'=>0, 'DOUBLE'=>0, 'DROP'=>0, 'ELSE'=>0, 'END'=>0, 'END -EXEC'=>0, 'ESCAPE'=>0, 'EXCEPT'=>0,
                            'EXCEPTION'=>0, 'EXEC'=>0, 'EXECUTE'=>0, 'EXISTS'=>0, 'EXTERNAL'=>0, 'EXTRACT'=>0, 'FALSE'=>0,
                            'FETCH'=>0, 'FIRST'=>0, 'FLOAT'=>0, 'FOR'=>0, 'FOREIGN'=>0, 'FOUND'=>0, 'FROM'=>0, 'FULL'=>0, 'GET'=>0,
                            'GLOBAL'=>0, 'GO'=>0, 'GOTO'=>0, 'GRANT'=>0, 'GROUP'=>0, 'HAVING'=>0, 'HOUR'=>0, 'IDENTITY'=>0,
                            'IMMEDIATE'=>0, 'IN'=>0, 'INDICATOR'=>0, 'INITIALLY'=>0, 'INNER'=>0, 'INPUT'=>0, 'INSENSITIVE'=>0,
                            'INSERT'=>0, 'INT'=>0, 'INTEGER'=>0, 'INTERSECT'=>0, 'INTERVAL'=>0, 'INTO'=>0, 'IS'=>0, 'ISOLATION'=>0,
                            'JOIN'=>0, 'KEY'=>0, 'LANGUAGE'=>0, 'LAST'=>0, 'LEADING'=>0, 'LEFT'=>0, 'LEVEL'=>0, 'LIKE'=>0, 'LOCAL'=>0,
                            'LOWER'=>0, 'MATCH'=>0, 'MAX'=>0, 'MIN'=>0, 'MINUTE'=>0, 'MODULE'=>0, 'MONTH'=>0, 'NAMES'=>0, 'NATIONAL'=>0,
                            'NATURAL'=>0, 'NCHAR'=>0, 'NEXT'=>0, 'NO'=>0, 'NOT'=>0, 'NULL'=>0, 'NULLIF'=>0, 'NUMERIC'=>0,
                            'OCTET_LENGTH'=>0, 'OF'=>0, 'ON'=>0, 'ONLY'=>0, 'OPEN'=>0, 'OPTION'=>0, 'OR'=>0, 'ORDER'=>0, 'OUTER'=>0,
                            'OUTPUT'=>0, 'OVERLAPS'=>0, 'PAD'=>0, 'PARTIAL'=>0, 'POSITION'=>0, 'PRECISION'=>0, 'PREPARE'=>0,
                            'PRESERVE'=>0, 'PRIMARY'=>0, 'PRIOR'=>0, 'PRIVILEGES'=>0, 'PROCEDURE'=>0, 'PUBLIC'=>0, 'READ'=>0,
                            'REAL'=>0, 'REFERENCES'=>0, 'RELATIVE'=>0, 'RESTRICT'=>0, 'REVOKE'=>0, 'RIGHT'=>0, 'ROLLBACK'=>0,
                            'ROWS'=>0, 'SCHEMA'=>0, 'SCROLL'=>0, 'SECOND'=>0, 'SECTION'=>0, 'SELECT'=>0, 'SESSION'=>0,
                            'SESSION_USER'=>0, 'SET'=>0, 'SIZE'=>0, 'SMALLINT'=>0, 'SOME'=>0, 'SPACE'=>0, 'SQL'=>0, 'SQLCODE'=>0,
                            'SQLERROR'=>0, 'SQLSTATE'=>0, 'SUBSTRING'=>0, 'SUM'=>0, 'SYSTEM_USER'=>0, 'TABLE'=>0, 'TEMPORARY'=>0,
                            'THEN'=>0, 'TIME'=>0, 'TIMESTAMP'=>0, 'TIMEZONE_HOUR'=>0, 'TIMEZONE_MINUTE'=>0, 'TO'=>0, 'TRAILING'=>0,
                            'TRANSACTION'=>0, 'TRANSLATE'=>0, 'TRANSLATION'=>0, 'TRIM'=>0, 'TRUE'=>0, 'UNION'=>0, 'UNIQUE'=>0,
                            'UNKNOWN'=>0, 'UPDATE'=>0, 'UPPER'=>0, 'USAGE'=>0, 'USER'=>0, 'USING'=>0, 'VALUE'=>0, 'VALUES'=>0,
                            'VARCHAR'=>0, 'VARYING'=>0, 'VIEW'=>0, 'WHEN'=>0, 'WHENEVER'=>0, 'WHERE'=>0, 'WITH'=>0, 'WORK'=>0,
                            'WRITE'=>0, 'YEAR'=>0, 'ZONE'=>0);
        $this->keywords= array('ADA'=>0, 'C'=>0, 'CATALOG_NAME'=>0, 'CHARACTER_SET_CATALOG'=>0, 'CHARACTER_SET_NAME'=>0,
                'CHARACTER_SET_SCHEMA'=>0, 'CLASS_ORIGIN'=>0, 'COBOL'=>0, 'COLLATION_CATALOG'=>0, 'COLLATION_NAME'=>0,
                'COLLATION_SCHEMA'=>0, 'COLUMN_NAME'=>0, 'COMMAND_FUNCTION'=>0, 'COMMITTED'=>0, 'CONDITION_NUMBER'=>0,
                'CONNECTION_NAME'=>0, 'CONSTRAINT_CATALOG'=>0, 'CONSTRAINT_NAME'=>0, 'CONSTRAINT_SCHEMA'=>0,
                'CURSOR_NAME'=>0, 'DATA'=>0, 'DATETIME_INTERVAL_CODE'=>0, 'DATETIME_INTERVAL_PRECISION'=>0,
                'DYNAMIC_FUNCTION'=>0, 'FORTRAN'=>0, 'LENGTH'=>0, 'MESSAGE_LENGTH'=>0, 'MESSAGE_OCTET_LENGTH'=>0,
                'MESSAGE_TEXT'=>0, 'MORE'=>0, 'MUMPS'=>0, 'NAME'=>0, 'NULLABLE'=>0, 'NUMBER'=>0, 'PASCAL'=>0, 'PLI'=>0, 'REPEATABLE'=>0,
                'RETURNED_LENGTH'=>0, 'RETURNED_OCTET_LENGTH'=>0, 'RETURNED_SQLSTATE'=>0, 'ROW_COUNT'=>0, 'SCALE'=>0,
                'SCHEMA_NAME'=>0, 'SERIALIZABLE'=>0, 'SERVER_NAME'=>0, 'SUBCLASS_ORIGIN'=>0, 'TABLE_NAME'=>0, 'TYPE'=>0,
                'UNCOMMITTED'=>0, 'UNNAMED'=>0);
        parent::__construct();
    }
    const normal=0;
    const in_string=1;
    const in_char=2;
    const in_comment=3;
    const in_line_comment=4;
    //TODO number detect
    const in_number=5;
    function print_file($filename, $filedata, $showln=true){
        $this->begin($filename,$showln);
        $state = self::normal;
        $pending='';
        $l = strlen($filedata);
        if($l){
            $this->show_line_number();
        }
        $current='';
        $previous='';
        for($i=0;$i<$l;$i++){
            //TODO call line_number
            $previous=$current;
            $current=$filedata[$i];
            if($i < ($l-1)) {
                $next = $filedata[$i+1];
            }else{
                $next ='';
            }
            if($current == self::CR){
                if($next == self::LF) {
                    continue;
                }else{
                    $current = self::LF;
                }
            }
            switch($state){
                case self::normal:{
                    if(ctype_alpha($current)){
                        $pending .= $current;
                    }else{
                        if(strlen($pending)){
                            $this->show_pending($pending);
                        }
                        if($current == '/' && $next == '*'){
                            $state= self::in_comment;
                            $this->initTag(self::c_comment);
                        }
                        if($current == '-' && $next == '-'){
                            $state= self::in_line_comment;
                            $this->initTag(self::c_comment);
                        }
                        if($current == '"')    {
                            $state = self::in_string;
                            $this->initTag(self::c_string);
                        }
                        if($current == "'")    {
                            $state = self::in_char;
                            $this->initTag(self::c_string);
                        }
                        $this->show_text($current);
                    }
                    break;
                }
                case self::in_line_comment: {
                    $this->show_text($current);
                    if($current==self::LF){
                        $this->endTag();
                        $state=self::normal;
                    }
                    break;
                }
                case self::in_comment: {
                    $this->show_text($current);
                    if($current== '*' && $next == '/'){
                        $this->show_text($next);
                        $i++;
                        $this->endTag();
                        $state=self::normal;
                    }
                    break;
                }
                case self::in_string: {
                    $this->show_text($current);
                    if($current=='"'){
                        if($next=='"') {
                            $this->show_text($current);
                            $i++;
                        }else{
                            $this->endTag();
                            $state=self::normal;
                        }
                    }
                    break;
                }
                case self::in_char: {
                    $this->show_text($current);
                    if($current=="'"){
                        $this->endTag();
                        $state=self::normal;;
                    }
                    break;
                }
            }
        }
        $this->show_pending($pending);
        if($state != self::normal){
            $this->endTag();
        }
        $this->end();
    }
}
