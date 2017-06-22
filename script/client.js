class Client {

	constructor(canvas, document) {
		this.canvas = canvas;
		this.document = document;
		this.color = 'hsl(0, 0%, ';
	}


	init() {
		this.canvas.init(this.document.documentElement.clientWidth - 2, this.document.documentElement.clientHeight - 224);
		//this.canvas.paint();
		$('pencilWidth').value = 1;
		$('saturation').value = 10;
		this.timer();
	}


	btnFn() {
		var This = this;
		$('clearCanvas').onclick = function() {
			This.canvas.clear();
			This.canvas.sendData(2,0);
		}
		$('undo').onclick = function(){
			This.canvas.undo()
			This.canvas.sendData(2,1);
		}
		$('redo').onclick = function() {
			This.canvas.redo();
			This.canvas.sendData(2,2);
		}
		$('pencilWidth').onchange = function() {
			This.canvas.setWidth(this.value);
		}

		this.colorBlock($('color-block').childNodes);
	}

	colorBlock(colors) {
		var This = this;
		for(var i=0, len=colors.length; i<len; i++) {
			colors[i].onclick = function() {
				This.color = colorTable[this.firstChild.id];
				if(this.firstChild.id=='BLACK') {
					This.canvas.setColor(This.color, 0);
					$('saturation').value = 0;
					$('saturation').style.background = "-webkit-linear-gradient(left,"+colorTable[this.firstChild.id]+"0%),"+colorTable[this.firstChild.id]+"100%))";
				} else {
					This.canvas.setColor(This.color, 50);
					$('saturation').value = 50;
					$('saturation').style.background = "-webkit-linear-gradient(left,"+colorTable[this.firstChild.id]+"20%),"+colorTable[this.firstChild.id]+"80%))";
				}
			}
		}

	}

	timer() {
		var time_left = 60;
		var timer = setInterval(function() {
			if(time_left == 0) {
				clearInterval(timer);
				return 0;
			}
			time_left--;
			$('time-left').innerText = time_left;
		},1000);
	}
}
