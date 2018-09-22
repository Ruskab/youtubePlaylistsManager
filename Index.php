<?php

include dirname(__FILE__)."/functions.php";
include dirname(__FILE__) . "/youtube/Video.php";
include dirname(__FILE__)."/youtube/YoutubeServiceAPI.php";
include dirname(__FILE__)."/youtube/playlistDAOImp.php";
include dirname(__FILE__)."/mysql_ddbb/VideoDAOImp.php";



//$youtubeManager->client->setAccessType('offline');
//$youtubeManager->client->setApprovalPrompt('force');
session_start();

$htmlListItems = '';
$nextPageToken = '';
$htmlBody = '';
$logOutUri = sprintf('
  <form id="logout" method="POST" action="%s">
    <input type="hidden" name="logout" value="" />
    <input class="w3-btn" type="submit" value="Logout">
  </form>
',htmlspecialchars($_SERVER['PHP_SELF']));

try {

    // todo Revisar lo de csrf token
    if (isset($_REQUEST['logout'])) {
        unset($_SESSION['upload_token']);
    }

    //Data access object
    $playlistDao = new playlistDAOImp();

    $authUri = $playlistDao->getAuthUri();

    if (!empty($authUri)){
       $htmlBody= addAuthorizationPanelAlert($authUri);
    }

    $htmlListItems .= addPublicListItem();
    $htmlListItems .= addTopVideosListItem();

    //List User Playlists
    if ($playlistDao->userHasAccess())
    {
        $response = $playlistDao->getAuthorizedUserPlaylists();
        if (!empty($response)){
            $htmlListItems .= parseRsShowPlayLists($response);
        }

        // Si el usuario esta autorizado
        if (!empty($_GET['idPlaylist'])) {
            //updatePlaylist($service);
            $response = $playlistDao->getPlaylistVideos($_GET['idPlaylist']);
            $youtubeVideos = parseRsGetVideosDic($response);

            $videosDAO = new VideoDAOImp();
            $videosDAO->renewPlaylistVideos($_GET['idPlaylist'],$youtubeVideos);
        }
    }

} catch (Exception $e) {
    //addPanelWithErrorMessage($e->getMessage());
} catch (Google_Service_Exception $e) {
    $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
} catch (Google_Exception $e) {
    $htmlBody .= addPanelWithMessage(htmlspecialchars($e->getMessage()));
}
//Estructura de la pagina principal
include("includes/bodyPage.php");


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

function addTopVideosListItem()
{
    return sprintf('
        <li>
           <button onclick="document.getElementById(\'id02\').style.display=\'block\'"class="w3-red w3-btn"><b style="text-shadow:2px 2px 0 #444" class="w3-wide">GET TOP SONGS</b></button>                            
        </li>
        
        
        
 
    <div id="id02" class="w3-modal">        
        <div class="w3-modal-content">
          <header class="w3-container w3-red"> 
            <span onclick="document.getElementById(\'id02\').style.display=\'none\'" 
            class="w3-button w3-display-topright">&times;</span>
            <h2 style="text-shadow:2px 2px 0 #444"><b>Insert a playlist ID</b></h2>
          </header>                    
            <div class="w3-container w3-padding-16 ">                   
                <form class="w3-container" action="getMostViewedVideos.php">                         
                    <input class="w3-input w3-border " name="idPlist" placeholder="Playlist ID" type="text">
                    
                    <input class="w3-radio" type="radio" name="gender" value="10" checked>
                    <label>TOP 10</label>
                    <input class="w3-radio" type="radio" name="gender" value="25">
                    <label>TOP 25</label>
                    <input class="w3-radio" type="radio" name="gender" value="50">
                    <label>TOP 50</label>
                    
                    <button class="w3-btn w3-Dark-Gray w3-hover-red w3-block w3-border w3-large w3-margin-top">Analyze</button>                       
                </form>                
            </div>           
        </div>            
    </div>
        ');

    return $buffer;

}

//Abs 3
function parseRsGetVideosDic($response)
{
        $dictionary = [];

    foreach ($response as $itemRS) {
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

