export {Memento}
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

