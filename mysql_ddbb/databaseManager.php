<?php
/**
 * Created by PhpStorm.
 * User: ilyak
 * Date: 26/08/2018
 * Time: 10:19
 */

class databaseManager
{

    public $servername;
    public $username;
    public $password;
    public $database;
    public $connection;

    public function __construct($serverName, $username, $password,$database)
    {
        $this->servername = $serverName;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
    }

    function create_connection()
    {
// Create connection
        $this->connection = new mysqli($this->servername, $this->username, $this->password,$this->database);
        // Check connection
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        echo "Connected successfully";
    }

    function close_connection()
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

    function renewPlaylistDDBB($idPlaylist, $videos){
        $this->create_connection();

        //make query
        $query = sprintf("DELETE FROM videos WHERE idPlaylist = '%s'",$idPlaylist);
        $this->delete_data($query);

        //hacer la query
        $query = "INSERT IGNORE INTO videos (id_video, titulo, idPlaylist) VALUES";
        foreach ($videos as $clave => $valor){
                $query .= sprintf("('%s','%s','%s'),",$clave,$this->connection->real_escape_string($valor),$idPlaylist);
        }
        //remove final ,
        $query = rtrim($query, ", ");
        $query .= ";";

        $this->insert_data($query);

        $this->close_connection();
    }




}