<?php

include dirname(__FILE__)."/functions.php";
include dirname(__FILE__) . "/youtube/Video.php";
include dirname(__FILE__)."/youtube/YoutubeServiceAPI.php";
include dirname(__FILE__)."/youtube/playlistDAOImp.php";
include dirname(__FILE__)."/mysql_ddbb/VideoDAOImp.php";


$idVideos = '';
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
        if (!empty($_GET['vdTitle']) && !empty($_GET['playlistId'])) {
            $response = $playlistDao->getVideosByTitle($_GET['vdTitle']);
            $idVideos = parseRsGetAlterVideosIds($response);
            if (!empty($idVideos)) {
                $videoDetailRS = $playlistDao->getVideosByIDs($idVideos);
                $htmlListItems = parseRsAddListVideos($videoDetailRS);
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

function parseRsAddListVideos($RS)
{
    $htmlListItems = "";
    $videoDuration = "";

    foreach ($RS as $videoDetails) {
        $videoDuration = convertYoutubeDurationTime($videoDetails['contentDetails']['duration']);
        $htmlListItems .= sprintf('
        
        <li class="w3-left-align">        
         <span class="w3-tag w3-blue">%s</span> 
            <a class=w3-btn href="insertVideo.php?vdId=%s&idPlist=%s&oldId=%s&LongIdVideo=%s" >
            <img height="35" width="35" class="w3-bar-item" src="https://img.youtube.com/vi/%s/1.jpg">
            %s</a>
        </li>',
            $videoDuration, $videoDetails['id'], $_GET['playlistId'],
            $_GET['oldId'], $_GET['LongIdVideo'],$videoDetails['id'], $videoDetails['snippet']['title']);
    }
    return $htmlListItems;
}

function convertYoutubeDurationTime($youtube_time)
{
    $start = new DateTime('@0'); // Unix epoch
    $start->add(new DateInterval($youtube_time));
    return $start->format('H:i:s');
}

?>




