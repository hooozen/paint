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
define(REGINF, 15);
define(REBACK, 16);
define(NEWLINK, 17);
define(USERS, 18);
define(USER_ORDER, 19);
define(USERINF, 20);
define(REQUEST_START, 21);
define(START_GAME, 22);

class WS {
var $master;
var $sockets = array();
var $paintSocket;
var $answerSocket = array();
var $subjects = array('明远湖','逸夫楼','李浩然','修远楼','汽车试验场','修远湖');
var $subject = null;
var $gameStat = 'pausing';
var $gamingSockets = array();

function __construct($address, $port) {
	$this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)
		or die("socket_create() failed");
	socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1)
		or die("socket_option() failed");
	socket_bind($this->master, $address, $port)
		or die("socket_bind() failed");
	socket_listen($this->master, 9)
		or die("socket_listen() failed");

	$this->sockets[0] = ['resource' => $this->master, 'type' => 'master'];


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
				echo "\nsource inmsg: $bytes\n";
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
						echo "\ninmsg:$buffer\n";
						$this->analyseMeg($socket, $buffer);
					}
				}
			}
		}
	}
}
function getUserInfo() {
	echo "\nall sockets : \n";
	print_r($this->sockets);
	$res = array();
	$i = 0;
	foreach ($this->sockets as $socket) {
		if ($socket['resource'] == $this->master) continue;
		if (!array_key_exists('userOrder', $socket)) continue;
		$res[$i]['name'] = $socket['userName'];
		$res[$i]['face'] = $socket['userFace'];
		$res[$i]['order'] = $socket['userOrder'];
		$res[$i]['uType'] = $socket['userType'];
		$res['type'] = USERINF;
		$i++;
	}	
	$res['user_num'] = $i;
	return $res;
}

function getGamingStat() {
	$gamingInf = array();
	$i = 0;
	foreach ($this->sockets as $socket) {
		if ($socket['resource'] == $this->master) continue;
		if ($socket['gameStat'] == 'gaming') {
			$gamingInf[$i]['name'] = $socket['userName'];
			$gamingInf[$i]['face'] = $socket['userFace'];
			$gamingInf[$i]['order'] = $socket['userOrder'];
			$gamingInf[$i]['uType'] = $socket['userType'];
			$i++;
		}
	}
	return $gamingInf;
}

function setGamingStat() {
	$gaming = array();
	$i = 0;
	foreach ($this->sockets as $socket) {
		if ($socket['resource'] == $this->master) continue;
		if ($socket['gameStat'] == 'waiting') {
			$socket['gameStat'] = 'gaming';
			$gaming[$i] = $socket;
			$i++;
		}
	}
	$gaming['type'] = USERINF;
	return $gaming;
}
function startGame() {
	$socket = $this->setGamingStat();
	$msg = ['type'=>START_GAME];
	$this->broadcast($msg, null, $socket);
}
function checkID($sessionID) {
	foreach($this->sockets as $socket) {
		if($socket['resource'] == $this->master) continue;
		if($socket['userSession'] == sessionID) {
			return true;
		}
	}
}
function analyseMeg($socket, $buffer) {
	echo "\n_____________inmsg:".$buffer."_______________\n";
	$buffer = json_decode($buffer);
	switch($buffer->type) {
	case NEWLINK:
		$this->sockets[(int)$socket]['client'] = 'unknow';
		$this->sockets[(int)$socket]['gameStat'] = 'free';
		$this->sockets[(int)$socket]['userSession'] = $buffer->session;
		$this->broadcast($this->getUserInfo(), 'unkonw', $this->sockets);
		break;
	case USER_ORDER:
		$this->sockets[(int)$socket]['userOrder'] = $buffer->order;
		$this->sockets[(int)$socket]['gameStat'] = 'waiting';
		$this->broadcast($this->getUserInfo(), 'unkonw', $this->sockets);
		break;
	case REQUEST_START:
		$this->gameStat = 'playing';
		$this->startGame();
		break;
	case PAINTER:
		echo "\n subject deliver\n";
		$this->sockets[(int)$socket]['client'] = 'painter';
		$this->sockets[(int)$socket]['gameStat'] = 'gaming';
		$this->sockets[(int)$socket]['userName'] = $buffer->name;
		$this->sockets[(int)$socket]['userFace'] = $buffer->face;
		$this->sockets[(int)$socket]['userOrder'] = $buffer->order;
		$this->sockets[(int)$socket]['userType'] = $buffer->uType;
		$rand = mt_rand(0, count($this->subjects)-1);
		$this->subject = $this->subjects[$rand];
		$msg = array('type'=>SUBJECT,'subject'=>$this->subjects[$rand]);
		echo "\n____________function getGamingStat()______________\n";
		print_r($this->getGamingStat());
		$this->broadcast($this->getUserInfo(), 'unkonw', $this->sockets);
		$this->send($socket, $msg);
		break;
	case ANSWER:
		$this->sockets[(int)$socket]['client'] = 'answer';
		$this->sockets[(int)$socket]['gameStat'] = 'gaming';
		$this->sockets[(int)$socket]['userName'] = $buffer->name;
		$this->sockets[(int)$socket]['userFace'] = $buffer->face;
		$this->sockets[(int)$socket]['userOrder'] = $buffer->order;
		$this->sockets[(int)$socket]['userType'] = $buffer->uType;
		$msg = array('type'=>TIPS, 'tips'=>iconv_strlen($this->subject));
		$this->broadcast($this->getUserInfo(), 'unkonw', $this->sockets);
		$this->send($socket, $msg);
		break;
	case MESSAGE:
		if($buffer->value == $this->subject) {
			$buffer->type = RIGHT;
			$buffer->value = "";
		}
		$this->broadcast($buffer, 'unknow', $this->sockets);
		break;
	case REGINF:
		echo "\n______________\n";
		echo (int)$socket;
		$this->sockets[(int)$socket]['userName'] = $buffer->name;
		$this->sockets[(int)$socket]['userFace'] = $buffer->face;
		$msg = array('type'=>REBACK, 'statu'=>true);
		$this->send($socket, $msg);
		break;
	case STATR:
	case DRAWING:
	case UNDO:
	case REDO:
	case SAVE:
	case CLEAR:
		$this->broadcast($buffer, 'painter', $this->sockets);
		break;
	default:
		echo "\nunkonw mesg\n";
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
	echo "\ndisconnect :\n";
	unset($this->sockets[(int)$socket]);
	$this->broadcast($this->getUserInfo(), 'unkonw', $this->sockets);
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
		$len = (ord($buffer[2])<<8) + ord($buffer[3]);
		echo (ord($buffer[2])<<8)." ".ord($buffer[3]);
		echo "\nlenght of data: $len\n";
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
	$len = strlen($buffer);
	if($len <= 125) {
		return "\x81". chr($len) .$buffer;
	} else if ($len<=65535) {
		return "\x81".chr(126).pack("n",$len).$buffer;
	} else {
		return "\x81".chr(127).pack("xxxxN", $len).$buffer;
	}
}
function send($socket, $msg) {
	$msg = $this->endecode($msg);
	echo "\nout:$msg\n";
	socket_write($socket, $msg, strlen($msg));
}
function broadcast($data, $client, $sockets) {
	echo "\n__________________all sockets__________________\n";
	print_r($this->sockets);
	if(empty($sockets)) {
		$sockets = $this->sockets;
	}
	$data = $this->endecode($data);
	echo "\n broadcast: $data \n";
	foreach ($sockets as $socket) {
		if($socket['resource'] == $this->master) {
			continue;
		}
		if($socket['client'] == $client) {
			continue;
		}
		socket_write($socket['resource'], $data, strlen($data));
	}
}
}

$ws = new WS('localhost', 4000);
