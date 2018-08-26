<?php
include_once "vendor/autoload.php";
include("mysql_ddbb/config.php");
include("mysql_ddbb/bbdd_param.php");
include("youtube_params.php");
include("apiAutentification.php");
include("functions.php");


function buildRqInsertVidedPlayList($videoId, $playListId, &$playlistItem)
{
    $resourceId = new Google_Service_YouTube_ResourceId();
    $resourceId->setVideoId($videoId);
    $resourceId->setKind('youtube#video');

    $playlistItemSnippet = new Google_Service_YouTube_PlaylistItemSnippet();
    $playlistItemSnippet->setPlaylistId($playListId);
    $playlistItemSnippet->setResourceId($resourceId);

    $playlistItem = new Google_Service_YouTube_PlaylistItem();
    $playlistItem->setSnippet($playlistItemSnippet);
}

session_start();
//Inicializamos el Cliente y YouTube API Service
initGoogleClientAndYoutubeService($client, $redirect, $youtube);
//Si estamos en la ventana de Autorizacion
createUserSesionAndExpireTime($client, $redirect);
//Check if token expired
checkTokenExpire($client);

// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
    try {
        if (!empty($_GET['vdId']) && !empty($_GET['plId']) && !empty($_GET['LongIdVideo'])) {

            //Crear la Request
            buildRqInsertVidedPlayList($_GET['vdId'], $_GET['plId'], $playlistItem);
            //Realizar la peticiones a YouTube
            $playlistItemResponse = $youtube->playlistItems->insert('snippet,contentDetails', $playlistItem, array());
            $DeleteVideoResponse = $youtube->playlistItems->delete($_GET['LongIdVideo'], array());

            //conexion a la bbdd
            $query = sprintf("DELETE FROM videos WHERE id_video = '%s'", $_GET['oldId']);
            $stateQuery = makeQueryDDBB($query);

            $conection = conexion_mysqli($db_host, $db_user, $db_pass, $database);
            $query = sprintf("INSERT INTO videos (id_video, titulo, idPlaylist) VALUES ('%s','%s','%s');",
                $_GET['vdId'], mysqli_real_escape_string($conection,
                    $playlistItemResponse['snippet']['title']), $_GET['plId']);

            $stateQuery = makeQueryDDBB($query);

            if ($stateQuery == "success") {
                header("Location:index.php");
            } else {
                $htmlBody = sprintf("
                    <div class=\"w3-panel w3-pale-blue w3-leftbar w3-rightbar w3-border-blue\">
                    <h3>%s</h3></div>", $stateQuery);
            }
        }

    } catch (Google_Service_Exception $e) {
        $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
            htmlspecialchars($e->getMessage()));
    } catch (Google_Exception $e) {
        $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
            htmlspecialchars($e->getMessage()));
    }
    $_SESSION['token'] = $client->getAccessToken();
} else {
    $htmlBody = showAuthorizationAlert($client);
}

//Estructura de la pagina principal
include("includes/bodyPage.php");
?>
