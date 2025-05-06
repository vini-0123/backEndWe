<?php
$host = "turntable.proxy.rlwy.net";
$user = "root";
$pass = "XlMJWtluJUkgYTVvZFoUuOljSSsILboG";
$bd   = "railway";
$port = 52519;

$mysqli = new mysqli($host, $user, $pass, $bd, $port); 

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
} else {
    // echo "Banco Conectado com sucesso!";
}

?>
