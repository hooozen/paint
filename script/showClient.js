export {ShowClient}
import {Client} from './class.js';
import {Manager} from './manager.js';

class ShowClient extends Client {
	constructor(manager, canvas) {
		super(manager, canvas);
		this.diagram = new ShowCanvas(canvas, document.documentElement.clientWidth - 2, document.documentElement.clientHeight - 182, manager);
		this.init();
	}
	init() {
		var manager = this.manager;
		this.manager.ws.onopen = function() {
			manager.sendData(ANSWER);
		}
		this.manager.getData(this);
		this.timer();
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
			btn = $('send-message');
		msg.oninput = function() {
			if(msg.value == '') {
				btn.style.background = "#abc6f9";
				btn.style.color = "#e8e8e8";
				btn.onclick = null;
			} else {
				btn.style.background = "#61a6f9";
				btn.style.color = "#fff";
				btn.onclick = function() {
					manager.sendData(MESSAGE, msg.value);
					msg.value=null;
					btn.onclick = null;
					btn.style.background = "#abc6f9";
					btn.style.color = "#e8e8e8";
				}
			}

		}
	}
}
