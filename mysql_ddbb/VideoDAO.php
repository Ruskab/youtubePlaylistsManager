<?php


interface VideoDAO
{
function insertVideo(Video $video);

function renewPlaylistVideos($idPlaylist ,array $videos);

function deleteVideo($id);

function fidByIdVideo($id);

function FindByTitle($title);

function GetVideosByPlaylistId($idPlaylist);



}