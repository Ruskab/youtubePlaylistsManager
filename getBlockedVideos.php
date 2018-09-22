<?php
include dirname(__FILE__)."/functions.php";
include dirname(__FILE__) . "/youtube/Video.php";
include dirname(__FILE__)."/youtube/YoutubeServiceAPI.php";
include dirname(__FILE__)."/youtube/playlistDAOImp.php";
include dirname(__FILE__)."/mysql_ddbb/VideoDAOImp.php";

$tubeVideoRS[] = array();
$idsVideosPlaylists[] = array();
$htmlListItems = '';
$nextPageToken = '';
$htmlBody = '';
$logOutUri = sprintf('
  <form id="logout" method="POST" action="%s">
    <input type="hidden" name="logout" value="" />
    <input class="w3-btn" type="submit" value="Logout">
  </form>
',htmlspecialchars($_SERVER['PHP_SELF']));

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
            $idsVideosPlaylists = getListLongVideoIds($response);
            $htmlListItems = getBlockedVideosListHTML($playlistDao,$response);
            $tubeVideoRS += parseRsGetAllVideosDic($response);

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

}
//Estructura de la pagina principal
include("includes/bodyPage.php");

//Abs 2
function getBlockedVideosListHTML($playlistDao, $response)
{
//diccionario de videos id -> title
    $idsVideosPlaylists = array();
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
                $hmtlElements .= parseRsAddBlockedVideos($response, $idsVideosPlaylists);
                $numIdInRequest = 0;
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
                    <li class=w3-left-align>
                        <a class=w3-btn href="https://www.youtube.com/watch?v=%s" >
                           <span class="w3-tag w3-green">Blocked</span> <img height="35" width="35" src="https://img.youtube.com/vi/%s/1.jpg"> %s
                        </a>
                    </li> ',
                    $tRS['id'],$tRS['id'], $tRS['snippet']['title']);
            }
        }
    }

    return $htmlListItems;
}
//Abs 2
function getListLongVideoIds($response)
{
    foreach ($response as $itemRS) {
        $idsVideosPlaylists[$itemRS['snippet']['resourceId']['videoId']] = $itemRS['id'];
    }
    return $idsVideosPlaylists;

}
//Abs 2
function parseRsGetAllVideosDic($response)
{
    $dictionary = [];

    foreach ($response as $itemRS) {
        $dictionary[$itemRS['snippet']['resourceId']['videoId']] = $itemRS['snippet']['title'];
    }
    return $dictionary;
}

?>




