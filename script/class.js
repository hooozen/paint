var STYLE = 1;
var START = 2;
var DRAWING = 3;
var OPRERATE = 4;
var CLEAR = 5;
var UNDO = 6;
var REDO = 7;
var SAVE = 8;
var PAINTER = 9;
var SUBJECT = 11;

function $(id) {
	return document.getElementById(id);
}
class Dialog {
	constructor() {
		this.dialog = document.createElement("div");
		this.dialog.innerHTML = "<div id='dialog-title'></div><div id = 'dialog-body'></div>"
		this.dialog.className = "dialog";
		document.body.appendChild(this.dialog);

		var bgBlack = document.createElement('div');
		bgBlack.id = "bg-black";
		document.body.appendChild(bgBlack);	
	}
}

class SubjectDia extends Dialog {
	constructor(doc) {
		super(doc);
	}
	init(subject) {
		document.getElementById('dialog-title').innerText = "你画";
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
	remove() {
		document.body.removeChild(this.dialog);
		document.body.removeChild($('bg-black'));
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
		switch(type){
			case PAINTER:
				var data = {
					type : PAINTER
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
		var diagram = client.diagram;
		this.ws.onmessage = function(event) {
			var data = event.data;
			data = JSON.parse(data);
			var tmp = '{"xx":1,"ss":"修远湖"}';
			switch (data.type) {
				case SUBJECT:
					client.startGame(data.subject);	
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
}

class PaintClient extends Client{
	constructor(manager, canvas) {
		super(manager, canvas);
		this.diagram = new PaintCanvas(canvas, document.documentElement.clientWidth - 2, document.documentElement.clientHeight - 224, manager);
		this.init();
	}
	init() {
		var client = this;
		this.manager.ws.onopen = function() {
			manager.sendData(PAINTER);
		}
		this.manager.getData(client);
		var diagram = this.diagram,
			manager = this.manager;
		$('pencilWidth').value = 1;
		$('saturation').value = 0;
		this.editBtn();
		this.paint(manager);
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
		var dialog = new SubjectDia(this.doc, "subject_dialog");
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
		canvas.onmousemove = function(event) {
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
		canvas.onmouseleave = function() {
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
		this.diagram = new ShowCanvas(canvas, document.documentElement.clientWidth - 2, document.documentElement.clientHeight - 224, manager);
		this.init();
	}
	init() {
		this.manager.getData(this.diagram);
	}
}
