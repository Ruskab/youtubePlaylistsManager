<?php  
include_once "../vendor/autoload.php";
include("../mysql_ddbb/config.php");
include("../mysql_ddbb/bbdd_param.php");

// Call set_include_path() as needed to point to your client library.

session_start();

/*
 * You can acquire an OAuth 2.0 client ID and client secret from the
 * Google Developers Console <https://console.developers.google.com/>
 * For more information about using OAuth 2.0 to access Google APIs, please see:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Please ensure that you have enabled the YouTube Data API for your project.
 */

$OAUTH2_CLIENT_ID = '761462490192-m4slh9abud0e4a90gu1c2oeb7kr5m28m.apps.googleusercontent.com';
$OAUTH2_CLIENT_SECRET = 'jOSlRkz9WM_2ek882DG3vlPb';

$STR_DELETED_VIDEO = "Deleted video";

$client = new Google_Client();
$client->setClientId($OAUTH2_CLIENT_ID);
$client->setClientSecret($OAUTH2_CLIENT_SECRET);
$client->setScopes('https://www.googleapis.com/auth/youtube');
$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
  FILTER_SANITIZE_URL);
$client->setRedirectUri($redirect);

// Define an object that will be used to make all API requests.
$youtube = new Google_Service_YouTube($client);
$nextPageToken = '';
$htmlBody = '';
$videos_result[] = array();

if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }

  $client->authenticate($_GET['code']);
  $_SESSION['token'] = $client->getAccessToken();
  header('Location: ' . $redirect);
}

if (isset($_SESSION['token'])) {
  $client->setAccessToken($_SESSION['token']);
}

// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
  try {
    // Call the channels.list method to retrieve information about the
    // currently authenticated user's channel.
    
do {
    $playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', array(
    'playlistId' => 'PLak68FO75_ICct3r1rPgeuS5AGop1fqFu',
    'maxResults' => 50,
    'pageToken' => $nextPageToken));

    foreach ($playlistItemsResponse['items'] as $playlistItem) {

            //Recorrer cada video de la playlist
            $videoItemsResponse = $youtube->videos->listVideos('contentDetails', array(
              'id' => $playlistItem['snippet']['resourceId']['videoId']                      
            ));                        

         //   $videos_result[$playlistItem['snippet']['resourceId']['videoId']] = $playlistItem['snippet']['title'];
         //   var_dump($videos_result);
         //   die();

//    $htmlBody .= sprintf('<li>%s (%s)</li>', $playlistItem['snippet']['title'], $videoItemsResponse["pageInfo"]["totalResults"]);            
    
    }

    $nextPageToken = $playlistItemsResponse['nextPageToken'];
} while ($nextPageToken <> '');
    

  } catch (Google_Service_Exception $e) {
    $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
      htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
      htmlspecialchars($e->getMessage()));
  }

  $_SESSION['token'] = $client->getAccessToken();
} else {
  $state = mt_rand();
  $client->setState($state);
  $_SESSION['state'] = $state;

  $authUrl = $client->createAuthUrl();
  $htmlBody = <<<END
  <h3>Authorization Required</h3>
  <p>You need to <a href="$authUrl">authorize access</a> before proceeding.<p>
END;
}
?>

<!doctype html>
<html>
  <head>
    <title>My Uploads</title>
  </head>
  <body>
    <?=$htmlBody?>
  </body>
</html>





