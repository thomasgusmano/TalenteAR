<?php
$host = "localhost"; 
$username = "root"; 
$password = ""; 
$database = "agenciatrabajo"; 

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Error al conectar a la base de talentos: " . mysqli_connect_error());
}
?>