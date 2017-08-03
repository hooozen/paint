class PaintCanvas extends DiagramEdit {
    constructor(canvas, width, height, manager) {
	super(canvas, width, height);
	this.manager = manager;
    }
    drawStart(x, y) {
	this.lastX = x;
	this.lastY = y;
	var data = {
	    x: x,
	    y: y,
	    color: this.getColor(),
	    width: this.getWidth()
	}
	this.manager.sendData(START, data);
    }
    drawing(x, y) {
	var context = this.context;
	context.beginPath();
	context.moveTo(this.lastX, this.lastY);
	context.lineTo(x, y);
	context.closePath();
	context.stroke();
	this.lastX = x;
	this.lastY = y;
	var data = {
	    x: x,
	    y: y
	}
	this.manager.sendData(DRAWING, data);
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
class PaintClient extends GameClient{
    constructor(manager, canvas) {
	super(manager, canvas);
	this.diagram = new PaintCanvas(canvas, document.documentElement.clientWidth - 2, document.documentElement.clientHeight - 224, this.manager);
	this.init();
    }
    init() {
	this.editBtn();
	this.paint(this.manager);
	$('pencilWidth').value = 1;
	$('saturation').value = 0;
        this.requreStart('newgame');
    }
    setX(event) {
	var event = event || window.event;
	var cx = event.clientX || event.touches[0].pageX;
	return cx - this.canvas.offsetLeft;
    }
    setY(event) {
	var event = event || window.event;
	var cy = event.clientY ||event.touches[0].pageY;
	return cy - this.canvas.offsetTop;
    }
    requreStart(type) {
	this.manager.sendData(REQUEST_PAINT, type);
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
	canvas.ontouchstart = function(event) {
	    mousePressed = true;
	    var x = This.setX(event),
		y = This.setY(event);
	    console.log("x:"+x+",y:"+y);
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
	if($('dialog')) {
	    this.remove();
	}
	this.showUp();
	this.timer();
    }
    timer () {
	var time = this.tips,
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
