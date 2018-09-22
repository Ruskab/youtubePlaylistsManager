<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 20/09/2018
 * Time: 20:41
 */

include "playlistDAO.php";

class playlistDAOImp extends YoutubeServiceAPI implements playlistDAO
{

    public function __construct()
    {
        $this->initGoogleClient();
    }

    function getAuthUri()
    {
        $this->uploadAccessToken();
        $this->genAuthorizationUri();
        return $this->auth_uri;
    }

    function userHasAccess()
    {
        if (isset($_GET['code'])) {
            $this->genAccessTokenWithCode();
            $this->redirectToMainPage();
        }


        return $this->checkHasAccessToken();
    }

    function getAuthorizedUserPlaylists()
    {
        return $this->youtubeServiceAPI->playlists->listPlaylists('snippet', array(
            'mine' => true,
            'maxResults' => 50));
    }

    function getPlaylistVideos($playlistID)
    {
        $nextPageToken = "";
        $videosList = array();

        do {
            $responseAPI = $this->youtubeServiceAPI->playlistItems->listPlaylistItems('id,snippet,contentDetails,status', array(
                'playlistId' => $playlistID,
                'maxResults' => 50,
                'pageToken' => $nextPageToken));

            foreach ($responseAPI->items as $videoitem) {
                $videosList[] = $videoitem;
            }
            $nextPageToken = $responseAPI->nextPageToken;
        } while ($responseAPI->nextPageToken <> '');

        return $videosList;

    }

    function getVideosByIDs($requestIDs){

            return $responseAPI = $this->youtubeServiceAPI->videos->listVideos('snippet,contentDetails,statistics', array(
            'id' => $requestIDs));
    }

    function getVideosByTitle($title){

        return $this->youtubeServiceAPI->search->listSearch('snippet', array(
            'q' => $title,
            'maxResults' => 5));
    }

    function setVideoInPlaylistAPI(Video $video)
    {
        $resourceId = new Google_Service_YouTube_ResourceId();
        $resourceId->setVideoId($video->getId());
        $resourceId->setKind('youtube#video');

        $playlistItemSnippet = new Google_Service_YouTube_PlaylistItemSnippet();
        $playlistItemSnippet->setPlaylistId($video->getPlaylist());
        $playlistItemSnippet->setResourceId($resourceId);
        $playlistItem = new Google_Service_YouTube_PlaylistItem();
        $playlistItem->setSnippet($playlistItemSnippet);

        return $this->youtubeServiceAPI->playlistItems->insert('snippet,contentDetails', $playlistItem, array());
    }

    function deleteVideoInPlaylist($IDVideo_IDplaylist){
        return $this->youtubeServiceAPI->playlistItems->delete($IDVideo_IDplaylist, array());
    }






}