var STYLE = 1;
var START = 2;
var DRAWING = 3;
var OPRERATE = 4;
var CLEAR = 5;
var UNDO = 6;
var REDO = 7;
var SAVE = 8;
var PAINTER = 9;
var ANSWER = 10;
var SUBJECT = 11;
var TIPS = 12;
var MESSAGE = 13; 
var RIGHT = 14;
var REGINF = 15;
var REBACK = 16;
var NEWLINK = 17;
var USERS = 18;
var USER_ORDER = 19;
var USER_INF = 20;
var REQUEST_START = 21;
var START_GAME = 22;

function $(id) {
	return document.getElementById(id);
}
function getByCls(clsName, oParent) {
	var oParent = oParent || document;
	var tags = oParent.getElementsByTagName('*'); 
	var aResult = new Array();
	for(var i =0; i<tags.length; i++) {
		if(tags[i].className == clsName) {
			aResult.push(tags[i]);
		} else {
			var names = tags[i].className.split(" ");
			for(var j=0; j<names.length; j++) {
				if(names[j] == clsName) {
					aResult.push(tags[i]);
				}
			}
		}
	}
	return aResult;
}
function getSessID() {
	var arr = new Array();
	var reg = new RegExp("(^| )PHPSESSID=([^;]*)(;|$)");
	if(arr = document.cookie.match(reg)) {
		return unescape(arr[2]);
	} else {
		return null;
	}
}

class User {
	constructor() {
		this.name = "";
		this.face = Math.round(Math.random()*25);
		this.order = "";
		this.statu = 'out';
		this.uType = 'normal';

		this.saveCookie('face', this.face);
		this.saveCookie('statu', this.statu);
		this.saveCookie('uType', this.uType);
	}
	saveCookie(key, value) {
		console.log(document.cookie);
		document.cookie = key + "=" + value;
	}
}
class Room {
	constructor(manager) {
		this.user = new User();
		this.manager = manager;
		this.dialog = new RegDialog('请输入姓名', manager, this.user);
		this.manager.ws.onopen = function() {
			manager.sendData(NEWLINK, getSessID());
		}
		manager.getData(this);
		this.sitFn();
	}
	sitFn() {
		var user = this.user,
			This = this,
			manager = this.manager,
			seats = getByCls("seat"); 
		for(var i = 0, len = seats.length; i<len; i++) {
			seats[i].ii = i;
			seats[i].onclick = function() {
				if(this.nextSibling.nextSibling.innerText != "")  {
					return;
				}
				user.order = this.ii;
				user.saveCookie('order', user.order);
				user.statu = 'waiting';
				user.saveCookie('statu', user.statu);
				manager.sendData(USER_ORDER, user.order);
				This.startGame();
			}
		}
	}
	setUsers(data) {
		var seats = getByCls('seat');	
		for(var i=0; i<8; i++) {
			for(var j=0; j<data['user_num']; j++) {
				if(i == data[j].order) {
					console.log(data[j]);
					var face = data[j].face,
						posX = face%5*74,
						posY = Math.floor(face/5)*74;
					seats[i].className += " sitted";
					seats[i].style.backgroundPosition = posX + "px " + posY +"px";
					seats[i].nextSibling.nextSibling.innerText = data[j].name;
					break;
				} else {
					seats[i].className = "seat";
					seats[i].nextSibling.nextSibling.innerText = null;
				}
			}
		}
		this.startGame();
	}
	startGame() {
		var This = this,
			manager = this.manager;
		if(this.user.statu == 'waiting') {
			$('start').value = '开始游戏';
			$('start').onclick = function() {
				This.user.uType = 'master';
				This.user.saveCookie('uType', 'master');
				manager.sendData(REQUEST_START);	
			}
		} else {
			this.value = "请入座";
			$('start').onclick = null;
		}
	}
}
class Dialog {
	constructor(title) {
		this.dialog = document.createElement("div");
		this.dialog.innerHTML = "<div id='dialog-title'>"+title+"</div><div id = 'dialog-body'></div>"
		this.dialog.className = "dialog";
		document.body.appendChild(this.dialog);

		var bgBlack = document.createElement('div');
		bgBlack.id = "bg-black";
		document.body.appendChild(bgBlack);	
	}
	remove() {
		document.body.removeChild(this.dialog);
		document.body.removeChild($('bg-black'));
	}
}

class RegDialog extends Dialog {
	constructor(title, manager, user) {
		super(title);
		var This = this;
		$('dialog-body').innerHTML = "<input type='input' id='user-name'><input type='button' value='确 定' id='name-confirm'>"
		$('user-name').oninput = function() {
			if(this.value == '' || this.value.length<1 || this.value.length>6) {
				$('name-confirm').style.color = "#888";
				$('name-confirm').style.bordercolor = "#888";
				$('name-confirm').onclick = null;
			} else {
				$('name-confirm').style.color = "#eee";
				$('name-confirm').style.bordercolor = "#eee";
				$('name-confirm').onclick = function() {
					manager.sendData(REGINF, $('user-name').value, user.face);
					user.name = $('user-name').value;
					user.saveCookie('name', user.name);
				}
			}
		}
		console.log(This.userName);
	}
}

class SubjectDia extends Dialog {
	constructor(title) {
		super(title);
	}
	init(subject) {
		document.getElementById('dialog-body').innerHTML= "<div id = 'subject-item'>"+subject+"</div><div id = subject-tips>准备开始</div>";
		this.timer();
	}
	timer () {
		var time = $('subject-tips'),
			i = 5,
			This = this;
		var intval = setInterval(function() {
			time.innerText = i--;
			if ( i < 0 ) {
				clearInterval(intval);
				This.remove();
			}
		},1000);
	}
}

class Manager {

	constructor(url) {
		this.ws = new WebSocket(url);
		this.ws.onclose = function() {
			alert("ws disconnect");
		}
	}

	sendData(type, x, y, c, w) {
		console.log("sendMsg: type:"+type+" x:"+x);
		switch(type){
			case NEWLINK:
				var data = {
					type : NEWLINK,
					session : x
				};
				break;
			case USER_ORDER:
				var data = {
					type : USER_ORDER,
					order : x
				}
				break;
			case PAINTER:
				var data = {
					type : PAINTER
				};
				break;
			case ANSWER:
				var data = {
					type : ANSWER
				};
				break;
			case START:
				var data = {
					type : START,
					x : x,
					y : y,
					color: c,
					width: w
				};
				break;
			case DRAWING:
				var data = {
					type : DRAWING,
					x : x,
					y : y
				};
				break;
			case MESSAGE:
				var data = {
					type: MESSAGE,
					value: x
				}
				break;
			case REGINF:
				var data = {
					type : REGINF,
					name : x,
					face : y,
				}
				break;
			default:
				var data = {
					type : type
				}
				break;
		}
		data = JSON.stringify(data);
		this.ws.send(data);
	}
	getData(client) {
		this.ws.onmessage = function(event) {
			var data = event.data;
			if('diagram' in client) {
				var diagram = client.diagram;
			}
			data = JSON.parse(data);
			console.log("getMsg: ");
			console.log(data);
			switch (data.type) {
				case USER_INF:
					client.setUsers(data);
					break;
				case SUBJECT:
					client.startGame(data.subject);	
					break;
				case START_GAME:
					if(client.user.uType == 'master') {
						window.location.href = "game.html";	
					} else {
						window.location.href = "show.html";
					}
					break;
				case TIPS:
					client.startGame(data.tips);
					break;
				case START:
					diagram.setColor(data.color);
					diagram.setWidth(data.width);
					diagram.drawStart(data.x, data.y);
					break;
				case DRAWING:
					diagram.drawing(data.x, data.y);
					break;
				case SAVE:
					diagram.save();
					break;
				case UNDO:
					diagram.undo();
					break;
				case REDO:
					diagram.redo();
					break;
				case CLEAR:
					diagram.clear();
					break;
				case MESSAGE:
					diagram.message("user", data.value, false);
					break;
				case RIGHT:
					diagram.message("user", "", true)
					break;
				case REBACK:
					client.dialog.remove();
					break;
				default: 
					break;
			}
		}
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

class PaintCanvas extends DiagramEdit {
	constructor(canvas, width, height, manager) {
		super(canvas, width, height);
		this.manager = manager;
	}
	drawStart(x, y) {
		this.lastX = x;
		this.lastY = y;
		this.manager.sendData(START, x, y, this.getColor(), this.getWidth());
	}
	drawing(x, y) {
		var context = this.context;
		context.beginPath();
		context.moveTo(this.lastX, this.lastY);
		context.lineTo(x, y);
		context.closePath();
		context.stroke();
		this.lastX = x,
			this.lastY = y;
		this.manager.sendData(DRAWING, x, y);
	}
	save() {
		this.memento.save(this.canvas);
		this.manager.sendData(SAVE);
	}
	undo() {
		this.memento.undo(this.context);
		this.manager.sendData(UNDO);
	}
	redo() {
		this.memento.redo(this.context);
		this.manager.sendData(REDO);
	}
	clear() {
		this.memento.clear(this.canvas, this.context);
		this.manager.sendData(CLEAR);
	}
}

class ShowCanvas extends DiagramEdit {
	constructor(canvas, width, height) {
		super(canvas, width, height);
	}
	drawStart(x, y) {
		this.lastX = x;
		this.lastY = y;
	}
	drawing(x, y) {
		var context = this.context;
		context.beginPath();
		context.moveTo(this.lastX, this.lastY);
		context.lineTo(x, y);
		context.closePath();
		context.stroke();
		this.lastX = x,
			this.lastY = y;
	}
	save() {
		this.memento.save(this.canvas);
	}
	undo() {
		this.memento.undo(this.context);
	}
	redo() {
		this.memento.redo(this.context);
	}
	clear() {
		this.memento.clear(this.canvas, this.context);
	}
}
class Client {
	constructor(manager, canvas) {
		this.manager = manager;
		this.canvas = canvas;
	}
	timer() {
		var i = 65;
		var timerBox = $('time-left');
		var timer = setInterval(function() {
			if (i <= 60) {
				timerBox.innerText = i;	
			}
			if (i <= 0) {
			clearInterval(timer);
			}
			i--;
		}, 1000);
	}
	getCookie() {
		var msg = {};
		var cookie = document.cookie;
		cookie = cookie.split("; ");	
		for(var i = 0; i<cookie.length; i++) {
			var tmp = cookie[i].split("=");
			msg[tmp[0]] = tmp[1];	
		}
		return msg;
	}
	setUsers(data) {
		var len = data.user_num;
		for(var i = 0; i<len; i++) {
			for(var j = i+1; j<len; j++) {
				if(data[i].order > data[j]) {
					var tmp = data[i];
					data[i] = data[j];
					data[j] = tmp;
				}
			}
		}
		for(var i = 0; i<len; i++) {
			var oLi = document.createElement('li');
			var face = data[i].face,
				posX = face%5*40,
				posY = Math.floor(face/5)*40,
				imgPos = posX + "px " + posY + "px";

			oLi.innerHTML= '<div class="score"></div><div class="uface" style="background-position:' + imgPos + '"></div><div class="unmae">'+data[i].name+'</div>';
			$('user-list').appendChild(oLi);
			if(data[i].uType == 'master') {
				var paint = document.createElement("div");
				paint.className = "painting";
				oLi.appendChild(paint);
			}

		}
	}
}

class PaintClient extends Client{
	constructor(manager, canvas) {
		super(manager, canvas);
		this.diagram = new PaintCanvas(canvas, document.documentElement.clientWidth - 2, document.documentElement.clientHeight - 224, manager);
		this.init();
	}
	init() {
		var manager = this.manager,
			cookie = this.getCookie();
		this.manager.ws.onopen = function() {
			cookie.type = PAINTER;
			cookie = JSON.stringify(cookie);
			console.log(cookie);
			manager.ws.send(cookie);
		}
		this.manager.getData(this);
		$('pencilWidth').value = 1;
		$('saturation').value = 0;
		this.editBtn();
		this.paint(manager);
		this.timer();
	}
	setX(event) {
		var event = event || window.event;
		var cx = event.clientX || event.touches[0].pageX;
		return cx - this.canvas.offsetLeft;
	}
	setY(event) {
		var event = event || window.event;
		var cy = event.clientY ||event.touches[0].paegY;
		return cy - this.canvas.offsetTop;
	}
	startGame(subject) {
		var dialog = new SubjectDia("你画");
		dialog.init(subject);
		$('item').innerText = subject;
	}
	paint(manager) {
		var manager = this.manager,
			diagram = this.diagram,
			canvas = this.canvas,
			This = this,
			mousePressed = false;
		canvas.onmousedown = function(event) {
			mousePressed = true;
			var x = This.setX(event),
				y = This.setY(event);
			diagram.drawStart(x, y);
		}
		canvas.ontoucestart = function(event) {
			mousePressed = true;
			var x = This.setX(event),
				y = This.setY(event);
			diagram.drawStart(x, y);
		}

		canvas.onmousemove = function(event) {
			if(mousePressed) {
				var x = This.setX(event),
					y = This.setY(event);
				diagram.drawing(x, y);
			}
		}
		canvas.ontouchmove = function(event) {
			if(mousePressed) {
				var x = This.setX(event),
					y = This.setY(event);
				diagram.drawing(x, y);
			}
		}

		canvas.onmouseup = function() {
			if(mousePressed) {
				mousePressed = false;
				diagram.save(manager);
			}
		}
		canvas.ontouchend = function() {
			if(mousePressed) {
				mousePressed = false;
				diagram.save(manager);
			}
		}

		canvas.onmouseleave = function() {
			if(mousePressed) {
				mousePressed = false;
				diagram.save(manager);
			}
		}
		canvas.onmousecancel = function() {
			if(mousePressed) {
				mousePressed = false;
				diagram.save(manager);
			}
		}


	}

	editBtn() {
		var diagram = this.diagram,
			color = 'hsl(0, 0%, ';
		$('clearCanvas').onclick = function() {
			diagram.clear();
		}
		$('undo').onclick = function() {
			diagram.undo();
		}
		$('redo').onclick = function() {
			diagram.redo();
		}
		$('pencilWidth').onchange = function() {
			diagram.setWidth(this.value);
		}
		$('saturation').onchange = function() {
			diagram.setColor(color, this.value);
		}

		var colors = $('color-block').childNodes;
		var colorTable = {
			BLACK : 'hsl(0, 0%, ',
			RED : 'hsl(0, 100%, ',
			YELLOWr : 'hsl(40, 100%, ',
			YELLOWg : 'hsl(80, 100%, ',
			GREEN : 'hsl(120, 100%, ',
			CYANg : 'hsl(160, 100%, ',
			CYANb : 'hsl(200, 100%, ',
			BLUE : 'hsl(240, 100%, ',
			MAGENTAb : 'hsl(280, 100%, ',
			MAGENTAr : 'hsl(320, 100%, ',
		}

		for(var i=0, len=colors.length; i<len; i++) {
			colors[i].onclick = function() {
				color = colorTable[this.firstChild.id];
				if(this.firstChild.id=='BLACK') {
					diagram.setColor(color, 0);
					$('saturation').value = 0;
					$('saturation').style.background = "-webkit-linear-gradient(left,"+colorTable[this.firstChild.id]+"0%),"+colorTable[this.firstChild.id]+"100%))";
				} else {
					diagram.setColor(color, 50);
					$('saturation').value = 50;
					$('saturation').style.background = "-webkit-linear-gradient(left,"+colorTable[this.firstChild.id]+"20%),"+colorTable[this.firstChild.id]+"80%))";
				}
			}
		}
	}
}

class ShowClient extends Client {
	constructor(manager, canvas) {
		super(manager, canvas);
		this.diagram = new ShowCanvas(canvas, document.documentElement.clientWidth - 2, document.documentElement.clientHeight - 182, manager);
		this.init();
	}
	init() {
		var manager = this.manager,
			cookie = this.getCookie();
		this.manager.ws.onopen = function() {
			cookie.type = ANSWER;
			cookie = JSON.stringify(cookie);
			console.log(cookie);
			manager.ws.send(cookie);
		}
		this.manager.getData(this);
		this.timer();
		this.sendMsg();
	}
	startGame(tips) {
		var dialog = new SubjectDia("提示");
		dialog.init(tips+"个字");
		$('item').innerText = tips + "个字";
	}
	sendMsg() {
		var manager = this.manager;
		var msg = $('answer-input'),
			btn = $('send-message');
		msg.oninput = function() {
			if(msg.value == '') {
				btn.style.background = "#abc6f9";
				btn.style.color = "#e8e8e8";
				btn.onclick = null;
			} else {
				btn.style.background = "#61a6f9";
				btn.style.color = "#fff";
				btn.onclick = function() {
					manager.sendData(MESSAGE, msg.value);
					msg.value=null;
					btn.onclick = null;
					btn.style.background = "#abc6f9";
					btn.style.color = "#e8e8e8";
				}
			}

		}
	}
}
