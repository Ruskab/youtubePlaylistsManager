<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 20/09/2018
 * Time: 20:39
 */

interface playlistDAO
{
    function getAuthUri();

    function userHasAccess();

    function getAuthorizedUserPlaylists();

    function getPlaylistVideos($playlistID);

    function getVideosByIDs($requestIDs);

    function getVideosByTitle($title);

    function setVideoInPlaylistAPI(Video $video);

    function deleteVideoInPlaylist($IDVideo_IDplaylist);

}