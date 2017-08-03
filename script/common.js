var REG_INF = 1,
    REG_RESULT = 2,
    NEWLINK = 3,
    GAME_INF = 4,
    USER_INF = 5,
    USERS_INF = 6,
    REQUEST_START = 7,
    MESSAGE = 8,
    GAME_START = 9,
    RESPONSE_INF = 10,
    SUBJECT = 11,
    CLIENT = 12;
var STYLE = 1001,
    START = 1002,
    DRAWING = 1003,
    CLEAR = 1004,
    UNDO = 1005,
    REDO = 1006,
    SAVE = 1007,
    REQUEST_PAINT = 1008;
/*
 *简化获取dom节点的操作
 */
function $(id) {
    return document.getElementById(id);
}
/*
 *根据节点类名获取Dom节点，返回包含该类名的节点集(array)
 *clsName： 查找的类名, 字符串
 *oParent： 查找范围，dom对象
 */
function getByCls(clsName, oParent) {
    var oParent = oParent || document;
    var tags = oParent.getElementsByTagName('*'); 
    var aResult = new Array();
    for(var i =0; i<tags.length; i++) {
	if(tags[i].className === clsName) {
	    aResult.push(tags[i]);
	} else {
	    var names = tags[i].className.split(" ");
	    for(var j=0; j<names.length; j++) {
		if(names[j] === clsName) {
		    aResult.push(tags[i]);
		}
	    }
	}
    }
    return aResult;
}
