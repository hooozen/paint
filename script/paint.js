function $(id) { return document.getElementById(id); }
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
class myCanvas {
	constructor(canvas){
		this.canvas = canvas;
		this.context = canvas.getContext('2d');
		this.lastX = 0;
		this.lastY = 0;
		this.history = new Array();
		this.historyStep = -1;
	}
	init(width, height) {
		this.canvas.width = width;
		this.canvas.height = height;
		this.setJoin();
		this.setColor();
		this.setWidth();
		this.clear();
	}
	setWidth(width = 1) {
		this.context.lineWidth = width;
	}
	setColor(color = 'hsl(0, 0%, ', l) {
		if(color == 'hsl(0, 0%, ') {
			this.context.strokeStyle = color + (l-10)*1.25+ "%)";
		} else {
			this.context.strokeStyle = color + l + "%)"; 
		}
	}
	setSaturation(s) {
	}
	setAlpha(num) {
		this.context.globalAlpha = num;
	}
	setJoin(type='round') {
		this.context.lineJoin = type; 
	}
	getWidth() {
		return this.context.lineWidth;
	}
	getColor() {
		return this.context.strokeStyle;
	}
	getAplpha() {
		return this.context.globalAlpha;
	}
	drawStart(x, y) {
		this.lastX = x;
		this.lastY = y;
	}
	drawing(x, y, isDown) {
		this.context.beginPath();
		this.context.moveTo(this.lastX, this.lastY);
		this.context.lineTo(x, y);
		this.context.closePath();
		this.context.stroke();
		this.lastX = x;
		this.lastY = y;
	}
	save() {
		this.historyStep++;
		if(this.historyStep < this.history.length) {
			this.history.length = this.historyStep;
		}
		this.history.push(this.canvas.toDataURL());
	}
	undo() {
		if(this.historyStep <= 0) return;
		var This = this;
		this.historyStep--;
		var pic = new Image();
		pic.src = this.history[this.historyStep];
		pic.onload = function() {
			This.context.drawImage(pic,0,0);
		}
	}
	redo() {
		if(this.historyStep >= this.history.length-1) return; 
		var This = this;
		this.historyStep++;
		var pic = new Image();
		pic.src = this.history[this.historyStep];
		pic.onload = function() {
			This.context.drawImage(pic, 0, 0);
		}
	}
	clear() {
		this.context.clearRect(0, 0, this.canvas.width, this.canvas.height);
		this.context.fillStyle = '#fff';
		this.context.fillRect(0, 0, this.canvas.width, this.canvas.height);
		this.save();
	}
}
class PaintCanvas extends myCanvas {
	constructor(canvas) {
		super(canvas);
		this.paths = new Array();
	}
	getX(event) {
		var event = event || window.event;
		var cx = event.clientX || event.touches[0].pageX;
		return cx - this.canvas.offsetLeft;
	}
	getY(event) {
		var event = event || window.event;
		var cy = event.clientY || event.touches[0].pageY
		return cy - this.canvas.offsetTop;
	}
	setPath(x, y) {
		this.path.pageX.push(x);
		this.path.pageY.push(y);
	}
	paint() {
		var This = this;
		var mousePressed = false;
		var touchPressed = false;
		this.canvas.onmousedown = function(event) {
			This.drawStart(This.getX(event), This.getY(event));
			mousePressed = true;
		}
		this.canvas.onmousemove = function(event) {
			if(mousePressed) {
				This.drawing(This.getX(event), This.getY(event));
			}
		}
		this.canvas.onmouseup  = function(event) {
			if(mousePressed) {
				mousePressed = false;
				This.save();
			}
		}
		this.canvas.onmouseleave = function(event) {
			if(mousePressed) {
				mousePressed = false;
				This.save();
			}
		}

		this.canvas.ontouchstart = function(event) {
			This.drawStart(This.getX(event), This.getY(event));
			touchPressed = true;
		}
		this.canvas.ontouchmove = function(event) {
			if(touchPressed) {
				This.drawing(This.getX(event), This.getY(event));
			}
		}
		this.canvas.ontouchend = function(event) {
			if(touchPressed) {
				touchPressed = false;
				This.save();
			}
		}
		this.canvas.ontouchleave = function(event) {
			if(touchPressed) {
				touchPressed = false;
				This.save();
			}
		}
		this.canvas.ontouchcancel = function(event) {
			if(touchPressed) {
				touchPressed = false;
				This.save();
			}
		}
	}
}
