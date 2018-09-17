<?php
/**
 * Created by PhpStorm.
 * User: ilyak
 * Date: 28/08/2018
 * Time: 09:55
 */
//Data Transfer Object
Class video
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


    //abs 2
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