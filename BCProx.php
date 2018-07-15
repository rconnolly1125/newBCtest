<?php
/**
 * proxy for Brightcove RESTful APIs
 * gets an access token, makes the request, and returns the response
 * Accessing:
 *         (note you should *always* access the proxy via HTTPS)
 *     Method: POST
 *     request body (accessed via php://input) is a JSON object with the following properties
 *
 * {string} url - the URL for the API request
 * {string} [requestType=GET] - HTTP method for the request
 * {string} [requestBody] - JSON data to be sent with write requests
 * {string} [client_id] - OAuth2 client id with sufficient permissions for the request
 * {string} [client_secret] - OAuth2 client secret with sufficient permissions for the request
 * {string} [account_id] - Brightcove account id
 *
 * if client_id, client_secret, or account_id are not included in the request, default values will be used
 *
 * @returns {string} $response - JSON response received from the API
 */

// security checks
// if you want to do some basic security checks, such as checking the origin of the
// the request against some white list, this is the place to do it
// CORS enablement and other headers
header("Access-Control-Allow-Origin: *");
header("Content-type: application/json");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection");

// default account values
// if you work on one Brightcove account, put in the values below
// if you do not provide defaults, the account id, client id, and client secret must
// be sent in the request body for each request
$default_account_id    = '5809027229001';
$default_client_id     = 'ac5aa2e6-be7a-4061-adba-765a3ea241fc';
$default_client_secret = 'LVwNiVui6adqbfWypN3evcRSqsU6BAyRm13AeLa5t83xoqTJ8EONCRBmO1UVYpOc4K5MuAihQ40YJ4kYOTSEng';

// get request body
$requestData = json_decode(file_get_contents('php://input'));

// set up access token request
if ($requestData->client_id) {
    $client_id = $requestData->client_id;
} else {
    // default to the id for all permissions for most BCLS accounts
    $client_id = $default_client_id;
}
if ($requestData->client_secret) {
    $client_secret = $requestData->client_secret;
} else {
    // default to the secret for all permissions for most BCLS accounts
    $client_secret = $default_client_secret;
}
if ($requestData->ccount_id) {
    $account_id = $requestData->account_id;
} else {
    // default to Doc Samples account; change to default to BrightcoveLearning or another account
    $account_id = $default_account_id;
}

$auth_string = "{$client_id}:{$client_secret}";
$request     = "https://oauth.brightcove.com/v4/access_token?grant_type=client_credentials";
$ch          = curl_init($request);
curl_setopt_array($ch, array(
        CURLOPT_POST           => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_SSL_VERIFYPEER => FALSE,
        CURLOPT_USERPWD        => $auth_string,
        CURLOPT_HTTPHEADER     => array(
            'Content-type: application/x-www-form-urlencoded',
        ),
    ));
$response = curl_exec($ch);
curl_close($ch);

// Check for errors
if ($response === FALSE) {
    die(curl_error($ch));
}

// Decode the response
$responseData = json_decode($response, TRUE);
$access_token = $responseData["access_token"];

// get request type or default to GET
if ($requestData->requestType) {
    $method = $requestData->requestType;
} else {
    $method = "GET";
}

// more security checks
// optional: you might want to check the URL for the API request here
// and make sure it is to an approved API
// and that there is no suspicious code appended to the URL


// get the URL and authorization info from the form data
$request = $requestData->url;
//send the http request
if ($requestData->requestBody) {
  $ch = curl_init($request);
  curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST  => $method,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_SSL_VERIFYPEER => FALSE,
    CURLOPT_HTTPHEADER     => array(
      'Content-type: application/json',
      "Authorization: Bearer {$access_token}",
    ),
    CURLOPT_POSTFIELDS => $requestData->requestBody
  ));
  $response = curl_exec($ch);
  curl_close($ch);
} else {
  $ch = curl_init($request);
  curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST  => $method,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_SSL_VERIFYPEER => FALSE,
    CURLOPT_HTTPHEADER     => array(
      'Content-type: application/json',
      "Authorization: Bearer {$access_token}",
    )
  ));
  $response = curl_exec($ch);
  curl_close($ch);
}

// Check for errors and log them if any
// note that logging will fail unless
// the file log.txt exists in the same
// directory as the proxy and is writable
if ($response === FALSE) {
    $logEntry = "\nError:\n".
    "\n".date("Y-m-d H:i:s")." UTC \n"
    .$response;
    $logFileLocation = "log.txt";
    $fileHandle      = fopen($logFileLocation, 'a') or die("-1");
    fwrite($fileHandle, $logEntry);
    fclose($fileHandle);
    echo "Error: there was a problem with your API call"+
    die(curl_error($ch));
}

// Decode the response
// $responseData = json_decode($response, TRUE);
// return the response to the AJAX caller
$responseDecoded = json_decode($response);
if (!isset($responseDecoded)) {
    $response = '{null}';
}
echo $response;
?>
