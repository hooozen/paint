export {RegDialog, SubjectDia}
class Dialog {
	constructor(title) {
		this.dialog = document.createElement("div");
		this.dialog.innerHTML = "<div id='dialog-title'>"+title+"</div><div id = 'dialog-body'></div>"
		this.dialog.className = "dialog";
		document.body.appendChild(this.dialog);

		var bgBlack = document.createElement('div');
		bgBlack.id = "bg-black";
		document.body.appendChild(bgBlack);	
	}
	remove() {
		document.body.removeChild(this.dialog);
		document.body.removeChild($('bg-black'));
	}
}

class RegDialog extends Dialog {
	constructor(title, manager) {
		super(title);
		$('dialog-body').innerHTML = "<input type='input' id='user-name'><input type='button' value='确 定' id='name-confirm'>"
		$('user-name').oninput = function() {
			if(this.value == '' || this.value.length<1 || this.value.length>6) {
				$('name-confirm').style.color = "#888";
				$('name-confirm').style.bordercolor = "#888";
				$('name-confirm').onclick = null;
			} else {
				$('name-confirm').style.color = "#eee";
				$('name-confirm').style.bordercolor = "#eee";
				$('name-confirm').onclick = function() {
					manager.sendData(USERNAME, $('user-name').value);
				}
			}
		}
	}
}

class SubjectDia extends Dialog {
	constructor(title) {
		super(title);
	}
	init(subject) {
		document.getElementById('dialog-body').innerHTML= "<div id = 'subject-item'>"+subject+"</div><div id = subject-tips>准备开始</div>";
		this.timer();
	}
	timer () {
		var time = $('subject-tips'),
			i = 5,
			This = this;
		var intval = setInterval(function() {
			time.innerText = i--;
			if ( i < 0 ) {
				clearInterval(intval);
				This.remove();
			}
		},1000);
	}
}

