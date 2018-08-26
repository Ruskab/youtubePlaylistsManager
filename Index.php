<?php
include_once "vendor/autoload.php";
include("mysql_ddbb/config.php");
include("mysql_ddbb/bbdd_param.php");
include("youtube_params.php");
include("apiAutentification.php");
include("functions.php");
include("youtube/youtubeManager.php");
include("mysql_ddbb/databaseManager.php");


//USO Y REFRESCO DE TOKENS MUY UTIL https://developers.google.com/youtube/v3/guides/auth/server-side-web-apps

/*
 * Hay 3 casos de video No disponibles
 * 1 Video eliminado "Deleted video"
 * 2 Video privado "privete video"
 * 3 El video esta bloqueado en tu region, el titulo sigue estando pero pero sale como
 * contentDetails->regionRestriction->bloqued
 */
//$youtubeManager->client->setAccessType('offline');
//$youtubeManager->client->setApprovalPrompt('force');

function parseRsGetVideosDic($response)
{
    $dictionary = [];

    foreach ($response['items'] as $itemRS) {
        $dictionary[$itemRS['snippet']['resourceId']['videoId']] = $itemRS['snippet']['title'];
    }
    return $dictionary;
}

function parseRsShowPlayLists($response)
{
    $buffer = "";
    foreach ($response['items'] as $itemRS) {
        $buffer .= sprintf('
<li>
    <a class=w3-btn  href=getDeletedVideos.php?Plist=%s ><b class="w3-wide">%s</b></a>
    <a href="index.php?idPlaylist=%s" class="w3-button w3-green">Update</a>    
</li>', $itemRS['id'], $itemRS['snippet']['localized']['title'], $itemRS['id']);
    }

    return $buffer;
}

function updatePlaylist($youtubeManager)
{
    global $db_host, $db_user, $db_pass, $database;
    $youtubeVideos = array();
    $nextPageToken = "";

    do {
        $response = $youtubeManager->getPlaylistItemsAPI($nextPageToken);
        $youtubeVideos += parseRsGetVideosDic($response);
        $nextPageToken = $response['nextPageToken'];
    } while ($nextPageToken <> '');

    $DBManager = new databaseManager($db_host, $db_user, $db_pass, $database);
    $DBManager->renewPlaylistDDBB($_GET['idPlaylist'], $youtubeVideos);
}

session_start();
$youtubeManager = new youtubeManager($OAUTH2_CLIENT_ID, $OAUTH2_CLIENT_SECRET);
$youtubeManager->createUserSesionAndExpireTime();
$youtubeManager->checkTokenExpire();

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
        $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
            htmlspecialchars($e->getMessage()));
    } catch (Google_Exception $e) {
        $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
            htmlspecialchars($e->getMessage()));
    }

    $_SESSION['token'] = $youtubeManager->client->getAccessToken();

} else {
    $htmlBody = showAuthorizationAlert($youtubeManager->client);
}
//Estructura de la pagina principal
include("includes/bodyPage.php");
?>

