<?php
include_once "vendor/autoload.php";
include("functions.php");
include("youtube/video.php");
include("mysql_ddbb/databaseManager.php");

$htmlBody="";
$htmlListItems  = "";

session_start();

if (!empty($_GET['vdId']) && !empty($_GET['idPlist']) && !empty($_GET['LongIdVideo'])) {
    $youtubeManager = initYoutubeService($OAUTH2_CLIENT_ID, $OAUTH2_CLIENT_SECRET);

    if ($youtubeManager->hasAccessToken()) {
        try {
            $newVideo = new video($_GET['vdId']);
            $newVideo->addVideoInfoFromGET(); //2

            $oldVideo = new video($_GET['oldId']);
            $oldVideo ->setIdVideoPlaylist($_GET['LongIdVideo']);

            $newVideo->setTitle(addNewVideoInPlaylistGetTitle($newVideo, $oldVideo, $youtubeManager));  //2
            $status_query = addNewVideoInDatabase($newVideo, $oldVideo); //2
            $htmlBody = addPanelWithStatus($status_query);

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
}
//Estructura de la pagina principal
include("includes/bodyPage.php");

//Abs 2
function addNewVideoInPlaylistGetTitle(video $video, video $oldvideo, youtubeManager $youtubeManager)
{
    $playlistItemResponse = $youtubeManager->setVideoInPlaylistAPI($video);
    $DeleteVideoResponse = $youtubeManager->deleteVideoInPlaylistAPI($oldvideo->getIdVideoPlaylist());
    return $playlistItemResponse['snippet']['title'];
}
//Abs 2
function addNewVideoInDatabase(video $newVideo, video $oldVideo)
{
    global $db_host, $db_user, $db_pass, $database;
    $DBManager = new databaseManager($db_host, $db_user, $db_pass, $database);
    $stateQuery =$DBManager->renewVideoInDDBB($newVideo, $oldVideo);
}

//Abs 2
function addPanelWithStatus ($stateQuery){
    if ($stateQuery == "success") {
        header("Location:index.php");
    } else {
        return addPanelWithMessage($stateQuery);
    }
}




?>
