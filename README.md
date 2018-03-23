# paint
Html5 你画我猜移动 Web 版
## 概述
这是一款基于 HTML5、JavaScript、CSS、PHP 纯手工实现的 web 在线小游戏。

因为这个游戏的初衷是做“软件体系结构”和“计算机网络”的课程设计，所以使用了 ES6 的语法和面向对象的编程方法，后台使用 PHP socket 编程自己实现了 WebSocket 服务器。

所以，相对来说前端的知识使用的比较基础，难点和重点在于类之间的设计和联系以及计算机网络通信的原理。
## 项目地址
游戏地址：[你画我猜](http://hozen.site/paint)
**由于域名上了 https，导致 websocket 连接不被允许，所以该地址暂无法体验游戏**

说明：一直是基于移动端和最新版Chrome开发的，所以手机端chrome体验可能要好一些，兼容性也不是很健壮。推荐使用手机或桌面最新版 Chrome 或者 FireFox 体验
## 本地部署
使用线上服务器网络延迟可能会比较高，所以可以将项目部署到本地，使用同一个路由器的玩家可以一起游戏。
#### 运行环境
Apache php+cli
#### 游戏部署
+ 下载源码
```shell
cd /var/www/html
git clone https://github.com/hooozen/paint.git
```
+ 查看本机ip
```shell
sudo ifconfig
```
+ 修改 WebSocket 服务器地址
```JavaScript
//修改 script 目录下的 common.js
var vsHost = "[本机ip]",
    vsPort = "4000";
```
+ 启动游戏服务器
```shell
cd /var/www/html/paint/server
php server.php
```
+ 进行游戏
启动 Apache 服务器后，局域网内的用户就可以通过 192.168.x.x/paint 进入游戏了

#### 可能遇到的问题
+ PHP 扩展
server.php 中用到了一些非默认安装的扩展（如 mb_str 模块），若运行报错，可更具提示安装响应的 php 模块
+ 无法连接 WebSockt 服务器
1. 查看本机防火墙是否开放了 4000 端口
2. 查看 4000 端口是否被占用，可以通过修改 server.php 修改 WebSocket 服务器端口
+ 其他问题
如果有游戏 bug 或者部署的问题请留言

## 文档
在 doc 目录下提供了课程设计报告（report.pdf）和讲解幻灯片（interpret.pptx)
#### ppt截图
![image](https://github.com/hooozen/paint/blob/master/doc/interpret/require.png)
![image](https://github.com/hooozen/paint/blob/master/doc/interpret/feature.png)
![image](https://github.com/hooozen/paint/blob/master/doc/interpret/hsl-color.png)
## 系统设计
当时做课程设计的时候是先做了 UML 设计的，后来课程设计结束后，仍然有很多Bug（多人即时在线游戏的各种数据同步和及时性的设计真的让人头疼...）

后来进行了几次改动直接在代码上改动了，没再进行 UML 设计。

再后来突然发现该找工作了（已是九月份中旬），就没有继续完善，因为虽然这个游戏开发起来的难度比挺大的。但对于前端技术的要求不是很高，主要是系统设计、游戏逻辑、和网络通信的设计太复杂。
### UML
这是最初的 UML 设计，后来功能增加了很多，设计也有修改。所有的 UML 图在 doc/UML 目录中。
#### 用例图
+ 准备状态用例

![image](https://github.com/hooozen/paint/blob/master/doc/UML/Ready%20Game.png)

+ 游戏状态用例

![image](https://github.com/hooozen/paint/blob/master/doc/UML/Play%20Game.png)
#### 类图
+ 类图
![image](https://github.com/hooozen/paint/blob/master/doc/UML/class%20diagram.png)
#### 时序图
时序图比较多，不一一列举了

![image](https://github.com/hooozen/paint/blob/master/doc/UML/message.png)
