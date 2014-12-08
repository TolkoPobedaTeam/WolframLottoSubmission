<?php
class WolframApi
{

private function parseStr($s,$title='')
{
$items=preg_split("/\|/",$s,2,PREG_SPLIT_NO_EMPTY);
$data=array();
if(count($items)>1) 
    {
    $data["name"]=trim($items[0]);
    if(preg_match_all("|\(([^()]+)\)|",$items[1],$matches))
		{
		$data["value"]=trim(preg_replace("|\([^()]+\)|","",$items[1]));
		$data["comments"]=$matches[1];
		}
    }
    elseif(!empty($title))
    {
     $data["name"]=$title;
     $data["value"]=$s;
    }
return $data;
}

private function getLineByNum($s,$line=0)
{
$items=preg_split("|\n|",$s);
return $items[$line];
}



public function getcached($word) //!!!added cache of requests because api of wolfram work very slowly
{
$path=__DIR__."/../cache/wl_$word.cache";
if(!file_exists($path)) 
	{
	file_put_contents($path,json_encode($this->get($word,false)));
	}
return json_decode(file_get_contents($path),true);
}


public function get($word,$cached=true)
{
if($cached) return $this->getcached($word);
error_log("Api->get($word)");
foreach(array(1,2,3) as $iteration)
	{
	$code=file_get_contents("http://www.wolframalpha.com/input/autocomplete.jsp?qr=0&i=$word");
	$quick=json_decode($code,true);
	$mainimg=$quick["results"][0]['summaryBoxPath'];
	if(!empty($mainimg)) break;
	}


//echo "<img src='http://www.wolframcdn.com/summaryboxes/$img/img/mainimage.gif' />";
foreach(array(1,2,3) as $iteration)
	{
	$code=file_get_contents("http://api.wolframalpha.com/v2/query?input=$word&appid=5688U4-84L7K4AK23");
	$xml = simplexml_load_string($code);
	$json = json_encode($xml);
	$data = json_decode($json,TRUE);
	$struct=array();
	foreach($data["pod"] as $k=>$pod)
		{
		$id=strtolower($pod["@attributes"]["id"]);
		$plain=$pod["subpod"]["plaintext"];
		$img=$pod["subpod"]["img"]["@attributes"]["src"];
		$imgw=$pod["subpod"]["img"]["@attributes"]["width"];
		$imgh=$pod["subpod"]["img"]["@attributes"]["height"];
		$struct[$id]=array("plain"=>$plain,"img"=>$img);
		}
	if(!empty($struct["demographicproperties:countrydata"]["plain"])
		and (!empty($struct["capitalcity:countrydata"]["plain"]))
		and (!empty($struct["geographicproperties:countrydata"]["plain"]))) break;
	}
$data=array();
$data[]=$this->parseStr($this->getLineByNum($struct["demographicproperties:countrydata"]["plain"]));
$data[]=$this->parseStr($this->getLineByNum($struct["capitalcity:countrydata"]["plain"]),"Capital");
$data[]=$this->parseStr($this->getLineByNum($struct["demographicproperties:countrydata"]["plain"],2));
$data[]=$this->parseStr($this->getLineByNum($struct["geographicproperties:countrydata"]["plain"]));
$data[]=$this->parseStr($this->getLineByNum($struct["demographicproperties:countrydata"]["plain"],3));
$full=array("data"=>$data);
if(!empty($mainimg)) $full["img"]="http://www.wolframcdn.com/summaryboxes/$mainimg/img/mainimage.gif";
error_log("apifinish");
return $full;
}

}
