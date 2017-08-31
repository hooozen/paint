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
        this.lastX = x;
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
        this.inputBox = new msgBox(this);
    }
    startGame(msg) {
        var dialog = new SubjectDia("提示");
        dialog.init(msg.data+"个字");
        $('item').innerText = msg.data + "个字";
        this.timer(msg.time);
    }
}
