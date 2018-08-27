<?php
include_once "vendor/autoload.php";

include("youtube/youtubeManager.php");
include("mysql_ddbb/databaseManager.php");

function parseResponseShowBlockedVideos($listResponse, $idsVideosPlaylists)
{
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
                    urlencode($tRS['snippet']['title']), $_GET['idPlist'], $tRS['id'], $idsVideosPlaylists[$tRS['id']], $tRS['snippet']['title']);
            }
        }
    }

    return $htmlListItems;
}

function showBlockedVideos($youtubeManager, $response)
{
//diccionario de videos id -> title
    $idsVideosPlaylists = array();
    $hmtlElements = "";
    $totalVideos = count($response['items']);
    $videosAnalized = 0;
    $numIdInRequest = 0;
    $requestIDs = "";

    foreach ($response['items'] as $itemRS) {
        $idsVideosPlaylists[$itemRS['snippet']['resourceId']['videoId']] = $itemRS['id'];
        //Get the bloqued videos
        if ($numIdInRequest <= 49) {
            $requestIDs .= $itemRS['snippet']['resourceId']['videoId'] . ",";
            if ($numIdInRequest == 49 || $totalVideos === ++$videosAnalized) {
                $requestIDs = rtrim($requestIDs, ", ");
                $response = $youtubeManager->getVideosByIdAPI($requestIDs);

                $numIdInRequest = 0;
                $hmtlElements .= parseResponseShowBlockedVideos($response, $idsVideosPlaylists);
            }
        }
        $numIdInRequest++;
    }
    return $hmtlElements;
}

function getListLongVideoIds($response)
{
    foreach ($response['items'] as $itemRS) {
        $idsVideosPlaylists[$itemRS['snippet']['resourceId']['videoId']] = $itemRS['id'];
    }
    return $idsVideosPlaylists;

}

function parseRsGetVideosDic($response)
{
    $dictionary = [];

    foreach ($response['items'] as $itemRS) {
        $dictionary[$itemRS['snippet']['resourceId']['videoId']] = $itemRS['snippet']['title'];
    }
    return $dictionary;
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
                    urlencode($dbVideo['titulo']), $_GET['idPlist'], $dbVideo['id_video'], $idsVideosPlaylists[$dbVideo['id_video']], $dbVideo['titulo']);
            } elseif ($tubeVideoRS[$dbVideo['id_video']] == $STR_PRIVATE_VIDEO) {
                $htmlListItems .= sprintf('
                    <li class=w3>
                        <a class=w3-btn href="findVideos.php?vdTitle=%s&playlistId=%s&oldId=%s&LongIdVideo=%s" >
                           <span class="w3-tag w3-red"> Private</span> %s
                        </a>
                    </li> ',
                    urlencode($dbVideo['titulo']), $_GET['idPlist'], $dbVideo['id_video'], $idsVideosPlaylists[$dbVideo['id_video']], $dbVideo['titulo']);
            }
        } else {
            //Si el usuario elimina el video de la playlist
            $htmlListItems .= sprintf('
            <li class=w3>
                <a class=w3-btn href="findVideos.php?vdTitle=%s&playlistId=%s&oldId=%s&LongIdVideo=%s">
                    <span class="w3-tag w3-green">Quitado</span> %s
                </a> 
            </li> ',
                urlencode($dbVideo['titulo']), $_GET['idPlist'], $dbVideo['id_video'], $idsVideosPlaylists[$dbVideo['id_video']], $dbVideo['titulo']);
        }
    }
    return $htmlListItems;
}

function getVideosByPlaylistIdFromDB($playlistId)
{
    global $db_host, $db_user, $db_pass, $database;
    $query = "SELECT * FROM videos WHERE idPlaylist = '" . $_GET['idPlist'] . "'";
    $DBManager = new databaseManager($db_host, $db_user, $db_pass, $database);
    return $DBManager->select_data($query);
}


$tubeVideoRS[] = array();
$ddbbVideoRS[] = array();
$idsVideosPlaylists[] = array();
$MSG_PRIVATE_VIDEO = "Private video";
$MSG_DELETED_VIDEO = "Deleted video";


session_start();
$youtubeManager = new youtubeManager($OAUTH2_CLIENT_ID, $OAUTH2_CLIENT_SECRET);
$youtubeManager->createUserSesionAndExpireTime();
$youtubeManager->checkTokenExpire();

if ($youtubeManager->hasAccessToken()) {
    try {
        if (!empty($_GET['idPlist'])) {
            do {
                $response = $youtubeManager->getPlaylistItemsAPI($nextPageToken, $_GET['idPlist']);
                $idsVideosPlaylists += getListLongVideoIds($response);
                $htmlListItems .= showBlockedVideos($youtubeManager, $response);
                $tubeVideoRS += parseRsGetVideosDic($response);

                $nextPageToken = $response['nextPageToken'];
            } while ($nextPageToken <> '');

            $ddbbVideoRS = getVideosByPlaylistIdFromDB($_GET['idPlist']);
            $htmlListItems .= analyzeAndShowDeletedVideos($ddbbVideoRS, $tubeVideoRS, $idsVideosPlaylists);
        }

    } catch (Google_Service_Exception $e) {
        $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
    } catch (Google_Exception $e) {
        $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
    }

    $_SESSION['token'] = $youtubeManager->getAccessToken();
} else {
    $urlAuth = generateAuthUrlSetState($youtubeManager->client);
    $htmlBody = addAuthorizationPanelAlert($urlAuth);
}
//Estructura de la pagina principal
include("includes/bodyPage.php");
?>




