<?php 
include("mysql_ddbb/bbdd_param.php");

function makeQueryDDBB($query){
    global $db_host, $db_user, $db_pass, $database;
    $conection = conexion_mysqli($db_host, $db_user, $db_pass, $database);

    if (mysqli_query($conection, $query)) {
        return "success";
    } else {
        return mysqli_error($conection);
    }
    mysqli_close($conection);

}


function getDataFromDatabase($query)
{
    global $db_host, $db_user, $db_pass, $database;
    $conection = conexion_mysqli($db_host, $db_user, $db_pass, $database);
    $results = mysqli_query($conection, $query);

    if (mysqli_num_rows($results) == 0)
        array_push($registro, "No hay datos");
    else { //si hay, meterlos en un array

        for($i = 0; $registro[$i] = mysqli_fetch_assoc($results); $i++) ;
        array_pop($registro);
    }
    mysqli_close($conection);
    return $registro;
}

?>
