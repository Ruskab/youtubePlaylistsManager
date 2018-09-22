<?php
include dirname(__FILE__)."/functions.php";
include dirname(__FILE__) . "/youtube/Video.php";
include dirname(__FILE__)."/youtube/YoutubeServiceAPI.php";
include dirname(__FILE__)."/youtube/playlistDAOImp.php";
include dirname(__FILE__)."/mysql_ddbb/VideoDAOImp.php";

$htmlBody = "";
$htmlListItems = "";
$logOutUri = sprintf('
  <form id="logout" method="POST" action="%s">
    <input type="hidden" name="logout" value="" />
    <input class="w3-btn" type="submit" value="Logout">
  </form>
',htmlspecialchars($_SERVER['PHP_SELF']));
session_start();


if (!empty($_GET['vdId']) && !empty($_GET['idPlist']) && !empty($_GET['LongIdVideo'])) {

    if (isset($_REQUEST['logout'])) {
        unset($_SESSION['upload_token']);
    }

    $playlistDao = new playlistDAOImp();

    $authUri = $playlistDao->getAuthUri();

    if (!empty($authUri)) {
        $htmlBody = addAuthorizationPanelAlert($authUri);
    }

    if ($playlistDao->userHasAccess()) {
        try {
            $newVideo = new video($_GET['vdId']);
            addVideoInfoFromGET($newVideo);

            $oldVideo = new video($_GET['oldId']);
            $oldVideo->setIdVideoPlaylist($_GET['LongIdVideo']);


            //$playlistDao->deleteVideoInPlaylist($oldVideo->getIdVideoPlaylist());
            $response = $playlistDao->setVideoInPlaylistAPI($newVideo);

            $newVideo->setTitle($response['snippet']['title']);

            $status_query = addNewVideoInDatabase($newVideo, $oldVideo); //2
            $htmlBody = addPanelWithStatus($status_query);

        } catch (Google_Service_Exception $e) {
            $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
        } catch (Google_Exception $e) {
            $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
        } catch (Exception $e) {
            $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
        }
    }
}
//Estructura de la pagina principal
include("includes/bodyPage.php");

//Abs 2
function addNewVideoInPlaylistGetTitle(video $video, video $oldvideo, youtubeManager $youtubeManager)
{
    $response = $youtubeManager->setVideoInPlaylistAPI($video);
    $DeleteVideoResponse = $youtubeManager->deleteVideoInPlaylistAPI($oldvideo->getIdVideoPlaylist());
    return $response['snippet']['title'];
}

//Abs 2
function addNewVideoInDatabase(video $newVideo, video $oldVideo)
{
    $videosDAO = new VideoDAOImp();
    $videosDAO->deleteVideo($oldVideo->getId());

    $msg_state = "";
    $msg_state = $videosDAO->insertVideo($newVideo);
    return $msg_state;
}

//Abs 2
function addPanelWithStatus($stateQuery)
{
    if ($stateQuery == "success") {
        header("Location:index.php");
    } else {
        return addPanelWithMessage($stateQuery);
    }
}

//abs 2
function addVideoInfoFromGET(Video &$video)
{
    if (!empty($_GET)) {
        if (!empty($_GET['title'])) $video->setTitle($_GET['title']);
        if (!empty($_GET['idPlist'])) $video->setPlaylist($_GET['idPlist']);
        if (!empty($_GET['idVideoPlist'])) $video->setIdVideoPlaylist($_GET['idVideoPlist']);
        if (!empty($_GET['duration'])) $video->setDuration($_GET['duration']);
    }
}

?>
