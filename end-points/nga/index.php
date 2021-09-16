<?php

$results = array();

if (!isset($_GET["limit"]))
	{$_GET["limit"] = 10;}
	
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
		{$esResults = getObjectImageInfo ($_GET["search"], intval($_GET["limit"]), intval($_GET["page"]));}
	else
		{$esResults = getObjectManifests ($_GET["search"], intval($_GET["limit"]), intval($_GET["page"]));}
	
	$out = array(		
		"limit" => $esResults[1],
		"from" => $esResults[2], 
		"limited" => $esResults[3],
		"total" => $esResults[4],
		"search" => $_GET["search"],
		"what" => $_GET["what"],
		"results" => $esResults[0],
		"comment" => $esResults[5]//,
		//"missed" => $esResults[6]
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
		"comment" => "This API has been setup to return lists of IIIF info.json URLs based on a simple keyword search passed via the URL (IIIF Manifests are not available in this example). Available variable include: \"search\" (the keyword of interest), \"limit\" (a simple limit on the total number of manifest to be returned, up to a maximum of 100, default 25), \"from\" (an offset value to facilitate pagination of results in conjunction with a defined \"limit\" value, default = 0) and \"what\" (determining what should be returned, either IIIF manifests or info.json files, valid options are normally 'manifests' or 'info', but this example only works for 'info', default = 'info'."//,
		//"missed" => 0
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
	
function getObjectManifests ($str, $limit=50, $page=0)
	{	
	$es = 'https://www.nga.gov/collection-search-result/jcr:content/parmain'.
		'/facetcomponent/parList/collectionsearchresu.pageSize__'.$limit.
		'.pageNumber__'.$page.'.json?_=1631290784856&keyword=';
		
	$limited = false;
	$mans = array();
	
	$str = urlencode($str);
	$uri = $es.$str;
	$total = false;
	$missed = false;	
	
	$arr = getsslJSONfile($uri);

	if ($arr["totalcount"] > $limit)
		{$limited = true;}
	
	$total = $arr["totalcount"];
	
	foreach	($arr["results"] as $k1 => $a)
		{
		// The manifest URLs suplied by the API do not have CORs setup, but by shortening them as shown CORs is fine.
		if (isset($a["iiifManifestURL"]) and preg_match ("/^(.+[\/])content[\/]ngaweb[\/](.+)$/", $a["iiifManifestURL"], $m)) {
			$mans[] = $m[1].$m[2];}
		else if (isset($a["iiifManifestURL"])) {
			$mans[] = $a["iiifManifestURL"];}
		else
			{$missed++;}
		}
	
	$from = intval(($page - 1) * $limit);	
	$comment = "IIIF manifests returned from a keyword Search for <b>$str</b>, of the entire object records in the National Gallery of Art's collection database for any word. (Not all the fields searched will be visible in your results).";
	
	return (array($mans, $limit, $from, $limited, $total, $comment, $missed));
	}
	
	
function getObjectImageInfo ($str, $limit=25, $page=0)
	{	
	if (preg_match ("/^[a-z0-9]{8}[-][a-z0-9]{4}[-][a-z0-9]{4}[-][a-z0-9]{4}[-][a-z0-9]{12}$/", $str, $m))
		{
		$arr = array(
			"totalcount" => 1,
			"results" => array(
				array(
					"imagepath" => "https://media.nga.gov/iiif/$str/full/blank/default.jpg"
					)));
		}
	else
		{
		$q = 'https://www.nga.gov/collection-search-result/jcr:content/parmain'.
			'/facetcomponent/parList/collectionsearchresu.pageSize__'.$limit.
			'.pageNumber__'.$page.'.json?_=1631290784856&keyword=';
	
		$limited = false;
	
		$str = urlencode($str);
		$uri = $q.$str;
		$arr = getsslJSONfile($uri);
		}
	
	if ($arr["totalcount"] > $limit)
		{$limited = true;}
	
	$total = $arr["totalcount"];
	$missed = 0;
	$list = array();
	
	foreach	($arr["results"] as $k => $a)
		{
		if ($a["imagepath"] and (preg_match ("/^(.+[\/])full[\/].+default.jpg$/", $a["imagepath"], $m)))
			{$list[] = $m[1]."info.json";}
		else
			{$missed++;}
		}
	
	$from = intval(($page - 1) * $limit);	
	$comment = "IIIF info.json files returned from a keyword Search for <b>$str</b>, of the entire object records in the National Gallery of Art's collection database for any word. (Not all the fields searched will be visible in your results).";
	
	return (array($list, $limit, $from, $limited, $total, $comment, $missed));
	}

?>
