<?php
$host = "localhost";
$user = "";
$pass = ""; 
$bd = "";

$mysqli = new mysqli($host, $user, $pass, $bd); 

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
?>