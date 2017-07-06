<?php
define(REG_INF, 1);
define(REG_RESULT, 2);
define(NEWLINK, 3);
define(GAME_INF, 4);
define(USER_INF, 5);
define(USERS_INF, 6);
define(REQUEST_START, 7);
define(USER_MESSAGE, 8);
define(SYSTEM_MESSAGE, 8);
class Server {

	private $master;
	private $sockets = array();
	private $gameState = "over";

	function __construct($addr, $port) {

		$this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)
			or die("socket_create() failed");

		socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1)
			or die("socket_option() failed");

		socket_bind($this->master, $addr, $port)
			or die("socket_bind() failed");

		socket_listen($this->master, 9)
			or die("socket_listen() failed");

		$this->sockets[0] = ['socket' => $this->master, 'type' => 'master'];

		echo "Socket initialize success.\n";
		while(true) {
			$read = array();
			foreach($this->sockets as $socket) {
				if($socket['state'] == 'offline') {
					continue;
				}
				$read[] = $socket['socket'];
			}
			$write = NULL;
			$except = NULL;
			socket_select($read, $write, $except, NULL);

			foreach ($read as $socket) {
				if ($socket == $this->master) {
					$client = socket_accept($socket);
					if($client < 0) {
						echo "socket_accpet failed\n";
						continue;
					} else {
						$this->connect($client);
						echo "A client have connected\n";
						continue;
					}
				} else {
					$bytes = socket_recv($socket, $buffer, 2048, 0);
					echo "message length:$bytes bytes\n";
					if($bytes < 9) {
						$this->disconnect($socket);
						continue;
					} else {
						if($this->sockets[(int)$socket]['handshake']) {
							$buffer = $this->decode($buffer);
							echo "get message: $buffer\n";
							$this->analyseMsg($socket, $buffer);
						} else {
							echo "dohandshake\n";
							$this->handShake($socket, $buffer);
						}
					}
				}
			}
		}
	}

	function analyseMsg($socket, $buffer) {
		$buffer = json_decode($buffer);
		$data = $buffer->data;
		$msg = (object)array();
		switch($buffer->type) {
			case REG_INF:
				$this->sockets[(int)$socket]['userName'] = $data->name;
				$this->sockets[(int)$socket]['userFace'] = $data->face;
				$this->sockets[(int)$socket]['userState'] = 'register';
				$msg->type = REG_RESULT;
				$msg->data = "success";
				$this->send($socket, $msg); 
				break;
			case NEWLINK:
				$msg->type = GAME_INF;
				$msg->state = $this->gameState;
				$msg->users = $this->getUserInfo();
				$this->send($socket, $msg);
				break;
			case USER_INF:
				$this->updateUser($socket, $data);
				$msg->type = USERS_INF;
				$msg->users = $this->getUserInfo();
				$this->broadcast($msg);
				break;
			case USER_MESSAGE:
			case SYSTEM_MESSAGE:
				$this->broadcast($buffer);
				break;
		}
	}
	function updateUser($socket, $data) {
		$this->sockets[(int)$socket]['userName'] = $data->name;
		$this->sockets[(int)$socket]['userOrder'] = $data->order;
		$this->sockets[(int)$socket]['userFace'] = $data->face;
		$this->sockets[(int)$socket]['userState'] = $data->state;
		$this->sockets[(int)$socket]['userType'] = $data->type;
	}
	function getUserInfo() {
		echo "\nall sockets : \n";
		print_r($this->sockets);
		$res = array();
		$i = 0;
		foreach ($this->sockets as $socket) {
			if ($socket['socket'] == $this->master) continue;
			if ($socket['userState'] == 'ready' || $socket['userState'] == 'gaming'){
				$res[$i]['name'] = $socket['userName'];
				$res[$i]['face'] = $socket['userFace'];
				$res[$i]['order'] = $socket['userOrder'];
				$res[$i]['uType'] = $socket['userType'];
				$i++;
			}
		}	
		return $res;
	}

	function connect($socket) {
		socket_getpeername($socket, $ip, $port);
		$socket_info = [
			'socket' => $socket,
			'handshake' => false,
			'ip' => $ip,
			'client' => 'unknow',
			'state' => 'online',
			'port' => $port,
			'userState' => 'observer'
		];
		$this->sockets[(int)$socket] = $socket_info;
	}

	function decode($buffer) {
		$len = $masks = $data = $decoded = null;
		$len = ord($buffer[1]) & 127;
		if ($len == 126) {
			$masks = substr($buffer, 4, 4);
			$data = substr($buffer, 8);
		} else if ($len == 127) {
			$masks = substr($buffer, 10, 4);
			$data = substr($buffer, 14);
		} else {
			$masks = substr($buffer, 2, 4);
			$data = substr($buffer, 6);
		}
		for($index = 0; $index < strlen($data); $index++) {
			$decoded .= $data[$index] ^ $masks[$index % 4];
		}
		return $decoded;
	}

	function handShake($socket, $buffer) {

		$key = null;
		$session = null;

		preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $buffer, $match);
		$key = $match[1];
		preg_match("/Cookie: PHPSESSID=(.*)\r\n/", $buffer, $match);
		$session = $match[1];

		$mask = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
		$acceptKey  = base64_encode(sha1($key.$mask, true));
		$upgrade = "HTTP/1.1 101 Switching Protocols\r\n".
			"Upgrade: webSocket\r\n".
			"Connection: Upgrade\r\n".
			"Sec-WebSocket-Accept: ".$acceptKey."\r\n".
			"\r\n";
		socket_write($socket, $upgrade, strlen($upgrade));
		$this->sockets[(int)$socket]['handshake'] = true;
		$this->checkSession($socket, $session);
		$this->sockets[(int)$socket]['session'] = $session;
	}

	function checkSession($socket, $session) {
		foreach ($this->sockets as $element) {
			if($element['socket'] == $this->master) continue;
			if($element['session'] == $session) {
				echo "one client have reclient\n";
				foreach($element as $key=>$value) {
					if($key=='userName' || $key=='userFace' || $key=='userOrder')
						$this->sockets[(int)$socket][$key] = $value;
				}
				unset($this->sockets[(int)$element['socket']]);
				break;
			}
		}
	}
	function disconnect($socket) {
		$this->sockets[(int)$socket]['state'] = "offline";
		$this->sockets[(int)$socket]['userState'] = "offline";
		$msg->type = USERS_INF;
		$msg->users = $this->getUserInfo();
		$this->broadcast($msg);
		echo "one client have disconnect\n";
	}
	function broadcast($data, $type="all") {
		$sockets = $this->sockets;
		foreach ($this->sockets as $socket) {
			if($socket['socket'] == $this->master) continue;
			if($socket['state'] == 'offline') continue;
			if($type = "all") {
				$this->send($socket['socket'], $data);
				continue;
			}
			if($socket["type"] == $type) {
				$this->send($socket['socket'], $data);
			}
		}
	}
	function encode($buffer) {
		$len = strlen($buffer);
		if($len <= 125) {
			return "\x81".chr($len).$buffer;
		} else if ($len<=65535) {
			return "\x81".chr(126).pack("n",$len).$buffer;
		} else {
			return "\x81".chr(127).pack("xxxxN", $len).$buffer;
		}
	}
	function send($socket, $data) {
		if(is_array($data) || is_object($data)) {
			$data= json_encode($data);
		}
		echo "send: $data\n";
		$data= $this->encode($data);	
		socket_write($socket, $data, strlen($data));
	}
}
$ws = new Server('localhost', 4000);

