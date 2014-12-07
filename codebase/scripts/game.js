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
        $('#onePlayer').hide();
    } else {
        $('#onePlayer').show();
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
    $("#draggable .cont").html(data.carddata);
    Interface.DraggableCard();
    
    $("#roundCounter").fadeIn();
    $("#roundCounter b").html(data.roundid);
}

Game.EndRound = function (data) {
    for (var u=0; u<data.users.length; u++) {
        if (data.users[u].sessmd5 == $.md5(Game.sessid)) {
            if (data.users[u].choices) {
                if (data.users[u].choices.result == 1) {
                    $("#draggable")
                        .attr("id", "card"+data.users[u].choices.placeid)
                        .draggable({disabled: true});
                }
            }
        }
    }
    
    $("#draggable").attr("id", "").fadeOut(500, function () {$(this).remove()});
    
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