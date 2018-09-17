<?php
include_once "vendor/autoload.php";
include("functions.php");
include("mysql_ddbb/databaseManager.php");
include("youtube/video.php");


$idVideosViews = array();
$idsVideosPlaylists = array();
$detailVideos = array();


session_start();
$youtubeManager = initYoutubeService($OAUTH2_CLIENT_ID, $OAUTH2_CLIENT_SECRET);

if ($youtubeManager->hasAccessToken()) {
    try {
        if (!empty($_GET['idPlist'])) {
            do {
                $response = $youtubeManager->getPlaylistItemsAPI($nextPageToken, $_GET['idPlist']);
                getVideosAndViews($youtubeManager, $response, $detailVideos, $idVideosViews);
                $nextPageToken = $response['nextPageToken'];
            } while ($nextPageToken <> '');

            if (!empty($idVideosViews)) {
                arsort($idVideosViews);
               $htmlListItems = addTopVideos(array_slice($idVideosViews,0, 10), $detailVideos);
            } else {
                $htmlListItems = addPanelWithMessage("No hay videos");
            }
        }

    } catch (Google_Service_Exception $e) {
        $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
    } catch (Google_Exception $e) {
        $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
    } catch (Exception $e) {
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
function getVideosAndViews($youtubeManager, $response, &$videosDetails, &$idVideosViews)
{
//diccionario de videos id -> title
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
                addVideosInDic($response, $idVideosViews);
                AddVideosDetailVideos($response, $videosDetails);
                $numIdInRequest = 0;
            }
        }
        $numIdInRequest++;
    }
}

//Abs 3
function addVideosInDic($listResponse, &$idVideosViews)
{
    foreach ($listResponse['items'] as $tRS) {
        if (!empty($tRS['statistics']['viewCount'])) {
            $idVideosViews[$tRS['id']] = $tRS['statistics']['viewCount'];
        }
    }
}

function AddVideosDetailVideos($listResponse, &$detailVideos)
{
    foreach ($listResponse['items'] as $tRS) {
        $video = new video($tRS['id']);
        $video->setTitle($tRS['snippet']['title']);
        $detailVideos[$tRS['id']] = $video;
    }


}

function addTopVideos($ids, $details)
{
    $htmlListItems = "";
    $count = 1;
    foreach ($ids as $id => $views) {
        $title = $details[$id]->getTitle();
        $htmlListItems .= sprintf('
                    <li class=w3-left-align>
                        <a class=w3-btn href="https://www.youtube.com/watch?v=%s" >
                           <span class="w3-tag w3-blue">%s</span>
                            <span class="w3-tag w3-green">%s</span>
                            %s
                        </a>
         
                    </li> ',
            $id, $count,$views,$title);

        $count++;
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




