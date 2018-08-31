<?php
include_once "vendor/autoload.php";
include("functions.php");
include("mysql_ddbb/databaseManager.php");

$tubeVideoRS[] = array();
$idsVideosPlaylists[] = array();

session_start();
$youtubeManager = initYoutubeService($OAUTH2_CLIENT_ID, $OAUTH2_CLIENT_SECRET);

if ($youtubeManager->hasAccessToken()) {
    try {
        if (!empty($_GET['idPlist'])) {
            do {
                $response = $youtubeManager->getPlaylistItemsAPI($nextPageToken, $_GET['idPlist']);
                $idsVideosPlaylists += getListLongVideoIds($response);
                $htmlListItems .= getVideoDetailsAndFilterBlockedVideos($youtubeManager, $response);
                $tubeVideoRS += parseRsGetAllVideosDic($response);

                $nextPageToken = $response['nextPageToken'];
            } while ($nextPageToken <> '');

            if (empty($htmlListItems)){
                $htmlListItems = addPanelWithMessage("No hay videos");
            }
        }

    } catch (Google_Service_Exception $e) {
        $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
    } catch (Google_Exception $e) {
        $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
    } catch (Exception $e){
        $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
    }

    $_SESSION['token'] = $youtubeManager->getAccessToken();
} else {
    $urlAuth = generateAuthUrlSetState($youtubeManager->client);
    $htmlBody = addAuthorizationPanelAlert($urlAuth);
}
//Estructura de la pagina principal
include("includes/bodyPage.php");

//Abs 2
function getVideoDetailsAndFilterBlockedVideos($youtubeManager, $response)
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
                $hmtlElements .= parseRsAddBlockedVideos($response, $idsVideosPlaylists);
            }
        }
        $numIdInRequest++;
    }
    return $hmtlElements;
}
//Abs 3
function parseRsAddBlockedVideos($listResponse, $idsVideosPlaylists)
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
//Abs 2
function getListLongVideoIds($response)
{
    foreach ($response['items'] as $itemRS) {
        $idsVideosPlaylists[$itemRS['snippet']['resourceId']['videoId']] = $itemRS['id'];
    }
    return $idsVideosPlaylists;

}
//Abs 2
function parseRsGetAllVideosDic($response)
{
    $dictionary = [];

    foreach ($response['items'] as $itemRS) {
        $dictionary[$itemRS['snippet']['resourceId']['videoId']] = $itemRS['snippet']['title'];
    }
    return $dictionary;
}

?>




