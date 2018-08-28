<?php
include_once "vendor/autoload.php";
include("functions.php");
include("youtube/video.php");
include("mysql_ddbb/databaseManager.php");


//$youtubeManager->client->setAccessType('offline');
//$youtubeManager->client->setApprovalPrompt('force');


session_start();
$youtubeManager = initYoutubeService($OAUTH2_CLIENT_ID, $OAUTH2_CLIENT_SECRET);

// Si el usuario esta autorizado
if ($youtubeManager->hasAccessToken()) {
    try {
        if (!empty($_GET['idPlaylist'])) {
            updatePlaylist($youtubeManager);
        }
        //List User Playlists
        $response = $youtubeManager->getPlaylistsAPI();
        $htmlListItems = parseRsShowPlayLists($response);

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


<?php


//Abs2
function updatePlaylist($youtubeManager)
{
    global $db_host, $db_user, $db_pass, $database;
    $youtubeVideos = array();
    $nextPageToken = "";

    do {
        $response = $youtubeManager->getPlaylistItemsAPI($nextPageToken, $_GET['idPlaylist']);
        $youtubeVideos += parseRsGetVideosDic($response);
        $nextPageToken = $response['nextPageToken'];
    } while ($nextPageToken <> '');

    $DBManager = new databaseManager($db_host, $db_user, $db_pass, $database);
    $DBManager->renewPlaylistDDBB($_GET['idPlaylist'], $youtubeVideos);
}

//Abs 3
function parseRsGetVideosDic($response)
{
    $dictionary = [];

    foreach ($response['items'] as $itemRS) {
        $dictionary[$itemRS['snippet']['resourceId']['videoId']] = $itemRS['snippet']['title'];
    }
    return $dictionary;
}

//Abs 2
function parseRsShowPlayLists($response)
{
    $buffer = "";
    $okIcon = "";
    foreach ($response['items'] as $itemRS) {
        $okIcon = "";
        if (!empty($_GET['idPlaylist']) &&  $itemRS['id'] == $_GET['idPlaylist']) {
            $okIcon = "<span class=\"glyphicon glyphicon-ok\"></span>";
        }

        $buffer .= sprintf('
<li>
    <a class=w3-btn  href=getDeletedVideos.php?idPlist=%s ><b class="w3-wide">%s</b></a>
    <a href="index.php?idPlaylist=%s" class="w3-button w3-green"> Update %s</a>    
</li>'
            , $itemRS['id'], $itemRS['snippet']['localized']['title'], $itemRS['id'], $okIcon);
    }

    return $buffer;
}

//USO Y REFRESCO DE TOKENS MUY UTIL https://developers.google.com/youtube/v3/guides/auth/server-side-web-apps
/*
 * Hay 3 casos de video No disponibles
 * 1 Video eliminado "Deleted video"
 * 2 Video privado "privete video"
 * 3 El video esta bloqueado en tu region, el titulo sigue estando pero pero sale como
 * contentDetails->regionRestriction->bloqued
 */
?>

