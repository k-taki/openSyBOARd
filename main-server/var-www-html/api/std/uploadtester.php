<?php
	// error?
	error_reporting(E_ERROR);
	ini_set( 'display_errors', 1 );
	header("Content-Type: text/plain");
	
	
	print_r($_POST);
	
	$escunav = (int)$_POST['LOCATION_UNAV'];
	echo "unav = ".(bool)$escunav ;
	
	
	$ch = curl_init();
	$uri = "http://maps.google.com/maps/api/geocode/json?latlng=35.928657799999996,139.4467732&sensor=false";
	curl_setopt($ch, CURLOPT_URL, $uri);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$res = curl_exec($ch);
	curl_close($ch);
	
	$placeinfo = JSON_decode($res, true);
	echo $placeinfo['results'][0]['formatted_address'];
	//print_r($placeinfo);
	
?>