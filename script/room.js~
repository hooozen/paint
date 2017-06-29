export {Room}
class Room {
	constructor(manager) {
		this.manager = manager;
		this.manager.ws.onopen = function() {
			manager.sendData(NEWLINK);	
		}
	}
}
