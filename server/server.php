<?php
require_once './ctrl.class.php';
@define(CTRL_INF, 2001);
@define(CTRL_ROUND_END, 2002);
@define(CTRL_ROUND_START, 2003);
@define(CTRL_GAME_OVER, 2004);
@define(REG_INF, 1);
@define(REG_RESULT, 2);
@define(NEWLINK, 3);
@define(GAME_INF, 4);
@define(USER_INF, 5);
@define(USERS_INF, 6);
@define(REQUEST_START, 7);
@define(MESSAGE, 8);
@define(GAME_START, 9);
@define(RESPONSE_INF, 10);
@define(SUBJECT, 11);
@define(CLIENT, 12);
@define(ANSWER, 13);
@define(NEW_ROUND, 14);
@define(GAME_OVER, 15);
@define(GUESS_MESSAGE, 16);
@define(STYLE, 1001);
@define(START, 1002);
@define(DRAWING, 1003);
@define(CLEAR, 1004);
@define(UNDO, 1005);
@define(REDO, 1006);
@define(SAVE, 1007);
@define(REQUEST_PAINT, 1008);
@define(CANVAS_SIZE, 1009);
class Subject {
    private $subjects = array(
        '饼干', '笔记本', '矿泉水', '手机', '微软', '鼠标', '飞机', '程序员', '狗', '雪人',
        '钓鱼岛', '扑克牌', '王炸', '鼠标垫', '油烟机', '护士', '医生', '演员', '电影',
        '天使', '猴子', '齐天大圣', '定海神针', '白日做梦', '梦游', '僵尸', '杀手', '世界杯', '泾渭分明',
        '望其项背', '鸡鸣狗盗', '中国梦', '蹦极', '鬼屋', '电影院', '鳄鱼', '武器',
        '螃蟹', '螳螂', '教学楼', '宿舍楼', '天安门', '青蛙', '蝉', '厕所', '啤酒', '英语六级', '大话西游',
        '篮球场', '酸奶', '大话西游', '笔记本', '挂科', '学霸', '学渣', '大闹天空', '天蓬元帅', '音乐',
        '饮水机', '空调', '沙发', '安卓', '剃须刀', '吸尘器', '少林寺', '李白', '江湖'
    );
    function getArr($num) {
        $res = array();
        $tmp = array_rand($this->subjects, $num);
        foreach($tmp as $val) {
            $res[] = $this->subjects[$val];
        }
        return $res;
    }
}
class Server {
    private $master;
    private $ctrl;
    //游戏题目对象
    private $subjects;
    //当前游戏回合的题目
    private $subject;
    private $sockets = array();
    //游戏状态 over: 结束 gaming: 游戏中
    private $gameState = "over";
    //剩余回合数
    private $rounds = 0;
    //当前回合数
    private $round = 1;
    //游戏开始时间
    private $startTime = 0;
    //当前回合开始时间戳
    private $roundTime = 0;
    //当前玩家数量
    private $userNum = 2;
    //回合答对题目者
    private $winner = 0;
    //当前游戏中玩家信息
    private $players = [];
    //记录玩家座次
    private $userOrder = [];
    //单回合玩家用时时间
    private $score = [];
    //所有回合玩家用时
    private $scores = [];
    //当前绘画者信息
    private $painter = [];
    //画布大小
    private $canvas_size = [];

    function __construct($addr, $port) {

        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)
            or die("socket_create() failed");

        socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1)
            or die("socket_option() failed");

        socket_bind($this->master, $addr, $port)
            or die("socket_bind() failed");

        socket_listen($this->master)
            or die("socket_listen() failed");

        $this->sockets[0] = ['socket' => $this->master, 'type' => 'master', 'state' => 'online'];

        echo "Socket initialize success.\n";
        while(true) {
            $read = array();
            foreach($this->sockets as $socket) {
                if(@$socket['state'] == 'offline') {
                    continue;
                }
                $read[] = $socket['socket'];
            }
            $write  = NULL;
            $except = NULL;

            //阻塞
            socket_select($read, $write, $except, NULL);

            if (in_array($this->master, $read)) {
                $client = socket_accept($this->master);
                $this->connect($client);
                echo "A client have connected\n";
                $key = array_search($this->master, $read);
                unset($read[$key]);
            }

            foreach ($read as $socket) {
                if ($socket === $this->master) continue;
                $bytes = socket_recv($socket, $buffer, 2048, 0);
                if ($bytes == 0) {
                    $this->disconnect($socket);
                    continue;
                } else {
                    if ($socket == $this->ctrl) {
                        $this->ctrlMesg($socket, $buffer);
                    } else if ($this->sockets[(int)$socket]['handshake']) {
                        if(dechex(ord($buffer[0])) == 88) {
                            $this->disconnect($socket);
                            continue;
                        }
                        $buffer = $this->decode($buffer);
                        $this->analyseMsg($socket, $buffer);
                    } else {
                        echo "dohandshake\n";
                        $this->handShake($socket, $buffer);
                    }
                }
            }
        }
    }
    function ctrlMesg($socket, $msg) {
        $msg = json_decode($msg);
        switch ($msg->type) {
        case CTRL_INF:
            $msg->data = [
                'rounds' => $this->rounds,
                'round'  => $this->round,
                'sTime'  => $this->startTime,
                'rTime'  => $this->roundTime,
                'state'  => $this->gameState
            ];
            $msg = json_encode($msg);
            socket_write($this->ctrl, $msg, strlen($msg));
            break;
        case CTRL_ROUND_END:
            $this->endRound();
            break;
        case CTRL_ROUND_START:
            $this->newRound();
            break;
        case CTRL_GAME_OVER:
            $this->gameOver();
            break;
        default:
            break;
        }
    }
    /*
     * 回合结束
     */
    function endRound() {

        $this->gameState = 'roundOver';

        echo "\n---------------回合结束-------------\n";
        print_r($this->score);
        print_r($this->scores);
        echo "------------------------------------\n";

        //广播回合结果
        $score = [];
        foreach($this->score as $key=>$val) {
            if($key == $this->painter['userName']) continue;
            $score[$key] = $val;
        }
        $msg = [
            'type'     => ANSWER,
            'answer'   => $this->subject,
            'score'    => $score,
            'gameOver' => false
        ];
        $this->broadcast($msg, 'all', 'gaming');
    }
    /*
     * 开始新回合
     */
    function newRound() {

        $this->gameState = 'gaming';

        $this->round ++;
        $this->winner = 0;
        //更改玩家身份
        $this->setPlayers();
        for ($i = 0; $i < count($this->players); $i++) {
            if ($i == count($this->players) - 1) {
                $this->sockets[(int)$this->players[0]['socket']]['client']  = 'painter';
                $this->players[0]['client'] = 'painter';
                $this->painter = $this->players[0];
                $this->sockets[(int)$this->players[$i]['socket']]['client'] = 'answerer';
                $this->players[$i]['client'] = 'answerer';
                break;
            }
            if ($this->players[$i]['client'] === 'painter') {
                $this->sockets[(int)$this->players[$i+1]['socket']]['client'] = 'painter';
                $this->players[$i+1]['client'] = 'painter';
                $this->painter = $this->players[$i+1];
                $this->sockets[(int)$this->players[$i]['socket']]['client']   = 'answerer';
                $this->players[$i]['client'] = 'answerer';
                break;
            }
        }
        //设置回合绘画者完成回合数 +1
        $this->sockets[(int)$this->painter['socket']]['completeRounds'] += 1;
        $this->setPlayers();

        //置空回合分数
        foreach($this->score as $key=>$score) {
            $this->score[$key] = 60;
        }
        //更新题目
        $this->subject = array_pop($this->subjects);
        //更新回合时间
        $this->roundTime = time();

        echo "-------------回合开始--------------\n";
        echo "开始时间\t$this->roundTime\n";
        echo "回合数  \t$this->round\n";
        echo "绘画者  \t{$this->painter['userName']}\n";
        echo "玩家数量\t$this->userNum\n";
        echo "回合题目\t$this->subject\n";
        echo "------------------------------------\n";

        //广播新回合
        $msg = [
            'type' => NEW_ROUND,
            'users' => $this->getUserInfo(),
            'time' => $this->roundTime,
        ];

        foreach ($this->players as $player) {
            $msg['client'] = $player['client'];
            if ($player['client'] === 'painter') {
                $msg['subject'] = $this->subject;
                $msg['canSize'] = false;
            } else {
                $msg['subject'] = mb_strlen($this->subject, 'utf-8');
                $msg['canSize'] = $this->canvas_size;
            }
            $this->send($player['socket'], $msg);
        }
    }

    /*
     * 接到客户端游戏开始请求后，检查是否可以开始
     * 若准备状态玩家个数大于2则可以开始游戏并置状态为gaming，否则不予开始
     * 返回bool值表示是否允许开始
     */
    function tryStart() {
        $i = 0;
        $arr = array();
        foreach ($this->sockets as $socket) {
            if ($socket['socket'] == $this->master || $socket['socket'] == $this->ctrl) continue;
            if ($socket['userState'] == 'ready') {
                $arr[] = $socket['socket'];
                $i++;
            }
        }
        if ($i < 2) {
            return false;
        } else {
            foreach ($arr as $index) {
                $this->sockets[(int)$index]['userState'] = 'gaming';
                if($this->sockets[(int)$index]['userOrder'] == 0) {
                    $this->sockets[(int)$index]['client'] = 'painter';
                } else {
                    $this->sockets[(int)$index]['client'] = 'answerer';
                }
            }
            return true;
        }
    }
    /*
     * 获取玩家信息
     * $reset 是否重置玩家信息
     */
    function setPlayers($reset = false) {
        $this->players = [];
        $completeRounds = 0;
        foreach ($this->sockets as $socket) {
            //获取所有在线玩家以及掉线的绘画者
            if ((@$socket['client'] === 'answerer' && @$socket['state'] !== 'offline') || @$socket['client'] === 'painter') {
                if ($reset) {
                    @$this->sockets[(int)$socket['socket']]['completeRounds'] = 0;
                }
                $this->players[] = $this->sockets[(int)$socket['socket']];
                $completeRounds += $this->sockets[(int)$socket['socket']]['completeRounds'];
            }
        }
        usort($this->players, function($a, $b) {
            $a = $a['userOrder'];
            $b = $b['userOrder'];
            return $a > $b ? 1 : -1;
        });
        //更新玩家数和剩余回合数
        $this->userNum = count($this->players);
        $this->rounds = $this->userNum * 2 - $completeRounds;
        echo "总玩家数：$this->userNum ，剩余回合数：$this->rounds \n";
    }

    function gameStart($socket) {
        //初始化游戏信息
        //设置绘画者
        $this->painter = $this->getUserInfo($socket);
        //充值玩家信息
        $this->setPlayers(true);
        //设置绘画者完成回合为1
        $this->sockets[(int)$socket]['completeRounds'] = 1;
        $this->players[0]['completeRounds'] = 1;
        //初始化玩家成绩
        $this->socre = null;
        $this->socres = null;
        foreach($this->players as $player) {
           $this->score[$player['userName']] = 60;
            $this->scores[$player['userName']]['atime'] = 60 * ($this->userNum - 1) * 2;
            $this->scores[$player['userName']]['ptime'] = 60 * 2;
            $this->scores['length'] = $this->userNum;
        }

        //设置当前回合数
        $this->round = 1;
        //设置游戏状态
        $this->gameState = "gaming";
        //设置游戏开始时间
        $this->startTime = time();
        //设置回合开始时间
        $this->roundTime = $this->startTime;
        //初始化题库
        $subjects = new Subject();
        $this->subjects = $subjects->getArr(2 * $this->userNum);
        $this->subject = array_pop($this->subjects);
        //输出游戏信息
        echo "-------------游戏开始--------------\n";
        echo "开始时间\t$this->roundTime\n";
        echo "回合数  \t$this->round\n";
        echo "绘画者  \t{$this->painter['userName']}\n";
        echo "玩家数量\t$this->userNum\n";
        echo "回合题目\t$this->subject\n";
        echo "------------------------------------\n";
        //发布游戏开始信息
        $msg = [
            'type' => GAME_START,
            'data' => 1
        ];
        $this->broadcast($msg);

        $msg['data'] = [
            'rounds' => $this->rounds,
            'round'  => $this->round,
            'sTime'  => $this->startTime,
            'rTime'  => $this->roundTime,
            'state'  => $this->gameState
        ];
        $msg = json_encode($msg);
        socket_write($this->ctrl, $msg, strlen($msg));
    }

    function analyseMsg($socket, $buffer) {
        echo "$buffer\n";
        $buffer = json_decode($buffer);
        $data = isset($buffer->data) ? $buffer->data : null;
        $msg = (object)array();
        switch($buffer->type) {
        case REG_INF:
            $flag = true;
            foreach($this->sockets as $tmp) {
                if(@$tmp['userName'] == $data->name) {
                    $flag = false;
                    break;
                }
            }
            if($flag) {
                $this->sockets[(int)$socket]['userName'] = $data->name;
                $this->sockets[(int)$socket]['userFace'] = $data->face;
                $this->sockets[(int)$socket]['userState'] = 'register';
                $msg->type = REG_RESULT;
                $msg->data = "success";
            } else {
                $msg->type = REG_RESULT;
                $msg->data = 'rename';
            }
            $this->send($socket, $msg); 
            break;
        case NEWLINK:
            $this->newlink($socket);
            break;
        case USER_INF:
            $this->updateUser($socket, $data);
            $msg->type = USERS_INF;
            $msg->users = $this->getUserInfo();
            $this->broadcast($msg);
            break;
        case REQUEST_START:
            $msg->type = GAME_START;
            if ($this->tryStart()) {
                $this->gameStart($socket);
            } else {
                $msg->data = 0; 
                $this->send($socket, $msg);
            }
            break;
        //答题消息
        case GUESS_MESSAGE:
            if ($this->gameState != 'gaming') {
                break;
            }
            $name = $buffer->data->userName;
            //判断是否答对
            if ($buffer->data->msgValue == $this->subject) {
                if ($this->score[$name] == 60) {
                    $this->winner ++;
                    echo "--------------Round{$this->round} {$name}第{$this->winner}个猜对答案------------------\n";
                    $duration = time() - $this->roundTime;
                    $this->score[$name] = $duration - 4;
                    $this->scores[$name]['atime'] -= 64 - $duration;
                    if ($this->winner === 1) {
                        $this->scores[$this->painter['userName']]['ptime'] -= 64 - $duration;
                    }
                    if ($this->winner == $this->userNum - 1) {
                        $this->endRound();
                    }
                }
                $msg = (object)[];
                $msg->type  = MESSAGE;
                $msg->data = (object)[];
                $msg->data->type = 3;
                $msg->data->userName = $buffer->data->userName;
                $this->broadcast($msg, 'all', 'gaming');
            } else {
                $buffer->type = MESSAGE;
                $this->broadcast($buffer, 'all', 'gaming');
            }
            break;
        case MESSAGE:
            $this->broadcast($buffer, 'unknow');
            break;
        case CANVAS_SIZE:
            $this->canvas_size['width']  = $buffer->data->width;
            $this->canvas_size['height'] = $buffer->data->height;
            $msg = (object)[];
            $msg->type = CANVAS_SIZE;
            $msg->size = $this->canvas_size;
            $this->broadcast($msg, 'answerer', 'gaimg');
            break;
        case START:
        case DRAWING:
        case UNDO:
        case REDO:
        case SAVE:
        case CLEAR:
            $this->broadcast($buffer, "answerer", 'gaming');
            break;
        }
    }
    function newlink($socket) {
        //新连接接入时，先广播游戏信息，包括游戏状态和玩家信息
        $msg = (object)array();
        $msg->type = GAME_INF;
        $msg->state = $this->gameState;
        $msg->users = $this->getUserInfo();
        $msg->time  = $this->startTime;
        $this->broadcast($msg);
        if ($this->sockets[(int)$socket]['userState'] === 'gaming') {
            $this->setPlayers();
        }
        //再分别发送客户端类型
        $msg->type = CLIENT;
        unset($msg->state);
        unset($msg->users);
        $msg->client = $this->sockets[(int)$socket]['client'];
        $this->send($socket, $msg);
        //发送题目信息
        if($this->gameState == 'gaming') {
            $msg->type = SUBJECT;
            $msg->time = $this->roundTime;
            if($this->sockets[(int)$socket]['client'] == 'painter') {
                $msg->data = $this->subject;
                $this->send($socket, $msg);
            } else if ($this->sockets[(int)$socket]['client'] == 'answerer') {
                $msg->data = mb_strlen($this->subject, 'utf-8');
                $this->send($socket, $msg);
                $msg->type = CANVAS_SIZE;
                $msg->size = $this->canvas_size;
                $this->send($socket, $msg);
            }
        }
    }
    function releaseSubject() {
        $msg = (object)array();
        $msg->type = SUBJECT;
        $msg->data = array_pop($this->subjects);
        $this->broadcast($msg, 'painter');
        $msg->data = count($msg->data);
        $this->broadcast($msg, 'answerer');
    }
    function updateUser($socket, $data) {
        $this->sockets[(int)$socket]['userName'] = $data->name;
        $this->sockets[(int)$socket]['userOrder'] = $data->order;
        $this->sockets[(int)$socket]['userFace'] = $data->face;
        $this->sockets[(int)$socket]['userState'] = $data->state;
    }
    /*
     * 获取某一状态的所有连接
     * 状态
     * 返回该状态的所有连接
     */
    function getSockets($state = 'all') {
        $res = array();
        if($state == 'all') {
            foreach($this->sockets as $socket) {
                if($socket['socket'] == $this->master || $socket['socket'] == $this->ctrl) continue;
                if($socket['state'] == 'offline') continue;
                $res[] = $socket;
            }
            return $res;
        } else {
            foreach($this->sockets as $socket) {
                if($socket['type'] == 'master' || $socket['socket'] == $this->ctrl) continue;
                if($socket['state'] == 'offline' && $socket['client'] == ['answerer']) continue;
                if($socket['userState'] == $state) {
                    $res[] = $socket;
                }
            }
            return $res;
        }
    }
    /*
     * 结束游戏
     */
    function gameOver() {

        if($this->gameState == 'over') return;
        $this->gameState = 'over';

        //广播消息
        $score = [];
        foreach($this->score as $key=>$val) {
            if($key == $this->painter['userName']) continue;
            $score[$key] = $val;
        }
        $msg = [
            'type' => GAME_OVER,
            'score' => $score,
        ];
        $msg['gameOver'] = true;
        $msg['scores'] = $this->scores;
        $this->broadcast($msg, 'all', 'gaming');
        $msg1 = [
            'type' => GAME_OVER
        ];
        $this->broadcast($msg, 'all', 'register');

        //重置玩家状态
        foreach($this->sockets as $socket) {
            if ($socket['socket'] == $this->master || $socket['socket'] == $this->ctrl) continue;
            $this->sockets[(int)$socket['socket']]['client'] = 'unknow';
            if ($socket['userState'] == 'gaming') {
                $this->sockets[(int)$socket['socket']]['userState'] = 'register';
            }
            if ($socket['state'] == 'offline') {
                unset($this->sockets[(int)$socket['socket']]);
            }
        }
        echo "\n-----------gameOver-------------\n";
    }
    /*
     * 获取用户信息
     * p1: socket套接字的值， 默认为'all'，返回所有用户信息；否则返回指定套接字用户的信息
     */
    function getUserInfo($socket='all') {
        $res = array();
        if ($socket === 'all') {
            $i = 0;
            foreach ($this->sockets as $socket) {
                if ($socket['socket'] == $this->master) continue;
                if (@$socket['userState'] == 'ready' || @$socket['userState'] == 'gaming'){
                    $res[$i]['name'] = $socket['userName'];
                    $res[$i]['face'] = $socket['userFace'];
                    $res[$i]['order'] = $socket['userOrder'];
                    $res[$i]['state'] = $socket['state'];
                    $res[$i]['type'] = $socket['client'];
                    $i++;
                }
            }	
            usort($res, function($a, $b) {
                $a = $a['order'];
                $b = $b['order'];
                return $a - $b;
            });
        } else {
            $socketInfo = $this->sockets[(int)$socket];
            foreach($socketInfo as $key=>$value) {
                if(substr($key,0,4) == 'user') {
                    $res[$key] = $value;			
                }
            }
        }
        return $res;
    }
    /*
     * 初始化新的套接字，设置相关信息，存入对象属性
     * p1: 需要设置的套接字
     */
    function connect($socket) {
        socket_getpeername($socket, $ip, $port);
        $socket_info = [
            'socket' => $socket,
            'type' => 'client',
            'handshake' => false,
            'ip' => $ip,
            'client' => 'unknow',
            'state' => 'online',
            'port' => $port,
            'userState' => 'observer'
        ];
        $this->sockets[(int)$socket] = $socket_info;
    }
    /*
     * 解码webSocket帧
     * return： 解码后的数据
     */
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
    /*
     * 接受http请求并解析，发送websocket连接响应，完成websocket连接的建立
     */
    function handShake($socket, $buffer) {

        $key = null;
        $session = null;

        //判断是否为控制客户端
        preg_match("/Ctrl-Key: (.*)\r\n/", $buffer, $match);
        $key = @$match[1];
        if ($key === "3be8e5f22f9a063b11065be898d74807" && $this->sockets[(int)$socket]['ip'] === "127.0.0.1") {
            unset($this->sockets[(int)$socket]);
            $this->sockets['ctrl']['socket'] = $socket;
            $this->sockets['ctrl']['type'] = "ctrl";
            $this->ctrl = $socket;
            echo "-----------控制器已连接----------\n";
            return;
        }

        //获取 websocket key
        preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $buffer, $match);
        $key = $match[1];
        //握手算法
        $mask = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
        $acceptKey  = base64_encode(sha1($key.$mask, true));
        //发送http响应
        $upgrade = "HTTP/1.1 101 Switching Protocols\r\n".
            "Upgrade: webSocket\r\n".
            "Connection: Upgrade\r\n".
            "Sec-WebSocket-Accept: ".$acceptKey."\r\n".
            "\r\n";
        socket_write($socket, $upgrade, strlen($upgrade));
        $this->sockets[(int)$socket]['handshake'] = true;

        //获取 session
        if (preg_match("/Cookie: PHPSESSID=(.*)\r\n/", $buffer, $match)) {
            $session = $match[1];
            $this->checkSession($socket, $session);
            $this->sockets[(int)$socket]['session'] = $session;
        } else {
            $this->responseLink($socket, true);
        }
        echo "--------------玩家已连接-------------\n";
    }
    /*
     * 对比$socket客户端和已有连接的phpsession值，判断是否为重连socket，以应对较差的客户端网络环境
     * p1: 新接入的套接字, p2: 新接入的客户端session
     */
    function checkSession($socket, $session) {
        $isNew = true;
        foreach ($this->sockets as $element) {
            if($element['socket'] == $this->master) continue;
            if(@$element['session'] == $session) {
                echo "one client have reclient\n";
                foreach($element as $key=>$value) {
                    if($key=='userName' || $key=='userFace' || $key=='userOrder' || $key=='userState' || $key=='client' || $key=='completeRounds')
                        $this->sockets[(int)$socket][$key] = $value;
                }
                unset($this->sockets[(int)$element['socket']]);
                $isNew = false;
                break;
            }
        }
        $this->responseLink($socket, $isNew);
    }
    /*
     * 判断新连接是否为全新连接后返回相应数据，用以客户端是否需要弹出注册界面
     */
    function responseLink($socket, $isNew) {
        $msg = (object)array();
        $msg->type = RESPONSE_INF;
        if($isNew) {
            $msg->isNew = 1;	
        } else {
            $msg->isNew = 0;	
            $msg->user = $this->getUserInfo($socket);
        }
        $this->send($socket, $msg);
    }
    /*
     * 客户端断开websocket连接时调用, 改变连接状态和玩家状态，以备重连
     */
    function disconnect($socket) {
        if (@$socket == $this->ctrl) {
            unset($this->sockets['ctrl']);
            die('-------------------控制器断开----------------\n');
        }
        $msg = (object)array();
        $this->sockets[(int)$socket]['state'] = "offline";
        //准备状态掉线时将状态重置为已注册，避免占空座位。而游戏中状态不重置，以备重连。
        if ($this->sockets[(int)$socket]['userState'] === 'ready') {
            $this->sockets[(int)$socket]['userState'] = 'register';
        } else if ($this->sockets[(int)$socket]['userState'] === 'gaming') {
            $this->setPlayers();
        }
        $msg->type = USERS_INF;
        $msg->users = $this->getUserInfo();
        $this->broadcast($msg);
        $msg->data = (object)[];
        $msg->data->type = 4;
        echo "one client have disconnect\n";
    }
    /*
     * 向客户端广播消息
     * p1:广播消息 p2:客户端类型 p3:玩家状态
     * 优先判断客户端类型
     */
    function broadcast($data, $client="all", $userState='all') {
        foreach ($this->sockets as $socket) {
            if($socket['socket'] == $this->master || $socket['socket'] == $this->ctrl) continue;
            if($socket['state'] == 'offline') continue;
            if($client == "all") {
                if($userState == 'all') {
                    $this->send($socket['socket'], $data);
                    continue;
                } else if ($socket['userState'] == $userState) {
                    $this->send($socket['socket'], $data);	
                    continue;
                }
            } else if ($socket["client"] == $client) {
                $this->send($socket['socket'], $data);
                continue;
            }
        }
    }
    /*
     * 将消息编码成WebSocket帧
     * p1: 需要编码的数据
     * 返回编码后的数据帧
     */
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
        if($socket == $this->ctrl || $socket == $this->master) return;
        if(is_array($data) || is_object($data)) {
            $data= json_encode($data);
        }
        $data= $this->encode($data);	
        socket_write($socket, $data, strlen($data));
    }
}
$pid = pcntl_fork();

if ($pid === -1 ) {
    die("启动失败\n");
} else if ($pid > 0) {
    $ws = new Server('0.0.0.0', 4000);
} else {
    $ctrl = new RoundCtrl('localhost', 4000);
}

