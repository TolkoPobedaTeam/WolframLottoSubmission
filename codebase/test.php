<?php
session_start();
?><!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8' />
<style type="text/css">
<!--
.chat_wrapper {
	width: 1000px;
	margin-right: auto;
	margin-left: auto;
	background: #CCCCCC;
	border: 1px solid #999999;
	padding: 10px;
	font: 12px 'lucida grande',tahoma,verdana,arial,sans-serif;
}
.chat_wrapper .message_box {
	background: #FFFFFF;
	height: 250px;
	overflow: auto;
	padding: 10px;
	border: 1px solid #999999;
}
.chat_wrapper .panel input{
	padding: 2px 2px 2px 5px;
}
.system_msg{color: #BDBDBD;font-style: italic;}
.user_name{font-weight:bold;}
.user_message{color: #88B6E0;}
-->
</style>
</head>
<body>	
<?php 
$colours = array('007AFF','FF7000','FF7000','15E25F','CFC700','CFC700','CF1100','CF00BE','F00');
$user_colour = array_rand($colours);
//echo "SESSID=".session_id();
?>

<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>

<script language="javascript" type="text/javascript">  
$(document).ready(function(){
	//create a new WebSocket object.
	var wsUri = "ws://wolf.verygame.ru:9000/"; 
	var sessid='<?php echo session_id();?>';
	websocket = new WebSocket(wsUri); 
	
	websocket.onopen = function(ev) { // connection is open 
		$('#message_box').append("<div class=\"system_msg\">Connected!</div>"); //notify user
	}

	$('#send-btn').click(function(){ //use clicks message send button	
		var mymessage = $('#message').val(); //get message text
		var myname = $('#name').val(); //get user name
		
		if(myname == ""){ //empty name?
			alert("Enter your Name please!");
			return;
		}
		if(mymessage == ""){ //emtpy message?
			alert("Enter Some message Please!");
			return;
		}
		
		//prepare json data
		var msg = {
		cmd:'chat.send',
		message: mymessage,
		name: myname,
		userid:sessid,
		color : '<?php echo $colours[$user_colour]; ?>'
		};
		//convert and send data to server
		websocket.send(JSON.stringify(msg));
	});
	
	$('#parsereport-btn').click(function(){ //use clicks message send button	
		var reportpath = $('#reportpath').val(); 
		
    	var msg = {
		cmd:'webulc.parsereport',
		userid:sessid,
		param1: reportpath
		};
		//convert and send data to server
		websocket.send(JSON.stringify(msg));
	});
	
	$('#gameconnect-btn').click(function(){ //use clicks message send button	
		var username = $('#username').val(); 	
    	var msg = {
		cmd:'game.connect',
		name:username,
		sessid:sessid,
		serverid: 1
		};
		//convert and send data to server
		$('#message_box').append("<div class=\"system_msg\"><span class=\"user_message\" style='color:green;'>SEND:"+JSON.stringify(msg)+"</span></div>");
		websocket.send(JSON.stringify(msg));
	});
	$('#setcard-btn').click(function(){ //use clicks message send button	
		var placeid = $('#cardid').val(); 	
    	var msg = {
		cmd:'game.setcard',
		placeid:placeid,
		sessid:sessid,
		};
		//convert and send data to server
		$('#message_box').append("<div class=\"system_msg\"><span class=\"user_message\" style='color:green;'>SEND:"+JSON.stringify(msg)+"</span></div>");
		websocket.send(JSON.stringify(msg));
	});
	
	//#### Message received from server?
	websocket.onmessage = function(ev) {
		var msg = JSON.parse(ev.data); //PHP sends Json data
		var type = msg.type; //message type
		var umsg = msg.message; //message text
		var uname = msg.name; //user name
		var ucolor = msg.color; //color

		/*if(type == 'usermsg') 
		{
			$('#message_box').append("<div><span class=\"user_name\" style=\"color:#"+ucolor+"\">"+uname+"</span> : <span class=\"user_message\">"+umsg+"</span></div>");
		}
		if(type == 'system')
		{
			$('#message_box').append("<div class=\"system_msg\">"+umsg+"</div>");
		}
		if(type == 'error')
		{
			$('#message_box').append("<div class=\"system_msg\">"+umsg+"</div>");
		}*/
		//if(type == 'debug')
		//{
		$('#message_box').append("<div class=\"system_msg\"><span class=\"user_message\">#"+type+"</span> "+ev.data+"</div>");
		//}
		
		//$('#message').val(''); //reset text
	};
	
	websocket.onerror	= function(ev){$('#message_box').append("<div class=\"system_error\">Error Occurred - "+ev.data+"</div>");}; 
	websocket.onclose 	= function(ev){$('#message_box').append("<div class=\"system_msg\">Connection Closed</div>");}; 
});
</script>
<div class="chat_wrapper">
<div class="message_box" id="message_box"></div>
<div class="panel">
<input type="text" name="name" id="name" placeholder="Your Name" maxlength="10" style="width:20%" value='a'  />
<input type="text" name="message" id="message" placeholder="Message" maxlength="80" style="width:60%"  value='1'  />
<button id="send-btn">Send</button>
<!--<br>
<br>
<input type="text" name="reportpath" id="reportpath" placeholder="reportpath" style="width:350px;" value='/var/www/web1/html/contao-3.1.1/system/modules/webulc/vendor/parser/files/L&D Reports/Menuestatistik/Menuestatistik.4.xlsx' />
<button id="parsereport-btn">Parse report</button>-->
<br>
<br>
<input type="text" name="username" id="username" placeholder="username" style="width:350px;" value='yak' />
<button id="gameconnect-btn">Connect to game</button>
<br>
<br>
<input type="text" name="cardid" id="cardid" placeholder="cardid" style="width:150px;" value='0' />
<button id="setcard-btn">Set to place (0-5)</button>

</div>
</div>

