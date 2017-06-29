import {Manager} from './manager.js';
import {Client} from './client.js';
export {PaintClient}
class PaintClient extends Client{
	constructor(manager, canvas) {
		super(manager, canvas);
		this.diagram = new PaintCanvas(canvas, document.documentElement.clientWidth - 2, document.documentElement.clientHeight - 224, manager);
		this.init();
	}
	init() {
		var manager = this.manager;
		this.manager.ws.onopen = function() {
			manager.sendData(PAINTER);
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


