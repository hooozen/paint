<!DOCTYPE html>
<?php
session_start();
?>
<html lang = 'zh'>
	<head>
		<meta charset = "utf-8">
		<meta name = "viewport" content = "width=device-width, initial-scale=1.0, user-scalable = no">
		<link rel="stylesheet" href="style/dialog.css" type="text/css">
		<title>长大版你画我猜</title>
		<style>
html,body,div {
	margin: 0;
	padding: 0;
}
a {
	text-decoration: none;
}
.face {
	background: url("images/face.jpg") center center no-repeat;
	background-size: contain;
	height: 200px;
	overflow: hidden;
	width: 100%;
}
.room {
	display: flex;
	padding:10px;
	flex-wrap: wrap;
}
.seat-wrap {
	width: 25%;
}
.seat {
	background: #eee;
	border-radius: 5px;
	height: 74px;
	margin: 4px auto;
	width: 74px;
}
.title {
	color: #8255fb;
	font-size: 25px;
	margin: 80px auto 0;
	text-align: center;
	text-shadow: 1px 1px 3px rgba(0,0,0,0.4);
	width: 100%;
}
.chd {
	color: #666;
	margin: 0 auto;
	text-align: right;
	width: 200px;
}
.btn-panel {
	text-align: center;
}
#start {
	background: #7130e0;
	border: none;
	color: #fff;
	border-radius: 4px;
	height: 40px;
	width: 80%
}
footer {
	color: #ddd;
	margin-top: 100px;
	font-size: 10px;
	text-align: center;
}
		</style>
	</head>
	<body>
		<header class="face">
			<div class="title">
				你 画 我 猜
			</div>
			<div class="chd">
				——长大版
			</div>
		</header>
		<div class = 'room'>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
			</div>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
			</div>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
			</div>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
			</div>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
			</div>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
			</div>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
			</div>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
			</div>
		</div>
		<div class='btn-panel'>
			<a href="game.html"><input id='start' type = 'button' value='开始游戏'></a>
		</div>
		<footer>
			长大版你画我猜<br>2017软件系软件体系结构课程设计-Hozen@live.com
		</footer>
	</body>
	<script type="text/javascript" src = "./script/class.js"></script>
	<script type="text/javascript">
	function text() {
		console.log("xx");
	 }
	function getSessID() {
		var arr = new Array();
		var reg = new RegExp("(^| )PHPSESSID=([^;]*)(;|$)");
		if(arr = document.cookie.match(reg)) {
			return unescape(arr[2]);
		} else {
			console.log("xx");
		}
	}
	window.onload = function() {
		var manager = new Manager("ws://localhost:4000");
		var regDia = new RegDialog('请输入姓名', manager);
		manager.getData(regDia);
		getSessID();
	}
	</script> 
</html>
