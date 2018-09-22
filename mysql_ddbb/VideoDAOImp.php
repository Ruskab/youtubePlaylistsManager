<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 20/09/2018
 * Time: 16:30
 */
include dirname(__FILE__)."/DBConnection.php";
include dirname(__FILE__)."/VideoDAO.php";

class VideoDAOImp extends DBConnection implements VideoDAO
{
    function insertVideo(Video $video)
    {
        $this->createConnection();

        $query = sprintf("INSERT INTO videos (id_video, titulo, idPlaylist) VALUES ('%s','%s','%s')",
            $video->getId(), $this->dbConnection->real_escape_string($video->getTitle()), $video->getPlaylist());

        if ($this->connection->query($query) === TRUE) {
            $this->closeConnection();

            return "New record created successfully";
        } else {
            $this->closeConnection();

            return $this->connection->error;
        }

    }

    function renewPlaylistVideos($idPlaylist, array $videos)
    {
        $query = sprintf("DELETE FROM videos WHERE idPlaylist = '%s'", $idPlaylist);
        $this->createConnection();
        $this->makeQuery($query);

        $query = "INSERT IGNORE INTO videos (id_video, titulo, idPlaylist) VALUES";
        foreach ($videos as $key => $value) {
            $query .= sprintf("('%s','%s','%s'),", $key, $this->filterSQLquery($value), $idPlaylist);
        }

        //remove final ,
        $query = rtrim($query, ", ");
        $query .= ";";
        $this->makeQuery($query);
        $this->closeConnection();
    }


    function deleteVideo($id)
    {
        $this->createConnection();

        $query = sprintf("DELETE FROM videos WHERE id_video = '%s'", $id);

        if ($this->connection->query($query) === TRUE) {
            $this->closeConnection();
            return "New record created successfully";
        } else {
            $this->closeConnection();
            return $this->connection->error;
        }

    }

    function fidByIdVideo($id)
    {

    }

    function FindByTitle($title)
    {

    }

    function GetVideosByPlaylistId($playlistID)
    {
        $videos = Array();

        $this->createConnection();
        $query = "SELECT * FROM videos WHERE idPlaylist = '" . $playlistID . "'";
        $result = $this->dbConnection->query($query);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $vd = new Video($row['id_video']);
                $vd->setTitle($row['titulo']);

                array_push($videos, $vd);
            }
        }

        $this->closeConnection();
        return $videos;
    }



    function makeQuery($query){
        $this->dbConnection->query($query);

    }

}