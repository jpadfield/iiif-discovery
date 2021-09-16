<?php

$results = array();

if (!isset($_GET["limit"]))
	{$_GET["limit"] = 100;}
	
if ($_GET["limit"] > 100)
	{$_GET["limit"] = 100;}
	
if (!isset($_GET["from"]))
	{$_GET["from"] = 0;}
	
$_GET["page"] = floor($_GET["from"]/$_GET["limit"]) + 1;
	
if (!isset($_GET["search"]))
	{$_GET["search"] = false;}
	
if (!isset($_GET["what"]))
	{$_GET["what"] = "manifests";}

if ($_GET["search"])
	{
	if ($_GET["what"] == "info")
		{$esResults = getESObjectImageInfo ($_GET["search"], intval($_GET["limit"]), intval($_GET["page"]));}
	else
		{$esResults = getESObjectManifests ($_GET["search"], intval($_GET["limit"]), intval($_GET["page"]));}

	$out = array(		
		"limit" => $esResults[1],
		"from" => $esResults[2], 
		"limited" => $esResults[3],
		"total" => $esResults[4],
		"search" => $_GET["search"],
		"what" => $_GET["what"],
		"results" => $esResults[0],
		"comment" => $esResults[5],
		"missed" => $esResults[6]
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
		"comment" => "This API has been setup to return lists of IIIF manifests or info.json URLs based on a simple keyword search passed via the URL. Available variable include: \"search\" (the keyword of interest), \"limit\" (a simple limit on the total number of manifest to be returned, up to a maximum of 100, default 25), \"from\" (an offset value to facilitate pagination of results in conjunction with a defined \"limit\" value, default = 0) and \"what\" (determining what should be returned, either IIIF manifests or info.json files, valid options are 'manifests' or 'info', default = 'manifests'.",
		"missed" => 0
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
	
function getESObjectManifests ($str, $limit=50, $page=0)
	{	
	$es = 'https://api.vam.ac.uk/v2/objects/search?page_size='.$limit.'&page='.$page.'&images_exist=1&q=';
		
	$limited = false;
	
	$str = urlencode($str);
	$uri = $es.$str;
	$arr = getsslJSONfile($uri);
	 
	//if (!isset($arr["info"]["pages"]))
//		{$arr["info"] = array(
		//"pages" => 0);}
//	
//	if ($arr["info"]["pages"] > $limit)
		//{$limited = true;}
	
	//$total = $arr["info"]["pages"];
	
		
	if ($arr["info"]["record_count"] > $limit)
		{$limited = true;}	
	
	$total = $arr["info"]["record_count"];
	$missed = 0;
	$list = array();
	$mans = array();
	
	foreach	($arr["records"] as $k => $a)
		{
		if ($a["_images"]["_iiif_presentation_url"])
			{$mans[] = $a["_images"]["_iiif_presentation_url"];}
		else
			{$missed++;}
		}
	
	//$comment = "a query of the public Victoria and Albert API - with a full-text";	
	$from = intval(($page - 1) * $limit);	
	$comment = "IIIF manifests returned from a full-text object search, for <b>$str</b> of the public Victoria and Albert API.";
	
	return (array($mans, $limit, $from, $limited, $total, $comment, $missed));
	}
	
	
function getESObjectImageInfo ($str, $limit=25, $page=0)
	{	
	$es = 'https://api.vam.ac.uk/v2/objects/search?page_size='.$limit.'&page='.$page.'&images_exist=1&q=';
	
	$limited = false;
	
	$str = urlencode($str);
	$uri = $es.$str;
	$arr = getsslJSONfile($uri);

//$json = json_encode($arr);
//header('Content-Type: application/json');
//header("Access-Control-Allow-Origin: *");
//echo "[".json_encode(array("page"=>$page)).",";
//echo $json;
//echo "]";
//exit;	
		 
	//if (!isset($arr["info"]["pages"]))
//		{$arr["info"] = array(
		//"pages" => 0);}
	
	if ($arr["info"]["record_count"] > $limit)
		{$limited = true;}	
	
	$total = $arr["info"]["record_count"];
	$missed = 0;
	$list = array();
	
	foreach	($arr["records"] as $k => $a)
		{
		if ($a["_images"]["_iiif_image_base_url"])
			{$list[] = $a["_images"]["_iiif_image_base_url"]."info.json";}
		else
			{$missed++;}
		}
	
	$from = intval(($page - 1) * $limit);	
	$comment = "IIIF info.json files returned from a full-text object search, for <b>$str</b> of the public Victoria and Albert API.";
	
	return (array($list, $limit, $from, $limited, $total, $comment, $missed));
	}

?>
