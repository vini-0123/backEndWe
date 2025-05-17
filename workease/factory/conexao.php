<?php
$mysqli = new mysqli("localhost", "root", "", "workease");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");
?>
