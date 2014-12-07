$(document).ready(function(){
	//create a new WebSocket object.
	var wsUri = "ws://wolf.verygame.ru:9000/"; 
	websocket = new WebSocket(wsUri);
	
	websocket.onopen = function(ev) { // connection is open 
	    console.log("Connected!");
	}

	//#### Message received from server?
	websocket.onmessage = function(ev) {
		var msg = JSON.parse(ev.data); //PHP sends Json data
		var type = msg.type; //message type
		var data = msg.data;
		
		console.log("ONMSG:"+ev.data);
		
		if (type == "game.refresh") Game.Refresh(data);
		if (type == "game.start") Game.Start(data);
		if (type == "game.round.start") Game.StartRound(data);
		if (type == "game.round.end") Game.EndRound(data);
	};
	
	websocket.onerror	= function(ev){console.log(ev.data)}; 
	websocket.onclose 	= function(ev){console.log(ev.data)}; 
});