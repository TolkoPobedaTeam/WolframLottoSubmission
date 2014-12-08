Interface = {};

Interface.Init = function ()
{
    $( ".startGame" ).click(function () {
        $(".login").addClass("hide");
        Game.Connect();
    });
    Interface.ModalInfo("Please wait, check session...");
}

Interface.DraggableCard = function ()
{
    var startPosition = $( "#draggable" ).offset();
    var width = $( "#draggable" ).width();
    var height = $( "#draggable" ).height();
    
    $( "#draggable" ).draggable({
		drag: function( event, ui ) {
		    var dragX = ui.position.left+width/2+parseInt($("#draggable").css("margin-left"));
		    var dragY = ui.position.top+height/2;
		    var closest = Infinity;
		    var closestBlock;
		    
			$(".block").each(function( index ) {
				var position = $( this ).offset();

				var blockX = position.left+width/2;
				var blockY = position.top+height/2;
				var sqr = Math.pow(blockX-dragX,2)+Math.pow(blockY-dragY,2);

				if (closest>sqr) {
				    closest=sqr;
				    closestBlock=$(this);
				}
			});
			
			$(".block").removeClass("active");
			$( "#draggable .title" ).text("");
			
			// console.log([width,height,closest]);
			
			// console.log(closest);
			if (closest<150*150) {
			    closestBlock.addClass("active");
			    // $( "#draggable" ).offset($(".block.active").offset());
			    $( "#draggable .title" ).text($(".block.active i").text());
			} else {
			    $( "#draggable" ).offset(startPosition);
			}
		},
		stop: function( event, ui ) {
			if ($(".block.active").length) {
				$( "#draggable" ).offset($(".block.active").offset());
				$( "#draggable .title" ).text($(".block.active i").text());
				$( "#draggable" ).draggable({disable:true});
				Game.Choice($(".block.active").attr("data-placeid"));
			} else {
				$( "#draggable" ).offset(startPosition);
				$( "#draggable .title" ).text("");
			}
		}
	});
}

Interface.ModalInfo = function (msg)
{
    $(".modal.info").removeClass("hide");
    $(".modal.info h4").html(msg);
}

Interface.ModalInfoHide = function ()
{
    $(".modal.info").addClass("hide");
}


Interface.LogIn = function ()
{
    $(".modal.login").removeClass("hide");
}

Interface.ResetActive = function ()
{
    $(".block").removeClass("active");
}

Interface.Info = function (html)
{
    $('#info').show().html(html);
}

Interface.InfoHide = function (html)
{
    if (html) {
        if ($('#info').html()==html) $('#info').hide();
    } else {
        $('#info').hide();
    }
}