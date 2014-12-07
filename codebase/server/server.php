#!/usr/bin/php
<?php
$is_console = PHP_SAPI == 'cli' || (!isset($_SERVER['DOCUMENT_ROOT']) && !isset($_SERVER['REQUEST_URI'])); 
if(!$is_console) die("Only cli mode available");
ini_set("display_errors",1);
ini_set("log_errors", 1);
ini_set("error_log", "/tmp/wolframlotto.log");
ignore_user_abort(true);
set_time_limit(0);
ob_start();

error_reporting(E_ALL);


//make fork
$pid = pcntl_fork();
if ($pid == -1)  die("Could not fork\n");
if ($pid) {
	echo "Go to background...\n";
	exit;
	}

require_once(dirname(__FILE__)."/classes/db.php");
require_once(dirname(__FILE__)."/classes/game.php");
require_once(dirname(__FILE__)."/classes/users.php");
require_once(dirname(__FILE__)."/classes/wolframApi.php");

#$host = 'localhost'; //host
#$host = 'wolf.verygame.ru'; //host
$host = 'yak15.koding.io'; //host
$port = '9000'; //port
$null = NULL; //null var
	
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);//Create TCP/IP sream socket
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);//reuseable port
if(!@socket_bind($socket, 0, $port))
	{
	//error_log("Can`t bind socket, exit.");
	//error_log(".");
	exit;
	}
error_log("#Started");
register_shutdown_function(function() {error_log("#Stopped");});

socket_listen($socket);
$clients = array($socket);


$lasttime=time();
$times=array(
	"waitplayers"=>25,
	"aftergamestart"=>2,
	"afterroundend"=>1,
	"afterroundstart"=>10,
	);
$maxrounds=15;
$Game=new Game();
$Game->End();	


while (true) 
	{
	if($Game->lastaction=='game.end')
		{
		if($Game->Users->count>1)
			if(time()-$GLOBALS["lasttime"]>$GLOBALS["times"]["waitplayers"])
				{
				$Game->Start();
				}
		}
	if(($Game->lastaction=='game.start' and time()-$GLOBALS["lasttime"]>$GLOBALS["times"]["aftergamestart"])
		or ($Game->lastaction=='game.round.end' and time()-$GLOBALS["lasttime"]>$GLOBALS["times"]["afterroundend"]))
		{
		$Game->RoundStart();
		}
	if($Game->lastaction=='game.round.start' and time()-$GLOBALS["lasttime"]>$GLOBALS["times"]["afterroundstart"])
		{
		$Game->RoundEnd();
		}
	
	$changed = $clients;
	socket_select($changed, $null, $null, 0, 10);
	
	//check for new socket
	if (in_array($socket, $changed)) 
		{
		$socket_new = socket_accept($socket); //accept new socket
		$clients[] = $socket_new; //add socket to client array
		$header = socket_read($socket_new, 1024); //read data sent by the socket
		perform_handshaking($header, $socket_new, $host, $port); //perform websocket handshake		
		socket_getpeername($socket_new, $ip); //get ip address of connected socket #$ip='127.0.0.1';
		$Game->GetInfo();
		//send_message(array('type'=>'debug', 'message'=>$ip.' connected')); //notify all users about new connection
		
		//make room for new socket
		$found_socket = array_search($socket, $changed);
		unset($changed[$found_socket]);
		}
	
	//loop through all connected sockets
	foreach ($changed as $changed_socket) 
		{			
		//check for any incomming data
		while(socket_recv($changed_socket, $buf, 1024, 0) >= 1)
			{
			$received_text = unmask($buf); //unmask data
			$msg = json_decode($received_text,true); //json decode 
			#$user_name = $msg["name"]; //sender name
			#$user_message = $msg["message"]; //message text
			#$user_color = $msg["color"]; //color
			$cmd = $msg["cmd"]; //cmd
			#$param1 = $msg["param1"]; //color
			
			//prepare data to be sent to client
			if(!empty($cmd))
				{
				if($cmd=='game.setcard')
					{
					$sessid=preg_replace("|[^a-z0-9]|","",$msg["sessid"]);						
					$placeid=(int)$msg["placeid"];
					if($Game->roundid<1) 
						{
						send_one_message(array('type'=>'error', 'message'=>"Game yet not started"));
						}
						elseif($placeid<0 or $placeid>5)
						{
						send_one_message(array('type'=>'error', 'message'=>"Invalid data"));
						}
						else
						{
						$Game->setCard($sessid,$placeid);
						send_one_message(array('type'=>'debug', 'message'=>"Ok"));
						}
					}
				if($cmd=='game.connect')
					{
					//check if game already in progress
					if($Game->lastaction!='game.end')
						{
						send_one_message(array('type'=>'error', 'message'=>"Game already started"));
						}
						else //all good
						{
						$serverid=(int)$msg["serverid"];
						$name=AddSlashes($msg["name"]);
						$sessid=preg_replace("|[^a-z0-9]|","",$msg["sessid"]);						
						$Game->AttachUser($serverid,$name,$sessid);
						}
					}
					
					
				if($cmd=='chat.send')
					{
					send_message(array('type'=>'usermsg', 'name'=>$user_name, 'message'=>$user_message, 'color'=>$user_color)); //send data
					}
				}
			break 2; //exist this loop
			}
		
		$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
		if ($buf === false) // check disconnected client
			{ 
			// remove client for $clients array
			$found_socket = array_search($changed_socket, $clients);
			socket_getpeername($changed_socket, $ip);#$ip='127.0.0.1';
			unset($clients[$found_socket]);

			//notify all users about disconnected connection
			send_message(array('type'=>'debug', 'message'=>$ip.' disconnected'));
			}
		}
	}

socket_close($sock);




								
function send_message($array)
{
global $clients;
$msg=mask(json_encode($array));
foreach($clients as $changed_socket)
	{
	@socket_write($changed_socket,$msg,strlen($msg));
	}
return true;
}

function send_one_message($array)
{
global $changed_socket;
$msg=mask(json_encode($array));
@socket_write($changed_socket,$msg,strlen($msg));
return true;
}


//Unmask incoming framed message
function unmask($text) {
	$length = ord($text[1]) & 127;
	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}
	elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}
	else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}

//Encode message for transfer to client.
function mask($text)
{
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);
	
	if($length <= 125)
		$header = pack('CC', $b1, $length);
	elseif($length > 125 && $length < 65536)
		$header = pack('CCn', $b1, 126, $length);
	elseif($length >= 65536)
		$header = pack('CCNN', $b1, 127, $length);
	return $header.$text;
}

//handshake new client.
function perform_handshaking($receved_header,$client_conn, $host, $port)
{
	$headers = array();
	$lines = preg_split("/\r\n/", $receved_header);
	foreach($lines as $line)
	{
		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
		{
			$headers[$matches[1]] = $matches[2];
		}
	}

	$secKey = $headers['Sec-WebSocket-Key'];
	$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	//hand shaking header
	$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
	"Upgrade: websocket\r\n" .
	"Connection: Upgrade\r\n" .
	"WebSocket-Origin: $host\r\n" .
	"WebSocket-Location: ws://$host:$port/\r\n".
	"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
	socket_write($client_conn,$upgrade,strlen($upgrade));
}



ob_end_flush();