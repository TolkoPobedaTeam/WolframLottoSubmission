<?php

class Users
{
//private $Db;
public $data;
//public $users;
//public $placemap;
public $count=0;
//public $serverid=1;

public function __construct()
{
//$this->Db=new Db();
$this->data["users"]=array();
$this->data["placemap"]=array();
}



public function Get()
{
$this->count=count($this->data["users"]);
return $this->data;
}

public function Add($name,$sessid)
{
$this->Get();
$users=$this->data["users"];
$placemap=$this->data["placemap"];
$userid=count($users)+1;
if(empty($name)) $name="Player$userid";
foreach($users as $user)
	{
	if($user["sessid"]!=$sessid) continue;
	return false;//user already exists
	}
$users[]=array("userid"=>$userid,"name"=>$name,"sessid"=>$sessid,"errors"=>0,"success"=>0);
$this->count=count($users);
$this->data=array("users"=>$users,"placemap"=>$placemap);
//$this->Db->writeData($this->serverid,"users",$this->data);
}

public function Save()
{
//$this->Db->writeData($this->serverid,"users",$this->data);
$this->count=count($users);
}


public function getData($choices=array())
{
$udata=array();
foreach($this->data["users"] as $user)
	{
	if(empty($user["positions"])) $user["positions"]=array();
	$choi=array();
	if(!empty($choices[$user["userid"]])) $choi=$choices[$user["userid"]];
	$udata[]=array(
		"userid"=>$user["userid"],
		"name"=>$user["name"],
		"sessmd5"=>md5($user["sessid"]),
		"errors"=>$user["errors"],
		"success"=>$user["success"],
		"positions"=>$user["positions"],
		"choices"=>$choi,
		);
	}
return $udata;
}


public function getUserKeyBySessid($sessid)
{
foreach($this->data["users"] as $key=>$user)
	{
	if($sessid==$user["sessid"]) return $key;
	}
return false;
}


public function getMap()
{
$map=array();
foreach($this->data["users"] as $key=>$user)
	{
	$map[$user["userid"]]=$key;
	}
return $map;
}

}