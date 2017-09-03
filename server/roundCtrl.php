<?php
@define(CTRL_INF, 2001);
@define(CTRL_ROUND_END, 2002);
@define(CTRL_ROUND_START, 2003);
@define(CTRL_GAME_OVER, 2004);
@define(GAME_START, 9);

$socket = socket_create(AF_INET, SOCK_STREAM, 0)
          or die("could not create socket\n");
$result = socket_connect($socket, "localhost", 4000)
          or die("could not connect to server\n");

$CtrlKey = "Ctrl-Key: 3be8e5f22f9a063b11065be898d74807\r\n";
socket_write($socket, $CtrlKey, strlen($CtrlKey));

$ctrlInf = [
    'type' => CTRL_INF
];
$flag = true;

while (true) {
    $msg = read($socket);
    if ($msg->type == GAME_START) {
        gameStart($socket, $msg);
    }
}

function gameStart($socket, $msg) {
    echo "gameStart\n";

    $sTime  = $msg->data->sTime;
    $rTime  = $msg->data->rTime;
    $round  = $msg->data->round;
    $rounds = $msg->data->rounds;

    while ($round < $rounds) {
        sleep(63 - (time() - $rTime));
        $msg->type = CTRL_ROUND_END;
        send($socket, $msg);
        echo "round $round over\n";
        sleep(3);
        $round ++;
        $rTime = time();
        $msg->type = CTRL_ROUND_START;
        send($socket, $msg);
        echo "round $round start\n";
    }
    if ($round === $rounds) {
        $msg->type = CTRL_ROUND_END;
        send($socket, $msg);
        echo "round $round over\n";
        sleep(3);
        $msg->type = CTRL_GAME_OVER;
        send($socket, $msg);
        echo "game over\n";
    }
}

function analyseMsg($socket, $msg) {
    switch ($msg->type) {
    default:
        break;
    }
}

function send($socket, $msg) {
    $msg = json_encode($msg);
    $res = socket_write($socket, $msg, strlen($msg));
    if($res == 0) {
        echo "xxx";
    }
}
function read($socket) {
    $msg = socket_recv($socket, $buffer, 2048, 0);
    echo "getMsg: $buffer";
    if ($msg == "") {
        die('conncection false\n');
    }
    return json_decode($buffer);
}

