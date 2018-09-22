<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 20/09/2018
 * Time: 16:32
 */
include "database_params.php";

class DBConnection
{
    protected  $dbConnection;

    private $servername;
    private $username;
    private $password;
    private $database;

    public function __construct()
    {
        global $db_host, $db_user, $db_pass, $database;
        $this->servername = $db_host;
        $this->username = $db_user;
        $this->password = $db_pass;
        $this->database = $database;
    }

    protected function createConnection(){
        $this->dbConnection = new mysqli($this->servername, $this->username, $this->password, $this->database);
    }

    protected function closeConnection(){
        $this->dbConnection->close();
    }

    protected function filterSQLquery($value){
        return $this->dbConnection->real_escape_string($value);
    }



}