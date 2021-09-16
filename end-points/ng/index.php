<?php

$results = array();

if (!isset($_GET["limit"]))
	{$_GET["limit"] = 25;}
	
if ($_GET["limit"] > 100)
	{$_GET["limit"] = 100;}
	
if (!isset($_GET["from"]))
	{$_GET["from"] = 0;}
	
if (!isset($_GET["search"]))
	{$_GET["search"] = false;}
	
if (!isset($_GET["what"]))
	{$_GET["what"] = "manifests";}

if ($_GET["search"])
	{
	if ($_GET["what"] == "info")
		{$esResults = getESObjectImageInfo ($_GET["search"], intval($_GET["limit"]), intval($_GET["from"]));}
	else
		{$esResults = getESObjectManifests ($_GET["search"], intval($_GET["limit"]), intval($_GET["from"]));}
	
	$out = array(		
		"limit" => $esResults[1],
		"from" => $esResults[2], 
		"limited" => $esResults[3],
		"total" => $esResults[4],
		"search" => $_GET["search"],
		"what" => $_GET["what"],
		"results" => $esResults[0],
		"comment" => $esResults[5]
		);
	}
else
	{
	$out = array(		
		"limit" => 25,
		"from" => 0, 
		"limited" => false,
		"total" => false,
		"search" => "required-search-term",
		"what" => "manifests",
		"results" => array(),
		"comment" => "This API has been setup to return lists of IIIF manifests or info.json URLs based on a simple keyword search passed via the URL. Available variable include: \"search\" (the keyword of interest), \"limit\" (a simple limit on the total number of manifest to be returned, up to a maximum of 100, default 25), \"from\" (an offset value to facilitate pagination of results in conjunction with a defined \"limit\" value, default = 0) and \"what\" (determining what should be returned, either IIIF manifests or info.json files, valid options are 'manifests' or 'info', default = 'manifests'."
		);
	}

$json = json_encode($out);
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
echo $json;
exit;	

function getsslJSONfile ($uri, $decode=true)
	{
	$arrContextOptions=array(
    "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,),);  

	$response = file_get_contents($uri, false, stream_context_create($arrContextOptions));
	
	if ($decode)
		{return (json_decode($response, true));}
	else
		{return ($response);}
	}
	
function getESObjectManifests ($str, $limit=25, $from=0)
	{	
	$es = 'https://data.ng-london.org.uk/elasticsearch/_search?'.
		'_source=false&default_operator=AND&size='.$limit.'&from='.$from.
		'&q=type.base:object+(_exists_:multimedia)+';
	
	$limited = false;
	
	$str = urlencode($str);
	$uri = $es.$str;
	$arr = getsslJSONfile($uri);
		 
	if (!isset($arr["hits"]["total"]))
		{$arr["hits"] = array(
		"total" => 0,
		"hits" => array());}
	
	if ($arr["hits"]["total"] > $limit)
		{$limited = true;}
	
	$total = $arr["hits"]["total"];
	
	$murl = "https://data.ng-london.org.uk/";
	$mans = array();
	
	foreach	($arr["hits"]["hits"] as $k => $a)
		{$mans[] = $murl.$a["_id"].'.iiif';}
	
	$comment = "IIIF manifests returned from a full-text object search, for <b>$str</b> of the public National Gallery instance of ElasticSearch.";
	
	return (array($mans, $limit, $from, $limited, $total, $comment));
	}
	
	
function getESObjectImageInfo ($str, $limit=25, $from=0)
	{	
	$es = 'https://data.ng-london.org.uk/elasticsearch/_search?'.
		'_source=multimedia.admin.uid&default_operator=AND&size='.$limit.'&from='.$from.
		'&q=type.base:object+(_exists_:multimedia)+';
	
	$limited = false;
	
	$str = urlencode($str);
	$uri = $es.$str;
	$arr = getsslJSONfile($uri);
		 
	if (!isset($arr["hits"]["total"]))
		{$arr["hits"] = array(
		"total" => 0,
		"hits" => array());}
	
	if ($arr["hits"]["total"] > $limit)
		{$limited = true;}
	
	$total = $arr["hits"]["total"];
	
	$iurl = "https://data.ng-london.org.uk/iiif/image/";
	$list = array();
	
	foreach	($arr["hits"]["hits"] as $k => $a)
		{
		if(isset($a["_source"]["multimedia"]))
			{
			foreach	($a["_source"]["multimedia"] as $j => $b)
				{$list[] = $iurl.$b["admin"]["uid"].'/info.json';}}
		}
	
	$comment = "IIIF info.json files returned from a full-text object search, for <b>$str</b> of the public National Gallery instance of ElasticSearch.";
	
	return (array($list, $limit, $from, $limited, $total, $comment));
	}

?>
