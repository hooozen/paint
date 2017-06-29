export {DiagramEdit}
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

