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
class ShowClient extends GameClient {
    constructor(manager, canvas) {
	super(manager, canvas);
	this.diagram = new ShowCanvas(canvas, document.documentElement.clientWidth - 2, document.documentElement.clientHeight - 182, manager);
	this.init();
    }
    init() {
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
	    btn = $('send-message'),
	    This = this;
	msg.oninput = function() {
	    if(msg.value == '') {
		btn.style.background = "#abc6f9";
		btn.style.color = "#e8e8e8";
		btn.onclick = null;
	    } else {
		btn.style.background = "#61a6f9";
		btn.style.color = "#fff";
		btn.onclick = function() {
		    console.log(This.user);
		    manager.sendData(MESSAGE, This.user.name, msg.value);
		    msg.value=null;
		    btn.onclick = null;
		    btn.style.background = "#abc6f9";
		    btn.style.color = "#e8e8e8";
		}
	    }

	}
    }
}
