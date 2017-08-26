/*
 *注册弹框，仅在room.html使用，若用户未注册则弹出
 *regName方法: 实现注册姓名的功能
 *waitFn方法: 等待server返回注册成功的消息，否则要求重试
 */
class RegDialog extends Dialog {
    constructor(title, manager, user) {
        super(title);
        var This = this;
        this.diaBodyName= document.createElement('input');
        this.diaBodyName.type = 'text';
        this.diaBodyName.id= 'user-name';

        this.diaBodyMsg = document.createElement('div');
        this.diaBodyMsg.id = 'reg-msg';

        this.diaBodyBtn = document.createElement('input');
        this.diaBodyBtn.id = 'name-confirm';
        this.diaBodyBtn.type= 'button';
        this.diaBodyBtn.value = '确 定';

        this.diaBody.appendChild(this.diaBodyName);
        this.diaBody.appendChild(this.diaBodyMsg);
        this.diaBody.appendChild(this.diaBodyBtn);

        this.regName(manager, user);
    }
    regName(manager, user) {
        var This = this,
            btn = this.diaBodyBtn,
            msg = this.diaBodyMsg,
            name = this.diaBodyName;
        name.oninput = function() {
            if(this.value === '' || this.value.length<1 || this.value.length>6) {
                btn.style.color = "#888";
                btn.style.bordercolor = "#888";
                if(this.value.length>6)
                    msg.innerText = '太长了';
                btn.onclick = null;
            } else {
                btn.style.color = "#eee";
                btn.style.bordercolor = "#eee";
                msg.innerText = '';
                btn.onclick = function() {
                    This.waitFn();
                    var data = new Object();
                    data.name = name.value;
                    data.face = user.face;
                    manager.sendData(REG_INF, data);
                    user.name = name.value;
                }
            }
        }

    }
    waitFn() {
        var msg = $('reg-msg');
        var i = 0;
        var timer = setInterval(function() {
            if (i===50) {
                clearInterval(timer);
                msg.innerText = '请重试';
            }
            if (i%4===0) {
                msg.innerText = '连接中';
            } else {
                msg.innerText += '.'; 
            }
            i++;
        }, 200);
    }
}
/*
 *roomClient类，用于room页面的游戏逻辑
 *init 方法: 连接severm，获取游戏信息，根据信息初始化client
 *sitFn 方法: 用户入座函数，实现用户点击座位的功能
 *setUsers 方法: 根据server端的玩家信息更新座位显示
 *btnChange 方法: 根据游戏状态更新按钮状态
 *startGame 方法: 开始游戏
 */
class roomClient extends Client {
    constructor(manager) {
        super(manager);
        this.regDialog = new RegDialog('请设置昵称', this.manager, this.user);
        this.inputBox = new msgBox(this);
        this.init();
    }
    init() {
        var This = this;
        this.manager.ws.onopen = function() {
            This.manager.getData(This);
            var data = {
                type: 'room'
            };
            This.manager.sendData(NEWLINK, data);
        }
    }
    setClient() {
    }
    setGameInf(msg) {
        this.gameState = msg.state;
        this.setUsers(msg.users);
        if(msg.state === 'over') {
            this.sitFn();
        }
    }
    sitFn() {
        var This = this,
            seats = getByCls("seat");
        for(var i = 0, len = seats.length; i<len; i++) {
            seats[i].ii = i;
            seats[i].onclick = function() {
                if(this.nextSibling.nextSibling.innerText != "") {
                    return 0;
                }
                if(this.ii === 0) {
                    var msg = {
                        type : 0,
                        msgValue : This.user.name
                    }
                    This.manager.sendData(MESSAGE, msg);
                }
                console.log(This.user);
                This.user.order = this.ii;
                This.user.state = 'ready';
                This.btnChange(This.gameState);
                This.manager.sendData(USER_INF, This.user.getUserInfo());
            }
        }
    }
    setUsers(userInf) {
        var seats = getByCls('seat'),
            userNum = userInf.length;
        for(var i=0; i<8; i++) {
            seats[i].style = null;
            seats[i].className = "seat";
            seats[i].nextSibling.nextSibling.innerText = null;
            for(var j=0; j<userNum; j++) {
                if(i === userInf[j].order) { 
                    var face = userInf[j].face,
                        posX = face%5*74,
                        posY = Math.floor(face/5)*74;
                    seats[i].className += " sitted";
                    seats[i].style.backgroundPosition = posX + "px " + posY +"px";
                    seats[i].nextSibling.nextSibling.innerText = userInf[j].name;
                }
            }
        }
        this.btnChange(this.gameState);
    }
    btnChange(type) {
        var This = this,
            manager = this.manager;
        switch (type) {
            case "gaming":
                $('start').value = '游戏中';
                $('start').style.background = '#c2a5f6';
                $('start').style.color= '#e2d5fb';
                $('start').onclick = null;
                break;
            case "over":
                if(This.user.state === 'ready') {
                    if(This.user.order === 0) {
                        $('start').value = '开始游戏';
                        $('start').style = null;
                        $('start').onclick = function() {
                            This.user.type = 'master';
                            manager.sendData(REQUEST_START);
                        } 
                    }else {
                        $('start').value = '等待开始';
                        $('start').style.background = '#c2a5f6';
                        $('start').style.color= '#e2d5fb';
                        $('start').onclick = null;
                    }
                } else {
                    $('start').value = "请入座";
                    $('start').onclick = null;
                }
                break;
            default:
                break;
        }
    }
    startGame() {
        window.location.href = 'game.html';
    }
}
