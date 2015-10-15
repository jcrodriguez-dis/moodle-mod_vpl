<?php
/**
 * @version		$Id: tokenizer_python.class.php,v 1.2 2013-06-11 18:31:10 juanca Exp $
 * @package		VPL. Python programing language tokenizer class
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Lang Michael <michael.lang.ima10@fh-joanneum.at>
 * @author		Lückl Bernd <bernd.lueckl.ima10@fh-joanneum.at>
 * @author		Lang Johannes <johannes.lang.ima10@fh-joanneum.at>
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/tokenizer_base.class.php';

class vpl_tokenizer_python extends vpl_tokenizer_base{
	const regular=0;
	const in_string=1;
	const in_char=2;
	const in_macro=3;
	const in_comment=4;
	const in_linecomment=5;
	const in_number=6;
	protected static $python_reserved=null;
	protected $line_number;
	protected $tokens;
	protected function is_indentifier($text){
		if(strlen($text)==0){
			return false;
		}
		$first=$text[0];
		return ($first >= 'a' && $first <= 'z') ||
					($first >= 'A' && $first <= 'Z') ||
					$first=='_';
	}
	protected function is_number($text){
		if(strlen($text)==0){
			return false;
		}
		$first=$text[0];
		return $first >= '0' && $first <= '9';
	}
	protected function is_text($text){
		if(strlen($text)==0){
			return false;
		}
		$first=$text[0];
		return $first == '"' || $first == "'";
	}
	
	protected function add_pending(&$pending){
		if($pending <= ' '){
			$pending = '';
			return;
		}
		if($this->is_indentifier($pending)){
			if(isset($this->reserved[$pending])){
				$type=vpl_token_type::reserved;
			}else{
				$type=vpl_token_type::identifier;
			}
		}else{
			if($this->is_number($pending) || $this->is_text($pending)){
				$type=vpl_token_type::literal;
			}else{
				$type=vpl_token_type::operator;
			}
		}
		$this->tokens[] = new vpl_token($type,$pending,$this->line_number);
		$pending='';
	}
	function __construct(){
		if(self::$python_reserved === null){
			self::$python_reserved= array('False' => true, 'class' => true, 'finally' => true, 'is' => true, 'return' => true,
					'None' => true, 'continue' => true, 'for' => true, 'lambda' => true, 'try' => true,
					'True' => true, 'def' => true, 'from' => true, 'nonlocal' => true, 'while' => true,
					'and' => true, 'del' => true, 'global' => true, 'not' => true, 'with' => true,
					'as' => true, 'elif' => true, 'if' => true, 'or' => true, 'yield' => true,
					'assert' => true, 'else' => true, 'import' => true, 'pass' => true,
					'break' => true, 'except' => true, 'in' => true, 'raise' => true);
		}
		$this->reserved=&self::$python_reserved;
		parent::__construct();
	}
	

	function parse($filedata){
		$this->tokens=array();
		$this->line_number=1;
		$state = self::regular;
		$pending='';
		$first_no_space = '';
		$last_no_space = '';
		$l = strlen($filedata);
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
			if($i < ($l-2)) {
				$nextnext = $filedata[$i+2];
			}else{
				$nextnext = '';
			}
			if($previous == self::LF){
				$last_no_space='';
				$first_no_space = '';
				$this->line_number++;
			}
			if($current == self::CR){
				if($next == self::LF) {
					continue;
				}else{
					$this->line_number++;
					$current = self::LF;
				}
			}
			if($current != ' ' && $current != "\t") {//Keep first and last no space char
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
					if($current=='"' && $next=='"' && $nextnext=='"') {
						$i+=2;
						$state=self::regular;
						continue;
					}
					break;
				case self::in_linecomment:
					// Check end of comment
					if($current==self::LF){
						$state=self::regular;
					}
					break;
				case self::in_string:
					// Check end of string
					if($current=='"' && $previous!='\\') {
						$state = self::regular;
						break;
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
						$state = self::regular;
						break;
					}
					//discard two backslash
					if($current=='\\' && $previous=='\\'){
						$current=' ';
					}
					break;
				case self::in_number:
					if(($current >= '0' && $current <= '9') ||
					    $current == '.' || $current == 'E' || $current == 'e'){
						$pending .= $current;
						continue;
					}
					if(($current == '-' || $current == '+') && ($previous == 'E' || $previous == 'e')){
						$pending .= $current;
						continue;
					}
					$this->add_pending($pending);
					$state = self::regular;
					//Process current as regular
				case self::regular:
					if($current == '"' && $next == '"' && $nextnext == '"') {
						// Begin block comments
						$state = self::in_comment;
						$this->add_pending($pending);
						$i+=2;
						continue;
					}elseif($current == '#') {
						// Begin line comment
						$state = self::in_linecomment;
						$this->add_pending($pending);
						continue;
					}elseif($current == '"')	{
						$state = self::in_string;
						$this->add_pending($pending);
						break;
					}elseif($current == "'"){
						$state = self::in_char;
						$this->add_pending($pending);
						break;
					} elseif($current == '#' && $first_no_space==$current){
						$state = self::in_macro;
						$this->add_pending($pending);
						break;
					} elseif($current >= '0' && $current <= '9'){
						$state = self::in_number;
						$this->add_pending($pending);
						$pending = $current;
						break;
					}
					if(($current >= 'a' && $current <= 'z') ||
					($current >= 'A' && $current <= 'Z') ||
					$current=='_' || ord($current) > 127){
						$pending .= $current;
					} else {
						$this->add_pending($pending);
						if($current >' '){
							$this->add_pending($current);
						}
					}
			}
		}
		$this->add_pending($pending);
		$this->compact_operators();
	}
	function get_tokens(){
		return $this->tokens;
	}
	function compact_operators(){
		$correct = array();
		$current = false;
		foreach($this->tokens as &$next){
			if($current){
				if($current->type == vpl_token_type::operator
				   && $next->type == vpl_token_type::operator
				   && strpos('()[]{};',$current->value) === false){
				   	$current->value .= $next->value;
				   	$next=false;
				}
				$correct[] = $current;
			}
			$current = $next;
		}
		if($current){
			$correct[] = $current;
		}
		$this->tokens = $correct;
	}
	function show_tokens(){
		foreach($this->tokens as $token){
			$token->show();
		}
	}
}

?>