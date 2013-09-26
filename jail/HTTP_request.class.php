<?php
/**
 * @version		$Id: HTTP_request.class.php,v 1.9 2012-06-05 23:22:13 juanca Exp $
 * @package		VPL. http request class definition.
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/**
 * Class to Perform a non blocking http request.
 * @uses		CURL
 */
class vpl_HTTP_request{
	private $ch;
	private $mh;
	private $url;
	private $message;
	private $response;
	private $state;
	private $strerror;
	const read_size=1024;
	const timeout=1000;
	const opening=0;
	const connected=1;
	const end=2;
	const error=3;

	/*
	 * Construct HTTP_request object
	 * @param $message XML-RPC request
	 */
	function __construct($message){
		$this->message = $message;
		$this->response = null;
		$this->strerror = '';
		$this->state = self::opening;
	}

	/*
	 * Get object state (opening, writing, reading, end, error)
	 * @return int state
	 */
	function get_state(){
		return $this->state;
	}


	/*
	 * Make the needed operation to advance the process
	 * @return int state
	 */
	function advance(){
		if($this->state == self::connected){
			$still_running=null;
			$ret = curl_multi_exec($this->mh,$still_running);
			if($ret != CURLM_CALL_MULTI_PERFORM && !$still_running){
				if($ret != CURLM_OK || curl_errno($this->ch)){
					$this->state = self::error;
					$this->strerror = 'curl_multi_exec in advance return error '.$ret;
					$this->close_handles();
					return;
				}else{
					$this->response = curl_multi_getcontent($this->ch);
					$this->close_handles();
					$this->state = self::end;
				}
			}
		}
		return $this->state;
	}

	/*
	 * Get the response
	 * @return string response
	 */
	function get_response(){
		return $this->response;
	}

	/*
	 * Get the errorstr
	 * @return string response
	 */
	function get_error(){
		return $this->strerror;
	}


	/*
	 * Close handles
	 */
	function close_handles(){
		curl_multi_remove_handle($this->mh,$this->ch);
		curl_multi_close($this->mh);
		curl_close($this->ch);
	}

	/*
	 * try to connect to host
	 * @return bool true==connect
	 */
	function try_server($url,$wait=1){
		if(!function_exists('curl_init')){
			throw new Exception('PHP cURL requiered');
		}
		$now = time();
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Content-type: text/xml;charset=UTF-8', 'User-Agent: VPL_XMLRPC'));
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->message);
		$this->mh = curl_multi_init();
		curl_multi_add_handle($this->mh, $this->ch);
		$still_running=null;
		$uwait = 30000;
		$maxiter= (1000000*$wait)/$uwait;
		$iter=0;
		do{
			$ret = curl_multi_exec($this->mh,$still_running);
			usleep($uwait);
			$iter++;
		}while($still_running && ((time()-$now) <= $wait) && ($iter < $maxiter));
		$info = curl_multi_info_read($this->mh);
		if($info !== false){
			$OK = $info['result'] ==0;
		}else{
			$OK=true;
		}
		if($ret == CURLM_OK && $OK && !curl_errno($this->ch)){
			if(!$still_running){
				if(curl_getinfo($this->ch,CURLINFO_HTTP_CODE) != 200){
					$this->state = self::error;
					$this->strerror = 'HTTP_CODE = '.curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
					$this->strerror .= curl_multi_getcontent($this->ch);;
					$this->close_handles();
					return false;
				}
				$this->response = curl_multi_getcontent($this->ch);
				$this->state = self::end;
			}
			else{
				$this->state = self::connected;
			}
			return true;
		}
		$this->strerror = curl_error($this->ch).curl_multi_getcontent($this->ch);
		$this->state = self::error;
		$this->close_handles();
		return false;
	}

	/**
	 * @return bool true if running
	 *
	 */
	function is_connected(){
		return $this->get_state() == vpl_HTTP_request::connected;
	}
}

?>