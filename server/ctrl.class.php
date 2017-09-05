<?php
@define(CTRL_INF, 2001);
@define(CTRL_ROUND_END, 2002);
@define(CTRL_ROUND_START, 2003);
@define(CTRL_GAME_OVER, 2004);
@define(GAME_START, 9);

class RoundCtrl {

    private $socket = null;

    private $sTime  = 0;
    private $rTime  = 0;
    private $round  = 0;
    private $rounds = 0;
    private $state  = 'over';

    private $CtrlKey = "Ctrl-Key: 3be8e5f22f9a063b11065be898d74807\r\n";
    private $ctrlInf = ['type' => CTRL_INF];


    function __construct($host, $port) {

        $this->socket = socket_create(AF_INET, SOCK_STREAM, 0)
            or die("could not create socket\n");
        $result = socket_connect($this->socket, $host, $port)
            or die("could not connect to server\n");

        socket_write($this->socket, $this->CtrlKey, strlen($this->CtrlKey));


        while (true) {
            $msg = $this->read();
            switch ($msg->type) {
            case GAME_START:
                $this->gameStart($msg);
                break;
            default:
                break;
            }
        }
    }

    function gameStart($msg) {
        echo "gameStart\n";

        $this->round  = $msg->data->round;
        $this->rounds = $msg->data->rounds;
        $this->state  = 'gaming';

        $info = [];

        while ($this->state != 'over') {

            $this->sTime  = $msg->data->sTime;
            $this->rTime  = $msg->data->rTime;
            $this->round  = $msg->data->round;
            $this->rounds = $msg->data->rounds;
            $this->state  = $msg->data->state;

            if($this->state === 'roundOver') {
                sleep(3);
                if($this->rounds == 0) {
                    $info['type'] = CTRL_GAME_OVER;
                    $this->send($info);
                    echo "game over\n";
                    break;
                } else {
                    $info['type'] = CTRL_ROUND_START;
                    $this->send($info);
                    echo "round $this->round start\n";
                }
            }


            if (time() - $this->rTime >= 63) {

                $info['type'] = CTRL_ROUND_END;
                $this->send($info);
                echo "round$this->round over\n";

                sleep(3);

                if($this->rounds == 0) {
                    $info['type'] = CTRL_GAME_OVER;
                    $this->send($info);
                    echo "game over\n";
                    break;
                } else {
                    $info['type'] = CTRL_ROUND_START;
                    $this->send($info);
                    echo "round".($this->round+1)." start\n";
                }
            }

            usleep(300000);

            //从父进程更新游戏信息
            $this->send($this->ctrlInf);
            $msg = $this->read();

        }
    }

    function analyseMsg($msg) {
        switch ($msg->type) {
        default:
        break;
        }
    }

    function send($msg) {
        $msg = json_encode($msg);
        $res = socket_write($this->socket, $msg, strlen($msg));
    }
    function read() {
        $msg = socket_recv($this->socket, $buffer, 2048, 0);
        if ($msg == "") {
            die('conncection false\n');
        }
        return json_decode($buffer);
    }

}
