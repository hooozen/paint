export {Client}
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

}

