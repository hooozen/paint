export {PaintCanvas}
import {DiagramEdit} from './diagramEidt.js';
import {Manager} from './manager.js';
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

