<?php  
include_once "vendor/autoload.php";
include("mysql_ddbb/config.php");
include("mysql_ddbb/bbdd_param.php");
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
$youtubeVideos_result[] = array();
$ddbb_videos_result[] = array();

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

            //Peticion para cada video individual
            //$videoItemsResponse = $youtube->videos->listVideos('contentDetails', array(
            //  'id' => $playlistItem['snippet']['resourceId']['videoId']                          
            //));
            //var_dump($videoItemsResponse["items"]["0"]["contentDetails"]["regionRestriction"]);
            
            //if ($playlistItem['snippet']['title'] == $STR_DELETED_VIDEO){
            //var_dump("video DELETED");                          
            //}

         $youtubeVideos_result[$playlistItem['snippet']['resourceId']['videoId']] = $playlistItem['snippet']['title'];

         //   $htmlBody .= sprintf('<li>%s#%s </li>', $playlistItem['snippet']['title'],$playlistItem['snippet']['resourceId']['videoId']);                
    }

    $nextPageToken = $playlistItemsResponse['nextPageToken'];
} while ($nextPageToken <> '');



    $mysql_connection=conexion_mysqli($db_host,$db_user,$db_pass,$database);

    $sql_querry = "SELECT * FROM videos WHERE 1";
    $result_mysql = mysqli_query($mysql_connection, $sql_querry);

if (mysqli_num_rows($result_mysql) > 0) {
    // output data of each row
    while($row = mysqli_fetch_assoc($result_mysql)) {                                          
              $ddbb_videos_result[$row['id_video']] = $row['titulo'];       
    }    
    
} else {
    echo "0 results";
}


//Procesamos las 2 listas, reccorremos la de la ddbb y comparamos con la de youtube
foreach ($ddbb_videos_result as $idVideo => $title){  
  if (array_key_exists($idVideo, $youtubeVideos_result)){  
    if ($youtubeVideos_result[$idVideo] == $STR_DELETED_VIDEO){
       $htmlBody .= sprintf('<li>%s#%s </li>', $idVideo, $title);                
    }
  }else{
       $htmlBody .= sprintf("<li>Eliminado de la Playlist:\t[%s] \t\t %s </li>", $idVideo, $title);                
  }
}



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





