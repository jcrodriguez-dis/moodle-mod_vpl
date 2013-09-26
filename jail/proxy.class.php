<?php
/**
 * @version		$Id: proxy.class.php,v 1.11 2013-06-10 08:20:00 juanca Exp $
 * @package		VPL. proxy and doubleproxy classes definition
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/**
 * Establish a proxy service to conect server<=>jail o server<=>client.
 * To create un object need a port range to select one.
 * Generate a password to acept a conection. The password must be recived first.
 * Main operations:
 *    advance(): to oerform comunication (non blocking)
 *    read(): read income information
 *    write(): send information (buffered)
 */
class vpl_proxy{
	private $base_socket;
	private $socket;
	private $port;
	private $password;
	private $state;
	private $readbuf;
	private $writebuf;
	private $was_connected;
	private $connection_time;
	const   error=0;
	const   waiting=1;
	const   checking_password=2;
	const   connected=3;
	const   closed=4;
	const   timeout=3; //3 sec
	const   maxbuf=20480; //20Kb

	/**
	 * Close base_sockect if != null and set to null
	 *
	 */
	function close_base_socket(){
		if($this->base_socket){
			socket_close($this->base_socket);
		}
		$this->base_socket = null;
	}
	
	/**
	 * Close sockects and set closed state
	 *
	 */
	function close(){
		if($this->state != self::closed){
			if($this->socket){
				socket_close($this->socket);
			}
			$this->close_base_socket();
			$this->state = self::closed;
		}
	}

	/**
	 * Set de range of port to use
	 */
	function __construct($from, $to){
		$retry =2*($to-$from+1); //Max number of ports to check
		for($i=0; $i<$retry; $i++){
			$this->port = rand($from, $to);
			//$this->base_socket = socket_create_listen($this->port);
			$this->base_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if($this->base_socket == null){
				continue;
			}
			socket_set_option($this->base_socket, SOL_SOCKET, SO_REUSEADDR, 1);
			if(!@socket_bind($this->base_socket,0,$this->port)){
				continue;
			}
			if(!@socket_listen($this->base_socket,0)){
				continue;
			}
			socket_set_nonblock($this->base_socket);
			break;
		}
		if($i>=$retry){
			$this->state=self::error;
		}else{
			$this->state=self::waiting;
		}
		$this->password = (string) mt_rand(1000000000, mt_getrandmax());
		$this->was_connected = false;
	}

	/**
	 * @return int port 0 error
	 */
	function get_port(){
		return  $this->port;
	}

	/**
	 * @return string password
	 */
	function get_password(){
		return  $this->password;
	}

	/**
	 * Return the data recived until, now need advance calls
	 * @return string readbuf
	 */
	function read(){
		if(strlen($this->readbuf)>self::maxbuf){
			$res = substr($this->readbuf,0,self::maxbuf);
			$this->readbuf = substr($this->readbuf,self::maxbuf);
		}else{
			$res = $this->readbuf;
			$this->readbuf = '';
		}
		return  $res;
	}

	/**
	 * Send data (buffered) need advance calls
	 * @param string data set data to send
	 */
	function write($data){
		if(strlen($this->writebuf) < 2*self::maxbuf){
			$this->writebuf .= $data;
		}else{// else lost data to write
			echo 'lost of data ';
		}
	}

	/**
	 * check write buffer
	 * @return boolean true is write buffer full
	 */
	function is_write_buffer_full(){
		return strlen($this->writebuf) >= self::maxbuf;
	}
	
	/*
	 * Get object state (error=0, waiting=1, checking_password=2, connected=3, closed=4)
	 * @return int state
	 */
	function get_state(){
		return $this->state;
	}

	/*
	 * Get object state (error=0, waiting=1, checking_password=2, connected=3, closed=4)
	 * @return bool
	 */
	function is_running(){
		return $this->state== self::waiting ||
		$this->state== self::checking_password ||
		$this->state== self::connected || $this->readbuf >'' || $this->writebuf > '';
	}

	/*
	 * Return if the proxy is closed (normal operation)
	 * @return bool
	 */
	function is_closed(){
		return $this->state== self::closed;
	}
	/*
	 * Return if the proxy is connected
	 * @return bool
	 */
	function is_connected(){
		return $this->state== self::connected;
	}
	
	/*
	 * Get if buffer pending
	 * @return bool
	 */
	function is_pending(){
		return $this->readbuf >'' || $this->writebuf > '';
	}
	
	/*
	 * Get if read buffer is pending and after connect
	 * @return bool
	 */
	function is_read_pending(){
		return $this->readbuf >'' && $this->was_connected;
	}
	
	/*
	 * Get if write buffer is pending
	 * @return bool
	 */
	function is_write_pending(){
		return $this->writebuf >'' && (!$this->was_connected || $this->is_connected());
	}
	/*
	 * Get buffer pending to be write
	 * @return String
	 */
	function get_pending(){
		return $this->writebuf;
	}
		
	/*
	 * Get if the proxy was connected
	 * @return boolean
	 */
	function get_was_connected(){
		return $this->was_connected;
	}	

	/*
	 * try_to_read
	 */
	function try_to_read(){
		if(strlen($this->readbuf)< self::maxbuf &&
		   @socket_select($read=array($this->socket),$write = null, $except=null,0)>0){
			if(count($read)){
				$data = @socket_read($this->socket,self::maxbuf);
				if($data !== false){
					if($data == ''){
						$this->close();
						$this->state = self::closed;
					}else{
						$this->readbuf .= $data;
					}
				}else{
					$this->close();
					$this->state = self::closed;
				}
			}
		}
	}

	/*
	 * try_to_write
	 */
	function try_to_write(){
		$read=null;
		$write = array();
		$write[] = $this->socket;
		$except=null;
		if(strlen($this->writebuf)>0 &&
		   @socket_select($read,$write, $except,0)>0){
			if(count($write)){
				$writen= socket_write($this->socket,$this->writebuf);
				if($writen === false){
					$this->close();
					$this->state = self::closed;
				}else{
					if($writen>0){
						$this->writebuf = substr($this->writebuf,$writen);
					}
				}
			}
		}
	}
	
	/*
	 * Recive and send data if posible
	 */
	function advance(){
		switch($this->state){
			case self::waiting:
				$this->socket = @socket_accept($this->base_socket);
				if($this->socket !== false){
					socket_set_nonblock($this->socket);
					$this->connection_time = time();
					$this->state = self::checking_password;
				}
				break;
			case self::checking_password:
				$lpass = strlen($this->password);
				if(strlen($this->readbuf)>=$lpass){
					if(substr($this->readbuf,0,$lpass)==$this->password){//Remove password from input
						$this->readbuf = substr($this->readbuf,$lpass);
						$this->close_base_socket();
						$this->state = self::connected;
						$this->was_connected = true;
					}
					else{
						$this->readbuf = '';
						$this->writebuf = 'pasword error';
						$this->state = self::waiting;
						break;
					}
				}
				if(time()-$this->connection_time >= self::timeout){
					$this->readbuf = '';
					$this->state = self::waiting;
					break;
				}
				$this->try_to_read();
				break;
			case self::connected:
				$this->try_to_read();
				$this->try_to_write();
				break;
		}
		return  $this->state;
	}
}

/**
 * Control the double proxy needed to conect jail <=> client
 *
 */
class vpl_doubleproxy{
	private $jail_proxy;
	private $client_proxy;

	/**
	 * Set de range of port to use
	 */
	function __construct($from, $to){
		$this->jail_proxy = new vpl_proxy($from,$to);
		$this->client_proxy = new vpl_proxy($from,$to);
	}

	/**
	 * @return bool true if no proxy error
	 *
	 */
	function no_error(){
		$js=$this->jail_proxy->get_state();
		$cs=$this->client_proxy->get_state();
		return $cs != vpl_proxy::error && $js != vpl_proxy::error ;
	}

	function info(){
		echo '<p>';
		print_r('Jail_proxy:');
		print_r($this->jail_proxy);
		echo "</p>\n";
		echo '<p>';
		print_r('Client_proxy:');
		print_r($this->client_proxy);
		echo "</p>\n";
	}

	/**
	 * @return bool true if double proxy connected
	 *
	 */
	function is_running(){
		if(!$this->was_jailconected() || !$this->was_clientconected()){
			return true;
		}
		$jrunning = $this->jail_proxy->is_running();
		$crunning = $this->client_proxy->is_running();
		$jpending = $this->jail_proxy->is_pending();
		$cpending = $this->client_proxy->is_pending();
		//Both running or one running and something to do
		return ($jrunning && $crunning)
		||(($jrunning || $crunning) && ($jpending || $cpending));
	}

	/**
	 * @return bool true if jail was connected
	 *
	 */
	function was_jailconected(){
		return $this->jail_proxy->get_was_connected();
	}
	
	/**
	 * @return bool true if client was connected
	 *
	 */
	function was_clientconected(){
		return $this->client_proxy->get_was_connected();
	}
	
	/**
	 * @return bool true if jail is connected
	 *
	 */
	function is_jailconected(){
		return $this->jail_proxy->get_state() == vpl_proxy::connected;
	}

	/**
	 * @return bool true if console is connected
	 *
	 */
	function is_clientconected(){
		return $this->client_proxy->get_state() == vpl_proxy::connected;
	}

	/**
	 * @return bool true if jail is closed
	 *
	 */
	function is_jailclosed(){
		return $this->jail_proxy->is_closed();
	}

	/**
	 * @return bool true if console is closed
	 *
	 */
	function is_clientclosed(){
		return $this->client_proxy->is_closed();
	}

	/**
	 * @return client pending buffer to be write
	 *
	 */
	function get_clientpending(){
		return $this->client_proxy->get_pending();
	}

	/**
	 * @return jail pending buffer to be write
	 *
	 */
	function get_jailpending(){
		return $this->client_proxy->get_pending();
	}
	
	/**
	 * @return is client pending buffer to be write
	 *
	 */
	function is_clientpending(){
		return $this->client_proxy->is_write_pending();
	}

	/**
	 * @return jail pending buffer to be write
	 *
	 */
	function is_jailpending(){
		return $this->client_proxy->is_read_pending();
	}	
	/**
	 * Get jail & client info
	 * @param &$jport int
	 * @param &$jpass password
	 * @param &$cjport int
	 * @param &$cjpass password
	 * 	 *
	 */
	function get_jail_info(&$jport, &$jpass,&$cport, &$cpass){
		$jport = $this->jail_proxy->get_port();
		$jpass = $this->jail_proxy->get_password();
		$cport = $this->client_proxy->get_port();
		$cpass = $this->client_proxy->get_password();
	}

	/**
	 * Perform comunication
	 *
	 */
	function advance(){
		if($this->no_error()){
			$this->client_proxy->advance();
			$this->jail_proxy->advance();
			if($this->jail_proxy->is_read_pending()
			   && !$this->client_proxy->is_write_buffer_full()){
				$data = $this->jail_proxy->read();
				if(strlen($data)>0){
					$this->client_proxy->write($data);
				}
			}
			if($this->client_proxy->is_read_pending()
				&& !$this->jail_proxy->is_write_buffer_full()){
				$data = $this->client_proxy->read();
				if(strlen($data)>0){
					$this->jail_proxy->write($data);
				}
			}
		}
	}

	/**
	 * Close connecions
	 *
	 */
	function close(){
		$this->client_proxy->close();
		$this->jail_proxy->close();
	}
}

?>