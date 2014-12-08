Game = {};
Game.Init = function (cfg)
{
    Game.sessid = cfg.sessid;
    Game.OpenWebSocket(cfg.wsURL);
    Interface.Init();
}

Game.OpenWebSocket = function (wsUri)
{
    var websocket =new WebSocket(wsUri);
    
    websocket.onopen = function(ev) { // connection is open 
	    console.log("Connected!");
	}

	//#### Message received from server?
	websocket.onmessage = function(ev) {
		var msg = JSON.parse(ev.data); //PHP sends Json data
		var type = msg.type; //message type
		var data = msg.data;
		
		console.log("ONMSG:"+ev.data);
		
		if (type == "game.info") Game.Info(data);
		if (type == "game.refresh") Game.Refresh(data);
		if (type == "game.start") Game.Start(data);
		if (type == "game.round.start") Game.StartRound(data);
		if (type == "game.round.end") Game.EndRound(data);
		if (type == "game.end") Game.End(data);
		if (type == "error") Game.Error(msg.message);
	};
	
	websocket.onerror	= function(ev){console.log(ev.data)}; 
	websocket.onclose 	= function(ev){console.log(ev.data)}; 
	
	Game.websocket = websocket;
}

Game.Info = function (data) {
     Game.RefreshUsers(data);
     Game.RefreshLoto(data);
     Interface.ModalInfoHide();
     if (!Game.isLogged) Interface.LogIn();
}

Game.Refresh = function (data) {
    Game.RefreshUsers(data);
    
    // timer
    if (data.users.length>1 && (data.dtime||0)>0) {
        $("#startGameCounter").show();
        var timer = data.dtime;
        Game.startInterval = setInterval(function(){
            $("#startGameCounter b").text(timer);
            timer--;
            if (timer<0) {
                clearInterval(Game.startInterval);
                $("#startGameCounter").fadeOut();
            }
        }, 1000);
    }
}

Game.Start = function (data) {
    Game.RefreshUsers(data);
    Game.RefreshLoto(data);
}

Game.RefreshUsers = function (data) {
    var usersHTML = "";
    for (var u=0; u<data.users.length; u++) {
        var className = [];
        
        if (data.users[u].sessmd5 == $.md5(Game.sessid)) {
            Game.isLogged = true;
            className.push("iam");
        }
        
        usersHTML += '<li class='+className.join(' ')+'>'+data.users[u].name
            +'<i class="success">'+data.users[u].success+'</i>'
            +'<i class="errors">'+data.users[u].errors+'</i>'
            +'</li>';
    }
    $(".users ul").html(usersHTML);
    
    if (data.users.length>1) {
        Interface.InfoHide("Wait perhaps 1 opponent.");
    } else {
        Interface.Info("Wait perhaps 1 opponent.");
    }
}

Game.RefreshLoto = function (data) {
    var loto = "";
    for (var u=0; u<data.users.length; u++) {
        if (data.users[u].sessmd5 == $.md5(Game.sessid)) {
            for (var i=0; i<data.users[u].positions.length; i++) {
                var title = data.users[u].positions[i].name;
                var placeid = data.users[u].positions[i].id;
                var statusCls = data.users[u].positions[i].status == 1 ? " statusOk" : "";
                loto += '<div class="block'+statusCls+'" data-placeid="'+placeid+'"><center><i>'+title+'</i></center></div>';
            }
        }
    }
    $(".loto").html(loto);
}

Game.StartRound = function (data) {
    $(document.body).append($("#card").clone().attr("id","draggable"));
    
    // set data in cloned card
    var cardHTML = "";
    for (var p in data.carddata.data) {
        var param = data.carddata.data[p];
        cardHTML += "<li>"+param.name+": <span>"+param.value+"</span></li>";
    }
    $("#draggable ul").html(cardHTML);
    $("#draggable img").attr("src", data.carddata.img);
    
    Interface.DraggableCard();
    
    $("#roundCounter").fadeIn();
    $("#roundCounter b").html(data.roundid);
    
    var rightAnswer = Game.cardname ? "<br><br><br>Right Answer was: <b>" + Game.cardname + "</b>" : "";
    Interface.Info(rightAnswer);
}

Game.EndRound = function (data) {
    Game.cardname = data.cardname;
    
    $("#draggable").draggable({disabled:true});
    $("#draggable").attr("id", "").fadeOut(500, function () {
        $(this).remove();
        Interface.ResetActive();
    });
    
    $("#roundCounter").fadeOut();
    
    var rightAnswer = Game.cardname ? "<br><br>Right Answer was: <b>" + Game.cardname + "</b>" : "";
    Interface.Info(
         "Wait next round. (Sometimes Wolfram API long answer)"
        + rightAnswer
    );
    
    Game.Refresh(data);
    Game.RefreshLoto(data);
}

Game.Connect = function ()
{
    var username = $('#username').val();
	var msg = {
            cmd : 'game.connect',
            name:username,
	        sessid : Game.sessid,
	        serverid : 1
	    };
	
	//convert and send data to server
	Game.websocket.send(JSON.stringify(msg));
	console.log("SEND: "+JSON.stringify(msg));
}

Game.Choice = function (placeid)
{
	var msg = {
        cmd : 'game.setcard',
        placeid : placeid,
        sessid : Game.sessid
    };
	
	//convert and send data to server
	Game.websocket.send(JSON.stringify(msg));
	console.log("SEND: "+JSON.stringify(msg));
}

Game.End = function (data)
{
    Interface.InfoHide();
    
    var endHTML = "Game End.<br>"
    
    if (data.winners.length) {
        var winners = [];
        var users = [];
        var iWinner = false;
        
        console.log(data.users.length);
        for (var i=0; i<data.users.length; i++) {
            users[data.users[i].userid] = data.users[i];
            console.log(data.users[i]);
        }
        for (var i=0; i<data.winners.length; i++) {
            var userid = data.winners[i];
            
            if (users[userid]) {
                winners.push(users[userid].name);
                if (users[userid].sessmd5 == $.md5(Game.sessid)) iWinner = true;
            }
        }
        console.log(users);
        endHTML += "Winners: " + winners.join(", ") + ".<br><br>";
        if (iWinner) endHTML += "Congrats, You win!! Your knowledge is impressive!!!";
    } else {
        endHTML += "No winners. :("
    }
    Interface.ModalInfo(endHTML);
}

Game.Error = function (err)
{
    Interface.ModalInfo("<span class='error'>Sorry, " + err + "</span>");
}

$(function() {
    /*
    Game.sessid = "lsflg3uml80jkkl2hug65g2k31";
    var testRefresh = {"dtime":12,"users":[{"userid":1,"name":"yak1","sessmd5":"e954d4adc5767a3ff486281983e37478","errors":0,"success":0,"positions":[],"choices":[]},{"userid":2,"name":"yak2","sessmd5":"52276cce98edd8e700553d4199f2e1ce","errors":0,"success":0,"positions":[],"choices":[]}]};
    var startTestData = {"users":[{"userid":1,"name":"yak1","sessmd5":"e954d4adc5767a3ff486281983e37478","errors":0,"success":0,"positions":[{"name":"Belarus","status":0,"id":0},{"name":"Burkina Faso","status":0,"id":1},{"name":"Cuba","status":0,"id":2},{"name":"Germany","status":0,"id":3},{"name":"Greece","status":0,"id":4},{"name":"Malta","status":0,"id":5}],"choices":[]},{"userid":2,"name":"yak2","sessmd5":"52276cce98edd8e700553d4199f2e1ce","errors":0,"success":0,"positions":[{"name":"Burkina Faso","status":0,"id":0},{"name":"China","status":0,"id":1},{"name":"Cuba","status":0,"id":2},{"name":"India","status":0,"id":3},{"name":"Thailand","status":0,"id":4},{"name":"Zimbabwe","status":0,"id":5}],"choices":[]}]};
    var roundEndData = {"users":[{"userid":1,"name":"yak1","sessmd5":"e954d4adc5767a3ff486281983e37478","errors":1,"success":0,"positions":[{"name":"Belarus","status":0,"id":0},{"name":"Burkina Faso","status":0,"id":1},{"name":"Cuba","status":0,"id":2},{"name":"Germany","status":0,"id":3},{"name":"Greece","status":0,"id":4},{"name":"Malta","status":0,"id":5}],"choices":{"placeid":1,"result":0}},{"userid":2,"name":"yak2","sessmd5":"52276cce98edd8e700553d4199f2e1ce","errors":0,"success":0,"positions":[{"name":"Burkina Faso","status":0,"id":0},{"name":"China","status":0,"id":1},{"name":"Cuba","status":0,"id":2},{"name":"India","status":0,"id":3},{"name":"Thailand","status":0,"id":4},{"name":"Zimbabwe","status":0,"id":5}],"choices":[]}]};
    var roundStartData = {"roundid":2,"carddata":"Test data fro country \"*ailand\""};
    Game.Refresh(testRefresh);
    Game.Start(startTestData);
    Game.StartRound(roundStartData);
    Game.EndRound(roundEndData);
    */
});