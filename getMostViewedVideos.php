<?php
include dirname(__FILE__)."/functions.php";
include dirname(__FILE__) . "/youtube/Video.php";
include dirname(__FILE__)."/youtube/YoutubeServiceAPI.php";
include dirname(__FILE__)."/youtube/playlistDAOImp.php";
include dirname(__FILE__)."/mysql_ddbb/VideoDAOImp.php";


$idVideosViews = array();
$idsVideosPlaylists = array();
$detailVideos = array();
$htmlListItems = '';
$nextPageToken = '';
$htmlBody = '';
$logOutUri = sprintf('
  <form id="logout" method="POST" action="%s">
    <input type="hidden" name="logout" value="" />
    <input class="w3-btn" type="submit" value="Logout">
  </form>
', htmlspecialchars($_SERVER['PHP_SELF']));


session_start();

if (isset($_REQUEST['logout'])) {
    unset($_SESSION['upload_token']);
}

$playlistDao = new playlistDAOImp();

$authUri = $playlistDao->getAuthUri();

if (!empty($authUri)){
    $htmlBody= addAuthorizationPanelAlert($authUri);
}

if ($playlistDao->userHasAccess()){
    try {
        if (!empty($_GET['idPlist'])) {

                $response = $playlistDao->getPlaylistVideos($_GET['idPlist']);

                getVideosAndViews($playlistDao, $response, $detailVideos, $idVideosViews);

            if (!empty($idVideosViews)) {
                arsort($idVideosViews);
               $htmlListItems = addTopVideos(array_slice($idVideosViews,0, $_GET['gender']), $detailVideos);
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
}
//Estructura de la pagina principal
include("includes/bodyPage.php");

//Abs 2
function getVideosAndViews($playlistDao, $response, &$videosDetails, &$idVideosViews)
{
//diccionario de videos id -> title
    $hmtlElements = "";
    $totalVideos = count($response);
    $videosAnalized = 0;
    $numIdInRequest = 0;
    $requestIDs = "";

    foreach ($response as $itemRS) {
        $idsVideosPlaylists[$itemRS['snippet']['resourceId']['videoId']] = $itemRS['id'];
        //Get the bloqued videos
        if ($numIdInRequest <= 49) {
            $requestIDs .= $itemRS['snippet']['resourceId']['videoId'] . ",";
            if ($numIdInRequest == 49 || $totalVideos === ++$videosAnalized) {
                $requestIDs = rtrim($requestIDs, ", ");
                $response = $playlistDao->getVideosByIDs($requestIDs);
                $requestIDs = "";

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




