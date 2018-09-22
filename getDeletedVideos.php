<?php

include dirname(__FILE__)."/functions.php";
include dirname(__FILE__) . "/youtube/Video.php";
include dirname(__FILE__)."/youtube/YoutubeServiceAPI.php";
include dirname(__FILE__)."/youtube/playlistDAOImp.php";
include dirname(__FILE__)."/mysql_ddbb/VideoDAOImp.php";


$tubeVideoRS[] = array();
$ddbbVideoRS[] = array();
$idsVideosPlaylists[] = array();
$MSG_PRIVATE_VIDEO = "Private video";
$MSG_DELETED_VIDEO = "Deleted video";
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
//$youtubeManager = initYoutubeService($OAUTH2_CLIENT_ID, $OAUTH2_CLIENT_SECRET);
    try {
      // todo Revisar lo de csrf token
if (isset($_REQUEST['logout'])) {
    unset($_SESSION['upload_token']);
}

$playlistDao = new playlistDAOImp();

$authUri = $playlistDao->getAuthUri();

if (!empty($authUri)){
    $htmlBody= addAuthorizationPanelAlert($authUri);
}

if ($playlistDao->userHasAccess()){
    if (!empty($_GET['idPlist'])) {
        $response = $playlistDao->getPlaylistVideos($_GET['idPlist']);
        $idsVideosPlaylists = getListLongVideoIds($response);
        $htmlListItems .= getBlockedVideosListHTML($playlistDao, $response);
        $tubeVideoRS += parseRsGetAllVideosDic($response);

        $videoDAOImp = new VideoDAOImp();
        $ddbbVideoRS = $videoDAOImp->GetVideosByPlaylistId($_GET['idPlist']);
        $htmlListItems .= analyzeAndAddDeletedVideos($ddbbVideoRS, $tubeVideoRS, $idsVideosPlaylists);

        if (empty($htmlListItems)){
            $htmlListItems = addPanelWithMessage("No hay videos");
        }
    }

}


    } catch (Google_Service_Exception $e) {
        $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
    } catch (Google_Exception $e) {
        $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
    } catch (Exception $e){
        $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
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
//Abs 2
function analyzeAndAddDeletedVideos($ddbbVideoRS, $tubeVideoRS, $idsVideosPlaylists)
{
    global $STR_DELETED_VIDEO, $STR_PRIVATE_VIDEO;
    $htmlListItems = "";

    //Procesamos las 2 listas, reccorremos la de la ddbb y comparamos con la de youtube
    foreach ($ddbbVideoRS as $dbVideo) {
        if (array_key_exists($dbVideo->getId(), $tubeVideoRS)) {
            if ($tubeVideoRS[$dbVideo->getId()] == $STR_DELETED_VIDEO) {
                $htmlListItems .= sprintf('
                    <li class=w3>
                        <a class=w3-btn href="findVideos.php?vdTitle=%s&playlistId=%s&oldId=%s&LongIdVideo=%s" >
                           <span class="w3-tag w3-red"> Deleted</span> %s
                        </a>
                    </li> ',
                    urlencode($dbVideo->getTitle()), $_GET['idPlist'], $dbVideo->getId(),
                    $idsVideosPlaylists[$dbVideo->getId()], $dbVideo->getTitle());

            } elseif ($tubeVideoRS[$dbVideo->getId()] == $STR_PRIVATE_VIDEO) {
                $htmlListItems .= sprintf('
                    <li class=w3>
                        <a class=w3-btn href="findVideos.php?vdTitle=%s&playlistId=%s&oldId=%s&LongIdVideo=%s" >
                           <span class="w3-tag w3-red"> Private</span> %s
                        </a>
                    </li> ',
                    urlencode($dbVideo->getTitle()), $_GET['idPlist'], $dbVideo->getId(),
                    $idsVideosPlaylists[$dbVideo->getId()], $dbVideo->getTitle());
            }
        } else {
            //Si el usuario elimina el video de la playlist
            $htmlListItems .= sprintf('
            <li class=w3>
                <a class=w3-btn href="findVideos.php?vdTitle=%s&playlistId=%s&oldId=%s&LongIdVideo=%s">
                    <span class="w3-tag w3-green">Quitado</span> %s
                </a> 
            </li> ',
                urlencode($dbVideo->getTitle()), $_GET['idPlist'], $dbVideo->getId(),$dbVideo->getPlaylist(), $dbVideo->getTitle());
        }
    }

    return $htmlListItems;
}

?>




