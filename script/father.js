/*
 *聊天输入框类,在room.html和show.html中均有使用
 *show方法在html页面中插入该输入框
 *sendMsg(client) 调用client的Manager对象向server发送消息
 */
class msgBox {
    constructor(client) {
	this.inputBox = document.createElement('div');
	this.inputBox.id = 'input-box';
	this.inputBox.innerHTML = "<input type = 'text' id = 'answer-input'><input type = 'button' id = 'send-message' value='发送'>";	

	this.show(client);
    }
    show(client) {
	document.body.appendChild(this.inputBox);
	this.sendMsg(client);
    }
    sendMsg(client) {
	var manager = client.manager,
	    user = client.user,
	    msg = $('answer-input'),
	    btn = $('send-message');
	msg.oninput = function() {
	    if(msg.value === '') {
		btn.style.background = "#abc6f9";
		btn.style.color = "#e8e8e8";
		btn.onclick = null;
	    } else {
		btn.style.background = "#61a6f9";
		btn.style.color = "#fff";
		btn.onclick = function() {
		    var data = new Object();
		    data.type = 1;
		    data.userName = user.name;
		    data.msgValue = msg.value;
		    manager.sendData(MESSAGE,data);
		    msg.value=null;
		    btn.onclick = null;
		    btn.style.background = "#abc6f9";
		    btn.style.color = "#e8e8e8";
		}
	    }

	}
    }

}
/*
 *消息管理类，用于发送和接收消息，所有client类均依赖于次类
 *sendData方法，有client调用，向server发送消息
 *getData方法，监听消息，根据消息调用相应的client方法
 */
class Manager {
    constructor(url) {
	this.ws = new WebSocket(url);
	this.ws.onclose = function() {
	    alert("ws disconnect");
	}
    }
    sendData(type, data) {
	var msg = new Object();
	msg.type = type;
	msg.data = data;
	msg = JSON.stringify(msg);
	console.log("sendMsg: "+msg);
	this.ws.send(msg);
    }
    getData(client) {
	this.ws.onmessage = function(event) {
	    var msg = event.data;
	    msg = JSON.parse(msg);
	    console.log("getMsg: ");
	    console.log(msg);

	    switch (msg.type) {
		case REG_RESULT:
		    if (msg.data === "success")
			client.regDialog.remove();
		    break;
		case GAME_INF:
		    client.setGameInf(msg);
		    break;
		case CLIENT:
		    client.setClient(msg.client);
		    break;
		case RESPONSE_INF:
		    if(msg.isNew) {
			client.regDialog.showUp();
		    } else {
			if(msg.user.userState === 'observer') {
			    client.regDialog.showUp()
			    break;
			}
			client.user.setUser(msg.user);
		    }
		    break;
		case USERS_INF:
		    client.setUsers(msg.users);
		    break;
		case MESSAGE:
		    client.showMsg(msg.data);
		    break;
		case GAME_START:
		    if (msg.data) {
			client.startGame();
		    } else {
			var msg = {
			    type : 2
			}
			client.showMsg(msg);
		    }
		    break;
		case SUBJECT:
                    client.startGame(msg.data);
		    break;
		default: 
		    break;
	    }
	}
    }
}
/*
 *User类，用于存储玩家信息
 *
 *
 */
class User {
    constructor() {
	this.name = null;
	this.face = Math.round(Math.random()*25);
	this.order = null;
	this.type = 'normal';
	this.state = 'observe';
    }
    setUser(data) {
	this.name = data.userName;
	this.face = data.userFace;
	this.order = data.userOrder;
	this.state = data.userState;
	this.type = data.userType;
	console.log(this);
    }
    getPHPSess() {
	var arr = new Array();
	var reg = new RegExp("(^| )PHPSESSID=([^;]*)(;|$)");
	if(arr = document.cookie.match(reg)) {
	    return unescape(arr[2]);
	} else {
	    return null;
	}
    }
    getUserInfo() {
	var res = {
	    name    : this.name,
	    face    : this.face,
	    type    : this.type,
	    order   : this.order,
	    state   : this.state,
	    session : this.PHPsession
	}
	return res;
    }
}
/*
 *Dialog类，对话框父类。
 *showUp方法: 在页面中弹出对话框
 *remove方法: 在页面中移除对话框
 */
class Dialog {
    constructor(title) {
	this.dialog = document.createElement("div");
	this.dialog.className = "dialog";

	this.diaTitle = document.createElement("div");
	this.diaTitle.id = 'dialog-title';
	this.diaTitle.innerText = title;
	this.diaBody = document.createElement("div");
	this.diaBody.id = 'dialog-body';

	this.dialog.appendChild(this.diaTitle);
	this.dialog.appendChild(this.diaBody);


	this.bgBlack = document.createElement('div');
	this.bgBlack.id = "bg-black";
    }
    showUp() {
	document.body.appendChild(this.bgBlack);	
	document.body.appendChild(this.dialog);
    }
    remove() {
	document.body.removeChild(this.dialog);
	document.body.removeChild(this.bgBlack);
    }
}
/*
 *Client父类, 生成Manager对象用于Socket通信，生成User对象用于存储玩家信息
 *showMsg 方法: 滚动展示玩家及系统消息
 *init 方法: 虚方法，初始化client
 */
class Client {
    constructor(manager) {
	this.manager = manager;
	this.user = new User();
	this.gameState = "over";
    }
    showMsg(msg) {
	var msgBoard = $('msgBoard');
	switch (msg.type) {
	    case 0:
		var content = '<span style="color: #80aee2">['+msg.msgValue+']成为房主</span>';
		break;
	    case 1:
		var content = '<span style="color: #80aee2">'+msg.userName+": </span>"+msg.msgValue;
		break;
	    case 2:
		var content = '<span style="color: #db30e0">至少需要2人入座</span>';
		break;
	    default:
		break;
	}
	var msg = document.createElement('div');
	msg.innerHTML = content;
	msg.style = null;
	msgBoard.appendChild(msg);
	var right = -50;
	var timer = setInterval(function() {
	    if(right > msgBoard.offsetWidth +5) {
		clearInterval(timer);
		msgBoard.removeChild(msg);
	    }
	    right += 1;
	    msg.style.right = right+"px";
	},10);

    }
}
class Memento {
	constructor() {
		this.history = new Array();
		this.historyStep = -1;
	}
	save(canvas) {
		this.historyStep++;
		//覆盖历史
		if(this.historyStep < this.history.length) {
			this.history.length = this.historyStep;
		}
		this.history.push(canvas.toDataURL());
	}
	undo(context) {
		if(this.historyStep <=0 ) return; //数组内仅有空白画面
		this.historyStep--;
		var pic = new Image();
		pic.src = this.history[this.historyStep];
		pic.onload = function() {
			context.drawImage(pic, 0, 0);	
		}
	}
	redo(context) {
		if(this.historyStep >= this.history.length - 1) return;
		this.historyStep++;
		var pic = new Image();
		pic.src = this.history[this.historyStep];
		pic.onload = function() {
			context.drawImage(pic, 0, 0);
		}
	}
	clear(canvas, context) {
		context.clearRect(0, 0, canvas.width, canvas.height);
		context.fillStyle = '#fff';
		context.fillRect(0, 0, canvas.width, canvas.height);
	}		

}

class DiagramEdit {
	constructor(canvas, width, height) {
		this.canvas = canvas,
			this.context = canvas.getContext('2d'),
			this.canvas.width = width,
			this.canvas.height = height,
			this.memento = new Memento();
		this.init();
	}
	init() {
		this.memento.clear(this.canvas, this.context);
		this.memento.save(this.canvas);
		this.context.lineJoin = 'round';
	}
	setWidth(width = 1) {
		this.context.lineWidth = width;
	}
	setColor(color = 'hsl(0, 0%, ', l) {
		if(color.substr(0,1) == '#') {
			this.context.strokeStyle = color;
		} else if(color == 'hsl(0, 0%, ') {
			this.context.strokeStyle = color + (l - 10) * 1.25 + "%)";
		} else {
			this.context.strokeStyle = color + l + "%)";
		}
	}
	getWidth() {
		return this.context.lineWidth;
	}
	getColor() {
		return this.context.strokeStyle;
	}
	message (uname, umesg, right) {
		var message = document.createElement('div');
		message.className='message';
		if(right) {
			message.innerText = uname+"答对了!";
			message.style.color = "red";
		} else {
			message.innerText=uname+":"+umesg;
			message.style.color = "#aaa";
		}
		$('title').appendChild(message);
		var right=0;
		var timer = setInterval(function(){
			if (right>=$('title').offsetWidth+5) {
				clearInterval(timer);
			}
			right += 2;
			message.style.right = right+"px";
		},20);
	}
}
