<?php

Class WS {
var $master;
var $socket = array();

function __construct($address, $port) {
	$this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)
		or die("socket_create() failed");
	socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1)
		or die("socket_option() failed");
	socket_bind($this->master, $address, $port)
		or die("socket_bind() failed");
	socket_listen($this->master, 9)
		or die("socket_listen() failed");

	$this->sockets[0] = ['resource' => $this->master];


	echo("Master socket : ".$this->master."\n");

	while(true) {
		$write = NULL;
		$except = NULL;
		$sockets = array_column($this->sockets, 'resource');
		socket_select($sockets, $write, $except, NULL);

		foreach($sockets as $socket) {
			if($socket ==  $this->master) {
				$client = socket_accept($this->master);
				if($client < 0) {
					echo "socket_accept() failed";
					continue;
				} else {
					$this->connect($client);
					echo "A client have connected\n";
					continue;
				}
			} else {
				$bytes = @socket_recv($socket, $buffer, 2048, 0);
				if($bytes < 9) {
					echo "disconnect\n";
					$this->disconnect($socket);
				} else {
					if (!$this->sockets[(int)$socket]['handshake']) {
						$this->doHandShake($socket, $buffer);
						echo "one client have shakeHand\n";
						continue;
					} else {
						$buffer = $this->decode($buffer);
						echo "msg:$buffer\n";
						$buffer = $this->endecode($buffer);
						$this->broadcast($buffer);
					}
				}
			}
		}
	}
}
function connect($socket) {
	socket_getpeername($socket, $ip, $port);
	$socket_info = [
		'resource' => $socket,
		'handshake' => false,
		'ip' => $ip,
		'port' => $port
	];
	$this->sockets[(int)$socket] = $socket_info;
}
function disconnect($socket) {
	unset($this->sockets[(int)$socket]);
}
function getKey($req) {
	$key = null;
	if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $req, $match)) {
		$key = $match[1];
	}
	echo $key."\n";
	return $key;
}
function encry($req) {
	$key = $this->getKey($req);
	$mask = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";

	return base64_encode(sha1($key . $mask, true));
}
function doHandShake($socket, $req) {
	$acceptKey = $this->encry($req);
	$upgrade = "HTTP/1.1 101 Switching Protocols\r\n".
				"Upgrade: webSocket\r\n".
				"Connection: Upgrade\r\n".
				"Sec-WebSocket-Accept: " . $acceptKey . "\r\n".
				"\r\n";

	socket_write($socket, $upgrade, strlen($upgrade));
	$this->sockets[(int)$socket]['handshake'] = true;
}
function decode($buffer) {
	echo bin2hex($buffer)."\n";
	$len = $masks = $data = $decoded = null;
	$len = ord($buffer[1]) & 127;

	if ($len == 126) {
		$masks = substr($buffer, 4, 4);
		$data = substr($buffer, 8, $len);
	} else if ($len == 127) {
		$masks = substr($buffer, 10, 4);
		$data = substr($buffer, 14, $len);
	} else {
		$masks = substr($buffer, 2, 4);
		$data = substr($buffer, 6, $len);
	}
	for($index = 0; $index < strlen($data); $index++) {
		$decoded .= $data[$index] ^ $masks[$index % 4];
	}
	return $decoded;
}
function endecode($buffer) {
	$buffer = str_split($buffer, 125);
	if (count($buffer) == 1) {
		return "\x81" . chr(strlen($buffer[0])) . $buffer[0];
	}
	$res = "";
	foreach ($buffer as $char) {
		$res .= "\x81" . chr(strlen($char)) . $char;
	}
	return $res;
}
function send($socket, $msg) {
	$msg = $this->endecode($msg);
	socket_write($socket, $msg, strlen($msg));
}
function broadcast($data) {
	foreach ($this->sockets as $socket) {

		if($socket['resource'] == $this->master) {
			continue;
		}
		socket_write($socket['resource'], $data, strlen($data));
	}
}
}

$ws = new WS('localhost', 4000);
