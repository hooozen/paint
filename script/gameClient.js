class GameClient extends Client {
    constructor(manager, canvas) {
        super(manager);
        this.canvas=canvas;
        this.clientInit();
    }
    clientInit() {
        var This = this;
        this.manager.ws.onopen = function() {
            This.manager.getData(This);
            This.manager.sendData(NEWLINK, 'client');
        }
    }
    setClient(client) {
        if(client === 'painter') {
            this.showPanel();
            var client = new PaintClient(this.manager, this.canvas);
            this.client = client;
            client.user = this.user;
            this.manager.getData(client);
        } else if (client === 'answerer') {
            this.removePanel();
            var client = new ShowClient(this.manager, this.canvas);
            this.client = client;
            client.user = this.user;
            this.manager.getData(client);
        } else {
            window.location = 'index.php';
        }

    }
    setGameInf(msg) {
        this.gameState = msg.state;
        this.setUsers(msg.users);
    }
    setUsers(data) {
        var len = data.length;
        $('user-list').innerHTML = null;
        for(var i = 0; i<len; i++) {
            var oLi = document.createElement('li');
            var face = data[i].face,
                posX = face%5*40,
                posY = Math.floor(face/5)*40,
                imgPos = posX + "px " + posY + "px";
            if (data[i].state === 'offline') {
            oLi.innerHTML = '<div class="score"></div><div class="uface" style="background-position:' + imgPos + '"><div class="offline">掉线</div></div><div class="unmae">'+data[i].name+'</div>';
            } else {
            oLi.innerHTML = '<div class="score"></div><div class="uface" style="background-position:' + imgPos + '"></div><div class="unmae">'+data[i].name+'</div>';
            }
            $('user-list').appendChild(oLi);
            if(data[i].type == 'painter') {
                var paint = document.createElement("div");
                paint.className = "painting";
                oLi.appendChild(paint);
            }
        }
    }
    showPanel() {
        if($('input-box')) {
            document.body.removeChild($('input-box'));
        }
        if($('tool-bar')) return;
        var panel = document.createElement('ul');
        panel.id = 'tool-bar';
        panel.innerHTML = "<ul id='width-block'><li><input id='pencilWidth' type='range' step='1' min='1' max='30' value='1'></li><li><input id='saturation' type='range' step='1'min='10' max='90' value='50'></li></ul><ul id='color-block'><li><div class='color' id='BLACK'></div></li><li><div class='color' id='RED'></div></li><li><div class='color' id='YELLOWr'></div></li><li><div class='color' id='YELLOWg'></div></li><li><div class='color' id='GREEN'></div></li><li><div class='color' id='CYANg'></div></li><li><div class='color' id='CYANb'></div></li><li><div class='color' id='BLUE'></div></li><li><div class='color' id='MAGENTAb'></div></li><li><div class='color' id='MAGENTAr'></div></li></ul>";
        document.body.insertBefore(panel, $('user-list'));
    }
    removePanel() {
      if($('tool-bar')) {
            $('body-wrap').removeChild($('tool-bar'));
        };
    }
    timer(time) {
        var leftTime,
            This = this;
        var timerBox = $('time-left');
        var timer = setInterval(function() {
            leftTime = 64 - (Date.parse(new Date())/1000 - time);
            if (leftTime <= 60) {
                timerBox.innerText = leftTime;	
            }
            if (leftTime <= 0) {
                timerBox.innerText = '0';
                clearInterval(timer);
            }
        }, 500);
    }
    newRound(msg) {
        window.location.reload();
        /*
        document.body.removeChild(getByCls('dialog')[0]);
        document.body.removeChild($('bg-black'));
        this.setClient(msg.client);
        if(msg.canSize) {
            this.client.diagram.setCanvasSize(msg.canSize.width, msg.canSize.height);
        }
        this.setUsers(msg.users);
        this.client.startGame(msg.subject, msg.time);
        */
    }
    gameOver(msg) {
        document.body.removeChild(getByCls('dialog')[0]);
        document.body.removeChild($('bg-black'));
        var scoresDia = new overDialog();
        scoresDia.showUp();
        scoresDia.setMvp(msg.scores);
    }
    roundOver(msg) {
        var resDia = new resDialog(msg); 
    }
}
class SubjectDia extends Dialog {
    constructor(title) {
        super(title);
        this.item = document.createElement('div');
        this.item.id = 'subject-item';
        this.tips = document.createElement('div');
        this.tips.id = 'subject-tips';
    }
    init(subject) {
        this.item.innerText = subject;
        this.tips.innerText = '准备开始';
        this.diaBody.appendChild(this.item);
        this.diaBody.appendChild(this.tips);
        this.showUp();
        this.timer(2);
    }
    timer (time) {
        var This = this;
        var intval = setInterval(function() {
            This.tips.innerText = time--;
            if ( time < 0 ) {
                clearInterval(intval);
                This.remove();
            }
        },1000);
    }
}

class resDialog extends Dialog {
    constructor(result) {
        super('答案');
        this.answer = '<div style="font-size: 25px">' + result.answer + '</div>';
        this.score = '<table class = "round-score"><tr><td>姓名</td><td>用时</td></tr>'
        var score = '';
        for (var item in result.score) {
            score += '<tr><td>' + item + '</td><td>' + result.score[item] + 's</td></tr>';
        }
        this.score += score + '</table>';
        this.diaBody.innerHTML = this.answer + this.score;
        this.showUp();
    }
}

class overDialog extends Dialog {
    constructor() {
        super('结算');
        this.resultTb = document.createElement('table');
        this.resultTb.className = "final-scores";
        this.diaBody.appendChild(this.resultTb);
        this.overBtn = document.createElement('input');
        this.overBtn.type = 'button';
        this.overBtn.value = '完成';
        this.diaBody.appendChild(this.overBtn);
    }
    setMvp(result) {
        var painter = {
            name: null,
            ptime: 10000
        };
        var answerer = {
            name: null,
            atime: 10000
        };
        for (var i in result) {
            if(result[i].ptime < painter.ptime) {
                painter.name = i;
                painter.ptime = result[i].ptime;
            }
            if(result[i].atime < answerer.atime) {
                answerer.name = i;
                answerer.atime = result[i].atime;
            }
        }
        this.resultTb.innerHTML = '<tr><td>称号</td><td>姓名</td><td>平均耗时</td></tr><tr><td>神乎其技</td><td>'+painter.name+'</td><td>'+Math.floor(painter.ptime/result.length)+'s</td></tr><tr><td>洞察之眼</td><td>'+answerer.name+'</td><td>'+Math.floor(answerer.atime/2)+'s</td></tr>';
        this.overBtn.onclick = function() {
            window.location = './index.php';
        }
    }
}
