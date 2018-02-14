#!/usr/bin/php
<?php
/**
 * Yelp Fusion API call code
 * Code example found at the Yelp API github
 * This code can be found at 
 */
 
$CLIENT_ID = "fDvtSMXgR4RE5G3hsa1T_Q";
$CLIENT_SECRET = "Yr7uOkVs6UPUAXgDcJYdM58kUIdKP7KTLbiroLlu9mnKXiiTy5QvcffxBPjOtsAh";
// API constants, you shouldn't have to change these via Yelp Fusion API
$API_HOST = "https://api.yelp.com";
$SEARCH_PATH = "/v3/businesses/search";
$BUSINESS_PATH = "/v3/businesses/";  // Business ID will come after slash.
$TOKEN_PATH = "/oauth2/token";
$GRANT_TYPE = "client_credentials";

//Globals
$location = NULL;
$term = NULL;
$stringBuilder = "";
$number = 10;
$offset = 0;
$price = 2;
$sort = "best_match";
$distance = 15;
$open = false;


function getQuery(){
	global $location, $term, $number,
	$offset, $price, $distance, $open;
	$location = $_GET['address'];
	$term= $_GET['term'];
	$number = $_GET['num'];
	$offset = $_GET['offset'];
	$price = $_GET['price'];
	$sort = $_GET['sort'];
	$distance = $_GET['distance'];
	$open = $_GET['open'];
	
	$distance = $distance*1609;
	if($distance > 40000){
		$distance = 39999;
	}
	//Default values
	/*
if($distance != "Distance"){
	$distance = substr($distance, 0, strpos($distance, " "));
}else{
	$distance = 10;
}
	
if($open == NULL){
	$open = false;
}

if(substr_count($price, '$') == 0){
	$price = 2;
}else{
	$price = substr_count($price, '$');
}
	
if($sort == "Best Match"){
	$sort = "best_match";
}elseif($sort == "Distance"){
	$sort ="distance";
}elseif($sort == "Rating"){
	$sort = "rating";
}else{
	$sort = "best_match";
}
	*/
	$offset = $offset - $number;
}

function debug(){
	print "Number ".$number
	."\nTerm ".$term
	."\nlocation ".$location
	."\nnumber ".$number
	."\ndistance ".$distance
	."\nsort by ".$sort
	."\nprice ".$price
	."\noffset ".$offset;
}
/**
 * Given a bearer token, send a GET request to the API., used from Yelp API Sample
 * 
 * @return   OAuth bearer token, obtained using client_id and client_secret.
 */
function obtain_bearer_token() {
    try {
        # Using the built-in cURL library for easiest installation.
        # Extension library HttpRequest would also work here.
        $curl = curl_init();
        if (FALSE === $curl)
            throw new Exception('Failed to initialize');
        $postfields = "client_id=" . $GLOBALS['CLIENT_ID'] .
            "&client_secret=" . $GLOBALS['CLIENT_SECRET'] .
            "&grant_type=" . $GLOBALS['GRANT_TYPE'];
        curl_setopt_array($curl, array(
            CURLOPT_URL => $GLOBALS['API_HOST'] . $GLOBALS['TOKEN_PATH'],
            CURLOPT_RETURNTRANSFER => true,  // Capture response.
            CURLOPT_ENCODING => "",  // Accept gzip/deflate/whatever.
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded",
            ),
        ));
        $response = curl_exec($curl);
        if (FALSE === $response)
            throw new Exception(curl_error($curl), curl_errno($curl));
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (200 != $http_status)
            throw new Exception($response, $http_status);
        curl_close($curl);
    } catch(Exception $e) {
        trigger_error(sprintf(
            'Curl failed with error #%d: %s',
            $e->getCode(), $e->getMessage()),
            E_USER_ERROR);
    }
    $body = json_decode($response);
    $bearer_token = $body->access_token;
    return $bearer_token;
}
/** 
 * Makes a request to the Yelp API and returns the response
 */
function request($bearer_token, $host, $path, $url_params = array()) {
    // Send Yelp API Call
    try {
        $curl = curl_init();
        if (FALSE === $curl)
            throw new Exception('Failed to initialize');
        $url = $host . $path . "?" . http_build_query($url_params);
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,  // Capture response.
            CURLOPT_ENCODING => "",  // Accept gzip/deflate/whatever.
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $bearer_token,
                "cache-control: no-cache",
            ),
        ));
        $response = curl_exec($curl);
        if (FALSE === $response)
            throw new Exception(curl_error($curl), curl_errno($curl));
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (200 != $http_status)
            throw new Exception($response, $http_status);
        curl_close($curl);
    } catch(Exception $e) {
		//We dont want the user seeing this
		/*
        trigger_error(sprintf(
            'Curl failed with error #%d: %s',
            $e->getCode(), $e->getMessage()),
            E_USER_ERROR);
			*/
    }
    return $response;
}
/**
 * Query the Search API by a search term and location 
 * 
 * @param    $bearer_token   API bearer token from obtain_bearer_token
 * @param    $term        The search term passed to the API 
 * @param    $location    The search location passed to the API 
 * @return   The JSON response from the request 
 */
function search($bearer_token, $term, $location, $number, $distance, $offset, $sort, $price) {
    $url_params = array();
    
	//Add the queries
    $url_params['term'] = $term;
    $url_params['location'] = $location;
    $url_params['limit'] = $number;
	$url_params['radius'] = $distance;
	$url_params['offset'] = $offset;
	$url_params['sort_by'] = $sort;
	$url_params['price'] = $price;
    
    return request($bearer_token, $GLOBALS['API_HOST'], $GLOBALS['SEARCH_PATH'], $url_params);
}
/**
 * Query the Business API by business_id, used from Yelp API sample
 * 
 * @param    $bearer_token   API bearer token from obtain_bearer_token
 * @param    $business_id    The ID of the business to query
 * @return   The JSON response from the request 
 */
function get_business($bearer_token, $business_id) {
    $business_path = $GLOBALS['BUSINESS_PATH'] . urlencode($business_id);
    
    return request($bearer_token, $GLOBALS['API_HOST'], $business_path);
}
/**
 * Queries the API
 */
function query_api($bearer_token, $term, $location, $number, $distance, $offset, $sort, $price) {     
    $bearer_token = obtain_bearer_token();
    $response = json_decode(search($bearer_token, $term, $location, $number, $distance, $offset, $sort, $price));
	$count = count($response->businesses);
	for($i = 0; $i < $count; $i++){
	$stringBuilder = $stringBuilder
	.'<article class="thumb">
							<a href="'.$response->businesses[$i]->url.'" class="image"><img style="width: 100%; height: 100%;" src="'.$response->businesses[$i]->image_url.'" alt="" /></a>
							<h2>'. $response->businesses[$i]->name .'</h2>
							<p>'. $response->businesses[$i]->categories[0]->title .'</p>
	</article>';
	}
	//Checks to make sure there are businesses that meet the criteria
	if($stringBuilder == ""){
		$stringBuilder = '<div class="misc" style="text-align:center;font-weight:bold;"><h2>No more results to show</h2></div>';
	}
	print $stringBuilder;
}
//Break up the GET request
getQuery();
//Checks
//debug();
//Query the yelp api
query_api($bearer_token, $term, $location, $number, $distance, $offset, $sort, $price);
?>