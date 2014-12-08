<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Wolfram Lotto</title>
  <script src="http://code.jquery.com/jquery-1.10.2.js"></script>
  <script src="http://code.jquery.com/ui/1.11.2/jquery-ui.js"></script>
  
  <link href="css/lotto.css" rel=stylesheet type=text/css>
  <link href="css/modal.css" rel=stylesheet type=text/css>
  <link href="css/theme1.css" rel=stylesheet type=text/css>
  
  <script src="scripts/jquery.md5.js"></script>
  <script src="scripts/interface.js"></script>
  <script src="scripts/game.js"></script>

  <script>
    $(function() {
        <?php session_start(); ?>
        Game.Init({
            sessid : '<?php echo session_id();?>',
            wsURL : 'ws://yak15.koding.io:9000/'
        });
    });
  </script>
  
</head>
<body>
 
    <center id="startGameCounter">
        Game start at <b>-.-</b>
    </center>
 
    <center id="roundCounter">
        <b>0</b><br>ROUND
    </center>
    
    <center id="info"></center>
 
    <div class="users">
        <h4 class="help">Players:</h4>
        <ul></ul>
    </div>
 
    <div class="login modal hide">
        <label class="modal__bg startGame" for="modal-1"></label>
        <div class="modal__inner">
            <h4>Please, enter your name:</h4>
            <input type="text" id="username">
            <br><br>
            <label class="btn startGame">Start <b>Wolfram Lotto</b> Game!</label>
        </div>
    </div>
    
    <div class="info modal hide">
        <label class="modal__bg" for="modal-1"></label>
        <div class="modal__inner">
            <h4></h4>
        </div>
    </div>
 
    <div class="card" id="card">
        <div class="wrapper">
            <div class="title">DRAG ME!</div>
            <div class="cont">
                <img src="img/card-wrap-bg.png">
                <ul class="wasb-nobullet">
                	<li class="population">population: <span class="wasb-propval">44.5 million people</span></li>
                	<li class="life_expectancy">life expectancy: <span class="wasb-propval">68.5 years</span></li>
                	<li class="capital">capital: <span class="wasb-propval">Kyiv</span></li>
                	<li class="total_area">area: <span class="wasb-propval">233 032 square miles</span></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="loto">
        <!--<div class="block"></div>
        <div class="block"></div>
        <div class="block"></div>
        -->
    </div>
 
</body>
</html>