<?php
define(STYLE, 1);
define(STATR, 2);
define(DRAWING, 3);
define(OPRERATE, 4);
define(CLEAR, 5);
define(UNDO, 6);
define(REDO, 7);
define(SAVE, 8);
define(PAINTER, 9);
define(ANSWER, 10);
define(SUBJECT, 11);
define(TIPS, 12);
define(MESSAGE, 13);
define(RIGHT, 14);
define(USERNAME, 15);
define(REBACK, 16);

class WS {
var $master;
var $sockets = array();
var $paintSocket;
var $answerSocket = array();
var $subjects = array('明远湖','逸夫楼','李浩然','修远楼','汽车试验场','修远湖');
var $subject = null;

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


	echo("Master socket : ".$this->master."\n"); while(true) { $write = NULL; $except = NULL;
		$sockets = array_column($this->sockets, 'resource');
		socket_select($sockets, $write, $except, NULL);

		foreach($sockets as $socket) {
			echo $socket."\n";
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
						$this->analyseMeg($socket, $buffer);
					}
				}
			}
		}
	}
}
function analyseMeg($socket, $buffer) {
	$buffer = json_decode($buffer);
	switch($buffer->type) {
	case PAINTER:
		$this->sockets[(int)$socket]['client'] = 'painter';
		$rand = mt_rand(0, count($this->subjects)-1);
		$this->subject = $this->subjects[$rand];
		$msg = array('type'=>SUBJECT,'subject'=>$this->subjects[$rand]);
		$this->send($socket, $msg);
		break;
	case ANSWER:
		$this->sockets[(int)$socket]['client'] = 'answer';
		$msg = array('type'=>TIPS, 'tips'=>iconv_strlen($this->subject));
		$this->send($socket, $msg);
		break;
	case MESSAGE:
		if($buffer->value == $this->subject) {
			$buffer->type = RIGHT;
			$buffer->value = "";
		}
		$this->broadcast($buffer, 'unknow');
		break;
	case USERNAME:
		$this->sockets[(int)$socket]['userName'] = $buffer->name;
		$msg = array('type'=>REBACK, 'statu'=>true);
		print_r($this->sockets);
		$this->send($socket, $msg);
		break;
	case STATR:
	case DRAWING:
	case UNDO:
	case REDO:
	case SAVE:
	case CLEAR:
		$this->broadcast($buffer, 'painter');
		break;
	default:
		break;
	}
}
function connect($socket) {
	socket_getpeername($socket, $ip, $port);
	$socket_info = [
		'resource' => $socket,
		'handshake' => false,
		'ip' => $ip,
		'client' => 'unknow',
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
	$buffer = json_encode($buffer);
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
	echo "$msg\n";
	socket_write($socket, $msg, strlen($msg));
}
function broadcast($data, $client) {
	$data = $this->endecode($data);
	foreach ($this->sockets as $socket) {
		if($socket['resource'] == $this->master) {
			continue;
		}
		if($socket['client'] == $client) {
			echo "xx";
			continue;
		}
		socket_write($socket['resource'], $data, strlen($data));
	}
}
}

$ws = new WS('localhost', 4000);
