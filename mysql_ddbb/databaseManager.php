<?php
/**
 * Created by PhpStorm.
 * User: ilyak
 * Date: 26/08/2018
 * Time: 10:19
 */

include("includes/constants.php");

class databaseManager
{
    public $servername;
    public $username;
    public $password;
    public $database;
    public $connection;

    public function __construct($serverName, $username, $password, $database)
    {
        $this->servername = $serverName;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
    }

    function renewPlaylistDDBB($idPlaylist, $videos)
    {
        $this->create_connection();

        //make query
        $query = sprintf("DELETE FROM videos WHERE idPlaylist = '%s'", $idPlaylist);
        $this->delete_data($query);

        //hacer la query
        $query = "INSERT IGNORE INTO videos (id_video, titulo, idPlaylist) VALUES";
        foreach ($videos as $clave => $valor) {
            $query .= sprintf("('%s','%s','%s'),", $clave, $this->connection->real_escape_string($valor), $idPlaylist);
        }
        //remove final ,
        $query = rtrim($query, ", ");
        $query .= ";";

        $this->insert_data($query);

        $this->close_connection();
    }

    function renewVideoInDDBB($newIdVideo, $oldIdvideo, $playlistId, $newVideoTitle){
        $this->create_connection();
        $query = sprintf("DELETE FROM videos WHERE id_video = '%s'", $oldIdvideo);
        $this->delete_data($query);

        $query = sprintf("INSERT INTO videos (id_video, titulo, idPlaylist) VALUES ('%s','%s','%s');",
            $newIdVideo, $this->connection->real_escape_string($newVideoTitle), $playlistId);

    return $this->insert_data($query);

        $this->close_connection();
    }

    private function create_connection()
    {
// Create connection
        $this->connection = new mysqli($this->servername, $this->username, $this->password, $this->database);
        // Check connection
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        echo "Connected successfully";
    }

    private function close_connection()
    {
        $this->connection->close();
    }

    function insert_data($query)
    {
        if ($this->connection->query($query) === TRUE) {
            echo "New record created successfully";
        } else {
            echo "Error: " . $query . "<br>" . $this->connection->error;
        }
    }

    function delete_data($query)
    {
        if ($this->connection->query($query) === TRUE) {
            echo "Record deleted successfully";
        } else {
            echo "Error deleting record: " . $this->connection->error;
        }
    }

    function select_data($query)
    {
        $registro = array();

        $this->create_connection();
        $results = $this->connection->query($query);
        if (mysqli_num_rows($results) == 0)
            array_push($registro, "No hay datos");
        else { //si hay, meterlos en un array
            for ($i = 0; $registro[$i] = mysqli_fetch_assoc($results); $i++) ;
            array_pop($registro);

        }
        $this->close_connection();
        return $registro;

    }



}