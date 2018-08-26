<?php 
include_once "vendor/autoload.php";
include("youtube_params.php");

function get_client(& $client, & $redirect, $cliendID, $client_secret){
	$client = new Google_Client();
	$client->setClientId($cliendID);
	$client->setClientSecret($client_secret);
	$client->setScopes('https://www.googleapis.com/auth/youtube');
	$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],FILTER_SANITIZE_URL);
	$client->setRedirectUri($redirect);
 }

function initGoogleClientAndYoutubeService(& $client, & $redirect, &$youtube){
    global $OAUTH2_CLIENT_ID, $OAUTH2_CLIENT_SECRET;
    $client = new Google_Client();
    $client->setClientId($OAUTH2_CLIENT_ID);
    $client->setClientSecret($OAUTH2_CLIENT_SECRET);
    $client->setScopes('https://www.googleapis.com/auth/youtube');
    //redirect url to autorize user
    $redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],FILTER_SANITIZE_URL);

    $client->setRedirectUri($redirect);

    // Define an object that will be used to make all API requests.
    $youtube = new Google_Service_YouTube($client);
}

function showAuthorizationAlert(&$client)
{
    $state = mt_rand();
    $client->setState($state);
    $_SESSION['state'] = $state;
    $authUrl = $client->createAuthUrl();
    $htmlBody = sprintf("<div class=\"w3-panel w3-pale-blue w3-leftbar w3-rightbar w3-border-blue\">
            <h3>Authorization Required</h3>
            <p>You need to <a href=\"%s\">authorize access</a> before proceeding.<p>
            </div>", $authUrl);

    return $htmlBody;
}


function checkTokenExpire(&$client)
{
//Siempre que se refresque la pagina agregar el token hasta que expire
    if (isset($_SESSION['token']) && time() < $_SESSION['access_token_expiry']) {
        $client->setAccessToken($_SESSION['token']);
    }
}

function createUserSesionAndExpireTime(&$client, $redirect)
{
    if (isset($_GET['code'])) {
        //comparamos como string la sesion
        if (strval($_SESSION['state']) !== strval($_GET['state'])) {
            die('The session state did not match.');
        }
        $client->authenticate($_GET['code']);
        $_SESSION['token'] = $client->getAccessToken();
        $_SESSION['access_token_expiry'] = time() + $client->getAccessToken()['expires_in'];
        header('Location: ' . $redirect);
    }
}

?>