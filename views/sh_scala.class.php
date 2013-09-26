<?php
/**
 * @version		$Id: sh_scala.class.php,v 1.2 2013-06-21 16:06:26 juanca Exp $
 * @package		VPL. Syntaxhighlighter for Scala language
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Lang Michael <michael.lang.ima10@fh-joanneum.at>
 * @author		Lückl Bernd <bernd.lueckl.ima10@fh-joanneum.at>
 * @author		Lang Johannes <johannes.lang.ima10@fh-joanneum.at>
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
require_once dirname(__FILE__).'/sh_base.class.php';

class vpl_sh_scala extends vpl_sh_base{
	protected function show_pending(&$rest){
		if(array_key_exists($rest  , $this->reserved)){
			$this->initTag(self::c_reserved);
			parent::show_pending($rest);
			echo self::endTag;
		}else{
			parent::show_pending($rest);
		}
	}
	const regular=0;
	const in_string=1;
	const in_char=2;
	const in_comment=3;
	const in_linecomment=4;	

	function __construct(){
		$this->reserved= array( 	'abstract' => true,
									'case' => true,
									'catch' => true,
									'class' => true,
									'def' => true,
									'do' => true,
									'else' => true,
									'extends' => true,
									'false' => true,
									'final' => true,
									'finally' => true,
									'for' => true,
									'forSome' => true,
									'if' => true,
									'implicit' => true,
									'import' => true,
									'lazy' => true,
									'match' => true,
									'new' => true,
									'null' => true,
									'object' => true,
									'override' => true,
									'package' => true,
									'private' => true,
									'protected' => true,
									'return' => true,
									'sealed' => true,
									'super' => true,
									'this' => true,
									'throw' => true,
									'trait' => true,
									'try' => true,
									'true' => true,
									'type' => true,
									'val' => true,
									'var' => true,
									'while' => true,
									'with' => true,
									'yield' => true,			
									
									'Byte' => true,
									'Short' => true,
									'Char' => true,
									'Int' => true,
									'Long' => true,
									'Float' => true,
									'Double' => true,
									'Boolean' => true,
									'Unit' => true,
									'String' => true);									
		parent::__construct();
	}
	
	function show_line_number(){
		echo "\n";
		parent::show_line_number();
	}

	function print_file($filename, $filedata, $showln=true){
		$this->begin($filename,$showln);
		$state = self::regular;
		$pending='';
		$first_no_space = '';
		$last_no_space = '';
		$l = strlen($filedata);
		if($l){
			$this->show_line_number();
		}
		$current='';
		$previous='';
		for($i=0;$i<$l;$i++){
			$previous=$current;
			$current=$filedata[$i];
			if($i < ($l-1)) {
				$next = $filedata[$i+1];
			}else{
				$next ='';
			}
			if($previous == self::LF){
				$last_no_space='';
				$first_no_space = '';
			}
			if($current == self::CR){
				if($next == self::LF) {
					continue;
				}else{
					$current = self::LF;
				}
			}
			if($current != ' ' && $current != "\t") {//Keep first and last char
				if($current != self::LF){
					$last_no_space=$current;
				}
				if($first_no_space == ''){
					$first_no_space = $current;
				}
			}
			switch($state){
				case self::in_comment:
					// Check end of block comment
					if($current=='*') {
						if($next=='/') {
							$state = self::regular;
							$pending .= '*/';
							$this->show_text($pending);
							$pending='';
							$this->endTag();
							$i++;
							continue 2;
						}
					}
					if($current == self::LF){
						$this->show_text($pending);
						$pending='';
						if($this->showln) { //Check to send endtag
							$this->endTag();
						}
						$this->show_line_number();
						if($this->showln) { //Check to send initTagtag
							$this->initTag(self::c_comment);
						}
					}else{
						$pending .= $current;
					}
					break;
				case self::in_linecomment:
					// Check end of comment
					if($current==self::LF){
						$this->show_text($pending);
						$pending='';
						$this->endTag();
						$this->show_line_number();
						$state=self::regular;
					}else{
						$pending .= $current;
					}
					break;
				case self::in_string:
					// Check end of string
					if($current=='"' && $previous!='\\') {
						$pending .= '"';
						$this->show_text($pending);
						$pending='';
						$this->endTag();
						$state = self::regular;
						break;
					}
					if($current==self::LF){
						$this->show_text($pending);
						$pending='';
						$this->endTag();
						$this->show_line_number();
						$this->initTag(self::c_string);
					}else{
						$pending .= $current;
					}
					//discard two backslash
					if($current=='\\' && $previous=='\\'){
						$current=' ';
					}
					break;
				case self::in_char:
					// Check end of char
					if($current=='\'' && $previous!='\\') {
						$pending .= '\'';
						$this->show_text($pending);
						$pending='';
						$this->endTag();
						$state = self::regular;
						break;
					}
					if($current==self::LF){
						$this->show_text($pending);
						$pending='';
						$this->endTag();
						$this->show_line_number();
						$this->initTag(self::c_string);
					}else{
						$pending .= $current;
					}
					//discard two backslash
					if($current=='\\' && $previous=='\\'){
						$current=' ';
					}
					break;
				case self::regular:
					if($current == '/') {
						if($next == '*') { // Begin block comments
							$state = self::in_comment;
							$this->show_pending($pending);
							$this->initTag(self::c_comment);
							$this->show_text('/*');
							$i++;
							continue 2;
						}
						if($next == '/'){ // Begin line comment
							$state = self::in_linecomment;
							$this->show_pending($pending);
							$this->initTag(self::c_comment);
							$this->show_text('//');
							$i++;
							continue 2;
						}
					}elseif($current == '"')	{
						$state = self::in_string;
						$this->show_pending($pending);
						$this->initTag(self::c_string);
						$this->show_text('"');
						break;
					}elseif($current == "'"){
						$state = self::in_char;
						$this->show_pending($pending);
						$this->initTag(self::c_string);
						$this->show_text('\'');
						break;
					}
					if(($current >= 'a' && $current <= 'z') ||
					($current >= 'A' && $current <= 'Z') ||
					($current >= '0' && $current <= '9') ||
					$current=='_' || ord($current) > 127){
						$pending .= $current;
					} else {
						$this->show_pending($pending);
						if($current == '{' || $current == '(' || $current == '['){
							$this->initHover();
						}
						if($current == self::LF){
							$this->show_line_number();
						}else{
							$aux =$current;
							$this->show_pending($aux);
						}
						if($current == ')' || $current == '}' || $current == ']'){
							$this->endHover();
						}
					}
			}
		}

		$this->show_pending($pending);
		if($state != self::regular){
			$this->endTag();
		}
		$this->end();
	}
}

?>