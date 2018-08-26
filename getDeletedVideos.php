<?php
include_once "vendor/autoload.php";
include("mysql_ddbb/config.php");
include("mysql_ddbb/bbdd_param.php");
include("youtube_params.php");
include("apiAutentification.php");
include("functions.php");


function buildRequestGetPlayListVideos(&$part, &$optionalParameters, &$nextPageToken)
{
    $part = 'id,snippet, contentDetails, status';

    $optionalParameters = array(
        'playlistId' => $_GET['Plist'],
        'maxResults' => 50,
        'pageToken' => $nextPageToken);
}
function buildRequestGetVideosDetails(&$part, &$optionalParameters, &$requestIDs){
    $part = "snippet,contentDetails";

    $optionalParameters = array(
        'id' => $requestIDs);
}
function parseResponseShowBlockedVideos($listResponse, $idsVideosPlaylists){
    $htmlListItems = "";

    foreach ($listResponse['items'] as $tRS) {
        if (!empty($tRS['contentDetails']['regionRestriction']['blocked'])) {
            if (in_array('ES', $tRS['contentDetails']['regionRestriction']['blocked'], false)) {
                $htmlListItems .= sprintf('
                    <li class=w3>
                        <a class=w3-btn href="findVideos.php?vdTitle=%s&playlistId=%s&oldId=%s&LongIdVideo=%s" >
                           <span class="w3-tag w3-green">Blocked</span> %s
                        </a>
                    </li> ',
                    urlencode($tRS['snippet']['title']), $_GET['Plist'], $tRS['id'], $idsVideosPlaylists[$tRS['id']], $tRS['snippet']['title']);
            }
        }
    }

    return $htmlListItems;
}
function parseRsGetBlockedVideos($response, &$nextPageToken, $youtube, &$idsVideosPlaylists, &$tubeVideoRS)
{

    $totalVideos = count($response['items']);
    $videosAnalized = 0;
    $numIdInRequest = 0;
    $requestIDs = "";
    $videoPlaylistID = "";

    foreach ($response['items'] as $itemRS) {
        $idsVideosPlaylists[$itemRS['snippet']['resourceId']['videoId']] = $itemRS['id'];
        //Get the bloqued videos
        if ($numIdInRequest <= 49) {

            $requestIDs .= $itemRS['snippet']['resourceId']['videoId'] . ",";
            if ($numIdInRequest == 49 || $totalVideos === ++$videosAnalized) {
                $requestIDs = rtrim($requestIDs, ", ");

                buildRequestGetVideosDetails($part, $optionalParameters, $requestIDs);
                $listResponse = $youtube->videos->listVideos($part, $optionalParameters);
                $numIdInRequest = 0;
                return parseResponseShowBlockedVideos($listResponse, $idsVideosPlaylists);
            }
        }
        $numIdInRequest++;

        $tubeVideoRS[$itemRS['snippet']['resourceId']['videoId']] = $itemRS['snippet']['title'];
    }
    $nextPageToken = $response['nextPageToken'];

}
function analyzeAndShowDeletedVideos($ddbbVideoRS, $tubeVideoRS, $idsVideosPlaylists)
{
    global $STR_DELETED_VIDEO, $STR_PRIVATE_VIDEO;
    $htmlListItems = "";

    //Procesamos las 2 listas, reccorremos la de la ddbb y comparamos con la de youtube

    foreach ($ddbbVideoRS as $dbVideo) {
        if (array_key_exists($dbVideo['id_video'], $tubeVideoRS)) {
            if ($tubeVideoRS[$dbVideo['id_video']] == $STR_DELETED_VIDEO) {
                $htmlListItems .= sprintf('
                    <li class=w3>
                        <a class=w3-btn href="findVideos.php?vdTitle=%s&playlistId=%s&oldId=%s&LongIdVideo=%s" >
                           <span class="w3-tag w3-red"> Deleted</span> %s
                        </a>
                    </li> ',
                    urlencode($dbVideo['titulo']), $_GET['Plist'], $dbVideo['id_video'], $idsVideosPlaylists[$dbVideo['id_video']], $dbVideo['titulo']);
            }elseif($tubeVideoRS[$dbVideo['id_video']] == $STR_PRIVATE_VIDEO){
                $htmlListItems .= sprintf('
                    <li class=w3>
                        <a class=w3-btn href="findVideos.php?vdTitle=%s&playlistId=%s&oldId=%s&LongIdVideo=%s" >
                           <span class="w3-tag w3-red"> Private</span> %s
                        </a>
                    </li> ',
                    urlencode($dbVideo['titulo']), $_GET['Plist'], $dbVideo['id_video'], $idsVideosPlaylists[$dbVideo['id_video']], $dbVideo['titulo']);
            }
        } else {
            //Si el usuario elimina el video de la playlist
            $htmlListItems .= sprintf('
            <li class=w3>
                <a class=w3-btn href="findVideos.php?vdTitle=%s&playlistId=%s&oldId=%s&LongIdVideo=%s">
                    <span class="w3-tag w3-green">Quitado</span> %s
                </a> 
            </li> ',
                urlencode($dbVideo['titulo']), $_GET['Plist'], $dbVideo['id_video'], $idsVideosPlaylists[$dbVideo['id_video']], $dbVideo['titulo']);
        }
    }
    return $htmlListItems;
}


// Define an object that will be used to make all API requests.

$tubeVideoRS[] = array();
$ddbbVideoRS[] = array();
$idsVideosPlaylists[] = array();
$MSG_PRIVATE_VIDEO = "Private video";
$MSG_DELETED_VIDEO = "Deleted video";


session_start();
$youtubeManager = new youtubeManager($OAUTH2_CLIENT_ID, $OAUTH2_CLIENT_SECRET);
$youtubeManager->createUserSesionAndExpireTime();
$youtubeManager->checkTokenExpire();


if ($client->getAccessToken()) {
    try {
        // Call the channels.list method to retrieve information about the
        // currently authenticated user's channel.

        if (!empty($_GET['Plist'])) {
            $count = 0;
            do {

                buildRequestGetPlayListVideos($part, $optionalParameters, $nextPageToken);
                $response = $youtube->playlistItems->listPlaylistItems($part, $optionalParameters);

                //$htmlListItems .= parseRsGetBlockedVideos($response,$nextPageToken,$youtube,$idsVideosPlaylists,$tubeVideoRS);

                //diccionario de videos id -> title
                $totalVideos = count($response['items']);
                $videosAnalized = 0;
                $numIdInRequest = 0;
                $requestIDs = "";
                $videoPlaylistID = "";

                foreach ($response['items'] as $itemRS) {
                    $idsVideosPlaylists[$itemRS['snippet']['resourceId']['videoId']] = $itemRS['id'];
                    //Get the bloqued videos
                    if ($numIdInRequest <= 49) {

                        $requestIDs .= $itemRS['snippet']['resourceId']['videoId'] . ",";
                        if ($numIdInRequest == 49 || $totalVideos === ++$videosAnalized) {
                            $requestIDs = rtrim($requestIDs, ", ");

                            buildRequestGetVideosDetails($part, $optionalParameters, $requestIDs);
                            $listResponse = $youtube->videos->listVideos($part, $optionalParameters);
                            $numIdInRequest = 0;
                            $htmlListItems .= parseResponseShowBlockedVideos($listResponse, $idsVideosPlaylists);
                        }
                    }
                    $numIdInRequest++;

                    $tubeVideoRS[$itemRS['snippet']['resourceId']['videoId']] = $itemRS['snippet']['title'];
                }
                $nextPageToken = $response['nextPageToken'];

            } while ($nextPageToken <> '');

            //conexion a la bbdd
            $sql_query = "SELECT * FROM videos WHERE idPlaylist = '" . $_GET['Plist'] . "'";
            $ddbbVideoRS = getDataFromDatabase($sql_query);

            $htmlListItems .= analyzeAndShowDeletedVideos($ddbbVideoRS, $tubeVideoRS, $idsVideosPlaylists);
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
    $htmlBody = showAuthorizationAlert($client);
}
//Estructura de la pagina principal
include("includes/bodyPage.php");
?>




