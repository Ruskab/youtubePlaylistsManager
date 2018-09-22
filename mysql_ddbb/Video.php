<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 22/09/2018
 * Time: 16:28
 */

class Video
{
    private $id;
    private $title;
    private $duration;
    private $playlist;
    public $idVideo_Playlist;
    public $views;

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

    public function setIdVideoPlaylist($value)
    {
        return $this->idVideo_Playlist= $value;
    }

}