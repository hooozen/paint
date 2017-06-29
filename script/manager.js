export {Manager}
class Manager {
	constructor(url) {
		this.ws = new WebSocket(url);
		this.ws.onclose = function() {
			alert("ws disconnect");
		}
	}
	sendData(type, x, y, c, w) {
		console.log("type:"+type+"x:"+x);
		switch(type){
			case PAINTER:
				var data = {
					type : PAINTER
				};
				break;
			case ANSWER:
				var data = {
					type : ANSWER
				};
				break;
			case START:
				var data = {
					type : START,
					x : x,
					y : y,
					color: c,
					width: w
				};
				break;
			case DRAWING:
				var data = {
					type : DRAWING,
					x : x,
					y : y
				};
				break;
			case MESSAGE:
				var data = {
					type: MESSAGE,
					value: x
				}
				break;
			case USERNAME:
				var data = {
					type : USERNAME,
					name : x
				}
				break;
			default:
				var data = {
					type : type
				}
				break;
		}
		data = JSON.stringify(data);
		this.ws.send(data);
	}
	getData(client) {
		console.log('diagram' in client);
		if('diagram' in client) {
			console.log("xx");
			var diagram = client.diagram;
		}
		this.ws.onmessage = function(event) {
			var data = event.data;
			data = JSON.parse(data);
			console.log(data);
			switch (data.type) {
				case SUBJECT:
					client.startGame(data.subject);	
					break;
				case TIPS:
					client.startGame(data.tips);
					break;
				case START:
					diagram.setColor(data.color);
					diagram.setWidth(data.width);
					diagram.drawStart(data.x, data.y);
					break;
				case DRAWING:
					diagram.drawing(data.x, data.y);
					break;
				case SAVE:
					diagram.save();
					break;
				case UNDO:
					diagram.undo();
					break;
				case REDO:
					diagram.redo();
					break;
				case CLEAR:
					diagram.clear();
					break;
				case MESSAGE:
					console.log("xx");
					diagram.message("user", data.value, false);
					break;
				case RIGHT:
					diagram.message("user", "", true)
					break;
				case REBACK:
					client.remove();
					console.log("success");
					break;
				default: 
					break;
			}
		}
	}
}

