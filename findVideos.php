<?php
include_once "vendor/autoload.php";
include("mysql_ddbb/config.php");
include("mysql_ddbb/bbdd_param.php");
include("functions.php");
include("youtube_params.php");
include("apiAutentification.php");
include("youtube/youtubeManager.php");



function parseRsGetAlterVideosIds($response){
    $numAlternVid = 0;
    $idVideos = "";

    foreach ($response['items'] as $itemRS) {

        if ($numAlternVid <= 5) {
            $idVideos .= $itemRS['id']['videoId'] . ',';
            $numAlternVid++;
        }

        if ($numAlternVid == 5) {
            $idVideos .= $itemRS['id']['videoId'];
        }
    }
    return $idVideos;
}
function parseRShowVideos($RS)
{
    $htmlListItems = "";
    $videoDuration = "";

    foreach ($RS['items'] as $videoDetails) {
        $videoDuration = convertYoutubeDurationTime($videoDetails['contentDetails']['duration']);
        $htmlListItems .= sprintf('
        <li class=w3> <span class="w3-tag w3-blue">%s</span> 
        <a class=w3-btn href="insertVideo.php?vdId=%s&playlistId=%s&oldId=%s&LongIdVideo=%s" >%s</a>
        </li> ',
            $videoDuration, $videoDetails['id'], $_GET['playlistId'], $_GET['oldId'], $_GET['LongIdVideo'], $videoDetails['snippet']['title']);
    }
    return $htmlListItems;
}
function convertYoutubeDurationTime($youtube_time)
{
    $start = new DateTime('@0'); // Unix epoch
    $start->add(new DateInterval($youtube_time));
    return $start->format('H:i:s');
}

$idVideos = '';

session_start();
$youtubeManager = new youtubeManager($OAUTH2_CLIENT_ID, $OAUTH2_CLIENT_SECRET);
$youtubeManager->createUserSesionAndExpireTime();
$youtubeManager->checkTokenExpire();

if ($youtubeManager->hasAccessToken()) {
    try {
        if (!empty($_GET['vdTitle']) && !empty($_GET['playlistId'])) {
            $response = $youtubeManager->getVideosByTitleAPI($_GET['vdTitle']);
            $idVideos = parseRsGetAlterVideosIds($response);

            if (!empty($idVideos)) {
                $videoDetallRS = $youtubeManager->getVideosByIdAPI($idVideos);
                $htmlListItems = parseRShowVideos($videoDetallRS);
            }
        }

    } catch (Google_Service_Exception $e) {
        $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
            htmlspecialchars($e->getMessage()));
    } catch (Google_Exception $e) {
        $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
            htmlspecialchars($e->getMessage()));
    }

    $_SESSION['token'] = $youtubeManager->client->getAccessToken();
} else {
    $htmlBody = showAuthorizationAlert($youtubeManager->client);
}

//Estructura de la pagina principal
include("includes/bodyPage.php");
?>




