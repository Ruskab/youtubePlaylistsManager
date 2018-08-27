<?php

include_once "vendor/autoload.php";
include("youtube/youtube_params.php");

class youtubeManager
{
    public $client;
    public $redirect;
    public $youtube;

    public function __construct($client_id, $client_secret)
    {
        $this->client = new Google_Client();
        $this->client->setClientId($client_id);
        $this->client->setClientSecret($client_secret);
        $this->client->setScopes('https://www.googleapis.com/auth/youtube');
        $this->redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
            FILTER_SANITIZE_URL);
        $this->client->setRedirectUri($this->redirect);
        $this->youtube = new Google_Service_YouTube($this->client);
    }

    /**
     * @return Google_Client
     */
    public function getAccessToken()
    {
        return $this->client->getAccessToken();
    }

    function checkTokenExpire()
    {
//Siempre que se refresque la pagina agregar el token hasta que expire
        if (isset($_SESSION['token']) && time() < $_SESSION['access_token_expiry']) {
            $this->client->setAccessToken($_SESSION['token']);
        }
    }

    public function createUserSesionAndExpireTime()
    {
        if (isset($_GET['code'])) {
            //comparamos como string la sesion
            if (strval($_SESSION['state']) !== strval($_GET['state'])) {
                die('The session state did not match.');
            }
            $this->client->authenticate($_GET['code']);
            $_SESSION['token'] = $this->client->getAccessToken();
            $_SESSION['access_token_expiry'] = time() + $this->client->getAccessToken()['expires_in'];
            header('Location: ' . $this->redirect);
        }
    }

    function showAuthorizationAlert()
    {
        $state = mt_rand();
        $this->client->setState($state);
        $_SESSION['state'] = $state;
        $authUrl = $this->client->createAuthUrl();
        $htmlBody = sprintf("<div class=\"w3-panel w3-pale-blue w3-leftbar w3-rightbar w3-border-blue\">
            <h3>Authorization Required</h3>
            <p>You need to <a href=\"%s\">authorize access</a> before proceeding.<p>
            </div>", $authUrl);

        return $htmlBody;
    }

    function hasAccessToken()
    {
        if ($this->client->getAccessToken())
            return true;
        else
            return false;
    }

    function getPlaylistItemsAPI($nextPageToken, $idPlaylist)
    {
        return $this->youtube->playlistItems->listPlaylistItems('id,snippet,contentDetails,status', array(
            'playlistId' => $idPlaylist,
            'maxResults' => 50,
            'pageToken' => $nextPageToken));
    }

    function getPlaylistsAPI()
    {
        return $this->youtube->playlists->listPlaylists('snippet', array(
            'mine' => true,
            'maxResults' => 50));
    }

    function getVideosByTitleAPI($videoTitle)
    {
        return $this->youtube->search->listSearch('snippet', array(
            'q' => $videoTitle,
            'maxResults' => 5));
    }

    function getVideosByIdAPI($videoIds)
    {
        return $this->youtube->videos->listVideos('snippet,contentDetails', array(
            'id' => $videoIds));
    }

    function setVideoInPlaylistAPI(video $video)
    {
        $resourceId = new Google_Service_YouTube_ResourceId();
        $resourceId->setVideoId($video->getId());
        $resourceId->setKind('youtube#video');

        $playlistItemSnippet = new Google_Service_YouTube_PlaylistItemSnippet();
        $playlistItemSnippet->setPlaylistId($video->getPlaylist());
        $playlistItemSnippet->setResourceId($resourceId);

        $playlistItem = new Google_Service_YouTube_PlaylistItem();
        $playlistItem->setSnippet($playlistItemSnippet);

        return $this->playlistItems->insert('snippet,contentDetails', $playlistItem, array());
    }

    function deleteVideoInPlaylistAPI($longVideoID)
    {
        return $this->playlistItems->delete($longVideoID, array());
    }

}

Class video
{
    private $id;
    private $title;
    private $duration;
    private $playlist;
    private $idVideo_Playlist;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title): void
    {
        $this->title = $title;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setDuration($duration): void
    {
        $this->duration = $duration;
    }

    public function getPlaylist()
    {
        return $this->playlist;
    }

    public function setPlaylist($playlist): void
    {
        $this->playlist = $playlist;
    }

    public function getIdVideoPlaylist()
    {
        return $this->idVideo_Playlist;
    }

    public function setIdVideoPlaylist($idVideo_Playlist): void
    {
        $this->idVideo_Playlist = $idVideo_Playlist;
    }

    public function addVideoInfoFromGET()
    {
        if (!empty($_GET)) {
            if (!empty($_GET['title'])) $this->setTitle($_GET['title']);
            if (!empty($_GET['idPlist'])) $this->setPlaylist($_GET['idPlist']);
            if (!empty($_GET['idVideoPlist'])) $this->setIdVideoPlaylist($_GET['idVideoPlist']);
            if (!empty($_GET['duration'])) $this->setDuration($_GET['duration']);
        }
    }

}