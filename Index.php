<?php
include_once "vendor/autoload.php";
include("functions.php");
include("youtube/video.php");
include("mysql_ddbb/databaseManager.php");


//$youtubeManager->client->setAccessType('offline');
//$youtubeManager->client->setApprovalPrompt('force');
session_start();
try {
    $youtubeManager = initYoutubeService($OAUTH2_CLIENT_ID, $OAUTH2_CLIENT_SECRET);

// Si el usuario esta autorizado
    if ($youtubeManager->hasAccessToken()) {
        if (!empty($_GET['idPlaylist'])) {
            updatePlaylist($youtubeManager);
        }
        //Show option Inspect public Playlist
        $htmlListItems .= addPublicListItem();

        //List User Playlists
        $response = $youtubeManager->getPlaylistsAPI();
        $htmlListItems .= parseRsShowPlayLists($response);


        $_SESSION['token'] = $youtubeManager->getAccessToken();

    } else {
        $urlAuth = generateAuthUrlSetState($youtubeManager->client);
        $htmlBody = addAuthorizationPanelAlert($urlAuth);
    }

} catch (Exception $e) {
    addPanelWithErrorMessage($e->getMessage());
} catch (Google_Service_Exception $e) {
    $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
} catch (Google_Exception $e) {
    $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
}
//Estructura de la pagina principal
include("includes/bodyPage.php");


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

function addPublicListItem()
{
    return sprintf('
        <li>
        <button onclick="document.getElementById(\'id01\').style.display=\'block\'"class="w3-red w3-btn"><b style="text-shadow:2px 2px 0 #444" class="w3-wide">Analyze by playlist ID</b></button>                   
        </li>
        
    <div id="id01" class="w3-modal">        
        <div class="w3-modal-content">
          <header class="w3-container w3-red"> 
            <span onclick="document.getElementById(\'id01\').style.display=\'none\'" 
            class="w3-button w3-display-topright">&times;</span>
            <h2 style="text-shadow:2px 2px 0 #444"><b>Insert a playlist ID</b></h2>
          </header>                    
            <p>                
            <div class="w3-container">                   
                <form class="w3-container" action="getBlockedVideos.php">             
                    <input class="w3-input w3-border " name="idPlist" placeholder="Playlist ID" type="text"></div>                            
                    <button class="w3-btn w3-Dark-Gray w3-hover-red w3-block w3-border w3-large">Analyze</button>                       
                </form>                
            </div>            
            </p>                                             
    </div>
        ');

    return $buffer;

}

//Abs 2
function addPanelInspectPublicPlaylist()
{
    $htmlItem = "";

    $htmlItem = sprintf("

");
    return $htmlItem;
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
        if (!empty($_GET['idPlaylist']) && $itemRS['id'] == $_GET['idPlaylist']) {
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

