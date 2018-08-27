<?php
include_once "vendor/autoload.php";
include("mysql_ddbb/config.php");
include("mysql_ddbb/bbdd_param.php");
include("youtube_params.php");
include("apiAutentification.php");
include("functions.php");


function addNewVideoInDatabase(video $newVideo, video $oldVideo)
{
    global $db_host, $db_user, $db_pass, $database;
    $DBManager = new databaseManager($db_host, $db_user, $db_pass, $database);
    return $DBManager->renewVideoInDDBB($newVideo->getId(), $oldVideo->getId(), $newVideo->getPlaylist(), $newVideo->getTitle());
}

session_start();
$youtubeManager = new youtubeManager($OAUTH2_CLIENT_ID, $OAUTH2_CLIENT_SECRET);
$youtubeManager->createUserSesionAndExpireTime();
$youtubeManager->checkTokenExpire();

// Check to ensure that the access token was successfully acquired.
if ($youtubeManager->hasAccessToken()) {
    try {
        if (!empty($_GET['vdId']) && !empty($_GET['plId']) && !empty($_GET['LongIdVideo'])) {
            //youtube API
            $newVideo = new video($_GET['vdId']);
            $newVideo->addVideoInfoFromGET();
            $oldVideo = new video($_GET['oldId']);
            $playlistItemResponse = $youtubeManager->setVideoInPlaylistAPI($newVideo);
            $DeleteVideoResponse = $youtubeManager->deleteVideoInPlaylistAPI($newVideo->getIdVideoPlaylist());

            //DDBB
            $stateQuery = addNewVideoInDatabase($newVideo, $oldVideo);

            if ($stateQuery == "success") {
                header("Location:index.php");
            } else {
                $htmlBody = addPanelWithMessage($stateQuery);
            }
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
